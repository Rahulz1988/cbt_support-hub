<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h5 class="fw-bold mb-4">Manage Common Issues</h5>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Add new issue -->
    <div class="col-md-4">
        <div class="form-card">
            <h6 class="fw-semibold mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Add New Issue</h6>
            <form method="POST" action="<?= site_url('admin/issues/store') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Issue Description <span class="text-danger">*</span></label>
                    <input type="text" name="issue_text" class="form-control"
                           placeholder="e.g. Biometric device not working" required maxlength="300">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg me-1"></i>Add Issue
                </button>
            </form>
        </div>
    </div>

    <!-- Issues list -->
    <div class="col-md-8">
        <div class="table-card">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">All Issues (<?= count($issues) ?>)</h6>
                <small class="text-muted">These appear in the dropdown when centers raise tickets</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Sl No</th><th>Issue Description</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($issues)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No issues yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($issues as $i => $issue): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <form method="POST" action="<?= site_url('admin/issues/update/' . $issue['id']) ?>" class="d-flex gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="sort_order" value="<?= $issue['sort_order'] ?>">
                                        <input type="text" name="issue_text" value="<?= esc($issue['issue_text']) ?>"
                                               class="form-control form-control-sm" style="min-width:220px;" required>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Save">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge <?= $issue['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $issue['is_active'] ? 'Active' : 'Hidden' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="<?= site_url('admin/issues/toggle/' . $issue['id']) ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm <?= $issue['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                title="<?= $issue['is_active'] ? 'Hide' : 'Show' ?>">
                                            <i class="bi bi-eye<?= $issue['is_active'] ? '-slash' : '' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= site_url('admin/issues/delete/' . $issue['id']) ?>" class="d-inline"
                                          onsubmit="return confirm('Delete this issue?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>