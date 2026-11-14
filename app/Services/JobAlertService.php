<?php

namespace App\Services;

use App\Models\JobAlertSubscriptionModel;
use App\Models\JobModel;
use App\Services\OutboxService;

class JobAlertService
{
    private JobAlertSubscriptionModel $alertModel;
    private JobModel $jobModel;
    private OutboxService $outboxService;

    public function __construct()
    {
        $this->alertModel    = new JobAlertSubscriptionModel();
        $this->jobModel      = new JobModel();
        $this->outboxService = new OutboxService();
    }

    /**
     * Subscribe candidate to job alerts
     */
    public function subscribe(
        string $email,
        ?string $jobCategory = null,
        ?string $preferredLocation = null,
        ?string $experienceLevel = null,
        ?string $employmentType = null,
        string $frequency = 'weekly'
    ): array {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Please provide a valid email address.',
            ];
        }

        $validFreq = ['immediate', 'daily', 'weekly'];
        if (!in_array($frequency, $validFreq, true)) {
            $frequency = 'weekly';
        }

        $existing = $this->alertModel->findExisting($email, $jobCategory);
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        if ($existing) {
            $this->alertModel->update($existing['id'], [
                'preferred_location' => $preferredLocation ?: $existing['preferred_location'],
                'experience_level'   => $experienceLevel ?: $existing['experience_level'],
                'employment_type'    => $employmentType ?: $existing['employment_type'],
                'frequency'          => $frequency,
                'status'             => 'active',
                'token_hash'         => $tokenHash,
            ]);
            $subscriptionId = (int) $existing['id'];
        } else {
            $subscriptionId = (int) $this->alertModel->insert([
                'email'              => $email,
                'job_category'       => $jobCategory,
                'preferred_location' => $preferredLocation,
                'experience_level'   => $experienceLevel,
                'employment_type'    => $employmentType,
                'frequency'          => $frequency,
                'status'             => 'active',
                'token_hash'         => $tokenHash,
            ]);
        }

        // Send confirmation with matching jobs if any exist
        $matches = $this->findMatchingJobs($jobCategory, $preferredLocation, $experienceLevel, $employmentType, 3);
        $unsubUrl = base_url("job-alerts/unsubscribe?token={$rawToken}");

        if (!empty($matches)) {
            $jobsHtml = '<ul style="padding-left:18px;margin:12px 0;">';
            $jobsText = '';
            foreach ($matches as $job) {
                $jobUrl = base_url('jobs/' . ($job['slug'] ?? $job['id']));
                $jobsHtml .= "<li style='margin-bottom:8px;'><a href='{$jobUrl}' style='color:#1D6A34;font-weight:bold;text-decoration:none;'>{$job['title']}</a> — <span style='color:#64748b;'>{$job['location']} ({$job['job_type']})</span></li>";
                $jobsText .= "• {$job['title']} ({$job['location']}): {$jobUrl}\n";
            }
            $jobsHtml .= '</ul>';

            $this->outboxService->queueTemplate(
                'job_alert_notification',
                $email,
                'Job Seeker',
                [
                    'jobs_list_html'  => $jobsHtml,
                    'jobs_list_text'  => $jobsText,
                    'jobs_url'        => base_url('jobs'),
                    'unsubscribe_url' => $unsubUrl,
                ],
                'job_alert_init_' . $subscriptionId
            );
        }

        return [
            'success'         => true,
            'message'         => 'You have successfully subscribed to TalentHub job alerts.',
            'matching_jobs'   => count($matches),
            'subscription_id' => $subscriptionId,
            'token'           => $rawToken,
        ];
    }

    /**
     * Unsubscribe from job alerts
     */
    public function unsubscribe(string $rawToken): array
    {
        $hash = hash('sha256', trim($rawToken));
        $sub = $this->alertModel->findByTokenHash($hash);

        if (!$sub) {
            return [
                'success' => false,
                'message' => 'Invalid or expired job alert unsubscribe token.',
            ];
        }

        $this->alertModel->update($sub['id'], [
            'status' => 'unsubscribed',
        ]);

        return [
            'success' => true,
            'message' => 'You have been unsubscribed from job alert notifications.',
        ];
    }

    /**
     * Find matching published jobs in database
     */
    public function findMatchingJobs(
        ?string $category = null,
        ?string $location = null,
        ?string $experience = null,
        ?string $jobType = null,
        int $limit = 5
    ): array {
        $builder = $this->jobModel->where('status', 'published');

        if (!empty($category)) {
            $builder->groupStart()
                    ->like('title', $category)
                    ->orLike('skills', $category)
                    ->orLike('description', $category)
                    ->groupEnd();
        }

        if (!empty($location)) {
            $builder->like('location', $location);
        }

        if (!empty($jobType)) {
            $builder->like('job_type', $jobType);
        }

        return $builder->orderBy('id', 'DESC')->findAll($limit);
    }

    /**
     * Process due job alerts (invoked by scheduler)
     */
    public function processDueAlerts(string $frequency = 'daily'): int
    {
        $due = $this->alertModel->getDueAlerts($frequency);
        $dispatched = 0;

        foreach ($due as $alert) {
            $matches = $this->findMatchingJobs(
                $alert['job_category'],
                $alert['preferred_location'],
                $alert['experience_level'],
                $alert['employment_type'],
                5
            );

            // Only send if matching jobs exist
            if (!empty($matches)) {
                $rawToken = bin2hex(random_bytes(16));
                $unsubUrl = base_url("job-alerts/unsubscribe?token={$rawToken}");
                $this->alertModel->update($alert['id'], [
                    'token_hash'         => hash('sha256', $rawToken),
                    'last_alert_sent_at' => date('Y-m-d H:i:s'),
                ]);

                $jobsHtml = '<ul style="padding-left:18px;margin:12px 0;">';
                $jobsText = '';
                foreach ($matches as $job) {
                    $jobUrl = base_url('jobs/' . ($job['slug'] ?? $job['id']));
                    $jobsHtml .= "<li style='margin-bottom:8px;'><a href='{$jobUrl}' style='color:#1D6A34;font-weight:bold;text-decoration:none;'>{$job['title']}</a> — <span style='color:#64748b;'>{$job['location']}</span></li>";
                    $jobsText .= "• {$job['title']} ({$job['location']}): {$jobUrl}\n";
                }
                $jobsHtml .= '</ul>';

                $this->outboxService->queueTemplate(
                    'job_alert_notification',
                    $alert['email'],
                    'Candidate',
                    [
                        'jobs_list_html'  => $jobsHtml,
                        'jobs_list_text'  => $jobsText,
                        'jobs_url'        => base_url('jobs'),
                        'unsubscribe_url' => $unsubUrl,
                    ],
                    'job_alert_' . $alert['id'] . '_' . date('Ymd')
                );

                $dispatched++;
            }
        }

        return $dispatched;
    }
}
