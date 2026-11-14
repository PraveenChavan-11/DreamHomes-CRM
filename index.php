<?php

/**
 * TalentHub Consultant — Root Web Entrypoint
 * Bridges incoming requests directly to the CodeIgniter 4 public/ front controller.
 */

// Staging Host Auto-Detection Guard
if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], 'staging.talenthubconsultant.com')) {
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

