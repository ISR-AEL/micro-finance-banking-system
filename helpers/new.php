
<?php
// backend/savings_transaction.php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

require_once "../config/database.php";
$conn = db();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// ---- Input ----
$client_id    = intval($_POST['client_id'] ?? 0);
$type         = trim($_POST['type'] ?? '');
$amount       = floatval($_POST['amount'] ?? 0);
$txn_date_raw = trim($_POST['date'] ?? ''); // deposits should pass this; withdrawals forced to today
$today        = date("Y-m-d");

if ($client_id <= 0 || !in_array($type, ['deposit','withdrawal'], true)) {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit;
}

// ---- Fetch savings account ----
$stmt = $conn->prepare("SELECT * FROM savings WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$savings = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$savings) {
    echo json_encode(["status" => "error", "message" => "Savings account not found."]);
    exit;
}

$savings_id   = (int)$savings['id'];
$daily_amount = (float)$savings['daily_amount'];

// ---- Helpers ----
function sum_txn(mysqli $conn, int $sid, string $type, ?string $max_date = null): float {
    if ($max_date) {
        $q = "SELECT COALESCE(SUM(amount),0) AS s
              FROM savings_transactions
              WHERE savings_id=? AND type=? AND payment_date<=?";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("iss", $sid, $type, $max_date);
    } else {
        $q = "SELECT COALESCE(SUM(amount),0) AS s
              FROM savings_transactions
              WHERE savings_id=? AND type=?";
        $stmt = $conn->prepare($q);
        $stmt->bind_param("is", $sid, $type);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($row['s'] ?? 0);
}

// last deposit date
$stmt = $conn->prepare("SELECT MAX(payment_date) AS last_dep FROM savings_transactions WHERE savings_id=? AND type='deposit'");
$stmt->bind_param("i", $savings_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$last_deposit_date = $row && $row['last_dep'] ? $row['last_dep'] : null;

// =====================================================
// DEPOSIT
// =====================================================
if ($type === 'deposit') {
    // must equal fixed daily amount
    if ($amount != $daily_amount) {
        echo json_encode([
            "status"  => "error",
            "message" => "Deposit must equal fixed daily amount: ₵{$daily_amount}"
        ]);
        exit;
    }

    if ($txn_date_raw === '') {
        echo json_encode(["status" => "error", "message" => "No deposit date selected."]);
        exit;
    }
    $d = date_create($txn_date_raw);
    if (!$d) {
        echo json_encode(["status" => "error", "message" => "Invalid deposit date."]);
        exit;
    }
    $txn_date = date_format($d, "Y-m-d");

    // prevent duplicate deposit for same date
    $stmt = $conn->prepare("SELECT id FROM savings_transactions WHERE savings_id=? AND payment_date=? AND type='deposit' LIMIT 1");
    $stmt->bind_param("is", $savings_id, $txn_date);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        echo json_encode(["status" => "error", "message" => "Deposit for this date already exists."]);
        exit;
    }
    $stmt->close();

    $conn->begin_transaction();
    try {
        // (1) Ensure monthly admin cut (1 day) ONCE per calendar month
        $month_ym = date('Y-m', strtotime($txn_date));
        $stmt = $conn->prepare("
            SELECT id FROM savings_transactions
            WHERE savings_id=? AND type='admin_cut' AND DATE_FORMAT(payment_date,'%Y-%m')=?
            LIMIT 1
        ");
        $stmt->bind_param("is", $savings_id, $month_ym);
        $stmt->execute();
        $hasCut = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$hasCut) {
            $cutType = 'admin_cut';
            $stmt = $conn->prepare("
                INSERT INTO savings_transactions (savings_id, payment_date, amount, type, created_at)
                VALUES (?,?,?,?,NOW())
            ");
            $stmt->bind_param("isds", $savings_id, $txn_date, $daily_amount, $cutType);
            $stmt->execute();
            $stmt->close();
        }

        // (2) Insert the deposit
        $stmt = $conn->prepare("
            INSERT INTO savings_transactions (savings_id, payment_date, amount, type, created_at)
            VALUES (?,?,?,?,NOW())
        ");
        $stmt->bind_param("isds", $savings_id, $txn_date, $amount, $type);
        $stmt->execute();
        $stmt->close();

        // (3) Update savings summary
        $stmt = $conn->prepare("
            UPDATE savings
            SET total_days_paid = total_days_paid + 1,
                last_payment_date = ?
            WHERE id = ?
        ");
        $stmt->bind_param("si", $txn_date, $savings_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Deposit recorded successfully."]);
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to record deposit."]);
        exit;
    }
}

// =====================================================
// WITHDRAWAL
// =====================================================
if ($type === 'withdrawal') {
    // enforce present-day withdrawals only
    $txn_date = $today;

    // cannot be before last deposit date (same day OK)
    if ($last_deposit_date && $txn_date < $last_deposit_date) {
        echo json_encode([
            "status"  => "error",
            "message" => "Withdrawal date cannot be before last deposit date ({$last_deposit_date})."
        ]);
        exit;
    }

    if ($amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid withdrawal amount."]);
        exit;
    }

    // eligible = deposits - admin_cuts - withdrawals (as of today)
    $totalDeposits  = sum_txn($conn, $savings_id, 'deposit',    $txn_date);
    $totalCuts      = sum_txn($conn, $savings_id, 'admin_cut',  $txn_date);
    $totalWithdrawn = sum_txn($conn, $savings_id, 'withdrawal', $txn_date);

    $eligible = $totalDeposits - $totalCuts - $totalWithdrawn;
    if ($eligible < 0) $eligible = 0.0;

    if ($eligible <= 0) {
        echo json_encode(["status" => "error", "message" => "No amount available for withdrawal yet."]);
        exit;
    }

    if ($amount > $eligible) {
        $eligible_fmt = number_format($eligible, 2);
        echo json_encode(["status" => "error", "message" => "Max withdrawable is ₵{$eligible_fmt}"]);
        exit;
    }

    // record withdrawal
    $stmt = $conn->prepare("
        INSERT INTO savings_transactions (savings_id, payment_date, amount, type, created_at)
        VALUES (?,?,?,?,NOW())
    ");
    $stmt->bind_param("isds", $savings_id, $txn_date, $amount, $type);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["status" => "success", "message" => "Withdrawal recorded successfully."]);
    exit;
}

// Fallback
echo json_encode(["status" => "error", "message" => "Invalid transaction type."]);










