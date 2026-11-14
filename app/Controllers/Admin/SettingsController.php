<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\WebsiteSettingModel;

class SettingsController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requirePermission('settings.manage')) {
            return $redirect;
        }

        $settingModel = new WebsiteSettingModel();
        $settings = $settingModel->getAllKeyed();

        return $this->renderAdminView('admin/settings/index', [
            'settings' => $settings,
            'title'    => 'Website Settings & Operations Control — TalentHub Admin',
        ], 'settings');
    }

    public function update()
    {
        if ($redirect = $this->requirePermission('settings.manage')) {
            return $redirect;
        }

        $settingModel = new WebsiteSettingModel();
        $oldSettings = $settingModel->getAllKeyed();

        $keys = [
            'site_name',
            'site_tagline',
            'contact_email',
            'hiring_email',
            'contact_phone',
            'whatsapp_number',
            'whatsapp_channel_url',
            'office_address',
            'working_hours',
            'linkedin_url',
            'facebook_url',
            'instagram_url',
            'twitter_url',
            'youtube_url',
            'ga_measurement_id',
            'ga_enabled',
            'tawk_property_id',
            'tawk_widget_id',
            'tawk_enabled',
            'maintenance_mode',
            'maintenance_headline',
            'maintenance_message',
            'coming_soon_mode',
            'jobs_enabled',
            'applications_enabled',
            'resume_upload_enabled',
            'employer_enquiry_enabled',
            'contact_form_enabled',
            'admin_email_otp_enabled',
        ];

        // Checkbox keys that default to 0 if not present in POST
        $checkboxKeys = [
            'ga_enabled',
            'tawk_enabled',
            'maintenance_mode',
            'coming_soon_mode',
            'jobs_enabled',
            'applications_enabled',
            'resume_upload_enabled',
            'employer_enquiry_enabled',
            'contact_form_enabled',
            'admin_email_otp_enabled',
        ];

        $changed = [];

        foreach ($keys as $k) {
            if (in_array($k, $checkboxKeys, true)) {
                $val = $this->request->getPost($k) ? '1' : '0';
            } else {
                $raw = $this->request->getPost($k);
                $val = ($raw !== null) ? trim((string) $raw) : null;
            }

            if ($val !== null) {
                if ($k === 'contact_email' && empty($val)) {
                    $val = 'hr@talenthubconsultant.com';
                }
                if ($k === 'hiring_email' && empty($val)) {
                    $val = 'hiring@talenthubconsultant.com';
                }
                if (($oldSettings[$k] ?? null) !== $val) {
                    $changed[$k] = [
                        'old' => $oldSettings[$k] ?? null,
                        'new' => $val,
                    ];
                }
                $settingModel->setSetting($k, $val);
            }
        }

        // Keep site_name and website_name synchronized
        if (isset($changed['site_name'])) {
            $settingModel->setSetting('website_name', $changed['site_name']['new']);
        }
        if (isset($changed['site_tagline'])) {
            $settingModel->setSetting('website_tagline', $changed['site_tagline']['new']);
        }

        // Audit Log if any changes occurred
        if (!empty($changed)) {
            try {
                $audit = new AuditLogModel();
                $oldVals = [];
                $newVals = [];
                foreach ($changed as $settingKey => $diff) {
                    $oldVals[$settingKey] = $diff['old'];
                    $newVals[$settingKey] = $diff['new'];
                }
                $audit->log('WEBSITE_SETTINGS_UPDATE', 'website_settings', 'settings', $oldVals, $newVals);
            } catch (\Throwable $e) {
                log_message('warning', 'SettingsController: Audit log write skipped: ' . $e->getMessage());
            }
        }

        session()->setFlashdata('success', 'Website configuration and telemetry settings saved successfully.');
        return redirect()->to(base_url('admin/settings'));
    }
}
