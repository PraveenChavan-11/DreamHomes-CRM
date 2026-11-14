<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
if ($mysqli->connect_errno) {
    echo "Connection error: " . $mysqli->connect_error . "\n";
    exit(1);
}

$pages = [
    [
        'slug'             => 'home',
        'title'            => 'Home — TalentHub Consultant',
        'meta_title'       => 'TalentHub Consultant | Leading Job Consultancy in Pune',
        'meta_description' => "TalentHub Consultant connects top engineering, IT, and leadership talent with Pune's leading corporate enterprises across Hinjawadi, Kharadi, and Chakan.",
        'meta_keywords'    => 'job consultancy Pune, recruitment agency Pune, placement consultants Pune, IT jobs Hinjawadi, recruitment agency Katraj',
    ],
    [
        'slug'             => 'about',
        'title'            => 'About Us — TalentHub Consultant',
        'meta_title'       => 'About Us | Top Recruitment Consultancy in Pune | TalentHub',
        'meta_description' => "Learn about TalentHub Consultant, Pune's trusted recruitment partner. Discover our ethical executive search, corporate staffing SLAs, and proven hiring record.",
        'meta_keywords'    => 'about TalentHub, Pune recruitment agency, executive search Pune, staffing firm Katraj, corporate headhunters Pune',
    ],
    [
        'slug'             => 'solutions',
        'title'            => 'Recruitment Solutions & Staffing Tracks',
        'meta_title'       => 'Recruitment Solutions & Staffing Tracks | TalentHub Pune',
        'meta_description' => 'Tailored recruitment solutions in Pune: IT staffing, engineering mandates, executive search, and turnkey volume hiring for Maharashtra corporate enterprises.',
        'meta_keywords'    => 'staffing solutions Pune, IT recruitment Pune, engineering hiring Chakan, executive headhunting Pune',
    ],
    [
        'slug'             => 'employers',
        'title'            => 'Employers & Corporate Staffing',
        'meta_title'       => 'Hire Top Talent in Pune | Corporate Recruitment | TalentHub',
        'meta_description' => 'Fast-track corporate hiring in Pune. TalentHub delivers pre-screened technical talent and executive leadership profiles to employers within 7 business days.',
        'meta_keywords'    => 'hire talent Pune, employer recruitment Pune, IT staffing Hinjawadi, corporate hiring partner Pune',
    ],
    [
        'slug'             => 'candidates',
        'title'            => 'Candidates & Career Guidance',
        'meta_title'       => 'Pune Job Opportunities & Career Advisory | TalentHub',
        'meta_description' => 'Explore verified career opportunities across Pune tech parks and manufacturing belts. Free candidate representation, salary benchmarks, and interview prep.',
        'meta_keywords'    => 'jobs in Pune, career guidance Pune, tech jobs Hinjawadi, engineering jobs Chakan, candidate placement Pune',
    ],
    [
        'slug'             => 'jobs',
        'title'            => 'Current Job Openings in Pune',
        'meta_title'       => 'Current Job Vacancies in Pune | IT & Core Roles | TalentHub',
        'meta_description' => 'Explore active job openings in Pune across software engineering, automotive, finance, and corporate management. Direct application with zero candidate fees.',
        'meta_keywords'    => 'job vacancies Pune, current openings Hinjawadi, Pune IT jobs, engineering recruitment Pune, walkin jobs Pune',
    ],
    [
        'slug'             => 'career-enquiry',
        'title'            => 'Career Desk & Resume Submission',
        'meta_title'       => 'Submit Resume | Confidential Pune Talent Vault | TalentHub',
        'meta_description' => "Upload your CV to TalentHub's private Pune candidate vault. Get matched with unadvertised leadership and technical mandates across Hinjawadi and Chakan.",
        'meta_keywords'    => 'submit resume Pune, upload CV Hinjawadi, candidate registration Pune, confidential job search Pune',
    ],
    [
        'slug'             => 'insights',
        'title'            => 'Pune Recruitment Insights & Trends',
        'meta_title'       => 'Pune Hiring Market Trends & Salary Insights | TalentHub',
        'meta_description' => 'Stay informed with Pune recruitment intelligence, engineering salary benchmarks, Hinjawadi tech hiring indexes, and quarterly corporate compensation reports.',
        'meta_keywords'    => 'Pune salary guide, IT hiring trends Pune, Hinjawadi tech salaries, recruitment insights Maharashtra',
    ],
    [
        'slug'             => 'interviews',
        'title'            => 'Interview Preparation & Guidance',
        'meta_title'       => 'Interview Preparation & Career Guidance | TalentHub Pune',
        'meta_description' => "Expert interview strategies, technical assessment tips, and executive salary negotiation guidance from TalentHub's veteran Pune recruitment consultants.",
        'meta_keywords'    => 'interview tips Pune, technical interview prep, career coaching Pune, executive interview guidance',
    ],
    [
        'slug'             => 'contact',
        'title'            => 'Contact Us & Office Headquarters',
        'meta_title'       => 'Contact TalentHub | Recruitment Office Katraj, Pune Desk',
        'meta_description' => 'Get in touch with TalentHub Consultant in Katraj, Pune. Reach our corporate hiring desk via phone +91 72761 46573, WhatsApp, email, or in-person advisory.',
        'meta_keywords'    => 'TalentHub office Pune, recruitment contact Katraj, staffing agency phone number, hiring consultancy Pune',
    ],
    [
        'slug'             => 'faq',
        'title'            => 'Frequently Asked Questions',
        'meta_title'       => 'Frequently Asked Questions | Hiring FAQs | TalentHub Pune',
        'meta_description' => 'Common questions on corporate staffing SLAs, candidate representation, hiring turnaround, and recruitment processes with TalentHub Consultant across Pune.',
        'meta_keywords'    => 'recruitment FAQ Pune, hiring process questions, candidate consultancy fees Pune, staffing FAQs',
    ],
    [
        'slug'             => 'privacy',
        'title'            => 'Privacy Policy & DPDP Governance',
        'meta_title'       => 'Privacy Policy & India DPDP Act Compliance | TalentHub',
        'meta_description' => 'TalentHub Consultant privacy policy, data protection governance, affirmative consent practices, and statutory India DPDP Act compliance protocols for users.',
        'meta_keywords'    => 'privacy policy TalentHub, India DPDP Act, data protection recruitment, candidate privacy rights',
    ],
    [
        'slug'             => 'terms',
        'title'            => 'Terms of Service & Conditions',
        'meta_title'       => 'Terms of Service & Advisory Conditions | TalentHub Pune',
        'meta_description' => 'Terms of service governing the use of TalentHub Consultant recruitment portal, job applications, employer mandate agreements, and staffing advisory services.',
        'meta_keywords'    => 'terms of service TalentHub, user agreement recruitment, employer terms Pune, job application conditions',
    ],
    [
        'slug'             => 'application-tracker',
        'title'            => 'Candidate Application Status Tracker',
        'meta_title'       => 'Track Job Application Status | Candidate Portal | TalentHub',
        'meta_description' => 'Check real-time application progress, interview schedules, and screening updates for your submitted Pune job applications with your email and reference ID.',
        'meta_keywords'    => 'track application status, Pune job application tracker, interview status lookup, candidate application progress',
    ],
    [
        'slug'             => 'request-cv-deletion',
        'title'            => 'Statutory CV Erasure Request (DPDP Act)',
        'meta_title'       => 'Request CV Deletion | DPDP Right to Erasure | TalentHub',
        'meta_description' => "Exercise your statutory Right to Erasure under the India DPDP Act. Permanently shred and delete your resume files from TalentHub's private document vault.",
        'meta_keywords'    => 'delete resume DPDP, right to erasure Pune, candidate data deletion, remove CV TalentHub',
    ],
];

foreach ($pages as $p) {
    $slug = $mysqli->real_escape_string($p['slug']);
    $title = $mysqli->real_escape_string($p['title']);
    $meta_title = $mysqli->real_escape_string($p['meta_title']);
    $meta_desc = $mysqli->real_escape_string($p['meta_description']);
    $meta_kw = $mysqli->real_escape_string($p['meta_keywords']);

    $check = $mysqli->query("SELECT id FROM website_pages WHERE slug = '{$slug}'");
    if ($check->num_rows > 0) {
        $mysqli->query("UPDATE website_pages SET title = '{$title}', meta_title = '{$meta_title}', meta_description = '{$meta_desc}', meta_keywords = '{$meta_kw}', status = 'PUBLISHED', updated_at = NOW() WHERE slug = '{$slug}'");
        echo "UPDATED: [{$slug}] - MetaTitle: " . strlen($p['meta_title']) . " chars | MetaDesc: " . strlen($p['meta_description']) . " chars\n";
    } else {
        $mysqli->query("INSERT INTO website_pages (slug, title, meta_title, meta_description, meta_keywords, status, version, created_at, updated_at) VALUES ('{$slug}', '{$title}', '{$meta_title}', '{$meta_desc}', '{$meta_kw}', 'PUBLISHED', 1, NOW(), NOW())");
        echo "INSERTED: [{$slug}] - MetaTitle: " . strlen($p['meta_title']) . " chars | MetaDesc: " . strlen($p['meta_description']) . " chars\n";
    }
}

echo "All 15 pages seeded and updated successfully.\n";

