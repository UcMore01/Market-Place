<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pages - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .footer { background: #1a1d20; color: #adb5bd; }
        .footer a { color: #adb5bd; text-decoration: none; }
        .footer a:hover { color: #fff; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">Marketplace</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.html">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="categories.html">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog.html">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages.php">Pages</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="fw-bold mb-4">Information Pages</h2>
        <div id="pagesList">
            <div class="text-center text-muted py-4">Loading pages...</div>
        </div>
    </div>

    <footer class="footer py-5 mt-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-md-4">
            <h5 class="text-white mb-3">Marketplace</h5>
            <p class="small">Your trusted online marketplace for buying and selling quality products from top sellers worldwide.</p>
          </div>
          <div class="col-md-2">
            <h6 class="text-white mb-3">Quick Links</h6>
            <ul class="list-unstyled small">
              <li><a href="index.html">Home</a></li>
              <li><a href="products.html">Products</a></li>
              <li><a href="pages.php">Pages</a></li>
              <li><a href="contact.html">Contact</a></li>
            </ul>
          </div>
          <div class="col-md-2">
            <h6 class="text-white mb-3">Support</h6>
            <ul class="list-unstyled small">
              <li><a href="faq.html">FAQ</a></li>
              <li><a href="refund_return_policy.html">Refund Policy</a></li>
              <li><a href="terms_condition.html">Terms & Conditions</a></li>
              <li><a href="privacy_policy.html">Privacy Policy</a></li>
            </ul>
          </div>
          <div class="col-md-4">
            <h6 class="text-white mb-3">Stay Connected</h6>
            <p class="small">Subscribe to get special offers and updates.</p>
            <form class="d-flex" onsubmit="event.preventDefault(); alert('Subscribed!');">
              <input class="form-control me-2" type="email" placeholder="Enter your email" aria-label="Email">
              <button class="btn btn-primary rounded-pill px-3" type="submit">Join</button>
            </form>
          </div>
        </div>
        <hr class="my-4" style="border-color: #343a40;">
        <div class="text-center small">
          &copy; 2026 Marketplace. All Rights Reserved.
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        fetch('admin_content/list.php')
            .then(res => res.json())
            .then(list => {
                const pages = (list || []).filter(c => c.type === 'page' && c.status === 'active');
                const container = document.getElementById('pagesList');
                if (!pages.length) {
                    container.innerHTML = '<div class="alert alert-info">No published pages yet.</div>';
                    return;
                }
                container.innerHTML = '<div class="list-group">' + pages.map(p => `
                    <a href="page.php?id=${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>${p.title}</span>
                        <span class="badge bg-primary rounded-pill">View</span>
                    </a>
                `).join('') + '</div>';
            })
            .catch(() => {
                document.getElementById('pagesList').innerHTML = '<div class="alert alert-danger">Failed to load pages.</div>';
            });
    </script>
<script src="auto_refresh.js"></script>
</body>
</html>
