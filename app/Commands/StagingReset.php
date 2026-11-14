<?php

namespace App\Commands;

use App\Services\EnvironmentSafetyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * TalentHub Consultant — Staging Database Reset Command
 *
 * Usage: php spark staging:reset [--force]
 */
class StagingReset extends BaseCommand
{
    protected $group       = 'TalentHub Staging';
    protected $name        = 'staging:reset';
    protected $description = 'Safely reset all test data in the Staging database (Guarded against Production)';
    protected $usage       = 'staging:reset [--force]';

    public function run(array $params)
    {
        CLI::write("==================================================", 'red');
        CLI::write(" TALENTHUB STAGING DATA RESET UTILITY", 'red');
        CLI::write("==================================================", 'red');

        // 1. Strict Production Safety Check
        try {
            EnvironmentSafetyService::assertNotProduction('Reset Staging Data');
        } catch (\Throwable $e) {
            CLI::error("FATAL: " . $e->getMessage());
            return;
        }

        $db = Database::connect();
        $dbName = $db->getDatabase();

        // 2. Additional Name Check: Must contain staging
        if (!str_contains(strtolower($dbName), 'staging')) {
            CLI::error("ABORTED: Database '{$dbName}' does not contain 'staging' in its name. Aborting reset.");
            return;
        }

        $force = CLI::getOption('force');
        if (!$force) {
            CLI::write("Target Database: {$dbName}", 'yellow');
            CLI::write("WARNING: This will purge all applications, enquiries, test emails, and reset staging data.", 'red');
            $confirm = CLI::prompt("Type 'RESET-STAGING' to confirm destructive purge of {$dbName}:");
            if ($confirm !== 'RESET-STAGING') {
                CLI::write("Operation cancelled by user.", 'yellow');
                return;
            }
        }

        CLI::write("Purging transactional test tables in {$dbName}...", 'yellow');

        $tablesToTruncate = [
            'applications',
            'application_status_history',
            'candidates',
            'candidate_documents',
            'contact_enquiries',
            'employer_enquiries',
            'enquiry_followups',
            'enquiry_notes',
            'general_resume_submissions',
            'newsletter_subscribers',
            'newsletter_preferences',
            'newsletter_activity_logs',
            'job_alert_subscriptions',
            'email_campaigns',
            'email_delivery_logs',
            'email_outbox',
            'audit_logs',
            'notification_logs',
            'cookie_consent_logs',
        ];

        $db->query("SET FOREIGN_KEY_CHECKS = 0;");
        foreach ($tablesToTruncate as $tbl) {
            if ($db->tableExists($tbl)) {
                $db->query("TRUNCATE TABLE `{$tbl}`;");
                CLI::write("  - Truncated table: {$tbl}", 'white');
            }
        }
        $db->query("SET FOREIGN_KEY_CHECKS = 1;");

        CLI::write("Transactional tables truncated successfully.", 'green');
        CLI::newLine();

        // Re-seed clean test data
        CLI::write("Re-seeding initial staging data...", 'cyan');
        $seeder = new StagingSeedTestData($this->logger, $this->commands);
        $seeder->run([]);

        CLI::newLine();
        CLI::write("STAGING RESET COMPLETE: Database is clean and ready for new testing cycle.", 'green');
    }
}

