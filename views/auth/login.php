<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Enterprise License Management Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient-dark: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --primary-accent: #6366f1;
            --primary-hover: #4f46e5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--primary-accent);
            color: #ffffff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(99, 102, 241, 0.4);
            color: #ffffff;
        }

        .theme-toggle-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: #ffffff;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>

    <!-- Dark/Light Theme Toggle -->
    <button class="theme-toggle-btn" id="theme-toggle-btn" title="Toggle Light/Dark Theme">
        <i class="fa-solid fa-moon" id="theme-icon"></i>
    </button>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                <div class="glass-card p-4 p-sm-5">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <div class="brand-logo mx-auto mb-3">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Enterprise Platform</h4>
                        <p class="text-secondary small mb-0">License Management SaaS Console</p>
                    </div>

                    <!-- Alert Container -->
                    <div id="login-alert-container"></div>

                    <!-- Login Form -->
                    <form id="login-form">
                        <input type="hidden" id="csrf-token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="input-email" class="form-label small fw-semibold text-secondary">Work Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 border-secondary text-secondary">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control border-start-0 ps-0" id="input-email" name="email" placeholder="admin@system.com" required autocomplete="email">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="input-password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 border-secondary text-secondary">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 ps-0" id="input-password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                            </div>
                        </div>

                        <!-- 2FA Code Input (Hidden by Default) -->
                        <div class="mb-4 d-none" id="totp-container">
                            <label for="input-totp" class="form-label small fw-semibold text-warning">Two-Factor Authenticator Code</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 border-warning text-warning">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0 border-warning text-warning" id="input-totp" name="totp_code" placeholder="6-Digit OTP Code" maxlength="6">
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary-custom w-100 mb-3" id="btn-login-submit">
                            <span id="btn-text"><i class="fa-solid fa-right-to-bracket me-2"></i> Sign In to Dashboard</span>
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>

                    </form>

                    <!-- Footer Info -->
                    <div class="text-center mt-3 pt-3 border-top border-secondary border-opacity-25">
                        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill">
                            <i class="fa-solid fa-lock me-1"></i> 256-Bit Argon2id Encrypted
                        </span>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX Client Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('login-form');
            const emailInput = document.getElementById('input-email');
            const passwordInput = document.getElementById('input-password');
            const totpInput = document.getElementById('input-totp');
            const totpContainer = document.getElementById('totp-container');
            const alertContainer = document.getElementById('login-alert-container');
            const btnSubmit = document.getElementById('btn-login-submit');
            const btnText = document.getElementById('btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            const csrfToken = document.getElementById('csrf-token').value;

            // Theme Toggle
            const themeBtn = document.getElementById('theme-toggle-btn');
            const themeIcon = document.getElementById('theme-icon');
            let isDark = true;

            themeBtn.addEventListener('click', () => {
                isDark = !isDark;
                document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
                themeIcon.className = isDark ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            });

            // Form Submit Handler
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                alertContainer.innerHTML = '';

                // UI Loading state
                btnSubmit.disabled = true;
                btnText.classList.add('d-none');
                btnSpinner.classList.remove('d-none');

                const payload = {
                    email: emailInput.value.trim(),
                    password: passwordInput.value,
                    csrf_token: csrfToken
                };

                if (totpInput.value.trim().length > 0) {
                    payload.totp_code = totpInput.value.trim();
                }

                try {
                    const response = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        if (data.data && data.data.requires_2fa) {
                            totpContainer.classList.remove('d-none');
                            alertContainer.innerHTML = `
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fa-solid fa-shield-cat me-2"></i> Two-Factor Authentication required. Please enter your 6-digit TOTP code.
                                </div>
                            `;
                            totpInput.focus();
                        } else {
                            // Store JWT Access Token
                            if (data.data && data.data.access_token) {
                                localStorage.setItem('elms_access_token', data.data.access_token);
                                localStorage.setItem('elms_refresh_token', data.data.refresh_token);
                                localStorage.setItem('elms_user', JSON.stringify(data.data.user));
                            }

                            alertContainer.innerHTML = `
                                <div class="alert alert-success fade show" role="alert">
                                    <i class="fa-solid fa-circle-check me-2"></i> Authentication successful! Redirecting...
                                </div>
                            `;

                            setTimeout(() => {
                                window.location.href = '<?php echo $_ENV['APP_URL'] ?? ''; ?>/dashboard';
                            }, 1000);
                        }
                    } else {
                        const errorMsg = data.message || 'Authentication failed.';
                        alertContainer.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> ${errorMsg}
                            </div>
                        `;
                    }
                } catch (err) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-xmark me-2"></i> Connection error. Please check network and server.
                        </div>
                    `;
                } finally {
                    btnSubmit.disabled = false;
                    btnText.classList.remove('d-none');
                    btnSpinner.classList.add('d-none');
                }
            });
        });
    </script>
</body>
</html>
