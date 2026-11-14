<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Replaces the Pune-only SEO meta that was auto-seeded into website_pages with India-wide meta.
 *
 * Safety:
 *  - Only rows that still look like untouched seed data are changed (version 1 and the exact seeded title).
 *    Anything an admin has edited is left alone.
 *  - Old values are copied to website_pages_meta_backup first (never overwritten once saved).
 *  - down() restores a row only if it still holds the value this migration wrote.
 *  - Nothing is deleted from website_pages.
 */
class IndiaWidePageMeta extends Migration
{
    /** Exact titles the old seeder wrote. A row is only changed while its title still equals this. */
    private const OLD_TITLES = [
        'home' => 'TalentHub Consultant | Leading Job Consultancy in Pune',
        'about' => 'About Us | Top Recruitment Consultancy in Pune | TalentHub',
        'solutions' => 'Recruitment Solutions & Staffing Tracks | TalentHub Pune',
        'employers' => 'Hire Top Talent in Pune | Corporate Recruitment | TalentHub',
        'candidates' => 'Pune Job Opportunities & Career Advisory | TalentHub',
        'jobs' => 'Current Job Vacancies in Pune | IT & Core Roles | TalentHub',
        'career-enquiry' => 'Submit Resume | Confidential Pune Talent Vault | TalentHub',
        'insights' => 'Pune Hiring Market Trends & Salary Insights | TalentHub',
        'interviews' => 'Interview Preparation & Career Guidance | TalentHub Pune',
        'contact' => 'Contact TalentHub | Recruitment Office Katraj, Pune Desk',
        'faq' => 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune',
        'terms' => 'Terms of Service & Advisory Conditions | TalentHub Pune',
    ];

    private const META = [
        'home' => ['TalentHub | Recruitment, IT Services & Digital Transformation in India',
            'TalentHub is a Pan-India recruitment and technology consulting partner: talent acquisition, software development, ERP/HRMS, QA and testing, and digital transformation.',
            'recruitment consultancy India, pan India recruitment services, IT recruitment, technology consulting, software development company, digital transformation'],
        'about' => ['About TalentHub | Recruitment & Technology Consulting Across India',
            'Learn about TalentHub: Pan-India recruitment and technology consulting with an operations base in Pune, Maharashtra.',
            'about TalentHub, recruitment and technology consulting India, IT staffing, software services'],
        'solutions' => ['Recruitment Solutions & Staffing Services | TalentHub India',
            'Permanent, contract, IT and executive recruitment for organisations across India, with sourcing, screening and interview coordination.',
            'recruitment solutions India, IT staffing services, contract staffing, executive search India'],
        'employers' => ['Hire Talent Across India | Corporate Recruitment | TalentHub',
            'Share your hiring requirement with TalentHub: IT and non-IT, permanent and contract hiring for startups, SMEs and enterprises across India.',
            'hire talent India, employer recruitment services, IT staffing, corporate hiring partner'],
        'candidates' => ['Job Opportunities & Career Guidance Across India | TalentHub',
            'Explore verified roles across India. Register your resume with TalentHub; candidate representation is free of charge.',
            'jobs in India, career guidance, IT jobs, submit resume, candidate registration'],
        'jobs' => ['Current Job Openings Across India | IT & Non-IT Roles | TalentHub',
            'Browse active job openings across India in software, engineering, finance, operations and management. Apply directly with TalentHub.',
            'job vacancies India, current openings, IT jobs, engineering jobs, jobs through consultancy'],
        'career-enquiry' => ['Submit Your Resume | Confidential Talent Pool | TalentHub',
            'Share your CV with TalentHub confidentially and be considered for roles across India.',
            'submit resume India, upload CV, candidate registration, confidential job search'],
        'insights' => ['Recruitment & Technology Insights | TalentHub India',
            'Hiring trends, salary insights and technology articles from TalentHub for business leaders and professionals across India.',
            'recruitment insights India, hiring trends, salary guide, technology consulting articles'],
        'interviews' => ['Interview Preparation & Career Guidance | TalentHub',
            'Interview strategies, technical assessment tips and negotiation guidance from the TalentHub team.',
            'interview tips, technical interview preparation, career guidance'],
        'contact' => ['Contact TalentHub | Recruitment & Technology Consulting',
            'Contact TalentHub for hiring requirements, technology projects and consulting enquiries. Operations base: Pune, Maharashtra; serving clients across India.',
            'contact TalentHub, recruitment enquiry, technology consulting enquiry, Pune operations base'],
        'faq' => ['Frequently Asked Questions | Hiring & Services | TalentHub',
            'Answers about recruitment processes, candidate registration, employer engagement and technology services at TalentHub.',
            'recruitment FAQ India, hiring process questions, technology services FAQ'],
        'terms' => ['Terms of Service & Conditions | TalentHub',
            'Terms of service governing the use of the TalentHub website and services.',
            'terms of service TalentHub, user agreement'],
        'application-tracker' => ['Track Your Job Application | TalentHub',
            'Check the progress of your job application with TalentHub.',
            'track application status, job application tracker'],
        'request-cv-deletion' => ['Request CV Deletion | DPDP Right to Erasure | TalentHub',
            'Request deletion of your resume and personal data held by TalentHub under India\'s Digital Personal Data Protection Act.',
            'delete resume DPDP, right to erasure, personal data deletion request'],
    ];

    public function up()
    {
        if (!$this->db->tableExists('website_pages')) {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `website_pages_meta_backup` (
            `slug` VARCHAR(100) NOT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` TEXT NULL,
            `meta_keywords` VARCHAR(255) NULL,
            `applied_meta_title` VARCHAR(255) NULL,
            `backed_up_at` DATETIME NOT NULL,
            PRIMARY KEY (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        foreach (self::META as $slug => [$title, $desc, $kw]) {
            $row = $this->db->table('website_pages')->where('slug', $slug)->get()->getRowArray();
            if (!$row) {
                continue;
            }
            // Only rows still carrying the exact seeded Pune title (or, for pages that were
            // seeded without a Pune title, still mentioning Pune) at version 1 are changed.
            $seededTitle = self::OLD_TITLES[$slug] ?? null;
            $untouched = (int) ($row['version'] ?? 1) === 1
                && ($seededTitle !== null
                    ? ($row['meta_title'] ?? '') === $seededTitle
                    : stripos(($row['meta_title'] ?? '') . ($row['meta_description'] ?? '') . ($row['meta_keywords'] ?? ''), 'Pune') !== false
                      && stripos($row['meta_title'] ?? '', 'Pune') === false);
            if (!$untouched) {
                continue;
            }
            $this->db->query('INSERT IGNORE INTO website_pages_meta_backup (slug, meta_title, meta_description, meta_keywords, applied_meta_title, backed_up_at) VALUES (?,?,?,?,?,NOW())',
                [$slug, $row['meta_title'], $row['meta_description'], $row['meta_keywords'], $title]);
            $this->db->table('website_pages')->where('slug', $slug)->update([
                'meta_title' => $title, 'meta_description' => $desc, 'meta_keywords' => $kw,
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('website_pages_meta_backup') || !$this->db->tableExists('website_pages')) {
            return;
        }
        foreach ($this->db->table('website_pages_meta_backup')->get()->getResultArray() as $b) {
            // Restore only if nobody has edited the title since this migration wrote it.
            $this->db->table('website_pages')->where('slug', $b['slug'])->where('meta_title', $b['applied_meta_title'])->update([
                'meta_title' => $b['meta_title'], 'meta_description' => $b['meta_description'], 'meta_keywords' => $b['meta_keywords'],
            ]);
        }
        $this->db->query('DROP TABLE IF EXISTS `website_pages_meta_backup`');
    }
}
