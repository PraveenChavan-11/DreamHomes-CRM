<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\DatabaseSchemaService;

class EnsureDatabaseSchema extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'db:ensure-schema';
    protected $description = 'Auto-provision all required TalentHub database tables, columns, roles and seeds';

    public function run(array $params)
    {
        CLI::write('Checking and auto-provisioning TalentHub database schema...', 'yellow');

        $result = DatabaseSchemaService::ensureSchema(true);

        if ($result['success']) {
            CLI::write('SUCCESS: ' . ($result['message'] ?? 'Schema up to date.'), 'green');
            if (!empty($result['created_tables'])) {
                CLI::write('Created tables: ' . implode(', ', $result['created_tables']), 'cyan');
            }
        } else {
            CLI::error('ERROR: ' . ($result['error'] ?? 'Unknown error occurred.'));
        }
    }
}

