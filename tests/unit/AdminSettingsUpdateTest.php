<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AdminSettingsUpdateTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testSettingsUpdateSucceedsWithRedirectAndNo500(): void
    {
        // Setup authenticated admin session
        $adminSession = [
            'admin_user' => [
                'id'          => 1,
                'username'    => 'admin',
                'email'       => 'admin@talenthubconsultant.com',
                'first_name'  => 'Super',
                'last_name'   => 'Admin',
                'roles'       => ['superadmin'],
                'permissions' => ['settings.manage'],
            ],
        ];

        $token = csrf_hash();

        $postData = [
            csrf_token()           => $token,
            'site_name'            => 'TalentHub Consultant',
            'site_tagline'         => 'Pune Leading Job Consultancy & Recruitment Specialists',
            'working_hours'        => 'Monday - Saturday: 9:30 AM - 7:00 PM IST',
            'contact_email'        => 'hr@talenthubconsultant.com',
            'hiring_email'         => 'hiring@talenthubconsultant.com',
            'contact_phone'        => '+91 72761 46573',
            'whatsapp_number'      => '+91 72761 46573',
            'office_address'       => 'Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046',
            'whatsapp_channel_url' => 'https://whatsapp.com/channel/0029VbDFr1ALCoX7PvOSOK1R',
            'linkedin_url'         => 'https://www.linkedin.com/in/talenthub-hiring',
            'instagram_url'        => '',
            'facebook_url'         => '',
            'twitter_url'          => '',
            'youtube_url'          => '',
            'ga_enabled'           => '1',
            'ga_measurement_id'    => 'G-K6J0RWMLBS',
            'tawk_enabled'         => '1',
            'tawk_property_id'     => '6ad65a04c40a0134c95ce3b6',
            'tawk_widget_id'       => '1k5akv9ak',
            'maintenance_headline' => 'We Are Improving Your Experience',
            'maintenance_message'  => 'TalentHub Consultant is currently undergoing scheduled platform upgrades to serve you better.',
            'jobs_enabled'         => '1',
            'applications_enabled' => '1',
            'employer_enquiry_enabled' => '1',
        ];

        $result = $this->withSession($adminSession)->call('post', '/admin/settings/update', $postData);

        // Must redirect to admin/settings with 302, NEVER a 500 error
        $result->assertStatus(302);
        $result->assertRedirectTo(base_url('admin/settings'));

        // Verify flashdata
        $this->assertNotNull(session()->getFlashdata('success'));
        $this->assertStringContainsString('saved successfully', session()->getFlashdata('success'));
    }
}
