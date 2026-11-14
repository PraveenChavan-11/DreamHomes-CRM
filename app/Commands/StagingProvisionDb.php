<?php

namespace App\Commands;

use App\Services\DatabaseSchemaService;
use App\Services\EnvironmentSafetyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * TalentHub Consultant — Staging Database Provisioning Command
 *
 * Usage: php spark staging:provision-db
 */
class StagingProvisionDb extends BaseCommand
{
    protected $group       = 'TalentHub Staging';
    protected $name        = 'staging:provision-db';
    protected $description = 'Provision schema, tables, and roles for the isolated Staging database';

    public function run(array $params)
    {
        CLI::write("==================================================", 'yellow');
        CLI::write(" TALENTHUB STAGING DATABASE PROVISIONER", 'yellow');
        CLI::write("==================================================", 'yellow');

        try {
            // Strict Production Safety Assertion
            EnvironmentSafetyService::assertNotProduction('Provision Staging Database');
        } catch (\Throwable $e) {
            CLI::error("ABORTED: " . $e->getMessage());
            return;
        }

        $db = Database::connect();
        $dbName = $db->getDatabase();

        CLI::write("Target Database: {$dbName}", 'cyan');
        CLI::write("Database Host:     {$db->hostname}", 'cyan');
        CLI::newLine();

        CLI::write("1. Verifying and provisioning core schema...", 'white');
        $result = DatabaseSchemaService::ensureSchema(true);

        if (!$result['success']) {
            CLI::error("Schema provisioning failed: " . ($result['error'] ?? 'Unknown error'));
            return;
        }

        CLI::write("SUCCESS: " . ($result['message'] ?? 'Schema provisioned successfully.'), 'green');
        if (!empty($result['created_tables'])) {
            CLI::write("Created tables: " . implode(', ', $result['created_tables']), 'cyan');
        }

        // Verify table count
        $tables = $db->listTables();
        CLI::write("Total tables verified in {$dbName}: " . count($tables), 'green');
        CLI::newLine();
        CLI::write("Staging database schema provisioning complete.", 'green');
    }
}

