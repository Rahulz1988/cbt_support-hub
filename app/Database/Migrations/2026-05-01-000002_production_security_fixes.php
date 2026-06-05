<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Production Security Fixes Migration
 *
 * Applies all schema changes required by the audit:
 *
 * M7 — Add otp_hash (VARCHAR 255 bcrypt) to projects; keep old otp column during transition.
 *      Run: php spark migrate
 *      Then: run the companion data migration script (see SETUP.md).
 *      Finally: remove the old `otp` column in a follow-up migration after all OTPs are re-issued.
 *
 * M4 — Add composite index (center_id, project_id) on tickets table.
 *
 * M6 — Create login_logs audit table.
 *
 * H4 — This migration does NOT change the admin password automatically.
 *      After running migrate, manually set a strong password via:
 *        UPDATE users SET password_hash = '<bcrypt>' WHERE username = 'admin';
 *      See SETUP.md for the PHP snippet to generate the hash.
 */
class ProductionSecurityFixes extends Migration
{
    public function up(): void
    {
        // ── M7: Add otp_hash column (bcrypt storage) ──────────────────
        // We add it alongside the existing `otp` column so old sessions still
        // work during a rolling deploy. Remove `otp` in a follow-up migration
        // once all projects have had their OTP regenerated.
        if (! $this->db->fieldExists('otp_hash', 'projects')) {
            $this->forge->addColumn('projects', [
                'otp_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'otp',
                    'comment'    => 'bcrypt hash of the 6-char OTP (M7 fix)',
                ],
            ]);
        }

        // ── M4: Composite index on tickets (center_id, project_id) ────
        // The most frequent query for center users filters on both columns.
        $indexes = $this->db->query("SHOW INDEX FROM tickets WHERE Key_name = 'idx_center_project'")->getResultArray();
        if (empty($indexes)) {
            $this->db->query('ALTER TABLE tickets ADD INDEX idx_center_project (center_id, project_id)');
        }

        // ── M6: Login audit log table ──────────────────────────────────
        if (! $this->db->tableExists('login_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'admin user id if admin login',
                ],
                'center_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'project_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'login_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['admin', 'center', 'unknown'],
                    'default'    => 'unknown',
                ],
                'result' => [
                    'type'       => 'ENUM',
                    'constraint' => ['success', 'failed', 'rate_limited'],
                    'null'       => false,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => true,
                ],
                'user_agent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'logged_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('logged_at');
            $this->forge->addKey('ip_address');
            $this->forge->createTable('login_logs');
        }
    }

    public function down(): void
    {
        // Remove otp_hash column
        if ($this->db->fieldExists('otp_hash', 'projects')) {
            $this->forge->dropColumn('projects', 'otp_hash');
        }

        // Remove composite index
        $indexes = $this->db->query("SHOW INDEX FROM tickets WHERE Key_name = 'idx_center_project'")->getResultArray();
        if (! empty($indexes)) {
            $this->db->query('ALTER TABLE tickets DROP INDEX idx_center_project');
        }

        // Drop login_logs table
        $this->forge->dropTable('login_logs', true);
    }
}
