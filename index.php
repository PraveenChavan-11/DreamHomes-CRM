<?php

/**
 * TalentHub Consultant — Root Web Entrypoint
 * Bridges incoming requests directly to the CodeIgniter 4 public/ front controller.
 */

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
    $envFile = __DIR__ . '/.env';
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

// Ensure writable directory exists
if (!is_dir(__DIR__ . '/writable')) {
    @mkdir(__DIR__ . '/writable', 0775, true);
}

// If accessed directly, route to public front controller
require_once __DIR__ . '/public/index.php';

