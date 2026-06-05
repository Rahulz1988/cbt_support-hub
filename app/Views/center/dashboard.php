<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('center/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Project + Center banner -->
<div class="form-card mb-4" style="background:linear-gradient(135deg,#1e3a5f,#2563eb);border:none;color:#fff;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div style="font-size:.78rem;opacity:.7;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.25rem;">Active Project</div>
            <h4 class="fw-bold mb-1" style="color:#fff;"><?= esc($project['name']) ?></h4>
            <?php if ($project['start_date'] && $project['end_date']): ?>
                <div style="font-size:.82rem;opacity:.8;">
                    <i class="bi bi-calendar-range me-1"></i>
                    <?= date('d M Y', strtotime($project['start_date'])) ?> – <?= date('d M Y', strtotime($project['end_date'])) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-end">
            <div style="font-size:.78rem;opacity:.7;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.25rem;">Your Center</div>
            <div class="fw-bold" style="font-size:1.1rem;color:#fff;"><code style="background:rgba(255,255,255,.15);padding:.2rem .5rem;border-radius:6px;font-size:1rem;"><?= esc($center['center_code']) ?></code></div>
            <div style="font-size:.85rem;opacity:.9;margin-top:.25rem;"><?= esc($center['center_name']) ?></div>
        </div>
    </div>
</div>

<!-- Center contact info -->
<?php if ($center['city'] || $center['contact_name']): ?>
<div class="form-card mb-4" style="background:#f8fafc;">
    <div class="d-flex flex-wrap gap-4" style="font-size:.84rem;color:#475569;">
        <?php if ($center['city']): ?>
            <span><i class="bi bi-geo-alt me-1 text-primary"></i><?= esc($center['city']) ?><?= $center['state'] ? ', ' . esc($center['state']) : '' ?></span>
        <?php endif; ?>
        <?php if ($center['contact_name']): ?>
            <span><i class="bi bi-person me-1 text-primary"></i><?= esc($center['contact_name']) ?></span>
        <?php endif; ?>
        <?php if ($center['contact_phone']): ?>
            <span><i class="bi bi-telephone me-1 text-primary"></i><?= esc($center['contact_phone']) ?></span>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ticket stats -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['open',        'Open',        '#fee2e2','#dc2626','exclamation-circle'],
        ['in_progress', 'In Progress', '#fef9c3','#ca8a04','arrow-repeat'],
        ['resolved',    'Resolved',    '#dcfce7','#16a34a','check-circle'],
        ['closed',      'Closed',      '#f1f5f9','#475569','archive'],
    ] as [$key, $label, $bg, $color, $icon]): ?>
    <div class="col-6 col-md-3">
        <a href="<?= site_url('center/tickets?status=' . $key) ?>" class="text-decoration-none">
            <div class="stat-card" style="cursor:pointer;">
                <div class="stat-icon" style="background:<?= $bg ?>;"><i class="bi bi-<?= $icon ?>" style="color:<?= $color ?>;"></i></div>
                <div><div class="stat-value"><?= $counts[$key] ?></div><div class="stat-label"><?= $label ?></div></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Big Raise Ticket CTA -->
<div class="text-center py-3">
    <a href="<?= site_url('center/tickets/raise') ?>" class="btn btn-danger btn-lg px-5 fw-semibold">
        <i class="bi bi-plus-circle me-2"></i>Raise Support Ticket
    </a>
    <p class="text-muted mt-2 mb-0" style="font-size:.82rem;">Having an issue during the exam? Click above to report it.</p>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('center/_idle_timeout') ?>
<script>
// Auto-refresh via silent ping (does NOT reset idle timer)
// Full page reload keeps dashboard counts live
async function silentPingAndRefresh() {
    try {
        const res = await fetch('<?= site_url('center/ping') ?>', { cache: 'no-store' });
        if (res.status === 401) { window.location.href = '<?= site_url('login') ?>'; return; }
        if (res.ok) { window.location.reload(); }
    } catch (e) { /* network blip — will retry */ }
}
let pollInterval = setInterval(silentPingAndRefresh, 10000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearInterval(pollInterval); }
    else { pollInterval = setInterval(silentPingAndRefresh, 10000); }
});
</script>
<?= $this->endSection() ?>
