<?php
// dashboard/index.php
session_start();
if (empty($_SESSION['user_id'])) { 
    header('Location: ../index.php'); 
    exit; 
}

require_once '../config/database.php';
$conn = db();

// --- Widgets Queries ---
// Total clients
$total_clients_query = $conn->query("SELECT COUNT(*) AS total_clients FROM clients");
$total_clients = $total_clients_query->fetch_assoc()['total_clients'];

// Total transactions today (SUM of amount not COUNT)
$today = date('Y-m-d');
$total_transactions_today_query = $conn->query("SELECT IFNULL(SUM(amount),0) AS total_transactions_today FROM transactions WHERE transaction_date = '$today'");
$total_transactions_today = $total_transactions_today_query->fetch_assoc()['total_transactions_today'];

// Total savings clients (clients who have at least one savings record)
$total_savings_clients_query = $conn->query("SELECT COUNT(DISTINCT client_id) AS total_savings_clients FROM savings");
$total_savings_clients = $total_savings_clients_query->fetch_assoc()['total_savings_clients'];

// Total savings transactions today (from savings_transactions table)
$total_savings_today_query = $conn->query("SELECT IFNULL(SUM(amount),0) AS total_savings_today FROM savings_transactions WHERE payment_date = '$today'");
$total_savings_today = $total_savings_today_query->fetch_assoc()['total_savings_today'];

// --- Charts Queries ---
// Transactions last 7 days (sum amounts instead of count)
$transactions_chart_query = $conn->query("
    SELECT transaction_date AS day, SUM(amount) AS total 
    FROM transactions 
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY day 
    ORDER BY day
");
$transactions_labels = [];
$transactions_data = [];
while($row = $transactions_chart_query->fetch_assoc()){
    $transactions_labels[] = $row['day'];
    $transactions_data[] = $row['total'];
}

// Savings last 7 days
$savings_chart_query = $conn->query("
    SELECT payment_date AS day, SUM(amount) AS total 
    FROM savings_transactions 
    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY day 
    ORDER BY day
");
$savings_labels = [];
$savings_data = [];
while($row = $savings_chart_query->fetch_assoc()){
    $savings_labels[] = $row['day'];
    $savings_data[] = $row['total'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f4f4; }
        
        /* Top Navbar */
        .topbar { width: 100%; height: 60px; background: #2980b9; color: #ecf0f1; position: fixed; top: 0; left: 0; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 1000; }
        .topbar .logo { font-weight: bold; font-size: 20px; }
        .topbar .user-info { font-size: 14px; margin-right: 5em; }

        /* Content */
        .content { margin-left: 220px; margin-top: 70px; padding: 20px; transition: all 0.3s; }
        .widgets { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .widget {
            flex: 1 1 220px;
            background: linear-gradient(135deg, #fff, #ecf0f1);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .widget:hover { transform: translateY(-5px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        .widget i { font-size: 36px; margin-bottom: 10px; color: #2980b9; }
        .widget h3 { margin: 10px 0; font-size: 24px; color: #2c3e50; }
        .charts { display: flex; flex-wrap: wrap; gap: 30px; }
        .chart-container { flex: 1 1 500px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .content { margin-left: 0; }
            .charts { flex-direction: column; }
        }
        @media (max-width: 768px) {
            .widgets { flex-direction: column; }
            .widget { flex: 1 1 100%; }
        }
        @media (max-width: 480px) {
            .topbar { flex-direction: column; height: auto; padding: 10px; text-align: center; }
            .topbar .user-info { margin-right: 0; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="logo">Banking System</div>
        <div class="user-info">
            Logged in as: <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
        </div>
    </div>

    <div class="content">
        <h2>Dashboard</h2>

        <!-- Widgets -->
        <div class="widgets">
            <div class="widget">
                <i class="fas fa-users"></i>
                <h3><?= $total_clients ?></h3>
                <p>Total Clients</p>
            </div>
            <div class="widget">
                <i class="fas fa-money-bill-wave"></i>
                <h3><?= number_format($total_transactions_today, 2) ?> GHS</h3>
                <p>Normal Savings Today</p>
            </div>
            <div class="widget">
                <i class="fas fa-piggy-bank"></i>
                <h3><?= $total_savings_clients ?></h3>
                <p>Total Savings Clients</p>
            </div>
            <div class="widget">
                <i class="fas fa-hand-holding-dollar"></i>
                <h3><?= number_format($total_savings_today, 2) ?> GHS</h3>
                <p>Daily Savings Today</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts">
            <div class="chart-container">
                <canvas id="transactionsChart"></canvas>
            </div>
            <div class="chart-container">
                <canvas id="savingsChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
        const transactionsChart = new Chart(transactionsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($transactions_labels) ?>,
                datasets: [{
                    label: 'Transactions (Last 7 Days)',
                    data: <?= json_encode($transactions_data) ?>,
                    borderColor: '#2980b9',
                    backgroundColor: 'rgba(41, 128, 185,0.2)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        const savingsCtx = document.getElementById('savingsChart').getContext('2d');
        const savingsChart = new Chart(savingsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($savings_labels) ?>,
                datasets: [{
                    label: 'Savings (Last 7 Days)',
                    data: <?= json_encode($savings_data) ?>,
                    backgroundColor: 'rgba(39, 174, 96,0.7)',
                    borderColor: '#27ae60',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>
