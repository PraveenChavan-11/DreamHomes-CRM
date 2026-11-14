<?php

namespace Tests\Crm;

use CodeIgniter\Test\CIUnitTestCase;
use Crm\Services\Crm;
use Crm\Services\ReferenceData;
use Crm\Services\Settings;
use Crm\Services\UserService;

/**
 * Base for CRM tests. Runs against dhis_crm_test only (guarded by tests/bootstrap.php).
 * Each test executes inside a DB transaction that is rolled back, so tests are isolated and repeatable.
 */
abstract class CrmTestCase extends CIUnitTestCase
{
    private static bool $seeded = false;
    protected static int $n = 0;
    /** Set false in suites that need committed data (demo seeding); they must clean up after themselves. */
    protected bool $useTransaction = true;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (! self::$seeded) {
            $db = Crm::db();
            foreach (glob(APPPATH . 'Crm/Database/Migrations/*.php') as $file) {   // idempotent: IF NOT EXISTS / column checks
                require_once $file;
                $class = 'Crm\\Database\\Migrations\\' . preg_replace('/^\d+_/', '', basename($file, '.php'));
                (new $class())->up();
            }
            ReferenceData::seedAll();
            self::$seeded = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Crm::resetContext();
        Settings::flush();
        if ($this->useTransaction) {
            Crm::db()->transBegin();
        }
    }

    protected function tearDown(): void
    {
        if ($this->useTransaction) {
            Crm::db()->transRollback();
        }
        Crm::resetContext();
        Settings::flush();
        parent::tearDown();
    }

    /** Create a user with a role; returns user id. */
    protected function user(string $role, array $over = []): int
    {
        $n = ++self::$n . bin2hex(random_bytes(2));

        return UserService::create($over + [
            'username' => "u{$n}", 'email' => "u{$n}@demo.invalid", 'first_name' => ucfirst($role), 'last_name' => $n,
            'role' => $role, 'password' => 'Correct-Horse-9',
        ]);
    }

    protected function sessionUser(int $uid): array
    {
        return \Crm\Services\AuthService::sessionUser($uid);
    }
}
