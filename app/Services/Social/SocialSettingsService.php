<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use App\Services\EnvironmentSafetyService;

/**
 * Enterprise Social Settings & Health Check Service
 *
 * Manages platform API credentials with environment variable fallbacks,
 * encrypted storage at rest, masked administrative view models,
 * and a comprehensive security & integration health checker.
 */
class SocialSettingsService
{
    private BaseConnection $db;
    private SocialSecurityService $security;

    public function __construct(?BaseConnection $db = null, ?SocialSecurityService $security = null)
    {
        $this->db       = $db ?? Database::connect();
        $this->security = $security ?? new SocialSecurityService($this->db);
    }

    /**
     * Retrieve settings for LinkedIn.
     */
    public function getLinkedInConfig(): array
    {
        $row = $this->db->table('social_credentials')
            ->where('platform', 'linkedin')
            ->where('account_id', null)
            ->get()
            ->getRowArray();

        $clientId     = env('LINKEDIN_CLIENT_ID', $row['client_id'] ?? '');
        $clientSecret = '';
        if (!empty($row['client_secret_encrypted'])) {
            try {
                $clientSecret = $this->security->decryptSecret($row['client_secret_encrypted']);
            } catch (\Throwable $e) {
                log_message('error', 'LinkedIn secret decrypt failed: ' . $e->getMessage());
            }
        }
        if ($clientSecret === '') {
            $clientSecret = env('LINKEDIN_CLIENT_SECRET', '');
        }

        $orgId       = env('LINKEDIN_ORG_ID', '');
        $apiVersion  = $row['api_version'] ?? env('LINKEDIN_API_VERSION', '202401');
        $redirectUri = base_url('admin/social/oauth/callback/linkedin');

        return [
            'client_id'               => (string) $clientId,
            'client_secret'           => (string) $clientSecret,
            'client_secret_masked'    => $this->security->maskSecret($clientSecret),
            'org_id'                  => (string) $orgId,
            'api_version'             => (string) $apiVersion,
            'redirect_uri'            => $redirectUri,
            'is_configured'           => !empty($clientId) && !empty($clientSecret),
            'updated_at'              => $row['updated_at'] ?? null,
        ];
    }

    /**
     * Retrieve settings for Instagram (Meta Graph API).
     */
    public function getInstagramConfig(): array
    {
        $row = $this->db->table('social_credentials')
            ->where('platform', 'instagram')
            ->where('account_id', null)
            ->get()
            ->getRowArray();

        $appId     = env('META_APP_ID', $row['app_id'] ?? $row['client_id'] ?? '');
        $appSecret = '';
        if (!empty($row['client_secret_encrypted'])) {
            try {
                $appSecret = $this->security->decryptSecret($row['client_secret_encrypted']);
            } catch (\Throwable $e) {
                log_message('error', 'Meta App secret decrypt failed: ' . $e->getMessage());
            }
        }
        if ($appSecret === '') {
            $appSecret = env('META_APP_SECRET', '');
        }

        $igAccountId = env('INSTAGRAM_ACCOUNT_ID', '');
        $fbPageId    = env('FACEBOOK_PAGE_ID', '');
        $apiVersion  = $row['api_version'] ?? env('META_GRAPH_VERSION', 'v19.0');
        $redirectUri = base_url('admin/social/oauth/callback/instagram');

        return [
            'app_id'                  => (string) $appId,
            'app_secret'              => (string) $appSecret,
            'app_secret_masked'       => $this->security->maskSecret($appSecret),
            'ig_account_id'           => (string) $igAccountId,
            'fb_page_id'              => (string) $fbPageId,
            'api_version'             => (string) $apiVersion,
            'redirect_uri'            => $redirectUri,
            'is_configured'           => !empty($appId) && !empty($appSecret),
            'updated_at'              => $row['updated_at'] ?? null,
        ];
    }

    /**
     * Save LinkedIn API configuration.
     */
    public function saveLinkedInConfig(array $data, ?int $adminUserId = null): void
    {
        $clientId    = trim($data['client_id'] ?? '');
        $secretPlain = trim($data['client_secret'] ?? '');
        $apiVersion  = trim($data['api_version'] ?? '202401');

        $existing = $this->db->table('social_credentials')
            ->where('platform', 'linkedin')
            ->where('account_id', null)
            ->get()
            ->getRowArray();

        $payload = [
            'client_id'   => $clientId,
            'api_version' => $apiVersion !== '' ? $apiVersion : '202401',
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($secretPlain !== '' && !str_contains($secretPlain, '••••')) {
            $payload['client_secret_encrypted'] = $this->security->encryptSecret($secretPlain);
        }

        if ($existing) {
            $this->db->table('social_credentials')
                ->where('id', $existing['id'])
                ->update($payload);
        } else {
            $payload['platform']   = 'linkedin';
            $payload['account_id'] = null;
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('social_credentials')->insert($payload);
        }

        $this->logActivity($adminUserId, 'update_credentials', 'social_credentials', 'linkedin', [
            'client_id'   => $clientId,
            'api_version' => $apiVersion,
        ]);
    }

    /**
     * Save Meta / Instagram API configuration.
     */
    public function saveInstagramConfig(array $data, ?int $adminUserId = null): void
    {
        $appId       = trim($data['app_id'] ?? '');
        $secretPlain = trim($data['app_secret'] ?? '');
        $apiVersion  = trim($data['api_version'] ?? 'v19.0');

        $existing = $this->db->table('social_credentials')
            ->where('platform', 'instagram')
            ->where('account_id', null)
            ->get()
            ->getRowArray();

        $payload = [
            'app_id'      => $appId,
            'client_id'   => $appId,
            'api_version' => $apiVersion !== '' ? $apiVersion : 'v19.0',
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($secretPlain !== '' && !str_contains($secretPlain, '••••')) {
            $payload['client_secret_encrypted'] = $this->security->encryptSecret($secretPlain);
        }

        if ($existing) {
            $this->db->table('social_credentials')
                ->where('id', $existing['id'])
                ->update($payload);
        } else {
            $payload['platform']   = 'instagram';
            $payload['account_id'] = null;
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('social_credentials')->insert($payload);
        }

        $this->logActivity($adminUserId, 'update_credentials', 'social_credentials', 'instagram', [
            'app_id'      => $appId,
            'api_version' => $apiVersion,
        ]);
    }

    /**
     * Comprehensive System Integration & Security Health Check.
     */
    public function runHealthCheck(): array
    {
        $checks = [];

        // 1. Encryption Key
        $hasKey = SocialSecurityService::isEncryptionConfigured();
        $checks['encryption_key'] = [
            'name'        => 'Application Encryption Key (At-Rest Security)',
            'status'      => $hasKey ? 'pass' : 'fail',
            'message'     => $hasKey ? 'Active AES-256 cipher key configured in environment.' : 'encryption.key is missing! Tokens cannot be stored securely.',
            'remediation' => 'Generate and set encryption.key in .env to ensure compliance.',
        ];

        // 2. HTTPS Enforcement
        $isHttps = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') || str_starts_with(base_url(), 'https://');
        $checks['https_protocol'] = [
            'name'        => 'OAuth Transport Security (HTTPS)',
            'status'      => $isHttps ? 'pass' : 'warning',
            'message'     => $isHttps ? 'HTTPS transport active for OAuth callbacks and API payloads.' : 'Non-HTTPS connection detected. OAuth callbacks require HTTPS in production.',
            'remediation' => 'Ensure SSL certificate is active on production host.',
        ];

        // 3. Media Upload Directory
        $uploadDir = FCPATH . 'uploads/social/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        $isWritable = is_dir($uploadDir) && is_writable($uploadDir);
        $checks['media_storage'] = [
            'name'        => 'Marketing Media Upload Storage',
            'status'      => $isWritable ? 'pass' : 'fail',
            'message'     => $isWritable ? "Storage directory {$uploadDir} is writable." : "Directory {$uploadDir} is not writable by web server.",
            'remediation' => 'Ensure chmod 755 or 775 permissions on public/uploads/social/.',
        ];

        // 4. LinkedIn Configuration
        $li = $this->getLinkedInConfig();
        $checks['linkedin_app'] = [
            'name'        => 'LinkedIn Developer Application',
            'status'      => $li['is_configured'] ? 'pass' : 'warning',
            'message'     => $li['is_configured'] ? 'Client ID & Secret configured. Ready for OAuth authorization.' : 'LinkedIn App Client ID or Secret is not configured.',
            'remediation' => 'Register an app in LinkedIn Developer Portal and save credentials in Settings.',
        ];

        // 5. Instagram Configuration
        $ig = $this->getInstagramConfig();
        $checks['instagram_app'] = [
            'name'        => 'Meta / Instagram Developer Application',
            'status'      => $ig['is_configured'] ? 'pass' : 'warning',
            'message'     => $ig['is_configured'] ? 'App ID & Secret configured. Ready for Meta Login authorization.' : 'Meta App ID or App Secret is not configured.',
            'remediation' => 'Register a Meta Developer App with Instagram Graph API product enabled.',
        ];

        // 6. Connected Accounts Health
        $accounts = $this->db->table('social_accounts')->get()->getResultArray();
        $activeCount = 0;
        $expiringCount = 0;
        $now = time();

        foreach ($accounts as $acc) {
            if ($acc['status'] === 'active') {
                $activeCount++;
            }
            $cred = $this->db->table('social_credentials')
                ->where('account_id', $acc['id'])
                ->get()
                ->getRowArray();
            if ($cred && !empty($cred['token_expires_at'])) {
                $expTime = strtotime($cred['token_expires_at']);
                if ($expTime - $now < (7 * 86400) && $expTime > $now) {
                    $expiringCount++;
                }
            }
        }

        $checks['connected_accounts'] = [
            'name'        => 'Connected Social Accounts',
            'status'      => $activeCount > 0 ? ($expiringCount > 0 ? 'warning' : 'pass') : 'info',
            'message'     => "{$activeCount} account(s) connected and active. " . ($expiringCount > 0 ? "({$expiringCount} token(s) expiring within 7 days)" : ""),
            'remediation' => $expiringCount > 0 ? 'Reconnect expiring accounts to refresh long-lived access tokens.' : null,
        ];

        // 7. Scheduler Health
        $lastLog = $this->db->table('social_activity_logs')
            ->where('action', 'scheduler_run')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getRowArray();

        $schedulerStatus = 'info';
        $schedulerMsg = 'Scheduler has not executed yet. Set up cron job `php spark social:publish`.';
        if ($lastLog) {
            $lastRunTime = strtotime($lastLog['created_at']);
            $diffMinutes = round(($now - $lastRunTime) / 60);
            if ($diffMinutes <= 15) {
                $schedulerStatus = 'pass';
                $schedulerMsg = "Scheduler running normally. Last executed {$diffMinutes} minute(s) ago.";
            } elseif ($diffMinutes <= 60) {
                $schedulerStatus = 'warning';
                $schedulerMsg = "Scheduler last executed {$diffMinutes} minutes ago.";
            } else {
                $schedulerStatus = 'fail';
                $schedulerMsg = "Scheduler alert: Last executed {$diffMinutes} minutes ago. Please verify hosting cron job.";
            }
        }

        $checks['scheduler_health'] = [
            'name'        => 'Automated Publishing Queue Cron Scheduler',
            'status'      => $schedulerStatus,
            'message'     => $schedulerMsg,
            'remediation' => 'Add `* * * * * cd /path/to/app && php spark social:publish >> /dev/null 2>&1` in cPanel/hPanel Cron Jobs.',
        ];

        $overallStatus = 'pass';
        foreach ($checks as $c) {
            if ($c['status'] === 'fail') {
                $overallStatus = 'fail';
                break;
            }
            if ($c['status'] === 'warning' && $overallStatus === 'pass') {
                $overallStatus = 'warning';
            }
        }

        return [
            'overall_status' => $overallStatus,
            'timestamp'      => date('Y-m-d H:i:s'),
            'checks'         => $checks,
        ];
    }

    /**
     * Record sanitized administrative activity log.
     */
    public function logActivity(?int $userId, string $action, string $entityType, ?string $entityId, ?array $metadata = null, string $result = 'success'): void
    {
        try {
            $request = service('request');
            $ip = $request ? $request->getIPAddress() : '127.0.0.1';
            $ua = $request && $request->getUserAgent() ? substr($request->getUserAgent()->getAgentString() ?? '', 0, 255) : 'CLI';

            $cleanMetadata = $metadata ? $this->security->sanitizePayload($metadata) : null;

            $this->db->table('social_activity_logs')->insert([
                'user_id'       => $userId,
                'action'        => $action,
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'metadata_json' => is_array($cleanMetadata) ? json_encode($cleanMetadata, JSON_UNESCAPED_UNICODE) : null,
                'ip_address'    => $ip,
                'user_agent'    => $ua,
                'result'        => $result,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'SocialSettingsService::logActivity failed: ' . $e->getMessage());
        }
    }
}

