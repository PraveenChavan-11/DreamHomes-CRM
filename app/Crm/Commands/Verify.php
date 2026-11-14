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

final class Verify extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:verify';
    protected $description = 'Run the database integrity audit.';

    public function run(array $params)
    {
        $p = IntegrityChecker::run();
        CLI::write($p ? implode("\n", $p) : 'Integrity check: clean', $p ? 'red' : 'green');

        return $p ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
