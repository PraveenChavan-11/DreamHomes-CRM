<?php

namespace App\Libraries;

/**
 * Delivery journey content for the two public services. Process copy only; makes no
 * guarantees about placement, joining dates, timelines or outcomes.
 * Each step: [title, description, output/deliverable, [activities...]]
 */
class ServiceJourneys
{
    public static function recruitment(): array
    {
        return [
            ['Discovery & Hiring Consultation', 'Understand the organisation, business objectives, open positions, team structure, hiring expectations and required timelines.', 'Confirmed hiring requirements', ['Organisation and team context', 'Open positions and priorities', 'Hiring expectations and timelines']],
            ['Job Description & Candidate Profile', 'Define responsibilities, experience, technical skills, qualifications, compensation expectations and suitability criteria.', 'Approved job description and candidate profile', ['Responsibilities and must-have skills', 'Qualifications and experience', 'Compensation expectations']],
            ['Talent Sourcing & Market Mapping', 'Identify suitable candidates through relevant sourcing channels, professional networks, databases and recruitment outreach.', 'Potential candidate pipeline', ['Channel and network sourcing', 'Database search', 'Candidate outreach']],
            ['Screening & Shortlisting', 'Review applications, evaluate relevant experience, conduct preliminary screening and assess alignment with the role.', 'Qualified shortlist', ['Application review', 'Preliminary screening conversations', 'Role-alignment assessment']],
            ['Interview Coordination', 'Coordinate interview schedules, share candidate information, manage communication and collect feedback from hiring teams.', 'Interview-ready candidates and feedback', ['Scheduling and reminders', 'Candidate information sharing', 'Feedback collection']],
            ['Selection & Offer Coordination', 'Support candidate selection, compensation discussions, offer communication and acceptance tracking.', 'Selected candidate and offer status', ['Selection support', 'Compensation discussions', 'Offer and acceptance tracking']],
            ['Joining & Onboarding Coordination', 'Coordinate joining formalities, documentation follow-ups and onboarding communication as agreed with the client.', 'Joining confirmation', ['Joining formalities', 'Documentation follow-ups', 'Onboarding communication']],
            ['Post-Placement Follow-up', 'Conduct agreed follow-ups, collect feedback and support the engagement through the agreed service period.', 'Recruitment closure and client feedback', ['Agreed follow-ups', 'Feedback collection', 'Engagement closure']],
        ];
    }

    public static function it(): array
    {
        return [
            ['Business Discovery & Consultation', 'Understand the business, operational challenges, goals, target audience and expected outcomes.', 'Discovery summary', ['Discovery meetings', 'Requirement discussions', 'Existing system assessment', 'Business problem identification']],
            ['Requirement Analysis & Scope Definition', 'Convert business requirements into functional and technical specifications.', 'Approved requirement specification and project scope', ['Requirement gathering', 'Functional and non-functional requirements', 'User roles and permissions', 'Feature prioritisation', 'Scope and dependency mapping']],
            ['Solution Architecture & Planning', 'Define the technology stack, system architecture, database design, integrations and implementation approach.', 'Technical architecture and implementation roadmap', ['Architecture design', 'Database and API planning', 'Security planning', 'Infrastructure assessment', 'Delivery roadmap']],
            ['Direct UI/UX Implementation', 'Design and build the production interface directly in the codebase: responsive layouts, components, typography, real assets, navigation, forms and interactions, reviewed on a staging environment rather than through separate mock-ups.', 'Fully implemented, functional pages available for review on staging', ['Responsive page layouts', 'Final visual components and sections', 'Typography, spacing, colour and hierarchy', 'Animations and micro-interactions', 'Desktop, tablet and mobile verification on staging']],
            ['Development & Implementation', 'Develop the frontend, backend, APIs, database integrations and required business workflows.', 'Functional application', ['Frontend and backend development', 'Database implementation', 'API integration', 'Authentication and authorisation', 'Admin functionality']],
            ['Quality Assurance & Testing', 'Validate the application before release.', 'Tested release candidate and UAT sign-off', ['Functional, API and integration testing', 'UI and responsive testing', 'Security validation and performance testing', 'User Acceptance Testing (UAT)', 'Defect resolution and regression testing']],
            ['Deployment & Go-Live', 'Prepare the production environment and release the approved application.', 'Production-ready application', ['Hosting, domain and SSL', 'Environment configuration', 'Database migrations', 'Production deployment', 'Backup verification and monitoring setup']],
            ['Support, Maintenance & Continuous Improvement', 'Support the application after launch through agreed maintenance and enhancement engagements. This is optional and separate from project delivery.', 'Ongoing application support and improvement', ['Bug fixes and security updates', 'Performance optimisation', 'Monitoring', 'Feature enhancements', 'Technical support']],
        ];
    }

    /** Fallback capability names if the services table is empty. */
    public static function fallbackCapabilities(string $slug): array
    {
        return $slug === 'recruitment'
            ? ['Permanent Recruitment', 'Contract Staffing', 'IT Recruitment', 'Non-IT Recruitment', 'Executive Search', 'Bulk Hiring', 'Campus Recruitment', 'Technical Recruitment', 'Recruitment Process Outsourcing (RPO)', 'Resume Screening and Shortlisting', 'Candidate Sourcing', 'Interview Coordination', 'Workforce Planning']
            : ['Custom Software Development', 'Website Development', 'Web Application Development', 'Enterprise Portal Development', 'Mobile Application Development', 'Progressive Web Applications', 'React and Modern Frontend Development', 'PHP and CodeIgniter Development', 'Python and Django Development', 'REST API Development and Integration', 'Backend System Development', 'Database Architecture', 'Cloud Application Integration', 'Legacy System Modernisation', 'ERP and HRMS Workflows', 'Business Process Automation', 'Quality Assurance and Software Testing', 'Technical SEO and Website Optimisation'];
    }
}
