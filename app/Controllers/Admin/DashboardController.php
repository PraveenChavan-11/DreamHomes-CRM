<?php

namespace App\Controllers\Admin;

use App\Models\ApplicationModel;
use App\Models\ContactEnquiryModel;
use App\Models\EmployerEnquiryModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Models\JobModel;
use App\Models\MailboxConnectionModel;
use App\Models\MailMessageIndexModel;
use App\Models\WebsiteSettingModel;

class DashboardController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        $jobModel = new JobModel();
        $appModel = new ApplicationModel();
        $resumeModel = new GeneralResumeSubmissionModel();
        $employerModel = new EmployerEnquiryModel();
        $contactModel = new ContactEnquiryModel();
        $mailModel = new MailMessageIndexModel();
        $mailboxModel = new MailboxConnectionModel();
        $settingModel = new WebsiteSettingModel();

        // 1. Time Period Filter Selection
        $period = $this->request->getGet('period') ?? '7days';
        $startDate = null;
        $endDate = date('Y-m-d 23:59:59');

        switch ($period) {
            case 'today':
                $startDate = date('Y-m-d 00:00:00');
                break;
            case 'yesterday':
                $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;
            case '7days':
                $startDate = date('Y-m-d 00:00:00', strtotime('-6 days'));
                break;
            case '30days':
                $startDate = date('Y-m-d 00:00:00', strtotime('-29 days'));
                break;
            case 'all':
            default:
                $period = 'all';
                $startDate = null;
                break;
        }

        // 2. All-Time & Period-Specific Counts
        $db = \Config\Database::connect();

        $totalJobs = $db->table('jobs')->whereIn('status', ['PUBLISHED', 'active', 'published'])->countAllResults();
        $totalApps = $db->table('applications')->countAllResults();
        $totalResumes = $db->table('general_resume_submissions')->countAllResults();
        $totalEmployers = $db->table('employer_enquiries')->countAllResults();
        $totalContacts = $db->table('contact_enquiries')->countAllResults();

        // Period counts
        $periodApps = ($startDate !== null)
            ? $db->table('applications')->where('created_at >=', $startDate)->where('created_at <=', $endDate)->countAllResults()
            : $totalApps;

        $periodEmployers = ($startDate !== null)
            ? $db->table('employer_enquiries')->where('created_at >=', $startDate)->where('created_at <=', $endDate)->countAllResults()
            : $totalEmployers;

        $periodContacts = ($startDate !== null)
            ? $db->table('contact_enquiries')->where('created_at >=', $startDate)->where('created_at <=', $endDate)->countAllResults()
            : $totalContacts;

        $periodResumes = ($startDate !== null)
            ? $db->table('general_resume_submissions')->where('created_at >=', $startDate)->where('created_at <=', $endDate)->countAllResults()
            : $totalResumes;

        // 3. Candidate Pipeline Breakdown
        $pipeline = [
            'applied'     => $db->table('applications')->whereIn('status', ['APPLIED', 'applied', 'NEW', 'new', ''])->countAllResults(),
            'screening'   => $db->table('applications')->whereIn('status', ['SCREENING', 'screening', 'reviewing', 'REVIEWING'])->countAllResults(),
            'shortlisted' => $db->table('applications')->whereIn('status', ['SHORTLISTED', 'shortlisted'])->countAllResults(),
            'interview'   => $db->table('applications')->whereIn('status', ['INTERVIEW', 'interview'])->countAllResults(),
            'offer'       => $db->table('applications')->whereIn('status', ['OFFER', 'offer', 'offered', 'OFFERED'])->countAllResults(),
            'hired'       => $db->table('applications')->whereIn('status', ['HIRED', 'hired'])->countAllResults(),
        ];

        // 4. Daily Trend Series for Interactive Chart.js (Last 7 Days)
        $trendLabels = [];
        $trendApps = [];
        $trendEmployers = [];
        $trendContacts = [];

        for ($i = 6; $i >= 0; $i--) {
            $dayDate = date('Y-m-d', strtotime("-$i days"));
            $trendLabels[] = date('M d', strtotime($dayDate));

            // Applications on day
            $appRow = $db->table('applications')
                ->where('DATE(created_at)', $dayDate)
                ->countAllResults();
            $trendApps[] = $appRow;

            // Employer enquiries on day
            $empRow = $db->table('employer_enquiries')
                ->where('DATE(created_at)', $dayDate)
                ->countAllResults();
            $trendEmployers[] = $empRow;

            // Contact enquiries on day
            $cntRow = $db->table('contact_enquiries')
                ->where('DATE(created_at)', $dayDate)
                ->countAllResults();
            $trendContacts[] = $cntRow;
        }

        // 5. Employer Lead Urgency Breakdown
        $urgencyBreakdown = [
            'Immediate' => $db->table('employer_enquiries')->whereIn('urgency', ['IMMEDIATE', 'Immediate', 'immediate'])->countAllResults(),
            '15 Days'   => $db->table('employer_enquiries')->whereIn('urgency', ['15_DAYS', '15 Days', '15 days'])->countAllResults(),
            '30 Days'   => $db->table('employer_enquiries')->whereIn('urgency', ['30_DAYS', '30 Days', '30 days'])->countAllResults(),
            'Pipeline'  => $db->table('employer_enquiries')->whereIn('urgency', ['EXPLORATORY', '60 Days', 'Pipeline', 'pipeline'])->countAllResults(),
        ];

        // 6. Recent Applications with Candidate Profile Joins
        try {
            $recentApplications = $appModel->getApplicationsWithDetails([], 6, 0);
        } catch (\Throwable $e) {
            try {
                $recentApplications = $appModel->orderBy('created_at', 'DESC')->findAll(6);
            } catch (\Throwable $e2) {
                $recentApplications = [];
            }
        }

        // 7. Recent Employer Enquiries
        try {
            $recentEmployers = $employerModel->orderBy('created_at', 'DESC')->findAll(5);
        } catch (\Throwable $e) {
            $recentEmployers = [];
        }

        // 8. Recent Contact Inquiries
        try {
            $recentContacts = $contactModel->orderBy('created_at', 'DESC')->findAll(5);
        } catch (\Throwable $e) {
            $recentContacts = [];
        }

        // 9. Recent Hostinger Mail Messages
        try {
            $recentEmails = $mailModel->orderBy('message_date', 'DESC')->findAll(5);
        } catch (\Throwable $e) {
            try {
                $recentEmails = $mailModel->orderBy('created_at', 'DESC')->findAll(5);
            } catch (\Throwable $e2) {
                $recentEmails = [];
            }
        }

        // 10. Connected Mailboxes
        try {
            $mailboxes = $mailboxModel->findAll();
        } catch (\Throwable $e) {
            $mailboxes = [];
        }

        // 11. Website Settings & Telemetry
        $settings = [];
        try {
            $settingsRows = $settingModel->findAll();
            foreach ($settingsRows as $s) {
                $settings[$s['setting_key'] ?? $s['key'] ?? ''] = $s['setting_value'] ?? $s['value'] ?? '';
            }
        } catch (\Throwable $e) {}

        $itLeadKpis = null;
        $crm = null;
        $crmProducts = $crmSources = [];
        try {
            $perms = $this->adminUser['permissions'] ?? [];
            if ($this->isSuperAdmin() || array_intersect(['it_leads.view', 'it_leads.manage', 'crm.view', 'crm.manage'], $perms)) {
                $itLeadKpis = (new \App\Services\ItLeadService())->counts();
                // CRM analytics block (real KPIs; failure here must never break the main dashboard)
                $range = \App\Services\CrmAnalyticsService::resolveRange(
                    (string) $this->request->getGet('crm_range'), (string) $this->request->getGet('crm_from'), (string) $this->request->getGet('crm_to'));
                $crmFilters = ['product' => trim((string) $this->request->getGet('crm_product')), 'source' => trim((string) $this->request->getGet('crm_source'))];
                $crm = (new \App\Services\CrmAnalyticsService())->compute($range, $crmFilters);
                $crmProducts = array_column($db->query("SELECT DISTINCT product_slug FROM it_leads WHERE product_slug IS NOT NULL AND product_slug <> '' AND deleted_at IS NULL ORDER BY product_slug LIMIT 100")->getResultArray(), 'product_slug');
                $crmSources  = array_column($db->query("SELECT DISTINCT source FROM it_leads WHERE deleted_at IS NULL ORDER BY source LIMIT 50")->getResultArray(), 'source');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard IT lead KPIs failed: ' . $e->getMessage());
        }

        return $this->renderAdminView('admin/dashboard/index', [
            'period'             => $period,
            'metrics'            => [
                'active_jobs'        => $totalJobs,
                'total_applications' => $totalApps,
                'resume_bank'        => $totalResumes,
                'employer_leads'     => $totalEmployers,
                'contact_messages'   => $totalContacts,
                'period_apps'        => $periodApps,
                'period_employers'   => $periodEmployers,
                'period_contacts'    => $periodContacts,
                'period_resumes'     => $periodResumes,
                'pipeline'           => $pipeline,
                'it_leads'           => $itLeadKpis,
            ],
            'chartData'          => [
                'labels'    => $trendLabels,
                'apps'      => $trendApps,
                'employers' => $trendEmployers,
                'contacts'  => $trendContacts,
                'urgency'   => $urgencyBreakdown,
            ],
            'crm'                => $crm,
            'crmProducts'        => $crmProducts,
            'crmSources'         => $crmSources,
            'recentApplications' => $recentApplications,
            'recentEmployers'    => $recentEmployers,
            'recentContacts'     => $recentContacts,
            'recentEmails'       => $recentEmails,
            'mailboxes'          => $mailboxes,
            'settings'           => $settings,
            'title'              => 'Executive Recruitment Dashboard — TalentHub',
        ], 'dashboard');
    }

    public function stats()
    {
        if ($redirect = $this->requireAuth()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $db = \Config\Database::connect();

        $pipeline = [
            'applied'     => $db->table('applications')->whereIn('status', ['APPLIED', 'applied', 'NEW', 'new', ''])->countAllResults(),
            'screening'   => $db->table('applications')->whereIn('status', ['SCREENING', 'screening', 'reviewing', 'REVIEWING'])->countAllResults(),
            'shortlisted' => $db->table('applications')->whereIn('status', ['SHORTLISTED', 'shortlisted'])->countAllResults(),
            'interview'   => $db->table('applications')->whereIn('status', ['INTERVIEW', 'interview'])->countAllResults(),
            'offer'       => $db->table('applications')->whereIn('status', ['OFFER', 'offer', 'offered', 'OFFERED'])->countAllResults(),
            'hired'       => $db->table('applications')->whereIn('status', ['HIRED', 'hired'])->countAllResults(),
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'jobs'               => $db->table('jobs')->whereIn('status', ['PUBLISHED', 'active', 'published'])->countAllResults(),
                'total_jobs'         => $db->table('jobs')->countAllResults(),
                'applications'       => $db->table('applications')->countAllResults(),
                'resumes'            => $db->table('general_resume_submissions')->countAllResults(),
                'employers'          => $db->table('employer_enquiries')->countAllResults(),
                'contacts'           => $db->table('contact_enquiries')->countAllResults(),
                'pipeline'           => $pipeline,
                'outbox'             => $db->table('email_outbox')->whereIn('status', ['PENDING', 'pending', 'PROCESSING', 'processing', 'QUEUED', 'queued'])->countAllResults(),
                'unread_mail'        => $db->table('mail_message_index')->where('is_read', 0)->countAllResults(),
                'server_time'        => date('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * PWA & In-App Real-time Notification Polling Endpoint
     */
    public function pollNotifications()
    {
        if ($redirect = $this->requireAuth()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(401);
        }

        $service = new \App\Services\EnquiryAutomationService();
        $overdue = $service->getOverdueFollowUps();

        $db = \Config\Database::connect();
        $today = date('Y-m-d 00:00:00');

        $newAppsToday = $db->table('applications')
            ->where('created_at >=', $today)
            ->countAllResults();

        $newEmployersToday = $db->table('employer_enquiries')
            ->where('created_at >=', $today)
            ->countAllResults();

        $newContactsToday = $db->table('contact_enquiries')
            ->where('created_at >=', $today)
            ->countAllResults();

        $pendingOutbox = $db->table('email_outbox')
            ->whereIn('status', ['PENDING', 'PROCESSING', 'QUEUED'])
            ->countAllResults();

        // Recent high-priority alerts
        $alerts = [];
        if ($overdue['total'] > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'title'   => "Overdue Follow-up Datelines ({$overdue['total']})",
                'message' => "You have {$overdue['total']} candidate or mandate follow-ups past deadline.",
                'url'     => base_url('admin/engagement/leads'),
                'time'    => 'Action Due',
            ];
        }

        if ($newAppsToday > 0) {
            $alerts[] = [
                'type'    => 'info',
                'title'   => "{$newAppsToday} New Candidate Application(s)",
                'message' => 'New candidate resumes received today awaiting screening.',
                'url'     => base_url('admin/applications'),
                'time'    => 'Today',
            ];
        }

        if ($newEmployersToday > 0) {
            $alerts[] = [
                'type'    => 'success',
                'title'   => "{$newEmployersToday} Employer Mandate(s)",
                'message' => 'New corporate client requirements registered.',
                'url'     => base_url('admin/enquiries/employers'),
                'time'    => 'Today',
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'total_unread'        => count($alerts),
                'overdue_count'       => $overdue['total'],
                'new_apps_today'      => $newAppsToday,
                'new_employers_today' => $newEmployersToday,
                'new_contacts_today'  => $newContactsToday,
                'pending_outbox'      => $pendingOutbox,
                'alerts'              => $alerts,
                'timestamp'           => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
