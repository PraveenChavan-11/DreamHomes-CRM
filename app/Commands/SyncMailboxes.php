<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\HostingerMailService;
use App\Services\MailboxService;
use App\Models\MailActivityLogModel;

class SyncMailboxes extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'mail:sync';
    protected $description = 'Synchronize Hostinger Email mailboxes, storage quotas, and recent messages via Hostinger Mail API';

    public function run(array $params)
    {
        CLI::write('Starting TalentHub Hostinger Mail synchronization...', 'yellow');

        $apiService = new HostingerMailService();
        $boxService = new MailboxService($apiService);
        $activityLog = new MailActivityLogModel();

        $rec   = new \App\Services\JobRunRecorder();
        $runId = $rec->start('mail:sync');
        try {
            $result = $boxService->syncMailboxes();
        } catch (\Throwable $e) {
            $rec->finish($runId, false, 0, get_class($e) . ': ' . $e->getMessage());
            throw $e;
        }
        $rec->finish($runId, !empty($result['success']), (int) ($result['synced_count'] ?? 0), empty($result['success']) ? (string) ($result['message'] ?? 'Sync failed') : null);
        if ($result['success']) {
            CLI::write('[PASS] ' . $result['message'], 'green');
            $activityLog->record('CRON_SYNC', null, null, ['status' => 'SUCCESS', 'count' => $result['synced_count'] ?? 0]);
        } else {
            CLI::error('[FAIL] ' . $result['message']);
            $activityLog->record('CRON_SYNC', null, null, ['status' => 'FAILED', 'error' => $result['message']]);
        }

        CLI::write('Mail synchronization complete.', 'green');
    }
}

