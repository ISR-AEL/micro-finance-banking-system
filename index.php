<?php
// index.php - Login + OTP
session_start();

// If already logged in fully (with OTP)
if (!empty($_SESSION['user_id']) && !empty($_SESSION['otp_verified'])) {
    header('Location: dashboard/index.php');
    exit;
}

$err = $_GET['err'] ?? '';
$step = $_SESSION['otp_pending'] ?? false; // flag to know which form to show
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Banking System - Login with OTP</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ===== Base Layout ===== */
    body {
      margin:0;
      font-family:'Segoe UI',Tahoma,Verdana,sans-serif;
      height:100vh;
      display:flex;
    }
    .container {
      display:flex;
      width:100%;
      height:100vh;
    }
    .left-panel {
      flex:1;
      background:linear-gradient(135deg,#057a55,#0c9a6c);
      color:white;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      padding:3rem;
      text-align:center;
    }
    .left-panel img {
      width:550px;
      max-width:100%;
      margin-bottom:1rem;
    }
    .left-panel h1 {font-size:2rem;margin-bottom:1rem;}
    .left-panel p {font-size:1.1rem;margin-bottom:2rem;}
    .btn-docs {
      padding:0.8rem 1.5rem;
      background:white;
      color:#057a55;
      border:none;
      border-radius:8px;
      cursor:pointer;
      font-weight:bold;
      text-decoration:none;
    }
    .btn-docs:hover {background:#f0f0f0;}
    .right-panel {
      flex:1;
      background:linear-gradient(135deg,#0f4c75,#3282b8,#56ccf2);
      display:flex;
      justify-content:center;
      align-items:center;
      padding:2rem;
    }
    .login-card {
      background:white;
      padding:2.5rem;
      border-radius:12px;
      box-shadow:0 4px 12px rgba(0,0,0,0.1);
      width:100%;
      max-width:400px;
    }
    .login-card h2 {
      text-align:center;
      color:#0f4c75;
      margin-bottom:1.5rem;
    }
    label {
      display:block;
      margin:0.8rem 0 0.3rem;
      font-weight:bold;
      font-size:0.9rem;
    }
    input {
      width:100%;
      padding:0.8rem;
      border:1px solid #ccc;
      border-radius:8px;
      font-size:1rem;
      box-sizing:border-box;
    }
    .pwd-wrap {position:relative;}
    .pwd-wrap input {padding-right:2.5rem;}
    .eye-btn {
      position:absolute;
      top:50%;
      right:0.7rem;
      transform:translateY(-50%);
      background:none;
      border:none;
      cursor:pointer;
      color:#555;
      font-size:1rem;
    }
    .btn-primary {
      width:100%;
      padding:0.9rem;
      margin-top:1.5rem;
      background:#0f4c75;
      color:white;
      border:none;
      border-radius:8px;
      font-size:1rem;
      font-weight:bold;
      cursor:pointer;
    }
    .btn-primary:hover {background:#09344f;}
    .forgot-link {
      display:block;
      text-align:right;
      margin-top:0.8rem;
      font-size:0.9rem;
      color:#0f4c75;
      text-decoration:none;
    }
    .forgot-link:hover {text-decoration:underline;}
    .alert {
      background:#ffd5d5;
      color:#a10000;
      padding:0.7rem;
      border-radius:6px;
      margin-bottom:1rem;
      font-size:0.9rem;
    }

    /* ===== Breakpoints ===== */

    /* Extra small (phones <576px) → stack top/bottom */
    @media (max-width: 575px) {
      .container {flex-direction:column;height:auto;}
      .left-panel,.right-panel {width:100%;padding:1.5rem;}
      .left-panel img {width:220px;}
      .left-panel h1 {font-size:1.3rem;}
      .left-panel p {font-size:0.9rem;}
      .login-card {padding:1.5rem;max-width:100%;}
      input,.btn-primary {font-size:0.9rem;}
    }

    /* Small devices (phones landscape / small tablets: 576px–767px) → stack */
    @media (min-width:576px) and (max-width:767px) {
      .container {flex-direction:column;height:auto;}
      .left-panel img {width:280px;}
      .left-panel h1 {font-size:1.5rem;}
      .login-card {max-width:100%;padding:2rem;}
    }

    /* Medium devices (tablets: 768px–991px) → still LEFT/RIGHT */
    @media (min-width:768px) and (max-width:991px) {
      .container {flex-direction:row;height:auto;}
      .left-panel img {width:340px;}
      .left-panel h1 {font-size:1.6rem;}
    }

    /* Large devices (laptops: 992px–1199px) → LEFT/RIGHT */
    @media (min-width:992px) and (max-width:1199px) {
      .container {flex-direction:row;height:auto;}
      .left-panel img {width:420px;}
      .left-panel h1 {font-size:1.9rem;}
    }

    /* Extra large devices (>=1200px desktops / ultrawide) */
    @media (min-width:1200px) {
      .container {flex-direction:row;height:100vh;}
      .left-panel img {width:550px;}
      .left-panel h1 {font-size:2.2rem;}
      .left-panel p {font-size:1.1rem;}
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Left -->
    <div class="left-panel">
      <img src="asstes/logo1.png" >
      <h1>MICRO FINANCE BANKING SYSTEM</h1>
      <p>Securely manage accounts, transactions, and reports with our admin dashboard.</p>
      <a href="docs/index.html" class="btn-docs">Check it out</a>
    </div>

    <!-- Right -->
    <div class="right-panel">
      <div class="login-card">
        <?php if (!$step): ?>
          <h2><i class="fa-solid fa-vault"></i> Admin Login</h2>
          <?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>
          <form method="post" action="backend/login.php" autocomplete="off">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required autofocus>
            <label>Password</label>
            <div class="pwd-wrap">
              <input type="password" id="password" name="password" placeholder="Enter password" required>
              <button type="button" class="eye-btn" id="togglePwd"><i class="fa-regular fa-eye"></i></button>
            </div>
            <a href="#" class="forgot-link">Forgot Password?</a>
            <button type="submit" class="btn-primary">Login</button>
          </form>
        <?php else: ?>
          <h2><i class="fa-solid fa-key"></i> Enter OTP</h2>
          <?php if ($err): ?><div class="alert"><?=htmlspecialchars($err)?></div><?php endif; ?>
          <form method="post" action="backend/verify_otp.php" autocomplete="off">
            <label>One-Time Password (OTP)</label>
            <input type="text" name="otp" placeholder="Enter the OTP" required autofocus>
            <button type="submit" class="btn-primary">Verify OTP</button>
          </form>
          <p style="margin-top:1rem;font-size:0.85rem;color:#555;">
            (For demo, OTP is <strong><?= $_SESSION['otp_code'] ?? '' ?></strong>)
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    const togglePwd = document.getElementById("togglePwd");
    const pwdField = document.getElementById("password");
    if(togglePwd){
      togglePwd.addEventListener("click",()=>{
        if(pwdField.type==="password"){
          pwdField.type="text";
          togglePwd.innerHTML='<i class="fa-regular fa-eye-slash"></i>';
        }else{
          pwdField.type="password";
          togglePwd.innerHTML='<i class="fa-regular fa-eye"></i>';
        }
      });
    }
  </script>
</body>
</html>
