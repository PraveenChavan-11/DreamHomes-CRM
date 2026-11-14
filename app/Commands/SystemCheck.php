<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SystemCheck extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'system:check';
    protected $description = 'Perform comprehensive system health, database, and permissions diagnostic check';

    public function run(array $params)
    {
        CLI::write('=== TalentHub System Diagnostic Check ===', 'green');

        // 1. PHP Version
        CLI::write('PHP Version: ' . PHP_VERSION, 'yellow');

        // 2. Database Connection
        try {
            $db = \Config\Database::connect();
            $version = $db->getVersion();
            CLI::write("[PASS] Database Connected: MySQL {$version}", 'green');
        } catch (\Throwable $e) {
            CLI::error('[FAIL] Database Connection Error: ' . $e->getMessage());
        }

        // 3. Storage Permissions
        $paths = [
            'writable'       => WRITEPATH,
            'writable/cache' => WRITEPATH . 'cache',
            'writable/logs'  => WRITEPATH . 'logs',
            'writable/vault' => WRITEPATH . 'vault',
            'public/uploads' => FCPATH . 'uploads',
        ];

        foreach ($paths as $name => $path) {
            if (is_dir($path) && is_writable($path)) {
                CLI::write("[PASS] Storage Writable: {$name}", 'green');
            } else {
                CLI::error("[FAIL] Storage NOT writable or missing: {$name} ({$path})");
            }
        }

        CLI::write('Diagnostic check complete.', 'green');
    }
}

