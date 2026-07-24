<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Management - Enterprise License Platform</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f172a;
            --panel-bg: rgba(30, 41, 59, 0.75);
            --border-glass: rgba(255, 255, 255, 0.1);
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: #f8fafc;
            min-height: 100vh;
        }

        .font-mono { font-family: 'JetBrains Mono', monospace; }

        .glass-panel {
            background: var(--panel-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .metric-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.25s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .metric-card:hover {
            transform: translateY(-3px);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
        }

        .metric-card.active-filter {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(30, 41, 59, 0.9) 100%);
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .form-control, .form-select {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(15, 23, 42, 0.95);
            border-color: var(--accent-color);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .table { color: #f8fafc; margin-bottom: 0; }
        .table th {
            background: rgba(15, 23, 42, 0.95);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.6px;
            color: #94a3b8;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 14px 16px;
        }
        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            vertical-align: middle;
        }
        .table tbody tr { transition: background 0.15s; }
        .table tbody tr:hover { background: rgba(255, 255, 255, 0.03); }

        /* Badges */
        .badge-draft { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); }
        .badge-active { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-inactive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.3); }
        .badge-expired { background: rgba(234, 179, 8, 0.15); color: #fde047; border: 1px solid rgba(234, 179, 8, 0.3); }
        .badge-suspended { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .badge-revoked { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Status selector dropdown in table */
        .status-select {
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }

        /* Bulk Action Floating Bar */
        .bulk-bar {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(15, 23, 42, 0.98) 100%);
            border: 1px solid var(--accent-color);
            border-radius: 12px;
            padding: 12px 20px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.25);
        }

        /* Modal Customization */
        .modal-content {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            color: #f8fafc;
        }
        .modal-header { border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .modal-footer { border-top: 1px solid rgba(255, 255, 255, 0.1); }

        /* Timeline / Audit log list */
        .timeline { position: relative; padding-left: 24px; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 6px;
            bottom: 6px;
            width: 2px;
            background: rgba(255, 255, 255, 0.1);
        }
        .timeline-item { position: relative; margin-bottom: 16px; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 6px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-color);
            box-shadow: 0 0 8px var(--accent-color);
        }

        /* Certificate Print Layout */
        @media print {
            body * { visibility: hidden; }
            #printable-certificate, #printable-certificate * { visibility: visible; }
            #printable-certificate { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="py-4">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="container-fluid px-4">

        
        <!-- Header Bar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small text-secondary">
                        <li class="breadcrumb-item"><a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/dashboard" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i> Dashboard</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">License Management</li>
                    </ol>
                </nav>
                <h3 class="fw-bold mb-0 text-white d-flex align-items-center">
                    <i class="fa-solid fa-rectangle-list text-primary me-3"></i>License Management Module
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 ms-3 fs-6 px-3 py-1 rounded-pill">
                        <i class="fa-solid fa-bolt me-1"></i> Live Database
                    </span>
                </h3>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" id="btn-refresh-data">
                    <i class="fa-solid fa-rotate me-1"></i> Refresh Data
                </button>
                <button class="btn btn-outline-info btn-sm rounded-pill px-3" id="btn-export-csv-all">
                    <i class="fa-solid fa-file-csv me-1"></i> Export CSV
                </button>
                <a href="<?php echo $_ENV['APP_URL'] ?? ''; ?>/licenses/create" class="btn btn-primary btn-sm rounded-pill px-4 font-weight-bold shadow-sm">
                    <i class="fa-solid fa-plus me-1"></i> Generate License Key
                </a>
            </div>
        </div>

        <div id="module-alert-container"></div>

        <!-- 1. DASHBOARD SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card active-filter" data-filter-status="" id="card-total">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-secondary small fw-semibold text-uppercase">Total Licenses</span>
                        <div class="metric-icon bg-primary bg-opacity-25 text-primary">
                            <i class="fa-solid fa-key"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold text-white mt-2" id="count-total">0</div>
                    <small class="text-secondary" style="font-size: 11px;">All generated keys</small>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card" data-filter-status="active" id="card-active">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-success small fw-semibold text-uppercase">Active</span>
                        <div class="metric-icon bg-success bg-opacity-25 text-success">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold text-success mt-2" id="count-active">0</div>
                    <small class="text-secondary" style="font-size: 11px;">Operational keys</small>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card" data-filter-status="inactive" id="card-inactive">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-secondary small fw-semibold text-uppercase">Inactive</span>
                        <div class="metric-icon bg-secondary bg-opacity-25 text-secondary">
                            <i class="fa-solid fa-circle-minus"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold text-secondary mt-2" id="count-inactive">0</div>
                    <small class="text-secondary" style="font-size: 11px;">Disabled / Unassigned</small>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card" data-filter-status="expired" id="card-expired">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-warning small fw-semibold text-uppercase">Expired</span>
                        <div class="metric-icon bg-warning bg-opacity-25 text-warning">
                            <i class="fa-solid fa-clock font-weight-bold"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold text-warning mt-2" id="count-expired">0</div>
                    <small class="text-secondary" style="font-size: 11px;">Validity period ended</small>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card" data-filter-status="suspended" id="card-suspended">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-purple small fw-semibold text-uppercase" style="color: #c084fc;">Suspended</span>
                        <div class="metric-icon bg-purple bg-opacity-25" style="background: rgba(168, 85, 247, 0.2); color: #c084fc;">
                            <i class="fa-solid fa-pause"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold mt-2" style="color: #c084fc;" id="count-suspended">0</div>
                    <small class="text-secondary" style="font-size: 11px;">Temporarily paused</small>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-card" data-filter-status="revoked" id="card-revoked">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-danger small fw-semibold text-uppercase">Revoked</span>
                        <div class="metric-icon bg-danger bg-opacity-25 text-danger">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>
                    <div class="fs-2 fw-bold text-danger mt-2" id="count-revoked">0</div>
                    <small class="text-secondary" style="font-size: 11px;">Permanently cancelled</small>
                </div>
            </div>
        </div>

        <!-- 2. SEARCH & FILTER CONTROLS -->
        <div class="glass-panel p-3 mb-4">
            <div class="row g-2 align-items-center">
                
                <!-- Search Input -->
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-secondary"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="filter-search" placeholder="Search by key, customer, email, company...">
                    </div>
                </div>

                <!-- Product Filter -->
                <div class="col-6 col-md-2">
                    <select class="form-select" id="filter-product">
                        <option value="">All Products</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Plan Filter -->
                <div class="col-6 col-md-2">
                    <select class="form-select" id="filter-plan">
                        <option value="">All Plans</option>
                        <?php foreach ($plans as $pl): ?>
                            <option value="<?php echo $pl['id']; ?>"><?php echo htmlspecialchars($pl['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2">
                    <select class="form-select" id="filter-status">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="expired">Expired</option>
                        <option value="suspended">Suspended</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>

                <!-- Date Range (Date From & Date To) -->
                <div class="col-6 col-md-3 d-flex gap-2">
                    <input type="date" class="form-control px-2" id="filter-date-from" title="Created From">
                    <input type="date" class="form-control px-2" id="filter-date-to" title="Created To">
                </div>

                <!-- Action Buttons -->
                <div class="col-12 mt-2 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="btn-reset-filters">
                        <i class="fa-solid fa-xmark me-1"></i> Reset Filters
                    </button>
                    <button class="btn btn-primary btn-sm px-4" id="btn-apply-filters">
                        <i class="fa-solid fa-filter me-1"></i> Filter Results
                    </button>
                </div>

            </div>
        </div>

        <!-- 3. DYNAMIC BULK ACTIONS TOOLBAR (Appears when checkboxes are selected) -->
        <div id="bulk-actions-container" class="mb-3 d-none">
            <div class="bulk-bar d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill" id="bulk-selected-count">0 Selected</span>
                    <span class="text-secondary small">Select bulk operation to apply to selected keys:</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success btn-sm px-3" onclick="executeBulkAction('activate')">
                        <i class="fa-solid fa-circle-check me-1"></i> Activate Selected
                    </button>
                    <button class="btn btn-secondary btn-sm px-3" onclick="executeBulkAction('deactivate')">
                        <i class="fa-solid fa-circle-minus me-1"></i> Deactivate Selected
                    </button>
                    <button class="btn btn-warning btn-sm px-3" onclick="executeBulkAction('suspend')">
                        <i class="fa-solid fa-pause me-1"></i> Suspend Selected
                    </button>
                    <button class="btn btn-outline-info btn-sm px-3" onclick="exportSelectedCSV()">
                        <i class="fa-solid fa-file-csv me-1"></i> Export Selected CSV
                    </button>
                    <button class="btn btn-danger btn-sm px-3" onclick="executeBulkAction('delete')">
                        <i class="fa-solid fa-trash me-1"></i> Delete Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. LICENSE TABLE PANEL -->
        <div class="glass-panel p-0 overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="check-select-all">
                            </th>
                            <th>License Key</th>
                            <th>Product</th>
                            <th>Plan / Duration</th>
                            <th>Customer Info</th>
                            <th>Status</th>
                            <th>Expiry Date</th>
                            <th>Device Usage</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Last Updated</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="license-table-body">
                        <tr>
                            <td colspan="12" class="text-center py-5 text-secondary">
                                <span class="spinner-border spinner-border-sm me-2"></span> Loading licenses...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. PAGINATION CONTROLS -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-5 gap-3">
            <span class="text-secondary small" id="pagination-info">Showing 0 of 0 licenses</span>
            <ul class="pagination pagination-sm mb-0" id="pagination-controls"></ul>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL 1: LICENSE DETAILS & AUDIT TIMELINE -->
    <!-- ========================================================= -->
    <div class="modal fade" id="modal-license-details" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center">
                        <i class="fa-solid fa-shield-halved text-primary me-2"></i> License Specifications & Audit History
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    
                    <ul class="nav nav-tabs border-secondary border-opacity-25 mb-4" id="detailsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-white" id="tab-overview-btn" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button"><i class="fa-solid fa-circle-info me-1"></i> Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="tab-devices-btn" data-bs-toggle="tab" data-bs-target="#tab-devices" type="button"><i class="fa-solid fa-laptop me-1"></i> Connected Devices (<span id="det-device-count">0</span>)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-white" id="tab-audit-btn" data-bs-toggle="tab" data-bs-target="#tab-audit" type="button"><i class="fa-solid fa-clock-rotate-left me-1"></i> Audit History</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="detailsTabContent">
                        
                        <!-- TAB 1: OVERVIEW -->
                        <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                            
                            <div class="p-3 bg-dark bg-opacity-60 border border-secondary border-opacity-25 rounded-3 mb-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-secondary text-uppercase d-block mb-1" style="font-size: 10px;">License Key</small>
                                    <h5 class="font-mono text-warning fw-bold mb-0" id="det-license-key">GB-XXXX-XXXX-XXXX-XXXX</h5>
                                </div>
                                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick="copyDetailKey()">
                                    <i class="fa-regular fa-copy me-1"></i> Copy Key
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-dark bg-opacity-40 border border-secondary border-opacity-25 rounded-3">
                                        <h6 class="text-primary fw-bold mb-3"><i class="fa-solid fa-box me-2"></i>Product & Plan</h6>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Product:</span>
                                            <strong class="text-white small" id="det-product-name">--</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Plan:</span>
                                            <strong class="text-info small" id="det-plan-name">--</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Duration Type:</span>
                                            <span class="badge bg-secondary bg-opacity-25 text-white small" id="det-license-type">--</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span class="text-secondary small">Status:</span>
                                            <span id="det-status-badge">--</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 bg-dark bg-opacity-40 border border-secondary border-opacity-25 rounded-3">
                                        <h6 class="text-primary fw-bold mb-3"><i class="fa-solid fa-user me-2"></i>Customer Profile</h6>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Name:</span>
                                            <strong class="text-white small" id="det-cust-name">--</strong>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Email:</span>
                                            <span class="text-info small" id="det-cust-email">--</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                                            <span class="text-secondary small">Company:</span>
                                            <span class="text-white small" id="det-cust-company">--</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1">
                                            <span class="text-secondary small">Country:</span>
                                            <span class="text-white small" id="det-cust-country">--</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="p-3 bg-dark bg-opacity-40 border border-secondary border-opacity-25 rounded-3">
                                        <h6 class="text-primary fw-bold mb-3"><i class="fa-solid fa-clock me-2"></i>Timestamps & Rules</h6>
                                        <div class="row g-2 small">
                                            <div class="col-md-4">
                                                <span class="text-secondary d-block">Expiry Date:</span>
                                                <strong class="text-warning" id="det-expiry-date">--</strong>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-secondary d-block">Allowed Devices:</span>
                                                <strong class="text-success" id="det-allowed-devices">--</strong>
                                            </div>
                                            <div class="col-md-4">
                                                <span class="text-secondary d-block">Created By:</span>
                                                <span class="text-white" id="det-creator-name">--</span>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <span class="text-secondary d-block">Created At:</span>
                                                <span class="text-white" id="det-created-at">--</span>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <span class="text-secondary d-block">Last Updated:</span>
                                                <span class="text-white" id="det-updated-at">--</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- TAB 2: CONNECTED DEVICES -->
                        <div class="tab-pane fade" id="tab-devices" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-white">Active Device Fingerprints</h6>
                                <button class="btn btn-warning btn-sm" id="btn-reset-devices-modal">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Reset All Devices
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Fingerprint / Device</th>
                                            <th>OS / Browser</th>
                                            <th>IP Address</th>
                                            <th>Last Active</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="det-devices-list">
                                        <tr><td colspan="5" class="text-center py-3 text-secondary">No device activations found.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: AUDIT HISTORY -->
                        <div class="tab-pane fade" id="tab-audit" role="tabpanel">
                            <h6 class="fw-bold mb-3 text-white">Audit & Event History Log</h6>
                            <div class="timeline" id="det-audit-timeline">
                                <div class="text-secondary small">Loading audit logs...</div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 2: EDIT LICENSE -->
    <!-- ========================================================= -->
    <div class="modal fade" id="modal-edit-license" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="form-edit-license">
                    <input type="hidden" id="edit-license-id">
                    <div class="modal-header border-secondary border-opacity-25">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit License & Customer Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Status</label>
                                <select class="form-select" id="edit-status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="expired">Expired</option>
                                    <option value="suspended">Suspended</option>
                                    <option value="revoked">Revoked</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Allowed Device Limit</label>
                                <input type="number" class="form-control" id="edit-allowed-devices" min="1" max="1000">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Expiry Date & Time</label>
                                <input type="datetime-local" class="form-control" id="edit-expiry-date">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Product</label>
                                <select class="form-select" id="edit-product-id">
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Plan</label>
                                <select class="form-select" id="edit-plan-id">
                                    <?php foreach ($plans as $pl): ?>
                                        <option value="<?php echo $pl['id']; ?>"><?php echo htmlspecialchars($pl['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Customer Name</label>
                                <input type="text" class="form-control" id="edit-cust-name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Customer Email</label>
                                <input type="email" class="form-control" id="edit-cust-email">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Customer Phone</label>
                                <input type="text" class="form-control" id="edit-cust-phone">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Company</label>
                                <input type="text" class="form-control" id="edit-cust-company">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small text-secondary">Country</label>
                                <input type="text" class="form-control" id="edit-cust-country">
                            </div>

                            <div class="col-12">
                                <label class="form-label small text-secondary">Notes</label>
                                <textarea class="form-control" id="edit-notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary border-opacity-25">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="btn-save-edit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 3: EXTEND EXPIRY -->
    <!-- ========================================================= -->
    <div class="modal fade" id="modal-extend-expiry" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="form-extend-expiry">
                    <input type="hidden" id="extend-license-id">
                    <div class="modal-header border-secondary border-opacity-25">
                        <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-clock text-warning me-2"></i> Extend Expiry Date</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-secondary small mb-3">Select a quick extension period or pick a custom date:</p>
                        
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="setExtendPreset(7)">+7 Days</button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="setExtendPreset(30)">+30 Days</button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="setExtendPreset(90)">+90 Days</button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="setExtendPreset(365)">+1 Year</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-secondary">New Expiry Date & Time</label>
                            <input type="datetime-local" class="form-control" id="extend-expiry-input" required>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary border-opacity-25">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4" id="btn-submit-extend">Extend Expiry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 4: PRINTABLE / DOWNLOADABLE PDF LICENSE CERTIFICATE -->
    <!-- ========================================================= -->
    <div class="modal fade" id="modal-pdf-card" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Official Software License Certificate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="printable-certificate">
                    
                    <div class="p-4 bg-white text-dark rounded-4 shadow-sm border border-secondary border-opacity-25 position-relative overflow-hidden">
                        
                        <!-- Watermark -->
                        <div style="position: absolute; right: -30px; bottom: -30px; opacity: 0.04; font-size: 200px; color: #000; pointer-events: none;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <div>
                                <h3 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-shield-halved me-2"></i>ENTERPRISE LICENSE CERTIFICATE</h3>
                                <small class="text-muted">Issued by Enterprise License Management System (ELMS)</small>
                            </div>
                            <span class="badge bg-success px-3 py-2 font-mono fs-6" id="pdf-status-badge">ACTIVE</span>
                        </div>

                        <div class="my-4 text-center p-3 bg-light rounded-3 border font-mono">
                            <small class="text-muted d-block text-uppercase mb-1" style="font-size: 11px;">Cryptographically Verified License Key</small>
                            <span class="fs-3 fw-bold text-primary" id="pdf-license-key">GB-XXXX-XXXX-XXXX-XXXX</span>
                        </div>

                        <div class="row g-3 my-3">
                            <div class="col-6">
                                <span class="text-muted small d-block">Software Product:</span>
                                <strong class="fs-6" id="pdf-product-name">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">License Plan:</span>
                                <strong class="fs-6 text-info" id="pdf-plan-name">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Licensed Customer:</span>
                                <strong class="fs-6" id="pdf-customer-name">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Customer Email:</span>
                                <strong class="fs-6" id="pdf-customer-email">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Allowed Device Limit:</span>
                                <strong class="fs-6" id="pdf-allowed-devices">--</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted small d-block">Expiration Date:</span>
                                <strong class="fs-6 text-danger" id="pdf-expiry-date">--</strong>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center text-muted small">
                            <span>Verification Signature Hash: <code id="pdf-hash">--</code></span>
                            <span>Generated: <?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>

                    </div>

                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX & REAL-TIME LOGIC -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('elms_access_token');
            const baseUrl = window.location.origin;

            let currentPage = 1;
            let currentFilterStatus = '';
            let currentLoadedItems = [];
            let activeDetailItem = null;

            // Modals
            const detailsModal = new bootstrap.Modal(document.getElementById('modal-license-details'));
            const editModal = new bootstrap.Modal(document.getElementById('modal-edit-license'));
            const extendModal = new bootstrap.Modal(document.getElementById('modal-extend-expiry'));
            const pdfModal = new bootstrap.Modal(document.getElementById('modal-pdf-card'));

            // Toast Notification Helper
            window.showToast = function(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;
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

            // Alert Helper
            function showAlert(message, type = 'success') {
                showToast(message, type);
                const alertContainer = document.getElementById('module-alert-container');
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-${type} alert-dismissible fade show mb-4" role="alert">
                            <i class="fa-solid fa-${type === 'success' ? 'circle-check' : 'triangle-exclamation'} me-2"></i> ${message}
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    setTimeout(() => { alertContainer.innerHTML = ''; }, 4000);
                }
            }


            // 1. Fetch Summary Metric Counts
            async function loadSummaryCounts() {
                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/summary', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        const s = data.data;
                        document.getElementById('count-total').innerText = s.total || 0;
                        document.getElementById('count-active').innerText = s.active || 0;
                        document.getElementById('count-inactive').innerText = s.inactive || 0;
                        document.getElementById('count-expired').innerText = s.expired || 0;
                        document.getElementById('count-suspended').innerText = s.suspended || 0;
                        document.getElementById('count-revoked').innerText = s.revoked || 0;
                    }
                } catch (e) {
                    console.error('Failed loading summary counts', e);
                }
            }

            // 2. Fetch Licenses Table Data
            async function loadLicenses(page = 1) {
                currentPage = page;
                const search = document.getElementById('filter-search').value.trim();
                const productId = document.getElementById('filter-product').value;
                const planId = document.getElementById('filter-plan').value;
                const status = currentFilterStatus || document.getElementById('filter-status').value;
                const dateFrom = document.getElementById('filter-date-from').value;
                const dateTo = document.getElementById('filter-date-to').value;

                const url = new URL(baseUrl + '/api/v1/licenses');
                url.searchParams.append('page', page);
                url.searchParams.append('per_page', 15);
                if (search) url.searchParams.append('search', search);
                if (productId) url.searchParams.append('product_id', productId);
                if (planId) url.searchParams.append('plan_id', planId);
                if (status) url.searchParams.append('status', status);
                if (dateFrom) url.searchParams.append('date_from', dateFrom);
                if (dateTo) url.searchParams.append('date_to', dateTo);

                try {
                    const res = await fetch(url, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await res.json();

                    if (res.ok && data.success) {
                        currentLoadedItems = data.data || [];
                        renderTable(currentLoadedItems);
                        renderPagination(data.meta);
                        loadSummaryCounts();
                    } else if (res.status === 401) {
                        window.location.href = baseUrl + '/login';
                    }
                } catch (err) {
                    console.error('Failed to load licenses', err);
                }
            }

            // Render Table Rows
            function renderTable(items) {
                const tbody = document.getElementById('license-table-body');
                document.getElementById('check-select-all').checked = false;
                toggleBulkBar();

                if (!items || items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-5 text-secondary">No license keys matched your search criteria.</td></tr>`;
                    return;
                }

                tbody.innerHTML = items.map(l => {
                    const custName = l.customer_name ? `<strong>${l.customer_name}</strong>` : '<span class="text-secondary">Unassigned</span>';
                    const custEmail = l.customer_email ? `<small class="text-secondary d-block">${l.customer_email}</small>` : '';
                    const expiryText = l.expiry_date ? new Date(l.expiry_date).toLocaleDateString() : '<span class="text-success fw-bold">Lifetime</span>';
                    const createdDate = l.created_at ? new Date(l.created_at).toLocaleDateString() : '--';
                    const updatedDate = l.updated_at ? new Date(l.updated_at).toLocaleDateString() : '--';

                    return `
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input row-checkbox" value="${l.id}" onchange="toggleBulkBar()">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="font-mono fw-bold text-warning fs-6">${l.license_key}</span>
                                    <button class="btn btn-sm btn-outline-warning ms-2 py-0 px-2 rounded-circle" onclick="copyKeyText('${l.license_key}', this)" title="Copy Key">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-white">${l.product_name || 'Software'}</span></td>
                            <td>
                                <div>${l.plan_name || 'Standard'}</div>
                                <small class="text-secondary">(${l.license_type || 'monthly'})</small>
                            </td>
                            <td>
                                <div>${custName}</div>
                                ${custEmail}
                            </td>
                            <td>
                                <select class="form-select status-select badge-${l.status}" onchange="updateRowStatus(${l.id}, this.value)">
                                    <option value="draft" ${l.status === 'draft' ? 'selected' : ''}>DRAFT</option>
                                    <option value="active" ${l.status === 'active' ? 'selected' : ''}>ACTIVE</option>
                                    <option value="inactive" ${l.status === 'inactive' ? 'selected' : ''}>INACTIVE</option>
                                    <option value="expired" ${l.status === 'expired' ? 'selected' : ''}>EXPIRED</option>
                                    <option value="suspended" ${l.status === 'suspended' ? 'selected' : ''}>SUSPENDED</option>
                                    <option value="revoked" ${l.status === 'revoked' ? 'selected' : ''}>REVOKED</option>
                                </select>
                            </td>
                            <td>${expiryText}</td>
                            <td>
                                <span class="badge bg-dark border border-secondary border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-laptop me-1 text-primary"></i> ${l.current_devices} / ${l.allowed_devices}
                                </span>
                            </td>
                            <td><small class="text-secondary">${l.creator_name || 'Admin'}</small></td>
                            <td><small class="text-secondary">${createdDate}</small></td>
                            <td><small class="text-secondary">${updatedDate}</small></td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle rounded-pill" data-bs-toggle="dropdown">Actions</button>
                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-lg">
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openDetailsModal(${l.id})"><i class="fa-solid fa-eye text-primary me-2"></i>View Details & Audit</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openEditModal(${l.id})"><i class="fa-solid fa-pen-to-square text-info me-2"></i>Edit License</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openExtendModal(${l.id})"><i class="fa-solid fa-clock text-warning me-2"></i>Extend Expiry</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="resetRowDevices(${l.id})"><i class="fa-solid fa-rotate me-2 text-success"></i>Reset Devices</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="openPdfModal(${l.id})"><i class="fa-solid fa-file-pdf text-danger me-2"></i>Download PDF Card</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="changeStatus(${l.id}, 'active')"><i class="fa-solid fa-circle-check text-success me-2"></i>Activate Key</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="changeStatus(${l.id}, 'inactive')"><i class="fa-solid fa-circle-minus text-secondary me-2"></i>Deactivate Key</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="changeStatus(${l.id}, 'suspended')"><i class="fa-solid fa-pause text-warning me-2"></i>Suspend Key</a></li>
                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="changeStatus(${l.id}, 'revoked')"><i class="fa-solid fa-ban text-danger me-2"></i>Revoke Key</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteRowLicense(${l.id})"><i class="fa-solid fa-trash me-2"></i>Delete Key</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            // Render Pagination Links
            function renderPagination(meta) {
                if (!meta) return;
                document.getElementById('pagination-info').innerText = `Showing page ${meta.page} of ${meta.total_pages} (${meta.total} total licenses)`;

                const paginationUl = document.getElementById('pagination-controls');
                let html = '';

                if (meta.page > 1) {
                    html += `<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="loadLicenses(${meta.page - 1})">Prev</a></li>`;
                }

                for (let i = 1; i <= meta.total_pages; i++) {
                    if (i === meta.page) {
                        html += `<li class="page-item active"><span class="page-link bg-primary text-white border-primary">${i}</span></li>`;
                    } else if (i <= 3 || i >= meta.total_pages - 2 || (i >= meta.page - 1 && i <= meta.page + 1)) {
                        html += `<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="loadLicenses(${i})">${i}</a></li>`;
                    }
                }

                if (meta.page < meta.total_pages) {
                    html += `<li class="page-item"><a class="page-link bg-dark text-white border-secondary" href="javascript:void(0)" onclick="loadLicenses(${meta.page + 1})">Next</a></li>`;
                }

                paginationUl.innerHTML = html;
            }

            // Debounced Live Search
            let searchDebounceTimer;
            document.getElementById('filter-search').addEventListener('input', () => {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(() => {
                    loadLicenses(1);
                }, 300);
            });

            // Filter Event Listeners
            document.getElementById('btn-apply-filters').addEventListener('click', () => loadLicenses(1));

            document.getElementById('btn-reset-filters').addEventListener('click', () => {
                document.getElementById('filter-search').value = '';
                document.getElementById('filter-product').value = '';
                document.getElementById('filter-plan').value = '';
                document.getElementById('filter-status').value = '';
                document.getElementById('filter-date-from').value = '';
                document.getElementById('filter-date-to').value = '';
                currentFilterStatus = '';
                document.querySelectorAll('.metric-card').forEach(c => c.classList.remove('active-filter'));
                document.getElementById('card-total').classList.add('active-filter');
                loadLicenses(1);
            });
            document.getElementById('btn-refresh-data').addEventListener('click', () => loadLicenses(currentPage));

            // Dashboard Card Click Filters
            document.querySelectorAll('.metric-card').forEach(card => {
                card.addEventListener('click', () => {
                    document.querySelectorAll('.metric-card').forEach(c => c.classList.remove('active-filter'));
                    card.classList.add('active-filter');
                    currentFilterStatus = card.getAttribute('data-filter-status') || '';
                    document.getElementById('filter-status').value = currentFilterStatus;
                    loadLicenses(1);
                });
            });

            // Select All Checkbox
            document.getElementById('check-select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                toggleBulkBar();
            });

            // Toggle Bulk Actions Floating Bar
            window.toggleBulkBar = function() {
                const selected = document.querySelectorAll('.row-checkbox:checked');
                const bulkContainer = document.getElementById('bulk-actions-container');
                if (selected.length > 0) {
                    bulkContainer.classList.remove('d-none');
                    document.getElementById('bulk-selected-count').innerText = `${selected.length} Selected`;
                } else {
                    bulkContainer.classList.add('d-none');
                }
            };

            // Execute Bulk Action
            window.executeBulkAction = async function(action) {
                const selected = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => parseInt(cb.value));
                if (selected.length === 0) return;

                if (action === 'delete' && !confirm(`Are you sure you want to PERMANENTLY delete ${selected.length} licenses?`)) {
                    return;
                }

                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/bulk-action', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ action: action, license_ids: selected })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showAlert(data.message || 'Bulk action performed successfully.');
                        loadLicenses(currentPage);
                    } else {
                        showAlert(data.message || 'Bulk action failed', 'danger');
                    }
                } catch (e) {
                    showAlert('Network error during bulk action.', 'danger');
                }
            };

            // Export CSV Functions
            document.getElementById('btn-export-csv-all').addEventListener('click', () => exportCSV(currentLoadedItems));
            window.exportSelectedCSV = function() {
                const selectedIds = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => parseInt(cb.value));
                const itemsToExport = currentLoadedItems.filter(l => selectedIds.includes(l.id));
                exportCSV(itemsToExport);
            };

            function exportCSV(items) {
                if (!items || items.length === 0) {
                    showAlert('No license items to export.', 'warning');
                    return;
                }
                const headers = ['License Key', 'Product', 'Plan', 'Customer Name', 'Customer Email', 'Status', 'Expiry Date', 'Current Devices', 'Allowed Devices', 'Created At'];
                const rows = items.map(l => [
                    l.license_key,
                    `"${l.product_name || ''}"`,
                    `"${l.plan_name || ''}"`,
                    `"${l.customer_name || ''}"`,
                    `"${l.customer_email || ''}"`,
                    l.status,
                    l.expiry_date || 'Lifetime',
                    l.current_devices,
                    l.allowed_devices,
                    l.created_at
                ]);

                let csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', `elms_licenses_${Date.now()}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // Copy Key Helper
            window.copyKeyText = function(text, btn) {
                navigator.clipboard.writeText(text);
                btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                setTimeout(() => { btn.innerHTML = '<i class="fa-regular fa-copy"></i>'; }, 1500);
            };

            // Update Row Status directly via dropdown
            window.updateRowStatus = async function(id, status) {
                window.changeStatus(id, status);
            };

            window.changeStatus = async function(id, status) {
                let endpoint = '/api/v1/licenses/activate';
                if (status === 'inactive') endpoint = '/api/v1/licenses/deactivate';
                else if (status === 'suspended') endpoint = '/api/v1/licenses/suspend';
                else if (status === 'revoked') endpoint = '/api/v1/licenses/revoke';
                else if (status === 'draft' || status === 'active') endpoint = '/api/v1/licenses/activate';

                try {
                    const res = await fetch(baseUrl + endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ license_id: id, status: status })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showAlert(`License status changed to ${status.toUpperCase()}`);
                        loadLicenses(currentPage);
                    }
                } catch (e) {
                    showAlert('Failed to update status', 'danger');
                }
            };

            // Delete Row License
            window.deleteRowLicense = async function(id) {
                if (!confirm('Are you sure you want to PERMANENTLY delete this license key?')) return;
                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/delete', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ license_id: id })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showAlert('License key deleted successfully.');
                        loadLicenses(currentPage);
                    } else {
                        showAlert(data.message || 'Delete failed', 'danger');
                    }
                } catch (e) {
                    showAlert('Error deleting license', 'danger');
                }
            };

            // Reset Devices
            window.resetRowDevices = async function(id) {
                if (!confirm('Reset all connected devices for this license key?')) return;
                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/reset-devices', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ license_id: id })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showAlert('Connected devices reset successfully.');
                        loadLicenses(currentPage);
                    }
                } catch (e) {
                    showAlert('Failed to reset devices', 'danger');
                }
            };

            // Open Details & Audit Modal
            window.openDetailsModal = async function(id) {
                activeDetailItem = currentLoadedItems.find(l => l.id === id);
                if (!activeDetailItem) return;

                // Tab 1: Overview
                document.getElementById('det-license-key').innerText = activeDetailItem.license_key;
                document.getElementById('det-product-name').innerText = activeDetailItem.product_name || '--';
                document.getElementById('det-plan-name').innerText = activeDetailItem.plan_name || '--';
                document.getElementById('det-license-type').innerText = activeDetailItem.license_type || 'monthly';
                document.getElementById('det-status-badge').innerHTML = `<span class="badge badge-${activeDetailItem.status} px-2 py-1">${activeDetailItem.status.toUpperCase()}</span>`;
                
                document.getElementById('det-cust-name').innerText = activeDetailItem.customer_name || 'Unassigned';
                document.getElementById('det-cust-email').innerText = activeDetailItem.customer_email || '--';
                document.getElementById('det-cust-company').innerText = activeDetailItem.customer_company || '--';
                document.getElementById('det-cust-country').innerText = activeDetailItem.customer_country || '--';

                document.getElementById('det-expiry-date').innerText = activeDetailItem.expiry_date || 'Lifetime';
                document.getElementById('det-allowed-devices').innerText = activeDetailItem.allowed_devices || 1;
                document.getElementById('det-creator-name').innerText = activeDetailItem.creator_name || 'Admin';
                document.getElementById('det-created-at').innerText = activeDetailItem.created_at || '--';
                document.getElementById('det-updated-at').innerText = activeDetailItem.updated_at || '--';

                // Fetch Full Details + Connected Devices
                try {
                    const res = await fetch(baseUrl + `/api/v1/licenses/${id}`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const data = await res.json();
                    if (res.ok && data.success && data.data.devices) {
                        renderDevicesTab(data.data.devices);
                    }
                } catch (e) {
                    console.error(e);
                }

                // Fetch Audit History
                try {
                    const resAudit = await fetch(baseUrl + `/api/v1/licenses/${id}/audit`, {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    const dataAudit = await resAudit.json();
                    if (resAudit.ok && dataAudit.success) {
                        renderAuditTab(dataAudit.data);
                    }
                } catch (e) {
                    console.error(e);
                }

                detailsModal.show();
            };

            window.copyDetailKey = function() {
                if (activeDetailItem) {
                    navigator.clipboard.writeText(activeDetailItem.license_key);
                    showAlert('License key copied to clipboard!');
                }
            };

            function renderDevicesTab(devices) {
                document.getElementById('det-device-count').innerText = devices.length;
                const tbody = document.getElementById('det-devices-list');
                if (!devices || devices.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-secondary">No device activations registered.</td></tr>`;
                    return;
                }
                tbody.innerHTML = devices.map(d => `
                    <tr>
                        <td class="font-mono small text-warning">${d.device_fingerprint || 'Unknown'}</td>
                        <td>${d.os || 'OS'} / ${d.browser || 'Browser'}</td>
                        <td><span class="badge bg-secondary bg-opacity-25 text-white font-mono">${d.ip_address || '127.0.0.1'}</span></td>
                        <td><small class="text-secondary">${d.last_seen ? new Date(d.last_seen).toLocaleString() : '--'}</small></td>
                        <td><span class="badge ${d.is_active ? 'bg-success' : 'bg-secondary'}">${d.is_active ? 'ACTIVE' : 'DISCONNECTED'}</span></td>
                    </tr>
                `).join('');
            }

            function renderAuditTab(logs) {
                const timeline = document.getElementById('det-audit-timeline');
                if (!logs || logs.length === 0) {
                    timeline.innerHTML = `<div class="text-secondary small">No activity logs recorded yet.</div>`;
                    return;
                }
                timeline.innerHTML = logs.map(l => {
                    const payload = l.payload_json ? JSON.parse(l.payload_json) : null;
                    let changesText = '';
                    if (payload) {
                        changesText = `<pre class="bg-dark p-2 rounded small text-info mt-1 mb-0">${JSON.stringify(payload, null, 2)}</pre>`;
                    }
                    return `
                        <div class="timeline-item">
                            <div class="fw-bold text-white small">${l.action} <small class="text-secondary fw-normal">by ${l.user_name || 'System'} (${l.ip_address || '127.0.0.1'})</small></div>
                            <div class="text-secondary small mt-1">${l.description}</div>
                            ${changesText}
                            <div class="text-secondary small mt-1" style="font-size: 11px;">${new Date(l.created_at).toLocaleString()}</div>
                        </div>
                    `;
                }).join('');
            }

            // Open Edit Modal
            window.openEditModal = function(id) {
                const item = currentLoadedItems.find(l => l.id === id);
                if (!item) return;

                document.getElementById('edit-license-id').value = item.id;
                document.getElementById('edit-status').value = item.status || 'active';
                document.getElementById('edit-allowed-devices').value = item.allowed_devices || 1;
                
                if (item.expiry_date) {
                    const dt = new Date(item.expiry_date);
                    document.getElementById('edit-expiry-date').value = dt.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit-expiry-date').value = '';
                }

                document.getElementById('edit-product-id').value = item.product_id;
                document.getElementById('edit-plan-id').value = item.plan_id;
                document.getElementById('edit-cust-name').value = item.customer_name || '';
                document.getElementById('edit-cust-email').value = item.customer_email || '';
                document.getElementById('edit-cust-phone').value = item.customer_phone || '';
                document.getElementById('edit-cust-company').value = item.customer_company || '';
                document.getElementById('edit-cust-country').value = item.customer_country || '';
                document.getElementById('edit-notes').value = item.notes || '';

                editModal.show();
            };

            // Save Edit Form Submit
            document.getElementById('form-edit-license').addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('edit-license-id').value;
                const payload = {
                    license_id: parseInt(id),
                    status: document.getElementById('edit-status').value,
                    allowed_devices: parseInt(document.getElementById('edit-allowed-devices').value),
                    expiry_date: document.getElementById('edit-expiry-date').value,
                    product_id: parseInt(document.getElementById('edit-product-id').value),
                    plan_id: parseInt(document.getElementById('edit-plan-id').value),
                    customer_name: document.getElementById('edit-cust-name').value,
                    customer_email: document.getElementById('edit-cust-email').value,
                    customer_phone: document.getElementById('edit-cust-phone').value,
                    customer_company: document.getElementById('edit-cust-company').value,
                    customer_country: document.getElementById('edit-cust-country').value,
                    notes: document.getElementById('edit-notes').value
                };

                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        editModal.hide();
                        showAlert('License updated successfully.');
                        loadLicenses(currentPage);
                    } else {
                        showAlert(data.message || 'Update failed', 'danger');
                    }
                } catch (e) {
                    showAlert('Error saving license updates.', 'danger');
                }
            });

            // Open Extend Expiry Modal
            window.openExtendModal = function(id) {
                const item = currentLoadedItems.find(l => l.id === id);
                if (!item) return;

                document.getElementById('extend-license-id').value = item.id;
                setExtendPreset(30);
                extendModal.show();
            };

            window.setExtendPreset = function(days) {
                const now = new Date();
                now.setDate(now.getDate() + days);
                document.getElementById('extend-expiry-input').value = now.toISOString().slice(0, 16);
            };

            document.getElementById('form-extend-expiry').addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('extend-license-id').value;
                const newExpiry = document.getElementById('extend-expiry-input').value;

                try {
                    const res = await fetch(baseUrl + '/api/v1/licenses/extend-expiry', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                        body: JSON.stringify({ license_id: parseInt(id), expiry_date: newExpiry })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        extendModal.hide();
                        showAlert('Expiry date extended successfully!');
                        loadLicenses(currentPage);
                    }
                } catch (e) {
                    showAlert('Failed to extend expiry date', 'danger');
                }
            });

            // PDF Card Modal
            window.openPdfModal = function(id) {
                const item = currentLoadedItems.find(l => l.id === id);
                if (!item) return;

                document.getElementById('pdf-license-key').innerText = item.license_key;
                document.getElementById('pdf-product-name').innerText = item.product_name || 'Software';
                document.getElementById('pdf-plan-name').innerText = item.plan_name || 'Standard';
                document.getElementById('pdf-customer-name').innerText = item.customer_name || 'Valued Customer';
                document.getElementById('pdf-customer-email').innerText = item.customer_email || 'n/a';
                document.getElementById('pdf-allowed-devices').innerText = item.allowed_devices || 1;
                document.getElementById('pdf-expiry-date').innerText = item.expiry_date || 'Lifetime';
                document.getElementById('pdf-status-badge').innerText = (item.status || 'ACTIVE').toUpperCase();
                document.getElementById('pdf-hash').innerText = item.uuid || 'SHA256-VERIFIED';

                pdfModal.show();
            };

            // Initial Data Load
            loadLicenses(1);
        });
    </script>
</body>
</html>
