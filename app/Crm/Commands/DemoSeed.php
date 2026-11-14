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

final class DemoSeed extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:demo-seed';
    protected $description = 'Build the fictional DHIS demo organisation (refuses unless DHIS_DEMO_ENV=true and setting demo.enabled=1).';

    public function run(array $params)
    {
        $t = microtime(true);
        $summary = (new DemoSeeder())->run();
        foreach ($summary as $k => $v) {
            CLI::write(str_pad($k, 22) . $v);
        }
        $problems = IntegrityChecker::run();
        CLI::write($problems ? 'INTEGRITY PROBLEMS: ' . implode('; ', $problems) : 'Integrity check: clean', $problems ? 'red' : 'green');
        CLI::write(sprintf('Done in %.1fs', microtime(true) - $t));
    }
}
