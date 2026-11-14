<?php

/**
 * TalentHub Consultant — Staging Environment Bootstrap
 *
 * Configures the PHP runtime for the isolated Staging environment:
 * - Prevents raw error dumps or backtraces from displaying publicly
 * - Logs all runtime notices, warnings, and errors to writable/logs
 * - Disables debug mode in public responses to protect server internals
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');

defined('CI_DEBUG') || define('CI_DEBUG', false);

