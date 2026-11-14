<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise Social Publishing Engine
 *
 * Orchestrates multi-platform post publishing, idempotency, pre-flight checks,
 * media routing, execution tracking, failure classification, and admin notification dispatch.
 */
class SocialPublishingEngine
{
    private BaseConnection $db;
    private SocialSecurityService $security;
    private LinkedInApiService $linkedIn;
    private InstagramApiService $instagram;
    private SocialSettingsService $settings;

    public function __construct(
        ?BaseConnection $db = null,
        ?LinkedInApiService $linkedIn = null,
        ?InstagramApiService $instagram = null,
        ?SocialSecurityService $security = null,
        ?SocialSettingsService $settings = null
    ) {
        $this->db        = $db ?? Database::connect();
        $this->security  = $security ?? new SocialSecurityService($this->db);
        $this->settings  = $settings ?? new SocialSettingsService($this->db, $this->security);
        $this->linkedIn  = $linkedIn ?? new LinkedInApiService($this->db, $this->security, $this->settings);
        $this->instagram = $instagram ?? new InstagramApiService($this->db, $this->security, $this->settings);
    }

    /**
     * Publish an existing post record across all targeted platforms.
     */
    public function publishPost(int $postId, ?int $triggeredByUserId = null): array
    {
        $post = $this->db->table('social_posts')->where('id', $postId)->get()->getRowArray();
        if (!$post) {
            throw new \InvalidArgumentException('Post record not found with ID: ' . $postId);
        }

        // APPROVAL GATE (defence in depth). Content created by an external AI agent can reach a platform only after a
        // human has approved it. This is checked here, in the single publishing path used by the admin UI, the
        // scheduler and retries, so no caller can bypass it. Nothing is modified when the gate refuses.
        if (($post['origin'] ?? 'admin') === 'agent' && !(($post['review_decision'] ?? null) === 'approved' && !empty($post['approved_by']))) {
            return [
                'success' => false,
                'status'  => 'blocked_unapproved',
                'message' => 'This post was created by an external AI agent and has not been approved by an administrator.',
                'results' => ['error' => 'Blocked: agent-created content requires administrator approval before publishing.'],
            ];
        }

        // Check if already published
        if ($post['status'] === 'published') {
            return [
                'success' => true,
                'status'  => 'published',
                'message' => 'Post has already been published across all targeted platforms.',
            ];
        }

        // Set to processing
        $this->db->table('social_posts')->where('id', $postId)->update([
            'status'     => 'processing',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $platforms = $this->db->table('social_post_platforms')
            ->where('post_id', $postId)
            ->get()
            ->getResultArray();

        if (empty($platforms)) {
            // Build default platform records if not yet initialized
            $this->initializePostPlatforms($post);
            $platforms = $this->db->table('social_post_platforms')
                ->where('post_id', $postId)
                ->get()
                ->getResultArray();
        }

        $allMedia = $this->db->table('social_post_media')
            ->where('post_id', $postId)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->getResultArray();

        $results = [];
        $hasFailures = false;
        $publishedCount = 0;

        foreach ($platforms as $pp) {
            $platformName = $pp['platform'];

            // Skip if already published on this platform
            if ($pp['status'] === 'published' && !empty($pp['external_post_id'])) {
                $results[$platformName] = [
                    'success'          => true,
                    'external_post_id' => $pp['external_post_id'],
                    'message'          => 'Already published on ' . $platformName,
                ];
                $publishedCount++;
                continue;
            }

            // Filter media assigned to this platform
            $platformMedia = array_filter($allMedia, function ($m) use ($platformName) {
                return $m['platform'] === 'all' || $m['platform'] === $platformName;
            });
            $platformMedia = array_values($platformMedia);

            // Publish to single platform
            try {
                $pubResult = $this->dispatchToPlatform($post, $pp, $platformMedia);
                $results[$platformName] = $pubResult;

                // Update platform record
                $this->db->table('social_post_platforms')->where('id', $pp['id'])->update([
                    'status'             => 'published',
                    'external_post_id'   => $pubResult['external_post_id'],
                    'external_permalink' => $pubResult['external_permalink'] ?? null,
                    'published_at'       => date('Y-m-d H:i:s'),
                    'error_message'      => null,
                    'api_response_json'  => json_encode($this->security->sanitizePayload($pubResult['raw_response'] ?? [])),
                    'updated_at'         => date('Y-m-d H:i:s'),
                ]);

                $publishedCount++;

                $this->recordAttempt(0, $postId, $platformName, 1, 'success', 200, null, null, [
                    'caption' => $pp['caption'],
                    'media'   => count($platformMedia),
                ], $pubResult['raw_response'] ?? []);

            } catch (\Throwable $e) {
                $hasFailures = true;
                $errMsg = $e->getMessage();
                $results[$platformName] = [
                    'success' => false,
                    'error'   => $errMsg,
                ];

                $this->db->table('social_post_platforms')->where('id', $pp['id'])->update([
                    'status'        => 'failed',
                    'error_message' => $errMsg,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

                $isRetryable = $this->isRetryable($platformName, $e);
                $attemptStatus = $isRetryable ? 'retryable_failure' : 'permanent_failure';

                $this->recordAttempt(0, $postId, $platformName, 1, $attemptStatus, 500, null, $errMsg, [
                    'caption' => $pp['caption'],
                ], ['error' => $errMsg]);

                // Create alert notification
                $this->createNotification(
                    $triggeredByUserId,
                    'publish_failed',
                    "Publishing Failed: {$post['title']}",
                    "Failed to publish to {$platformName}: {$errMsg}",
                    base_url("admin/social/posts/{$postId}")
                );
            }
        }

        // Update overall post status
        $finalStatus = 'published';
        if ($publishedCount === 0 && $hasFailures) {
            $finalStatus = 'failed';
        } elseif ($hasFailures) {
            $finalStatus = 'published'; // Partial success
        }

        $this->db->table('social_posts')->where('id', $postId)->update([
            'status'       => $finalStatus,
            'published_at' => $publishedCount > 0 ? date('Y-m-d H:i:s') : null,
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        if ($finalStatus === 'published') {
            $this->createNotification(
                $triggeredByUserId,
                'publish_success',
                "Post Published: {$post['title']}",
                "Successfully published across {$publishedCount} platform(s).",
                base_url("admin/social/posts/{$postId}")
            );
        }

        $this->settings->logActivity($triggeredByUserId, 'publish_post', 'social_posts', (string) $postId, [
            'final_status'    => $finalStatus,
            'published_count' => $publishedCount,
            'results'         => $results,
        ], $hasFailures ? 'warning' : 'success');

        return [
            'success'         => !$hasFailures,
            'status'          => $finalStatus,
            'published_count' => $publishedCount,
            'results'         => $results,
        ];
    }

    /**
     * Dispatch post to specific platform adapter.
     */
    private function dispatchToPlatform(array $post, array $postPlatform, array $media): array
    {
        $platform = $postPlatform['platform'];
        $caption  = trim($postPlatform['caption'] !== '' ? $postPlatform['caption'] : $post['base_content']);
        $hashtags = trim($postPlatform['hashtags'] ?? $post['base_hashtags'] ?? '');
        $destUrl  = trim($postPlatform['destination_url'] ?? $post['base_destination_url'] ?? '');

        $fullText = $caption;
        if ($hashtags !== '') {
            $fullText .= "\n\n" . $hashtags;
        }

        // Identify connected account
        $accountId = !empty($postPlatform['account_id']) ? (int) $postPlatform['account_id'] : $this->getDefaultAccountId($platform);
        if (!$accountId) {
            throw new \RuntimeException("No active connected {$platform} account available to publish.");
        }

        if ($platform === 'linkedin') {
            return $this->linkedIn->publishPost($accountId, $fullText, $media, $destUrl !== '' ? $destUrl : null);
        }

        if ($platform === 'instagram') {
            return $this->instagram->publishPost($accountId, $fullText, $media);
        }

        throw new \InvalidArgumentException("Unsupported social platform: {$platform}");
    }

    /**
     * Get default active account ID for a platform.
     */
    public function getDefaultAccountId(string $platform): ?int
    {
        $row = $this->db->table('social_accounts')
            ->where('platform', $platform)
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        return $row ? (int) $row['id'] : null;
    }

    /**
     * Automatically initialize post_platforms records if omitted.
     */
    private function initializePostPlatforms(array $post): void
    {
        $targetPlatforms = [];
        if ($post['primary_platform'] === 'both') {
            $targetPlatforms = ['linkedin', 'instagram'];
        } else {
            $targetPlatforms = [$post['primary_platform']];
        }

        foreach ($targetPlatforms as $p) {
            $existing = $this->db->table('social_post_platforms')
                ->where('post_id', $post['id'])
                ->where('platform', $p)
                ->get()
                ->getRowArray();

            if (!$existing) {
                $this->db->table('social_post_platforms')->insert([
                    'post_id'         => $post['id'],
                    'platform'        => $p,
                    'account_id'      => $this->getDefaultAccountId($p),
                    'caption'         => $post['base_content'],
                    'hashtags'        => $post['base_hashtags'],
                    'cta'             => $post['base_cta'],
                    'destination_url' => $post['base_destination_url'],
                    'status'          => 'pending',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Check if exception is retryable.
     */
    private function isRetryable(string $platform, \Throwable $e): bool
    {
        $msg = $e->getMessage();
        if ($platform === 'linkedin') {
            return $this->linkedIn->isRetryableError(500, $msg);
        }
        if ($platform === 'instagram') {
            return $this->instagram->isRetryableError(500, $msg);
        }
        return false;
    }

    /**
     * Record execution attempt in audit queue.
     */
    public function recordAttempt(
        int $jobId,
        int $postId,
        string $platform,
        int $attemptNumber,
        string $status,
        int $httpStatus,
        ?string $errorCode,
        ?string $errorMessage,
        array $requestPayload,
        array $responsePayload
    ): void {
        try {
            $this->db->table('social_publish_attempts')->insert([
                'job_id'                     => $jobId,
                'post_id'                    => $postId,
                'platform'                   => $platform,
                'attempt_number'             => $attemptNumber,
                'started_at'                 => date('Y-m-d H:i:s'),
                'completed_at'               => date('Y-m-d H:i:s'),
                'status'                     => $status,
                'http_status'                => $httpStatus,
                'error_code'                 => $errorCode,
                'error_message'              => $errorMessage,
                'request_payload_sanitized'  => json_encode($this->security->sanitizePayload($requestPayload), JSON_UNESCAPED_UNICODE),
                'response_payload_sanitized' => json_encode($this->security->sanitizePayload($responsePayload), JSON_UNESCAPED_UNICODE),
                'created_at'                 => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'recordAttempt failed: ' . $e->getMessage());
        }
    }

    /**
     * Create in-dashboard notification and trigger email if configured.
     */
    public function createNotification(?int $userId, string $eventType, string $title, string $message, ?string $link = null): void
    {
        try {
            $this->db->table('social_notifications')->insert([
                'user_id'    => $userId,
                'event_type' => $eventType,
                'title'      => $title,
                'message'    => $message,
                'link'       => $link,
                'is_read'    => 0,
                'email_sent' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'createNotification failed: ' . $e->getMessage());
        }
    }
}

