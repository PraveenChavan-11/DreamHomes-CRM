<?php

namespace App\Commands;

use App\Services\EnvironmentSafetyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * TalentHub Consultant — Staging Environment Status & Telemetry Command
 *
 * Usage: php spark staging:status
 */
class StagingStatus extends BaseCommand
{
    protected $group       = 'TalentHub Staging';
    protected $name        = 'staging:status';
    protected $description = 'Display comprehensive Staging environment telemetry and isolation status';

    public function run(array $params)
    {
        CLI::write("==================================================", 'cyan');
        CLI::write(" TALENTHUB STAGING STATUS & ISOLATION TELEMETRY", 'cyan');
        CLI::write("==================================================", 'cyan');

        $telemetry = EnvironmentSafetyService::getStagingHealthSummary();

        CLI::write("ENVIRONMENT ARCHITECTURE:", 'yellow');
        CLI::write("  Runtime Environment:    " . strtoupper($telemetry['environment']), 'green');
        CLI::write("  Is Staging:             " . ($telemetry['is_staging'] ? 'YES' : 'NO'), $telemetry['is_staging'] ? 'green' : 'red');
        CLI::write("  Is Production:          " . ($telemetry['is_production'] ? 'YES' : 'NO'), $telemetry['is_production'] ? 'red' : 'green');
        CLI::write("  Application Base URL:   " . $telemetry['app_url'], 'white');
        CLI::write("  Git Branch:             " . $telemetry['git']['branch'], 'white');
        CLI::newLine();

        CLI::write("DATABASE ISOLATION:", 'yellow');
        CLI::write("  Connection Status:      " . $telemetry['database']['status'], 'green');
        CLI::write("  Database Name:          " . $telemetry['database']['name'], 'white');
        CLI::write("  Database Host:          " . $telemetry['database']['host'], 'white');
        CLI::write("  Strict Name Isolation:  " . ($telemetry['database']['is_isolated'] ? 'VERIFIED (Contains staging)' : 'WARNING: NOT MARKED AS STAGING'), $telemetry['database']['is_isolated'] ? 'green' : 'red');
        if (!empty($telemetry['database']['counts'])) {
            CLI::write("  Active Table Counts:", 'white');
            foreach ($telemetry['database']['counts'] as $table => $cnt) {
                CLI::write("    - {$table}: {$cnt}", 'white');
            }
        }
        CLI::newLine();

        CLI::write("EMAIL & NOTIFICATION SANDBOX:", 'yellow');
        CLI::write("  Sandbox Status:         " . $telemetry['email_sandbox']['status'], 'green');
        CLI::write("  Capture Test Inbox:     " . $telemetry['email_sandbox']['inbox'], 'white');
        CLI::newLine();

        CLI::write("SEARCH ENGINE & SEO DEFENSE:", 'yellow');
        CLI::write("  Public Indexing:        " . ($telemetry['seo_protection']['indexing_allowed'] ? 'ALLOWED' : 'BLOCKED (noindex)'), $telemetry['seo_protection']['indexing_allowed'] ? 'red' : 'green');
        CLI::write("  Robots Meta Header:     " . $telemetry['seo_protection']['robots_meta'], 'white');
        CLI::write("  robots.txt Directive:   " . $telemetry['seo_protection']['robots_txt'], 'white');
        CLI::write("  Sitemaps Blocked:       " . ($telemetry['seo_protection']['sitemaps_blocked'] ? 'YES (404 Disabled)' : 'NO'), 'green');
        CLI::newLine();

        CLI::write("PWA & BROWSER PARTITIONING:", 'yellow');
        CLI::write("  PWA Manifest:           " . $telemetry['pwa_isolation']['manifest'], 'white');
        CLI::write("  Service Worker:         " . $telemetry['pwa_isolation']['service_worker'], 'white');
        CLI::write("  Isolated Cache Name:    " . $telemetry['pwa_isolation']['cache_name'], 'white');
        CLI::newLine();

        CLI::write("STORAGE & PERMISSIONS:", 'yellow');
        CLI::write("  Writable Path:          " . $telemetry['storage']['writable'], 'white');
        CLI::write("  Is Writable:            " . ($telemetry['storage']['is_writable'] ? 'YES' : 'NO'), $telemetry['storage']['is_writable'] ? 'green' : 'red');
        CLI::write("  Vault Ready:            " . ($telemetry['storage']['is_vault_ready'] ? 'YES' : 'NO'), $telemetry['storage']['is_vault_ready'] ? 'green' : 'red');
        CLI::newLine();
        CLI::write("Diagnostic check completed at: " . $telemetry['timestamp'], 'cyan');
    }
}

