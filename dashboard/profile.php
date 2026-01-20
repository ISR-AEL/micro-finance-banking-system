<?php
session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
<h2>Profile</h2>

<!-- Update Admin Info -->
<button id="editProfileBtn">Edit Profile</button>

<div id="editProfileModal" style="display:none; padding: 20px; border: 1px solid #ccc; background: #f9f9f9;">
    <h3>Edit Profile</h3>
    <form method="POST" action="index.php?action=profile">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required><br><br>

        <label>Phone Number:</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>"><br><br>

        <button type="submit" name="update_profile">Save Changes</button>
        <button type="button" id="closeModal">Cancel</button>
    </form>
</div>

<!-- Change Password -->
<h3>Change Password</h3>
<form method="POST" action="index.php?action=profile">
    <label>Current Password:</label>
    <input type="password" name="current_password" id="current_password" required>
    <i class="fas fa-eye" id="toggleCurrent" style="cursor:pointer;"></i><br><br>

    <label>New Password:</label>
    <input type="password" name="new_password" id="new_password" required>
    <i class="fas fa-eye" id="toggleNew" style="cursor:pointer;"></i><br><br>

    <label>Confirm New Password:</label>
    <input type="password" name="confirm_password" id="confirm_password" required>
    <i class="fas fa-eye" id="toggleConfirm" style="cursor:pointer;"></i><br><br>

    <button type="submit" name="change_password">Change Password</button>
</form>

<script>
// Toggle password visibility
const toggles = [
    {btn: 'toggleCurrent', input: 'current_password'},
    {btn: 'toggleNew', input: 'new_password'},
    {btn: 'toggleConfirm', input: 'confirm_password'},
];

toggles.forEach(t => {
    const btn = document.getElementById(t.btn);
    const input = document.getElementById(t.input);
    btn.addEventListener('click', function(){
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
    });
});

// Modal toggle
const editBtn = document.getElementById('editProfileBtn');
const modal = document.getElementById('editProfileModal');
const closeBtn = document.getElementById('closeModal');

editBtn.addEventListener('click', () => modal.style.display = 'block');
closeBtn.addEventListener('click', () => modal.style.display = 'none');
</script>

</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
