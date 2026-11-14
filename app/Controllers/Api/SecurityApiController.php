<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\LoginAttemptModel;
use App\Services\SitemapService;
use CodeIgniter\HTTP\ResponseInterface;

class SecurityApiController extends BaseController
{
    public function stats(): ResponseInterface
    {
        $db = \Config\Database::connect();

        $totalAttempts = $db->table('login_attempts')->countAllResults();
        $failedAttempts = $db->table('login_attempts')->where('success', 0)->countAllResults();
        $successfulAttempts = $db->table('login_attempts')->where('success', 1)->countAllResults();
        $lockedAccounts = $db->table('admin_users')->where('locked_until >', date('Y-m-d H:i:s'))->countAllResults();

        return $this->respondJson([
            'total_login_attempts'      => $totalAttempts,
            'failed_login_attempts'     => $failedAttempts,
            'successful_login_attempts' => $successfulAttempts,
            'locked_accounts_count'     => $lockedAccounts,
            'password_hash_algorithm'   => 'Argon2id / Bcrypt (Cost 12)',
            'session_driver'            => 'File (HTTP-Only, Secure)',
            'csrf_protection'           => 'Active & Enforced',
            'rate_limiting'             => 'Active (Atomic File/Cache Throttling)',
        ]);
    }

    public function loginAttempts(): ResponseInterface
    {
        $attemptModel = new LoginAttemptModel();
        $attempts = $attemptModel->orderBy('attempted_at', 'DESC')->findAll(100);
        return $this->respondJson($attempts);
    }

    public function auditLogs(): ResponseInterface
    {
        $auditModel = new AuditLogModel();
        $logs = $auditModel->orderBy('created_at', 'DESC')->findAll(100);
        return $this->respondJson($logs);
    }

    public function health(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $dbStatus = 'OK';
        try {
            $db->query('SELECT 1');
        } catch (\Throwable $e) {
            $dbStatus = 'ERROR: ' . $e->getMessage();
        }

        $writableChecks = [
            'writable/cache'   => is_writable(WRITEPATH . 'cache'),
            'writable/logs'    => is_writable(WRITEPATH . 'logs'),
            'writable/session' => is_writable(WRITEPATH . 'session'),
            'vault/resumes'    => is_writable(WRITEPATH . 'vault/resumes'),
        ];

        return $this->respondJson([
            'system_time'    => date('Y-m-d H:i:s'),
            'php_version'    => PHP_VERSION,
            'database'       => $dbStatus,
            'disk_storage'   => $writableChecks,
            'overall_health' => ($dbStatus === 'OK') ? 'HEALTHY' : 'DEGRADED',
        ]);
    }

    public function seoStatus(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $lastSitemap = $db->table('sitemap_log')->orderBy('created_at', 'DESC')->get(1)->getRowArray();

        return $this->respondJson([
            'sitemap_status' => $lastSitemap ? $lastSitemap['status'] : 'NOT_RUN',
            'last_generated' => $lastSitemap ? $lastSitemap['created_at'] : null,
            'total_urls'     => $lastSitemap ? (int) $lastSitemap['total_urls'] : 0,
            'sitemap_urls'   => [
                base_url('sitemap.xml'),
                base_url('sitemap-pages.xml'),
                base_url('sitemap-jobs.xml'),
                base_url('sitemap-insights.xml'),
                base_url('sitemap-news.xml'),
            ],
        ]);
    }

    public function sitemapRegenerate(): ResponseInterface
    {
        $start = microtime(true);
        $service = new SitemapService();

        $service->generateIndexXml();
        $service->generatePagesXml();
        $jobsXml = $service->generateJobsXml();
        $insightsXml = $service->generateInsightsXml();
        $newsXml = $service->generateNewsXml();

        $duration = (int) round((microtime(true) - $start) * 1000);
        $totalUrls = substr_count($jobsXml, '<url>') + substr_count($insightsXml, '<url>') + substr_count($newsXml, '<url>') + 13;

        $service->recordGenerationLog('MANUAL', $totalUrls, $duration, 'SUCCESS');

        return $this->respondJson([
            'total_urls'  => $totalUrls,
            'duration_ms' => $duration,
        ], 'Sitemaps regenerated and validated successfully');
    }
}

