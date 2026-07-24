<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Enterprise License Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-glass: rgba(255, 255, 255, 0.1);
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-dark); color: #f8fafc; overflow-x: hidden; }
        .glass-panel { background: var(--panel-bg); backdrop-filter: blur(14px); border: 1px solid var(--border-glass); border-radius: 16px; }
        .stat-card { background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.9) 100%); border: 1px solid var(--border-glass); border-radius: 16px; padding: 20px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .sidebar { min-height: 100vh; background: rgba(15, 23, 42, 0.95); border-right: 1px solid var(--border-glass); }
        .sidebar .nav-link { color: #94a3b8; font-weight: 500; padding: 12px 20px; border-radius: 10px; margin-bottom: 4px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffffff; background: rgba(99, 102, 241, 0.15); border-left: 3px solid #6366f1; }
        .top-navbar { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border-glass); }
    </style>
</head>
<body>

    <div class="d-flex">
        <!-- Sidebar Navigation -->
        <div class="sidebar p-3 d-none d-lg-block" style="width: 260px;">
            <div class="d-flex align-items-center gap-2 mb-4 px-2">
                <div class="p-2 bg-primary text-white rounded-3 fs-4"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <h6 class="fw-bold mb-0 text-white">ELMS Enterprise</h6>
                    <small class="text-secondary" style="font-size: 11px;">v1.0 SaaS Platform</small>
                </div>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/dashboard" class="nav-link active"><i class="fa-solid fa-chart-line me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses" class="nav-link"><i class="fa-solid fa-key me-2"></i> Licenses</a></li>
                <li class="nav-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses/create" class="nav-link"><i class="fa-solid fa-plus me-2"></i> Generate Keys</a></li>
                <li class="nav-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/devices" class="nav-link"><i class="fa-solid fa-laptop me-2"></i> Devices</a></li>
                <li class="nav-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/logout" class="nav-link text-danger mt-4"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="flex-grow-1">
            <!-- Top Navbar -->
            <nav class="top-navbar px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Executive Dashboard</h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                        <i class="fa-solid fa-circle me-1" style="font-size: 8px;"></i> System Online
                    </span>
                    <span class="text-secondary small" id="current-user-display">Super Admin</span>
                </div>
            </nav>

            <div class="p-4">
                <!-- Metrics Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">TOTAL LICENSES</span>
                                <i class="fa-solid fa-key text-primary fs-5"></i>
                            </div>
                            <h2 class="fw-bold mb-0" id="metric-total-licenses">0</h2>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">ACTIVE LICENSES</span>
                                <i class="fa-solid fa-circle-check text-success fs-5"></i>
                            </div>
                            <h2 class="fw-bold mb-0 text-success" id="metric-active-licenses">0</h2>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">TODAY'S ACTIVATIONS</span>
                                <i class="fa-solid fa-bolt text-warning fs-5"></i>
                            </div>
                            <h2 class="fw-bold mb-0 text-warning" id="metric-today-activations">0</h2>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small fw-semibold">ACTIVE PRODUCTS</span>
                                <i class="fa-solid fa-box text-info fs-5"></i>
                            </div>
                            <h2 class="fw-bold mb-0 text-info" id="metric-total-products">0</h2>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row g-4 mb-4">
                    <div class="col-12 col-xl-8">
                        <div class="glass-panel p-4 h-100">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-area text-primary me-2"></i>Monthly License Growth</h6>
                            <canvas id="growthChart" height="220"></canvas>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="glass-panel p-4 h-100">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie text-info me-2"></i>Status Distribution</h6>
                            <canvas id="statusChart" height="220"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="row g-4">
                    <div class="col-12 col-xl-6">
                        <div class="glass-panel p-4 h-100">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-laptop text-warning me-2"></i>Recent Device Activations</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle small mb-0">
                                    <thead>
                                        <tr>
                                            <th>License Key</th>
                                            <th>Product</th>
                                            <th>Fingerprint</th>
                                            <th>Activated</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-activations-body">
                                        <tr><td colspan="4" class="text-center text-secondary">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="glass-panel p-4 h-100">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>Recent System Audit Trail</h6>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle small mb-0">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Description</th>
                                            <th>User</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recent-logs-body">
                                        <tr><td colspan="4" class="text-center text-secondary">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- AJAX Dashboard Loader Script -->
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const token = localStorage.getItem('elms_access_token');

            try {
                const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/dashboard', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    const m = data.data.metrics;
                    document.getElementById('metric-total-licenses').innerText = m.total_licenses;
                    document.getElementById('metric-active-licenses').innerText = m.active_licenses;
                    document.getElementById('metric-today-activations').innerText = m.today_activations;
                    document.getElementById('metric-total-products').innerText = m.total_products;

                    // Render Growth Chart
                    const growthCtx = document.getElementById('growthChart').getContext('2d');
                    const growthData = data.data.charts.monthly_growth;
                    new Chart(growthCtx, {
                        type: 'line',
                        data: {
                            labels: growthData.map(g => g.month_label),
                            datasets: [{
                                label: 'Licenses Generated',
                                data: growthData.map(g => g.count),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { display: false } } }
                    });

                    // Render Status Pie Chart
                    const statusCtx = document.getElementById('statusChart').getContext('2d');
                    const sb = data.data.charts.status_breakdown;
                    new Chart(statusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Active', 'Expired', 'Revoked', 'Suspended'],
                            datasets: [{
                                data: [sb.active, sb.expired, sb.revoked, sb.suspended],
                                backgroundColor: ['#22c55e', '#eab308', '#ef4444', '#a855f7']
                            }]
                        },
                        options: { responsive: true }
                    });

                    // Render Activations Table
                    const actBody = document.getElementById('recent-activations-body');
                    actBody.innerHTML = data.data.recent_activations.map(a => `
                        <tr>
                            <td class="font-monospace text-warning">${a.license_key}</td>
                            <td>${a.product_name}</td>
                            <td class="font-monospace text-info">${a.device_fingerprint.substring(0, 12)}...</td>
                            <td class="text-secondary">${new Date(a.activation_date).toLocaleTimeString()}</td>
                        </tr>
                    `).join('');

                    // Render Audit Logs Table
                    const logsBody = document.getElementById('recent-logs-body');
                    logsBody.innerHTML = data.data.recent_logs.map(l => `
                        <tr>
                            <td><span class="badge bg-secondary">${l.action}</span></td>
                            <td>${l.description}</td>
                            <td>${l.full_name || 'System'}</td>
                            <td class="text-secondary">${new Date(l.created_at).toLocaleTimeString()}</td>
                        </tr>
                    `).join('');
                }
            } catch (err) {
                console.error('Failed to load dashboard data', err);
            }
        });
    </script>
</body>
</html>
