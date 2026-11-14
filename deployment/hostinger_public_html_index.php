<?php

/**
 * TalentHub Consultant — Hostinger Production Web Front Controller
 * Location on Hostinger: /home/uXXXXX/public_html/index.php
 *
 * Core application files reside securely outside the public web root in:
 * /home/uXXXXX/talenthub_core/
 */

// Check PHP version requirement
$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION
    );
    exit($message);
}

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
chdir(FCPATH);

// Load the paths config file from the core directory outside public_html
require FCPATH . '../talenthub_core/app/Config/Paths.php';

$paths = new Config\Paths();

// Override system directory to point to talenthub_core
$paths->systemDirectory = FCPATH . '../talenthub_core/vendor/codeigniter4/framework/system';
$paths->appDirectory    = FCPATH . '../talenthub_core/app';
$paths->writableDirectory = FCPATH . '../talenthub_core/writable';
$paths->testsDirectory  = FCPATH . '../talenthub_core/tests';
$paths->viewDirectory   = FCPATH . '../talenthub_core/app/Views';

// Load the framework bootstrap file
require $paths->systemDirectory . '/Boot.php';

exit(CodeIgniter\Boot::bootWeb($paths));

