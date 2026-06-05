<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Bulk Import Centers</h5>
    <a href="<?= site_url('admin/centers') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Upload Card -->
    <div class="col-md-7">
        <div class="form-card">
            <h6 class="fw-semibold mb-3"><i class="bi bi-upload me-2 text-primary"></i>Upload CSV File</h6>
            <form method="POST" action="<?= site_url('admin/centers/import') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="upload-area mb-3" id="uploadArea">
                    <i class="bi bi-file-earmark-spreadsheet" style="font-size:2.5rem;color:#2563eb;display:block;margin-bottom:.5rem;"></i>
                    <p class="mb-1 fw-semibold" style="font-size:.9rem;">Drag & drop your CSV here</p>
                    <p class="text-muted mb-2" style="font-size:.8rem;">or click the button below to browse</p>
                    <input type="file" name="csv_file" id="csvFile" accept=".csv" class="d-none" required>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="chooseFileBtn">
                        Choose File
                    </button>
                    <div id="fileName" class="mt-2 text-muted" style="font-size:.8rem;"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-cloud-upload me-2"></i>Import Centers
                </button>
            </form>
        </div>
    </div>

    <!-- Format Guide Card -->
    <div class="col-md-5">
        <div class="form-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>CSV Format</h6>
                <a href="<?= site_url('admin/centers/template') ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-1"></i>Download Template
                </a>
            </div>

            <p class="text-muted mb-3" style="font-size:.82rem;">
                Your CSV must have columns in this exact order (header row is skipped during import):
            </p>

            <div class="table-responsive">
                <table class="table table-sm table-bordered" style="font-size:.8rem;">
                    <thead class="table-dark">
                        <tr><th>Column</th><th>Required</th></tr>
                    </thead>
                    <tbody>
                        <tr><td><code>center_code</code></td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td><code>center_name</code></td><td><span class="badge bg-danger">Yes</span></td></tr>
                        <tr><td><code>city</code></td><td><span class="badge bg-secondary">Optional</span></td></tr>
                        <tr><td><code>state</code></td><td><span class="badge bg-secondary">Optional</span></td></tr>
                        <tr><td><code>contact_name</code></td><td><span class="badge bg-secondary">Optional</span></td></tr>
                        <tr><td><code>contact_phone</code></td><td><span class="badge bg-secondary">Optional</span></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="p-2 rounded mt-2" style="background:#fef9c3;border:1px solid #fde68a;font-size:.78rem;color:#78350f;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Note:</strong> Rows with duplicate center codes are automatically skipped.
            </div>
        </div>

        <div class="form-card mt-3">
            <h6 class="fw-semibold mb-2" style="font-size:.85rem;"><i class="bi bi-eye me-1"></i>Example CSV</h6>
            <pre style="background:#f8fafc;border-radius:8px;padding:.75rem;font-size:.75rem;color:#334155;margin:0;overflow-x:auto;">center_code,center_name,city,state,contact_name,contact_phone
CTR001,Sunrise Academy,Chennai,Tamil Nadu,Ravi Kumar,9841001001
CTR002,Greenwood Institute,Coimbatore,Tamil Nadu,Priya Nair,9842002002
CTR003,Bright Future Center,Madurai,Tamil Nadu,Senthil Raj,9843003003</pre>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.upload-area {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.upload-area:hover, .upload-area.dragover {
    border-color: #2563eb;
    background: #eff6ff;
}
</style>
<script>
const area  = document.getElementById('uploadArea');
const input = document.getElementById('csvFile');
const label = document.getElementById('fileName');
const btn   = document.getElementById('chooseFileBtn');

input.addEventListener('change', () => {
    label.textContent = input.files[0] ? '✓ ' + input.files[0].name : '';
});
// Only the button triggers file dialog (prevents double-open)
btn.addEventListener('click', (e) => { e.stopPropagation(); input.click(); });
area.addEventListener('dragover',  e => { e.preventDefault(); area.classList.add('dragover'); });
area.addEventListener('dragleave', () => area.classList.remove('dragover'));
area.addEventListener('drop', e => {
    e.preventDefault();
    area.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        input.files = dt.files;
        label.textContent = '✓ ' + dt.files[0].name;
    }
});
</script>
<?= $this->endSection() ?>
