<?php

namespace Crm\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration 20261117000001: Auto QA Engine & Product Tour Onboarding System
 */
class AddQaAndTourTables extends Migration
{
    public function up(): void
    {
        // 1. crm_tour_progress: track user guided tour progress & completions
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'tour_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'version' => ['type' => 'INT', 'default' => 1],
            'last_step' => ['type' => 'INT', 'default' => 0],
            'is_completed' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_skipped' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'tour_key']);
        $this->forge->createTable('crm_tour_progress', true);

        // 2. crm_tour_definitions: dynamic configurable guided tour steps
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tour_key' => ['type' => 'VARCHAR', 'constraint' => 64],
            'role_slug' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 150],
            'description' => ['type' => 'TEXT', 'null' => true],
            'steps_json' => ['type' => 'LONGTEXT'],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'version' => ['type' => 'INT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tour_key');
        $this->forge->createTable('crm_tour_definitions', true);

        // 3. crm_qa_runs: stores automated QA check execution summaries
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'run_uuid' => ['type' => 'VARCHAR', 'constraint' => 64],
            'executed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'category_filter' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['running', 'passed', 'failed', 'warning'], 'default' => 'running'],
            'total_checks' => ['type' => 'INT', 'default' => 0],
            'passed_checks' => ['type' => 'INT', 'default' => 0],
            'failed_checks' => ['type' => 'INT', 'default' => 0],
            'warning_checks' => ['type' => 'INT', 'default' => 0],
            'duration_ms' => ['type' => 'INT', 'default' => 0],
            'health_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100.00],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('run_uuid');
        $this->forge->createTable('crm_qa_runs', true);

        // 4. crm_qa_findings: individual check results and findings
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'run_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'check_id' => ['type' => 'VARCHAR', 'constraint' => 64],
            'category' => ['type' => 'VARCHAR', 'constraint' => 64],
            'check_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'severity' => ['type' => 'ENUM', 'constraint' => ['critical', 'high', 'medium', 'low', 'info'], 'default' => 'medium'],
            'status' => ['type' => 'ENUM', 'constraint' => ['passed', 'failed', 'warning', 'skipped', 'blocked'], 'default' => 'passed'],
            'duration_ms' => ['type' => 'INT', 'default' => 0],
            'expected_result' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'actual_result' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'evidence' => ['type' => 'TEXT', 'null' => true],
            'remediation_plan' => ['type' => 'TEXT', 'null' => true],
            'is_auto_fixable' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['run_id', 'category']);
        $this->forge->addKey(['check_id', 'status']);
        $this->forge->createTable('crm_qa_findings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('crm_qa_findings', true);
        $this->forge->dropTable('crm_qa_runs', true);
        $this->forge->dropTable('crm_tour_definitions', true);
        $this->forge->dropTable('crm_tour_progress', true);
    }
}

