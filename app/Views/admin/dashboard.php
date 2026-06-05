<?= $this->extend('layouts/main') ?>

<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Dashboard header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Dashboard</h5>
</div>

<!-- Global Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dbeafe;"><i class="bi bi-ticket-detailed" style="color:#2563eb;"></i></div>
            <div>
                <div class="stat-value" id="stat-total"><?= $total_tickets ?></div>
                <div class="stat-label">Total Tickets</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fee2e2;"><i class="bi bi-exclamation-circle" style="color:#dc2626;"></i></div>
            <div>
                <div class="stat-value" id="stat-open"><?= $open_tickets ?></div>
                <div class="stat-label">Open</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef9c3;"><i class="bi bi-arrow-repeat" style="color:#ca8a04;"></i></div>
            <div>
                <div class="stat-value" id="stat-inprogress"><?= $in_progress ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;"><i class="bi bi-check-circle" style="color:#16a34a;"></i></div>
            <div>
                <div class="stat-value" id="stat-resolved"><?= $resolved_tickets ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f3e8ff;"><i class="bi bi-folder2" style="color:#7c3aed;"></i></div>
            <div><div class="stat-value"><?= $active_projects ?></div><div class="stat-label">Active Projects</div></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#ffedd5;"><i class="bi bi-building" style="color:#ea580c;"></i></div>
            <div><div class="stat-value"><?= $total_centers ?></div><div class="stat-label">Total Centers</div></div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e0f2fe;"><i class="bi bi-building" style="color:#0369a1;"></i></div>
            <div><div class="stat-value" id="stat-centers"><?= $total_centers ?></div><div class="stat-label">Total Centers</div></div>
        </div>
    </div>
</div>

<!-- Active Project Tiles -->
<?php if (! empty($project_tiles)): ?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0" style="font-size:.95rem;"><i class="bi bi-folder2-open me-2 text-primary"></i>Active Projects</h6>
    <a href="<?= site_url('admin/projects') ?>" class="btn btn-sm btn-outline-primary" style="font-size:.78rem;">Manage All</a>
</div>
<div class="row g-3" id="projectTilesContainer">
    <?php foreach ($project_tiles as $p): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="project-tile-card" id="project-tile-<?= $p['id'] ?>"
             onclick="window.location='<?= site_url('admin/projects/' . $p['id'] . '/tickets') ?>'"
             style="cursor:pointer;">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold" style="font-size:.92rem;color:#1e293b;"><?= esc($p['name']) ?></div>
                    <?php if ($p['start_date'] && $p['end_date']): ?>
                        <div style="font-size:.75rem;color:#64748b;margin-top:2px;">
                            <i class="bi bi-calendar-range me-1"></i>
                            <?= date('d M', strtotime($p['start_date'])) ?> – <?= date('d M Y', strtotime($p['end_date'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="badge bg-success" style="font-size:.7rem;">Active</span>
            </div>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <!-- Each badge stops propagation so clicking it applies a filter instead of opening all-tickets -->
                <a href="<?= site_url('admin/projects/' . $p['id'] . '/tickets?status=open') ?>"
                   class="proj-mini-stat text-decoration-none" style="background:#fee2e2;color:#dc2626;"
                   onclick="event.stopPropagation();" title="View Open tickets">
                    <span class="proj-mini-val"><?= $p['count_open'] ?></span>
                    <span class="proj-mini-lbl">Open</span>
                </a>
                <a href="<?= site_url('admin/projects/' . $p['id'] . '/tickets?status=in_progress') ?>"
                   class="proj-mini-stat text-decoration-none" style="background:#fef9c3;color:#92400e;"
                   onclick="event.stopPropagation();" title="View In Progress tickets">
                    <span class="proj-mini-val"><?= $p['count_in_progress'] ?></span>
                    <span class="proj-mini-lbl">In Progress</span>
                </a>
                <a href="<?= site_url('admin/projects/' . $p['id'] . '/tickets?status=resolved') ?>"
                   class="proj-mini-stat text-decoration-none" style="background:#dcfce7;color:#15803d;"
                   onclick="event.stopPropagation();" title="View Resolved tickets">
                    <span class="proj-mini-val"><?= $p['count_resolved'] ?></span>
                    <span class="proj-mini-lbl">Resolved</span>
                </a>
                <a href="<?= site_url('admin/projects/' . $p['id'] . '/tickets?status=closed') ?>"
                   class="proj-mini-stat text-decoration-none" style="background:#f1f5f9;color:#475569;"
                   onclick="event.stopPropagation();" title="View Closed tickets">
                    <span class="proj-mini-val"><?= $p['count_closed'] ?></span>
                    <span class="proj-mini-lbl">Closed</span>
                </a>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <span style="font-size:.78rem;color:#94a3b8;" id="p<?= $p['id'] ?>-total">
                    <?= $p['count_total'] ?> total ticket<?= $p['count_total'] != 1 ? 's' : '' ?>
                </span>
                <span style="font-size:.78rem;color:#2563eb;font-weight:600;">View All <i class="bi bi-arrow-right-short"></i></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.project-tile-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    transition: border-color .15s, box-shadow .15s, transform .15s;
    height: 100%;
}
.project-tile-card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 20px rgba(37,99,235,.12);
    transform: translateY(-2px);
}
.proj-mini-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    border-radius: 8px;
    padding: .3rem .65rem;
    min-width: 56px;
    transition: transform .1s, box-shadow .1s;
}
.proj-mini-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
    text-decoration: none !important;
}
.proj-mini-val { font-size: 1.1rem; font-weight: 700; line-height: 1.2; }
.proj-mini-lbl { font-size: .65rem; font-weight: 500; opacity: .85; }
</style>

<script>
// Banner: dismissible per page-load only (reappears on every refresh as required)
// Banner close is handled inline in layouts/main.php

let pollInterval = setInterval(() => window.location.reload(), 10000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearInterval(pollInterval); }
    else { pollInterval = setInterval(() => window.location.reload(), 10000); }
});
</script>
<?= $this->endSection() ?>
