<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('admin/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">All Tickets</h5>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted" style="font-size:.82rem;"><?= count($tickets) ?> ticket(s)</span>
        <?php
            $dlParams = http_build_query(array_filter([
                'status'     => $filters['status']     ?? '',
                'urgency'    => $filters['urgency']    ?? '',
                'issue_type' => $filters['issueType']  ?? '',
                'project_id' => $filters['projectId']  ?? '',
                'q'          => $filters['q']          ?? '',
            ]));
        ?>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#downloadModal" title="Download as Excel">
            <i class="bi bi-file-earmark-excel me-1"></i>Download Report
        </button>
    </div>
</div>

<!-- Download Report Modal -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold"><i class="bi bi-file-earmark-excel text-success me-1"></i>Download Report</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="<?= site_url('admin/tickets/download') ?>">
                <input type="hidden" name="status"     value="<?= esc($filters['status']    ?? '') ?>">
                <input type="hidden" name="urgency"    value="<?= esc($filters['urgency']   ?? '') ?>">
                <input type="hidden" name="issue_type" value="<?= esc($filters['issueType'] ?? '') ?>">
                <input type="hidden" name="project_id" value="<?= esc($filters['projectId'] ?? '') ?>">
                <input type="hidden" name="q"          value="<?= esc($filters['q']         ?? '') ?>">
                <div class="modal-body">
                    <p class="text-muted mb-3" style="font-size:.8rem;">Optionally filter by raised date. Leave blank to export all.</p>
                    <div class="mb-2">
                        <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">From Date</label>
                        <input type="date" name="date_from" class="form-control form-control-sm">
                    </div>
                    <div>
                        <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;">To Date</label>
                        <input type="date" name="date_to" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success" id="adminDlBtn"><i class="bi bi-download me-1"></i>Download</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('downloadModal');

    // Require at least one date before allowing download
    document.getElementById('adminDlBtn').addEventListener('click', function (e) {
        var from = modal.querySelector('[name="date_from"]').value.trim();
        var to   = modal.querySelector('[name="date_to"]').value.trim();
        if (!from && !to) {
            e.preventDefault();
            modal.querySelector('[name="date_from"]').focus();
            alert('Please select at least a From Date or To Date to download the report.');
        }
    });
})();
</script>

<!-- Filters -->
<form method="GET" class="mb-3" id="filter-form">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search ticket no, subject or project name…" value="<?= esc($filters['q'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <?php foreach (['open','in_progress','resolved','closed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
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
            <a href="<?= site_url('admin/tickets') ?>" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
        </div>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Ticket #</th>
                    <th>Project</th>
                    <th>Center</th>
                    <th>Mobile No.</th>
                    <th>Subject</th>
                    <th>Urgency</th>
                    <th>Status</th>
                    <th>Support Staff</th>
                    <th>Raised At</th>
                    <th>In Progress At</th>
                    <th>Resolved At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="12" class="text-center text-muted py-4">No tickets found.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><span class="fw-semibold" style="color:#2563eb;font-size:.83rem;"><?= esc($t['ticket_number']) ?></span></td>
                        <td style="font-size:.8rem;"><?= esc($t['project_name'] ?? '—') ?></td>
                        <td><code style="font-size:.78rem;"><?= esc($t['center_code']) ?></code></td>
                        <td style="font-size:.82rem;">
                            <?php if (!empty($t['mobile_number'])): ?>
                                <a href="tel:<?= esc($t['mobile_number']) ?>" class="text-decoration-none" style="color:#2563eb;">
                                    <i class="bi bi-phone-fill me-1"></i><?= esc($t['mobile_number']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.84rem;"><?= esc(substr($t['subject'], 0, 45)) ?>…</td>
                        <td><span class="urgency-badge badge-<?= strtolower($t['urgency']) ?>"><?= $t['urgency'] ?></span></td>
                        <td><span class="status-badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                        <td style="font-size:.82rem;">
                            <?php if (!empty($t['support_staff'])): ?>
                                <?php
                                    $staffList = array_map('trim', explode(',', $t['support_staff']));
                                ?>
                                <?php foreach ($staffList as $si => $sName): ?>
                                    <span class="d-flex align-items-center gap-1 <?= $si > 0 ? 'mt-1' : '' ?>">
                                        <i class="bi bi-person-fill <?= $si === 0 ? 'text-primary' : 'text-secondary' ?>" style="font-size:.8rem;"></i>
                                        <?= esc($sName) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:.75rem;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= date('d M, H:i', strtotime($t['created_at'])) ?></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= $t['in_progress_at'] ? date('d M, H:i', strtotime($t['in_progress_at'])) : '—' ?></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= $t['resolved_at'] ? date('d M, H:i', strtotime($t['resolved_at'])) : '—' ?></td>
                        <td><a href="<?= site_url('admin/tickets/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>