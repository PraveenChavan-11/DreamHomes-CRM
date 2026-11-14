<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Social\SocialSchedulerService;

/**
 * CLI Command: php spark social:publish
 * 
 * Safe database-backed queue runner for scheduled social media publishing on LinkedIn & Instagram.
 * Protected with non-blocking file locking for reliable shared hosting cron execution.
 */
class SocialPublishCommand extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'social:publish';
    protected $description = 'Process TalentHub scheduled social media marketing publishing queue';
    protected $usage       = 'social:publish [options]';
    protected $options     = [
        '--batch' => 'Maximum number of due jobs to process in single run (default 10)',
    ];

    public function run(array $params)
    {
        CLI::write('=== TalentHub Social Media Publishing Queue Runner ===', 'green');

        // File-based concurrency lock for shared hosting cron
        $lockFile = WRITEPATH . 'social_scheduler.lock';
        $fp = fopen($lockFile, 'c+');

        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            CLI::write('[NOTICE] Another instance of social:publish is currently running. Exiting cleanly.', 'yellow');
            if ($fp) {
                fclose($fp);
            }
            return;
        }

        try {
            $batchSize = isset($params['batch']) ? (int) $params['batch'] : 10;
            if ($batchSize <= 0) {
                $batchSize = 10;
            }

            CLI::write("Scanning due jobs (batch limit: {$batchSize})...", 'cyan');

            $rec   = new \App\Services\JobRunRecorder();
            $runId = $rec->start('social:publish');
            try {
                $scheduler = new SocialSchedulerService();
                $results   = $scheduler->processQueue($batchSize);
            } catch (\Throwable $e) {
                $rec->finish($runId, false, 0, get_class($e) . ': ' . $e->getMessage());
                throw $e;
            }
            // Job-level failures are normal queue outcomes; the run itself succeeded.
            $rec->finish($runId, true, (int) $results['processed']);

            CLI::write("Worker ID:  {$results['worker_id']}", 'white');
            CLI::write("Processed:  {$results['processed']} job(s)", 'white');
            CLI::write("Succeeded:  {$results['succeeded']} job(s)", 'green');
            if ($results['failed'] > 0) {
                CLI::write("Failed:     {$results['failed']} job(s)", 'red');
            }
            if ($results['stale_freed'] > 0) {
                CLI::write("Stale Freed: {$results['stale_freed']} abandoned lock(s)", 'yellow');
            }
            CLI::write("Duration:   {$results['duration_ms']} ms", 'cyan');

            CLI::write('=== Execution Finished Successfully ===', 'green');
        } catch (\Throwable $e) {
            CLI::error('CRITICAL SCHEDULER ERROR: ' . $e->getMessage());
            log_message('critical', 'social:publish failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        } finally {
            if ($fp) {
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
    }
}

