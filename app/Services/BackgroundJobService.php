<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Unified, read-mostly view over TalentHub's two real job queues:
 *   - social : `social_scheduled_jobs`  (LinkedIn/Instagram publishing)
 *   - email  : `email_outbox`
 *
 * Retry/cancel are single atomic conditional UPDATEs (`... WHERE id=? AND status=?`), so two admins clicking
 * at once — or a worker claiming the row concurrently — can never double-retry or cancel a running job.
 * Error text is always passed through JobRunRecorder::sanitize(); payloads and bodies are never returned.
 */
class BackgroundJobService
{
    public const QUEUES = ['social', 'email'];
    public const STATUSES = ['pending', 'processing', 'completed', 'failed', 'cancelled'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** @return array{rows:array,total:int,page:int,per_page:int} */
    public function list(?string $queue, ?string $status, int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, $page);
        $perPage = min(100, max(5, $perPage));
        $parts   = [];
        if (($queue === null || $queue === 'social') && $this->db->tableExists('social_scheduled_jobs')) {
            $parts[] = "SELECT 'social' AS queue, j.id, CONCAT('Publish to ', j.platform) AS type,
                CASE j.status WHEN 'queued' THEN 'pending' ELSE j.status END AS status,
                j.created_at, j.locked_at AS started_at,
                CASE WHEN j.status = 'completed' THEN j.updated_at END AS completed_at,
                j.attempts, j.max_attempts, j.last_error AS error, CONCAT('Social post #', j.post_id) AS record_label,
                CONCAT('admin/social/posts/', j.post_id) AS record_path,
                (SELECT TIMESTAMPDIFF(SECOND, a.started_at, a.completed_at) FROM social_publish_attempts a WHERE a.job_id = j.id AND a.completed_at IS NOT NULL ORDER BY a.id DESC LIMIT 1) AS duration_s
                FROM social_scheduled_jobs j";
        }
        if (($queue === null || $queue === 'email') && $this->db->tableExists('email_outbox')) {
            $parts[] = "SELECT 'email' AS queue, o.id, CONCAT('Email: ', COALESCE(o.template_code, 'message')) AS type,
                CASE o.status WHEN 'PENDING' THEN 'pending' WHEN 'SENT' THEN 'completed' ELSE 'failed' END AS status,
                o.created_at, NULL AS started_at, o.sent_at AS completed_at, o.attempts, 4 AS max_attempts,
                o.last_error AS error, NULL AS record_label, NULL AS record_path, NULL AS duration_s
                FROM email_outbox o";
        }
        if (!$parts) {
            return ['rows' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
        $where = '';
        $binds = [];
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $where   = ' WHERE u.status = ?';
            $binds[] = $status;
        }
        $union = '(' . implode(' UNION ALL ', $parts) . ') u';
        $total = (int) ($this->db->query("SELECT COUNT(*) c FROM $union$where", $binds)->getRowArray()['c'] ?? 0);
        $rows  = $this->db->query("SELECT u.* FROM $union$where ORDER BY u.created_at DESC, u.id DESC LIMIT " . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage), $binds)->getResultArray();
        foreach ($rows as &$r) {
            $r = $this->present($r);
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function find(string $queue, int $id): ?array
    {
        if ($queue === 'social') {
            $row = $this->db->query("SELECT 'social' AS queue, j.id, CONCAT('Publish to ', j.platform) AS type,
                CASE j.status WHEN 'queued' THEN 'pending' ELSE j.status END AS status, j.created_at, j.locked_at AS started_at,
                CASE WHEN j.status='completed' THEN j.updated_at END AS completed_at, j.attempts, j.max_attempts, j.last_error AS error,
                CONCAT('Social post #', j.post_id) AS record_label, CONCAT('admin/social/posts/', j.post_id) AS record_path,
                (SELECT TIMESTAMPDIFF(SECOND, a.started_at, a.completed_at) FROM social_publish_attempts a WHERE a.job_id=j.id AND a.completed_at IS NOT NULL ORDER BY a.id DESC LIMIT 1) AS duration_s,
                j.next_attempt_at, j.locked_by
                FROM social_scheduled_jobs j WHERE j.id = ?", [$id])->getRowArray();
        } elseif ($queue === 'email') {
            $row = $this->db->query("SELECT 'email' AS queue, o.id, CONCAT('Email: ', COALESCE(o.template_code,'message')) AS type,
                CASE o.status WHEN 'PENDING' THEN 'pending' WHEN 'SENT' THEN 'completed' ELSE 'failed' END AS status,
                o.created_at, NULL AS started_at, o.sent_at AS completed_at, o.attempts, 4 AS max_attempts, o.last_error AS error,
                NULL AS record_label, NULL AS record_path, NULL AS duration_s, o.scheduled_at AS next_attempt_at, NULL AS locked_by
                FROM email_outbox o WHERE o.id = ?", [$id])->getRowArray();
        } else {
            return null;
        }
        return $row ? $this->present($row) : null;
    }

    /** Recent publish attempts for a social job — sanitised summaries only, no request/response payloads. */
    public function attempts(int $jobId): array
    {
        if (!$this->db->tableExists('social_publish_attempts')) {
            return [];
        }
        $rows = $this->db->table('social_publish_attempts')
            ->select('attempt_number, started_at, completed_at, status, http_status, error_code, error_message')
            ->where('job_id', $jobId)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['error_message'] = $r['error_message'] ? JobRunRecorder::sanitize($r['error_message'], 300) : null;
        }
        return $rows;
    }

    /** Failed → queued. Returns true only if THIS call changed the row. */
    public function retry(string $queue, int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($queue === 'social') {
            $this->db->transStart();
            $this->db->table('social_scheduled_jobs')->where('id', $id)->where('status', 'failed')->update([
                'status' => 'queued', 'attempts' => 0, 'next_attempt_at' => $now, 'locked_at' => null, 'locked_by' => null, 'updated_at' => $now,
            ]);
            $changed = $this->db->affectedRows() === 1;
            if ($changed) {
                $postId = (int) ($this->db->table('social_scheduled_jobs')->select('post_id')->where('id', $id)->get()->getRowArray()['post_id'] ?? 0);
                // Put the post back into the state the scheduler expects, only if it was marked failed.
                $this->db->table('social_posts')->where('id', $postId)->where('status', 'failed')->update(['status' => 'scheduled', 'updated_at' => $now]);
            }
            $this->db->transComplete();
            return $changed && $this->db->transStatus();
        }
        if ($queue === 'email') {
            $this->db->table('email_outbox')->where('id', $id)->where('status', 'FAILED')->update(['status' => 'PENDING', 'attempts' => 0]);
            return $this->db->affectedRows() === 1;
        }
        return false;
    }

    /** Queued → cancelled (social only). A job a worker has already claimed ('processing') cannot be cancelled. */
    public function cancel(string $queue, int $id): bool
    {
        if ($queue !== 'social') {
            return false; // email_outbox has no cancelled state; cancelling would require deleting data.
        }
        $this->db->table('social_scheduled_jobs')->where('id', $id)->where('status', 'queued')->update([
            'status' => 'cancelled', 'locked_at' => null, 'locked_by' => null, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affectedRows() === 1;
    }

    public static function canRetry(array $job): bool
    {
        return $job['status'] === 'failed';
    }

    public static function canCancel(array $job): bool
    {
        return $job['queue'] === 'social' && $job['status'] === 'pending';
    }

    private function present(array $r): array
    {
        $r['error_summary'] = $r['error'] ? JobRunRecorder::sanitize($r['error'], 200) : null;
        unset($r['error']);
        $r['duration_label'] = $r['duration_s'] !== null ? ((int) $r['duration_s'] . ' s') : '—';
        return $r;
    }
}
