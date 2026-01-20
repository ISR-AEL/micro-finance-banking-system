<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/footer.php';
?>

<div class="content">
    <h2>Savings Accounts</h2>

    <!-- Top Controls -->
    <div class="top-controls">
        <input type="text" id="searchAccounts" placeholder="Search client by name or phone...">
        <button id="addAccountBtn" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create/Update Savings Account
        </button>
    </div>

    <!-- Accounts Table -->
    <table class="table" id="accountsTable">
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Daily Amount</th>
                <th>Start Date</th>
                <th>Month</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="accountsBody">
            <!-- Filled by AJAX -->
        </tbody>
    </table>
</div>

<!-- =============== Create/Update Savings Account Modal =============== -->
<div id="addAccountModal" class="modal">
    <div class="modal-content" style="max-width:600px;">
        <span class="close">&times;</span>
        <h3>Create / Update Savings Account</h3>

        <form id="addAccountForm">
            <div class="form-grid">
                <!-- Client Search -->
                <div class="field full">
                    <label for="clientSearch">Client</label>
                    <select id="clientSearch" name="client_id" required>
                        <option value="">Search or select client...</option>
                    </select>
                </div>

                <div class="field">
                    <label for="daily_amount">Daily Amount (₵)</label>
                    <input type="number" step="0.01" min="1" name="daily_amount" id="daily_amount" required>
                </div>

                <div class="field">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="field">
                    <label for="savings_month">Savings Month</label>
                    <input type="month" name="savings_month" id="savings_month" value="<?= date('Y-m') ?>" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

<style>
/* General */
.table { width: 100%; border-collapse: collapse; font-size:14px; }
.table th, .table td { border: 1px solid #ddd; padding: 10px; text-align:left; }
.table th { background: #f9f9f9; font-weight:600; }

.btn { padding: 6px 10px; margin: 2px; border: none; cursor: pointer; border-radius: 6px; font-size: 14px; }
.btn-primary { background: #2980b9; color: #fff; }
.btn-success { background: #27ae60; color: #fff; }
.btn-danger { background: #c0392b; color: #fff; }

.top-controls {
    display:flex; gap:10px; align-items:center; justify-content: space-between;
    margin-bottom:20px;
}
.top-controls input {
    padding:10px; width:40%; border:1px solid #ccc; border-radius:6px; font-size:14px;
}

/* Modal */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:1000; }
.modal-content { 
    background:#fff; padding:25px; margin:80px auto; position:relative; 
    border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.3);
    animation: fadeIn 0.3s ease-in-out;
}
.modal .close { position:absolute; top:16px; right:16px; font-size:22px; cursor:pointer; color:#666; }
.modal .close:hover { color:#000; }

/* Form grid */
.form-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:14px; }
.form-grid .full { grid-column: 1 / -1; }
.field label { display:block; margin-bottom:6px; font-weight:600; font-size:14px; color:#333; }
.field input, .field select {
    width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-size:14px;
}
.form-actions { margin-top:14px; }

/* Animations */
@keyframes fadeIn { from {opacity:0; transform:translateY(-20px);} to {opacity:1; transform:translateY(0);} }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
// Helpers
const qs  = (sel, el=document) => el.querySelector(sel);
const qsa = (sel, el=document) => [...el.querySelectorAll(sel)];
const openModal  = m => m.style.display = 'block';
const closeModal = m => m.style.display = 'none';

// Close modal
qsa('.modal .close').forEach(x => x.addEventListener('click', () => closeModal(x.closest('.modal'))));
window.addEventListener('click', (e) => { qsa('.modal').forEach(m => { if (e.target === m) closeModal(m); }); });

// Open modal
qs('#addAccountBtn').addEventListener('click', () => openModal(qs('#addAccountModal')));

// Select2 AJAX client search
$(function() {
    $('#clientSearch').select2({
        dropdownParent: $('#addAccountModal'),
        placeholder: 'Search/select client...',
        width: '100%',
        allowClear: true,
        ajax: {
            url: '../backend/search_clients.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });

    // Load accounts
    loadAccounts();

    // Live search
    $('#searchAccounts').on('input', function () { loadAccounts(this.value); });

    // Submit form
    $('#addAccountForm').on('submit', function(e){
        e.preventDefault();
        $.post('../backend/add_savings.php', $(this).serialize(), function(resp){
            if (resp && resp.status === 'success') {
                closeModal(qs('#addAccountModal'));
                $('#addAccountForm')[0].reset();
                $('#clientSearch').val(null).trigger('change');
                loadAccounts($('#searchAccounts').val() || '');
            } else {
                alert(resp && resp.message ? resp.message : 'Failed to save account.');
            }
        }, 'json');
    });
});

// Load savings accounts table
function loadAccounts(q='') {
    $.get('../backend/get_savings_accounts.php', { q }, function(resp){
        const body = $('#accountsBody');
        body.empty();

        if (!resp || resp.status !== 'success' || !resp.accounts.length) {
            body.append('<tr><td colspan="5" style="text-align:center; padding:20px;">No savings accounts found.</td></tr>');
            return;
        }

        resp.accounts.forEach(r => {
            body.append(`
                <tr>
                    <td>${r.full_name || ''} <br><small>${r.telephone || ''}</small></td>
                    <td>₵${r.daily_amount || ''}</td>
                    <td>${r.start_date || ''}</td>
                    <td>${r.savings_month || ''}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteAccount(${r.id})">
                            <i class="fas fa-trash"></i> Close
                        </button>
                    </td>
                </tr>
            `);
        });
    }, 'json');
}

// Delete account
function deleteAccount(id) {
    if (!confirm("Are you sure you want to close this savings account?")) return;

    $.post('../backend/delete_savings.php', { savings_id: id }, function(resp){
        if (resp && resp.status === 'success') {
            loadAccounts($('#searchAccounts').val() || '');
        } else {
            alert(resp && resp.message ? resp.message : 'Failed to close account.');
        }
    }, 'json');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
