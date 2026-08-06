<?php require_once 'auth_guard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Blog Posts - Seller Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .stat-card {
            background: #fff; border-radius: 16px; padding: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;
        }
        .img-preview { max-height: 120px; margin-top: 0.5em; }
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
                    <li class="nav-item"><a class="nav-link" href="seller_dashboard.html">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">My Blog Posts</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal" onclick="openModal()">Write New Post</button>
        </div>
        <div id="alert-area"></div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Reactions</th>
                                <th>Comments</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="posts-table">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
              <li><a href="about_us.html">About Us</a></li>
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

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="postForm" onsubmit="event.preventDefault(); savePost();">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Write New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="post-id">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" id="post-title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" id="post-category" class="form-control" placeholder="e.g. Fashion, Electronics">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Summary</label>
                        <input type="text" id="post-summary" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea id="post-content" class="form-control" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input type="file" id="image-upload" accept="image/*" class="form-control" onchange="previewImage(event)">
                        <img id="image-preview" src="" alt="Image Preview" class="img-fluid img-preview d-none">
                        <input type="hidden" id="post-image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Publish Post</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let posts = [], editModal;
        document.addEventListener('DOMContentLoaded', function() {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
            fetchPosts();
        });

        function fetchPosts() {
            fetch('seller_blog_api.php')
                .then(res => res.json())
                .then(data => {
                    posts = data.posts || [];
                    renderPosts();
                })
                .catch(() => {
                    document.getElementById('posts-table').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load posts.</td></tr>';
                });
        }

        function renderPosts() {
            const table = document.getElementById('posts-table');
            if (!posts.length) {
                table.innerHTML = '<tr><td colspan="7" class="text-center">No posts yet. Click "Write New Post" to get started.</td></tr>';
                return;
            }
            table.innerHTML = posts.map(post => `
                <tr>
                    <td>${escapeHTML(post.title)}</td>
                    <td>${escapeHTML(post.category || '-')}</td>
                    <td><span class="badge bg-${post.status === 'published' ? 'success' : 'warning'}">${escapeHTML(post.status)}</span></td>
                    <td>${post.reactions || 0}</td>
                    <td>${post.comment_count || 0}</td>
                    <td>${new Date(post.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-warning me-1" onclick="openModal(${post.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePost(${post.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function openModal(id = 0) {
            document.getElementById('postForm').reset();
            document.getElementById('image-preview').classList.add('d-none');
            document.getElementById('image-preview').src = '';
            document.getElementById('post-id').value = '';
            document.getElementById('post-image').value = '';
            document.getElementById('modalTitle').textContent = id ? 'Edit Post' : 'Write New Post';

            if (id) {
                const post = posts.find(p => p.id == id);
                if (post) {
                    document.getElementById('post-id').value = post.id;
                    document.getElementById('post-title').value = post.title;
                    document.getElementById('post-summary').value = post.summary || '';
                    document.getElementById('post-content').value = post.content || '';
                    document.getElementById('post-category').value = post.category || '';
                    document.getElementById('post-image').value = post.image || '';
                    if (post.image) {
                        const preview = document.getElementById('image-preview');
                        preview.src = post.image;
                        preview.classList.remove('d-none');
                    }
                }
            }
            editModal.show();
        }

        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('image-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
                uploadImage(input.files[0]);
            }
        }

        function uploadImage(file) {
            const formData = new FormData();
            formData.append('image', file);
            fetch('blog_upload.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('post-image').value = data.image;
                    showAlert('Image uploaded!', 'success');
                } else {
                    showAlert(data.error || 'Image upload failed.', 'danger');
                }
            })
            .catch(() => showAlert('Image upload failed.', 'danger'));
        }

        function savePost() {
            const id = document.getElementById('post-id').value;
            const payload = {
                id: id ? parseInt(id) : 0,
                title: document.getElementById('post-title').value.trim(),
                summary: document.getElementById('post-summary').value.trim(),
                content: document.getElementById('post-content').value.trim(),
                category: document.getElementById('post-category').value.trim(),
                image: document.getElementById('post-image').value.trim()
            };
            fetch('seller_blog_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) {
                    showAlert('Published successfully!', 'success');
                    fetchPosts();
                    editModal.hide();
                } else {
                    showAlert(resp.error || 'Error saving post.', 'danger');
                }
            }).catch(() => showAlert('Error saving post.', 'danger'));
        }

        function deletePost(id) {
            if (!confirm('Are you sure you want to delete this post?')) return;
            fetch('seller_blog_api.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            })
            .then(res => res.json())
            .then(resp => {
                if (resp.success) {
                    showAlert('Deleted successfully!', 'success');
                    fetchPosts();
                } else {
                    showAlert(resp.error || 'Error deleting post.', 'danger');
                }
            }).catch(() => showAlert('Error deleting post.', 'danger'));
        }

        function showAlert(msg, type='info') {
            document.getElementById('alert-area').innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${escapeHTML(msg)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
        }

        function escapeHTML(str) {
            return String(str || '').replace(/[&<>"']/g, function(m) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
            });
        }
    </script>
</body>
</html>
