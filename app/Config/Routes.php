<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── 1. PUBLIC SERVER-RENDERED WEB ROUTES ─────────────────────────────────────
$routes->get('/', 'Web\HomeController::index');

// Jobs & Vacancies
$routes->get('jobs', 'Web\JobsController::index');
$routes->get('jobs/(:segment)', 'Web\JobsController::show/$1');
$routes->post('jobs/(:segment)/apply', 'Web\JobsController::submitApplication/$1', ['filter' => 'ratelimit:10,60']);

// Service catalogue & products
$routes->get('services', 'Web\ServicesController::index');
$routes->get('services/(:segment)', 'Web\ServicesController::category/$1');
// Legacy URLs: capability pages and the retired products catalogue (301 to the two-service structure)
$routes->get('services/(:segment)/(:segment)', 'Web\ServicesController::legacyService/$1/$2');
// ── Product catalogue (public). Old SocietyHub/PeopleHub slugs still 301 via ProductsController::show. ──
$routes->get('products', 'Web\ProductsController::index');
$routes->post('products/(:segment)/enquiry', 'Web\ProductsController::enquiry/$1', ['filter' => 'ratelimit:10,60']);
$routes->get('products/(:segment)', 'Web\ProductsController::show/$1');

// IT project enquiries (feeds the IT Services CRM)
$routes->get('case-studies', 'Web\CaseStudiesController::index');
$routes->get('case-studies/(:segment)', 'Web\CaseStudiesController::show/$1');
$routes->get('project-enquiry', 'Web\ProjectEnquiryController::index');
$routes->post('project-enquiry/submit', 'Web\ProjectEnquiryController::submit', ['filter' => 'ratelimit:10,60']);

// About & Solutions
$routes->get('about', 'Web\AboutController::index');
$routes->get('solutions', 'Web\SolutionsController::index');

// Employers (B2B)
$routes->get('employers', 'Web\EmployersController::index');
$routes->post('employers/enquiry', 'Web\EmployersController::submitEnquiry', ['filter' => 'ratelimit:10,60']);

// Candidates & Resume Submission
$routes->get('candidates', 'Web\CandidatesController::index');
$routes->get('career-enquiry', 'Web\CareerEnquiryController::index');
$routes->post('career-enquiry/submit', 'Web\CareerEnquiryController::submit', ['filter' => 'ratelimit:10,60']);

// Insights (Blog)
$routes->get('insights', 'Web\InsightsController::index');
$routes->get('insights/(:segment)', 'Web\InsightsController::show/$1');

// Virtual Interviews
$routes->get('interviews', 'Web\InterviewsController::index');

// Contact Desk
$routes->get('contact', 'Web\ContactController::index');
$routes->post('contact/submit', 'Web\ContactController::submit', ['filter' => 'ratelimit:10,60']);

// Submission Status Pages (Thank You & Failure Notices)
$routes->get('thank-you', 'Web\SubmissionStatusController::thankYou');
$routes->get('submission-failed', 'Web\SubmissionStatusController::failed');

// Candidate Self-Service Application Tracker & DPDP Right to Erasure (GAP-06 & GAP-07)
$routes->match(['get', 'post'], 'application/tracker', 'Web\CandidateComplianceController::trackApplication', ['filter' => 'ratelimit:20,60']);
$routes->match(['get', 'post'], 'privacy/request-cv-deletion', 'Web\CandidateComplianceController::requestCvDeletion', ['filter' => 'ratelimit:10,60']);
$routes->get('privacy/confirm-cv-deletion', 'Web\CandidateComplianceController::confirmCvDeletion');

// Legal & FAQ
$routes->get('faq', 'Web\FaqController::index');
$routes->get('privacy', 'Web\LegalController::privacy');
$routes->get('terms', 'Web\LegalController::terms');

// Engagement & Newsletter Public Web Endpoints
$routes->get('newsletter/verify', 'Web\NewsletterController::verify');
$routes->get('newsletter/unsubscribe', 'Web\NewsletterController::unsubscribe');
$routes->get('newsletter/preferences', 'Web\NewsletterController::preferences');
$routes->get('job-alerts/unsubscribe', 'Web\JobAlertController::unsubscribe');

// Dynamic Robots, Agent Endpoints & XML Sitemaps
$routes->get('robots.txt', 'Web\LegalController::robots');
$routes->get('llms.txt', 'Web\LegalController::llmsTxt');
$routes->get('llms-full.txt', 'Web\LegalController::llmsFullTxt');
$routes->get('.well-known/mcp/server-card.json', 'Web\LegalController::mcpServerCard');
$routes->get('sitemap.xml', 'Web\SitemapController::index');
$routes->get('sitemap-pages.xml', 'Web\SitemapController::pages');
$routes->get('sitemap-jobs.xml', 'Web\SitemapController::jobs');
$routes->get('sitemap-insights.xml', 'Web\SitemapController::insights');
$routes->get('sitemap-news.xml', 'Web\SitemapController::news');

// ─── 2. ADMIN AUTHENTICATION & PORTAL ROUTES ──────────────────────────────────
$routes->match(['get', 'post'], 'admin/login', 'Admin\AuthController::login', ['filter' => 'ratelimit:20,60']);
$routes->match(['get', 'post'], 'admin/logout', 'Admin\AuthController::logout');
$routes->match(['get', 'post'], 'admin/forgot-password', 'Admin\AuthController::forgotPassword', ['filter' => 'ratelimit:10,60']);
$routes->match(['get', 'post'], 'admin/reset-password', 'Admin\AuthController::resetPassword', ['filter' => 'ratelimit:10,60']);
$routes->match(['get', 'post'], 'admin/verify-otp', 'Admin\AuthController::verifyOtp', ['filter' => 'ratelimit:20,60']);
$routes->post('admin/resend-otp', 'Admin\AuthController::resendOtp', ['filter' => 'ratelimit:5,60']);
$routes->get('admin/cancel-otp', 'Admin\AuthController::cancelOtp');

// Admin Portal Native Dynamic Routes (Protected by Auth Filter)
$routes->group('admin', ['filter' => 'auth'], static function ($routes) {
    // Dashboard
    $routes->get('', 'Admin\DashboardController::index');
    $routes->get('dashboard', 'Admin\DashboardController::index');
    $routes->get('dashboard/notifications', 'Admin\DashboardController::pollNotifications');

    // Job Postings
    $routes->get('jobs', 'Admin\JobsController::index');
    $routes->get('jobs/new', 'Admin\JobsController::new');
    $routes->post('jobs/create', 'Admin\JobsController::create');
    $routes->get('jobs/edit/(:num)', 'Admin\JobsController::edit/$1');
    $routes->post('jobs/update/(:num)', 'Admin\JobsController::update/$1');
    $routes->post('jobs/toggle/(:num)', 'Admin\JobsController::toggleStatus/$1');
    $routes->post('jobs/delete/(:num)', 'Admin\JobsController::delete/$1');

    // Candidate Applications
    $routes->get('applications', 'Admin\ApplicationsController::index');
    $routes->post('applications/status/(:num)', 'Admin\ApplicationsController::updateStatus/$1');
    $routes->post('applications/bulk-action', 'Admin\ApplicationsController::bulkAction');
    $routes->get('applications/resume/(:num)', 'Admin\ApplicationsController::downloadResume/$1');

    // General Resume Bank
    $routes->get('resume-bank', 'Admin\ResumeBankController::index');
    $routes->post('resume-bank/status/(:num)', 'Admin\ResumeBankController::updateStatus/$1');
    $routes->get('resume-bank/resume/(:num)', 'Admin\ResumeBankController::downloadResume/$1');

    // Employer Enquiries
    $routes->get('enquiries/employers', 'Admin\EnquiriesController::employers');
    $routes->post('enquiries/employers/status/(:num)', 'Admin\EnquiriesController::updateEmployerStatus/$1');

    // Contact Messages
    $routes->get('enquiries/contact', 'Admin\EnquiriesController::contact');
    $routes->post('enquiries/contact/status/(:num)', 'Admin\EnquiriesController::updateContactStatus/$1');

    // Hostinger Mail Center
    $routes->get('mail', 'Admin\MailController::index');
    $routes->get('mail/view/(:num)', 'Admin\MailController::view/$1');
    $routes->get('mail/compose', 'Admin\MailController::compose');
    $routes->post('mail/send', 'Admin\MailController::send');
    $routes->post('mail/sync', 'Admin\MailController::sync');
    $routes->get('mail/settings', 'Admin\MailController::settings');
    $routes->post('mail/settings', 'Admin\MailController::updateSettings');

    // Engagement Centre (Master Architecture)
    $routes->get('engagement', 'Admin\EngagementController::index');
    $routes->get('engagement/announcements', 'Admin\EngagementController::announcements');
    $routes->get('engagement/announcements/new', 'Admin\EngagementController::newAnnouncement');
    $routes->post('engagement/announcements/store', 'Admin\EngagementController::storeAnnouncement');
    $routes->get('engagement/announcements/edit/(:num)', 'Admin\EngagementController::editAnnouncement/$1');
    $routes->post('engagement/announcements/update/(:num)', 'Admin\EngagementController::updateAnnouncement/$1');
    $routes->post('engagement/announcements/toggle/(:num)', 'Admin\EngagementController::toggleAnnouncementStatus/$1');
    $routes->post('engagement/announcements/duplicate/(:num)', 'Admin\EngagementController::duplicateAnnouncement/$1');
    $routes->post('engagement/announcements/delete/(:num)', 'Admin\EngagementController::deleteAnnouncement/$1');
    $routes->get('engagement/quick-notice', 'Admin\EngagementController::quickNotice');
    $routes->post('engagement/quick-notice/send', 'Admin\EngagementController::sendQuickNotice');
    $routes->get('engagement/subscribers', 'Admin\EngagementController::subscribers');
    $routes->post('engagement/subscribers/toggle/(:num)', 'Admin\EngagementController::toggleSubscriberStatus/$1');
    $routes->get('engagement/subscribers/export', 'Admin\EngagementController::exportSubscribersCsv');
    $routes->get('engagement/job-alerts', 'Admin\EngagementController::jobAlerts');
    $routes->get('engagement/job-alerts/export', 'Admin\EngagementController::exportJobAlertsCsv');
    $routes->get('engagement/campaigns', 'Admin\EngagementController::campaigns');
    $routes->get('engagement/campaigns/new', 'Admin\EngagementController::newCampaign');
    $routes->post('engagement/campaigns/store', 'Admin\EngagementController::storeCampaign');
    $routes->get('engagement/campaigns/edit/(:num)', 'Admin\EngagementController::editCampaign/$1');
    $routes->post('engagement/campaigns/update/(:num)', 'Admin\EngagementController::updateCampaign/$1');
    $routes->post('engagement/campaigns/test/(:num)', 'Admin\EngagementController::sendTestEmail/$1');
    $routes->post('engagement/campaigns/send/(:num)', 'Admin\EngagementController::sendCampaignBroadcast/$1');
    $routes->get('engagement/campaigns/logs/(:num)', 'Admin\EngagementController::campaignLogs/$1');
    $routes->get('engagement/leads', 'Admin\EngagementController::leads');
    $routes->get('engagement/leads/export', 'Admin\EngagementController::exportLeadsCsv');
    $routes->get('engagement/analytics', 'Admin\EngagementController::analytics');
    $routes->get('engagement/analytics/report', 'Admin\EngagementController::executiveReport');
    $routes->get('engagement/cookies', 'Admin\EngagementController::cookies');
    $routes->post('migrate-schema', 'Admin\EngagementController::migrateSchema');

    // Email Templates Management
    $routes->get('mail/templates', 'Admin\MailTemplatesController::index');
    $routes->get('mail/templates/edit/(:num)', 'Admin\MailTemplatesController::edit/$1');
    $routes->post('mail/templates/update/(:num)', 'Admin\MailTemplatesController::update/$1');
    $routes->post('mail/templates/test/(:num)', 'Admin\MailTemplatesController::testSend/$1');

    // Security & Mutation Audit Logs
    // IT Services CRM
    $routes->get('it-leads', 'Admin\ItLeadsController::index');
    $routes->get('it-leads/(:num)', 'Admin\ItLeadsController::show/$1');
    $routes->post('it-leads/(:num)/status', 'Admin\ItLeadsController::updateStatus/$1');
    $routes->post('it-leads/(:num)/assign', 'Admin\ItLeadsController::assign/$1');
    $routes->post('it-leads/(:num)/note', 'Admin\ItLeadsController::addNote/$1');
    $routes->post('it-leads/(:num)/follow-up', 'Admin\ItLeadsController::followUp/$1');
    $routes->post('it-leads/(:num)/delete', 'Admin\ItLeadsController::delete/$1');

    // --- CRM extension + WorkSphere integration admin (crm.* / integrations.* permissions) ---
    $routes->post('it-leads/bulk', 'Admin\ItLeadsController::bulk');
    $routes->post('it-leads/(:num)/priority', 'Admin\ItLeadsController::updateMeta/$1');
    $routes->get('it-leads/settings', 'Admin\ItLeadsController::settings');
    $routes->post('it-leads/settings', 'Admin\ItLeadsController::saveSettings');
    $routes->post('it-leads/settings/test-notification', 'Admin\ItLeadsController::testNotification');
    $routes->get('integrations', 'Admin\IntegrationsController::index');
    $routes->post('integrations/create', 'Admin\IntegrationsController::create');
    $routes->post('integrations/(:num)/rotate', 'Admin\IntegrationsController::rotate/$1');
    $routes->post('integrations/(:num)/toggle', 'Admin\IntegrationsController::toggle/$1');
    // --- end CRM block ---

    // ── Product catalogue (admin) ──
    $routes->get('products', 'Admin\ProductsController::index');
    $routes->get('products/new', 'Admin\ProductsController::new');
    $routes->post('products/create', 'Admin\ProductsController::create');
    $routes->get('products/(:num)/edit', 'Admin\ProductsController::edit/$1');
    $routes->post('products/(:num)/update', 'Admin\ProductsController::update/$1');
    $routes->get('products/(:num)/preview', 'Admin\ProductsController::preview/$1');
    $routes->post('products/(:num)/status', 'Admin\ProductsController::status/$1');
    $routes->post('products/(:num)/feature', 'Admin\ProductsController::feature/$1');
    $routes->post('products/(:num)/move', 'Admin\ProductsController::move/$1');
    $routes->post('products/(:num)/delete', 'Admin\ProductsController::delete/$1');

    // Case studies (portfolio)
    $routes->get('case-studies', 'Admin\CaseStudiesController::index');
    $routes->get('case-studies/new', 'Admin\CaseStudiesController::new');
    $routes->post('case-studies/create', 'Admin\CaseStudiesController::create');
    $routes->get('case-studies/(:num)/edit', 'Admin\CaseStudiesController::edit/$1');
    $routes->post('case-studies/(:num)/update', 'Admin\CaseStudiesController::update/$1');
    $routes->post('case-studies/(:num)/status', 'Admin\CaseStudiesController::status/$1');
    $routes->post('case-studies/(:num)/delete', 'Admin\CaseStudiesController::delete/$1');

    // ── Social Media Marketing & Management Centre ──
    $routes->get('social', 'Admin\SocialMarketingController::overview');
    $routes->get('social/overview', 'Admin\SocialMarketingController::overview');

    // Posts & Composer Studio
    $routes->get('social/posts', 'Admin\SocialMarketingController::posts');
    $routes->get('social/posts/create', 'Admin\SocialMarketingController::createPost');
    $routes->post('social/posts/store', 'Admin\SocialMarketingController::storePost');
    $routes->get('social/posts/(:num)', 'Admin\SocialMarketingController::showPost/$1');
    $routes->post('social/posts/(:num)/publish', 'Admin\SocialMarketingController::publishNow/$1');
    $routes->post('social/posts/(:num)/reschedule', 'Admin\SocialMarketingController::reschedulePost/$1');
    $routes->post('social/posts/(:num)/delete', 'Admin\SocialMarketingController::deletePost/$1');

    // Calendar, Scheduled & Queue
    $routes->get('social/calendar', 'Admin\SocialMarketingController::calendar');
    $routes->get('social/scheduled', 'Admin\SocialMarketingController::scheduled');
    $routes->get('social/drafts', 'Admin\SocialMarketingController::drafts');
    $routes->get('social/queue', 'Admin\SocialMarketingController::queue');
    $routes->post('social/queue/(:num)/retry', 'Admin\SocialMarketingController::retryJob/$1');
    $routes->post('social/queue/(:num)/cancel', 'Admin\SocialMarketingController::cancelJob/$1');

    // Media Library & AJAX upload
    $routes->get('social/media', 'Admin\SocialMarketingController::media');
    $routes->post('social/media/upload', 'Admin\SocialMarketingController::uploadMedia');
    $routes->post('social/media/(:num)/delete', 'Admin\SocialMarketingController::deleteMedia/$1');

    // Organic Campaigns
    $routes->get('social/campaigns', 'Admin\SocialMarketingController::campaigns');
    $routes->get('social/campaigns/create', 'Admin\SocialMarketingController::campaignForm');
    $routes->get('social/campaigns/(:num)/edit', 'Admin\SocialMarketingController::campaignForm/$1');
    $routes->post('social/campaigns/save', 'Admin\SocialMarketingController::saveCampaign');
    $routes->post('social/campaigns/(:num)/save', 'Admin\SocialMarketingController::saveCampaign/$1');

    // Enquiries & Leads
    $routes->get('social/enquiries', 'Admin\SocialMarketingController::enquiries');
    $routes->post('social/enquiries/store', 'Admin\SocialMarketingController::storeEnquiry');
    $routes->post('social/enquiries/(:num)/status', 'Admin\SocialMarketingController::updateEnquiryStatus/$1');
    $routes->get('social/enquiries/export', 'Admin\SocialMarketingController::exportEnquiries');

    // Comments & Engagement
    $routes->get('social/engagement', 'Admin\SocialMarketingController::comments');

    // Analytics & Reports
    $routes->get('social/analytics', 'Admin\SocialMarketingController::analytics');

    // Social Accounts & OAuth
    $routes->get('social/accounts', 'Admin\SocialMarketingController::accounts');
    $routes->get('social/oauth/connect/(:segment)', 'Admin\SocialOAuthController::connect/$1');
    $routes->get('social/oauth/callback/linkedin', 'Admin\SocialOAuthController::callbackLinkedIn');
    $routes->get('social/oauth/callback/instagram', 'Admin\SocialOAuthController::callbackInstagram');
    $routes->post('social/oauth/disconnect/(:num)', 'Admin\SocialOAuthController::disconnect/$1');

    // Integration Settings & Logs
    $routes->get('social/settings', 'Admin\SocialMarketingController::settings');
    $routes->post('social/settings', 'Admin\SocialMarketingController::saveSettings');
    $routes->get('social/logs', 'Admin\SocialMarketingController::logs');

    // Content Assistant AJAX
    $routes->post('social/assistant/suggest', 'Admin\SocialMarketingController::assistantSuggest');
    $routes->post('social/assistant/rewrite', 'Admin\SocialMarketingController::assistantRewrite');

    $routes->get('audit-logs', 'Admin\AuditLogController::index');

    // System Health, Background Jobs, Scheduler (system.view / system.manage enforced in controller)
    $routes->get('system/health', 'Admin\SystemController::health');
    $routes->post('system/health/refresh', 'Admin\SystemController::refreshHealth');
    $routes->get('system/jobs', 'Admin\SystemController::jobs');
    $routes->get('system/jobs/(social|email)/(:num)', 'Admin\SystemController::jobDetail/$1/$2');
    $routes->post('system/jobs/(social|email)/(:num)/retry', 'Admin\SystemController::retryJob/$1/$2');
    $routes->post('system/jobs/(social|email)/(:num)/cancel', 'Admin\SystemController::cancelJob/$1/$2');
    $routes->get('system/scheduler', 'Admin\SystemController::scheduler');

    // External AI agents + human approval of agent drafts (agents.view / agents.manage / social.approve enforced in controller)
    $routes->get('agents', 'Admin\AgentsController::index');
    $routes->get('agents/create', 'Admin\AgentsController::create');
    $routes->post('agents/store', 'Admin\AgentsController::store');
    $routes->get('agents/approvals', 'Admin\AgentsController::approvals');
    $routes->get('agents/approvals/(:num)', 'Admin\AgentsController::review/$1');
    $routes->post('agents/approvals/(:num)/decide', 'Admin\AgentsController::decide/$1');
    $routes->post('agents/approvals/(:num)/edit', 'Admin\AgentsController::saveEdits/$1');
    $routes->get('agents/(:num)', 'Admin\AgentsController::show/$1');
    $routes->post('agents/(:num)/update', 'Admin\AgentsController::update/$1');
    $routes->post('agents/(:num)/toggle', 'Admin\AgentsController::toggle/$1');
    $routes->post('agents/(:num)/rotate', 'Admin\AgentsController::rotate/$1');
    $routes->post('agents/(:num)/revoke', 'Admin\AgentsController::revoke/$1');

    // Staff Users Management
    $routes->get('users', 'Admin\UsersController::index');
    $routes->get('users/new', 'Admin\UsersController::new');
    $routes->post('users/create', 'Admin\UsersController::create');
    $routes->get('users/edit/(:num)', 'Admin\UsersController::edit/$1');
    $routes->post('users/update/(:num)', 'Admin\UsersController::update/$1');
    $routes->post('users/toggle/(:num)', 'Admin\UsersController::toggleStatus/$1');
    $routes->post('users/delete/(:num)', 'Admin\UsersController::delete/$1');

    // Roles & Granular Permissions Management
    $routes->get('roles', 'Admin\RolesController::index');
    $routes->get('roles/new', 'Admin\RolesController::new');
    $routes->post('roles/create', 'Admin\RolesController::create');
    $routes->get('roles/edit/(:num)', 'Admin\RolesController::edit/$1');
    $routes->post('roles/update/(:num)', 'Admin\RolesController::update/$1');
    $routes->post('roles/delete/(:num)', 'Admin\RolesController::delete/$1');

    // CMS & Page Content
    $routes->get('content/articles', 'Admin\ContentController::articles');
    $routes->get('content/articles/new', 'Admin\ContentController::newArticle');
    $routes->post('content/articles/store', 'Admin\ContentController::storeArticle');
    $routes->get('content/articles/edit/(:num)', 'Admin\ContentController::editArticle/$1');
    $routes->post('content/articles/update/(:num)', 'Admin\ContentController::updateArticle/$1');
    $routes->post('content/articles/toggle/(:num)', 'Admin\ContentController::toggleArticleStatus/$1');
    $routes->post('content/articles/delete/(:num)', 'Admin\ContentController::deleteArticle/$1');
    $routes->get('content/pages', 'Admin\ContentController::pages');
    $routes->get('content/pages/edit/(:num)', 'Admin\ContentController::editPage/$1');
    $routes->post('content/pages/update/(:num)', 'Admin\ContentController::updatePage/$1');
    $routes->get('content/testimonials', 'Admin\ContentController::testimonials');
    $routes->post('content/testimonials/store', 'Admin\ContentController::storeTestimonial');
    $routes->post('content/testimonials/delete/(:num)', 'Admin\ContentController::deleteTestimonial/$1');
    $routes->get('content/faqs', 'Admin\ContentController::faqs');
    $routes->post('content/faqs/store', 'Admin\ContentController::storeFaq');
    $routes->post('content/faqs/delete/(:num)', 'Admin\ContentController::deleteFaq/$1');

    // Website Settings & Profile
    $routes->get('settings', 'Admin\SettingsController::index');
    $routes->post('settings/update', 'Admin\SettingsController::update');
    $routes->get('profile', 'Admin\ProfileController::index');
    $routes->post('profile/update', 'Admin\ProfileController::update');
});

// ─── 3. REST API V1 ENDPOINTS (/api/v1/...) ───────────────────────────────────
$routes->group('api/v1', static function ($routes) {
    // Public APIs
    $routes->get('jobs', 'Api\JobApiController::index');
    $routes->get('jobs/categories', 'Api\JobApiController::categories');
    $routes->get('jobs/(:segment)', 'Api\JobApiController::show/$1');
    $routes->post('applications', 'Web\JobsController::submitApplication/direct', ['filter' => 'ratelimit:5,600,upload']);
    $routes->post('applications/submit', 'Web\JobsController::submitApplication/direct', ['filter' => 'ratelimit:5,600,upload']);
    $routes->post('resume-submissions', 'Web\CareerEnquiryController::submit', ['filter' => 'ratelimit:5,600,upload']);
    $routes->post('enquiry/general-resume', 'Web\CareerEnquiryController::submit', ['filter' => 'ratelimit:5,600,upload']);
    $routes->post('service-enquiries', 'Web\ProjectEnquiryController::submit', ['filter' => 'ratelimit:5,600,service']);
    $routes->post('contact-enquiries', 'Api\EnquiryApiController::submitContact', ['filter' => 'ratelimit:5,600,contact']);
    $routes->post('enquiry/contact', 'Api\EnquiryApiController::submitContact', ['filter' => 'ratelimit:5,600,contact']);
    $routes->post('employer-enquiries', 'Api\EnquiryApiController::submitEmployer', ['filter' => 'ratelimit:5,600,employer']);
    $routes->post('enquiry/employer', 'Api\EnquiryApiController::submitEmployer', ['filter' => 'ratelimit:5,600,employer']);
    $routes->get('blogs', 'Api\CmsApiController::blogs');
    $routes->get('blogs/(:segment)', 'Api\CmsApiController::blog/$1');
    $routes->get('faqs', 'Api\CmsApiController::faqs');
    $routes->get('testimonials', 'Api\CmsApiController::testimonials');
    $routes->get('pages/(:segment)', 'Api\CmsApiController::page/$1');
    $routes->get('settings', 'Api\SettingsApiController::index');
    $routes->get('sitemaps/index', 'Web\SitemapController::index');
    $routes->get('sitemaps/pages', 'Web\SitemapController::pages');
    $routes->get('sitemaps/jobs', 'Web\SitemapController::jobs');
    $routes->get('sitemaps/insights', 'Web\SitemapController::insights');
    $routes->get('sitemaps/news', 'Web\SitemapController::news');

    // Hostinger Mail API Inbound Webhooks
    $routes->post('webhooks/hostinger-mail', 'Api\MailApiController::webhook', ['filter' => 'ratelimit:120,60']);

    // External AI agents (draft-only). Bearer-token auth + scope per route (AgentAuthFilter); no session, no CSRF cookie.
    // There is intentionally NO publish / schedule / approve endpoint.
    $routes->group('integrations/agents', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
        $routes->get('capabilities', 'AgentApiController::capabilities', ['filter' => 'agentauth']);
        $routes->get('social/accounts', 'AgentApiController::accounts', ['filter' => 'agentauth:social.accounts.read']);
        $routes->get('social/content-guidelines', 'AgentApiController::guidelines', ['filter' => 'agentauth:social.guidelines.read']);
        $routes->get('social/templates', 'AgentApiController::templates', ['filter' => 'agentauth:social.templates.read']);
        $routes->get('social/calendar', 'AgentApiController::calendar', ['filter' => 'agentauth:social.calendar.read']);
        $routes->get('social/analytics', 'AgentApiController::analytics', ['filter' => 'agentauth:social.analytics.read']);
        $routes->post('social/posts/drafts', 'AgentApiController::createDraft', ['filter' => 'agentauth:social.drafts.create']);
        $routes->get('social/posts/(:num)', 'AgentApiController::showPost/$1', ['filter' => 'agentauth:social.posts.read']);
        $routes->post('social/posts/(:num)/submit-approval', 'AgentApiController::submitApproval/$1', ['filter' => 'agentauth:social.drafts.submit']);
    });

    // WorkSphere signed event receiver (HMAC auth inside the controller; outside CSRF as api/v1/*)
    $routes->post('integrations/worksphere/events', 'Api\WorksphereEventController::receive');

    // Newsletter & Engagement Public APIs (V1)
    $routes->post('newsletter/subscribe', 'Api\NewsletterApiController::subscribe', ['filter' => 'ratelimit:10,60']);
    $routes->post('newsletter/verify', 'Api\NewsletterApiController::verify', ['filter' => 'ratelimit:30,60']);
    $routes->post('newsletter/unsubscribe', 'Api\NewsletterApiController::unsubscribe', ['filter' => 'ratelimit:30,60']);
    $routes->post('newsletter/resubscribe', 'Api\NewsletterApiController::resubscribe', ['filter' => 'ratelimit:30,60']);
    $routes->get('newsletter/preferences', 'Api\NewsletterApiController::getPreferences', ['filter' => 'ratelimit:30,60']);
    $routes->post('newsletter/preferences', 'Api\NewsletterApiController::updatePreferences', ['filter' => 'ratelimit:30,60']);

    // Smart Announcements & Popups APIs (V1)
    $routes->get('announcements/active', 'Api\AnnouncementApiController::active');
    $routes->post('analytics/campaign-event', 'Api\AnnouncementApiController::trackEvent', ['filter' => 'ratelimit:60,60']);

    // Candidate Job Alerts APIs (V1)
    $routes->post('job-alerts/subscribe', 'Api\JobAlertApiController::subscribe', ['filter' => 'ratelimit:10,60']);
    $routes->post('job-alerts/unsubscribe', 'Api\JobAlertApiController::unsubscribe', ['filter' => 'ratelimit:30,60']);

    // Cookie Consent APIs (V1)
    $routes->post('cookies/consent', 'Api\CookieConsentApiController::logConsent', ['filter' => 'ratelimit:30,60']);

    // Admin Auth APIs
    $routes->post('admin/login', 'Admin\AuthController::login', ['filter' => 'ratelimit:15,60']);
    $routes->post('admin/logout', 'Admin\AuthController::logout');
    $routes->get('admin/session', 'Admin\AuthController::session');
    $routes->post('admin/forgot-password', 'Admin\AuthController::forgotPassword', ['filter' => 'ratelimit:10,60']);
    $routes->post('admin/reset-password', 'Admin\AuthController::resetPassword', ['filter' => 'ratelimit:10,60']);

    // Admin Protected APIs (Requires Session Auth)
    // 'apiperm' = per-route permission map (fail-closed); 'auth' alone only proves the caller is signed in.
    $routes->group('admin', ['filter' => ['auth', 'apiperm']], static function ($routes) {
        // Profile
        $routes->get('profile', 'Api\UserApiController::profile');
        $routes->put('profile', 'Api\UserApiController::updateProfile');
        $routes->put('profile/password', 'Api\UserApiController::changePassword');

        // Dashboard Telemetry
        $routes->get('dashboard', 'Admin\DashboardController::stats');

        // Notifications
        $routes->get('notifications', 'Api\NotificationApiController::index');
        $routes->get('notifications/unread-count', 'Api\NotificationApiController::unreadCount');
        $routes->post('notifications/(:num)/read', 'Api\NotificationApiController::markRead/$1');
        $routes->post('notifications/read-all', 'Api\NotificationApiController::markAllRead');

        // Jobs Management
        $routes->get('jobs', 'Api\JobApiController::index');
        $routes->post('jobs', 'Api\JobApiController::store');
        $routes->get('jobs/(:num)', 'Api\JobApiController::show/$1');
        $routes->put('jobs/(:num)', 'Api\JobApiController::update/$1');
        $routes->put('jobs/(:num)/status', 'Api\JobApiController::transitionStatus/$1');
        $routes->post('jobs/(:num)/duplicate', 'Api\JobApiController::duplicate/$1');
        $routes->delete('jobs/(:num)', 'Api\JobApiController::destroy/$1');

        // Applications & Resume Vault
        $routes->get('applications', 'Api\ApplicationApiController::index');
        $routes->get('applications/(:num)', 'Api\ApplicationApiController::show/$1');
        $routes->put('applications/(:num)/status', 'Api\ApplicationApiController::updateStatus/$1');
        $routes->post('applications/(:num)/notes', 'Api\ApplicationApiController::addNote/$1');
        $routes->get('documents/(:num)/stream', 'Api\ApplicationApiController::streamResume/$1');
        $routes->get('resume-bank', 'Api\ApplicationApiController::resumeBank');
        $routes->put('resume-bank/(:num)/status', 'Api\ApplicationApiController::updateResumeBankStatus/$1');

        // Enquiries
        $routes->get('enquiries/employers', 'Api\EnquiryApiController::employerEnquiries');
        $routes->get('enquiries/contact', 'Api\EnquiryApiController::contactEnquiries');
        $routes->put('enquiries/employers/(:num)/status', 'Api\EnquiryApiController::updateEmployerStatus/$1');
        $routes->put('enquiries/contact/(:num)/status', 'Api\EnquiryApiController::updateContactStatus/$1');

        // CMS Content
        $routes->get('blogs', 'Api\CmsApiController::blogs');
        $routes->post('blogs', 'Api\CmsApiController::storeBlog');
        $routes->put('blogs/(:num)', 'Api\CmsApiController::updateBlog/$1');
        $routes->delete('blogs/(:num)', 'Api\CmsApiController::destroyBlog/$1');

        $routes->get('faqs', 'Api\CmsApiController::faqs');
        $routes->post('faqs', 'Api\CmsApiController::storeFaq');
        $routes->put('faqs/(:num)', 'Api\CmsApiController::updateFaq/$1');
        $routes->delete('faqs/(:num)', 'Api\CmsApiController::destroyFaq/$1');

        $routes->get('testimonials', 'Api\CmsApiController::testimonials');
        $routes->post('testimonials', 'Api\CmsApiController::storeTestimonial');
        $routes->put('testimonials/(:num)', 'Api\CmsApiController::updateTestimonial/$1');
        $routes->delete('testimonials/(:num)', 'Api\CmsApiController::destroyTestimonial/$1');

        // Dynamic Pages & Rollback
        $routes->get('pages', 'Api\CmsApiController::pages');
        $routes->get('pages/(:segment)', 'Api\CmsApiController::page/$1');
        $routes->put('pages/(:segment)/draft', 'Api\CmsApiController::savePageDraft/$1');
        $routes->post('pages/(:segment)/publish', 'Api\CmsApiController::publishPage/$1');
        $routes->post('pages/(:segment)/rollback/(:num)', 'Api\CmsApiController::rollbackPage/$1/$2');

        // Media Library
        $routes->get('media', 'Api\CmsApiController::media');
        $routes->post('media', 'Api\CmsApiController::uploadMedia');
        $routes->delete('media/(:num)', 'Api\CmsApiController::deleteMedia/$1');

        // Email Outbox & Templates
        $routes->get('email-templates', 'Api\EmailApiController::indexTemplates');
        $routes->get('email-templates/(:num)', 'Api\EmailApiController::showTemplate/$1');
        $routes->put('email-templates/(:num)', 'Api\EmailApiController::updateTemplate/$1');
        $routes->get('email-outbox', 'Api\EmailApiController::outbox');
        $routes->post('email-outbox/(:num)/resend', 'Api\EmailApiController::resend/$1');

        // Hostinger Mail API & Recruitment Communication Center
        $routes->get('mail/mailboxes', 'Api\MailApiController::mailboxes');
        $routes->post('mail/mailboxes/sync', 'Api\MailApiController::syncMailboxes');
        $routes->get('mail/folders', 'Api\MailApiController::folders');
        $routes->post('mail/folders', 'Api\MailApiController::createFolder');
        $routes->delete('mail/folders', 'Api\MailApiController::deleteFolder');
        $routes->get('mail/messages', 'Api\MailApiController::messages');
        $routes->get('mail/messages/detail', 'Api\MailApiController::messageDetail');
        $routes->post('mail/messages/action', 'Api\MailApiController::messageAction');
        $routes->post('mail/send', 'Api\MailApiController::send');
        $routes->get('mail/drafts', 'Api\MailApiController::drafts');
        $routes->post('mail/drafts', 'Api\MailApiController::saveDraft');
        $routes->delete('mail/drafts/(:num)', 'Api\MailApiController::deleteDraft/$1');
        $routes->get('mail/templates', 'Api\MailApiController::templates');
        $routes->post('mail/templates/render', 'Api\MailApiController::renderTemplate');
        $routes->get('mail/analytics', 'Api\MailApiController::analytics');
        $routes->get('mail/settings', 'Api\MailApiController::settings');
        $routes->post('mail/settings', 'Api\MailApiController::updateSettings');
        $routes->post('mail/test-connection', 'Api\MailApiController::testConnection');
        $routes->post('mail/link-recruitment', 'Api\MailApiController::linkRecruitment');

        // Users & Roles
        $routes->get('users', 'Api\UserApiController::index');
        $routes->post('users', 'Api\UserApiController::store');
        $routes->put('users/(:num)', 'Api\UserApiController::update/$1');
        $routes->get('roles', 'Api\UserApiController::roles');

        // Security & Telemetry
        $routes->get('security/stats', 'Api\SecurityApiController::stats');
        $routes->get('security/login-attempts', 'Api\SecurityApiController::loginAttempts');
        $routes->get('audit-logs', 'Api\SecurityApiController::auditLogs');
        $routes->get('health', 'Api\SecurityApiController::health');
        $routes->get('seo/status', 'Api\SecurityApiController::seoStatus');
        $routes->post('seo/sitemap-regenerate', 'Api\SecurityApiController::sitemapRegenerate');

        // Reports
        $routes->get('reports/applications', 'Api\ReportApiController::exportApplications');
        $routes->get('reports/candidates', 'Api\ReportApiController::exportCandidates');
        $routes->get('reports/employers', 'Api\ReportApiController::exportEmployers');

        // Website Settings & Controls
        $routes->get('settings', 'Api\SettingsApiController::index');
        $routes->put('settings', 'Api\SettingsApiController::update');
        $routes->get('settings/website-control', 'Api\SettingsApiController::getWebsiteControl');
        $routes->put('settings/website-control', 'Api\SettingsApiController::updateWebsiteControl');
        $routes->post('settings/test-smtp', 'Api\SettingsApiController::testSmtp');
    });
});

// ─── 4. CONVENIENCE & SCHEDULER API ALIASES (/api/...) ─────────────────────────
$routes->group('api', static function ($routes) {
    // Newsletter
    $routes->post('newsletter/subscribe', 'Api\NewsletterApiController::subscribe', ['filter' => 'ratelimit:10,60']);
    $routes->post('newsletter/verify', 'Api\NewsletterApiController::verify', ['filter' => 'ratelimit:30,60']);
    $routes->post('newsletter/unsubscribe', 'Api\NewsletterApiController::unsubscribe', ['filter' => 'ratelimit:30,60']);
    $routes->get('newsletter/preferences', 'Api\NewsletterApiController::getPreferences', ['filter' => 'ratelimit:30,60']);
    $routes->post('newsletter/preferences', 'Api\NewsletterApiController::updatePreferences', ['filter' => 'ratelimit:30,60']);

    // Announcements
    $routes->get('announcements/active', 'Api\AnnouncementApiController::active');
    $routes->post('analytics/campaign-event', 'Api\AnnouncementApiController::trackEvent', ['filter' => 'ratelimit:60,60']);

    // Job Alerts
    $routes->post('job-alerts/subscribe', 'Api\JobAlertApiController::subscribe', ['filter' => 'ratelimit:10,60']);
    $routes->post('job-alerts/unsubscribe', 'Api\JobAlertApiController::unsubscribe', ['filter' => 'ratelimit:30,60']);

    // Protected HTTP Scheduler Trigger for Shared Hosting Cron (GAP-04)
    $routes->get('cron/run', static function () {
        $key = service('request')->getGet('key');
        $key = $key ?: service('request')->getHeaderLine('X-Cron-Key');
        $expectedKey = (string) getenv('CRON_SECRET_KEY');

        // Fail closed: no built-in default secret. CRON_SECRET_KEY must be set in the env file.
        if ($expectedKey === '') {
            return service('response')->setStatusCode(503)->setJSON([
                'success' => false,
                'message' => 'HTTP cron is disabled: CRON_SECRET_KEY is not configured.',
            ]);
        }

        if (empty($key) || !hash_equals($expectedKey, (string) $key)) {
            return service('response')->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized cron key provided.',
            ]);
        }

        $outboxStats = ['processed' => 0, 'errors' => 0];
        try {
            $outbox = new \App\Services\OutboxService();
            $outboxStats = $outbox->processPending(30);
        } catch (\Throwable $e) {
            log_message('error', 'Cron outbox execution error: ' . $e->getMessage());
            $outboxStats['error'] = $e->getMessage();
        }

        $engagementStats = [];
        try {
            $automation = new \App\Services\EnquiryAutomationService();
            $engagementStats = $automation->processEngagementFollowUps();
        } catch (\Throwable $e) {
            log_message('error', 'Cron engagement execution error: ' . $e->getMessage());
            $engagementStats['error'] = $e->getMessage();
        }

        return service('response')->setJSON([
            'success'    => true,
            'timestamp'  => date('c'),
            'outbox'     => $outboxStats,
            'engagement' => $engagementStats,
            'message'    => 'TalentHub background scheduler executed successfully.',
        ]);
    });
});
