<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        ob_start(static fn ($buffer) => $buffer);
    }

    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});

/*
 * FIX: Changed from post_controller → pre_controller so that project
 * active/inactive state is updated in the DB BEFORE any controller
 * (e.g. Admin DashboardController) reads it. With post_controller the
 * dashboard query ran first, then the update fired — meaning expired
 * projects kept appearing as active tiles until the *next* request.
 *
 * Auto-activate and auto-deactivate projects based on start_date / end_date.
 * Runs once per request (static flag) before the controller fires.
 */
Events::on('pre_controller', static function (): void {
    static $ran = false;
    if ($ran || ENVIRONMENT === 'testing' || is_cli()) return;
    $ran = true;

    try {
        $db = \Config\Database::connect();

        // Activate: start_date reached AND end_date not yet passed AND currently inactive AND not manually disabled
        $db->query("
            UPDATE projects
            SET    is_active = 1
            WHERE  is_active = 0
              AND  manually_disabled = 0
              AND  start_date IS NOT NULL
              AND  end_date   IS NOT NULL
              AND  start_date <= CURDATE()
              AND  end_date   >= CURDATE()
        ");

        // Deactivate: end_date has passed AND currently active
        $db->query("
            UPDATE projects
            SET    is_active = 0
            WHERE  is_active = 1
              AND  end_date  IS NOT NULL
              AND  end_date  < CURDATE()
        ");

    } catch (\Throwable $e) {
        log_message('error', 'Project auto-activate/deactivate failed: ' . $e->getMessage());
    }
});

/*
 * FIX: Changed from post_controller → pre_controller so that tickets
 * are auto-closed BEFORE the controller reads ticket statuses.
 * Previously, resolved tickets that had passed 2 hours showed as
 * "resolved" on the current page because the close ran after rendering.
 *
 * Auto-close resolved tickets not reopened within 2 hours.
 */
Events::on('pre_controller', static function (): void {
    static $closed = false;
    if ($closed || ENVIRONMENT === 'testing' || is_cli()) return;
    $closed = true;

    try {
        (new \App\Models\TicketModel())->autoCloseResolved();
    } catch (\Throwable $e) {
        log_message('error', 'Auto-close tickets failed: ' . $e->getMessage());
    }
});
