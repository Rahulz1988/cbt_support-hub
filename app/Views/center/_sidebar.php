<?php
function centerActive(string $path): string {
    return str_contains(current_url(), $path) ? 'active' : '';
}
?>
<div class="nav-section-label">Navigation</div>
<a href="<?= site_url('center/dashboard') ?>" class="nav-link <?= centerActive('/center/dashboard') ?>"><i class="bi bi-grid"></i> Dashboard</a>
<a href="<?= site_url('center/tickets/raise') ?>" class="nav-link <?= centerActive('/center/tickets/raise') ?>"><i class="bi bi-plus-circle"></i> Raise Ticket</a>
<a href="<?= site_url('center/tickets') ?>" class="nav-link <?= centerActive('center/tickets') && !centerActive('center/tickets/raise') ? 'active' : '' ?>"><i class="bi bi-ticket-detailed"></i> My Tickets</a>
