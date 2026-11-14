<?php

namespace App\Commands;

use App\Models\AdminUserModel;
use App\Services\EnvironmentSafetyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * TalentHub Consultant — Staging Administrator Provisioning Command
 *
 * Usage: php spark staging:provision-admin [--username=...] [--email=...] [--password=...]
 */
class StagingProvisionAdmin extends BaseCommand
{
    protected $group       = 'TalentHub Staging';
    protected $name        = 'staging:provision-admin';
    protected $description = 'Provision or update dedicated Staging Administrator credentials';
    protected $usage       = 'staging:provision-admin [--username=...] [--email=...] [--password=...]';

    public function run(array $params)
    {
        CLI::write("==================================================", 'yellow');
        CLI::write(" TALENTHUB STAGING ADMIN PROVISIONER", 'yellow');
        CLI::write("==================================================", 'yellow');

        try {
            EnvironmentSafetyService::assertNotProduction('Provision Staging Administrator');
        } catch (\Throwable $e) {
            CLI::error("ABORTED: " . $e->getMessage());
            return;
        }

        $username = $params['username'] ?? CLI::getOption('username') ?? 'staging_admin';
        $email    = $params['email']    ?? CLI::getOption('email')    ?? 'admin.staging@talenthubconsultant.com';
        $password = $params['password'] ?? CLI::getOption('password') ?? null;

        $isGenerated = false;
        if (empty($password)) {
            // Generate high-entropy 20-character password
            $password = 'StgAdmin2026!' . bin2hex(random_bytes(4));
            $isGenerated = true;
        }

        $adminModel = new AdminUserModel();
        $db = Database::connect();

        $existing = $adminModel->where('username', $username)->orWhere('email', $email)->first();

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        if ($existing) {
            $adminModel->update($existing['id'], [
                'password_hash'      => $passwordHash,
                'is_active'          => 1,
                'locked_until'       => null,
                'failed_login_count' => 0,
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            $adminId = $existing['id'];
            CLI::write("Updated existing staging administrator ID #{$adminId}.", 'green');
        } else {
            $adminId = $adminModel->insert([
                'username'           => $username,
                'email'              => $email,
                'password_hash'      => $passwordHash,
                'first_name'         => 'Staging',
                'last_name'          => 'Admin Lead',
                'phone'              => '+91 72761 46573',
                'is_active'          => 1,
                'failed_login_count' => 0,
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
            CLI::write("Created new staging administrator ID #{$adminId}.", 'green');
        }

        // Ensure user has Super Admin role (role_id 1)
        if ($db->tableExists('user_roles')) {
            $hasRole = $db->table('user_roles')
                ->where('user_id', $adminId)
                ->where('role_id', 1)
                ->countAllResults();

            if ($hasRole === 0) {
                $db->table('user_roles')->insert([
                    'user_id' => $adminId,
                    'role_id' => 1,
                ]);
            }
        }

        CLI::newLine();
        CLI::write("STAGING ADMIN CREDENTIALS:", 'cyan');
        CLI::write("Portal URL:  " . base_url('admin/login'), 'white');
        CLI::write("Username:    {$username}", 'white');
        CLI::write("Email:       {$email}", 'white');
        CLI::write("Password:    {$password}", 'yellow');
        CLI::write("Role:        Super Administrator (Unrestricted)", 'white');
        if ($isGenerated) {
            CLI::write("NOTE: Password was randomly generated. Keep it safe.", 'magenta');
        }
    }
}

