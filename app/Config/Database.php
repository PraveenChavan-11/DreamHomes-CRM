<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    //    /**
    //     * Sample database connection for SQLite3.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'database'    => 'database.db',
    //        'DBDriver'    => 'SQLite3',
    //        'DBPrefix'    => '',
    //        'DBDebug'     => true,
    //        'swapPre'     => '',
    //        'failover'    => [],
    //        'foreignKeys' => true,
    //        'busyTimeout' => 1000,
    //        'synchronous' => null,
    //        'dateFormat'  => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for Postgre.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'public',
    //        'DBDriver'   => 'Postgre',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'port'       => 5432,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for SQLSRV.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'dbo',
    //        'DBDriver'   => 'SQLSRV',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'encrypt'    => false,
    //        'failover'   => [],
    //        'port'       => 1433,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    //    /**
    //     * Sample database connection for OCI8.
    //     *
    //     * You may need the following environment variables:
    //     *   NLS_LANG                = 'AMERICAN_AMERICA.UTF8'
    //     *   NLS_DATE_FORMAT         = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_FORMAT    = 'YYYY-MM-DD HH24:MI:SS'
    //     *   NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS'
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'DSN'        => 'localhost:1521/FREEPDB1',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'DBDriver'   => 'OCI8',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'AL32UTF8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => 'root',
        'password'    => '',
        'database'    => 'talenthub_test',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => '',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_unicode_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'dateFormat'  => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * The ONLY database automated tests may touch. PHPUnit's DatabaseTestTrait runs migration
     * rollbacks/refreshes and the suite inserts/deletes rows, so it must never share a database
     * with development, staging or production. See docs/transformation/TEST_DATABASE_ISOLATION.md.
     */
    public const TEST_DATABASE = 'talenthub_test';

    /** Databases PHPUnit may ever use. Selected with env TEST_DB_NAME (whitelist only; anything else is ignored). */
    public const ALLOWED_TEST_DATABASES = ['talenthub_test', 'dhis_crm_test'];

    public static function testDatabaseName(): string
    {
        $requested = (string) (getenv('TEST_DB_NAME') ?: ($_SERVER['TEST_DB_NAME'] ?? ''));

        return in_array($requested, self::ALLOWED_TEST_DATABASES, true) ? $requested : self::TEST_DATABASE;
    }

    public function __construct()
    {
        parent::__construct();

        // Under PHPUnit (ENVIRONMENT === 'testing') EVERY group is pinned to the isolated test database,
        // on the local machine, after .env / phpunit.xml overrides have been applied — so neither an
        // environment variable nor an explicit connect('default') can reach a real database.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
            foreach (['default', 'tests'] as $group) {
                $this->{$group}['database'] = self::testDatabaseName();
                $this->{$group}['hostname'] = '127.0.0.1';
                $this->{$group}['DSN']      = '';
                $this->{$group}['failover'] = [];
            }
        } elseif (empty($this->default['database']) && isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'dhiscrm.talenthubconsultant.com')) {
            $this->default['hostname'] = '127.0.0.1';
            $this->default['database'] = 'u139147679_dhiscrm';
            $this->default['username'] = 'u139147679_dhiscrm';
            $this->default['password'] = 'DreamHomes2026!Db';
            $this->default['port']     = 3306;
            $this->default['DBDriver'] = 'MySQLi';
        }
    }

    /**
     * Refuse to continue unless the live connection really is the test database.
     * Called from tests/bootstrap.php before any test (and any migration/refresh) runs.
     *
     * @throws \RuntimeException
     */
    public static function assertSafeTestDatabase(): string
    {
        if (ENVIRONMENT !== 'testing') {
            throw new \RuntimeException('Refusing to run destructive test operations: ENVIRONMENT is "' . ENVIRONMENT . '", not "testing".');
        }
        foreach (['default', 'tests'] as $group) {
            $db     = static::connect($group);
            $active = (string) ($db->query('SELECT DATABASE() AS d')->getRowArray()['d'] ?? '');
            $host   = (string) $db->hostname;
            if ($active !== self::testDatabaseName() || ! in_array($host, ['127.0.0.1', 'localhost'], true)) {
                throw new \RuntimeException("Refusing to run tests: group '{$group}' is connected to '{$active}' on '{$host}'. Only '" . self::testDatabaseName() . "' on localhost is allowed.");
            }
        }
        return self::testDatabaseName();
    }
}
