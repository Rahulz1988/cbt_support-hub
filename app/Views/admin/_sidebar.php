<?php
// Helper: returns 'active' if current URI matches
function sidebarActive(string $path): string {
    return str_contains(current_url(), $path) ? 'active' : '';
}
?>
<div class="nav-section-label">Main</div>
<a href="<?= site_url('admin/dashboard') ?>" class="nav-link <?= sidebarActive('/admin/dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
<div class="nav-section-label">Management</div>
<a href="<?= site_url('admin/projects') ?>" class="nav-link <?= sidebarActive('/admin/projects') ?>"><i class="bi bi-folder2-open"></i> Projects</a>
<a href="<?= site_url('admin/tickets') ?>" class="nav-link <?= sidebarActive('/admin/tickets') ?>"><i class="bi bi-ticket-detailed"></i> All Tickets</a>
<a href="<?= site_url('admin/centers') ?>" class="nav-link <?= sidebarActive('/admin/centers') ?>"><i class="bi bi-building"></i> Centers</a>
<a href="<?= site_url('admin/issues') ?>" class="nav-link <?= sidebarActive('/admin/issues') ?>"><i class="bi bi-list-ul"></i> Common Issues</a>
