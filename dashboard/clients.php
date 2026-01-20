<?php
// dashboard/clients.php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }

require_once '../config/database.php';
$conn = db();

// Load all clients (all fields so View/Edit has the data)
$res = $conn->query("SELECT * FROM clients ORDER BY id DESC");
$clients = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
    <h2>Client Management</h2>

    <!-- Top Controls -->
    <div style="display:flex; gap: 10px; align-items:center; justify-content: space-between; margin-bottom: 20px;">
        <input type="text" id="clientSearch" placeholder="Search clients..." style="padding:10px; width:40%;">
        <button id="addClientBtn" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Client
        </button>
    </div>

    <!-- Clients Table -->
    <table class="table" id="clientsTable">
        <thead>
          <tr>
              <th>Full Name</th>
              <th>Account No</th>
              <th>Phone</th>
              <th>ID No</th>
              <th>Date Registered</th>   <!-- NEW -->
              <th>Picture</th>
              <th style="width:170px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($clients)): ?>
            <tr><td colspan="6" style="text-align:center; padding:20px;">No clients yet.</td></tr>
        <?php else: ?>
            <?php foreach ($clients as $c): ?>
              <tr class="client-row" id="client-<?= (int)$c['id'] ?>">
                  <td class="td-name"><?= htmlspecialchars(($c['surname'] ?? '').' '.($c['firstname'] ?? '')) ?></td>
                  <td class="td-account"><?= htmlspecialchars($c['account_number'] ?? '') ?></td>
                  <td class="td-phone"><?= htmlspecialchars($c['telephone'] ?? '') ?></td>
                  <td class="td-idnum"><?= htmlspecialchars($c['id_number'] ?? '') ?></td>
                  <td class="td-regdate"><?= htmlspecialchars($c['registration_date'] ?? '') ?></td>
                  <td class="td-picture">
                      <?php if (!empty($c['picture'])): ?>
                          <img src="<?= '../' . htmlspecialchars($c['picture']) ?>" 
                              alt="Picture" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                      <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-buttons">
                        <button class="btn btn-info viewBtn" style="background-color:chocolate"
                            data-json='<?= json_encode($c, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>'
                            title="View"><i class="fas fa-eye"></i></button>

                        <button class="btn btn-warning editBtn" style="background-color:#16a34a"
                            data-json='<?= json_encode($c, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>'
                            title="Edit"><i class="fas fa-edit"></i></button>

                        <button class="btn btn-secondary toggleBtn" 
                            data-id="<?= (int)$c['id'] ?>" style="background-color:#f59e0b"
                            title="Toggle Active"><i class="fas fa-toggle-on"></i></button>
                        <button class="btn btn-danger deleteBtn"
                            data-id="<?= (int)$c['id'] ?>"
                            data-name="<?= htmlspecialchars(($c['surname'] ?? '').' '.($c['firstname'] ?? '')) ?>"
                            style="background-color:#dc3545" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- =============== Add Client Modal =============== -->
<div id="addClientModal" class="modal">
  <div class="modal-content modal-xl">
    <span class="close">&times;</span>
    <h3>Add New Client</h3>
    <form id="addClientForm" method="POST" enctype="multipart/form-data" action="../backend/add_client.php" >
      <!-- 4-column tidy grid -->
      <div class="form-grid">
        <!-- Row 1 -->
        <div class="field">
          <label>Last name*</label>
          <input type="text" name="surname" placeholder="Last name">
        </div>
        <div class="field">
          <label>First Name*</label>
          <input type="text" name="firstname" placeholder="First Name">
        </div>
        <div class="field">
          <label>Date of Birth*</label>
          <input type="date" name="date_of_birth">
        </div>
        <div class="field">
          <label>Gender*</label>
          <select name="gender">
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
          </select>
        </div>

        <!-- Row 2 -->
        <div class="field">
          <label>Place of Birth</label>
          <input type="text" name="place_of_birth" placeholder="Place of Birth">
        </div>
        <div class="field">
          <label>ID Number</label>
          <input type="text" name="id_number" placeholder="ID Number">
        </div>
        <div class="field">
          <label>Account Number</label>
          <input type="text" name="account_number" placeholder="Account Number">
        </div>
        <div class="field">
        <label>Date of Registration*</label>
        <input type="date" name="registration_date">
      </div>

        <div class="field">
          <label>Marital Status</label>
          <input type="text" name="marital_status" placeholder="Marital Status">
        </div>

        <!-- Row 3 -->
        <div class="field col-span-2">
          <label>Residential Address</label>
          <input type="text" name="residential_address" placeholder="Residential Address">
        </div>
        <div class="field">
          <label>Occupation</label>
          <input type="text" name="occupation" placeholder="Occupation">
        </div>
        <div class="field">
          <label>Telephone*</label>
          <input type="text" name="telephone" placeholder="024870982">
        </div>

        <!-- Row 4 -->
        <div class="field col-span-2">
          <label>Communication Address</label>
          <input type="text" name="communication_address" placeholder="Communication Address">
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="Email">
        </div>
        <div class="field">
          <label>Next of Kin</label>
          <input type="text" name="next_of_kin" placeholder="Next of Kin">
        </div>

        <!-- Row 5 -->
        <div class="field">
          <label>Next of Kin Telephone</label>
          <input type="text" name="next_of_kin_telephone" placeholder="Next of Kin Telephone">
        </div>
        <div class="field">
          <label>Mother Name</label>
          <input type="text" name="mother_name" placeholder="Mother Name">
        </div>
        <div class="field">
          <label>Mother Hometown</label>
          <input type="text" name="mother_hometown" placeholder="Mother Hometown">
        </div>
        <div class="field">
          <label>Father Name</label>
          <input type="text" name="father_name" placeholder="Father Name">
        </div>
        <div class="field">
          <label>Father Hometown</label>
          <input type="text" name="father_hometown" placeholder="Father Hometown">
        </div>

        <!-- Row 6 -->
        <div class="field">
          <label>Signature</label>
          <input type="file" name="signature" accept="image/*">
        </div>
        <div class="field">
          <label>Picture</label>
          <input type="file" name="picture" accept="image/*" >
        </div>
        <div class="col-span-2"></div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-success">Save Client</button>
      </div>
    </form>
  </div>
</div>

<!-- =============== View Client Modal =============== -->
<div id="viewClientModal" class="modal">
  <div class="modal-content" style="max-width:700px; background-color: #969de9ff;">
    <span class="close">&times;</span>
    <h3>Client Details</h3>
    <div class="view-grid">
      <div class="stack">
        <p><strong>Name:</strong> <span id="v_name"></span></p>
        <p><strong>DOB:</strong> <span id="v_dob"></span></p>
        <p><strong>Gender:</strong> <span id="v_gender"></span></p>
        <p><strong>Place of Birth:</strong> <span id="v_pob"></span></p>
        <p><strong>ID Number:</strong> <span id="v_id"></span></p>
        <p><strong>Account #:</strong> <span id="v_acct"></span></p>
        <p><strong>Date Registered:</strong> <span id="v_regdate"></span></p>
        <p><strong>Marital Status:</strong> <span id="v_marital"></span></p>
        <p><strong>Address:</strong> <span id="v_address"></span></p>
        <p><strong>Occupation:</strong> <span id="v_occupation"></span></p>
        <p><strong>Comm. Address:</strong> <span id="v_commaddr"></span></p>
        <p><strong>Email:</strong> <span id="v_email"></span></p>
        <p><strong>Telephone:</strong> <span id="v_phone"></span></p>
        <p><strong>Mother:</strong> <span id="v_mother"></span></p>
        <p><strong>Mother Hometown:</strong> <span id="v_mother_ht"></span></p>
        <p><strong>Father:</strong> <span id="v_father"></span></p>
        <p><strong>Father Hometown:</strong> <span id="v_father_ht"></span></p>
        <p><strong>Next of Kin:</strong> <span id="v_nok"></span></p>
        <p><strong>Next of Kin Tel:</strong> <span id="v_nok_tel"></span></p>
      </div>
      <div class="image-col">
        <div>
          <p><strong>Picture</strong></p>
          <img id="v_picture" src="" alt="Picture" class="thumb">
        </div>
        <div style="margin-top:10px;">
          <p><strong>Signature</strong></p>
          <img id="v_signature" src="" alt="Signature" class="thumb">
        </div>
      </div>
    </div>
  </div>
</div>

<!-- =============== Edit Client Modal =============== -->
<div id="editClientModal" class="modal">
  <div class="modal-content modal-xl">
    <span class="close">&times;</span>
    <h3>Edit Client</h3>
    <form id="editClientForm" method="POST" action="edit_client.php">
      <input type="hidden" name="id" id="e_id">

      <div class="form-grid">
        <!-- Row 1 -->
        <div class="field">
          <label for="e_surname">Last Name</label>
          <input type="text" name="surname" id="e_surname">
        </div>
        <div class="field">
          <label for="e_firstname">First Name</label>
          <input type="text" name="firstname" id="e_firstname">
        </div>
        <div class="field">
          <label for="e_dob">Date of Birth</label>
          <input type="date" name="date_of_birth" id="e_dob">
        </div>
        <div class="field">
          <label for="e_gender">Gender</label>
          <select name="gender" id="e_gender">
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
          </select>
        </div>

        <!-- Row 2 -->
        <div class="field">
          <label for="e_pob">Place of Birth</label>
          <input type="text" name="place_of_birth" id="e_pob">
        </div>
        <div class="field">
          <label for="e_idnum">ID Number</label>
          <input type="text" name="id_number" id="e_idnum">
        </div>
        <div class="field">
          <label for="e_account">Account Number</label>
          <input type="text" name="account_number" id="e_account">
        </div>
        <div class="field">
          <label for="e_regdate">Date of Registration</label>
          <input type="date" name="registration_date" id="e_regdate">
        </div>

        <div class="field">
          <label for="e_marital_status">Marital Status</label>
          <input type="text" name="marital_status" id="e_marital_status">
        </div>

        <!-- Row 3 -->
        <div class="field col-span-2">
          <label for="e_address">Residential Address</label>
          <input type="text" name="residential_address" id="e_address">
        </div>
        <div class="field">
          <label for="e_occupation">Occupation</label>
          <input type="text" name="occupation" id="e_occupation">
        </div>
        <div class="field">
          <label for="e_phone">Telephone</label>
          <input type="text" name="telephone" id="e_phone">
        </div>

        <!-- Row 4 -->
        <div class="field col-span-2">
          <label for="e_commaddr">Communication Address</label>
          <input type="text" name="communication_address" id="e_commaddr">
        </div>
        <div class="field">
          <label for="e_email">Email</label>
          <input type="email" name="email" id="e_email">
        </div>
        <div class="field">
          <label for="e_nok">Next of Kin</label>
          <input type="text" name="next_of_kin" id="e_nok">
        </div>
        <div class="field">
          <label for="e_nok_tel">Next of Kin Telephone</label>
          <input type="text" name="next_of_kin_telephone" id="e_nok_tel">
        </div>

        <!-- Row 5 -->
        <div class="field">
          <label for="e_mother">Mother Name</label>
          <input type="text" name="mother_name" id="e_mother">
        </div>
        <div class="field">
          <label for="e_mother_ht">Mother Hometown</label>
          <input type="text" name="mother_hometown" id="e_mother_ht">
        </div>
        <div class="field">
          <label for="e_father">Father Name</label>
          <input type="text" name="father_name" id="e_father">
        </div>
        <div class="field">
          <label for="e_father_ht">Father Hometown</label>
          <input type="text" name="father_hometown" id="e_father_ht">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-warning">
          <i class="fas fa-save"></i> Update Client
        </button>
      </div>
    </form>
  </div>
</div>


<!-- Toast -->
<div id="toast" class="toast"></div>

<style>
/* Table */
.table { width: 100%; border-collapse: collapse; font-size:14px; }
.table th, .table td { border: 1px solid #e5e7eb; padding: 10px; text-align:left; }
.table th { background: #f9fafb; font-weight:600; }
.center { text-align:center; padding:20px; }

/* Buttons */
.btn { padding: 8px 12px; margin: 2px; border: none; cursor: pointer; border-radius: 8px; font-size: 14px; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-success { background: #16a34a; color: #fff; }
.btn-info { background: #0ea5e9; color: #fff; }
.btn-warning { background: #f59e0b; color: #fff; }
.btn-danger { background: #ef4444; color: #fff; }
.btn-secondary { background: #6b7280; color: #fff; }
.action-buttons { display:flex; gap:6px; align-items:center; }
.thumb-mini { width:50px; height:50px; object-fit:cover; border-radius:6px; }

/* Modal */
.modal { display:none; position:fixed; inset:0; z-index: 1000;}
.modal-content {
  background:white;
  padding:20px;
  margin:60px auto;
  position:relative;
  border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,0.2);
  max-width:550px;
  max-height:90vh;
  overflow-y:auto;
}
.modal .close { position:absolute; top:12px; right:16px; font-size:22px; cursor:pointer; color:#374151; }
.modal-xl { max-width: 550px; }

.modal-content {
    max-height: 90vh;   /* so it fits within the viewport */
    overflow-y: auto;   /* enable vertical scrolling if content exceeds height */
}


/* Grid system for modals (4 tidy columns) */
.form-grid {
  display:grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-top: 12px;
}
.col-span-2 { grid-column: span 2; }
.col-span-3 { grid-column: span 3; }
.col-span-4 { grid-column: span 4; }

/* Fields */
.field label { display:block; margin-bottom:6px; font-weight:600; font-size:13px; color:#374151; }
.field input, .field select, .field textarea {
  width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; background:#fff;
}
.field input:focus, .field select:focus, .field textarea:focus {
  outline:none; border-color:#2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15);
}

/* View modal */
.view-grid { display:grid; grid-template-columns: 2fr 1fr; gap:16px; }
.thumb { width: 100%; max-width: 260px; height: 190px; object-fit: cover; border:1px solid #e5e7eb; border-radius: 10px; }
.image-col { display:flex; flex-direction:column; align-items:flex-start; }
.stack p { margin: 6px 0; }

/* Form footer */
.form-actions {
  margin-top:16px; padding-top:12px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end;
}

.toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: #333;
  color: #fff;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.3);
  opacity: 0;
  transform: translateY(-20px);
  transition: all 0.4s ease;
  z-index: 9999;
}
.toast.show {
  opacity: 1;
  transform: translateY(0);
}
.toast.success { background: #28a745; }
.toast.error { background: #dc3545; }


/* Responsive */
@media (max-width: 1200px) {
  .form-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
  .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .modal-xl { max-width: 90vw; }
  .view-grid { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
  .form-grid { grid-template-columns: 1fr; }
}
</style>

<script>
// Helpers
const qs  = (sel, el=document) => el.querySelector(sel);
const qsa = (sel, el=document) => [...el.querySelectorAll(sel)];
const openModal  = m => m.style.display = 'block';
const closeModal = m => m.style.display = 'none';

// Close handlers
qsa('.modal .close').forEach(x => x.addEventListener('click', () => closeModal(x.closest('.modal'))));
window.addEventListener('click', (e) => {
  qsa('.modal').forEach(m => { if (e.target === m) closeModal(m); });
});

// Add Client
qs('#addClientBtn').addEventListener('click', () => openModal(qs('#addClientModal')));

// View details
qsa('.viewBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const c = JSON.parse(btn.dataset.json);
    qs('#v_name').textContent       = (c.surname || '') + ' ' + (c.firstname || '');
    qs('#v_dob').textContent        = c.date_of_birth || '';
    qs('#v_gender').textContent     = c.gender || '';
    qs('#v_pob').textContent        = c.place_of_birth || '';
    qs('#v_id').textContent         = c.id_number || '';
    qs('#v_acct').textContent       = c.account_number || '';
    qs('#v_regdate').textContent    = c.registration_date || '';
    qs('#v_marital').textContent    = c.marital_status || '';
    qs('#v_address').textContent    = c.residential_address || '';
    qs('#v_occupation').textContent = c.occupation || '';
    qs('#v_commaddr').textContent   = c.communication_address || '';
    qs('#v_email').textContent      = c.email || '';
    qs('#v_phone').textContent      = c.telephone || '';
    qs('#v_mother').textContent     = c.mother_name || '';
    qs('#v_mother_ht').textContent  = c.mother_hometown || '';
    qs('#v_father').textContent     = c.father_name || '';
    qs('#v_father_ht').textContent  = c.father_hometown || '';
    qs('#v_nok').textContent        = c.next_of_kin || '';
    qs('#v_nok_tel').textContent    = c.next_of_kin_telephone || '';
    qs('#v_picture').src   = c.picture   ? ('../' + c.picture)   : '';
    qs('#v_signature').src = c.signature ? ('../' + c.signature) : '';
    openModal(qs('#viewClientModal'));
  });
});


document.addEventListener("DOMContentLoaded", function() { 
  const qs  = sel => document.querySelector(sel);
  const qsa = sel => document.querySelectorAll(sel);

// Edit details (prefill form)
qsa('.editBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const c = JSON.parse(btn.dataset.json);
    if (qs('#e_id'))              qs('#e_id').value             = c.id || '';
    if (qs('#e_surname'))         qs('#e_surname').value        = c.surname || '';
    if (qs('#e_firstname'))       qs('#e_firstname').value      = c.firstname || '';
    if (qs('#e_dob'))             qs('#e_dob').value            = c.date_of_birth || '';
    if (qs('#e_gender'))          qs('#e_gender').value         = c.gender || '';
    if (qs('#e_pob'))             qs('#e_pob').value            = c.place_of_birth || '';
    if (qs('#e_idnum'))           qs('#e_idnum').value          = c.id_number || '';
    if (qs('#e_account'))         qs('#e_account').value        = c.account_number || '';
    if (qs('#e_regdate'))         qs('#e_regdate').value        = c.registration_date || '';
    if (qs('#e_marital_status'))  qs('#e_marital_status').value = c.marital_status || '';
    if (qs('#e_address'))         qs('#e_address').value        = c.residential_address || '';
    if (qs('#e_occupation'))      qs('#e_occupation').value     = c.occupation || '';
    if (qs('#e_commaddr'))        qs('#e_commaddr').value       = c.communication_address || '';
    if (qs('#e_email'))           qs('#e_email').value          = c.email || '';
    if (qs('#e_phone'))           qs('#e_phone').value          = c.telephone || '';
    if (qs('#e_mother'))          qs('#e_mother').value         = c.mother_name || '';
    if (qs('#e_mother_ht'))       qs('#e_mother_ht').value      = c.mother_hometown || '';
    if (qs('#e_father'))          qs('#e_father').value         = c.father_name || '';
    if (qs('#e_father_ht'))       qs('#e_father_ht').value      = c.father_hometown || '';
    if (qs('#e_nok'))             qs('#e_nok').value            = c.next_of_kin || '';
    if (qs('#e_nok_tel'))         qs('#e_nok_tel').value        = c.next_of_kin_telephone || '';
    openModal(qs('#editClientModal'));
  });
});

// Handle submit update
const editForm = document.getElementById("editClientForm");
if (editForm) {
  editForm.addEventListener("submit", function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    fetch("../backend/update_client.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          closeModal(qs('#editClientModal'));
          showToast("Client updated successfully!", "success");
          // Update table row inline
          const clientId = qs('#e_id').value;
          const row = document.getElementById(`client-${clientId}`);
          if (row) {
            row.querySelector('.td-name').textContent    = qs('#e_surname').value + " " + qs('#e_firstname').value;
            row.querySelector('.td-account').textContent = qs('#e_account').value;
            row.querySelector('.td-phone').textContent   = qs('#e_phone').value;
            row.querySelector('.td-idnum').textContent   = qs('#e_idnum').value;
            row.querySelector('.td-regdate').textContent = qs('#e_regdate').value;
          }
        } else {
          showToast("Failed: " + (data.error || "Unable to update client."), "error");
        }
      })
      .catch(err => showToast("Error: " + err, "error"));
  });
}
});



// Simple client-side search (filters visible rows)
qs('#clientSearch').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  qsa('#clientsTable tbody tr').forEach(tr => {
    const text = tr.textContent.toLowerCase();
    tr.style.display = text.includes(q) ? '' : 'none';
  });
});

// Toggle Active (placeholder)
qsa('.toggleBtn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    if (!id) return;
    alert('TODO: build toggle_client.php to flip is_active for ID=' + id);
  });
});


document.addEventListener("DOMContentLoaded", function() {
    // Delete button confirmation
    document.querySelectorAll(".deleteBtn").forEach(btn => {
      btn.addEventListener("click", function() {
          let clientId = this.getAttribute("data-id");
          let clientName = this.getAttribute("data-name") || "this client";
          if (confirm(`Are you sure you want to delete client: ${clientName}?`)) {
            fetch("../backend/delete_client.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: "id=" + encodeURIComponent(clientId)
            })
            .then(res => res.text())
            .then(data => {
              if (data.trim() === "success") {
                showToast("Client deleted successfully!", "success");
                const row = document.getElementById("client-" + clientId);
                if (row) row.remove();
              } else {
                showToast("Failed to delete client: " + data, "error");
              }
            })
            .catch(err => showToast("Error: " + err, "error"));
          }
        });
      });

});

// Toast
function showToast(msg, type="success") {
  const toast = document.getElementById("toast");
  toast.textContent = msg;
  toast.className = "toast " + type;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2500);
}

</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
