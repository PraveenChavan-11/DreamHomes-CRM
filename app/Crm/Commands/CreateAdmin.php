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

final class CreateAdmin extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:create-admin';
    protected $description = 'Create the first Super Admin. Usage: crm:create-admin <email> [--name "First Last"]  (password is prompted, never passed on the command line)';

    public function run(array $params)
    {
        $email = $params[0] ?? CLI::prompt('Email');
        $name  = CLI::getOption('name') ?: CLI::prompt('Full name');
        $pw    = CLI::prompt('Password (min 10 chars, upper+lower+digit)');
        [$f, $l] = array_pad(explode(' ', trim($name), 2), 2, '');
        $id = UserService::create(['username' => strtolower(strstr($email, '@', true)), 'email' => $email, 'first_name' => $f, 'last_name' => $l ?: '-', 'role' => 'super_admin', 'password' => $pw]);
        CLI::write("Super Admin created (id {$id}).", 'green');
    }
}
