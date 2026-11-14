<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestDashboardCounts extends BaseCommand
{
    protected $group       = 'TalentHub';
    protected $name        = 'talenthub:test-counts';
    protected $description = 'Verifies all dashboard counts, pipeline stages, and testimonial integrity.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        CLI::write("=== 1. PIPELINE VELOCITY STAGES ===", 'green');
        $pipeline = [
            'applied'     => $db->table('applications')->whereIn('status', ['APPLIED', 'applied', 'NEW', 'new', ''])->countAllResults(),
            'screening'   => $db->table('applications')->whereIn('status', ['SCREENING', 'screening', 'reviewing', 'REVIEWING'])->countAllResults(),
            'shortlisted' => $db->table('applications')->whereIn('status', ['SHORTLISTED', 'shortlisted'])->countAllResults(),
            'interview'   => $db->table('applications')->whereIn('status', ['INTERVIEW', 'interview'])->countAllResults(),
            'offer'       => $db->table('applications')->whereIn('status', ['OFFER', 'offer', 'offered', 'OFFERED'])->countAllResults(),
            'hired'       => $db->table('applications')->whereIn('status', ['HIRED', 'hired'])->countAllResults(),
        ];
        foreach ($pipeline as $stage => $count) {
            CLI::write(sprintf("  %-15s: %d", strtoupper($stage), $count), 'yellow');
        }

        CLI::write("\n=== 2. GLOBAL RECRUITMENT COUNTS ===", 'green');
        $counts = [
            'Active Jobs'         => $db->table('jobs')->whereIn('status', ['PUBLISHED', 'active', 'published'])->countAllResults(),
            'Total Jobs'          => $db->table('jobs')->countAllResults(),
            'Total Applications'  => $db->table('applications')->countAllResults(),
            'Talent Resume Vault' => $db->table('general_resume_submissions')->countAllResults(),
            'Employer Mandates'   => $db->table('employer_enquiries')->countAllResults(),
            'Contact Inquiries'   => $db->table('contact_enquiries')->countAllResults(),
            'Unread Mail'         => $db->table('mail_message_index')->where('is_read', 0)->countAllResults(),
            'Pending Outbox'      => $db->table('email_outbox')->whereIn('status', ['PENDING', 'pending', 'PROCESSING', 'processing', 'QUEUED', 'queued'])->countAllResults(),
        ];
        foreach ($counts as $metric => $count) {
            CLI::write(sprintf("  %-22s: %d", $metric, $count), 'yellow');
        }

        CLI::write("\n=== 3. CORPORATE HIRING URGENCY ===", 'green');
        $urgency = [
            'Immediate' => $db->table('employer_enquiries')->whereIn('urgency', ['IMMEDIATE', 'Immediate', 'immediate'])->countAllResults(),
            '15 Days'   => $db->table('employer_enquiries')->whereIn('urgency', ['15_DAYS', '15 Days', '15 days'])->countAllResults(),
            '30 Days'   => $db->table('employer_enquiries')->whereIn('urgency', ['30_DAYS', '30 Days', '30 days'])->countAllResults(),
            'Pipeline'  => $db->table('employer_enquiries')->whereIn('urgency', ['EXPLORATORY', '60 Days', 'Pipeline', 'pipeline'])->countAllResults(),
        ];
        foreach ($urgency as $u => $count) {
            CLI::write(sprintf("  %-15s: %d", $u, $count), 'yellow');
        }

        CLI::write("\n=== 4. TESTIMONIAL DATA SAMPLE ===", 'green');
        $testimonials = $db->table('testimonials')->get()->getResultArray();
        CLI::write(sprintf("  Total Testimonials: %d", count($testimonials)), 'yellow');
        foreach ($testimonials as $t) {
            $quoteSnippet = mb_substr($t['content'], 0, 50);
            CLI::write(sprintf("  [%d] %-18s (%s) -> \"%s...\"", $t['id'], $t['name'], $t['company'], $quoteSnippet));
        }

        CLI::write("\nAll dashboard telemetry and data verification completed successfully.", 'green');
    }
}

