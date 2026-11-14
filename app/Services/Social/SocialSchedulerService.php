<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise Social Scheduler & Database Queue Runner
 * 
 * Manages database-backed scheduled publishing, atomic locking,
 * exponential backoff retry handling, stale lock recovery, and cron lifecycle monitoring.
 */
class SocialSchedulerService
{
    private BaseConnection $db;
    private SocialPublishingEngine $publishingEngine;
    private SocialSettingsService $settings;

    public const MAX_ATTEMPTS = 3;
    public const LOCK_TIMEOUT_MINUTES = 15;

    public function __construct(
        ?BaseConnection $db = null,
        ?SocialPublishingEngine $publishingEngine = null,
        ?SocialSettingsService $settings = null
    ) {
        $this->db               = $db ?? Database::connect();
        $this->settings         = $settings ?? new SocialSettingsService($this->db);
        $this->publishingEngine = $publishingEngine ?? new SocialPublishingEngine($this->db, null, null, null, $this->settings);
    }

    /**
     * Main execution entry point called by cron CLI command `php spark social:publish`.
     */
    public function processQueue(?int $batchSize = 10): array
    {
        $startTime = microtime(true);
        $workerId  = 'worker_' . getmypid() . '_' . bin2hex(random_bytes(3));

        // 1. Recover stale locks
        $staleCount = $this->recoverStaleLocks();

        // 2. Discover due scheduled posts and enqueue them
        $enqueuedCount = $this->enqueueDuePosts();

        // 3. Process claimed jobs
        $processed = 0;
        $succeeded = 0;
        $failed    = 0;
        $retried   = 0;

        $jobs = $this->claimDueJobs($workerId, $batchSize);

        foreach ($jobs as $job) {
            $processed++;
            $jobId  = (int) $job['id'];
            $postId = (int) $job['post_id'];

            try {
                $res = $this->publishingEngine->publishPost($postId, null);
                if ($res['success']) {
                    $succeeded++;
                    $this->db->table('social_scheduled_jobs')->where('id', $jobId)->update([
                        'status'     => 'completed',
                        'last_error' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    $this->handleJobFailure($job, $res['results'] ?? ['error' => 'Publishing encountered failure.']);
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->handleJobFailure($job, ['error' => $e->getMessage()]);
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // Record scheduler run in activity logs for health monitoring
        $this->settings->logActivity(null, 'scheduler_run', 'social_scheduler', $workerId, [
            'processed'   => $processed,
            'succeeded'   => $succeeded,
            'failed'      => $failed,
            'duration_ms' => $durationMs,
            'stale_freed' => $staleCount,
            'enqueued'    => $enqueuedCount,
        ]);

        return [
            'worker_id'   => $workerId,
            'processed'   => $processed,
            'succeeded'   => $succeeded,
            'failed'      => $failed,
            'stale_freed' => $staleCount,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Identify posts with status='scheduled' and scheduled_at <= NOW() and queue them.
     */
    public function enqueueDuePosts(): int
    {
        $now = date('Y-m-d H:i:s');
        $duePosts = $this->db->table('social_posts')
            ->where('status', 'scheduled')
            ->where('scheduled_at <=', $now)
            ->get()
            ->getResultArray();

        $enqueued = 0;
        foreach ($duePosts as $post) {
            $postId = (int) $post['id'];

            // Check if job already exists in queue
            $existingJob = $this->db->table('social_scheduled_jobs')
                ->where('post_id', $postId)
                ->whereIn('status', ['queued', 'processing', 'completed'])
                ->get()
                ->getRowArray();

            if (!$existingJob) {
                $this->db->table('social_scheduled_jobs')->insert([
                    'post_id'         => $postId,
                    'platform'        => $post['primary_platform'] === 'both' ? 'linkedin' : $post['primary_platform'],
                    'scheduled_at'    => $post['scheduled_at'],
                    'status'          => 'queued',
                    'attempts'        => 0,
                    'max_attempts'    => self::MAX_ATTEMPTS,
                    'next_attempt_at' => $post['scheduled_at'],
                    'created_at'      => $now,
                ]);
                $enqueued++;
            }
        }

        return $enqueued;
    }

    /**
     * Atomically claim due jobs for the current worker.
     */
    private function claimDueJobs(string $workerId, int $limit): array
    {
        $now = date('Y-m-d H:i:s');

        // 1. Select candidate IDs first (compatible with all MariaDB/MySQL versions)
        $candidates = $this->db->table('social_scheduled_jobs')
            ->select('id')
            ->where('status', 'queued')
            ->where('scheduled_at <=', $now)
            ->groupStart()
                ->where('next_attempt_at IS NULL')
                ->orWhere('next_attempt_at <=', $now)
            ->groupEnd()
            ->orderBy('scheduled_at', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $ids = array_map('intval', array_column($candidates, 'id'));
        if (empty($ids)) {
            return [];
        }

        // 2. Claim atomically
        $this->db->table('social_scheduled_jobs')
            ->whereIn('id', $ids)
            ->where('status', 'queued')
            ->update([
                'status'    => 'processing',
                'locked_at' => date('Y-m-d H:i:s'),
                'locked_by' => $workerId,
            ]);

        return $this->db->table('social_scheduled_jobs')
            ->whereIn('id', $ids)
            ->where('locked_by', $workerId)
            ->where('status', 'processing')
            ->get()
            ->getResultArray();
    }

    /**
     * Handle job retry or permanent failure with exponential backoff.
     */
    private function handleJobFailure(array $job, array $errorDetails): void
    {
        $jobId = (int) $job['id'];
        $postId = (int) $job['post_id'];
        $attempts = (int) ($job['attempts'] ?? 0) + 1;
        $maxAttempts = (int) ($job['max_attempts'] ?? self::MAX_ATTEMPTS);
        $errString = is_string($errorDetails['error'] ?? null) ? $errorDetails['error'] : json_encode($errorDetails);

        if ($attempts < $maxAttempts) {
            // Exponential backoff: 2^attempts minutes (2 min, 4 min, 8 min)
            $backoffSeconds = pow(2, $attempts) * 60;
            $nextAttemptAt = date('Y-m-d H:i:s', time() + $backoffSeconds);

            $this->db->table('social_scheduled_jobs')->where('id', $jobId)->update([
                'status'          => 'queued',
                'attempts'        => $attempts,
                'next_attempt_at' => $nextAttemptAt,
                'locked_at'       => null,
                'locked_by'       => null,
                'last_error'      => "Attempt {$attempts} failed: {$errString}. Retrying at {$nextAttemptAt}.",
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Permanent failure after max attempts
            $this->db->table('social_scheduled_jobs')->where('id', $jobId)->update([
                'status'     => 'failed',
                'attempts'   => $attempts,
                'locked_at'  => null,
                'locked_by'  => null,
                'last_error' => "Exceeded max attempts ({$maxAttempts}): {$errString}",
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('social_posts')->where('id', $postId)->update([
                'status'     => 'failed',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Unlock jobs whose locks are older than LOCK_TIMEOUT_MINUTES.
     */
    public function recoverStaleLocks(): int
    {
        $staleThreshold = date('Y-m-d H:i:s', time() - (self::LOCK_TIMEOUT_MINUTES * 60));

        $this->db->query("
            UPDATE social_scheduled_jobs
            SET status = 'queued',
                locked_at = NULL,
                locked_by = NULL
            WHERE status = 'processing'
              AND locked_at < ?
        ", [$staleThreshold]);

        return $this->db->affectedRows();
    }
}
