<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp']);

    if (!isset($_SESSION['otp_code'], $_SESSION['pending_user'])) {
        header("Location: index.php?err=Session expired");
        exit;
    }

    if (time() > $_SESSION['otp_expire']) {
        unset($_SESSION['otp_code'], $_SESSION['otp_expire'], $_SESSION['pending_user']);
        header("Location: index.php?err=OTP expired. Please login again.");
        exit;
    }

    if ($enteredOtp == $_SESSION['otp_code']) {
        // ✅ OTP verified, log user in
        $_SESSION['user_id'] = $_SESSION['pending_user'];
        unset($_SESSION['otp_code'], $_SESSION['otp_expire'], $_SESSION['pending_user']);

        header("Location: dashboard/index.php");
        exit;
    } else {
        $err = "Invalid OTP code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
  <link rel="stylesheet" href="./assets/css/styles.css">
</head>
<body class="auth-body">
  <div class="login-card">
    <h2>Enter OTP</h2>
    <?php if (!empty($err)): ?>
      <div class="alert"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>
    <form method="post" action="">
      <label>OTP Code</label>
      <input type="text" name="otp" placeholder="Enter the 6-digit code" required>
      <button type="submit" class="btn-primary">Verify</button>
    </form>
  </div>
</body>
</html>
