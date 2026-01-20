<?php
// dashboard/settings.php
session_start();
if (empty($_SESSION['user_id'])) { 
    header("Location: ../index.php");
    exit;
}

require_once "../config/database.php";
$conn = db();

// Fetch current settings
$res = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $res->fetch_assoc();

// Handle main settings form submission (excluding SMS API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $system_name   = $_POST['system_name'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $sms_username  = $_POST['sms_username'] ?? '';

    $stmt = $conn->prepare("UPDATE settings 
        SET system_name=?, contact_email=?, contact_phone=?, sms_username=? 
        WHERE id=?");
    $stmt->bind_param("ssssi", $system_name, $contact_email, $contact_phone, $sms_username, $settings['id']);
    $stmt->execute();

    $_SESSION['message'] = "Settings updated successfully!";
    header("Location: settings.php");
    exit;
}

// Handle SMS API key update (from modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_sms_api'])) {
    $new_sms_api = $_POST['sms_api_key'] ?? '';
    $admin_pass  = $_POST['admin_password'] ?? '';

    // Verify admin password
    $userId = $_SESSION['user_id'];
    $resUser = $conn->query("SELECT password FROM users WHERE id = $userId");
    $user = $resUser->fetch_assoc();
    if (password_verify($admin_pass, $user['password'])) {
        $stmt = $conn->prepare("UPDATE settings SET sms_api_key=? WHERE id=?");
        $stmt->bind_param("si", $new_sms_api, $settings['id']);
        $stmt->execute();
        $_SESSION['sms_message'] = "SMS API Key updated successfully!";
    } else {
        $_SESSION['sms_message'] = "Invalid password. SMS API key not updated!";
    }
    header("Location: settings.php");
    exit;
}
?>

<?php 
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/topbar.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="content">
    <div class="container-fluid px-4">
        <h2 class="mt-4 mb-4">System Settings</h2>

        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="msgAlert">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['sms_message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" id="msgAlert">
                <?= $_SESSION['sms_message']; unset($_SESSION['sms_message']); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Update Settings</h5>
            </div>
            <div class="card-body p-4">
                <form method="post" class="row g-3">
                    <input type="hidden" name="update_settings" value="1">

                    <div class="col-md-6">
                        <label class="form-label">System Name</label>
                        <input type="text" name="system_name" 
                               class="form-control" 
                               value="<?= htmlspecialchars($settings['system_name'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="contact_email" 
                               class="form-control" 
                               value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" 
                               class="form-control" 
                               value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">SMS Username</label>
                        <input type="text" name="sms_username" 
                               class="form-control" 
                               value="<?= htmlspecialchars($settings['sms_username'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">SMS API Key</label>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($settings['sms_api_key'] ?? '') ?>" readonly>
                        <small class="text-muted d-block">To update, click the button below</small>
                        <button type="button" class="btn btn-warning mt-2" data-bs-toggle="modal" data-bs-target="#smsApiModal">
                            Update SMS API Key
                        </button>
                    </div>

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal for SMS API Key (inside content) -->
        <div class="modal fade" id="smsApiModal" tabindex="-1" aria-labelledby="smsApiModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <form method="post" class="modal-content">
                <input type="hidden" name="update_sms_api" value="1">
              <div class="modal-header">
                <h5 class="modal-title" id="smsApiModalLabel">Update SMS API Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">New SMS API Key</label>
                    <input type="text" name="sms_api_key" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Password</label>
                    <input type="password" name="admin_password" class="form-control" required>
                    <small class="text-muted">Enter your password to confirm update.</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" class="btn btn-success">Update API Key</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
    </div>
</div>

<script>
// Auto hide messages after 3 seconds
setTimeout(() => {
    const msg = document.getElementById('msgAlert');
    if(msg) msg.style.display = 'none';
}, 3000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
