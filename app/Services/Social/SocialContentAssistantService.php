<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise Social Content Assistant Service
 *
 * Provides intelligent template-driven and optional AI-assisted caption generation,
 * platform adaptation (LinkedIn vs Instagram), hashtag suggestions, headline formulation,
 * and CTA recommendations across TalentHub's 15 business verticals with 7 tone presets.
 *
 * Free-first architecture: Works 100% offline out-of-the-box without paid AI API dependencies.
 */
class SocialContentAssistantService
{
    private BaseConnection $db;

    public const CATEGORIES = [
        'Recruitment & Hiring',
        'HR Consulting',
        'IT Services',
        'Software Development',
        'Website Development',
        'Mobile Applications',
        'HRMS / WorkSphere',
        'Digital Transformation',
        'Business Automation',
        'API Integration',
        'Technology Insights',
        'Company Updates',
        'Client Success Stories',
        'Educational Content',
        'Job Opportunities',
    ];

    public const TONES = [
        'Professional',
        'Corporate',
        'Informative',
        'Engaging',
        'Thought Leadership',
        'Promotional',
        'Educational',
    ];

    public const PLATFORM_HASHTAGS = [
        'Recruitment & Hiring' => ['#TalentAcquisition', '#TechHiring', '#ExecutiveSearch', '#RecruitmentAdvisory', '#TalentHub'],
        'HR Consulting' => ['#HRConsulting', '#WorkplaceStrategy', '#Compliance', '#PeopleOperations', '#HRLeadership'],
        'IT Services' => ['#ITServices', '#CloudInfrastructure', '#EnterpriseTech', '#DigitalConsulting', '#TechSolutions'],
        'Software Development' => ['#SoftwareEngineering', '#FullStack', '#CodeArchitecture', '#AgileDevelopment', '#CleanCode'],
        'Website Development' => ['#WebDevelopment', '#ResponsiveWeb', '#FrontendDesign', '#WebPerformance', '#TalentHubTech'],
        'Mobile Applications' => ['#MobileAppDev', '#iOSDevelopment', '#AndroidDev', '#CrossPlatform', '#MobileUX'],
        'HRMS / WorkSphere' => ['#WorkSphere', '#HRMS', '#PayrollAutomation', '#BiometricIntegration', '#WorkforceManagement'],
        'Digital Transformation' => ['#DigitalTransformation', '#BusinessModernization', '#CloudMigration', '#TechStrategy'],
        'Business Automation' => ['#BusinessAutomation', '#ProcessEfficiency', '#WorkflowOptimization', '#AutomationTools'],
        'API Integration' => ['#APIIntegration', '#SystemArchitecture', '#Microservices', '#EnterpriseIntegration'],
        'Technology Insights' => ['#TechInsights', '#IndustryTrends', '#EngineeringStrategy', '#FutureOfTech'],
        'Company Updates' => ['#TalentHubUpdates', '#CompanyMilestones', '#Leadership', '#TeamGrowth'],
        'Client Success Stories' => ['#ClientSuccess', '#DigitalImpact', '#CaseStudy', '#EngineeringExcellence'],
        'Educational Content' => ['#TechGuide', '#BestPractices', '#DeveloperTips', '#TechEducation'],
        'Job Opportunities' => ['#HiringNow', '#TechCareers', '#JobOpening', '#OpportunityAlert', '#CareerGrowth'],
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Generate content recommendations based on category, tone, objective, and platform.
     */
    public function generateSuggestions(string $category, string $tone = 'Professional', ?string $topic = null, string $platform = 'both'): array
    {
        $category = in_array($category, self::CATEGORIES, true) ? $category : 'IT Services';
        $tone     = in_array($tone, self::TONES, true) ? $tone : 'Professional';
        $topic    = trim((string) $topic);

        // Fetch matching database templates first
        $dbTemplate = $this->db->table('social_content_templates')
            ->where('category', $category)
            ->where('is_active', 1)
            ->orderBy('id', 'RANDOM')
            ->get()
            ->getRowArray();

        $baseCaption = $dbTemplate['caption_template'] ?? $this->composeAlgorithmicCaption($category, $tone, $topic);
        $hashtags    = $dbTemplate['hashtag_group'] ?? implode(' ', self::PLATFORM_HASHTAGS[$category] ?? ['#TalentHub']);
        $cta         = $dbTemplate['cta_template'] ?? $this->suggestCta($category, $tone);

        // Adapt for LinkedIn
        $linkedInCaption = $this->adaptForLinkedIn($baseCaption, $category, $tone, $topic);
        $linkedInHashtags = implode(' ', array_slice(explode(' ', $hashtags), 0, 5));

        // Adapt for Instagram
        $instagramCaption = $this->adaptForInstagram($baseCaption, $category, $tone, $topic);
        $instagramHashtags = $hashtags . ' ' . implode(' ', ['#TechCommunity', '#CareerGrowth', '#WorkSmart']);

        $headline = $this->generateHeadline($category, $topic, $tone);

        return [
            'headline' => $headline,
            'category' => $category,
            'tone'     => $tone,
            'base'     => [
                'caption'  => $baseCaption,
                'hashtags' => $hashtags,
                'cta'      => $cta,
            ],
            'linkedin' => [
                'caption'     => $linkedInCaption,
                'hashtags'    => $linkedInHashtags,
                'char_count'  => mb_strlen($linkedInCaption, 'UTF-8'),
                'recommended' => 'Use desktop carousel PDF or single high-res graphic for maximum engagement.',
            ],
            'instagram' => [
                'caption'     => $instagramCaption,
                'hashtags'    => $instagramHashtags,
                'char_count'  => mb_strlen($instagramCaption, 'UTF-8'),
                'recommended' => 'Pair with a clean 1:1 or 4:5 vertical carousel and clear visual slide hooks.',
            ],
            'suggested_ctas' => $this->getSuggestedCtas($category),
        ];
    }

    /**
     * Rewrite or polish existing caption according to a selected tone.
     */
    public function rewriteCaption(string $text, string $targetTone = 'Professional', string $platform = 'both'): array
    {
        $clean = trim($text);
        if ($clean === '') {
            return ['rewritten' => '', 'char_count' => 0];
        }

        $rewritten = $clean;
        switch ($targetTone) {
            case 'Corporate':
                $rewritten = "Enterprise Overview:\n\n" . $clean . "\n\nLearn how TalentHub empowers scalable organizational execution.";
                break;
            case 'Engaging':
                $rewritten = "💡 Strategic Perspective:\n\n" . $clean . "\n\nWhat are your thoughts on this approach? Let's discuss in the comments below! 👇";
                break;
            case 'Thought Leadership':
                $rewritten = "As the technology and recruitment landscape shifts rapidly, one principle remains constant:\n\n" . $clean . "\n\nHow is your leadership team approaching this in your quarterly roadmap?";
                break;
            case 'Educational':
                $rewritten = "📌 Key Insights & Takeaways:\n\n" . $clean . "\n\nSave this post for your team's next planning session.";
                break;
            case 'Promotional':
                $rewritten = $clean . "\n\nReady to transform your tech capabilities? Explore TalentHub's verified solutions today.";
                break;
            case 'Professional':
            default:
                $rewritten = $clean . "\n\nConnect with TalentHub for strategic recruitment and enterprise IT advisory.";
                break;
        }

        return [
            'original'   => $text,
            'rewritten'  => $rewritten,
            'tone'       => $targetTone,
            'char_count' => mb_strlen($rewritten, 'UTF-8'),
        ];
    }

    private function composeAlgorithmicCaption(string $category, string $tone, string $topic): string
    {
        $subject = $topic !== '' ? $topic : $category;

        switch ($category) {
            case 'Recruitment & Hiring':
                return "Connecting high-growth enterprises with specialized technology talent.\n\nFinding the right technical fit requires more than resume filtering; it demands rigorous technical vetting, cultural alignment, and domain understanding.\n\nAt TalentHub, our dedicated recruitment advisory accelerates hiring cycles while maintaining uncompromising standards for engineering quality.\n\nLooking to scale your technical bench? Let's discuss your hiring requirements.";

            case 'HRMS / WorkSphere':
                return "Say goodbye to fragmented workforce spreadsheets and disconnected payroll tools.\n\nWorkSphere by TalentHub delivers automated biometric attendance tracking, seamless compliance calculations, and employee self-service in one unified workspace.\n\nEngineered for modern enterprise teams that prioritize accuracy and administrative efficiency.\n\nExplore our live interactive dashboard and schedule a walkthrough today.";

            case 'IT Services':
            case 'Software Development':
                return "Building fault-tolerant digital infrastructure for forward-thinking organizations.\n\nFrom high-performance web platforms to secure microservice integrations, TalentHub IT Services bridges strategic business objectives with modern engineering execution.\n\nNeed technical advisory or dedicated engineering delivery? Connect with our solution architects today.";

            case 'Technology Insights':
                return "Engineering efficiency isn't just about writing code faster—it's about removing architectural bottlenecks and investing in maintainable systems.\n\nOrganizations that foster clear technical ownership and streamlined deployment workflows consistently deliver higher-quality product iterations.\n\nWhat engineering priorities are guiding your team's roadmap this quarter?";

            default:
                return "Empowering organizations through strategic technology consulting and verified recruitment solutions.\n\nAt TalentHub, we partner with enterprises to solve complex workforce and digital infrastructure challenges.\n\nDiscover how our advisory can support your organizational growth.";
        }
    }

    private function adaptForLinkedIn(string $base, string $category, string $tone, string $topic): string
    {
        return $base;
    }

    private function adaptForInstagram(string $base, string $category, string $tone, string $topic): string
    {
        // Add visual spacing and friendly emoji hooks for Instagram audience
        $lines = explode("\n", $base);
        $igLines = [];
        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if ($i === 0 && $trimmed !== '') {
                $igLines[] = "✨ " . $trimmed;
            } else {
                $igLines[] = $trimmed;
            }
        }
        $igLines[] = "";
        $igLines[] = "👉 Link in bio to explore more!";
        return implode("\n", $igLines);
    }

    private function generateHeadline(string $category, string $topic, string $tone): string
    {
        if ($topic !== '') {
            return "Strategic Advisory: {$topic}";
        }
        $headlines = [
            'Recruitment & Hiring'   => 'Scaling Elite Technical Teams With Precision',
            'HRMS / WorkSphere'      => 'Automate Your Workforce Operations With WorkSphere HRMS',
            'IT Services'            => 'Enterprise Custom Software & Cloud Infrastructure',
            'Software Development'   => 'Modern Full-Stack Engineering & Scalable Architecture',
            'Technology Insights'    => 'Key Architecture & Talent Strategy Trends',
        ];
        return $headlines[$category] ?? "TalentHub — {$category} Spotlight";
    }

    private function suggestCta(string $category, string $tone): string
    {
        if ($category === 'HRMS / WorkSphere') {
            return 'Explore WorkSphere HRMS';
        }
        if (str_contains($category, 'Recruitment') || str_contains($category, 'Hiring')) {
            return 'Schedule Talent Consultation';
        }
        if (str_contains($category, 'IT') || str_contains($category, 'Software')) {
            return 'Request Technical Proposal';
        }
        return 'Connect With TalentHub';
    }

    public function getSuggestedCtas(string $category): array
    {
        return [
            'Connect With TalentHub Advisory',
            'Explore WorkSphere HRMS',
            'Request Dedicated Consultation',
            'View Open Tech Roles',
            'Schedule Executive Demo',
            'Learn More at talenthubconsultant.com',
        ];
    }
}

