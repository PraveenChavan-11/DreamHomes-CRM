<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Admin\AdminBaseController;
use App\Models\AnnouncementModel;
use App\Models\AnnouncementEventModel;
use App\Models\NewsletterSubscriberModel;
use App\Models\NewsletterPreferenceModel;
use App\Models\NewsletterActivityLogModel;
use App\Models\JobAlertSubscriptionModel;
use App\Models\EmailCampaignModel;
use App\Models\EmailDeliveryLogModel;
use App\Models\EmployerEnquiryModel;
use App\Models\ContactEnquiryModel;
use App\Models\ApplicationModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Models\CookieConsentLogModel;
use App\Models\WebsiteSettingModel;
use App\Services\AnnouncementService;
use App\Services\NewsletterService;
use App\Services\EmailCampaignService;
use App\Services\JobAlertService;

class EngagementController extends AdminBaseController
{
    use \App\Controllers\Traits\GuardsAdminPermissions;

    /** Methods that previously had no check of their own. (Others guard themselves, so FAIL_CLOSED stays off.) */
    protected const PERMISSIONS_FAIL_CLOSED = false;
    protected const PERMISSIONS = [
        'editAnnouncement' => ['engagement.manage'], 'updateAnnouncement' => ['engagement.manage'], 'toggleAnnouncementStatus' => ['engagement.manage'],
        'duplicateAnnouncement' => ['engagement.manage'], 'deleteAnnouncement' => ['engagement.manage'],
        'toggleSubscriberStatus' => ['newsletter.manage', 'engagement.manage'],
        'editCampaign' => ['campaigns.send'], 'updateCampaign' => ['campaigns.send'], 'sendTestEmail' => ['campaigns.send'],
        'sendCampaignBroadcast' => ['campaigns.send'], 'campaignLogs' => ['campaigns.send'],
    ];

    private AnnouncementModel $announcementModel;
    private AnnouncementEventModel $eventModel;
    private NewsletterSubscriberModel $subscriberModel;
    private NewsletterPreferenceModel $preferenceModel;
    private NewsletterActivityLogModel $activityModel;
    private JobAlertSubscriptionModel $alertModel;
    private EmailCampaignModel $campaignModel;
    private EmailDeliveryLogModel $deliveryLogModel;
    private EmployerEnquiryModel $employerEnquiryModel;
    private ContactEnquiryModel $contactEnquiryModel;
    private ApplicationModel $applicationModel;
    private GeneralResumeSubmissionModel $resumeBankModel;
    private CookieConsentLogModel $cookieConsentModel;
    private WebsiteSettingModel $settingModel;

    private AnnouncementService $announcementService;
    private NewsletterService $newsletterService;
    private EmailCampaignService $campaignService;
    private JobAlertService $jobAlertService;

    public function __construct()
    {
        $this->announcementModel     = new AnnouncementModel();
        $this->eventModel            = new AnnouncementEventModel();
        $this->subscriberModel       = new NewsletterSubscriberModel();
        $this->preferenceModel       = new NewsletterPreferenceModel();
        $this->activityModel         = new NewsletterActivityLogModel();
        $this->alertModel            = new JobAlertSubscriptionModel();
        $this->campaignModel         = new EmailCampaignModel();
        $this->deliveryLogModel      = new EmailDeliveryLogModel();
        $this->employerEnquiryModel  = new EmployerEnquiryModel();
        $this->contactEnquiryModel   = new ContactEnquiryModel();
        $this->applicationModel      = new ApplicationModel();
        $this->resumeBankModel       = new GeneralResumeSubmissionModel();
        $this->cookieConsentModel    = new CookieConsentLogModel();
        $this->settingModel          = new WebsiteSettingModel();

        $this->announcementService   = new AnnouncementService();
        $this->newsletterService     = new NewsletterService();
        $this->campaignService       = new EmailCampaignService();
        $this->jobAlertService       = new JobAlertService();
    }

    private function getCommonData(string $activeMenu, string $title): array
    {
        $session = session();
        $adminUser = $session->get('admin_user') ?? [
            'name'  => 'Administrator',
            'email' => 'admin@talenthubconsultant.com',
            'role'  => 'super_admin',
        ];

        return [
            'title'       => $title . ' — Engagement Centre — TalentHub',
            'activeMenu'  => $activeMenu,
            'adminUser'   => $adminUser,
            'counts'      => [
                'active_campaigns' => $this->announcementModel->where('status', 'ACTIVE')->countAllResults(false),
                'subscribers'      => $this->subscriberModel->countAllResults(false),
                'job_alerts'       => $this->alertModel->where('status', 'active')->countAllResults(false),
            ],
            'settings'    => $this->settingModel->getAllKeyed(),
        ];
    }

    /**
     * 1. Overview Dashboard
     */
    public function index()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.view', 'engagement.manage', 'newsletter.manage', 'campaigns.send'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_overview', 'Engagement Overview & Telemetry');

        $data['subStats']       = $this->subscriberModel->getTelemetryCounts();
        $data['campaignStats']  = $this->announcementModel->getCampaignStats();
        $data['deliveryStats']  = $this->campaignModel->getDeliveryStats();
        $data['alertCount']     = $this->alertModel->where('status', 'active')->countAllResults();
        $data['recentActivity'] = $this->activityModel->orderBy('id', 'DESC')->findAll(10);
        $data['analytics']      = $this->eventModel->getAnalyticsSummary();
        $data['cookieStats']    = $this->cookieConsentModel->getConsentStats();

        // Calculate aggregate impression & conversion telemetry
        $totalImp = 0;
        $totalClk = 0;
        $totalConv = 0;
        foreach ($data['analytics'] as $row) {
            $totalImp += (int) $row['impressions'];
            $totalClk += (int) $row['clicks'];
            $totalConv += (int) $row['conversions'];
        }
        $data['totalImpressions'] = $totalImp;
        $data['totalClicks']       = $totalClk;
        $data['totalConversions']  = $totalConv;
        $data['overallCtr']        = $totalImp > 0 ? round(($totalClk / $totalImp) * 100, 2) : 0;

        return $this->renderAdminView('admin/engagement/overview', $data, 'engagement_overview');
    }

    /**
     * 2. Announcements & Popups List
     */
    public function announcements()
    {
        if ($redirect = $this->requirePermission('engagement.manage')) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_announcements', 'Announcements & Popup Manager');

        $statusFilter = $this->request->getGet('status');
        $search       = $this->request->getGet('search');

        $builder = $this->announcementModel;
        if (!empty($statusFilter)) {
            $builder->where('status', $statusFilter);
        }
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('title', $search)
                    ->orLike('public_heading', $search)
                    ->groupEnd();
        }

        $data['announcements'] = $builder->orderBy('priority', 'DESC')->orderBy('id', 'DESC')->findAll();
        $data['statusFilter']  = $statusFilter;
        $data['search']        = $search;

        return $this->renderAdminView('admin/engagement/announcements/index', $data, 'engagement_announcements');
    }

    /**
     * Announcement Create Form
     */
    public function newAnnouncement()
    {
        if ($redirect = $this->requirePermission('engagement.manage')) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_announcements', 'Create Announcement / Popup');
        $data['announcement'] = null;
        return $this->renderAdminView('admin/engagement/announcements/form', $data, 'engagement_announcements');
    }

    /**
     * Announcement Store
     */
    public function storeAnnouncement()
    {
        if ($redirect = $this->requirePermission('engagement.manage')) {
            return $redirect;
        }
        $payload = [
            'title'                => $this->request->getPost('title'),
            'internal_description'=> $this->request->getPost('internal_description'),
            'public_heading'       => $this->request->getPost('public_heading'),
            'public_content'       => $this->request->getPost('public_content'),
            'image_url'            => $this->request->getPost('image_url') ?: null,
            'cta_text'             => $this->request->getPost('cta_text') ?: null,
            'cta_url'              => $this->request->getPost('cta_url') ?: null,
            'announcement_type'    => $this->request->getPost('announcement_type') ?: 'company_announcement',
            'display_format'       => $this->request->getPost('display_format') ?: 'center_modal',
            'start_date'           => $this->request->getPost('start_date') ?: date('Y-m-d'),
            'end_date'             => $this->request->getPost('end_date') ?: date('Y-m-d', strtotime('+30 days')),
            'start_time'           => $this->request->getPost('start_time') ?: '00:00:00',
            'end_time'             => $this->request->getPost('end_time') ?: '23:59:59',
            'timezone'             => $this->request->getPost('timezone') ?: 'Asia/Kolkata',
            'audience'             => $this->request->getPost('audience') ?: 'everyone',
            'target_pages'         => $this->request->getPost('target_pages') ?: null,
            'frequency'            => $this->request->getPost('frequency') ?: 'once_per_session',
            'priority'             => (int) ($this->request->getPost('priority') ?? 1),
            'status'               => $this->request->getPost('status') ?: 'DRAFT',
        ];

        $id = $this->announcementModel->insert($payload);

        return redirect()->to(base_url('admin/engagement/announcements'))->with('success', 'Announcement campaign created successfully.');
    }

    /**
     * Announcement Edit Form
     */
    public function editAnnouncement(int $id)
    {
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            return redirect()->to(base_url('admin/engagement/announcements'))->with('error', 'Campaign not found.');
        }

        $data = $this->getCommonData('engagement_announcements', 'Edit Announcement / Popup');
        $data['announcement'] = $announcement;
        return $this->renderAdminView('admin/engagement/announcements/form', $data, 'engagement_announcements');
    }

    /**
     * Announcement Update
     */
    public function updateAnnouncement(int $id)
    {
        $payload = [
            'title'                => $this->request->getPost('title'),
            'internal_description'=> $this->request->getPost('internal_description'),
            'public_heading'       => $this->request->getPost('public_heading'),
            'public_content'       => $this->request->getPost('public_content'),
            'image_url'            => $this->request->getPost('image_url') ?: null,
            'cta_text'             => $this->request->getPost('cta_text') ?: null,
            'cta_url'              => $this->request->getPost('cta_url') ?: null,
            'announcement_type'    => $this->request->getPost('announcement_type') ?: 'company_announcement',
            'display_format'       => $this->request->getPost('display_format') ?: 'center_modal',
            'start_date'           => $this->request->getPost('start_date'),
            'end_date'             => $this->request->getPost('end_date'),
            'start_time'           => $this->request->getPost('start_time') ?: '00:00:00',
            'end_time'             => $this->request->getPost('end_time') ?: '23:59:59',
            'timezone'             => $this->request->getPost('timezone') ?: 'Asia/Kolkata',
            'audience'             => $this->request->getPost('audience') ?: 'everyone',
            'target_pages'         => $this->request->getPost('target_pages') ?: null,
            'frequency'            => $this->request->getPost('frequency') ?: 'once_per_session',
            'priority'             => (int) ($this->request->getPost('priority') ?? 1),
            'status'               => $this->request->getPost('status') ?: 'DRAFT',
        ];

        $this->announcementModel->update($id, $payload);

        return redirect()->to(base_url('admin/engagement/announcements'))->with('success', 'Campaign updated successfully.');
    }

    /**
     * Toggle status (Pause / Activate / Resume)
     */
    public function toggleAnnouncementStatus(int $id)
    {
        $announcement = $this->announcementModel->find($id);
        if (!$announcement) {
            return redirect()->to(base_url('admin/engagement/announcements'))->with('error', 'Campaign not found.');
        }

        $newStatus = ($announcement['status'] === 'ACTIVE') ? 'PAUSED' : 'ACTIVE';
        $this->announcementModel->update($id, ['status' => $newStatus]);

        return redirect()->to(base_url('admin/engagement/announcements'))->with('success', "Campaign status transitioned to {$newStatus}.");
    }

    /**
     * Duplicate Announcement
     */
    public function duplicateAnnouncement(int $id)
    {
        $original = $this->announcementModel->find($id);
        if (!$original) {
            return redirect()->to(base_url('admin/engagement/announcements'))->with('error', 'Campaign not found.');
        }

        unset($original['id'], $original['created_at'], $original['updated_at']);
        $original['title'] = $original['title'] . ' (Copy)';
        $original['status'] = 'DRAFT';

        $this->announcementModel->insert($original);

        return redirect()->to(base_url('admin/engagement/announcements'))->with('success', 'Campaign duplicated as draft.');
    }

    /**
     * Delete Announcement
     */
    public function deleteAnnouncement(int $id)
    {
        $this->announcementModel->delete($id);
        return redirect()->to(base_url('admin/engagement/announcements'))->with('success', 'Campaign deleted.');
    }

    /**
     * 3. Newsletter Subscribers Management
     */
    public function subscribers()
    {
        if ($redirect = $this->requireAnyPermission(['newsletter.manage', 'engagement.manage'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_subscribers', 'Newsletter Subscribers');

        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $builder = $this->subscriberModel;
        if (!empty($status)) {
            $builder->where('status', $status);
        }
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('email', $search)
                    ->orLike('first_name', $search)
                    ->groupEnd();
        }

        $data['subscribers']  = $builder->orderBy('id', 'DESC')->findAll(100);
        $data['statusFilter'] = $status;
        $data['search']       = $search;
        $data['stats']        = $this->subscriberModel->getTelemetryCounts();

        return $this->renderAdminView('admin/engagement/subscribers/index', $data, 'engagement_subscribers');
    }

    /**
     * Toggle subscriber status (Admin suppression / Reactivation)
     */
    public function toggleSubscriberStatus(int $id)
    {
        $sub = $this->subscriberModel->find($id);
        if (!$sub) {
            return redirect()->to(base_url('admin/engagement/subscribers'))->with('error', 'Subscriber not found.');
        }

        $newStatus = ($sub['status'] === 'subscribed') ? 'unsubscribed' : 'subscribed';
        $unsubTime = ($newStatus === 'unsubscribed') ? date('Y-m-d H:i:s') : null;

        $this->subscriberModel->update($id, [
            'status'          => $newStatus,
            'unsubscribed_at' => $unsubTime,
            'verified_at'     => $sub['verified_at'] ?: date('Y-m-d H:i:s'),
        ]);

        $this->activityModel->logActivity($id, 'admin_status_override', 'admin_portal', ['new_status' => $newStatus]);

        return redirect()->to(base_url('admin/engagement/subscribers'))->with('success', "Subscriber transitioned to {$newStatus}.");
    }

    /**
     * Export Subscribers to CSV
     */
    public function exportSubscribersCsv()
    {
        if ($redirect = $this->requireAnyPermission(['newsletter.manage', 'engagement.manage'])) {
            return $redirect;
        }
        $subscribers = $this->subscriberModel->orderBy('id', 'DESC')->findAll();

        $filename = 'talenthub_newsletter_subscribers_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Email', 'First Name', 'Status', 'Consent Given', 'Consent Source', 'Consent Timestamp', 'Verified At', 'Unsubscribed At', 'Created At']);

        foreach ($subscribers as $row) {
            fputcsv($out, [
                $row['id'],
                $row['email'],
                $row['first_name'] ?? '',
                $row['status'],
                $row['consent_given'] ? 'Yes' : 'No',
                $row['consent_source'],
                $row['consent_timestamp'],
                $row['verified_at'] ?? 'Pending',
                $row['unsubscribed_at'] ?? '',
                $row['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * 4. Candidate Job Alerts
     */
    public function jobAlerts()
    {
        if ($redirect = $this->requireAnyPermission(['newsletter.manage', 'engagement.manage', 'jobs.publish'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_job_alerts', 'Job Alert Subscriptions');

        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        $builder = $this->alertModel;
        if (!empty($status)) {
            $builder->where('status', $status);
        }
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('email', $search)
                    ->orLike('job_category', $search)
                    ->orLike('preferred_location', $search)
                    ->groupEnd();
        }

        $data['alerts']       = $builder->orderBy('id', 'DESC')->findAll(100);
        $data['statusFilter'] = $status;
        $data['search']       = $search;
        $data['totalActive']  = $this->alertModel->where('status', 'active')->countAllResults();

        return $this->renderAdminView('admin/engagement/job_alerts/index', $data, 'engagement_job_alerts');
    }

    /**
     * Export Job Alerts CSV
     */
    public function exportJobAlertsCsv()
    {
        if ($redirect = $this->requireAnyPermission(['newsletter.manage', 'engagement.manage', 'jobs.publish'])) {
            return $redirect;
        }
        $alerts = $this->alertModel->orderBy('id', 'DESC')->findAll();

        $filename = 'talenthub_job_alerts_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Email', 'Job Category', 'Preferred Location', 'Experience Level', 'Employment Type', 'Frequency', 'Status', 'Last Alert Sent', 'Created At']);

        foreach ($alerts as $row) {
            fputcsv($out, [
                $row['id'],
                $row['email'],
                $row['job_category'] ?? 'All',
                $row['preferred_location'] ?? 'Pune Corridors',
                $row['experience_level'] ?? '',
                $row['employment_type'] ?? '',
                $row['frequency'],
                $row['status'],
                $row['last_alert_sent_at'] ?? 'Never',
                $row['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * 5. Email Campaigns Management
     */
    public function campaigns()
    {
        if ($redirect = $this->requirePermission('campaigns.send')) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_campaigns', 'Email Campaigns & Broadcasts');

        $data['campaigns']     = $this->campaignModel->orderBy('id', 'DESC')->findAll();
        $data['deliveryStats'] = $this->campaignModel->getDeliveryStats();
        $data['verifiedCount'] = $this->subscriberModel->where('status', 'subscribed')->where('verified_at IS NOT NULL', null, false)->countAllResults();

        return $this->renderAdminView('admin/engagement/campaigns/index', $data, 'engagement_campaigns');
    }

    /**
     * Campaign Compose Form
     */
    public function newCampaign()
    {
        if ($redirect = $this->requirePermission('campaigns.send')) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_campaigns', 'Compose Email Campaign');
        $data['campaign'] = null;
        return $this->renderAdminView('admin/engagement/campaigns/form', $data, 'engagement_campaigns');
    }

    /**
     * Campaign Store
     */
    public function storeCampaign()
    {
        if ($redirect = $this->requirePermission('campaigns.send')) {
            return $redirect;
        }
        $session = session();
        $admin = $session->get('admin_user');

        $payload = [
            'title'           => $this->request->getPost('title'),
            'campaign_type'   => $this->request->getPost('campaign_type') ?: 'newsletter',
            'subject'         => $this->request->getPost('subject'),
            'preview_text'    => $this->request->getPost('preview_text') ?: null,
            'body_html'       => $this->request->getPost('body_html'),
            'body_text'       => $this->request->getPost('body_text') ?: strip_tags($this->request->getPost('body_html')),
            'audience_filter' => $this->request->getPost('audience_filter') ?: 'all',
            'status'          => 'DRAFT',
            'created_by'      => $admin['id'] ?? 1,
        ];

        $id = $this->campaignModel->insert($payload);

        return redirect()->to(base_url('admin/engagement/campaigns'))->with('success', 'Email campaign saved as draft.');
    }

    /**
     * Campaign Edit Form
     */
    public function editCampaign(int $id)
    {
        $campaign = $this->campaignModel->find($id);
        if (!$campaign) {
            return redirect()->to(base_url('admin/engagement/campaigns'))->with('error', 'Campaign not found.');
        }

        $data = $this->getCommonData('engagement_campaigns', 'Edit Email Campaign');
        $data['campaign'] = $campaign;
        return $this->renderAdminView('admin/engagement/campaigns/form', $data, 'engagement_campaigns');
    }

    /**
     * Campaign Update
     */
    public function updateCampaign(int $id)
    {
        $payload = [
            'title'           => $this->request->getPost('title'),
            'campaign_type'   => $this->request->getPost('campaign_type') ?: 'newsletter',
            'subject'         => $this->request->getPost('subject'),
            'preview_text'    => $this->request->getPost('preview_text') ?: null,
            'body_html'       => $this->request->getPost('body_html'),
            'body_text'       => $this->request->getPost('body_text') ?: strip_tags($this->request->getPost('body_html')),
            'audience_filter' => $this->request->getPost('audience_filter') ?: 'all',
        ];

        $this->campaignModel->update($id, $payload);

        return redirect()->to(base_url('admin/engagement/campaigns'))->with('success', 'Campaign content updated.');
    }

    /**
     * Send Test Email for a Campaign
     */
    public function sendTestEmail(int $id)
    {
        $testEmail = $this->request->getPost('test_email');
        $result = $this->campaignService->sendTestEmail($id, $testEmail);

        if ($result['success']) {
            return redirect()->to(base_url('admin/engagement/campaigns'))->with('success', $result['message']);
        }
        return redirect()->to(base_url('admin/engagement/campaigns'))->with('error', $result['message']);
    }

    /**
     * Send Campaign Broadcast to Verified Audience
     */
    public function sendCampaignBroadcast(int $id)
    {
        $result = $this->campaignService->sendCampaign($id);

        if ($result['success']) {
            return redirect()->to(base_url('admin/engagement/campaigns'))->with('success', $result['message']);
        }
        return redirect()->to(base_url('admin/engagement/campaigns'))->with('error', $result['message']);
    }

    /**
     * View Campaign Delivery Logs
     */
    public function campaignLogs(int $id)
    {
        $campaign = $this->campaignModel->find($id);
        if (!$campaign) {
            return redirect()->to(base_url('admin/engagement/campaigns'))->with('error', 'Campaign not found.');
        }

        $data = $this->getCommonData('engagement_campaigns', 'Campaign Delivery Logs');
        $data['campaign'] = $campaign;
        $data['logs']     = $this->deliveryLogModel->where('campaign_id', $id)->orderBy('id', 'DESC')->findAll(100);

        return $this->renderAdminView('admin/engagement/campaigns/logs', $data, 'engagement_campaigns');
    }

    /**
     * 1-Click Direct Notice & Announcement Broadcast Page
     */
    public function quickNotice()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.manage', 'campaigns.send'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_quick_notice', '1-Click Direct Announcement & Notice Dispatcher');

        $db = \Config\Database::connect();
        $data['totalSubscribers'] = $db->table('newsletter_subscribers')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->countAllResults();

        $data['candidateSubscribers'] = $db->table('newsletter_subscribers')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->like('consent_source', 'candidate')
            ->countAllResults();

        $data['employerSubscribers'] = $db->table('newsletter_subscribers')
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->like('consent_source', 'employer')
            ->countAllResults();

        $data['recentBroadcasts'] = $db->table('email_outbox')
            ->select('id, recipient_email, recipient_name, subject, status, created_at')
            ->like('idempotency_key', 'notice_')
            ->orderBy('id', 'DESC')
            ->limit(25)
            ->get()
            ->getResultArray();

        return $this->renderAdminView('admin/engagement/quick_notice', $data, 'engagement_quick_notice');
    }

    /**
     * Handle 1-Click Direct Notice Send
     */
    public function sendQuickNotice()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.manage', 'campaigns.send'])) {
            return $redirect;
        }
        $rules = [
            'subject'  => 'required|min_length[5]|max_length[255]',
            'headline' => 'required|min_length[5]|max_length[255]',
            'content'  => 'required|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please fill in all required fields (subject, headline, and message content).');
        }

        $payload = [
            'subject'        => (string) $this->request->getPost('subject'),
            'headline'       => (string) $this->request->getPost('headline'),
            'content'        => (string) $this->request->getPost('content'),
            'priority_badge' => (string) ($this->request->getPost('priority_badge') ?: 'Official Notice'),
            'cta_text'       => (string) ($this->request->getPost('cta_text') ?: ''),
            'cta_url'        => (string) ($this->request->getPost('cta_url') ?: ''),
            'audience'       => (string) ($this->request->getPost('audience') ?: 'all'),
        ];

        $service = new \App\Services\EnquiryAutomationService();
        $result = $service->sendDirectNotice($payload);

        if ($result['success']) {
            return redirect()->to(base_url('admin/engagement/quick-notice'))->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * 6. Unified Lead Management
     */
    public function leads()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.manage', 'enquiries.manage'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_leads', 'Unified Inbound Lead Management');

        $typeFilter   = $this->request->getGet('type') ?? 'all';
        $statusFilter = $this->request->getGet('status') ?? 'all';
        $search       = $this->request->getGet('search') ?? '';

        $leads = [];

        // Employer enquiries
        if ($typeFilter === 'all' || $typeFilter === 'employer') {
            $eBuilder = $this->employerEnquiryModel;
            if ($statusFilter !== 'all') $eBuilder->where('status', $statusFilter);
            if (!empty($search)) {
                $eBuilder->groupStart()
                         ->like('company_name', $search)
                         ->orLike('contact_person', $search)
                         ->orLike('email', $search)
                         ->groupEnd();
            }
            $eRows = $eBuilder->orderBy('id', 'DESC')->findAll(50);
            foreach ($eRows as $r) {
                $leads[] = [
                    'id'             => $r['id'],
                    'type'           => 'Employer Mandate',
                    'lead_type'      => 'employer',
                    'contact_name'   => $r['contact_person'] ?? $r['company_name'],
                    'company'        => $r['company_name'] ?? 'Direct Mandate',
                    'email'          => $r['email'],
                    'phone'          => $r['phone'] ?? '-',
                    'status'         => $r['status'] ?? 'new',
                    'summary'        => 'Roles: ' . ($r['positions_needed'] ?? $r['hiring_needs'] ?? $r['required_roles'] ?? 'General Corporate'),
                    'follow_up_date' => $r['follow_up_date'] ?? null,
                    'reopened_at'    => $r['reopened_at'] ?? null,
                    'created_at'     => $r['created_at'],
                ];
            }
        }

        // Contact enquiries
        if ($typeFilter === 'all' || $typeFilter === 'contact') {
            $cBuilder = $this->contactEnquiryModel;
            if ($statusFilter !== 'all') $cBuilder->where('status', $statusFilter);
            if (!empty($search)) {
                $cBuilder->groupStart()
                         ->like('first_name', $search)
                         ->orLike('last_name', $search)
                         ->orLike('email', $search)
                         ->orLike('subject', $search)
                         ->groupEnd();
            }
            $cRows = $cBuilder->orderBy('id', 'DESC')->findAll(50);
            foreach ($cRows as $r) {
                $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?: ($r['name'] ?? 'Inquirer');
                $leads[] = [
                    'id'             => $r['id'],
                    'type'           => 'General Contact',
                    'lead_type'      => 'contact',
                    'contact_name'   => $name,
                    'company'        => $r['company'] ?? '-',
                    'email'          => $r['email'],
                    'phone'          => $r['phone'] ?? '-',
                    'status'         => $r['status'] ?? 'new',
                    'summary'        => $r['subject'] ?? substr($r['message'] ?? '', 0, 80),
                    'follow_up_date' => $r['follow_up_date'] ?? null,
                    'reopened_at'    => $r['reopened_at'] ?? null,
                    'created_at'     => $r['created_at'],
                ];
            }
        }

        // Candidate Applications
        if ($typeFilter === 'all' || $typeFilter === 'candidate') {
            $aModel = new \App\Models\ApplicationModel();
            $aRows = $aModel->getApplicationsWithDetails([], 50, 0);
            foreach ($aRows as $r) {
                if ($statusFilter !== 'all' && strtoupper($statusFilter) !== strtoupper($r['status'] ?? '')) {
                    continue;
                }
                $leads[] = [
                    'id'             => $r['id'],
                    'type'           => 'Candidate Application',
                    'lead_type'      => 'candidate',
                    'contact_name'   => $r['candidate_name'] ?? 'Candidate #' . $r['id'],
                    'company'        => $r['job_title'] ?? 'Role',
                    'email'          => $r['candidate_email'] ?? '-',
                    'phone'          => $r['candidate_phone'] ?? '-',
                    'status'         => $r['status'] ?? 'applied',
                    'summary'        => 'Role: ' . ($r['job_title'] ?? 'General') . ' (' . ($r['experience_years'] ?? '0') . ' yrs exp)',
                    'follow_up_date' => $r['follow_up_date'] ?? null,
                    'reopened_at'    => $r['reopened_at'] ?? null,
                    'created_at'     => $r['created_at'],
                ];
            }
        }

        // Sort unified leads by created_at DESC
        usort($leads, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        $data['leads']        = $leads;
        $data['typeFilter']   = $typeFilter;
        $data['statusFilter'] = $statusFilter;
        $data['search']       = $search;

        return $this->renderAdminView('admin/engagement/leads/index', $data, 'engagement_leads');
    }

    /**
     * Export Leads CSV
     */
    public function exportLeadsCsv()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.manage', 'enquiries.manage'])) {
            return $redirect;
        }
        $filename = 'talenthub_leads_export_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Lead Type', 'Contact Name', 'Company', 'Email', 'Phone', 'Status', 'Summary', 'Created At']);

        $eRows = $this->employerEnquiryModel->orderBy('id', 'DESC')->findAll();
        foreach ($eRows as $r) {
            fputcsv($out, [
                'Employer Mandate',
                $r['contact_person'] ?? $r['company_name'],
                $r['company_name'] ?? 'Direct Mandate',
                $r['email'],
                $r['phone'] ?? '-',
                $r['status'] ?? 'new',
                $r['positions_needed'] ?? $r['hiring_needs'] ?? '',
                $r['created_at'],
            ]);
        }

        $cRows = $this->contactEnquiryModel->orderBy('id', 'DESC')->findAll();
        foreach ($cRows as $r) {
            fputcsv($out, [
                'General Contact',
                $r['name'],
                '-',
                $r['email'],
                $r['phone'] ?? '-',
                $r['status'] ?? 'new',
                $r['subject'] ?? '',
                $r['created_at'],
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * 7. Campaign Analytics
     */
    public function analytics()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.view', 'engagement.manage'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_analytics', 'Campaign Analytics & Event Telemetry');
        $data['analytics'] = $this->eventModel->getAnalyticsSummary();
        return $this->renderAdminView('admin/engagement/analytics/index', $data, 'engagement_analytics');
    }

    /**
     * Executive Placement & Marketing Performance Report (GAP-09)
     * Provides printable B2B Executive Summary with KPI charts, candidate funnel, and telemetry.
     */
    public function executiveReport()
    {
        if ($redirect = $this->requireAnyPermission(['engagement.view', 'engagement.manage', 'reports.view'])) {
            return $redirect;
        }

        $appModel = new \App\Models\ApplicationModel();
        $jobModel = new \App\Models\JobModel();
        $candModel = new \App\Models\CandidateModel();

        $data = $this->getCommonData('engagement_analytics', 'TalentHub — Executive Placement & Operations Report');
        $data['totalApplications'] = $appModel->countAllResults();
        $data['hiredCount'] = $appModel->where('status', 'HIRED')->countAllResults();
        $data['shortlistedCount'] = $appModel->whereIn('status', ['SHORTLISTED', 'INTERVIEW', 'OFFER'])->countAllResults();
        $data['activeJobsCount'] = $jobModel->where('status', 'PUBLISHED')->countAllResults();
        $data['totalCandidates'] = $candModel->countAllResults();
        $data['analytics'] = $this->eventModel->getAnalyticsSummary();

        return view('admin/engagement/analytics/executive_report', $data);
    }

    /**
     * 8. Cookie Settings & Logs
     */
    public function cookies()
    {
        if ($redirect = $this->requireAnyPermission(['settings.manage', 'engagement.manage'])) {
            return $redirect;
        }
        $data = $this->getCommonData('engagement_cookies', 'Cookie Settings & Consent Telemetry');
        $data['stats'] = $this->cookieConsentModel->getConsentStats();
        $data['logs']  = $this->cookieConsentModel->orderBy('id', 'DESC')->findAll(50);
        return $this->renderAdminView('admin/engagement/cookies/index', $data, 'engagement_cookies');
    }

    /**
     * Trigger explicit schema check and auto-provisioning
     */
    public function migrateSchema()
    {
        if (!$this->isSuperAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Super Administrator access required.']);
        }
        $res = \App\Services\DatabaseSchemaService::ensureSchema(true);
        return $this->response->setJSON($res);
    }
}

