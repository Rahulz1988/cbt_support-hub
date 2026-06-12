<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'CBT Support Hub') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --primary: #1e3a5f;
            --primary-light: #2563eb;
            --accent: #f59e0b;
            --sidebar-bg: #0f1f36;
            --sidebar-text: #cbd5e1;
            --sidebar-hover: rgba(255,255,255,0.08);
            --sidebar-active: rgba(37,99,235,0.25);
        }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f1f5f9; min-height: 100vh; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-brand h5 {
            color: #fff;
            font-weight: 700;
            font-size: .95rem;
            margin: 0;
            letter-spacing: .5px;
        }
        .sidebar-brand span { color: var(--accent); }
        .sidebar-nav { padding: 1rem 0; flex: 1; }
        .nav-section-label {
            font-size: .65rem;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #475569;
            padding: .5rem 1.5rem .25rem;
        }
        .sidebar-nav .nav-link {
            color: var(--sidebar-text);
            padding: .6rem 1.5rem;
            font-size: .85rem;
            font-weight: 400;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: .65rem;
            transition: background .15s, color .15s;
        }
        .sidebar-nav .nav-link:hover { background: var(--sidebar-hover); color: #fff; }
        .sidebar-nav .nav-link.active { background: var(--sidebar-active); color: #60a5fa; font-weight: 600; border-right: 3px solid #2563eb; }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 1.1rem; text-align: center; }
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: .78rem;
            color: #475569;
        }

        /* Main content */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-navbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .top-navbar .page-title {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
        }
        .content-body { padding: 1.5rem; flex: 1; }

        /* Cards */
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }
        .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; color: #1e293b; }
        .stat-label { font-size: .78rem; color: #64748b; margin-top: .2rem; }

        /* Badges */
        .badge-p1 { background: #fee2e2; color: #dc2626; }
        .badge-p2 { background: #fef3c7; color: #d97706; }
        .badge-p3 { background: #dbeafe; color: #2563eb; }
        .badge-open { background: #e0f2fe; color: #0369a1; }
        .badge-in_progress { background: #fef9c3; color: #854d0e; }
        .badge-resolved { background: #dcfce7; color: #15803d; }
        .badge-closed { background: #f1f5f9; color: #64748b; }

        .status-badge, .urgency-badge {
            font-size: .72rem;
            font-weight: 600;
            padding: .28rem .65rem;
            border-radius: 20px;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* Table */
        .table-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .table-card .table { margin: 0; }
        .table-card .table th {
            background: #f8fafc;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1rem;
        }
        .table-card .table td { padding: .75rem 1rem; vertical-align: middle; font-size: .85rem; color: #334155; }
        .table-card .table tbody tr:hover { background: #f8fafc; }

        /* Forms */
        .form-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 1.75rem;
        }
        .form-label { font-size: .83rem; font-weight: 500; color: #374151; margin-bottom: .35rem; }
        .form-control, .form-select {
            font-size: .875rem;
            border-color: #d1d5db;
            border-radius: 6px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }

        /* Buttons */
        .btn-primary { background: #2563eb; border-color: #2563eb; }
        .btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }

        /* Project tiles */
        .project-tile {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: box-shadow .2s, transform .2s, border-color .2s;
            text-decoration: none;
            display: block;
            color: inherit;
        }
        .project-tile:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,.1);
            transform: translateY(-2px);
            border-color: #2563eb;
            color: inherit;
        }
        .project-tile .tile-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #1e3a5f, #2563eb);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .project-tile h6 { font-weight: 600; font-size: .95rem; color: #1e293b; margin-bottom: .25rem; }
        .project-tile small { color: #64748b; font-size: .78rem; }


        /* Logout button looks like a link */
        .btn-logout-link {
            background: none; border: none; padding: 0;
            color: var(--sidebar-text); font-size: .85rem;
            cursor: pointer; display: inline-flex; align-items: center;
            transition: color .15s;
        }
        .btn-logout-link:hover { color: #fff; }
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
    <?= $this->renderSection('head') ?>
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <h5><i class="bi bi-shield-check me-2"></i>CBT <span>Support Hub</span></h5>
        <div style="font-size:.72rem;color:#475569;margin-top:.25rem;"><?= esc(session()->get('user_name') ?? session()->get('center_name')) ?></div>
    </div>
    <div class="sidebar-nav">
        <?= $this->renderSection('sidebar_nav') ?>
    </div>
    <div class="sidebar-footer">
        <form method="POST" action="<?= site_url('logout') ?>" class="d-inline"><?= csrf_field() ?><button type="submit" class="btn-logout-link">
            <i class="bi bi-box-arrow-left me-1"></i> Logout
        </button></form>
    </div>
</nav>

<!-- Main -->
<div id="main-content">
    <div class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-md-none" id="sidebarToggle"><i class="bi bi-list fs-5"></i></button>
            <span class="page-title"><?= esc($title ?? 'Dashboard') ?></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary"><?= esc(strtoupper(session()->get('role') ?? '')) ?></span>
            <span class="text-muted" style="font-size:.82rem;"><?= esc(session()->get('username') ?? session()->get('center_code')) ?></span>
        </div>
    </div>

    <div class="content-body">
        <?php
        // ── Global open-ticket alert banner (admin pages only) ──────────────
        // Shows on every page load whenever open tickets exist.
        // Dismissed only for the current page load (closes on X click).
        // Re-appears on next page/refresh — intentional per requirements.
        $isAdminPage = (session()->get('role') === 'admin');
        if ($isAdminPage):
            $db = \Config\Database::connect();
            $openCount = (int) $db->query("SELECT COUNT(*) AS cnt FROM tickets WHERE status = 'open'")->getRow()->cnt;
            if ($openCount > 0):
        ?>
        <div id="urgentBanner" style="
            display:flex; align-items:center; gap:.85rem;
            background:#fef2f2; border:1.5px solid #fca5a5; border-left:5px solid #dc2626;
            border-radius:10px; padding:.85rem 1.1rem; margin-bottom:1rem;
            box-shadow:0 2px 8px rgba(220,38,38,.1);">
            <span style="font-size:1.35rem;flex-shrink:0;">🚨</span>
            <div style="flex:1;font-size:.875rem;color:#1e293b;">
                <strong>Urgent Attention Needed!</strong>
                &nbsp;—&nbsp;
                <strong style="color:#dc2626;"><?= $openCount ?> ticket<?= $openCount !== 1 ? 's are' : ' is' ?> in Open state</strong>
                and awaiting action.
                <a href="<?= site_url('admin/tickets?status=open') ?>" style="color:#dc2626;font-weight:600;margin-left:.5rem;">View Open Tickets →</a>
            </div>
            <button onclick="document.getElementById('urgentBanner').style.display='none';"
                    style="background:none;border:none;cursor:pointer;font-size:1.25rem;line-height:1;color:#9ca3af;padding:.1rem .3rem;flex-shrink:0;"
                    title="Dismiss">&times;</button>
        </div>
        <?php endif; endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ((array)session()->getFlashdata('errors') as $err): ?>
                        <li><?= esc($err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>

<?php if (session()->get('role') === 'admin'): ?>
<script>
(function () {
    const BASE_TITLE   = <?= json_encode(esc($title ?? 'CBT Support Hub')) ?>;
    const COUNT_URL    = '<?= site_url('admin/api/open-ticket-count') ?>';
    const POLL_MS      = 15000; // poll every 15 s

    let lastKnownCount = null;

    function applyTabState(openCount) {
        if (openCount > 0) {
            document.title = '\uD83D\uDD34 (' + openCount + ') ' + BASE_TITLE;
        } else {
            document.title = BASE_TITLE;
        }
    }

    async function fetchCount() {
        try {
            const res  = await fetch(COUNT_URL, { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            const n    = parseInt(data.open, 10) || 0;

            // Update tab title whenever tab is hidden
            if (document.hidden) {
                applyTabState(n);
            } else {
                // Tab is visible — restore clean title but keep count if page
                // hasn't reloaded yet (stat-open element may already show it)
                document.title = BASE_TITLE;
            }

            lastKnownCount = n;
        } catch (e) { /* swallow network errors silently */ }
    }

    // When user comes back to the tab, restore clean title immediately
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            document.title = BASE_TITLE;
        }
    });

    // Start polling
    setInterval(fetchCount, POLL_MS);
})();
</script>
<?php endif; ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
