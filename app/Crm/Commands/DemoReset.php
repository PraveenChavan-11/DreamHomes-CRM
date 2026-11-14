<?php

namespace Crm\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Crm\Services\AuthService;
use Crm\Services\Crm;
use Crm\Services\DemoSeeder;
use Crm\Services\DemoService;
use Crm\Services\ActivityService;
use Crm\Services\EmailService;
use Crm\Services\IntegrityChecker;
use Crm\Services\JobRunner;
use Crm\Services\ReferenceData;
use Crm\Services\UserService;

final class DemoReset extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:demo-reset';
    protected $description = 'Reset ONLY demo-owned data and rebuild it. Requires --confirm "RESET DEMO" and --by <admin email>.';

    public function run(array $params)
    {
        $email = CLI::getOption('by') ?: CLI::prompt('Acting admin email');
        $u = Crm::db()->table('admin_users')->where('email', $email)->get()->getRowArray();
        if (! $u) {
            CLI::error('Unknown admin.');

            return EXIT_ERROR;
        }
        $r = DemoService::reset(AuthService::sessionUser((int) $u['id']), (string) CLI::getOption('confirm'));
        CLI::write("Snapshot: {$r['snapshot']}\nRemoved: {$r['removed']}  Created: " . array_sum($r['created']));
        CLI::write($r['integrity'] ? 'INTEGRITY PROBLEMS: ' . implode('; ', $r['integrity']) : 'Integrity check: clean', $r['integrity'] ? 'red' : 'green');
    }
}
