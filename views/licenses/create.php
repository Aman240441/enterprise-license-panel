<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Key Generator - Enterprise License Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.7);
            --border-glass: rgba(255, 255, 255, 0.1);
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-dark); color: #f8fafc; min-height: 100vh; }
        .glass-panel { background: var(--panel-bg); backdrop-filter: blur(14px); border: 1px solid var(--border-glass); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .form-control, .form-select { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.12); color: #f8fafc; border-radius: 10px; padding: 10px 14px; }
        .form-control:focus, .form-select:focus { background: rgba(15, 23, 42, 0.95); border-color: var(--accent-color); color: #fff; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25); }
        .form-control[readonly], .form-control:disabled { background: rgba(15, 23, 42, 0.6); opacity: 1; color: #f59e0b; font-weight: 700; border-color: rgba(245, 158, 11, 0.3); }
        .btn-primary-custom { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; font-weight: 600; padding: 14px 28px; border-radius: 12px; color: #fff; box-shadow: 0 4px 16px rgba(99, 102, 241, 0.35); transition: all 0.2s; }
        .btn-primary-custom:hover { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); transform: translateY(-1px); }
        .preview-card { background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 16px; position: sticky; top: 20px; }
        .preview-key-box { background: rgba(15, 23, 42, 0.85); border: 1px dashed rgba(234, 179, 8, 0.5); border-radius: 12px; font-family: monospace; letter-spacing: 1px; }
        .step-badge { width: 28px; height: 28px; background: #6366f1; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; margin-right: 8px; }
        optgroup { background: #1e293b; color: #818cf8; font-weight: 700; font-style: normal; }
        option { background: #0f172a; color: #f8fafc; font-weight: 400; padding: 8px; }
        .plan-description-box { background: rgba(99, 102, 241, 0.1); border: 1px border-subtle border-primary border-opacity-25; border-radius: 8px; padding: 8px 12px; }
    </style>
</head>
<body class="py-4">

    <div class="container" style="max-width: 1100px;">
        
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1"><i class="fa-solid fa-wand-magic-sparkles text-primary me-2"></i>License Generator</h3>
                <p class="text-secondary small mb-0">Generate instant, secure random license keys for <strong>Chrome Extension</strong></p>
            </div>
            <a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Licenses
            </a>
        </div>

        <?php 
            // Extract single Chrome Extension product ID
            $chromeProduct = $products[0] ?? null;
            if (!$chromeProduct) {
                // Fallback: fetch directly from DB
                $chromeProduct = \App\Database\DatabaseConnection::fetchOne("SELECT id FROM products WHERE status='active' LIMIT 1");
            }
            $chromeProductId = $chromeProduct['id'] ?? 0;

            // Group Plans by Category
            $categories = [
                'Standard' => 'STANDARD PLANS',
                'Business' => 'BUSINESS PLANS',
                'Partner'  => 'PARTNER PLANS'
            ];
            $groupedPlans = [];
            foreach ($plans as $p) {
                $cat = $p['category'] ?? 'Standard';
                $groupedPlans[$cat][] = $p;
            }
        ?>

        <div id="gen-alert-container"></div>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: Generator Form -->
            <div class="col-lg-7">
                <div class="glass-panel p-4">
                    <form id="single-gen-form">
                        <input type="hidden" id="single-product-id" value="<?php echo $chromeProductId; ?>">

                        <!-- STEP 1: SELECT PLAN & READONLY CALCULATED EXPIRY -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-white mb-3 d-flex align-items-center">
                                <span class="step-badge">1</span> Step 1: Select Plan *
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-semibold text-secondary">License Plan</label>
                                    <select class="form-select form-select-lg" id="single-plan-id" required>
                                        <?php foreach ($categories as $catKey => $catLabel): ?>
                                            <?php if (!empty($groupedPlans[$catKey])): ?>
                                                <optgroup label="<?php echo $catLabel; ?>">
                                                    <?php foreach ($groupedPlans[$catKey] as $idx => $pl): ?>
                                                        <option value="<?php echo $pl['id']; ?>" 
                                                                data-slug="<?php echo $pl['slug']; ?>" 
                                                                data-duration="<?php echo $pl['duration_type']; ?>" 
                                                                data-days="<?php echo $pl['duration_days']; ?>" 
                                                                data-name="<?php echo htmlspecialchars($pl['name']); ?>"
                                                                data-desc="<?php echo htmlspecialchars($pl['description'] ?? ''); ?>"
                                                                data-category="<?php echo $catKey; ?>"
                                                                <?php echo ($catKey === 'Standard' && $idx === 0) ? 'selected' : ''; ?>>
                                                            • <?php echo htmlspecialchars($pl['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold text-secondary">Calculated Expiry Date</label>
                                    <input type="text" class="form-control form-control-lg font-monospace text-warning text-center" id="single-calculated-expiry-input" readonly disabled>
                                </div>

                                <!-- Selected Plan Description Display -->
                                <div class="col-12 mt-2">
                                    <div class="plan-description-box text-info small d-flex align-items-center">
                                        <i class="fa-solid fa-circle-info me-2"></i>
                                        <span id="selected-plan-desc-text">7-day full feature trial period</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: CUSTOMER INFORMATION (OPTIONAL) & DEVICE LIMIT -->
                        <div class="mb-4 pt-3 border-top border-secondary border-opacity-25">
                            <h6 class="fw-bold text-white mb-3 d-flex align-items-center">
                                <span class="step-badge">2</span> Step 2: Device Limit & Customer Details <span class="text-secondary small ms-2 fw-normal">(Optional)</span>
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Allowed Device Limit</label>
                                    <input type="number" class="form-control" id="single-allowed-devices" value="1" min="1" max="1000" placeholder="1">
                                    <span class="form-text small text-secondary" style="font-size: 11px;">Default is 1. Editable by Super Admin.</span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Customer Name</label>
                                    <input type="text" class="form-control" id="single-cust-name" placeholder="John Doe">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Email Address</label>
                                    <input type="email" class="form-control" id="single-cust-email" placeholder="john@example.com">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Phone Number</label>
                                    <input type="text" class="form-control" id="single-cust-phone" placeholder="+1 555-0199">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Company</label>
                                    <input type="text" class="form-control" id="single-cust-company" placeholder="Acme Inc">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-secondary">Country</label>
                                    <input type="text" class="form-control" id="single-cust-country" placeholder="United States">
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3: GENERATE BUTTON -->
                        <div class="pt-3 border-top border-secondary border-opacity-25">
                            <h6 class="fw-bold text-white mb-3 d-flex align-items-center">
                                <span class="step-badge">3</span> Step 3: Generate License Key
                            </h6>
                            <button type="submit" class="btn btn-primary-custom w-100 text-uppercase tracking-wider" id="btn-single-gen">
                                <i class="fa-solid fa-key me-2"></i>Generate License Key
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: LIVE PREVIEW & RESULT CARD -->
            <div class="col-lg-5">
                <div class="preview-card p-4 shadow-lg">
                    
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-1 rounded-pill small">
                            <i class="fa-solid fa-eye me-1"></i> Real-time Live Preview
                        </span>
                        <span class="text-secondary small font-monospace">Prefix: GB</span>
                    </div>

                    <!-- Live Key Format Box -->
                    <div class="preview-key-box p-3 text-center mb-4">
                        <small class="text-secondary d-block text-uppercase mb-1" style="font-size: 11px;">License Key Preview</small>
                        <div class="fs-4 font-monospace text-warning fw-bold user-select-all" id="preview-key-format">
                            GB-XXXX-XXXX-XXXX-XXXX
                        </div>
                    </div>

                    <!-- Live Details List -->
                    <div class="list-group list-group-flush bg-transparent small mb-3">
                        <div class="list-group-item bg-transparent text-secondary border-secondary border-opacity-25 d-flex justify-content-between py-2">
                            <span><i class="fa-brands fa-chrome text-primary me-2"></i>Product:</span>
                            <strong class="text-white">Chrome Extension</strong>
                        </div>
                        
                        <div class="list-group-item bg-transparent text-secondary border-secondary border-opacity-25 d-flex justify-content-between py-2">
                            <span><i class="fa-solid fa-box text-info me-2"></i>Plan:</span>
                            <strong class="text-info" id="preview-plan-name">Free Trial (7 Days)</strong>
                        </div>

                        <div class="list-group-item bg-transparent text-secondary border-secondary border-opacity-25 d-flex justify-content-between py-2">
                            <span><i class="fa-regular fa-calendar-check text-warning me-2"></i>Calculated Expiry:</span>
                            <strong class="text-warning" id="preview-expiry-date">--</strong>
                        </div>

                        <div class="list-group-item bg-transparent text-secondary border-secondary border-opacity-25 d-flex justify-content-between py-2">
                            <span><i class="fa-solid fa-laptop text-success me-2"></i>Device Limit:</span>
                            <strong class="text-success" id="preview-device-limit">1</strong>
                        </div>

                        <div class="list-group-item bg-transparent text-secondary border-secondary border-opacity-25 d-flex justify-content-between py-2" id="preview-customer-row" style="display: none;">
                            <span><i class="fa-solid fa-user me-2"></i>Customer:</span>
                            <strong class="text-white" id="preview-customer-name">--</strong>
                        </div>
                    </div>

                    <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 text-center text-secondary small">
                        <i class="fa-solid fa-shield-halved me-1 text-primary"></i> 256-bit Cryptographically Secure & Unique
                    </div>

                </div>

                <!-- Generated Result Container (Appears Below Preview when generated) -->
                <div id="gen-result-container" class="mt-4"></div>
            </div>

        </div>

    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Real-time Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('elms_access_token');
            const alertBox = document.getElementById('gen-alert-container');
            const resultBox = document.getElementById('gen-result-container');

            // If no token, redirect to login page
            if (!token) {
                window.location.href = '<?php echo $_ENV['APP_URL'] ?? ''; ?>/login';
                return;
            }

            const planSelect = document.getElementById('single-plan-id');
            const calculatedExpiryInput = document.getElementById('single-calculated-expiry-input');
            const planDescText = document.getElementById('selected-plan-desc-text');
            const deviceInput = document.getElementById('single-allowed-devices');
            const custNameInput = document.getElementById('single-cust-name');

            const previewPlan = document.getElementById('preview-plan-name');
            const previewExpiry = document.getElementById('preview-expiry-date');
            const previewDevice = document.getElementById('preview-device-limit');
            const previewCustomerRow = document.getElementById('preview-customer-row');
            const previewCustomerName = document.getElementById('preview-customer-name');

            // Safe month addition handling month-end overflow (e.g. Jan 31 -> Feb 28/29) and leap years
            function addMonthsSafe(dateObj, months) {
                const d = new Date(dateObj.getTime());
                const originalDate = d.getDate();
                d.setMonth(d.getMonth() + months);
                if (d.getDate() !== originalDate) {
                    d.setDate(0); // Clamp to last valid day of previous month
                }
                return d;
            }

            // Format Date object to DD-MM-YYYY
            function formatDateDDMMYYYY(d) {
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            }

            // Robust Dynamic Expiry Calculator Function based on plan attributes
            function getCalculatedExpiryDate(slug, durationType, planName, durationDays) {
                const now = new Date();
                
                const s = (slug || '').toLowerCase();
                const d = (durationType || '').toLowerCase();
                const name = (planName || '').toLowerCase();
                const days = parseInt(durationDays || 30);

                // 1. Lifetime → 31-12-2099
                if (s.includes('lifetime') || d.includes('lifetime') || name.includes('lifetime')) {
                    return '31-12-2099';
                }

                // 2. Free Trial (7 Days) → Today + 7 Days
                if (s.includes('trial') || d.includes('trial') || name.includes('trial') || name.includes('free')) {
                    const trialDate = new Date(now.getTime());
                    trialDate.setDate(trialDate.getDate() + 7);
                    return formatDateDDMMYYYY(trialDate);
                }

                // 3. 12 Months (Yearly) → Today + 12 Months
                if (s === '12_months' || d === '12_months' || name.includes('12 month') || name.includes('yearly')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 12));
                }

                // 4. Monthly / 1 Month → Today + 1 Month
                if (s === 'monthly' || d === '1_month' || name.includes('1 month') || (name.includes('monthly') && !name.includes('12'))) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 1));
                }

                // 5. 2 Months → Today + 2 Months
                if (s === '2_months' || d === '2_months' || name.includes('2 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 2));
                }

                // 6. 3 Months → Today + 3 Months
                if (s === '3_months' || d === '3_months' || name.includes('3 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 3));
                }

                // 7. 4 Months → Today + 4 Months
                if (s === '4_months' || d === '4_months' || name.includes('4 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 4));
                }

                // 8. 6 Months → Today + 6 Months
                if (s === '6_months' || d === '6_months' || name.includes('6 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 6));
                }

                // 9. 7 Months → Today + 7 Months
                if (s === '7_months' || d === '7_months' || name.includes('7 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 7));
                }

                // 10. 8 Months → Today + 8 Months
                if (s === '8_months' || d === '8_months' || name.includes('8 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 8));
                }

                // 11. 9 Months → Today + 9 Months
                if (s === '9_months' || d === '9_months' || name.includes('9 month')) {
                    return formatDateDDMMYYYY(addMonthsSafe(now, 9));
                }

                // Business & Partner Plans: Calculate from configured duration_days
                if (days > 0) {
                    const customDate = new Date(now.getTime());
                    customDate.setDate(customDate.getDate() + days);
                    return formatDateDDMMYYYY(customDate);
                }

                // Fallback: Today + 1 Month
                return formatDateDDMMYYYY(addMonthsSafe(now, 1));
            }

            // Instant Live Recalculation Handler
            function updatePlanAndExpiry() {
                const selectedOption = planSelect.options[planSelect.selectedIndex];
                if (!selectedOption) return;

                const slug = selectedOption.getAttribute('data-slug') || '';
                const duration = selectedOption.getAttribute('data-duration') || '';
                const days = selectedOption.getAttribute('data-days') || 30;
                const name = (selectedOption.getAttribute('data-name') || selectedOption.text || '').replace(/^•\s*/, '');
                const desc = selectedOption.getAttribute('data-desc') || 'Standard plan access';

                const expiryStr = getCalculatedExpiryDate(slug, duration, name, days);

                // Instantly update read-only field, description, and live preview
                calculatedExpiryInput.value = expiryStr;
                planDescText.textContent = desc;
                previewPlan.textContent = name;
                previewExpiry.textContent = expiryStr;
            }

            // Attach instant event listeners on change, input, and click
            planSelect.addEventListener('change', updatePlanAndExpiry);
            planSelect.addEventListener('input', updatePlanAndExpiry);
            planSelect.addEventListener('click', updatePlanAndExpiry);

            deviceInput.addEventListener('input', () => {
                previewDevice.textContent = deviceInput.value || 1;
            });

            custNameInput.addEventListener('input', () => {
                const val = custNameInput.value.trim();
                if (val.length > 0) {
                    previewCustomerRow.style.display = 'flex';
                    previewCustomerName.textContent = val;
                } else {
                    previewCustomerRow.style.display = 'none';
                }
            });

            // Initial Trigger on Page Load
            updatePlanAndExpiry();

            // Single Key Generation Submit Handler
            document.getElementById('single-gen-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                alertBox.innerHTML = '';
                resultBox.innerHTML = '';

                const btn = document.getElementById('btn-single-gen');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generating Key...';

                const payload = {
                    product_id: parseInt(document.getElementById('single-product-id').value),
                    plan_id: parseInt(planSelect.value),
                    prefix: 'GB',
                    allowed_devices: deviceInput.value || 1,
                    customer_name: custNameInput.value.trim(),
                    customer_email: document.getElementById('single-cust-email').value.trim(),
                    customer_phone: document.getElementById('single-cust-phone').value.trim(),
                    customer_company: document.getElementById('single-cust-company').value.trim(),
                    customer_country: document.getElementById('single-cust-country').value.trim()
                };

                try {
                    const res = await fetch('<?php echo $_ENV['APP_URL'] ?? ''; ?>/api/v1/licenses/generate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${token}`
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        const key = data.data.license_key;

                        // Update Live Key Preview Box
                        document.getElementById('preview-key-format').textContent = key;

                        alertBox.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                <i class="fa-solid fa-circle-check me-2"></i> License Key generated successfully!
                            </div>
                        `;

                        resultBox.innerHTML = `
                            <div class="glass-panel p-4 border-success text-center">
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill mb-3">
                                    <i class="fa-solid fa-check-circle me-1"></i> Key Ready
                                </span>
                                <h6 class="text-secondary small text-uppercase tracking-wider mb-2">Generated License Key</h6>
                                
                                <div class="d-flex align-items-center justify-content-center gap-2 my-3">
                                    <input type="text" class="form-control form-control-lg font-monospace text-warning text-center fw-bold user-select-all fs-3" value="${key}" readonly id="created-key-input">
                                    <button class="btn btn-warning btn-lg px-4" id="btn-copy-key" title="Copy Key">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                        <i class="fa-solid fa-rectangle-list me-2"></i> View in License Table
                                    </a>
                                </div>
                            </div>
                        `;

                        // Copy button logic
                        document.getElementById('btn-copy-key').addEventListener('click', () => {
                            const keyInput = document.getElementById('created-key-input');
                            keyInput.select();
                            navigator.clipboard.writeText(key);
                            const copyBtn = document.getElementById('btn-copy-key');
                            copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                            setTimeout(() => {
                                copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i>';
                            }, 2000);
                        });


                    } else {
                        // If 401, session expired — redirect to login
                        if (res.status === 401) {
                            localStorage.removeItem('elms_access_token');
                            localStorage.removeItem('elms_refresh_token');
                            localStorage.removeItem('elms_user');
                            alertBox.innerHTML = `
                                <div class="alert alert-warning fade show mb-4" role="alert">
                                    <i class="fa-solid fa-lock me-2"></i> Session expired. Redirecting to login...
                                </div>
                            `;
                            setTimeout(() => { window.location.href = '<?php echo $_ENV['APP_URL'] ?? ''; ?>/login'; }, 1500);
                            return;
                        }
                        alertBox.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> ${data.message || 'Key generation failed.'}
                            </div>
                        `;
                    }
                } catch (err) {
                    alertBox.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> Network error occurred.
                        </div>
                    `;
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-key me-2"></i>Generate License Key';
                }
            });
        });
    </script>
</body>
</html>
