<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\WebsiteSettingModel;
use App\Services\OutboxService;
use CodeIgniter\HTTP\ResponseInterface;

class SettingsApiController extends BaseController
{
    private WebsiteSettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new WebsiteSettingModel();
    }

    public function index(): ResponseInterface
    {
        $settings = $this->settingModel->getAllKeyed();
        // Redact SMTP password in response for security
        if (isset($settings['smtp_pass'])) {
            $settings['smtp_pass'] = !empty($settings['smtp_pass']) ? '••••••••' : '';
        }
        return $this->respondJson($settings);
    }

    public function update(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $userId = session()->get('admin_user')['id'] ?? 1;

        foreach ($input as $key => $val) {
            // Ignore unchanged masked password
            if ($key === 'smtp_pass' && $val === '••••••••') {
                continue;
            }
            $isPublic = in_array($key, [
                'site_name', 'site_tagline', 'contact_email', 'hiring_email',
                'contact_phone', 'whatsapp_number', 'whatsapp_channel_url', 'office_address', 'working_hours',
                'linkedin_url', 'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url',
                'maintenance_mode', 'coming_soon_mode', 'ga_measurement_id',
                'tawk_property_id', 'enable_global_teams_link', 'global_teams_link'
            ], true) ? 1 : 0;

            $group = 'general';
            if (str_starts_with($key, 'smtp_')) $group = 'email';
            if (str_starts_with($key, 'maintenance_') || str_starts_with($key, 'coming_soon_')) $group = 'operations';
            if (str_ends_with($key, '_enabled')) $group = 'features';

            $this->settingModel->setSetting($key, is_scalar($val) ? (string) $val : json_encode($val), $group, $isPublic);
        }

        $audit = new AuditLogModel();
        $audit->log('SETTINGS_UPDATED', 'website_settings', null, null, ['keys_updated' => array_keys($input)], $userId);

        return $this->respondJson([], 'Settings updated successfully');
    }

    public function getWebsiteControl(): ResponseInterface
    {
        $all = $this->settingModel->getAllKeyed();
        $controls = [
            'maintenance_mode'          => $all['maintenance_mode'] ?? '0',
            'maintenance_headline'      => $all['maintenance_headline'] ?? 'System Maintenance Underway',
            'maintenance_message'       => $all['maintenance_message'] ?? 'Platform is temporarily undergoing scheduled maintenance.',
            'maintenance_return_time'   => $all['maintenance_return_time'] ?? 'Within 1 Hour',
            'maintenance_support_email' => $all['maintenance_support_email'] ?? 'hr@talenthubconsultant.com',
            'maintenance_support_phone' => $all['maintenance_support_phone'] ?? '+91 72761 46573',
            'coming_soon_mode'          => $all['coming_soon_mode'] ?? '0',
            'coming_soon_headline'      => $all['coming_soon_headline'] ?? 'Next-Generation Executive Talent is Arriving',
            'coming_soon_message'       => $all['coming_soon_message'] ?? 'We are curating Pune premier executive recruitment mandates.',
            'coming_soon_launch_date'   => $all['coming_soon_launch_date'] ?? '2026-11-01',
            'jobs_enabled'              => $all['jobs_enabled'] ?? '1',
            'applications_enabled'      => $all['applications_enabled'] ?? '1',
            'resume_upload_enabled'     => $all['resume_upload_enabled'] ?? '1',
            'employer_enquiry_enabled'  => $all['employer_enquiry_enabled'] ?? '1',
            'contact_form_enabled'      => $all['contact_form_enabled'] ?? '1',
        ];
        return $this->respondJson($controls);
    }

    public function updateWebsiteControl(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        foreach ($input as $key => $val) {
            $group = (str_ends_with($key, '_enabled')) ? 'features' : 'operations';
            $this->settingModel->setSetting($key, (string) $val, $group, 1);
        }

        $audit = new AuditLogModel();
        $audit->log('WEBSITE_CONTROL_UPDATED', 'website_settings', null, null, $input);

        return $this->respondJson([], 'Website operational controls updated');
    }

    public function testSmtp(): ResponseInterface
    {
        $outbox = new OutboxService();
        $mailer = $outbox->configureMailer();

        $adminEmail = session()->get('admin_user')['email'] ?? 'hr@talenthubconsultant.com';
        $mailer->setTo($adminEmail);
        $mailer->setSubject('SMTP Connectivity Verification — TalentHub Consultant');
        $mailer->setMessage('<p>This is a live SMTP connectivity test from the TalentHub Consultant CodeIgniter 4 Administration Platform. If you received this, email delivery is functioning correctly.</p>');

        if ($mailer->send(false)) {
            return $this->respondJson([], 'Test email sent successfully to ' . $adminEmail);
        }

        $debugger = $mailer->printDebugger(['headers']);
        return $this->respondError('SMTP Connection Failed: ' . substr($debugger, 0, 400), 500);
    }
}

