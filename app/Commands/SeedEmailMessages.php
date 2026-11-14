<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SeedEmailMessages extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'mail:seed-messages';
    protected $description = 'Seed realistic recruitment emails into mail_message_index';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $count = $db->table('mail_message_index')->countAllResults();
        if ($count > 0) {
            CLI::write("mail_message_index already has {$count} messages.", 'yellow');
            return;
        }

        $now = date('Y-m-d H:i:s');
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));

        $messages = [
            [
                'mailbox_resource_id' => 'mb_hr_pune_primary',
                'message_uid'         => 'msg_20261028_001',
                'folder'              => 'INBOX',
                'thread_id'           => 'th_app_101_rahul',
                'internet_message_id' => '<2026102801.rahul.sharma@gmail.com>',
                'subject'             => 'Application for Steel Foreman — Rahul Sharma (Pune)',
                'from_name'           => 'Rahul Sharma',
                'from_email'          => 'rahul.sharma.civil@gmail.com',
                'to_recipients'       => json_encode([['name' => 'TalentHub HR', 'email' => 'hr@talenthubconsultant.com']]),
                'snippet'             => 'Dear TalentHub Team, I have 8+ years of structural steel erection and site supervision experience in Pune and Chakan industrial areas...',
                'body_html'           => '<p>Dear TalentHub Team,</p><p>I am applying for the <strong>Steel Foreman</strong> opening in Pune (Ref: TH-ENG-2026). I have over 8 years of hands-on experience leading structural steel fabrication and erection crews across major infrastructure projects in Chakan, Talegaon, and Bhosari MIDC.</p><p>Key Highlights:</p><ul><li>Supervised teams of 35+ welders, fitters, and riggers with zero safety incidents.</li><li>Expert in reading structural drawings, bar bending schedules, and welding quality inspection.</li><li>Current CTC: 5.5 LPA | Expected CTC: 7.0 LPA | Notice Period: 15 Days.</li></ul><p>Please find my updated resume attached for your review. I look forward to your response.</p><p>Warm regards,<br><strong>Rahul Sharma</strong><br>+91 98230 11223<br>rahul.sharma.civil@gmail.com</p>',
                'body_text'           => "Dear TalentHub Team,\n\nI am applying for the Steel Foreman opening in Pune (Ref: TH-ENG-2026). I have over 8 years of hands-on experience leading structural steel fabrication and erection crews across major infrastructure projects in Chakan.\n\nCurrent CTC: 5.5 LPA | Expected CTC: 7.0 LPA | Notice Period: 15 Days.\n\nWarm regards,\nRahul Sharma\n+91 98230 11223",
                'has_attachments'     => 1,
                'attachments_meta'    => json_encode([
                    ['id' => 'att_001', 'name' => 'Rahul_Sharma_Steel_Foreman_CV.pdf', 'size' => 245760, 'mime' => 'application/pdf']
                ]),
                'is_read'             => 0,
                'is_flagged'          => 1,
                'category'            => 'candidate_app',
                'recruitment_type'    => 'application',
                'recruitment_id'      => 1,
                'message_date'        => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'mailbox_resource_id' => 'mb_hr_pune_primary',
                'message_uid'         => 'msg_20261028_002',
                'folder'              => 'INBOX',
                'thread_id'           => 'th_emp_kirloskar_hiring',
                'internet_message_id' => '<corpHiring.2026@kirloskar-eng.com>',
                'subject'             => 'Urgent Requirement: 5 Senior CNC Machinists & Quality Inspectors',
                'from_name'           => 'Vikram Deshmukh (GM HR - Kirloskar Precision)',
                'from_email'          => 'vikram.deshmukh@kirloskar-eng.com',
                'to_recipients'       => json_encode([['name' => 'TalentHub HR', 'email' => 'hr@talenthubconsultant.com']]),
                'snippet'             => 'Hi TalentHub Team, We have an immediate requirement for 5 Senior CNC Turners/Machinists and 2 QA Engineers for our Hinjawadi Phase 2 plant...',
                'body_html'           => '<p>Dear TalentHub Recruitment Desk,</p><p>Kirloskar Precision Engineering has approved immediate staffing for our newly expanded Hinjawadi Phase 2 CNC division. We would like to partner with TalentHub Consultant on this exclusive mandate.</p><p><strong>Requirements:</strong></p><ol><li><strong>5x Senior CNC Machinists:</strong> 5-8 yrs experience with Fanuc/Siemens controls, precision tolerance components. Budget: Up to 6.5 LPA.</li><li><strong>2x Quality Assurance Engineers:</strong> CMM operation, ISO/TS standards, PPAP documentation. Budget: Up to 7.8 LPA.</li></ol><p>Please share candidate shortlists by this Friday. Can we schedule an onboarding call tomorrow at 11:30 AM?</p><p>Regards,<br><strong>Vikram Deshmukh</strong><br>General Manager — Human Resources<br>Kirloskar Precision Engineering, Pune</p>',
                'body_text'           => "Dear TalentHub Recruitment Desk,\n\nWe have approved immediate staffing for our Hinjawadi Phase 2 CNC division:\n- 5x Senior CNC Machinists (Fanuc/Siemens)\n- 2x QA Engineers (CMM / ISO)\n\nPlease share candidate shortlists by this Friday.\n\nRegards,\nVikram Deshmukh\nKirloskar Precision Engineering",
                'has_attachments'     => 1,
                'attachments_meta'    => json_encode([
                    ['id' => 'att_002', 'name' => 'JD_CNC_Senior_Machinist_2026.docx', 'size' => 112640, 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
                ]),
                'is_read'             => 1,
                'is_flagged'          => 1,
                'category'            => 'employer_req',
                'recruitment_type'    => 'employer_enquiry',
                'recruitment_id'      => 1,
                'message_date'        => $yesterday,
                'created_at'          => $yesterday,
                'updated_at'          => $yesterday,
            ],
            [
                'mailbox_resource_id' => 'mb_info_general',
                'message_uid'         => 'msg_20261028_003',
                'folder'              => 'INBOX',
                'thread_id'           => 'th_gen_priya_consultation',
                'internet_message_id' => '<priya.kulkarni88@yahoo.com>',
                'subject'             => 'Inquiry regarding Executive Search and Virtual Interview assistance',
                'from_name'           => 'Priya Kulkarni',
                'from_email'          => 'priya.kulkarni88@yahoo.com',
                'to_recipients'       => json_encode([['name' => 'TalentHub Info Desk', 'email' => 'info@talenthubconsultant.com']]),
                'snippet'             => 'Hello, I am seeking guidance on senior finance management roles in Pune. Does TalentHub offer virtual interview coaching and resume positioning...',
                'body_html'           => '<p>Hello TalentHub Consultant,</p><p>I came across your recruitment consultancy website and would like to know if your Katraj office handles executive search for Chartered Accountants and Finance Directors in Maharashtra.</p><p>Additionally, do you provide virtual interview coordination for overseas candidates returning to Pune?</p><p>Thank you,<br><strong>Priya Kulkarni, FCA</strong><br>Kothrud, Pune</p>',
                'body_text'           => "Hello TalentHub Consultant,\n\nI would like to know if your Katraj office handles executive search for Chartered Accountants and Finance Directors in Maharashtra.\n\nThank you,\nPriya Kulkarni, FCA",
                'has_attachments'     => 0,
                'attachments_meta'    => json_encode([]),
                'is_read'             => 1,
                'is_flagged'          => 0,
                'category'            => 'general_enquiry',
                'recruitment_type'    => 'contact_enquiry',
                'recruitment_id'      => 1,
                'message_date'        => $twoDaysAgo,
                'created_at'          => $twoDaysAgo,
                'updated_at'          => $twoDaysAgo,
            ],
            [
                'mailbox_resource_id' => 'mb_hr_pune_primary',
                'message_uid'         => 'msg_20261028_004',
                'folder'              => 'SENT',
                'thread_id'           => 'th_app_101_rahul',
                'internet_message_id' => '<sent.20261028.hr@talenthubconsultant.com>',
                'subject'             => 'Re: Application for Steel Foreman — Interview Invitation',
                'from_name'           => 'TalentHub HR Desk',
                'from_email'          => 'hr@talenthubconsultant.com',
                'to_recipients'       => json_encode([['name' => 'Rahul Sharma', 'email' => 'rahul.sharma.civil@gmail.com']]),
                'snippet'             => 'Dear Rahul, Thank you for applying for the Steel Foreman role. We are pleased to invite you for a virtual preliminary discussion...',
                'body_html'           => '<p>Dear Rahul,</p><p>Thank you for submitting your profile for the Steel Foreman position with our client in Pune. We were impressed with your structural fabrication track record.</p><p>We would like to invite you for a preliminary interview on <strong>Friday, 31st October at 3:00 PM IST</strong> via Google Meet.</p><p>Please confirm if this schedule works for you.</p><p>Warm regards,<br>TalentHub HR Desk<br>Pune, Maharashtra</p>',
                'body_text'           => "Dear Rahul,\n\nThank you for submitting your profile. We would like to invite you for a preliminary interview on Friday, 31st October at 3:00 PM IST.\n\nPlease confirm.\n\nWarm regards,\nTalentHub HR Desk",
                'has_attachments'     => 0,
                'attachments_meta'    => json_encode([]),
                'is_read'             => 1,
                'is_flagged'          => 0,
                'category'            => 'interview',
                'recruitment_type'    => 'application',
                'recruitment_id'      => 1,
                'message_date'        => $now,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]
        ];

        $db->table('mail_message_index')->insertBatch($messages);
        CLI::write("[PASS] Seeded " . count($messages) . " recruitment messages into mail_message_index!", 'green');
    }
}

