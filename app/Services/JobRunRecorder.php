<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Records real executions of CLI/cron jobs in `scheduled_job_runs`.
 *
 * The heartbeat is written by the command itself, so "last successful run" means the code
 * actually ran to completion — not merely that a cron line is configured. Recording failures
 * never break the job being recorded.
 */
class JobRunRecorder
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Returns the run id, or 0 if the heartbeat could not be written. */
    public function start(string $job): int
    {
        try {
            $this->db->table('scheduled_job_runs')->insert([
                'job_name'   => substr($job, 0, 100),
                'status'     => 'RUNNING',
                'started_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $this->db->insertID();
        } catch (\Throwable $e) {
            log_message('error', 'JobRunRecorder::start failed: ' . get_class($e));
            return 0;
        }
    }

    public function finish(int $runId, bool $success, int $items = 0, ?string $error = null): void
    {
        if ($runId <= 0) {
            return;
        }
        try {
            $row   = $this->db->table('scheduled_job_runs')->select('started_at')->where('id', $runId)->get()->getRowArray();
            $ms    = $row ? max(0, (int) round((microtime(true) - strtotime($row['started_at'])) * 1000)) : 0;
            $this->db->table('scheduled_job_runs')->where('id', $runId)->update([
                'status'          => $success ? 'SUCCESS' : 'FAILED',
                'items_processed' => $items,
                'error_details'   => $error === null ? null : self::sanitize($error),
                'duration_ms'     => $ms,
                'completed_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'JobRunRecorder::finish failed: ' . get_class($e));
        }
    }

    /** Run a callable as a recorded job. The callable returns the number of items processed. */
    public function run(string $job, callable $fn): mixed
    {
        $id = $this->start($job);
        try {
            $result = $fn();
            $this->finish($id, true, is_int($result) ? $result : 0);
            return $result;
        } catch (\Throwable $e) {
            $this->finish($id, false, 0, get_class($e) . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Strip anything that looks like a credential and cap the size. Stack traces are never stored.
     */
    public static function sanitize(?string $text, int $max = 500): string
    {
        $text = (string) $text;
        $text = preg_replace('/(Bearer\s+)[A-Za-z0-9\-._~+\/=]+/i', '$1[redacted]', $text);
        $text = preg_replace('/((?:access|refresh|id)?_?token|secret|password|passwd|pwd|api[_-]?key|authorization|client_secret)(["\']?\s*[:=]\s*["\']?)[^\s"\'&,;}]+/i', '$1$2[redacted]', $text);
        $text = preg_replace('/\b[A-Za-z0-9_\-]{32,}\b/', '[redacted]', $text);
        $text = preg_replace('/[A-Za-z]:\\\\[^\s:]+|\/(?:home|var|usr)\/[^\s:]+/', '[path]', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return mb_substr(trim($text), 0, $max);
    }
}
