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

final class SeedReference extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:seed-reference';
    protected $description = 'Idempotently seed roles, permissions, status matrix, lookups, templates and default settings (no users).';

    public function run(array $params)
    {
        ReferenceData::seedAll();
        CLI::write('Reference data seeded.', 'green');
    }
}
