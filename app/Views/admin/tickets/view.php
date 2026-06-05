<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= esc($ticket['ticket_number']) ?></h5>
    <a href="<?= site_url('admin/tickets') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row g-3">
    <!-- Left: ticket body -->
    <div class="col-md-8">
        <div class="form-card mb-3">
            <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="urgency-badge badge-<?= strtolower($ticket['urgency']) ?>">
                        <?= $ticket['urgency'] ?> – <?= ['P1'=>'Critical','P2'=>'High','P3'=>'Medium'][$ticket['urgency']] ?>
                    </span>
                    <span class="status-badge badge-<?= $ticket['status'] ?>">
                        <?= ucfirst(str_replace('_',' ',$ticket['status'])) ?>
                    </span>
                    <!-- Issue type badge -->
                    <?php
                        $typeColors = ['technical'=>['#dbeafe','#1e40af'],'operational'=>['#fef9c3','#854d0e'],'other'=>['#f1f5f9','#475569']];
                        $tc = $typeColors[$ticket['issue_type']] ?? $typeColors['other'];
                    ?>
                    <span style="background:<?= $tc[0] ?>;color:<?= $tc[1] ?>;font-size:.72rem;font-weight:600;padding:.28rem .65rem;border-radius:20px;text-transform:uppercase;letter-spacing:.4px;">
                        <?= ucfirst($ticket['issue_type']) ?>
                    </span>
                </div>
                <span style="font-size:.78rem;color:#94a3b8;"><?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?></span>
            </div>

            <h6 class="fw-semibold mb-2"><?= esc($ticket['subject']) ?></h6>

            <?php if (! empty($ticket['issue_label'])): ?>
                <div class="mb-2 p-2 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:.84rem;">
                    <i class="bi bi-tag me-1 text-primary"></i>
                    <strong>Issue:</strong> <?= esc($ticket['issue_label']) ?>
                </div>
            <?php endif; ?>

            <p style="font-size:.88rem;color:#334155;white-space:pre-wrap;"><?= esc($ticket['description']) ?></p>

            <?php if (! empty($attachments)): ?>
            <hr>
            <h6 class="fw-semibold mb-2" style="font-size:.85rem;">Attachments</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($attachments as $att): ?>
                    <a href="<?= site_url('admin/attachments/' . $att['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-paperclip me-1"></i><?= esc($att['file_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status History -->
        <?php if (! empty($logs)): ?>
        <div class="form-card">
            <h6 class="fw-semibold mb-3" style="font-size:.88rem;">Status History</h6>
            <div class="timeline">
                <?php foreach ($logs as $log): ?>
                <div class="d-flex gap-3 mb-3" style="font-size:.82rem;">
                    <div style="width:8px;height:8px;background:#2563eb;border-radius:50%;margin-top:5px;flex-shrink:0;"></div>
                    <div>
                        <strong><?= ucfirst(str_replace('_',' ',$log['old_status'] ?? 'created')) ?></strong>
                        → <strong><?= ucfirst(str_replace('_',' ',$log['new_status'])) ?></strong>
                        <span class="text-muted ms-2"><?= date('d M Y, H:i', strtotime($log['changed_at'])) ?></span>
                        <?php
                            $by = $log['changed_by_admin'] ? 'Admin' : 'Center';
                        ?>
                        <span class="badge bg-light text-muted ms-1" style="font-size:.68rem;"><?= $by ?></span>
                        <?php if ($log['note']): ?>
                            <div class="text-muted mt-1"><?= esc($log['note']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: meta + actions -->
    <div class="col-md-4">
        <!-- Ticket Info -->
        <div class="form-card mb-3" style="font-size:.84rem;">
            <h6 class="fw-semibold mb-3">Ticket Info</h6>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Project</td><td><?= esc($ticket['project_name']) ?></td></tr>
                <tr><td class="text-muted">Center</td><td>
                    <code><?= esc($ticket['center_code']) ?></code> — <?= esc($ticket['center_name']) ?>
                    <?php if (! empty($ticket['contact_name'])): ?>
                        <br><span style="font-size:.8rem;color:#64748b;"><i class="bi bi-person me-1"></i><?= esc($ticket['contact_name']) ?></span>
                    <?php endif; ?>
                    <?php if (! empty($ticket['contact_phone'])): ?>
                        <br><a href="tel:<?= esc($ticket['contact_phone']) ?>" class="text-decoration-none" style="font-size:.8rem;color:#2563eb;">
                            <i class="bi bi-phone me-1"></i><?= esc($ticket['contact_phone']) ?>
                        </a>
                    <?php endif; ?>
                </td></tr>
                <?php if (! empty($ticket['mobile_number'])): ?>
                <tr>
                    <td class="text-muted">Mobile No.</td>
                    <td>
                        <a href="tel:<?= esc($ticket['mobile_number']) ?>" class="text-decoration-none" style="color:#2563eb;font-weight:600;">
                            <i class="bi bi-phone-fill me-1"></i><?= esc($ticket['mobile_number']) ?>
                        </a>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="text-muted">Remote Access</td>
                    <td>
                        <?php if (($ticket['remote_access'] ?? '') === 'yes'): ?>
                            <span class="badge" style="background:#dcfce7;color:#15803d;font-size:.75rem;">
                                <i class="bi bi-wifi me-1"></i>Yes — Required
                            </span>
                            <?php if (!empty($ticket['anydesk_id'])): ?>
                                <div class="mt-2 p-2 rounded d-flex align-items-center gap-2"
                                     style="background:#eff6ff;border:1px solid #bfdbfe;">
                                    <i class="bi bi-display text-primary"></i>
                                    <div>
                                        <div style="font-size:.7rem;color:#6b7280;line-height:1;">AnyDesk ID</div>
                                        <span style="font-size:1rem;font-weight:700;letter-spacing:.08em;color:#1e40af;font-family:monospace;">
                                            <?= esc($ticket['anydesk_id']) ?>
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm ms-auto copy-anydesk-btn"
                                            style="background:#2563eb;color:#fff;font-size:.72rem;padding:.2rem .55rem;"
                                            data-anydesk="<?= esc($ticket['anydesk_id'], 'attr') ?>">
                                        Copy
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php elseif (($ticket['remote_access'] ?? '') === 'no'): ?>
                            <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.75rem;">
                                <i class="bi bi-wifi-off me-1"></i>No
                            </span>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (! empty($ticket['support_staff'])): ?>
                <tr>
                    <td class="text-muted">Support Staff</td>
                    <td>
                        <?php
                            $staffNames = array_map('trim', explode(',', $ticket['support_staff']));
                        ?>
                        <?php foreach ($staffNames as $i => $staffName): ?>
                            <div class="d-flex align-items-center gap-1 <?= $i > 0 ? 'mt-1' : '' ?>">
                                <i class="bi bi-person-fill <?= $i === 0 ? 'text-primary' : 'text-secondary' ?>" style="font-size:.8rem;"></i>
                                <span style="font-weight:600;color:<?= $i === 0 ? '#1e40af' : '#475569' ?>;font-size:.84rem;">
                                    <?= esc($staffName) ?>
                                </span>
                                <?php if ($i === 0 && count($staffNames) > 1): ?>
                                    <span style="font-size:.65rem;background:#dbeafe;color:#1e40af;padding:.1rem .4rem;border-radius:10px;margin-left:.2rem;">Initial</span>
                                <?php elseif ($i > 0): ?>
                                    <span style="font-size:.65rem;background:#f1f5f9;color:#475569;padding:.1rem .4rem;border-radius:10px;margin-left:.2rem;">Reopened</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($ticket['resolved_at']): ?>
                <tr><td class="text-muted">Resolved At</td><td><?= date('d M Y, H:i', strtotime($ticket['resolved_at'])) ?></td></tr>
                <?php endif; ?>
                <?php if (! empty($ticket['in_progress_at'])): ?>
                <tr><td class="text-muted">In Progress At</td><td><?= date('d M Y, H:i', strtotime($ticket['in_progress_at'])) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Update Status -->
        <?php if (in_array($ticket['status'], ['open', 'in_progress'])): ?>
            <?php /* Admin can move: open → in_progress → resolved only. Cannot go backwards. */ ?>
            <div class="form-card mb-3">
                <h6 class="fw-semibold mb-3">Update Status</h6>
                <form method="POST" action="<?= site_url('admin/tickets/update-status/' . $ticket['id']) ?>" id="update-status-form">
                    <?= csrf_field() ?>
                    <?php
                        $hasStaff   = ! empty($ticket['support_staff_initial']);
                        $isReopened = ($ticket['status'] === 'open' && $hasStaff);
                        $needsStaff = (! $hasStaff) || $isReopened;
                    ?>
                    <?php if ($needsStaff): ?>
                    <div class="mb-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            Support Staff <span class="text-danger">*</span>
                        </label>
                        <?php if ($isReopened): ?>
                            <div class="form-text mb-1" style="font-size:.72rem;color:#d97706;">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Ticket reopened — enter the attending staff name.
                            </div>
                        <?php endif; ?>
                        <input type="text"
                               name="support_staff_new"
                               id="support_staff_new"
                               class="form-control form-control-sm"
                               placeholder="Enter attending staff name…"
                               maxlength="150"
                               required>
                        <div class="invalid-feedback">Please enter the support staff name.</div>
                    </div>
                    <?php else: ?>
                        <?php
                            $staffParts  = array_map('trim', explode(',', $ticket['support_staff'] ?? ''));
                            $currentStaff = end($staffParts);
                        ?>
                        <input type="hidden" name="support_staff_new" value="<?= esc($currentStaff) ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <?php
                                // Admin can only move forward: open → in_progress → resolved
                                $allowedTransitions = [
                                    'open'        => ['open', 'in_progress', 'resolved'],
                                    'in_progress' => ['in_progress', 'resolved'],
                                ];
                                $options = $allowedTransitions[$ticket['status']] ?? [];
                            ?>
                            <?php foreach ($options as $s): ?>
                                <option value="<?= $s ?>" <?= $ticket['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Tickets auto-close 2 hrs after resolving.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Notes</label>
                        <textarea name="admin_notes" class="form-control form-control-sm" rows="3"><?= esc($ticket['admin_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100" id="update-status-btn">
                        <i class="bi bi-check-lg me-1"></i>Update Status
                    </button>
                </form>
            </div>

        <?php elseif ($ticket['status'] === 'resolved'): ?>
            <?php /* Resolved — admin cannot touch it. Only center can reopen. */ ?>
            <div class="form-card mb-3 text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <i class="bi bi-check-circle-fill text-success" style="font-size:1.6rem;"></i>
                <p class="fw-semibold mt-2 mb-1" style="font-size:.88rem;color:#15803d;">Marked as Resolved</p>
                <p class="text-muted mb-0" style="font-size:.78rem;">
                    Auto-closes 2 hrs after resolution.<br>
                    Only the <strong>center</strong> can reopen this ticket if the issue persists.
                </p>
            </div>

        <?php elseif ($ticket['status'] === 'closed'): ?>
            <div class="form-card mb-3 text-center" style="background:#f8fafc;">
                <i class="bi bi-archive text-muted" style="font-size:1.5rem;"></i>
                <p class="mt-2 mb-0 text-muted" style="font-size:.83rem;">This ticket is closed.</p>
            </div>
        <?php endif; ?>

        <!-- Retag Issue Type -->
        <div class="form-card">
            <h6 class="fw-semibold mb-3">
                <i class="bi bi-tag me-1 text-primary"></i>Issue Type
                <small class="text-muted fw-normal ms-1" style="font-size:.72rem;">Admin can correct if wrongly tagged</small>
            </h6>
            <form method="POST" action="<?= site_url('admin/tickets/retag/' . $ticket['id']) ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <select name="issue_type" class="form-select form-select-sm">
                        <?php foreach (['technical'=>'Technical','operational'=>'Operational'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $ticket['issue_type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-arrow-repeat me-1"></i>Update Type
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Copy AnyDesk ID
document.querySelectorAll('.copy-anydesk-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(this.dataset.anydesk).then(() => {
            this.textContent = 'Copied!';
            setTimeout(() => { this.textContent = 'Copy'; }, 1500);
        });
    });
});

// Support staff validation — only when the visible input is present
const statusForm = document.getElementById('update-status-form');
if (statusForm) {
    statusForm.addEventListener('submit', function (e) {
        const staffInput = document.getElementById('support_staff_new');
        if (staffInput && staffInput.hasAttribute('required') && staffInput.value.trim() === '') {
            e.preventDefault();
            staffInput.classList.add('is-invalid');
            staffInput.focus();
        } else if (staffInput) {
            staffInput.classList.remove('is-invalid');
        }
    });

    const staffInput = document.getElementById('support_staff_new');
    if (staffInput) {
        staffInput.addEventListener('input', function () {
            if (this.value.trim() !== '') this.classList.remove('is-invalid');
        });
    }
}
</script>
<?= $this->endSection() ?>