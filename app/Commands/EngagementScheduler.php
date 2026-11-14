<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\AnnouncementService;
use App\Services\JobAlertService;
use App\Services\OutboxService;

class EngagementScheduler extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'engagement:run';
    protected $description = 'Process TalentHub Engagement Centre lifecycle transitions, email outbox, and due job alerts';

    public function run(array $params)
    {
        CLI::write('=== Starting TalentHub Engagement Scheduler ===', 'green');

        // File-based lock to prevent overlapping executions on shared hosting cron
        $lockFile = WRITEPATH . 'engagement_scheduler.lock';
        $fp = fopen($lockFile, 'c+');

        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            CLI::write('[NOTICE] Another instance of engagement:run is currently active. Exiting cleanly.', 'yellow');
            if ($fp) fclose($fp);
            return;
        }

        $rec   = new \App\Services\JobRunRecorder();
        $runId = $rec->start('engagement:run');
        try {
            // 1. Lifecycle transitions for announcements & popups
            CLI::write('1. Checking announcement and popup lifecycles...', 'cyan');
            $announcementService = new AnnouncementService();
            $transitions = $announcementService->runLifecycleTransitions();
            CLI::write("   Promoted: {$transitions['promoted']}, Expired: {$transitions['expired']}", 'white');

            // 2. Process due job alerts
            CLI::write('2. Processing due job alerts...', 'cyan');
            $jobAlertService = new JobAlertService();
            $alertsSent = $jobAlertService->processDueAlerts('daily');
            CLI::write("   Job alerts dispatched: {$alertsSent}", 'white');

            // 3. Process email outbox
            CLI::write('3. Processing email outbox queue...', 'cyan');
            $outboxService = new OutboxService();
            $outboxResults = $outboxService->processQueue(25);
            CLI::write("   Outbox: {$outboxResults['sent']} sent, {$outboxResults['failed']} failed, {$outboxResults['total']} claimed.", 'white');

            // 4. Purge expired verification tokens older than 72 hours
            CLI::write('4. Cleaning expired unverified tokens...', 'cyan');
            $db = \Config\Database::connect();
            $db->query("
                UPDATE newsletter_subscribers
                SET verification_token_hash = NULL
                WHERE status = 'unverified'
                  AND verification_expires_at < DATE_SUB(NOW(), INTERVAL 72 HOUR)
            ");
            CLI::write('   Cleaned expired tokens.', 'white');

            // 5. Monitor overdue enquiry & application follow-up datelines
            CLI::write('5. Monitoring follow-up datelines...', 'cyan');
            $automationService = new \App\Services\EnquiryAutomationService();
            $overdue = $automationService->getOverdueFollowUps();
            $appsCount = count($overdue['applications']);
            $empCount  = count($overdue['employers']);
            $cntCount  = count($overdue['contacts']);
            CLI::write("   Follow-ups pending/overdue: {$overdue['total']} ({$appsCount} applications, {$empCount} employer mandates, {$cntCount} contact inquiries)", 'white');

            $rec->finish($runId, true, (int) ($overdue['total'] ?? 0));
            CLI::write('=== TalentHub Engagement Scheduler Completed Successfully ===', 'green');
        } catch (\Throwable $e) {
            $rec->finish($runId, false, 0, get_class($e) . ': ' . $e->getMessage());
            throw $e;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}

