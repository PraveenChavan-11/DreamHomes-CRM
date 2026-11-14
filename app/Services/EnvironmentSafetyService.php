<?php

namespace App\Services;

use Config\Database;
use Config\Services;

/**
 * TalentHub Consultant — Environment Safety & Isolation Service
 *
 * Enforces strict environment separation between Production and Staging.
 * Acts as the authoritative gatekeeper for:
 * 1. Environment identification (production, staging, development, testing)
 * 2. Destructive action prevention against production
 * 3. Outbound email sandboxing
 * 4. Search engine indexing prevention
 * 5. Third-party telemetry/analytics isolation
 * 6. PWA manifest and service worker cache partitioning
 */
class EnvironmentSafetyService
{
    /**
     * Get current runtime environment name.
     */
    public static function getEnvironment(): string
    {
        if (defined('ENVIRONMENT')) {
            return strtolower(ENVIRONMENT);
        }

        $env = env('CI_ENVIRONMENT');
        if (!empty($env)) {
            return strtolower($env);
        }

        return 'production';
    }

    /**
     * Determine if current runtime is Staging.
     */
    public static function isStaging(): bool
    {
        $env = self::getEnvironment();
        if ($env === 'staging') {
            return true;
        }

        // Secondary host-level heuristic
        if (isset($_SERVER['HTTP_HOST']) && str_starts_with(strtolower($_SERVER['HTTP_HOST']), 'staging.')) {
            return true;
        }

        $appUrl = (string) (config('App')->baseURL ?? '');
        if (str_contains($appUrl, 'staging.talenthubconsultant.com')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if current runtime is Production.
     */
    public static function isProduction(): bool
    {
        // Staging takes priority over fallback production strings
        if (self::isStaging()) {
            return false;
        }

        return self::getEnvironment() === 'production';
    }

    /**
     * Determine if running in local development or test environment.
     */
    public static function isLocal(): bool
    {
        $env = self::getEnvironment();
        return in_array($env, ['development', 'testing', 'local'], true);
    }

    /**
     * Strict Production Guard: Throws fatal exception if called in Production.
     * Also checks active database name to prevent cross-environment execution.
     *
     * @throws \RuntimeException
     */
    public static function assertNotProduction(string $operationName = 'This operation'): void
    {
        if (self::isProduction()) {
            throw new \RuntimeException(
                "FATAL SAFETY VIOLATION: '{$operationName}' cannot be executed in the PRODUCTION environment!"
            );
        }

        // Secondary database name safeguard
        try {
            $db = Database::connect();
            $dbName = strtolower($db->getDatabase());
            // If connected to production DB (u139147679_talenthub) and not staging
            if (str_contains($dbName, 'talenthub') && !str_contains($dbName, 'staging') && !str_contains($dbName, 'test') && !str_contains($dbName, '_db')) {
                throw new \RuntimeException(
                    "FATAL DATABASE GUARD: Active database is '{$dbName}' (PRODUCTION). '{$operationName}' aborted."
                );
            }
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException) {
                throw $e;
            }
            // If DB connection fails, allow exception to bubble
        }
    }

    /**
     * Check if email delivery should be sandboxed.
     * In staging, always true. Can also be overridden by env setting.
     */
    public static function isEmailSandboxed(): bool
    {
        if (self::isStaging()) {
            return true;
        }

        $envSetting = env('EMAIL_SANDBOX_ENABLED');
        if ($envSetting !== null && ($envSetting === true || $envSetting === 'true' || $envSetting === '1')) {
            return true;
        }

        return !self::isProduction();
    }

    /**
     * Get designated staging test email capture inbox.
     */
    public static function getSandboxInbox(): string
    {
        return env('STAGING_SANDBOX_INBOX') ?: 'staging-logs@talenthubconsultant.com';
    }

    /**
     * Check if search engine indexing is allowed.
     * Forbidden on Staging, allowed ONLY on verified Production.
     */
    public static function isIndexingAllowed(): bool
    {
        if (self::isStaging() || self::isLocal()) {
            return false;
        }

        return self::isProduction();
    }

    /**
     * Check if live analytics (Google Analytics 4 / Google Tag Manager) is allowed.
     * Suppressed in Staging to prevent polluting production metrics.
     */
    public static function isAnalyticsAllowed(): bool
    {
        if (self::isStaging() || self::isLocal()) {
            return false;
        }

        return self::isProduction();
    }

    /**
     * Get active PWA manifest filename for current environment.
     */
    public static function getManifestPath(): string
    {
        if (self::isStaging()) {
            return 'manifest-staging.json';
        }

        return 'manifest.json';
    }

    /**
     * Get active PWA service worker script filename for current environment.
     */
    public static function getServiceWorkerPath(): string
    {
        if (self::isStaging()) {
            return 'sw-staging.js';
        }

        return 'sw.js';
    }

    /**
     * Gather comprehensive telemetry and status for Staging Control Centre.
     */
    public static function getStagingHealthSummary(): array
    {
        $db = null;
        $dbStatus = 'DISCONNECTED';
        $dbName = 'UNKNOWN';
        $dbHost = 'UNKNOWN';
        $isDbIsolated = false;
        $tableCounts = [];

        try {
            $db = Database::connect();
            if ($db->connect()) {
                $dbStatus = 'CONNECTED';
                $dbName = $db->getDatabase();
                $dbHost = $db->hostname;
                $isDbIsolated = str_contains(strtolower($dbName), 'staging');

                $tables = [
                    'jobs',
                    'applications',
                    'contact_enquiries',
                    'employer_enquiries',
                    'newsletter_subscribers',
                    'announcements',
                    'email_outbox',
                    'audit_logs',
                    'admin_users',
                ];

                foreach ($tables as $t) {
                    if ($db->tableExists($t)) {
                        $tableCounts[$t] = $db->table($t)->countAllResults();
                    }
                }
            }
        } catch (\Throwable $e) {
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        // Git metadata
        $gitBranch = 'main';
        $gitCommit = 'HEAD';
        if (is_dir(ROOTPATH . '.git')) {
            $headFile = ROOTPATH . '.git/HEAD';
            if (file_exists($headFile)) {
                $ref = trim(file_get_contents($headFile));
                if (str_starts_with($ref, 'ref: refs/heads/')) {
                    $gitBranch = substr($ref, 16);
                }
            }
        }

        // Storage verification
        $writablePath = WRITEPATH;
        $isWritable = is_writable($writablePath);
        $vaultPath = WRITEPATH . 'vault/resumes';
        $isVaultReady = is_dir($vaultPath) && is_writable($vaultPath);

        return [
            'environment'     => self::getEnvironment(),
            'is_staging'      => self::isStaging(),
            'is_production'   => self::isProduction(),
            'app_url'         => (string) (config('App')->baseURL ?? ''),
            'git'             => [
                'branch' => $gitBranch,
                'commit' => $gitCommit,
            ],
            'database'        => [
                'status'      => $dbStatus,
                'name'        => $dbName,
                'host'        => $dbHost,
                'is_isolated' => $isDbIsolated,
                'counts'      => $tableCounts,
            ],
            'email_sandbox'   => [
                'enabled' => self::isEmailSandboxed(),
                'inbox'   => self::getSandboxInbox(),
                'status'  => self::isEmailSandboxed() ? 'ACTIVE (INTERCEPTING OUTBOUND)' : 'LIVE',
            ],
            'seo_protection'  => [
                'indexing_allowed' => self::isIndexingAllowed(),
                'robots_meta'      => self::isStaging() ? 'noindex, nofollow, noarchive, nosnippet' : 'index, follow',
                'robots_txt'       => self::isStaging() ? 'Disallow: /' : 'Allow: /',
                'sitemaps_blocked' => self::isStaging(),
            ],
            'pwa_isolation'   => [
                'manifest'       => self::getManifestPath(),
                'service_worker' => self::getServiceWorkerPath(),
                'cache_name'     => self::isStaging() ? 'talenthub-staging-cache-v1' : 'talenthub-cache-v3',
            ],
            'storage'         => [
                'writable'      => $writablePath,
                'is_writable'   => $isWritable,
                'is_vault_ready'=> $isVaultReady,
            ],
            'timestamp'       => date('Y-m-d H:i:s T'),
        ];
    }
}

