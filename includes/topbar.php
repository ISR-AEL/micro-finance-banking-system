<?php
require_once __DIR__ . '/../config/database.php';
$conn = db();

// Fetch system name from settings
$system_name = 'Banking System'; // default
$res = $conn->query("SELECT system_name FROM settings LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $system_name = $row['system_name'];
}
?>

<div class="topbar">
    <div class="logo"><?= htmlspecialchars($system_name) ?></div>
    <div class="user-info">
        Logged in as: <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
    </div>
</div>
