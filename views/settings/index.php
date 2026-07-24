<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Settings - Enterprise License Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-panel { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; }
        .form-control, .form-select { background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; border-radius: 10px; }
    </style>
</head>
<body class="py-4">

    <div class="container px-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-gears text-primary me-2"></i>Platform System Settings</h3>
                <p class="text-secondary small mb-0">Configure site parameters, default prefixes, security thresholds, and storage drivers</p>
            </div>
        </div>

        <div id="settings-alert-container"></div>

        <div class="glass-panel p-4 mb-4">
            <form id="settings-form">
                <div class="row g-4">
                    <div class="col-12"><h6 class="fw-bold text-primary border-bottom border-secondary pb-2"><i class="fa-solid fa-sliders me-2"></i>General System Settings</h6></div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">System Platform Title</label>
                        <input type="text" class="form-control" name="site_name" value="<?php echo $_ENV['APP_NAME'] ?? 'Enterprise License Manager'; ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Application Timezone</label>
                        <select class="form-select" name="timezone">
                            <option value="UTC" selected>UTC (Coordinated Universal Time)</option>
                            <option value="America/New_York">America/New_York (EST)</option>
                            <option value="Europe/London">Europe/London (GMT)</option>
                            <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4"><h6 class="fw-bold text-warning border-bottom border-secondary pb-2"><i class="fa-solid fa-key me-2"></i>Licensing Defaults</h6></div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Default Key Prefix</label>
                        <input type="text" class="form-control" name="license_prefix" value="GB" maxlength="10">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Default License Duration (Days)</label>
                        <input type="number" class="form-control" name="default_expiry_days" value="30">
                    </div>

                    <div class="col-12 mt-4"><h6 class="fw-bold text-danger border-bottom border-secondary pb-2"><i class="fa-solid fa-shield-halved me-2"></i>Security Policy Parameters</h6></div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Failed Login Lockout Threshold (Attempts)</label>
                        <input type="number" class="form-control" name="account_lockout_attempts" value="5">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Account Lockout Duration (Minutes)</label>
                        <input type="number" class="form-control" name="account_lockout_duration_minutes" value="15">
                    </div>

                    <div class="col-12 mt-4"><h6 class="fw-bold text-info border-bottom border-secondary pb-2"><i class="fa-solid fa-database me-2"></i>Storage Driver Configuration</h6></div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary">Active Primary Storage Driver</label>
                        <select class="form-select" name="storage_driver">
                            <option value="local" selected>Local File System Storage</option>
                            <option value="supabase">Supabase Storage Bucket</option>
                            <option value="s3">Amazon Web Services S3</option>
                            <option value="r2">Cloudflare R2 Storage</option>
                        </select>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save System Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('elms_access_token');
            const alertBox = document.getElementById('settings-alert-container');

            document.getElementById('settings-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                alertBox.innerHTML = '';

                const formData = new FormData(e.target);
                const settingsObj = {};
                formData.forEach((val, key) => settingsObj[key] = val);

                try {
                    const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/settings/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ settings: settingsObj })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        alertBox.innerHTML = `<div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i> Settings saved successfully!</div>`;
                    } else {
                        alertBox.innerHTML = `<div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i> ${data.message}</div>`;
                    }
                } catch (err) {
                    alertBox.innerHTML = `<div class="alert alert-danger">Network error occurred.</div>`;
                }
            });
        });
    </script>
</body>
</html>
