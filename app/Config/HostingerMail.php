<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Models\WebsiteSettingModel;

class HostingerMail extends BaseConfig
{
    public bool $enabled = true;
    public string $apiBaseUrl = 'https://api.mail.hostinger.com';
    public string $apiToken = '';
    public string $defaultMailboxId = '';
    public string $webhookSecret = '';
    public int $syncIntervalMinutes = 5;
    public int $timeoutSeconds = 20;

    public function __construct()
    {
        parent::__construct();

        // 1. Load from environment (.env)
        $envEnabled = env('HOSTINGER_MAIL_ENABLED');
        if ($envEnabled !== null) {
            $this->enabled = filter_var($envEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        $envBaseUrl = env('HOSTINGER_MAIL_API_BASE_URL');
        if (!empty($envBaseUrl)) {
            $this->apiBaseUrl = rtrim($envBaseUrl, '/');
        }

        $envToken = env('HOSTINGER_MAIL_API_TOKEN');
        if (!empty($envToken)) {
            $this->apiToken = trim($envToken);
        }

        $envDefaultMailbox = env('HOSTINGER_MAIL_DEFAULT_MAILBOX_ID');
        if (!empty($envDefaultMailbox)) {
            $this->defaultMailboxId = trim($envDefaultMailbox);
        }

        $envWebhookSecret = env('HOSTINGER_MAIL_WEBHOOK_SECRET');
        if (!empty($envWebhookSecret)) {
            $this->webhookSecret = trim($envWebhookSecret);
        }

        // 2. Allow database settings to override if configured via Admin UI
        try {
            $settingModel = new WebsiteSettingModel();
            $dbToken = $settingModel->getSetting('hostinger_mail_api_token');
            if (!empty($dbToken)) {
                $this->apiToken = trim($dbToken);
            }

            $dbBaseUrl = $settingModel->getSetting('hostinger_mail_api_base_url');
            if (!empty($dbBaseUrl)) {
                $this->apiBaseUrl = rtrim($dbBaseUrl, '/');
            }

            $dbDefaultMailbox = $settingModel->getSetting('hostinger_mail_default_mailbox_id');
            if (!empty($dbDefaultMailbox)) {
                $this->defaultMailboxId = trim($dbDefaultMailbox);
            }

            $dbWebhookSecret = $settingModel->getSetting('hostinger_mail_webhook_secret');
            if (!empty($dbWebhookSecret)) {
                $this->webhookSecret = trim($dbWebhookSecret);
            }

            $dbEnabled = $settingModel->getSetting('hostinger_mail_enabled');
            if ($dbEnabled !== null) {
                $this->enabled = (bool) $dbEnabled;
            }
        } catch (\Throwable $e) {
            // Silently fall back to .env if DB not connected yet
        }
    }

    /**
     * Check if a valid API token is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && strlen($this->apiToken) > 10;
    }

    /**
     * Get masked API token for administrative display (never expose raw token in JS or full logs)
     */
    public function getMaskedToken(): string
    {
        if (empty($this->apiToken)) {
            return '';
        }

        $len = strlen($this->apiToken);
        if ($len <= 8) {
            return '********';
        }

        return substr($this->apiToken, 0, 4) . str_repeat('*', max(4, $len - 8)) . substr($this->apiToken, -4);
    }
}

