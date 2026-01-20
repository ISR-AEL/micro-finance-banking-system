<?php
// dashboard/ajax/load_table.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error", "message"=>"Unauthorized"]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$conn = db();

// --- Tables and numeric columns ---
$all_tables = [
    'admin_revenue' => ['one_day_cut'],
    'admin_withdrawals' => ['amount'],
    'audit_log' => [],
    'clients' => [],
    'savings' => ['daily_amount'],
    'savings_plans' => [],
    'savings_transactions' => ['amount'],
    'settings' => [],
    'transactions' => ['amount'],
    'users' => []
];

// --- Get POST data ---
$selected_tables = $_POST['tables'] ?? [];
$start_date = $_POST['start_date'] ?? '';
$end_date   = $_POST['end_date'] ?? '';

$response = [];

if (empty($selected_tables)) {
    echo json_encode(["status"=>"error","message"=>"No tables selected"]);
    exit;
}

foreach ($selected_tables as $table) {
    if (!isset($all_tables[$table])) continue;

    $query = "SELECT * FROM `$table`";

    // Apply date filter only for tables that have created_at
    $date_tables = ['audit_log','transactions','savings_transactions','admin_withdrawals','admin_revenue'];
    if (!empty($start_date) && !empty($end_date) && in_array($table, $date_tables)) {
        $query .= " WHERE DATE(created_at) BETWEEN '".$conn->real_escape_string($start_date)."' AND '".$conn->real_escape_string($end_date)."'";
    }

    $query .= " ORDER BY id ASC";
    $res = $conn->query($query);

    $data = [];
    $fields = [];
    if ($res && $res->num_rows > 0) {
        $fields = array_map(function($f){ return $f->name; }, $res->fetch_fields());
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $response[$table] = [
        "fields" => $fields,
        "rows"   => $data
    ];
}

echo json_encode(["status"=>"success","tables"=>$response]);
