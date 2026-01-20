<?php 
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/footer.php';
?>

<div class="content">
    <h2>Normal Savings Management</h2>

    <!-- Top Controls -->
    <div class="top-controls">
        <input type="text" id="searchTransactions" placeholder="Search by client name or phone...">
        <div class="right-controls">
            <div class="view-toggle">
                <button id="tabTransactions" class="btn btn-tab active">Transactions</button>
                <button id="tabAccounts" class="btn btn-tab">Accounts Summary</button>
                <button id="tabAdminWithdrawals" class="btn btn-tab">Admin Withdrawals</button>
            </div>
            <button id="addTransactionBtn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Transaction
            </button>
            <button id="addAdminWithdrawBtn" class="btn btn-success" style="display:none;">
                <i class="fas fa-university"></i> Withdraw Admin Fee
            </button>
        </div>
    </div>

    <!-- Transactions Section -->
    <div id="transactionsSection">
        <table class="table" id="transactionsTable">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody id="transactionsBody"></tbody>
        </table>
    </div>

    <!-- Accounts Summary Section -->
    <div id="accountsSection" style="display:none;">
        <div class="summary-cards">
            <div class="card">
                <div class="card-title">Total Accounts</div>
                <div class="card-value" id="sumAccounts">0</div>
            </div>
            <div class="card">
                <div class="card-title">Pending Admin Fee (eligible)</div>
                <div class="card-value" id="sumAdminFee">₵0.00</div>
            </div>
            <div class="card">
                <div class="card-title">Total Withdrawable (eligible)</div>
                <div class="card-value" id="sumWithdrawable">₵0.00</div>
            </div>
            <div class="card">
                <div class="card-title">Ready to Close</div>
                <div class="card-value" id="sumReady">0</div>
            </div>
        </div>

        <table class="table" id="accountsTable">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Days Paid</th>
                    <th>Clients Total Paid</th>
                    <th>Clients Amount Withdrawable</th>
                    <th>Monthly Charges</th>
                    <th>Already Withdrawn</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="accountsBody"></tbody>
        </table>
    </div>

    <!-- Admin Withdrawals Section -->
    <div id="adminWithdrawalsSection" style="display:none;">
        <h3>Admin Withdrawals</h3>
        <table class="table" id="adminWithdrawalsTable">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Withdrawn At</th>
                    <th>Note</th>
                    <th>Admin</th>
                </tr>
            </thead>
            <tbody id="adminWithdrawalsBody"></tbody>
        </table>
    </div>
</div>

<!-- Add Transaction Modal -->
<div id="addTransactionModal" class="modal">
    <div class="modal-content" style="max-width:650px;">
        <span class="close">&times;</span>
        <h3>Add Transaction</h3>
        <form id="addTransactionForm">
            <div class="form-grid">
                <div class="field full">
                    <label for="clientSearchModal">Client</label>
                    <select id="clientSearchModal" name="client_id" required></select>
                </div>
                <div class="field">
                    <label>Transaction Type</label>
                    <div class="radio-row">
                        <label><input type="radio" name="type" value="deposit" required> Deposit</label>
                        <label><input type="radio" name="type" value="withdrawal"> Withdrawal</label>
                    </div>
                </div>
                <div class="field">
                    <label>Amount</label>
                    <input type="number" name="amount" min="0" step="0.01" required>
                </div>
                <div class="field">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="field full">
                    <label>Details</label>
                    <textarea name="details" rows="3" placeholder="Optional note..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Save Transaction</button>
            </div>
        </form>
    </div>
</div>

<!-- Admin Withdraw Modal -->
<div id="addAdminWithdrawModal" class="modal">
    <div class="modal-content" style="max-width:450px;">
        <span class="close">&times;</span>
        <h3>Withdraw Admin Fee</h3>
        <form id="addAdminWithdrawForm">
            <div class="form-grid">
                <div class="field">
                    <label>Amount</label>
                    <input type="number" name="amount" min="0" step="0.01" required>
                </div>
                <div class="field full">
                    <label>Note</label>
                    <textarea name="note" rows="3" placeholder="Optional note..."></textarea>
                </div>
                <div class="field full">
                    <label>Admin Password</label>
                    <input type="password" name="password" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Confirm Withdrawal</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Confirm Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <span class="close">&times;</span>
        <h3>Confirm Action</h3>
        <p id="confirmModalMessage">Are you sure?</p>
        <div class="form-actions" style="text-align:right; margin-top:20px;">
            <button id="confirmCancelBtn" class="btn btn-secondary">Cancel</button>
            <button id="confirmOkBtn" class="btn btn-success">Confirm</button>
        </div>
    </div>
</div>

<style>
/* Styles */
.table { width:100%; border-collapse: collapse; font-size:14px; }
.table th, .table td { border:1px solid #ddd; padding:10px; text-align:left; }
.table th { background:#f9f9f9; font-weight:600; }
.btn { padding:6px 10px; margin:2px; border:none; cursor:pointer; border-radius:6px; font-size:14px; }
.btn-primary { background:#2980b9; color:#fff; } .btn-success { background:#27ae60; color:#fff; } .btn-secondary { background:#aaa; color:#fff; }
.top-controls { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:20px; }
.top-controls input { padding:10px; width:40%; border:1px solid #ccc; border-radius:6px; font-size:14px; }
.summary-cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap:12px; margin:10px 0 16px 0; }
.summary-cards .card { background:#fff; border:1px solid #eee; border-radius:10px; padding:14px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.card-title { font-size:12px; color:#666; margin-bottom:6px; text-transform:uppercase; letter-spacing:.3px; }
.card-value { font-size:18px; font-weight:700; }
.modal { display:none; position:fixed; inset:0; z-index:1000; background: rgba(0,0,0,0.25); }
.modal-content { background:#fff; padding:20px; margin:60px auto; position:relative; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.2);}
.modal .close { position:absolute; top:20px; right:14px; font-size:20px; cursor:pointer; }
.form-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:14px; }
.form-grid .full { grid-column: 1 / -1; }
.field label { display:block; margin-bottom:6px; font-weight:600; font-size:14px; color:#333; }
.field input, .field textarea, .field select { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px; }
.radio-row { display:flex; gap:16px; align-items:center; }
.form-actions { margin-top:14px; }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
const qs = (sel, el=document) => el.querySelector(sel);
const qsa = (sel, el=document) => [...el.querySelectorAll(sel)];
const openModal = m => m.style.display='block';
const closeModal = m => m.style.display='none';
const money = v => '₵'+(Number(v||0).toFixed(2));

// Custom confirm modal
const confirmModal = qs('#confirmModal');
const confirmMessage = qs('#confirmModalMessage');
const confirmOkBtn = qs('#confirmOkBtn');
const confirmCancelBtn = qs('#confirmCancelBtn');
function showConfirm(message, callback){
    confirmMessage.textContent = message;
    openModal(confirmModal);

    const okHandler = () => { 
        closeModal(confirmModal); 
        confirmOkBtn.removeEventListener('click', okHandler);
        confirmCancelBtn.removeEventListener('click', cancelHandler);
        callback(true); 
    };

    const cancelHandler = () => { 
        closeModal(confirmModal); 
        confirmOkBtn.removeEventListener('click', okHandler);
        confirmCancelBtn.removeEventListener('click', cancelHandler);
        callback(false); 
    };

    confirmOkBtn.addEventListener('click', okHandler);
    confirmCancelBtn.addEventListener('click', cancelHandler);
}

// Tabs
function activateTab(which){
    const tabs = {
        transactions: {btn:'#tabTransactions', sec:'#transactionsSection'},
        accounts: {btn:'#tabAccounts', sec:'#accountsSection'},
        admin: {btn:'#tabAdminWithdrawals', sec:'#adminWithdrawalsSection'}
    };
    for(const k in tabs){
        qs(tabs[k].btn).classList.remove('active');
        qs(tabs[k].sec).style.display='none';
    }
    qs(tabs[which].btn).classList.add('active');
    qs(tabs[which].sec).style.display='block';
    if(which==='transactions') loadTransactions(qs('#searchTransactions').value||'');
    if(which==='accounts') loadAccounts(qs('#searchTransactions').value||'');
    if(which==='admin'){
        loadAdminWithdrawals();
        qs('#addAdminWithdrawBtn').style.display='inline-block';
    } else qs('#addAdminWithdrawBtn').style.display='none';
}

qsa('.modal .close').forEach(x=>x.addEventListener('click',()=>closeModal(x.closest('.modal'))));
window.addEventListener('click', e=>{ qsa('.modal').forEach(m=>{ if(e.target===m) closeModal(m); }); });

qs('#addTransactionBtn').addEventListener('click', ()=>openModal(qs('#addTransactionModal')));
qs('#addAdminWithdrawBtn').addEventListener('click', ()=>openModal(qs('#addAdminWithdrawModal')));

qs('#tabTransactions').addEventListener('click', ()=>activateTab('transactions'));
qs('#tabAccounts').addEventListener('click', ()=>activateTab('accounts'));
qs('#tabAdminWithdrawals').addEventListener('click', ()=>activateTab('admin'));

$(function(){
    $('#clientSearchModal').select2({
        dropdownParent: $('#addTransactionModal'),
        placeholder:'Search/select client...',
        width:'100%',
        allowClear:true,
        ajax:{url:'../backend/search_clients.php', dataType:'json', delay:250, data:params=>({q:params.term}), processResults:data=>({results:data})}
    });

    loadTransactions();
    loadAccounts();

    $('#searchTransactions').on('input', function(){
        if(qs('#tabTransactions').classList.contains('active')) loadTransactions(this.value);
        else if(qs('#tabAccounts').classList.contains('active')) loadAccounts(this.value);
    });

    // Add Transaction Form
    $('#addTransactionForm').on('submit', function(e){
        e.preventDefault();
        let type = $(this).find('input[name="type"]:checked').val();
        let message = type==='deposit' ? 'Are you sure you want to deposit for this client?' : 'Are you sure you want to withdraw for this client?';
        showConfirm(message, confirmed=>{
            if(!confirmed) return;
            $.post('../backend/add_transaction.php', $(this).serialize(), function(resp){
                if(resp && resp.success){
                    closeModal(qs('#addTransactionModal'));
                    $('#addTransactionForm')[0].reset();
                    $('#clientSearchModal').val(null).trigger('change');
                    loadTransactions();
                    loadAccounts();
                } else alert(resp?.error || 'Failed to save transaction.');
            }, 'json');
        });
    });

    // Admin Withdraw Form
    $('#addAdminWithdrawForm').on('submit', function(e){
        e.preventDefault();
        showConfirm('Are you sure you want to withdraw this admin fee?', confirmed=>{
            if(!confirmed) return;
            $.post('../backend/withdraw_admin_fee.php', $(this).serialize(), function(resp){
                if(resp && resp.success){
                    closeModal(qs('#addAdminWithdrawModal'));
                    $('#addAdminWithdrawForm')[0].reset();
                    loadAdminWithdrawals();
                    $('#sumAdminFee').text(money(resp.pending_admin_fee));
                    loadAccounts();
                } else alert(resp?.error || resp?.message || 'Withdrawal failed.');
            }, 'json');
        });
    });
});

// Transactions
function loadTransactions(q=''){
    $.get('../backend/get_transactions.php', {q}, function(resp){
        const rows = resp.transactions || [];
        const body = $('#transactionsBody').empty();
        if(!rows.length){
            body.append('<tr><td colspan="7" style="text-align:center;padding:20px;">No transactions found.</td></tr>');
            return;
        }
        rows.forEach(r=>{
            body.append(`<tr>
                <td>${r.client_name||''}</td>
                <td>${r.phone||''}</td>
                <td>${r.date||''}</td>
                <td>${r.type||''}</td>
                <td>${money(r.amount)}</td>
                <td>${money(r.balance)}</td>
                <td>${r.details||''}</td>
            </tr>`);
        });
    }, 'json');
}

// Accounts
function loadAccounts(q=''){
    $.get('../backend/get_transactions.php', {q}, function(resp){
        const body = $('#accountsBody').empty();
        const accounts = resp.accounts || [];
        const summary = resp.summary || {};

        $('#sumAccounts').text(summary.total_accounts || 0);
        $('#sumAdminFee').text(money(summary.pending_admin_fee));
        $('#sumWithdrawable').text(money(summary.total_withdrawable));
        $('#sumReady').text(summary.ready_to_close || 0);

        if(!accounts.length){
            body.append('<tr><td colspan="9" style="text-align:center;padding:20px;">No accounts found.</td></tr>');
            return;
        }

        accounts.forEach(r=>{
            let rowClass = '';
            if(r.status==='Withdrawable') rowClass='withdrawable';
            else if(r.status==='Ready to Close') rowClass='ready-to-close';

            const actionBtn = r.status==='Ready to Close'
                ? `<button class="btn btn-danger btn-close-account" data-id="${r.id}">Close Account</button>`
                : '';

            body.append(`<tr class="${rowClass}">
                <td>${r.full_name}</td>
                <td>${r.telephone}</td>
                <td>${r.total_days_paid}</td>
                <td>${money(r.total_paid)}</td>
                <td>${money(r.withdrawable)}</td>
                <td>${money(r.monthly_charges)}</td>
                <td>${money(r.already_withdrawn)}</td>
                <td>${r.status}</td>
                <td>${actionBtn}</td>
            </tr>`);
        });

        // Close account with modal confirmation
        $('.btn-close-account').off('click').on('click', function(){
            const id = $(this).data('id');
            showConfirm('Are you sure you want to close this account?', confirmed=>{
                if(!confirmed) return;
                $.post('../backend/close_account.php', {account_id: id}, function(resp){
                    if(resp && resp.status==='success'){
                        alert(resp.message);
                        loadAccounts();
                        loadTransactions();
                    } else alert(resp?.message || 'Failed to close account.');
                }, 'json');
            });
        });
    },'json');
}

// Admin Withdrawals
function loadAdminWithdrawals(){
    $.get('../backend/get_admin_withdrawals.php', function(resp){
        const body = $('#adminWithdrawalsBody').empty();
        if(resp.status==='success'){
            (resp.withdrawals||[]).forEach(w=>{
                body.append(`<tr>
                    <td>${money(w.amount)}</td>
                    <td>${w.withdrawn_at}</td>
                    <td>${w.note || '-'}</td>
                    <td>${w.admin_name || w.admin_id}</td>
                </tr>`);
            });
        } else {
            body.append('<tr><td colspan="4" style="text-align:center;">No admin withdrawals yet.</td></tr>');
        }
    },'json');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
