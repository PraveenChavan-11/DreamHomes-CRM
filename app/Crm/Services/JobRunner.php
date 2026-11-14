<?php

namespace Crm\Services;

/** Execution log + overlap guard for cron jobs (shared-hosting friendly: no resident worker). */
final class JobRunner
{
    /** @param callable():int $fn returns items processed @return array{status:string,items:int,error:?string} */
    public static function run(string $name, callable $fn): array
    {
        $db = Crm::db();
        // overlap guard: refuse if the same job is RUNNING and started < 10 minutes ago
        $running = $db->table('scheduled_job_runs')->where(['job_name' => $name, 'status' => 'RUNNING'])->where('started_at >', date('Y-m-d H:i:s', time() - 600))->countAllResults();
        if ($running > 0) {
            return ['status' => 'SKIPPED', 'items' => 0, 'error' => 'Previous run still in progress'];
        }
        $t0 = microtime(true);
        $db->table('scheduled_job_runs')->insert(['job_name' => $name, 'status' => 'RUNNING', 'started_at' => date('Y-m-d H:i:s')]);
        $id = (int) $db->insertID();
        Crm::setActor(null, 'cron');
        try {
            $items = (int) $fn();
            $db->table('scheduled_job_runs')->where('id', $id)->update(['status' => 'SUCCESS', 'items_processed' => $items, 'duration_ms' => (int) ((microtime(true) - $t0) * 1000), 'completed_at' => date('Y-m-d H:i:s')]);

            return ['status' => 'SUCCESS', 'items' => $items, 'error' => null];
        } catch (\Throwable $e) {
            log_message('error', "[crm-job:{$name}] " . $e->getMessage());
            $db->table('scheduled_job_runs')->where('id', $id)->update(['status' => 'FAILED', 'error_details' => mb_substr($e->getMessage(), 0, 1000), 'duration_ms' => (int) ((microtime(true) - $t0) * 1000), 'completed_at' => date('Y-m-d H:i:s')]);

            return ['status' => 'FAILED', 'items' => 0, 'error' => $e->getMessage()];
        } finally {
            Crm::resetContext();
        }
    }
}
