<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= esc($title) ?></h5>
    <a href="<?= site_url('admin/projects') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="form-card" style="max-width:680px;">
    <form method="POST" enctype="multipart/form-data"
          action="<?= $project ? site_url('admin/projects/update/' . $project['id']) : site_url('admin/projects/store') ?>">
        <?= csrf_field() ?>

        <?php if ($project && $project['manually_disabled']): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="font-size:.85rem;">
            <i class="bi bi-slash-circle-fill fs-5"></i>
            <div>
                <strong>This project is cancelled.</strong>
                Centers cannot log in. You can re-enable it from the
                <a href="<?= site_url('admin/projects') ?>">Projects list</a>.
            </div>
        </div>
        <?php endif; ?>

        <!-- Name & Description -->
        <div class="mb-3">
            <label class="form-label">Project Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control"
                   value="<?= old('name', $project['name'] ?? '') ?>"
                   required placeholder=" Enter The Project Name ">
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"><?= old('description', $project['description'] ?? '') ?></textarea>
        </div>

        <!-- Date Range -->
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" id="startDate" class="form-control"
                       value="<?= old('start_date', $project['start_date'] ?? '') ?>" required>
                <div class="form-text">Project activates on this date.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" id="endDate" class="form-control"
                       value="<?= old('end_date', $project['end_date'] ?? '') ?>" required>
                <div class="form-text">Can be same day as start for single-day exams.</div>
            </div>
        </div>

        <!-- Live status preview -->
        <div id="statusPreview" class="mb-3 p-3 rounded" style="display:none;font-size:.82rem;"></div>

        <hr class="my-4">

        <!-- Center Assignment -->
        <h6 class="fw-semibold mb-1"><i class="bi bi-building me-2 text-primary"></i>Assign Centers</h6>
        <p class="text-muted mb-3" style="font-size:.82rem;">
            Only assigned centers will be able to log in to this project.
            <?php if ($project): ?><strong>Upload a new CSV to add more centers.</strong><?php endif; ?>
        </p>

        <div class="mb-3">
            <label class="form-label">Upload CSV of Center Codes</label>
            <input type="file" name="centers_csv" class="form-control" accept=".csv">
            <div class="form-text">
                CSV columns: <code>center_code, center_name, city, state, contact_name, contact_phone</code> (header row skipped).<br>
                <!-- <strong>New centers are created automatically</strong> from this CSV — no need to add them separately.
                Existing centers will be updated with any new details provided. -->
                <a href="<?= site_url('admin/centers/template') ?>" target="_blank">Download template</a>
            </div>
        </div>

        <?php if (! empty($assignedCenters)): ?>
        <div class="mb-3">
            <label class="form-label text-muted" style="font-size:.8rem;">Currently Assigned Centers (<?= count($assignedCenters) ?>)</label>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($assignedCenters as $ac): ?>
                    <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                        <code><?= esc($ac['center_code']) ?></code> <?= esc($ac['center_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- <div class="p-3 rounded mb-4" style="background:#f0f9ff;border:1px solid #bae6fd;font-size:.82rem;color:#0369a1;">
            <i class="bi bi-magic me-1"></i>
            <strong>Fully Automatic:</strong> Project activates on start date and deactivates after end date.
            A unique 6-character OTP is auto-generated — share it with center staff to log in.
        </div> -->

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i><?= $project ? 'Update Project' : 'Create Project & Generate OTP' ?>
        </button>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const startInput = document.getElementById('startDate');
const endInput   = document.getElementById('endDate');
const preview    = document.getElementById('statusPreview');

function updatePreview() {
    const start = startInput.value, end = endInput.value;
    if (! start || ! end) { preview.style.display = 'none'; return; }
    if (end < start) {
        preview.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;';
        preview.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i><strong>End date must be on or after start date.</strong>';
        return;
    }
    const today = new Date().toISOString().split('T')[0];
    const diffDays = Math.ceil((new Date(end) - new Date(start)) / 86400000) + 1;
    let style, msg;
    if (today < start) {
        style = 'background:#fef9c3;border:1px solid #fde68a;color:#92400e;';
        const daysUntil = Math.ceil((new Date(start) - new Date()) / 86400000);
        msg = `<i class="bi bi-clock me-1"></i><strong>Scheduled</strong> — Auto-activates in ${daysUntil} day${daysUntil!==1?'s':''}.`;
    } else if (today >= start && today <= end) {
        style = 'background:#dcfce7;border:1px solid #86efac;color:#15803d;';
        const daysLeft = Math.ceil((new Date(end) - new Date()) / 86400000);
        msg = `<i class="bi bi-check-circle-fill me-1"></i><strong>Active Now</strong> — Ends in ${daysLeft} day${daysLeft!==1?'s':''}.`;
    } else {
        style = 'background:#f1f5f9;border:1px solid #cbd5e1;color:#475569;';
        msg = `<i class="bi bi-archive me-1"></i><strong>Inactive</strong> — End date is past.`;
    }
    preview.style.cssText = `display:block;${style}`;
    preview.innerHTML = `${msg} <span style="opacity:.65;">(${diffDays} day${diffDays!==1?'s':''})</span>`;
}
startInput.addEventListener('change', updatePreview);
endInput.addEventListener('change',   updatePreview);
updatePreview();
</script>
<?= $this->endSection() ?>
