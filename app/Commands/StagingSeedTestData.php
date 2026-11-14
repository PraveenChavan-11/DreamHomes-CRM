<?php

namespace App\Commands;

use App\Services\EnvironmentSafetyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * TalentHub Consultant — Staging Test Data Seeder Command
 *
 * Usage: php spark staging:seed-test-data
 */
class StagingSeedTestData extends BaseCommand
{
    protected $group       = 'TalentHub Staging';
    protected $name        = 'staging:seed-test-data';
    protected $description = 'Seed realistic, isolated test data exclusively for the Staging environment';

    public function run(array $params)
    {
        CLI::write("==================================================", 'yellow');
        CLI::write(" TALENTHUB STAGING TEST DATA SEEDER", 'yellow');
        CLI::write("==================================================", 'yellow');

        try {
            EnvironmentSafetyService::assertNotProduction('Seed Staging Test Data');
        } catch (\Throwable $e) {
            CLI::error("ABORTED: " . $e->getMessage());
            return;
        }

        $db = Database::connect();
        $now = date('Y-m-d H:i:s');

        CLI::write("Connected to: " . $db->getDatabase(), 'cyan');
        CLI::newLine();

        // ── 1. SEED TEST JOBS ────────────────────────────────────────────────────────
        if ($db->tableExists('jobs')) {
            CLI::write("1. Seeding Staging Test Jobs...", 'white');
            $sampleJobs = [
                [
                    'title'             => '[STAGING] Principal Cloud Architect (AWS/GCP)',
                    'slug'              => 'staging-principal-cloud-architect-pune',
                    'category_id'       => 1, // IT & Software
                    'location_id'       => 1, // Hinjawadi Phase 1
                    'job_type'          => 'Full-time',
                    'experience_level'  => '10-15 Years',
                    'salary_range'      => '35 - 50 LPA',
                    'department'        => 'Cloud Engineering',
                    'overview'          => 'Oversee multi-cloud migration architecture for enterprise clients.',
                    'responsibilities'  => "• Lead multi-region infrastructure architecture on AWS and GCP.\n• Mentor senior cloud engineers.\n• Establish FinOps cost optimization frameworks.",
                    'requirements'      => "• 10+ years enterprise cloud systems experience.\n• AWS Solutions Architect Professional or Google Cloud Architect certified.\n• Deep Kubernetes and Terraform expertise.",
                    'benefits'          => "• Hybrid flexibility\n• Comprehensive executive health insurance\n• Annual bonus",
                    'status'            => 'PUBLISHED',
                    'is_featured'       => 1,
                    'is_urgent'         => 1,
                    'views_count'       => 142,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'title'             => '[STAGING] Lead Full-Stack Engineer (PHP CI4 & Vue)',
                    'slug'              => 'staging-lead-full-stack-engineer-pune',
                    'category_id'       => 1,
                    'location_id'       => 2, // Baner
                    'job_type'          => 'Full-time',
                    'experience_level'  => '6-10 Years',
                    'salary_range'      => '22 - 32 LPA',
                    'department'        => 'Software Development',
                    'overview'          => 'Architect scalable web applications using CodeIgniter 4 and modern frontend frameworks.',
                    'responsibilities'  => "• Deliver high-performance REST APIs.\n• Optimize MySQL database indexing and queries.\n• Supervise sprint code reviews.",
                    'requirements'      => "• 6+ years PHP OOP & CodeIgniter/Laravel experience.\n• Solid Tailwind CSS and TypeScript skills.\n• Strong database normalization track record.",
                    'benefits'          => "• Health insurance\n• Learning stipend\n• Flexible hours",
                    'status'            => 'PUBLISHED',
                    'is_featured'       => 1,
                    'is_urgent'         => 0,
                    'views_count'       => 98,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'title'             => '[STAGING] Senior Automotive Design Engineer (CATIA V5)',
                    'slug'              => 'staging-senior-automotive-design-engineer-chakan',
                    'category_id'       => 2, // Core Engineering
                    'location_id'       => 3, // Chakan Industrial Zone
                    'job_type'          => 'Full-time',
                    'experience_level'  => '5-8 Years',
                    'salary_range'      => '14 - 20 LPA',
                    'department'        => 'Automotive BIW',
                    'overview'          => 'Design sheet metal and plastic automotive trims for electric vehicle platforms.',
                    'responsibilities'  => "• Create 3D CAD models in CATIA V5.\n• Perform GD&T tolerance stack-up analysis.\n• Coordinate tool trials with suppliers.",
                    'requirements'      => "• B.E./B.Tech Mechanical/Automobile.\n• 5+ years BIW or interior trim design.\n• Strong DFMEA understanding.",
                    'benefits'          => "• Transport assistance\n• Production incentive\n• Group mediclaim",
                    'status'            => 'PUBLISHED',
                    'is_featured'       => 0,
                    'is_urgent'         => 1,
                    'views_count'       => 76,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ];

            foreach ($sampleJobs as $job) {
                $exists = $db->table('jobs')->where('slug', $job['slug'])->countAllResults();
                if ($exists === 0) {
                    $db->table('jobs')->insert($job);
                }
            }
            CLI::write("  -> Seeded 3 test job listings.", 'green');
        }

        // ── 2. SEED TEST CANDIDATES & APPLICATIONS ───────────────────────────────────
        if ($db->tableExists('candidates') && $db->tableExists('applications')) {
            CLI::write("2. Seeding Staging Test Applications...", 'white');
            $sampleCandidates = [
                [
                    'first_name'        => 'Praveen Testing',
                    'last_name'         => 'Staging',
                    'email'             => 'praveen.candidate.stg@example.com',
                    'phone'             => '+91 98765 43210',
                    'current_company'   => 'Staging Tech Labs Pune',
                    'current_role'      => 'Senior Software Engineer',
                    'experience_years'  => 8,
                    'current_ctc'       => 2400000,
                    'expected_ctc'      => 3000000,
                    'notice_period_days'=> 30,
                    'city'              => 'Pune',
                    'resume_path'       => 'vault/resumes/staging_dummy_resume_praveen.pdf',
                    'resume_filename'   => 'Praveen_Testing_Staging_Resume.pdf',
                    'skills'            => 'PHP, CodeIgniter, MySQL, AWS, Docker',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'first_name'        => 'Rohan Testing',
                    'last_name'         => 'Staging',
                    'email'             => 'rohan.cloud.stg@example.com',
                    'phone'             => '+91 98765 43211',
                    'current_company'   => 'CloudCorp Pune',
                    'current_role'      => 'DevOps Architect',
                    'experience_years'  => 11,
                    'current_ctc'       => 3500000,
                    'expected_ctc'      => 4500000,
                    'notice_period_days'=> 15,
                    'city'              => 'Pune',
                    'resume_path'       => 'vault/resumes/staging_dummy_resume_rohan.pdf',
                    'resume_filename'   => 'Rohan_Testing_Staging_Resume.pdf',
                    'skills'            => 'Kubernetes, Terraform, AWS, CI/CD',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ];

            foreach ($sampleCandidates as $cand) {
                $exists = $db->table('candidates')->where('email', $cand['email'])->get()->getRowArray();
                $candId = $exists ? $exists['id'] : null;

                if (!$exists) {
                    $candId = $db->table('candidates')->insert($cand);
                }

                if ($candId) {
                    $hasApp = $db->table('applications')->where('candidate_id', $candId)->countAllResults();
                    if ($hasApp === 0) {
                        $jobRow = $db->table('jobs')->first();
                        $jobId = $jobRow ? $jobRow['id'] : 1;

                        $db->table('applications')->insert([
                            'job_id'             => $jobId,
                            'candidate_id'       => $candId,
                            'full_name'          => $cand['first_name'] . ' ' . $cand['last_name'],
                            'email'              => $cand['email'],
                            'phone'              => $cand['phone'],
                            'total_experience'   => $cand['experience_years'],
                            'current_ctc'        => $cand['current_ctc'],
                            'expected_ctc'       => $cand['expected_ctc'],
                            'notice_period_days' => $cand['notice_period_days'],
                            'current_location'   => 'Pune (Hinjawadi)',
                            'resume_filename'    => $cand['resume_filename'],
                            'resume_storage_path'=> $cand['resume_path'],
                            'status'             => 'SHORTLISTED',
                            'cover_note'         => '[STAGING TEST RECORD] Automated test job application for end-to-end UAT pipeline verification.',
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ]);
                    }
                }
            }
            CLI::write("  -> Seeded test candidate profiles and applications.", 'green');
        }

        // ── 3. SEED TEST ENQUIRIES ────────────────────────────────────────────────────
        if ($db->tableExists('contact_enquiries')) {
            CLI::write("3. Seeding Staging Contact Enquiries...", 'white');
            $contacts = [
                [
                    'name'          => 'Praveen Testing - Staging Contact',
                    'email'         => 'praveen.contact.stg@example.com',
                    'phone'         => '+91 72761 46573',
                    'subject'       => '[STAGING TEST] Executive Search Query Baner',
                    'message'       => 'This is an isolated staging test enquiry to verify admin enquiry management, status transitions, and note logging.',
                    'source_page'   => '/contact',
                    'ip_address'    => '127.0.0.1',
                    'status'        => 'NEW',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'name'          => 'Anita Sharma (Staging QA)',
                    'email'         => 'anita.qa.stg@example.com',
                    'phone'         => '+91 91234 56789',
                    'subject'       => '[STAGING TEST] Hinjawadi Recruitment Drive Inquiry',
                    'message'       => 'Validating double opt-in and auto-response mechanisms in safe staging sandbox.',
                    'source_page'   => '/contact',
                    'ip_address'    => '127.0.0.1',
                    'status'        => 'IN_PROGRESS',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
            ];

            foreach ($contacts as $c) {
                $exists = $db->table('contact_enquiries')->where('email', $c['email'])->countAllResults();
                if ($exists === 0) {
                    $db->table('contact_enquiries')->insert($c);
                }
            }
            CLI::write("  -> Seeded test contact inquiries.", 'green');
        }

        if ($db->tableExists('employer_enquiries')) {
            CLI::write("4. Seeding Staging Employer Mandates...", 'white');
            $employers = [
                [
                    'company_name'    => 'Acme Technologies Pune (Staging Test)',
                    'contact_person'  => 'Praveen Testing - Staging Employer',
                    'email'           => 'praveen.employer.stg@example.com',
                    'phone'           => '+91 72761 46573',
                    'designation'     => 'Vice President Engineering',
                    'hiring_roles'    => '5x Senior Java Full-Stack, 2x Staff DevOps Engineers',
                    'approx_budget'   => '25 - 40 LPA',
                    'hiring_urgency'  => 'Immediate (15 Days)',
                    'status'          => 'NEW',
                    'notes'           => '[STAGING TEST RECORD] Employer hiring mandate for testing lead qualification workflows.',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
            ];

            foreach ($employers as $e) {
                $exists = $db->table('employer_enquiries')->where('email', $e['email'])->countAllResults();
                if ($exists === 0) {
                    $db->table('employer_enquiries')->insert($e);
                }
            }
            CLI::write("  -> Seeded test employer inquiries.", 'green');
        }

        // ── 5. SEED TEST SUBSCRIBERS ──────────────────────────────────────────────────
        if ($db->tableExists('newsletter_subscribers')) {
            CLI::write("5. Seeding Staging Newsletter Subscribers...", 'white');
            $subscribers = [
                [
                    'email'             => 'subscriber1.stg@example.com',
                    'first_name'        => 'Praveen Testing - Staging Sub',
                    'status'            => 'subscribed',
                    'consent_given'     => 1,
                    'consent_timestamp' => $now,
                    'consent_source'    => 'staging_test_seeder',
                    'verified_at'       => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
                [
                    'email'             => 'subscriber2.stg@example.com',
                    'first_name'        => 'Rohit Sharma (Staging QA)',
                    'status'            => 'pending',
                    'consent_given'     => 1,
                    'consent_timestamp' => $now,
                    'consent_source'    => 'footer_newsletter',
                    'verification_token_hash' => hash('sha256', 'dummy_staging_token_123'),
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ];

            foreach ($subscribers as $s) {
                $exists = $db->table('newsletter_subscribers')->where('email', $s['email'])->countAllResults();
                if ($exists === 0) {
                    $db->table('newsletter_subscribers')->insert($s);
                }
            }
            CLI::write("  -> Seeded test newsletter subscribers.", 'green');
        }

        // ── 6. SEED EMAIL SANDBOX LOGS ────────────────────────────────────────────────
        if ($db->tableExists('email_outbox')) {
            CLI::write("6. Seeding Staging Sandboxed Email Logs...", 'white');
            $sampleEmails = [
                [
                    'recipient_email' => 'candidate.stg@example.com',
                    'recipient_name'  => 'Praveen Testing - Staging',
                    'subject'         => '[STAGING TEST EMAIL] Application Received — Lead Full-Stack Engineer',
                    'body_html'       => '<h2>[STAGING SANDBOX PREVIEW]</h2><p>Hello Praveen,</p><p>Your application has been received in the Staging Environment.</p>',
                    'body_text'       => '[STAGING SANDBOX PREVIEW] Hello Praveen, Your application has been received.',
                    'source_type'     => 'staging_test',
                    'idempotency_key' => 'stg_init_' . uniqid(),
                    'status'          => 'SENT',
                    'attempts'        => 1,
                    'sent_at'         => $now,
                    'last_error'      => '[SANDBOXED] Successfully intercepted by Staging Email Sandbox.',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]
            ];

            foreach ($sampleEmails as $em) {
                $db->table('email_outbox')->insert($em);
            }
            CLI::write("  -> Seeded test email outbox entries.", 'green');
        }

        // ── 7. SEED AUDIT LOGS ────────────────────────────────────────────────────────
        if ($db->tableExists('audit_logs')) {
            CLI::write("7. Seeding Staging Audit Logs...", 'white');
            $db->table('audit_logs')->insert([
                'user_id'     => 1,
                'user_email'  => 'admin.staging@talenthubconsultant.com',
                'action'      => 'staging.environment_initialized',
                'entity_type' => 'system',
                'entity_id'   => 'staging_init',
                'description' => 'Staging isolated database initialized with realistic dummy data.',
                'ip_address'  => '127.0.0.1',
                'user_agent'  => 'TalentHub Staging CLI / Spark',
                'created_at'  => $now,
            ]);
            CLI::write("  -> Seeded staging initialization audit log.", 'green');
        }

        CLI::newLine();
        CLI::write("SUCCESS: All Staging test data provisioned successfully.", 'green');
    }
}

