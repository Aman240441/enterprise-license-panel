<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee & User Management - Enterprise License Management Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; }
        .form-control, .form-select { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; border-radius: 10px; }
        .table { color: #f8fafc; }
        .table th { background: rgba(15, 23, 42, 0.9); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #94a3b8; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="py-4">

    <div class="container-fluid px-4">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-users-gear text-primary me-2"></i>Employee & User Management</h3>
                <p class="text-secondary small mb-0">Manage system roles, assign generation quotas, configure per-user permissions, and control access</p>
            </div>
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fa-solid fa-user-plus me-1"></i> Create Employee / User
            </button>
        </div>

        <!-- Users Table -->
        <div class="glass-panel p-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Daily Limit</th>
                            <th>Monthly Limit</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <tr><td colspan="8" class="text-center py-4 text-secondary">Loading system users...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-panel border-secondary text-light">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Create New User Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="create-user-form">
                    <div class="modal-body">
                        <div id="modal-alert-container"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Full Name *</label>
                                <input type="text" class="form-control" id="user-full-name" required placeholder="Jane Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Work Email Address *</label>
                                <input type="email" class="form-control" id="user-email" required placeholder="jane@company.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Account Password *</label>
                                <input type="password" class="form-control" id="user-password" required minlength="6" placeholder="••••••••">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">System Role *</label>
                                <select class="form-select" id="user-role" required>
                                    <option value="employee">Employee (Restricted Generation Quotas)</option>
                                    <option value="admin">Admin (Manage Licenses & Plans)</option>
                                    <option value="reseller">Reseller (Wholesale Partner)</option>
                                    <option value="viewer">Viewer (Read-Only Access)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Daily Generation Limit (0 = Unconstrained)</label>
                                <input type="number" class="form-control" id="user-daily-limit" value="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Monthly Generation Limit (0 = Unconstrained)</label>
                                <input type="number" class="form-control" id="user-monthly-limit" value="1000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('elms_access_token');

            async function loadUsers() {
                try {
                    const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/users/list', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        renderTable(data.data);
                    }
                } catch (err) {
                    console.error('Failed to load users', err);
                }
            }

            function renderTable(users) {
                const tbody = document.getElementById('user-table-body');
                if (!users || users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-secondary">No users found.</td></tr>`;
                    return;
                }

                tbody.innerHTML = users.map(u => `
                    <tr>
                        <td class="fw-bold">${u.full_name}</td>
                        <td class="text-secondary">${u.email}</td>
                        <td><span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">${u.role_name}</span></td>
                        <td>${u.daily_gen_limit > 0 ? u.daily_gen_limit + '/day' : 'Unlimited'}</td>
                        <td>${u.monthly_gen_limit > 0 ? u.monthly_gen_limit + '/month' : 'Unlimited'}</td>
                        <td><span class="badge bg-success bg-opacity-25 text-success">${u.status.toUpperCase()}</span></td>
                        <td><small class="text-secondary">${u.last_login_at ? new Date(u.last_login_at).toLocaleString() : 'Never'}</small></td>
                        <td>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteUser(${u.id})"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `).join('');
            }

            // Toast Notification Helper
            window.showToast = function(message, type = 'success') {
                let container = document.getElementById('toast-container');
                if (!container) {
                    document.body.insertAdjacentHTML('beforeend', '<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>');
                    container = document.getElementById('toast-container');
                }
                const toastId = 'toast-' + Date.now();
                const bgClass = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-danger');
                const icon = type === 'success' ? 'fa-circle-check' : (type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark');
                const toastHtml = `
                    <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body d-flex align-items-center gap-2">
                                <i class="fa-solid ${icon} fs-5"></i>
                                <span>${message}</span>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', toastHtml);
                const toastEl = document.getElementById(toastId);
                const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
                bsToast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            };

            document.getElementById('create-user-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const payload = {
                    full_name: document.getElementById('user-full-name').value,
                    email: document.getElementById('user-email').value,
                    password: document.getElementById('user-password').value,
                    role_slug: document.getElementById('user-role').value,
                    daily_gen_limit: parseInt(document.getElementById('user-daily-limit').value),
                    monthly_gen_limit: parseInt(document.getElementById('user-monthly-limit').value)
                };

                try {
                    const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/users/create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                        showToast('User created successfully.', 'success');
                        loadUsers();
                    } else {
                        document.getElementById('modal-alert-container').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                } catch (err) {
                    showToast('User creation failed', 'danger');
                }
            });

            window.deleteUser = async (id) => {
                if (!confirm('Are you sure you want to delete this user?')) return;
                try {
                    const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/users/delete', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ user_id: id })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('User deleted successfully.', 'success');
                    } else {
                        showToast(data.message || 'User deletion failed', 'danger');
                    }
                } catch (err) {
                    showToast('Error deleting user', 'danger');
                }
                loadUsers();
            };

            loadUsers();
        });
    </script>
</body>
</html>

