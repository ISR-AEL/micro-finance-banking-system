<?php
// dashboard/audit_log.php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$conn = db();

// Initialize messages
$success = "";
$error = "";

// --- Handle Backup Action ---
if (isset($_POST['backup_now'])) {
    $backupDir = __DIR__ . "/backups";
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    $backupFile = $backupDir . "/backup_" . date('Ymd_His') . ".sql";

    // Database credentials
    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = ''; // Fill your MySQL password if any
    $dbName = 'banking_system';

    // Detect OS and set mysqldump path
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $mysqldump = "C:/xampp/mysql/bin/mysqldump.exe"; // adjust if needed
    } else {
        $mysqldump = "/usr/bin/mysqldump"; // typical Linux path
    }

    if (!file_exists($mysqldump)) {
        $error = "mysqldump not found at: $mysqldump";
    } else {
        // Build command
        $command = "\"$mysqldump\" --host=$dbHost --user=$dbUser --password=$dbPass $dbName --routines --events --single-transaction --quick --lock-tables=false > \"$backupFile\" 2>&1";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            // Log backup in audit table
            $stmt = $conn->prepare("INSERT INTO audit_log (action, performed_by, created_at) VALUES (?, ?, NOW())");
            $action = "Database backup: " . basename($backupFile);
            $performed_by = $_SESSION['username'] ?? 'Unknown';
            $stmt->bind_param("ss", $action, $performed_by);
            $stmt->execute();
            $stmt->close();

            $success = "Backup successful: " . basename($backupFile);
        } else {
            $error = "Backup failed! Return code: $return_var\nOutput:\n" . implode("\n", $output);
        }
    }
}

// --- Fetch Audit Logs ---
$logs_query = $conn->query("SELECT * FROM audit_log ORDER BY created_at DESC");
$logs = [];
while ($row = $logs_query->fetch_assoc()) {
    $logs[] = $row;
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="content">
    <h2>Audit Log</h2>

    <?php if ($success): ?>
        <div style="color:green; margin-bottom:10px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="color:red; margin-bottom:10px; white-space: pre-wrap;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Backup Button -->
    <form method="post" style="margin-bottom:20px;">
        <button type="submit" name="backup_now" style="padding:10px 20px; background:#2980b9; color:white; border:none; border-radius:5px; cursor:pointer;">
            <i class="fas fa-cloud-upload-alt"></i> Backup Now
        </button>
    </form>

    <!-- Audit Log Table -->
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; border-collapse: collapse; background:white;">
        <thead>
            <tr style="background:#2980b9; color:black;">
                <th>ID</th>
                <th>Action</th>
                <th>Performed By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" style="text-align:center;">No audit logs found.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['id']) ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['performed_by']) ?></td>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
