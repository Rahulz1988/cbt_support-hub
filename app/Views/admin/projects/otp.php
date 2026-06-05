<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Project OTP — <?= esc($project['name']) ?></h5>
    <a href="<?= site_url('admin/projects') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>All Projects
    </a>
</div>

<!-- OTP Banner -->
<div class="form-card mb-4" style="background:linear-gradient(135deg,#1e3a5f,#2563eb);border:none;color:#fff;text-align:center;">
    <div style="font-size:.82rem;opacity:.75;text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem;">
        Master OTP for this Project
    </div>
    <div id="otpDisplay" style="font-size:3rem;font-weight:800;letter-spacing:.4em;font-family:monospace;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.2);">
        <?php
            $plainOtp = null;
            if (!empty($project['otp_encrypted'])) {
                $encrypter = \Config\Services::encrypter();
                $plainOtp  = $encrypter->decrypt($project['otp_encrypted']);
            }
        ?>
        <?php if ($plainOtp): ?>
            <?= esc($plainOtp) ?>
        <?php else: ?>
            <span style="font-size:1rem;opacity:.7;">— Click Regenerate OTP —</span>
        <?php endif; ?>
    </div>
    <div style="font-size:.8rem;opacity:.7;margin-top:.5rem;">
        Centers enter their <strong>Center Code</strong> + this OTP to log in
    </div>
    <div class="d-flex justify-content-center gap-2 mt-3">
        <button class="btn btn-light btn-sm fw-semibold" onclick="copyOtp()">
            <i class="bi bi-clipboard me-1"></i>Copy OTP
        </button>
        <form method="POST" action="<?= site_url('admin/projects/' . $project['id'] . '/otp/regenerate') ?>"
              onsubmit="return confirm('Regenerate OTP? All centers will need the new OTP to log in.');" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-warning btn-sm fw-semibold">
                <i class="bi bi-arrow-clockwise me-1"></i>Regenerate OTP
            </button>
        </form>
    </div>
</div>

<!-- Assigned Centers -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-building me-2 text-primary"></i>Assigned Centers (<?= count($centers) ?>)</h6>
                <a href="<?= site_url('admin/projects/edit/' . $project['id']) ?>" class="btn btn-outline-primary btn-sm" style="font-size:.78rem;">
                    <i class="bi bi-pencil me-1"></i>Edit / Add Centers
                </a>
            </div>
            <?php if (empty($centers)): ?>
                <div class="text-muted text-center py-3" style="font-size:.85rem;">
                    <i class="bi bi-exclamation-triangle me-1 text-warning"></i>No centers assigned yet.
                    <a href="<?= site_url('admin/projects/edit/' . $project['id']) ?>">Add centers now</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                        <thead>
                            <tr><th>Code</th><th>Name</th><th>City</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($centers as $c): ?>
                            <tr>
                                <td><code><?= esc($c['center_code']) ?></code></td>
                                <td><?= esc($c['center_name']) ?></td>
                                <td class="text-muted"><?= esc($c['city'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Project info -->
<div class="form-card" style="font-size:.84rem;">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Project</div>
            <div class="fw-semibold"><?= esc($project['name']) ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Start Date</div>
            <div class="fw-semibold" style="color:#15803d;"><?= $project['start_date'] ? date('d M Y', strtotime($project['start_date'])) : '—' ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">End Date</div>
            <div class="fw-semibold" style="color:#dc2626;"><?= $project['end_date'] ? date('d M Y', strtotime($project['end_date'])) : '—' ?></div>
        </div>
        <div class="col-md-3">
            <div class="text-muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;">Status</div>
            <?php if ($project['is_active']): ?>
                <span class="badge bg-success">Active</span>
            <?php elseif ($project['manually_disabled']): ?>
                <span class="badge bg-danger">Cancelled</span>
            <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function copyOtp() {
    const otp = document.getElementById('otpDisplay').textContent.trim();
    navigator.clipboard.writeText(otp).then(() => {
        const btn = event.target.closest('button');
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy OTP'; }, 2000);
    });
}
</script>
<?= $this->endSection() ?>
