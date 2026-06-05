<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php $importErrors = session()->getFlashdata('import_errors'); ?>
<?php if (! empty($importErrors)): ?>
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Import Skipped Rows:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($importErrors as $e): ?>
            <li style="font-size:.83rem;"><?= esc((string)$e) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Centers</h5>
</div>
<p class="text-muted mb-3" style="font-size:.82rem;">
    <i class="bi bi-info-circle me-1"></i>Centers are added automatically when you upload a CSV while creating or editing a project.
</p>

<!-- Search -->
<form method="GET" class="mb-3">
    <div class="input-group" style="max-width:360px;">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by code, name or city…" value="<?= esc($search ?? '') ?>">
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        <?php if ($search): ?>
            <a href="<?= site_url('admin/centers') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Project</th><th>Code</th><th>Name</th><th>City</th><th>State</th><th>Contact</th><th>Phone</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($centers)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No centers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($centers as $c): ?>
                    <tr>
                        <td style="font-size:.82rem;">
                            <?php if (!empty($c['project_names'])): ?>
                                <?php foreach (explode(', ', $c['project_names']) as $pname): ?>
                                    <span class="badge" style="background:#eff6ff;color:#1e40af;font-weight:500;font-size:.75rem;margin-bottom:2px;display:inline-block;">
                                        <?= esc(trim($pname)) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:.75rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?= esc($c['center_code']) ?></code></td>
                        <td><?= esc($c['center_name']) ?></td>
                        <td><?= esc($c['city'] ?? '—') ?></td>
                        <td><?= esc($c['state'] ?? '—') ?></td>
                        <td><?= esc($c['contact_name'] ?? '—') ?></td>
                        <td><?= esc($c['contact_phone'] ?? '—') ?></td>
                        <td>
                            <a href="<?= site_url('admin/centers/edit/' . $c['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pager): ?>
        <div class="p-3 border-top"><?= $pager->links() ?></div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
