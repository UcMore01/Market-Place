<?php
// Admin login entry point. Server-side guard so buyer/seller sessions
// can never reach the admin portal.
require_once __DIR__ . '/session.php';

if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['buyer', 'seller'])) {
    $dash = ($_SESSION['user_role'] === 'seller') ? 'seller_dashboard.html' : 'buyer_dashboard.html';
    header('Location: ' . $dash);
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="description" content="Marketplace - Admin portal login">
    <meta name="keywords" content="marketplace, admin, login">
    <meta name="author" content="Marketplace">

  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .login-container {
      max-width: 400px;
      margin: 80px auto;
      padding: 30px 25px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.07);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2 class="mb-4 text-center">Admin Login</h2>
    <div id="login-error" class="alert alert-danger d-none"></div>
    <form id="adminLoginForm" autocomplete="off">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('adminLoginForm').onsubmit = function(e) {
      e.preventDefault();
      document.getElementById('login-error').classList.add('d-none');
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;

      fetch('admin_api/login.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = data.redirect || 'admin_dashboard.php';
        } else {
          document.getElementById('login-error').textContent = data.message || "Invalid credentials.";
          document.getElementById('login-error').classList.remove('d-none');
        }
      })
      .catch(() => {
        document.getElementById('login-error').textContent = "Login failed. Please try again.";
        document.getElementById('login-error').classList.remove('d-none');
      });
    };
  </script>
<script src="auto_refresh.js"></script>
</body>
</html>
