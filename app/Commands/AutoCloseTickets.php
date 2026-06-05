<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\TicketModel;

/**
 * AutoCloseTickets CLI Command
 *
 * Fix (M3): Provides a proper CLI entry point for cron-based auto-closing.
 *
 * Usage:
 *   php spark tickets:autoclose
 *
 * Recommended cron (every 15 minutes):
 *   */15 * * * * php /var/www/html/cbt_support_hub/spark tickets:autoclose >> /dev/null 2>&1
 */
class AutoCloseTickets extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'tickets:autoclose';
    protected $description = 'Auto-close resolved tickets that have not been reopened within 2 hours.';

    public function run(array $params): void
    {
        CLI::write('Running ticket auto-close...', 'yellow');

        $model = new TicketModel();
        $model->autoCloseResolved();

        $affected = $model->db->affectedRows();
        CLI::write("Done. {$affected} ticket(s) closed.", 'green');
    }
}
