<?= $this->extend('layouts/main') ?>
<?= $this->section('sidebar_nav') ?>
<?= $this->include('center/_sidebar') ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">My Tickets</h5>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted" style="font-size:.82rem;"><?= count($tickets) ?> ticket(s)</span>
        <a href="<?= site_url('center/tickets/raise') ?>" class="btn btn-danger btn-sm"><i class="bi bi-plus-circle me-1"></i>Raise Ticket</a>
    </div>
</div>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Ticket #</th><th>Subject</th><th>Urgency</th><th>Status</th><th>Raised At</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-ticket" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
                        No tickets raised yet.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $t): ?>
                    <tr style="cursor:pointer;" onclick="window.location='<?= site_url('center/tickets/' . $t['id']) ?>'">
                        <td><span class="fw-semibold" style="color:#2563eb;font-size:.83rem;"><?= esc($t['ticket_number']) ?></span></td>
                        <td style="font-size:.84rem;"><?= esc(substr($t['subject'], 0, 55)) . (strlen($t['subject']) > 55 ? '…' : '') ?></td>
                        <td><span class="urgency-badge badge-<?= strtolower($t['urgency']) ?>"><?= $t['urgency'] ?></span></td>
                        <td><span class="status-badge badge-<?= $t['status'] ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                        <td style="font-size:.75rem;color:#94a3b8;"><?= date('d M Y, H:i', strtotime($t['created_at'])) ?></td>
                        <td onclick="event.stopPropagation();">
                            <a href="<?= site_url('center/tickets/' . $t['id']) ?>" class="btn btn-sm btn-outline-primary" style="font-size:.75rem;">View</a>
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
<?= $this->include('center/_idle_timeout') ?>
<script>
// Auto-refresh via silent ping (does NOT reset idle timer)
async function silentPingAndRefresh() {
    try {
        const res = await fetch('<?= site_url('center/ping') ?>', { cache: 'no-store' });
        if (res.status === 401) { window.location.href = '<?= site_url('login') ?>'; return; }
        if (res.ok) { window.location.reload(); }
    } catch (e) { /* network blip — will retry */ }
}
let pollInterval = setInterval(silentPingAndRefresh, 10000);
document.addEventListener('visibilitychange', () => {
    if (document.hidden) { clearInterval(pollInterval); }
    else { pollInterval = setInterval(silentPingAndRefresh, 10000); }
});
</script>
<?= $this->endSection() ?>
