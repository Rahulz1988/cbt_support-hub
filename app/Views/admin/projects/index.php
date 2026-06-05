<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Projects</h5>
    <a href="<?= site_url('admin/projects/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Project
    </a>
</div>

<!-- Filter tabs -->
<div class="mb-3">
    <div class="btn-group btn-group-sm" role="group">
        <a href="?filter=all"      class="btn <?= $filter === 'all'      ? 'btn-primary'   : 'btn-outline-secondary' ?>">All (<?= $allCount ?>)</a>
        <a href="?filter=active"   class="btn <?= $filter === 'active'   ? 'btn-success'   : 'btn-outline-secondary' ?>">Active (<?= $activeCount ?>)</a>
        <a href="?filter=inactive" class="btn <?= $filter === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Inactive (<?= $inactiveCount ?>)</a>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Date Range</th>
                    <th>OTP</th>
                    <th>Status</th>
                    <th>Centers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No projects found.</td></tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                    <tr>
                        <!-- Name — click to see tickets -->
                        <td style="cursor:pointer;" onclick="window.location='<?= site_url('admin/projects/' . $p['id'] . '/tickets') ?>'">
                            <div class="fw-semibold" style="font-size:.88rem;color:#2563eb;">
                                <?= esc($p['name']) ?> <i class="bi bi-arrow-right-short"></i>
                            </div>
                            <?php if ($p['description']): ?>
                                <div class="text-muted" style="font-size:.75rem;"><?= esc(substr($p['description'], 0, 60)) . (strlen($p['description']) > 60 ? '…' : '') ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- Date range -->
                        <td style="font-size:.82rem;">
                            <?php if ($p['start_date'] && $p['end_date']): ?>
                                <span style="color:#0369a1;"><i class="bi bi-play-fill" style="font-size:.7rem;"></i> <?= date('d M Y', strtotime($p['start_date'])) ?></span><br>
                                <span style="color:#dc2626;"><i class="bi bi-stop-fill" style="font-size:.7rem;"></i> <?= date('d M Y', strtotime($p['end_date'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- OTP — click to open OTP page -->
                        <td onclick="event.stopPropagation();">
                            <a href="<?= site_url('admin/projects/' . $p['id'] . '/otp') ?>"
                               class="text-decoration-none d-flex align-items-center gap-1"
                               style="font-family:monospace;font-weight:700;font-size:.95rem;color:#7c3aed;letter-spacing:.1em;">
                                <span style="opacity:.45;font-size:.8rem;">View OTP</span>
                                <i class="bi bi-eye" style="font-size:.75rem;opacity:.6;"></i>
                            </a>
                        </td>

                        <!-- Status -->
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($p['manually_disabled']): ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <!-- Center count -->
                        <td onclick="event.stopPropagation();">
                            <a href="<?= site_url('admin/projects/' . $p['id'] . '/otp') ?>"
                               class="btn btn-xs btn-outline-primary btn-sm" style="font-size:.75rem;">
                                <i class="bi bi-building me-1"></i>Centers
                            </a>
                        </td>

                        <!-- Actions -->
                        <td onclick="event.stopPropagation();">
                            <div class="d-flex gap-1">
                                <a href="<?= site_url('admin/projects/' . $p['id'] . '/tickets') ?>"
                                   class="btn btn-sm btn-outline-primary" style="font-size:.75rem;" title="View Tickets">
                                    <i class="bi bi-ticket-detailed"></i>
                                </a>
                                <?php
                                    // Naturally expired = is_active=0 AND manually_disabled=0 AND end_date < today
                                    $naturallyExpired = (! $p['is_active'] && ! $p['manually_disabled'] && $p['end_date'] < date('Y-m-d'));
                                ?>
                                <?php if (! $naturallyExpired): ?>
                                    <?php /* Only show Edit for active or manually-disabled projects, NOT naturally expired */ ?>
                                    <a href="<?= site_url('admin/projects/edit/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($p['is_active']): ?>
                                    <?php /* Active project: admin can disable it */ ?>
                                    <form method="POST" action="<?= site_url('admin/projects/toggle/' . $p['id']) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Disable Project"
                                                onclick="return confirm('Disable this project? Centers will not be able to log in.')">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                <?php elseif ($p['manually_disabled']): ?>
                                    <?php /* Manually cancelled: admin can re-enable it */ ?>
                                    <form method="POST" action="<?= site_url('admin/projects/toggle/' . $p['id']) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-success"
                                                title="Re-enable Project"
                                                onclick="return confirm('Re-enable this project?')">
                                            <i class="bi bi-play"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php /* Naturally expired (is_active=0, manually_disabled=0): no edit, no toggle */ ?>
                            </div>
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
// Auto-refresh every 10 seconds (pause when tab is hidden)
let pollInterval = setInterval(() => window.location.reload(), 10000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearInterval(pollInterval); }
    else { pollInterval = setInterval(() => window.location.reload(), 10000); }
});
</script>
<?= $this->endSection() ?>