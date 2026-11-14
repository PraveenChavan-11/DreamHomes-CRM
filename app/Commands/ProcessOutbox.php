<?php

namespace App\Commands;

use App\Services\OutboxService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessOutbox extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'outbox:process';
    protected $description = 'Process pending emails from the TalentHub email outbox queue';

    public function run(array $params)
    {
        CLI::write('Starting TalentHub email outbox dispatch...', 'green');
        $batchSize = (int) ($params[0] ?? 20);

        $rec   = new \App\Services\JobRunRecorder();
        $runId = $rec->start('outbox:process');
        try {
            $outbox  = new OutboxService();
            $results = $outbox->processQueue($batchSize);
        } catch (\Throwable $e) {
            $rec->finish($runId, false, 0, get_class($e) . ': ' . $e->getMessage());
            throw $e;
        }
        $rec->finish($runId, true, (int) ($results['total'] ?? 0));

        CLI::write("Dispatched: {$results['sent']} sent, {$results['failed']} failed, {$results['total']} processed.", 'yellow');
    }
}

