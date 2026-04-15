<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$admin       = current_admin();
$current     = basename($_SERVER['PHP_SELF']);
$site_name   = get_setting('site_name', 'Foglalas.hu');

// Értesítők
$new_inquiries = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? 'Admin') ?> – <?= e($site_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

        :root {
            --gold:        #c8a96e;
            --gold-2:      #d4b887;
            --gold-light:  rgba(200,169,110,.1);
            --dark:        #0f0f0f;
            --dark-2:      #1a1a1a;
            --dark-3:      #242424;
            --dark-4:      #2e2e2e;
            --border:      #2a2a2a;
            --text:        #e0e0e0;
            --text-muted:  #888;
            --text-light:  #bbb;
            --white:       #ffffff;
            --red:         #ef4444;
            --green:       #22c55e;
            --blue:        #3b82f6;
            --orange:      #f59e0b;
            --purple:      #8b5cf6;
            --radius:      10px;
            --radius-lg:   16px;
            --shadow:      0 2px 12px rgba(0,0,0,.3);
            --shadow-lg:   0 8px 32px rgba(0,0,0,.4);
            --sidebar-w:   260px;
            --topbar-h:    64px;
            --accent:      var(--gold);
        }

        body {
            background: var(--dark);
            color: var(--text);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--dark-2);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform .3s;
        }

        .sidebar-logo {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo .logo-icon {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--gold), var(--gold-2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--dark);
            font-size: 16px;
            flex-shrink: 0;
        }
        .sidebar-logo .logo-text {
            font-size: 16px;
            font-weight: 800;
            color: var(--white);
            letter-spacing: .5px;
        }
        .sidebar-logo .logo-sub {
            font-size: 11px;
            color: var(--text-muted);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 16px 0;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        .nav-section {
            padding: 8px 16px 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 0;
            transition: all .2s;
            position: relative;
            margin: 1px 8px;
            border-radius: 8px;
        }
        .nav-item i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }
        .nav-item:hover {
            background: var(--dark-3);
            color: var(--text);
        }
        .nav-item.active {
            background: var(--gold-light);
            color: var(--gold);
            font-weight: 600;
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 6px; bottom: 6px;
            width: 3px;
            background: var(--gold);
            border-radius: 0 3px 3px 0;
            margin-left: -8px;
        }
        .nav-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 20px;
            min-width: 18px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--dark-3);
            border-radius: 10px;
            margin-bottom: 8px;
        }
        .admin-avatar {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--gold), var(--gold-2));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--dark);
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .admin-name { font-size: 13px; font-weight: 600; color: var(--white); }
        .admin-role { font-size: 11px; color: var(--text-muted); }
        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 9px;
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.2);
            border-radius: 8px;
            color: #fca5a5;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-logout:hover {
            background: rgba(239,68,68,.2);
            border-color: rgba(239,68,68,.4);
        }

        /* ── TOPBAR ── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: var(--dark-2);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            z-index: 99;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            flex: 1;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-btn {
            width: 38px; height: 38px;
            background: var(--dark-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 15px;
            transition: all .2s;
            position: relative;
        }
        .topbar-btn:hover { color: var(--gold); border-color: var(--gold); }
        .topbar-btn .badge {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--red);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            width: 16px; height: 16px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        .menu-toggle {
            display: none;
            width: 38px; height: 38px;
            background: var(--dark-3);
            border: 1px solid var(--border);
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            color: var(--text);
            cursor: pointer;
            font-size: 16px;
        }

        /* ── MAIN CONTENT ── */
        .main-wrap {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            flex: 1;
            min-height: calc(100vh - var(--topbar-h));
            padding: 28px;
        }

        /* ── KOMPONENSEK ── */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-header h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-header h2 i { color: var(--gold); }

        .card {
            background: var(--dark-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .card-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header h3 i { color: var(--gold); }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-primary   { background:linear-gradient(135deg,var(--gold),var(--gold-2)); color:var(--dark); }
        .btn-secondary { background:var(--dark-3); border:1px solid var(--border); color:var(--text); }
        .btn-danger    { background:rgba(239,68,68,.15); border:1px solid rgba(239,68,68,.3); color:#fca5a5; }
        .btn-success   { background:rgba(34,197,94,.15); border:1px solid rgba(34,197,94,.3); color:#86efac; }
        .btn-sm { padding:6px 12px; font-size:12px; }
        .btn:hover { opacity:.85; transform:translateY(-1px); }

        /* Alerts */
        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-success { background:rgba(34,197,94,.1);  border:1px solid rgba(34,197,94,.3);  color:#86efac; }
        .alert-error   { background:rgba(239,68,68,.1);  border:1px solid rgba(239,68,68,.3);  color:#fca5a5; }
        .alert-warning { background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.3); color:#fcd34d; }
        .alert-info    { background:rgba(59,130,246,.1); border:1px solid rgba(59,130,246,.3); color:#93c5fd; }

        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 7px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: var(--dark-3);
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text);
            font-size: 14px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(200,169,110,.1);
        }
        .form-group small { font-size: 12px; color: var(--text-muted); margin-top: 4px; display: block; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        /* Table */
        .admin-table { width:100%; border-collapse:collapse; font-size:13px; }
        .admin-table th {
            text-align: left;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }
        .admin-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            vertical-align: middle;
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover td { background: rgba(255,255,255,.02); }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-active   { background:rgba(34,197,94,.15);  color:#86efac; }
        .badge-inactive { background:rgba(107,114,128,.15); color:#9ca3af; }
        .badge-new      { background:rgba(59,130,246,.15);  color:#93c5fd; }
        .badge-read     { background:rgba(107,114,128,.15); color:#9ca3af; }
        .badge-replied  { background:rgba(34,197,94,.15);   color:#86efac; }
        .badge-gold     { background:rgba(200,169,110,.15); color:var(--gold); }

        /* Action buttons */
        .table-actions { display:flex; gap:6px; }
        .action-btn {
            width: 30px; height: 30px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .2s;
        }
        .action-btn.edit   { background:rgba(59,130,246,.15); color:#93c5fd; }
        .action-btn.delete { background:rgba(239,68,68,.15);  color:#fca5a5; }
        .action-btn.view   { background:rgba(34,197,94,.15);  color:#86efac; }
        .action-btn:hover  { filter:brightness(1.3); transform:scale(1.05); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }
        .empty-state i  { font-size: 40px; margin-bottom: 12px; opacity: .4; display: block; }
        .empty-state p  { font-size: 14px; margin-bottom: 16px; }

        /* Toggle */
        .toggle-switch { position:relative; display:inline-block; width:42px; height:24px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: var(--dark-4);
            border-radius: 24px;
            cursor: pointer;
            transition: .3s;
            border: 1px solid var(--border);
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 16px; height: 16px;
            left: 3px; bottom: 3px;
            background: var(--text-muted);
            border-radius: 50%;
            transition: .3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: var(--gold); border-color: var(--gold); }
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(18px);
            background: var(--dark);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--dark-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .modal-header h3 { font-size: 16px; font-weight: 700; color: var(--white); }
        .modal-close {
            width: 30px; height: 30px;
            background: var(--dark-3);
            border: 1px solid var(--border);
            border-radius: 7px;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .modal-close:hover { color: var(--red); border-color: var(--red); }
        .modal-body   { padding: 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        /* Responsive */
        @media(max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .topbar { left: 0; }
            .main-wrap { margin-left: 0; }
            .menu-toggle { display: flex; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
            <div class="logo-text"><?= e($site_name) ?></div>
            <div class="logo-sub">Admin felület</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Főmenü</div>
        <a href="<?= ADMIN_URL ?>/index.php"
           class="nav-item <?= $current === 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>

        <div class="nav-section">Tartalom</div>
        <a href="<?= ADMIN_URL ?>/systems.php"
           class="nav-item <?= $current === 'systems.php' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Rendszerek
        </a>
        <a href="<?= ADMIN_URL ?>/categories.php"
           class="nav-item <?= $current === 'categories.php' ? 'active' : '' ?>">
            <i class="fas fa-folder"></i> Kategóriák
        </a>
        <a href="<?= ADMIN_URL ?>/pricing.php"
           class="nav-item <?= $current === 'pricing.php' ? 'active' : '' ?>">
            <i class="fas fa-tags"></i> Árazás
        </a>
        <a href="<?= ADMIN_URL ?>/testimonials.php"
           class="nav-item <?= $current === 'testimonials.php' ? 'active' : '' ?>">
            <i class="fas fa-star"></i> Vélemények
        </a>
        <a href="<?= ADMIN_URL ?>/faqs.php"
           class="nav-item <?= $current === 'faqs.php' ? 'active' : '' ?>">
            <i class="fas fa-question-circle"></i> GYIK
        </a>
		
		<a href="<?= ADMIN_URL ?>/pages.php"
   class="nav-item <?= $current === 'pages.php' ? 'active' : '' ?>">
    <i class="fas fa-file-alt"></i> Oldalak
</a>

        <div class="nav-section">Kapcsolat</div>
        <a href="<?= ADMIN_URL ?>/inquiries.php"
           class="nav-item <?= $current === 'inquiries.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i> Érdeklődések
            <?php if ($new_inquiries > 0): ?>
            <span class="nav-badge"><?= $new_inquiries ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section">Rendszer</div>
        <a href="<?= ADMIN_URL ?>/settings.php"
           class="nav-item <?= $current === 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i> Beállítások
        </a>
        <a href="<?= BASE_URL ?>" target="_blank" class="nav-item">
            <i class="fas fa-external-link-alt"></i> Weboldal megtekintése
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar">
                <?= strtoupper(substr($admin['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div>
                <div class="admin-name"><?= e($admin['name'] ?? '') ?></div>
                <div class="admin-role">
                    <?= ($admin['role'] ?? '') === 'superadmin' ? '⭐ Super Admin' : 'Admin' ?>
                </div>
            </div>
        </div>
        <a href="<?= ADMIN_URL ?>/logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Kijelentkezés
        </a>
    </div>
</aside>

<!-- ── TOPBAR ── -->
<div class="topbar">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-title"><?= e($page_title ?? 'Admin') ?></div>
    <div class="topbar-actions">
        <a href="<?= ADMIN_URL ?>/inquiries.php" class="topbar-btn" title="Érdeklődések">
            <i class="fas fa-envelope"></i>
            <?php if ($new_inquiries > 0): ?>
            <span class="badge"><?= $new_inquiries ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>" target="_blank" class="topbar-btn" title="Weboldal">
            <i class="fas fa-external-link-alt"></i>
        </a>
    </div>
</div>

<!-- ── MAIN ── -->
<div class="main-wrap">