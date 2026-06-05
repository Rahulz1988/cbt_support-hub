<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('center/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><?= esc($ticket['ticket_number']) ?></h5>
    <a href="<?= site_url('center/tickets') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to My Tickets</a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="form-card">
            <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <span class="urgency-badge badge-<?= strtolower($ticket['urgency']) ?> me-2">
                        <?= $ticket['urgency'] ?> — <?= ['P1'=>'Critical','P2'=>'High','P3'=>'Medium'][$ticket['urgency']] ?>
                    </span>
                    <span class="status-badge badge-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span>
                    <?php
                        $typeColors = ['technical'=>['#dbeafe','#1e40af'],'operational'=>['#fef9c3','#854d0e'],'other'=>['#f1f5f9','#475569']];
                        $tc = $typeColors[$ticket['issue_type'] ?? 'other'] ?? $typeColors['other'];
                    ?>
                    <span style="background:<?= $tc[0] ?>;color:<?= $tc[1] ?>;font-size:.72rem;font-weight:600;padding:.28rem .65rem;border-radius:20px;text-transform:uppercase;letter-spacing:.4px;">
                        <?= ucfirst($ticket['issue_type'] ?? 'technical') ?>
                    </span>
                </div>
                <span style="font-size:.78rem;color:#94a3b8;"><?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?></span>
            </div>
            <h6 class="fw-semibold mb-3"><?= esc($ticket['subject']) ?></h6>
            <p style="font-size:.88rem;color:#334155;white-space:pre-wrap;line-height:1.7;"><?= esc($ticket['description']) ?></p>

            <?php if (! empty($attachments)): ?>
                <hr>
                <h6 class="fw-semibold mb-2" style="font-size:.85rem;"><i class="bi bi-paperclip me-1"></i>Attachments</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($attachments as $att): ?>
                        <a href="<?= site_url('center/attachments/' . $att['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark me-1"></i><?= esc($att['file_name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($ticket['admin_notes']): ?>
                <hr>
                <h6 class="fw-semibold mb-2" style="font-size:.85rem;"><i class="bi bi-chat-left-text me-1"></i>Support Team Notes</h6>
                <div class="p-3 rounded" style="background:#f0f9ff;border:1px solid #bae6fd;font-size:.86rem;color:#0c4a6e;">
                    <?= esc($ticket['admin_notes']) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-card">
            <h6 class="fw-semibold mb-3">Ticket Info</h6>
            <table class="table table-sm table-borderless mb-0" style="font-size:.83rem;">
                <tr><td class="text-muted">Ticket #</td><td><strong><?= esc($ticket['ticket_number']) ?></strong></td></tr>
                <tr><td class="text-muted">Project</td><td><?= esc($ticket['project_name']) ?></td></tr>
                <tr><td class="text-muted">Center</td><td><code><?= esc($ticket['center_code']) ?></code></td></tr>
                <tr><td class="text-muted">Status</td><td><span class="status-badge badge-<?= $ticket['status'] ?>"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span></td></tr>
                <tr><td class="text-muted">Urgency</td><td><span class="urgency-badge badge-<?= strtolower($ticket['urgency']) ?>"><?= $ticket['urgency'] ?></span></td></tr>
                <tr><td class="text-muted">Raised</td><td><?= date('d M Y, H:i', strtotime($ticket['created_at'])) ?></td></tr>
                <?php if ($ticket['resolved_at']): ?>
                <tr><td class="text-muted">Resolved</td><td><?= date('d M Y, H:i', strtotime($ticket['resolved_at'])) ?></td></tr>
                <?php endif; ?>
            </table>
            <hr>
            <?php if ($ticket['status'] === 'open' || $ticket['status'] === 'in_progress'): ?>
                <div class="p-2 rounded text-center" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                    <p class="mb-0" style="font-size:.78rem;color:#15803d;">
                        <i class="bi bi-info-circle me-1"></i>Your ticket is in the queue. The support team will update you shortly.
                    </p>
                </div>
            <?php elseif ($ticket['status'] === 'resolved'): ?>
                <div class="p-2 rounded text-center mb-3" style="background:#f0f9ff;border:1px solid #bae6fd;">
                    <p class="mb-0" style="font-size:.78rem;color:#0369a1;">
                        <i class="bi bi-check2-circle me-1"></i>This ticket has been resolved by the support team.
                    </p>
                </div>
                <?php if ($project_active): ?>
                    <form method="POST" action="<?= site_url('center/tickets/reopen/' . $ticket['id']) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-warning w-100 btn-sm fw-semibold"
                                onclick="return confirm('Reopen this ticket? It will be sent back to the support team.')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reopen Ticket
                        </button>
                    </form>
                    <p class="mt-2 mb-0 text-muted text-center" style="font-size:.72rem;">
                        <i class="bi bi-clock me-1"></i>Auto-closes 2 hrs after resolving if not reopened.
                    </p>
                <?php endif; ?>
            <?php elseif ($ticket['status'] === 'closed'): ?>
                <div class="p-2 rounded text-center" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <p class="mb-0" style="font-size:.78rem;color:#64748b;">
                        <i class="bi bi-archive me-1"></i>This ticket is closed and cannot be reopened.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->include('center/_idle_timeout') ?>
<script>
(function () {
    const STATUS_URL     = '<?= site_url('center/tickets/' . (int)$ticket['id'] . '/status') ?>';
    const currentStatus  = '<?= esc($ticket['status']) ?>';
    const currentNotes   = <?= json_encode($ticket['admin_notes'] ?? '') ?>;
    const currentUpdated = '<?= esc($ticket['updated_at'] ?? '') ?>';

    async function pollTicket() {
        try {
            const res = await fetch(STATUS_URL, { cache: 'no-store' });
            // Session expired — redirect to login
            if (res.status === 401) { window.location.href = '<?= site_url('login') ?>'; return; }
            if (!res.ok) return;
            const data = await res.json();
            if (data.status !== currentStatus || data.admin_notes !== currentNotes || data.updated_at !== currentUpdated) {
                window.location.reload();
            }
        } catch (e) {
            // Silently ignore — will retry next interval
        }
    }

    let pollInterval = setInterval(pollTicket, 5000);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { clearInterval(pollInterval); }
        else { pollTicket(); pollInterval = setInterval(pollTicket, 5000); }
    });
})();
</script>
<?= $this->endSection() ?>
