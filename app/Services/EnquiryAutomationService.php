<?php

namespace App\Services;

use App\Models\ApplicationModel;
use App\Models\AuditLogModel;
use App\Models\CandidateModel;
use App\Models\ContactEnquiryModel;
use App\Models\EmployerEnquiryModel;
use App\Models\JobModel;
use App\Services\OutboxService;
use Config\Database;

/**
 * TalentHub Enquiry & Candidate Application Automation Service
 * Handles:
 * 1. Automatic polite emails to candidates on status changes (Rejection, Shortlist, Interview, Closure)
 * 2. Automatic emails to employers on mandate closure / discussion updates
 * 3. Follow-up deadline tracking, dateline alerts, and reopening workflows
 * 4. Audit history and status progression logging
 */
class EnquiryAutomationService
{
    private ApplicationModel $applicationModel;
    private EmployerEnquiryModel $employerEnquiryModel;
    private ContactEnquiryModel $contactEnquiryModel;
    private JobModel $jobModel;
    private CandidateModel $candidateModel;
    private AuditLogModel $auditModel;
    private OutboxService $outboxService;

    public function __construct()
    {
        $this->applicationModel     = new ApplicationModel();
        $this->employerEnquiryModel = new EmployerEnquiryModel();
        $this->contactEnquiryModel  = new ContactEnquiryModel();
        $this->jobModel             = new JobModel();
        $this->candidateModel       = new CandidateModel();
        $this->auditModel           = new AuditLogModel();
        $this->outboxService        = new OutboxService();
    }

    /**
     * Process Candidate Application Status Change with Automated Email & Follow-up Dateline
     */
    public function updateApplicationStatus(
        int $applicationId,
        string $newStatus,
        ?string $comment = null,
        ?string $followUpDate = null,
        ?int $changedByUserId = null
    ): array {
        $app = $this->applicationModel->find($applicationId);
        if (!$app) {
            return ['success' => false, 'message' => 'Candidate application record not found.'];
        }

        $validStatuses = [
            'APPLIED',
            'SCREENING',
            'SHORTLISTED',
            'INTERVIEW',
            'OFFER',
            'HIRED',
            'REJECTED',
            'WITHDRAWN',
        ];

        $targetStatus = strtoupper(trim($newStatus));
        if (!in_array($targetStatus, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid candidate pipeline status provided.'];
        }

        $oldStatus = strtoupper($app['status'] ?? 'APPLIED');
        $now = date('Y-m-d H:i:s');

        // Check if application is being reopened from a terminal state
        $terminalStates = ['REJECTED', 'WITHDRAWN', 'HIRED'];
        $isReopening = in_array($oldStatus, $terminalStates, true) && !in_array($targetStatus, $terminalStates, true);

        // Prepare updates
        $updates = [
            'status'            => $targetStatus,
            'updated_at'        => $now,
            'last_contacted_at' => $now,
        ];

        if (!empty($comment)) {
            $updates['recruiter_notes'] = trim($comment);
            $updates['follow_up_notes'] = trim($comment);
        }

        if (!empty($followUpDate)) {
            $updates['follow_up_date'] = date('Y-m-d H:i:s', strtotime($followUpDate));
        }

        if ($isReopening) {
            $updates['reopened_at'] = $now;
            $updates['closed_at']   = null;
        } elseif (in_array($targetStatus, ['REJECTED', 'WITHDRAWN'], true)) {
            $updates['closed_at'] = $now;
        }

        $this->applicationModel->update($applicationId, $updates);

        // Fetch candidate & job details for automated email communication
        $candidate = !empty($app['candidate_id']) ? $this->candidateModel->find($app['candidate_id']) : null;
        $job = !empty($app['job_id']) ? $this->jobModel->find($app['job_id']) : null;

        $candidateName = $candidate['full_name'] ?? 'Candidate';
        $candidateEmail = $candidate['email'] ?? null;
        $jobTitle = $job['title'] ?? 'Corporate Position';
        $employerName = $job['employer_name'] ?? null;

        // Automated Email Dispatch based on status transition
        $emailDispatched = false;
        $notificationType = null;

        if (!empty($candidateEmail) && filter_var($candidateEmail, FILTER_VALIDATE_EMAIL)) {
            $companyClause = !empty($employerName) ? " at {$employerName}" : '';
            $notesBlock = !empty($comment)
                ? "<div style=\"background: #f8fafc; border-left: 4px solid #1D6A34; padding: 12px 16px; margin: 18px 0; border-radius: 0 8px 8px 0;\"><p style=\"font-size: 13px; color: #334155; margin: 0;\"><strong>Consultant Update:</strong> " . esc($comment) . "</p></div>"
                : '';

            $variables = [
                'candidate_name'  => $candidateName,
                'job_title'       => $jobTitle,
                'company_name'    => $employerName ?: 'Confidential Corporate Partner',
                'company_clause'  => $companyClause,
                'recruiter_notes' => $comment ?: '',
                'notes_block'     => $notesBlock,
                'jobs_url'        => base_url('jobs'),
            ];

            // 1. REJECTED: Send polite rejection & talent pool retention email
            if ($targetStatus === 'REJECTED') {
                $notificationType = 'app_candidate_rejected';
                $idempotencyKey   = "app_rejected_{$applicationId}_" . date('Ymd');

                $this->outboxService->queueTemplate(
                    'app_candidate_rejected',
                    $candidateEmail,
                    $candidateName,
                    $variables,
                    $idempotencyKey
                );
                $emailDispatched = true;
            }
            // 2. WITHDRAWN / CLOSED: Send mandate conclusion notice
            elseif ($targetStatus === 'WITHDRAWN') {
                $notificationType = 'app_candidate_closed';
                $idempotencyKey   = "app_closed_{$applicationId}_" . date('Ymd');

                $this->outboxService->queueTemplate(
                    'app_candidate_closed',
                    $candidateEmail,
                    $candidateName,
                    $variables,
                    $idempotencyKey
                );
                $emailDispatched = true;
            }
            // 3. SHORTLISTED: Send profile shortlist notification
            elseif ($targetStatus === 'SHORTLISTED') {
                $notificationType = 'app_candidate_shortlisted';
                $idempotencyKey   = "app_shortlist_{$applicationId}_" . date('Ymd');

                $this->outboxService->queueTemplate(
                    'app_candidate_shortlisted',
                    $candidateEmail,
                    $candidateName,
                    $variables,
                    $idempotencyKey
                );
                $emailDispatched = true;
            }
            // 4. INTERVIEW: Send interview shortlist notification
            elseif ($targetStatus === 'INTERVIEW') {
                $notificationType = 'interview_shortlist';
                $idempotencyKey   = "app_interview_{$applicationId}_" . date('Ymd');

                $this->outboxService->queueTemplate(
                    'interview_shortlist',
                    $candidateEmail,
                    $candidateName,
                    $variables,
                    $idempotencyKey
                );
                $emailDispatched = true;
            }
        }

        // Record Status History
        try {
            $db = Database::connect();
            $db->table('application_status_history')->insert([
                'application_id'     => $applicationId,
                'previous_status'    => $oldStatus,
                'new_status'         => $targetStatus,
                'changed_by_user_id' => $changedByUserId,
                'comment'            => $comment ?: ($isReopening ? 'Application reopened for consideration' : 'Pipeline status updated'),
                'follow_up_date'     => !empty($followUpDate) ? date('Y-m-d H:i:s', strtotime($followUpDate)) : null,
                'email_dispatched'   => $emailDispatched ? 1 : 0,
                'notification_type'  => $notificationType,
                'created_at'         => $now,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Status history write error: ' . $e->getMessage());
        }

        // Audit Log
        $this->auditModel->log('APPLICATION_STATUS_CHANGE', 'application', $applicationId, [
            'status' => $oldStatus,
        ], [
            'status'           => $targetStatus,
            'is_reopened'      => $isReopening,
            'email_dispatched' => $emailDispatched,
            'follow_up_date'   => $followUpDate,
        ]);

        return [
            'success'          => true,
            'message'          => 'Application status updated to ' . ucfirst(strtolower($targetStatus)) . ($emailDispatched ? ' and candidate notification queued automatically.' : '.'),
            'old_status'       => $oldStatus,
            'new_status'       => $targetStatus,
            'is_reopened'      => $isReopening,
            'email_dispatched' => $emailDispatched,
            'follow_up_date'   => $followUpDate,
        ];
    }

    /**
     * Process Employer Hiring Enquiry Status Change with Automated Email & Follow-up Dateline
     */
    public function updateEmployerStatus(
        int $enquiryId,
        string $newStatus,
        ?string $comment = null,
        ?string $followUpDate = null,
        ?int $changedByUserId = null
    ): array {
        $record = $this->employerEnquiryModel->find($enquiryId);
        if (!$record) {
            return ['success' => false, 'message' => 'Employer mandate record not found.'];
        }

        $validStatuses = [
            'NEW',
            'CONTACTED',
            'IN_DISCUSSION',
            'MANDATE_WON',
            'CLOSED_LOST',
        ];

        $targetStatus = strtoupper(trim($newStatus));
        if (!in_array($targetStatus, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid employer mandate status provided.'];
        }

        $oldStatus = strtoupper($record['status'] ?? 'NEW');
        $now = date('Y-m-d H:i:s');
        $isReopening = ($oldStatus === 'CLOSED_LOST' || $oldStatus === 'MANDATE_WON') && ($targetStatus === 'IN_DISCUSSION' || $targetStatus === 'CONTACTED');

        $updates = [
            'status'            => $targetStatus,
            'updated_at'        => $now,
            'last_contacted_at' => $now,
        ];

        if (!empty($comment)) {
            $updates['notes'] = trim($comment);
            $updates['follow_up_notes'] = trim($comment);
        }

        if (!empty($followUpDate)) {
            $updates['follow_up_date'] = date('Y-m-d H:i:s', strtotime($followUpDate));
        }

        if ($isReopening) {
            $updates['reopened_at'] = $now;
            $updates['closed_at']   = null;
        } elseif ($targetStatus === 'CLOSED_LOST') {
            $updates['closed_at'] = $now;
        }

        $this->employerEnquiryModel->update($enquiryId, $updates);

        $emailDispatched = false;
        $recipientEmail = $record['email'] ?? null;
        $contactPerson = $record['contact_person'] ?? ($record['company_name'] ?? 'Corporate Partner');
        $roles = $record['required_roles'] ?? ($record['positions_needed'] ?? 'Corporate Staffing Mandate');

        if (!empty($recipientEmail) && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $notesBlock = !empty($comment)
                ? "<div style=\"background: #f8fafc; border-left: 4px solid #1D6A34; padding: 12px 16px; margin: 18px 0; border-radius: 0 8px 8px 0;\"><p style=\"font-size: 13px; color: #334155; margin: 0;\"><strong>Corporate Desk Summary:</strong> " . esc($comment) . "</p></div>"
                : '';

            $variables = [
                'contact_person'  => $contactPerson,
                'company_name'    => $record['company_name'] ?? 'Company',
                'required_roles'  => $roles,
                'recruiter_notes' => $comment ?: '',
                'notes_block'     => $notesBlock,
            ];

            // If CLOSED_LOST: send mandate closure email automatically
            if ($targetStatus === 'CLOSED_LOST') {
                $this->outboxService->queueTemplate(
                    'employer_mandate_closed',
                    $recipientEmail,
                    $contactPerson,
                    $variables,
                    "emp_closed_{$enquiryId}_" . date('Ymd')
                );
                $emailDispatched = true;
            }
        }

        // Audit Log
        $this->auditModel->log('EMPLOYER_STATUS_CHANGE', 'employer_enquiry', $enquiryId, [
            'status' => $oldStatus,
        ], [
            'status'           => $targetStatus,
            'is_reopened'      => $isReopening,
            'follow_up_date'   => $followUpDate,
            'email_dispatched' => $emailDispatched,
        ]);

        return [
            'success'          => true,
            'message'          => 'Employer enquiry updated to ' . str_replace('_', ' ', $targetStatus) . ($emailDispatched ? ' and corporate closing notice dispatched.' : '.'),
            'old_status'       => $oldStatus,
            'new_status'       => $targetStatus,
            'is_reopened'      => $isReopening,
            'email_dispatched' => $emailDispatched,
            'follow_up_date'   => $followUpDate,
        ];
    }

    /**
     * Process General Contact Message Status Change & Follow-up
     */
    public function updateContactStatus(
        int $contactId,
        string $newStatus,
        ?string $replyMessage = null,
        ?string $followUpDate = null,
        ?int $changedByUserId = null
    ): array {
        $record = $this->contactEnquiryModel->find($contactId);
        if (!$record) {
            return ['success' => false, 'message' => 'Contact message record not found.'];
        }

        $validStatuses = ['UNREAD', 'READ', 'RESPONDED', 'SPAM'];
        $targetStatus = strtoupper(trim($newStatus));
        if (!in_array($targetStatus, $validStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid contact message status provided.'];
        }

        $oldStatus = strtoupper($record['status'] ?? 'UNREAD');
        $now = date('Y-m-d H:i:s');

        $updates = [
            'status'            => $targetStatus,
            'updated_at'        => $now,
            'last_contacted_at' => $now,
        ];

        if (!empty($replyMessage)) {
            $updates['follow_up_notes'] = trim($replyMessage);
        }

        if (!empty($followUpDate)) {
            $updates['follow_up_date'] = date('Y-m-d H:i:s', strtotime($followUpDate));
        }

        $this->contactEnquiryModel->update($contactId, $updates);

        $emailDispatched = false;
        if ($targetStatus === 'RESPONDED' && !empty($replyMessage) && !empty($record['email'])) {
            $recipientName = trim(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')) ?: 'Valued Inquirer';
            $subject = 'Re: ' . ($record['subject'] ?? 'TalentHub Inquiry');

            $bodyHtml = "<div style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #1a1a1a; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;\">
                <div style=\"border-bottom: 2px solid #1D6A34; padding-bottom: 12px; margin-bottom: 20px;\">
                    <h2 style=\"color: #072412; margin: 0;\">TalentHub Consultant</h2>
                    <p style=\"color: #1D6A34; font-size: 13px; font-weight: bold; margin: 4px 0 0 0;\">Response from Recruitment Desk</p>
                </div>
                <p>Dear <strong>" . esc($recipientName) . "</strong>,</p>
                <p>Thank you for reaching out to TalentHub Consultant. Regarding your inquiry on <em>\"" . esc($record['subject'] ?? 'General Consultation') . "\"</em>:</p>
                <div style=\"background: #f8fafc; border-left: 4px solid #1D6A34; padding: 14px 18px; margin: 20px 0; border-radius: 0 8px 8px 0; color: #1e293b; font-size: 14px;\">
                    " . nl2br(esc($replyMessage)) . "
                </div>
                <p>If you have any further questions or require immediate recruitment assistance, please feel free to reply directly to this email or call our desk at <a href=\"tel:+917276146573\" style=\"color: #1D6A34; text-decoration: none; font-weight: bold;\">+91 72761 46573</a>.</p>
                <hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;\" />
                <p style=\"font-size: 12px; color: #64748b; margin: 0;\">TalentHub Consultant Desk | Katraj, Pune | hr@talenthubconsultant.com</p>
            </div>";

            $bodyText = "Dear {$recipientName},\n\nThank you for contacting TalentHub Consultant.\n\nResponse:\n{$replyMessage}\n\nBest regards,\nTalentHub Recruitment Desk\n+91 72761 46573 | hr@talenthubconsultant.com";

            // Direct queue into outbox
            $db = Database::connect();
            $db->table('email_outbox')->insert([
                'recipient_email' => $record['email'],
                'recipient_name'  => $recipientName,
                'subject'         => $subject,
                'body_html'       => $bodyHtml,
                'body_text'       => $bodyText,
                'status'          => 'PENDING',
                'idempotency_key' => "contact_reply_{$contactId}_" . date('YmdHis'),
                'created_at'      => $now,
            ]);
            $emailDispatched = true;
        }

        // Audit Log
        $this->auditModel->log('CONTACT_STATUS_CHANGE', 'contact_enquiry', $contactId, [
            'status' => $oldStatus,
        ], [
            'status'           => $targetStatus,
            'follow_up_date'   => $followUpDate,
            'email_dispatched' => $emailDispatched,
        ]);

        return [
            'success'          => true,
            'message'          => 'Contact inquiry marked as ' . ucfirst(strtolower($targetStatus)) . ($emailDispatched ? ' and automated response email dispatched.' : '.'),
            'old_status'       => $oldStatus,
            'new_status'       => $targetStatus,
            'email_dispatched' => $emailDispatched,
            'follow_up_date'   => $followUpDate,
        ];
    }

    /**
     * Broadcast New Job Vacancy Alert to verified job seekers & newsletter subscribers
     */
    public function broadcastNewJobAlert(array $job): array
    {
        $db = Database::connect();
        $jobId = (int) ($job['id'] ?? 0);
        if ($jobId <= 0) {
            return ['success' => false, 'queued' => 0, 'message' => 'Invalid job ID.'];
        }

        // Get category name
        $categoryName = 'Professional Roles';
        if (!empty($job['category_id'])) {
            $cat = $db->table('job_categories')->where('id', (int) $job['category_id'])->get()->getRowArray();
            if ($cat && !empty($cat['name'])) {
                $categoryName = $cat['name'];
            }
        }

        // Fetch verified active newsletter subscribers and job alert subscribers
        $subscribers = $db->table('newsletter_subscribers')
            ->select('id, email, first_name, unsubscribe_token_hash')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $jobUrl = base_url('jobs/' . ($job['slug'] ?? $jobId));
        $descSummary = !empty($job['description']) ? (mb_substr(strip_tags($job['description']), 0, 200) . '...') : 'Exciting career opportunity.';
        $location = $job['location'] ?? 'India';
        $exp = $job['experience_label'] ?? '2-5 Years';
        $title = $job['title'] ?? 'Career Opportunity';
        $jobType = $job['job_type'] ?? 'Full-Time';

        $template = (new \App\Models\EmailTemplateModel())->getByCode('new_job_broadcast');
        $queued = 0;

        foreach ($subscribers as $sub) {
            $subEmail = trim(strtolower($sub['email']));
            $token = !empty($sub['unsubscribe_token_hash']) ? $sub['unsubscribe_token_hash'] : bin2hex(random_bytes(16));
            $unsubUrl = base_url("newsletter/unsubscribe?token={$token}");
            $prefUrl  = base_url("newsletter/preferences?token={$token}");
            $subName  = !empty($sub['first_name']) ? $sub['first_name'] : 'Professional';
            $idempotency = "job_alert_{$jobId}_{$sub['id']}";

            $replacements = [
                '{{subscriber_name}}'     => esc($subName),
                '{{job_title}}'           => esc($title),
                '{{category_name}}'       => esc($categoryName),
                '{{job_type}}'            => esc($jobType),
                '{{location}}'            => esc($location),
                '{{experience_label}}'    => esc($exp),
                '{{description_summary}}' => esc($descSummary),
                '{{job_url}}'             => esc($jobUrl),
                '{{unsubscribe_url}}'     => esc($unsubUrl),
                '{{preferences_url}}'     => esc($prefUrl),
            ];

            $subject = strtr($template['subject'] ?? 'New Career Opportunity: {{job_title}} — TalentHub', $replacements);
            $bodyHtml = strtr($template['body_html'] ?? '', $replacements);
            $bodyText = strtr($template['body_text'] ?? '', $replacements);

            // Check if already queued
            $exists = $db->table('email_outbox')->where('idempotency_key', $idempotency)->countAllResults();
            if ($exists === 0) {
                $db->table('email_outbox')->insert([
                    'recipient_email' => $subEmail,
                    'recipient_name'  => $subName,
                    'subject'         => $subject,
                    'body_html'       => $bodyHtml,
                    'body_text'       => $bodyText,
                    'status'          => 'PENDING',
                    'idempotency_key' => $idempotency,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                $queued++;
            }
        }

        return [
            'success' => true,
            'queued'  => $queued,
            'message' => "Job notification queued for {$queued} verified subscribers.",
        ];
    }

    /**
     * Broadcast New Article / Insight Publication to verified subscribers
     */
    public function broadcastNewArticleAlert(array $article): array
    {
        $db = Database::connect();
        $articleId = (int) ($article['id'] ?? 0);
        if ($articleId <= 0) {
            return ['success' => false, 'queued' => 0, 'message' => 'Invalid article ID.'];
        }

        $subscribers = $db->table('newsletter_subscribers')
            ->select('id, email, first_name, unsubscribe_token_hash')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->get()
            ->getResultArray();

        $articleUrl = base_url('insights/' . ($article['slug'] ?? $articleId));
        $summary = !empty($article['summary']) ? $article['summary'] : 'Read the latest recruitment commentary and hiring trends.';
        $title = $article['title'] ?? 'Market Intelligence Article';
        $categoryName = $article['category_name'] ?? 'Industry Analysis';

        $template = (new \App\Models\EmailTemplateModel())->getByCode('new_article_broadcast');
        $queued = 0;

        foreach ($subscribers as $sub) {
            $subEmail = trim(strtolower($sub['email']));
            $token = !empty($sub['unsubscribe_token_hash']) ? $sub['unsubscribe_token_hash'] : bin2hex(random_bytes(16));
            $unsubUrl = base_url("newsletter/unsubscribe?token={$token}");
            $prefUrl  = base_url("newsletter/preferences?token={$token}");
            $subName  = !empty($sub['first_name']) ? $sub['first_name'] : 'Subscriber';
            $idempotency = "article_alert_{$articleId}_{$sub['id']}";

            $replacements = [
                '{{subscriber_name}}' => esc($subName),
                '{{article_title}}'   => esc($title),
                '{{category_name}}'   => esc($categoryName),
                '{{article_summary}}' => esc($summary),
                '{{article_url}}'     => esc($articleUrl),
                '{{unsubscribe_url}}' => esc($unsubUrl),
                '{{preferences_url}}' => esc($prefUrl),
            ];

            $subject = strtr($template['subject'] ?? 'TalentHub Insights: {{article_title}}', $replacements);
            $bodyHtml = strtr($template['body_html'] ?? '', $replacements);
            $bodyText = strtr($template['body_text'] ?? '', $replacements);

            $exists = $db->table('email_outbox')->where('idempotency_key', $idempotency)->countAllResults();
            if ($exists === 0) {
                $db->table('email_outbox')->insert([
                    'recipient_email' => $subEmail,
                    'recipient_name'  => $subName,
                    'subject'         => $subject,
                    'body_html'       => $bodyHtml,
                    'body_text'       => $bodyText,
                    'status'          => 'PENDING',
                    'idempotency_key' => $idempotency,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                $queued++;
            }
        }

        return [
            'success' => true,
            'queued'  => $queued,
            'message' => "Article publication alert queued for {$queued} subscribers.",
        ];
    }

    /**
     * 1-Click Direct Notice / Announcement Dispatcher
     */
    public function sendDirectNotice(array $notice): array
    {
        $db = Database::connect();
        $subject = trim($notice['subject'] ?? 'TalentHub Official Announcement');
        $headline = trim($notice['headline'] ?? $subject);
        $content = trim($notice['content'] ?? '');
        $priorityBadge = trim($notice['priority_badge'] ?? 'Official Notice');
        $ctaText = trim($notice['cta_text'] ?? '');
        $ctaUrl = trim($notice['cta_url'] ?? '');
        $audience = trim($notice['audience'] ?? 'all');

        if (empty($content)) {
            return ['success' => false, 'queued' => 0, 'message' => 'Notice message content is required.'];
        }

        // Query targeted audience
        $builder = $db->table('newsletter_subscribers')
            ->select('id, email, first_name, unsubscribe_token_hash')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false);

        if ($audience === 'candidates') {
            $builder->like('consent_source', 'candidate');
        } elseif ($audience === 'employers') {
            $builder->like('consent_source', 'employer');
        }

        $subscribers = $builder->get()->getResultArray();
        if (empty($subscribers)) {
            return ['success' => false, 'queued' => 0, 'message' => 'No active verified subscribers found for the selected audience.'];
        }

        $template = (new \App\Models\EmailTemplateModel())->getByCode('direct_announcement_notice');
        $now = date('Y-m-d H:i:s');
        $batchId = uniqid('notice_');
        $queued = 0;

        foreach ($subscribers as $sub) {
            $subEmail = trim(strtolower($sub['email']));
            $token = !empty($sub['unsubscribe_token_hash']) ? $sub['unsubscribe_token_hash'] : bin2hex(random_bytes(16));
            $unsubUrl = base_url("newsletter/unsubscribe?token={$token}");
            $prefUrl  = base_url("newsletter/preferences?token={$token}");
            $subName  = !empty($sub['first_name']) ? $sub['first_name'] : 'Valued Member';
            $idempotency = "notice_{$batchId}_{$sub['id']}";

            $hasCta = !empty($ctaText) && !empty($ctaUrl);
            $ctaHtml = $hasCta ? '<div style="text-align: center; margin: 30px 0;"><a href="' . esc($ctaUrl) . '" style="display: inline-block; background-color: #1D6A34; color: #ffffff; font-size: 13px; font-weight: 700; padding: 13px 28px; text-decoration: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(29,106,52,0.25);">' . esc($ctaText) . ' &rarr;</a></div>' : '';

            $replacements = [
                '{{announcement_subject}}'  => esc($subject),
                '{{announcement_headline}}' => esc($headline),
                '{{announcement_content}}'  => nl2br(esc($content)),
                '{{priority_badge}}'        => esc($priorityBadge),
                '{{subscriber_name}}'       => esc($subName),
                '{{cta_text}}'              => esc($ctaText),
                '{{cta_url}}'               => esc($ctaUrl),
                '{{has_cta}}'               => '',
                '{{/has_cta}}'              => '',
                '{{unsubscribe_url}}'       => esc($unsubUrl),
                '{{preferences_url}}'       => esc($prefUrl),
            ];

            $defaultBodyHtml = '<div style="font-family:sans-serif;max-width:600px;margin:0 auto;padding:24px;"><h2 style="color:#072412;">{{announcement_headline}}</h2><p style="color:#334155;line-height:1.6;">{{announcement_content}}</p>' . $ctaHtml . '</div>';
            $renderedSubject = strtr($template['subject'] ?? $subject, $replacements);
            $renderedHtml    = strtr($template['body_html'] ?? $defaultBodyHtml, $replacements);
            $renderedText    = strtr($template['body_text'] ?? ($headline . "\n\n" . $content), $replacements);

            if (!$hasCta) {
                // remove CTA block if empty
                $renderedHtml = preg_replace('/\{\{#has_cta\}\}.*?\{\{\/has_cta\}\}/s', '', $renderedHtml);
            }

            $db->table('email_outbox')->insert([
                'recipient_email' => $subEmail,
                'recipient_name'  => $subName,
                'subject'         => $renderedSubject,
                'body_html'       => $renderedHtml,
                'body_text'       => $renderedText,
                'status'          => 'PENDING',
                'idempotency_key' => $idempotency,
                'created_at'      => $now,
            ]);
            $queued++;
        }

        // Log audit
        $this->auditModel->log('DIRECT_NOTICE_BROADCAST', 'announcements', 0, null, [
            'subject'  => $subject,
            'audience' => $audience,
            'queued'   => $queued,
        ]);

        return [
            'success' => true,
            'queued'  => $queued,
            'message' => "1-Click Direct Notice dispatched to {$queued} subscribed recipients.",
        ];
    }

    /**
     * Send Candidate Interview Reminder (24h before interview)
     */
    public function sendCandidateInterviewReminder(int $applicationId, array $reminderData): array
    {
        $app = $this->applicationModel->find($applicationId);
        if (!$app) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        $candidate = $this->candidateModel->find($app['candidate_id']);
        if (!$candidate || empty($candidate['email'])) {
            return ['success' => false, 'message' => 'Candidate email not found.'];
        }

        $job = $this->jobModel->find($app['job_id']);
        $jobTitle = $job['title'] ?? 'Role';

        $db = Database::connect();
        $now = date('Y-m-d H:i:s');
        $template = (new \App\Models\EmailTemplateModel())->getByCode('candidate_interview_reminder');

        $replacements = [
            '{{candidate_name}}'      => esc($candidate['full_name']),
            '{{job_title}}'           => esc($jobTitle),
            '{{company_name}}'        => esc($reminderData['company_name'] ?? 'TalentHub Partner Client'),
            '{{interview_date}}'      => esc($reminderData['interview_date'] ?? date('d M Y, h:i A', strtotime('+1 day'))),
            '{{interview_mode}}'      => esc($reminderData['interview_mode'] ?? 'Google Meet / Online'),
            '{{interview_location}}'  => esc($reminderData['interview_location'] ?? 'Link will be shared 15 mins prior'),
            '{{notes}}'               => esc($reminderData['notes'] ?? 'Please have your resume and technical portfolio handy.'),
            '{{contact_phone}}'       => esc($reminderData['contact_phone'] ?? '+91 72761 46573'),
        ];

        $subject  = strtr($template['subject'] ?? 'Interview Reminder: {{job_title}}', $replacements);
        $bodyHtml = strtr($template['body_html'] ?? '', $replacements);
        $bodyText = strtr($template['body_text'] ?? '', $replacements);

        $idempotency = "interview_reminder_{$applicationId}_" . date('Ymd');
        $exists = $db->table('email_outbox')->where('idempotency_key', $idempotency)->countAllResults();

        if ($exists === 0) {
            $db->table('email_outbox')->insert([
                'recipient_email' => $candidate['email'],
                'recipient_name'  => $candidate['full_name'],
                'subject'         => $subject,
                'body_html'       => $bodyHtml,
                'body_text'       => $bodyText,
                'status'          => 'PENDING',
                'idempotency_key' => $idempotency,
                'created_at'      => $now,
            ]);

            return [
                'success' => true,
                'message' => 'Candidate interview reminder queued successfully.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Candidate interview reminder was already dispatched today.',
        ];
    }

    /**
     * Get all overdue follow-ups across applications, employers, and contact inquiries
     */
    public function getOverdueFollowUps(): array
    {
        $now = date('Y-m-d H:i:s');

        // Overdue applications
        $apps = $this->applicationModel
            ->select('applications.id, applications.status, applications.follow_up_date, applications.follow_up_notes, candidates.full_name as contact_name, jobs.title as subject')
            ->join('candidates', 'candidates.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.follow_up_date IS NOT NULL', null, false)
            ->where('applications.follow_up_date <=', $now)
            ->whereNotIn('applications.status', ['REJECTED', 'WITHDRAWN', 'HIRED'])
            ->orderBy('applications.follow_up_date', 'ASC')
            ->findAll(50);

        // Overdue employer mandates
        $employers = $this->employerEnquiryModel
            ->select('id, status, follow_up_date, follow_up_notes, company_name, contact_person as contact_name, required_roles as subject')
            ->where('follow_up_date IS NOT NULL', null, false)
            ->where('follow_up_date <=', $now)
            ->whereNotIn('status', ['MANDATE_WON', 'CLOSED_LOST'])
            ->orderBy('follow_up_date', 'ASC')
            ->findAll(50);

        // Overdue contact inquiries
        $contacts = $this->contactEnquiryModel
            ->select('id, status, follow_up_date, follow_up_notes, CONCAT(first_name, " ", last_name) as contact_name, subject')
            ->where('follow_up_date IS NOT NULL', null, false)
            ->where('follow_up_date <=', $now)
            ->whereNotIn('status', ['RESPONDED', 'SPAM'])
            ->orderBy('follow_up_date', 'ASC')
            ->findAll(50);

        return [
            'applications' => $apps,
            'employers'    => $employers,
            'contacts'     => $contacts,
            'total'        => count($apps) + count($employers) + count($contacts),
        ];
    }
}

