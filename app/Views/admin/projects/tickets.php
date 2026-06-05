<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <a href="<?= site_url('admin/projects') ?>" class="btn btn-outline-secondary btn-sm me-2">
            <i class="bi bi-arrow-left me-1"></i>All Projects
        </a>
        <span class="fw-bold" style="font-size:1rem;"><?= esc($project['name']) ?></span>
        <?php if ($project['start_date'] && $project['end_date']): ?>
            <span class="text-muted ms-2" style="font-size:.8rem;">
                <i class="bi bi-calendar-range me-1"></i>
                <?= date('d M Y', strtotime($project['start_date'])) ?> – <?= date('d M Y', strtotime($project['end_date'])) ?>
            </span>
        <?php endif; ?>
    </div>
    <a href="<?= site_url('admin/tickets') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-ticket-detailed me-1"></i>All Tickets
    </a>
</div>

<!-- Project Ticket Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="cursor:pointer;" onclick="setFilter('status','open')">
            <div class="stat-icon" style="background:#fee2e2;"><i class="bi bi-exclamation-circle" style="color:#dc2626;"></i></div>
            <div><div class="stat-value"><?= $stats['open'] ?></div><div class="stat-label">Open</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="cursor:pointer;" onclick="setFilter('status','in_progress')">
            <div class="stat-icon" style="background:#fef9c3;"><i class="bi bi-arrow-repeat" style="color:#ca8a04;"></i></div>
            <div><div class="stat-value"><?= $stats['in_progress'] ?></div><div class="stat-label">In Progress</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="cursor:pointer;" onclick="setFilter('status','resolved')">
            <div class="stat-icon" style="background:#dcfce7;"><i class="bi bi-check-circle" style="color:#16a34a;"></i></div>
            <div><div class="stat-value"><?= $stats['resolved'] ?></div><div class="stat-label">Resolved</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="cursor:pointer;" onclick="setFilter('status','closed')">
            <div class="stat-icon" style="background:#f1f5f9;"><i class="bi bi-archive" style="color:#475569;"></i></div>
            <div><div class="stat-value"><?= $stats['closed'] ?></div><div class="stat-label">Closed</div></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="GET" id="filterForm" class="mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search ticket # or subject…" value="<?= esc($filters['q'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm" id="statusSelect">
                <option value="">All Status</option>
                <?php foreach (['open','in_progress','resolved','closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_',' ',$s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="urgency" class="form-select form-select-sm">
                <option value="">All Urgency</option>
                <option value="P1" <?= ($filters['urgency'] ?? '') === 'P1' ? 'selected' : '' ?>>P1 – Critical</option>
                <option value="P2" <?= ($filters['urgency'] ?? '') === 'P2' ? 'selected' : '' ?>>P2 – High</option>
                <option value="P3" <?= ($filters['urgency'] ?? '') === 'P3' ? 'selected' : '' ?>>P3 – Medium</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
        </div>
        <div class="col-md-1">
            <a href="<?= site_url('admin/projects/' . $project['id'] . '/tickets') ?>" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
        </div>
    </div>
</form>

<!-- Tickets Table -->
<div class="table-card">
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h6 class="mb-0 fw-semibold" style="font-size:.9rem;">
            <?= count($tickets) ?> ticket<?= count($tickets) != 1 ? 's' : '' ?>
            <?php if ($filters['status'] ?? ''): ?>
                — <span class="text-primary"><?= ucfirst(str_replace('_',' ',$filters['status'])) ?></span>
            <?php endif; ?>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Center</th>
                    <th>Subject</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Raised At</th>
                    <th>In Progress At</th>
                    <th>Resolved At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                        No tickets found for this project.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr style="cursor:pointer;" onclick="window.location='<?= site_url('admin/tickets/' . $t['id']) ?>'">
                        <td><span class="fw-semibold" style="color:#2563eb;font-size:.83rem;"><?= esc($t['ticket_number']) ?></span></td>
                        <td><code style="font-size:.78rem;"><?= esc($t['center_code']) ?></code><br>
                            <span style="font-size:.72rem;color:#94a3b8;"><?= esc($t['center_name'] ?? '') ?></span></td>
                        <td style="font-size:.84rem;max-width:220px;">
                            <?= esc(substr($t['subject'], 0, 55)) . (strlen($t['subject']) > 55 ? '…' : '') ?>
                        </td>
                        <td><span class="urgency-badge badge-<?= strtolower($t['urgency']) ?>"><?= $t['urgency'] ?></span></td>
                        <td><span class="status-badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= date('d M, H:i', strtotime($t['created_at'])) ?></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= $t['in_progress_at'] ? date('d M, H:i', strtotime($t['in_progress_at'])) : '—' ?></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= $t['resolved_at'] ? date('d M, H:i', strtotime($t['resolved_at'])) : '—' ?></td>
                        <td onclick="event.stopPropagation();">
                            <a href="<?= site_url('admin/tickets/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function setFilter(name, value) {
    document.querySelector('[name="' + name + '"]').value = value;
    document.getElementById('filterForm').submit();
}
</script>
<?= $this->endSection() ?>
