<?php
// Single admin registration entry point. Buyers/sellers are blocked.
// If no admin exists yet, this page is open so the first admin can be
// created; otherwise it requires an authenticated admin session.
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db_conn.php';

if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['buyer', 'seller'])) {
    $dash = ($_SESSION['user_role'] === 'seller') ? 'seller_dashboard.html' : 'buyer_dashboard.html';
    header('Location: ' . $dash);
    exit;
}

$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$firstSetup = ($adminCount === 0);

if (!$firstSetup && (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin')) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="description" content="Marketplace - Register a new admin account">
    <meta name="keywords" content="marketplace, admin, register">
    <meta name="author" content="Marketplace">

  <title>Register Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .login-container {
      max-width: 420px;
      margin: 60px auto;
      padding: 30px 25px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.07);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2 class="mb-3 text-center">Register Admin</h2>
    <p class="text-muted text-center small">Create a new admin account.</p>
    <div id="register-error" class="alert alert-danger d-none"></div>
    <div id="register-info" class="alert alert-success d-none"></div>
    <form id="adminRegisterForm" autocomplete="off">
      <input type="hidden" id="csrfToken" name="csrf_token" value="">
      <div class="mb-3">
        <label for="fullname" class="form-label">Full Name</label>
        <input type="text" id="fullname" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" class="form-control" minlength="6" required>
      </div>
      <button type="submit" class="btn btn-success w-100">Create Account</button>
      <div class="text-center mt-3">
        <a href="admin_dashboard.php" class="small text-decoration-none">Back to dashboard</a>
      </div>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let csrfToken = '';

    (async function loadCsrf() {
      try {
        const res = await fetch('csrf_token.php', { credentials: 'same-origin' });
        const data = await res.json();
        if (data.csrf_token) csrfToken = data.csrf_token;
      } catch (e) { console.error('Failed to load CSRF token'); }
    })();

    document.getElementById('adminRegisterForm').onsubmit = function(e) {
      e.preventDefault();
      const errBox = document.getElementById('register-error');
      const infoBox = document.getElementById('register-info');
      errBox.classList.add('d-none');
      infoBox.classList.add('d-none');

      const fullname = document.getElementById('fullname').value.trim();
      const username = document.getElementById('username').value.trim();
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;

      fetch('admin_api/register.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fullname, username, email, password, csrf_token: csrfToken })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          infoBox.textContent = data.message;
          infoBox.classList.remove('d-none');
          document.getElementById('adminRegisterForm').reset();
        } else {
          errBox.textContent = data.message || 'Registration failed.';
          errBox.classList.remove('d-none');
        }
      })
      .catch(() => {
        errBox.textContent = 'Registration failed. Please try again.';
        errBox.classList.remove('d-none');
      });
    };
  </script>
<script src="auto_refresh.js"></script>
</body>
</html>
