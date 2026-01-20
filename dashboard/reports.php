<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/../config/database.php';
$conn = db();

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
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/topbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="content" style="padding:20px;">
    <h2>Admin Reports</h2>

    <!-- Table Selection -->
    <div>
        <strong>Select Tables:</strong><br>
        <?php foreach($all_tables as $tbl => $nums): ?>
            <label style="margin-right:10px;">
                <input type="checkbox" name="tables[]" class="table-checkbox" value="<?= $tbl ?>"> <?= ucfirst(str_replace('_',' ',$tbl)) ?>
            </label>
        <?php endforeach; ?>
    </div>
    <br>

    <!-- Date Filters -->
    <div>
        <label>Start Date: <input type="date" id="start_date"></label>
        <label>End Date: <input type="date" id="end_date"></label>
        <button onclick="loadTables()" style="padding:5px 10px;">Apply Filter</button>
        <button onclick="printReport()" style="padding:5px 10px;">Print / Export PDF</button>
    </div>

    <hr>

    <!-- Printable Header -->
    <div id="printHeader" style="display:none; margin-bottom:20px;">
        <table style="width:100%; margin-bottom:10px;">
            <tr>
                <td style="width:50%;"><img src="/banking-system/assets/logo.png" style="height:60px;"></td>
                <td style="width:50%; text-align:right; font-size:24px; font-weight:bold;">Microfinance</td>
            </tr>
        </table>
        <hr>
    </div>

    <!-- Report Section -->
    <div id="reportSection"></div>
</div>

<style>
/* Highlight recent rows */
.recent-row { background:#d1f7d1; }

/* Print styling */
@media print {
    body * { visibility: hidden; }
    #printHeader, #reportSection, #printHeader *, #reportSection * { visibility: visible; }
    #printHeader { position:fixed; top:0; left:0; width:100%; }
    #reportSection { position:absolute; top:80px; left:0; width:100%; }

    table { page-break-inside:auto; border-collapse:collapse; width:100%; }
    tr { page-break-inside:avoid; page-break-after:auto; }

    /* Landscape for wide tables */
    .wide-table { width:100%; }
}
</style>

<script>
let selectedTables = [];

document.querySelectorAll('.table-checkbox').forEach(cb=>{
    cb.addEventListener('change', function(){
        loadTables();
    });
});

function loadTables(){
    const tables = Array.from(document.querySelectorAll('.table-checkbox:checked')).map(x=>x.value);
    selectedTables = tables;
    const start_date = document.getElementById('start_date').value;
    const end_date = document.getElementById('end_date').value;

    if(tables.length===0){
        document.getElementById('reportSection').innerHTML = '<p style="color:#888;">No tables selected.</p>';
        return;
    }

    const formData = new FormData();
    formData.append('start_date', start_date);
    formData.append('end_date', end_date);
    tables.forEach(t=>formData.append('tables[]', t));

    fetch('/banking-system/dashboard/ajax/load_table.php', { method:'POST', body:formData })
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success'){
                document.getElementById('reportSection').innerHTML = `<p style="color:red;">${data.message}</p>`;
                return;
            }

            let html = '';
            for(const [table, info] of Object.entries(data.tables)){
                html += `<h3 style='margin-top:30px; background:#f1f1f1; padding:8px; border-left:5px solid #2980b9;'>${table.replace('_',' ')}</h3>`;

                if(info.rows.length === 0){
                    html += "<p style='color:#7f8c8d;'>No records found.</p>";
                    continue;
                }

                const isWide = info.fields.length > 10 ? 'wide-table' : '';
                html += "<div style='overflow-x:auto; margin-bottom:20px;'><table border='1' cellpadding='8' cellspacing='0' class='"+isWide+"' style='border-collapse:collapse; width:100%;'>";
                
                // Table headers
                html += "<thead style='background:#2980b9; color:black; text-align:left;'><tr>";
                info.fields.forEach(f => html += `<th>${f}</th>`);
                html += "</tr></thead><tbody>";

                const now = new Date();
                info.rows.forEach(r=>{
                    let highlight = '';
                    if(r.created_at){
                        let rowDate = new Date(r.created_at);
                        let diffDays = Math.floor((now - rowDate)/(1000*60*60*24));
                        if(diffDays <= 7) highlight = 'background:#d1f7d1;';
                    }
                    html += `<tr style='${highlight}'>`;
                    info.fields.forEach(f => html += `<td>${r[f] ?? ''}</td>`);
                    html += "</tr>";
                });

                html += "</tbody></table></div>";
            }

            document.getElementById('reportSection').innerHTML = html;
        })
        .catch(err=>{
            console.error(err);
            document.getElementById('reportSection').innerHTML = '<p style="color:red;">Error loading tables!</p>';
        });
}

function printReport(){
    // Show print header
    document.getElementById('printHeader').style.display = 'block';
    window.print();
    document.getElementById('printHeader').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
