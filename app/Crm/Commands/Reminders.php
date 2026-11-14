<?php

namespace Crm\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Crm\Services\AuthService;
use Crm\Services\Crm;
use Crm\Services\DemoSeeder;
use Crm\Services\DemoService;
use Crm\Services\ActivityService;
use Crm\Services\EmailService;
use Crm\Services\IntegrityChecker;
use Crm\Services\JobRunner;
use Crm\Services\ReferenceData;
use Crm\Services\UserService;

final class Reminders extends BaseCommand
{
    protected $group = 'CRM';
    protected $name = 'crm:reminders';
    protected $description = 'Cron (every 5-15 min): follow-up reminders/overdue/escalation, site-visit reminders, email retry. Idempotent, logged to scheduled_job_runs.';

    public function run(array $params)
    {
        $r1 = JobRunner::run('crm:followups', static function () { $r = ActivityService::runReminders(); return $r['reminded'] + $r['missed'] + $r['escalated']; });
        $r2 = JobRunner::run('crm:visit-reminders', static fn () => ActivityService::runVisitReminders());
        $r3 = JobRunner::run('crm:email-retry', static fn () => EmailService::retryDue());
        foreach (['followups' => $r1, 'visit-reminders' => $r2, 'email-retry' => $r3] as $n => $r) {
            CLI::write(str_pad($n, 18) . $r['status'] . ' items=' . $r['items'] . ($r['error'] ? " ({$r['error']})" : ''));
        }
    }
}
