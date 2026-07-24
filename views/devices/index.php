<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Management - Enterprise License Management Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; }
        .stat-card { background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.8) 100%); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 20px; }
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
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-laptop text-primary me-2"></i>Device Management</h3>
                <p class="text-secondary small mb-0">Monitor active hardware & browser activations, deactivate devices, and manage session heartbeats</p>
            </div>
            <a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-rectangle-list me-1"></i> View License Registry
            </a>
        </div>

        <!-- Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-25 text-primary rounded-3 fs-3">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold text-uppercase">Total Active Devices</div>
                        <h2 class="fw-bold mb-0" id="stat-total-devices"><?php echo number_format($totalDevices); ?></h2>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-4">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="p-3 bg-info bg-opacity-25 text-info rounded-3 fs-3">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-semibold text-uppercase">Licenses with Active Devices</div>
                        <h2 class="fw-bold mb-0"><?php echo number_format($totalLicensesWithDevices); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-panel p-3 mb-4">
            <div class="row g-2">
                <div class="col-12 col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="device-search" placeholder="Search by fingerprint, IP address, license key, browser, OS...">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <button class="btn btn-secondary w-100" id="btn-device-search">
                        <i class="fa-solid fa-filter me-1"></i> Search Devices
                    </button>
                </div>
            </div>
        </div>

        <!-- Devices Data Table -->
        <div class="glass-panel p-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Device Fingerprint</th>
                            <th>License Key</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>IP & Location</th>
                            <th>OS & Browser</th>
                            <th>Last Seen</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="device-table-body">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">
                                <span class="spinner-border spinner-border-sm me-2"></span> Loading active devices...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-secondary small" id="device-pagination-info">Showing 0 of 0 devices</span>
            <ul class="pagination pagination-sm mb-0" id="device-pagination"></ul>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('elms_access_token');
            let currentPage = 1;

            const baseUrl = window.location.origin;

            async function loadDevices(page = 1) {
                currentPage = page;
                const search = document.getElementById('device-search').value;

                const url = new URL(baseUrl + '/api/v1/devices');
                url.searchParams.append('page', page);
                url.searchParams.append('per_page', 15);
                if (search) url.searchParams.append('search', search);

                try {
                    const res = await fetch(url, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        renderTable(data.data);
                        renderPagination(data.meta);
                    }
                } catch (err) {
                    console.error('Failed to load devices', err);
                }
            }

            function renderTable(items) {
                const tbody = document.getElementById('device-table-body');
                if (!items || items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-secondary">No active devices found.</td></tr>`;
                    return;
                }

                tbody.innerHTML = items.map(d => {
                    const fingerprintShort = d.device_fingerprint.substring(0, 16) + '...';
                    const lastSeenText = new Date(d.last_seen).toLocaleString();
                    const custName = d.customer_name || '<span class="text-secondary">Unassigned</span>';

                    return `
                        <tr>
                            <td class="font-monospace text-info small" title="${d.device_fingerprint}">
                                <i class="fa-solid fa-fingerprint me-1"></i> ${fingerprintShort}
                            </td>
                            <td class="font-monospace fw-bold text-warning">${d.license_key}</td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-light">${d.product_name}</span></td>
                            <td>${custName}</td>
                            <td><small><i class="fa-solid fa-network-wired me-1"></i> ${d.ip_address || 'Unknown'}</small></td>
                            <td><small><i class="fa-brands fa-windows me-1"></i> ${d.os || 'OS'} / ${d.browser || 'Browser'}</small></td>
                            <td><small class="text-secondary">${lastSeenText}</small></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">Manage</button>
                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="deactivateDevice(${d.id})"><i class="fa-solid fa-power-off text-warning me-2"></i>Deactivate Device</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="forceLogout(${d.id})"><i class="fa-solid fa-right-from-bracket text-info me-2"></i>Force Logout Session</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="resetLicenseDevices(${d.license_id})"><i class="fa-solid fa-rotate-left me-2"></i>Reset All License Devices</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
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

            function renderPagination(meta) {
                if (!meta) return;
                document.getElementById('device-pagination-info').innerText = `Showing page ${meta.page} of ${meta.total_pages} (${meta.total} total devices)`;
            }

            let searchTimeout;
            document.getElementById('device-search').addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadDevices(1), 300);
            });

            document.getElementById('btn-device-search').addEventListener('click', () => loadDevices(1));
            loadDevices(1);

            window.deactivateDevice = async (id) => {
                if (!confirm('Are you sure you want to deactivate this device?')) return;
                try {
                    const res = await fetch(baseUrl + '/api/v1/device/deactivate', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ device_id: id })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('Device deactivated successfully.', 'success');
                    } else {
                        showToast(data.message || 'Deactivation failed', 'danger');
                    }
                } catch (e) {
                    showToast('Error deactivating device', 'danger');
                }
                loadDevices(currentPage);
            };

            window.forceLogout = async (id) => {
                if (!confirm('Force logout active session for this device?')) return;
                try {
                    const res = await fetch(baseUrl + '/api/v1/device/force-logout', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ device_id: id })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('Session logged out successfully.', 'success');
                    }
                } catch (e) {
                    showToast('Error forcing session logout', 'danger');
                }
                loadDevices(currentPage);
            };

            window.resetLicenseDevices = async (licenseId) => {
                if (!confirm('Are you sure you want to flush and reset ALL devices for this license?')) return;
                try {
                    const res = await fetch(baseUrl + '/api/v1/device/reset', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ license_id: licenseId })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('All devices for license reset successfully.', 'success');
                    }
                } catch (e) {
                    showToast('Error resetting license devices', 'danger');
                }
                loadDevices(currentPage);
            };
        });
    </script>
</body>
</html>

