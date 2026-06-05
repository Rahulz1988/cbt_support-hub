<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= esc($title) ?></h5>
    <a href="<?= site_url('admin/centers') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="form-card" style="max-width:600px;">
    <form method="POST" action="<?= $center ? site_url('admin/centers/update/' . $center['id']) : site_url('admin/centers/store') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Center Code <span class="text-danger">*</span></label>
                <input type="text" name="center_code" class="form-control" style="text-transform:uppercase;"
                       value="<?= old('center_code', $center['center_code'] ?? '') ?>"
                       <?= $center ? 'readonly' : 'required' ?>>
            </div>
            <div class="col-md-8">
                <label class="form-label">Center Name <span class="text-danger">*</span></label>
                <input type="text" name="center_name" class="form-control" value="<?= old('center_name', $center['center_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= old('city', $center['city'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">State</label>
                <input type="text" name="state" class="form-control" value="<?= old('state', $center['state'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Name</label>
                <input type="text" name="contact_name" class="form-control" value="<?= old('contact_name', $center['contact_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contact Phone</label>
                <input type="text" name="contact_phone" class="form-control" value="<?= old('contact_phone', $center['contact_phone'] ?? '') ?>">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i><?= $center ? 'Update' : 'Add' ?> Center
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
