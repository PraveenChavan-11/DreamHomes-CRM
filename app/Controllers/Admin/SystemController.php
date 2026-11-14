<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Services\BackgroundJobService;
use App\Services\JobRunRecorder;
use App\Services\SystemHealthService;

/**
 * System Health, Background Jobs and Scheduler monitoring.
 * system.view to look; system.manage to retry/cancel jobs. Super admin passes both.
 * Health results are cached for 60s so the page cannot be used to hammer the database.
 */
class SystemController extends AdminBaseController
{
    private const CACHE_KEY = 'system_health_v1';
    private const CACHE_TTL = 60;

    private function audit(string $action, string $entity, string $id, ?array $data = null): void
    {
        (new AuditLogModel())->log($action, $entity, $id, null, $data, (int) ($this->adminUser['id'] ?? 0));
    }

    private function canManage(): bool
    {
        return $this->isSuperAdmin() || in_array('system.manage', $this->adminUser['permissions'] ?? [], true);
    }

    public function health()
    {
        if ($r = $this->requirePermission('system.view')) {
            return $r;
        }
        $cache  = cache();
        $report = $cache->get(self::CACHE_KEY);
        if (!is_array($report)) {
            $report = (new SystemHealthService())->run();
            $cache->save(self::CACHE_KEY, $report, self::CACHE_TTL);
        }
        return $this->renderAdminView('admin/system/health', [
            'title'  => 'System Health — TalentHub Admin',
            'report' => $report,
            'ttl'    => self::CACHE_TTL,
        ], 'system_health');
    }

    public function refreshHealth()
    {
        if ($r = $this->requirePermission('system.view')) {
            return $r;
        }
        cache()->delete(self::CACHE_KEY);
        return redirect()->to(base_url('admin/system/health'));
    }

    public function jobs()
    {
        if ($r = $this->requirePermission('system.view')) {
            return $r;
        }
        $queue  = $this->request->getGet('queue');
        $status = $this->request->getGet('status');
        $queue  = in_array($queue, BackgroundJobService::QUEUES, true) ? $queue : null;
        $status = in_array($status, BackgroundJobService::STATUSES, true) ? $status : null;
        $svc    = new BackgroundJobService();
        $page   = max(1, (int) $this->request->getGet('page'));
        return $this->renderAdminView('admin/system/jobs', [
            'title'      => 'Background Jobs — TalentHub Admin',
            'result'     => $svc->list($queue, $status, $page, 25),
            'jobCounts'  => (new SystemHealthService())->jobCounts(),
            'queue'      => $queue,
            'status'     => $status,
            'canManage'  => $this->canManage(),
        ], 'system_jobs');
    }

    public function jobDetail(string $queue, int $id)
    {
        if ($r = $this->requirePermission('system.view')) {
            return $r;
        }
        $svc = new BackgroundJobService();
        $job = $svc->find($queue, $id);
        if (!$job) {
            session()->setFlashdata('error', 'That job was not found.');
            return redirect()->to(base_url('admin/system/jobs'));
        }
        return $this->renderAdminView('admin/system/job_detail', [
            'title'     => 'Job #' . $id . ' — TalentHub Admin',
            'job'       => $job,
            'attempts'  => $queue === 'social' ? $svc->attempts($id) : [],
            'canManage' => $this->canManage(),
        ], 'system_jobs');
    }

    public function retryJob(string $queue, int $id)
    {
        return $this->mutateJob('retry', $queue, $id);
    }

    public function cancelJob(string $queue, int $id)
    {
        return $this->mutateJob('cancel', $queue, $id);
    }

    private function mutateJob(string $action, string $queue, int $id)
    {
        if ($r = $this->requirePermission('system.manage')) {
            return $r;
        }
        $back = base_url('admin/system/jobs/' . rawurlencode($queue) . '/' . $id);
        if (!in_array($queue, BackgroundJobService::QUEUES, true)) {
            session()->setFlashdata('error', 'Unknown queue.');
            return redirect()->to(base_url('admin/system/jobs'));
        }
        $svc = new BackgroundJobService();
        $ok  = $action === 'retry' ? $svc->retry($queue, $id) : $svc->cancel($queue, $id);
        $this->audit('JOB_' . strtoupper($action) . ($ok ? '' : '_REJECTED'), 'background_job', $queue . ':' . $id, ['queue' => $queue]);
        if ($ok) {
            session()->setFlashdata('success', $action === 'retry' ? 'Job re-queued. It will run on the next scheduler pass.' : 'Job cancelled.');
        } else {
            session()->setFlashdata('error', $action === 'retry'
                ? 'This job can no longer be retried — it is not in a failed state (someone may already have retried it).'
                : 'This job cannot be cancelled — only waiting social jobs can be, and it may already be running.');
        }
        return redirect()->to($back);
    }

    public function scheduler()
    {
        if ($r = $this->requirePermission('system.view')) {
            return $r;
        }
        $db     = \Config\Database::connect();
        $recent = $db->tableExists('scheduled_job_runs')
            ? $db->table('scheduled_job_runs')->select('id,job_name,status,items_processed,error_details,duration_ms,started_at,completed_at')
                ->orderBy('started_at', 'DESC')->limit(40)->get()->getResultArray()
            : [];
        foreach ($recent as &$row) {
            $row['error_details'] = $row['error_details'] ? JobRunRecorder::sanitize($row['error_details'], 200) : null;
        }
        return $this->renderAdminView('admin/system/scheduler', [
            'title'  => 'Scheduler & Cron — TalentHub Admin',
            'jobs'   => (new SystemHealthService($db))->schedulerJobs(),
            'recent' => $recent,
        ], 'system_scheduler');
    }
}
