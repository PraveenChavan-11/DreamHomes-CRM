<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Host Auto-Detection & Live Environment Guard
$isHostinger = (isset($_SERVER['HTTP_HOST']) && (str_contains($_SERVER['HTTP_HOST'], 'talenthubconsultant.com') || str_contains($_SERVER['HTTP_HOST'], 'dhiscrm')))
    || (isset($_SERVER['DOCUMENT_ROOT']) && str_contains($_SERVER['DOCUMENT_ROOT'], 'u139147679'))
    || (isset($_SERVER['SCRIPT_FILENAME']) && str_contains($_SERVER['SCRIPT_FILENAME'], 'u139147679'))
    || (is_dir('/home/u139147679'));

if ($isHostinger) {
    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'production');
    }
    $_SERVER['CI_ENVIRONMENT'] = 'production';
    $_ENV['CI_ENVIRONMENT'] = 'production';
    putenv('CI_ENVIRONMENT=production');

    $_SERVER['DHIS_DEMO_ENV'] = 'true';
    $_ENV['DHIS_DEMO_ENV'] = 'true';
    putenv('DHIS_DEMO_ENV=true');

    // Auto-provision .env on live Hostinger deployment if not present
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) {
        $envContent = "CI_ENVIRONMENT = production\n"
            . "app.baseURL = 'https://dhiscrm.talenthubconsultant.com/'\n\n"
            . "database.default.hostname = 127.0.0.1\n"
            . "database.default.database = u139147679_dhiscrm\n"
            . "database.default.username = u139147679_dhiscrm\n"
            . "database.default.password = DreamHomes2026!Db\n"
            . "database.default.DBDriver = MySQLi\n"
            . "database.default.port = 3306\n\n"
            . "DHIS_DEMO_ENV = true\n";
        @file_put_contents($envFile, $envContent);
        @chmod($envFile, 0600);
    }
} elseif (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'staging.talenthubconsultant.com')) {
    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'staging');
    }
    $_SERVER['CI_ENVIRONMENT'] = 'staging';
    $_ENV['CI_ENVIRONMENT'] = 'staging';
    putenv('CI_ENVIRONMENT=staging');
}

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// Ensure writable directory exists before framework boots
$writableDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'writable';
if (!is_dir($writableDir)) {
    @mkdir($writableDir, 0775, true);
}

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
