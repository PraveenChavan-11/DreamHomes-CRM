<?php

/** @var \CodeIgniter\Router\RouteCollection $routes — DHIS Sales CRM routes. Every route below `crmauth` is session-guarded; `crmperm:` adds a server-side permission gate. */

$routes->group('crm', ['namespace' => 'Crm\Controllers'], static function ($routes) {
    // ── public (no session) ──
    $routes->match(['get', 'post'], 'login', 'AuthController::login', ['filter' => 'ratelimit:20,60']);
    $routes->match(['get', 'post'], 'forgot-password', 'AuthController::forgot', ['filter' => 'ratelimit:10,60']);
    $routes->match(['get', 'post'], 'reset-password', 'AuthController::reset', ['filter' => 'ratelimit:10,60']);

    // ── authenticated ──
    $routes->group('', ['filter' => 'crmauth'], static function ($routes) {
        $routes->post('logout', 'AuthController::logout');
        $routes->get('profile', 'AuthController::profile');
        $routes->post('profile/password', 'AuthController::changePassword');

        // dashboard · calendar · notifications
        $routes->get('/', 'DashboardController::index', ['filter' => 'crmperm:dashboard.view']);
        $routes->get('calendar', 'DashboardController::calendar');
        $routes->get('notifications', 'DashboardController::notifications');
        $routes->get('notifications/count', 'DashboardController::notificationCount');
        $routes->post('notifications/read', 'DashboardController::markRead');
        $routes->post('notifications/(:num)/read', 'DashboardController::markRead/$1');

        // customers
        $routes->group('customers', ['filter' => 'crmperm:customers.view'], static function ($routes) {
            $routes->get('', 'CustomerController::index');
            $routes->get('export', 'CustomerController::export', ['filter' => 'crmperm:customers.export']);
            $routes->get('new', 'CustomerController::new', ['filter' => 'crmperm:customers.create']);
            $routes->post('create', 'CustomerController::create', ['filter' => 'crmperm:customers.create']);
            $routes->get('(:num)', 'CustomerController::show/$1');
            $routes->get('(:num)/edit', 'CustomerController::edit/$1', ['filter' => 'crmperm:customers.edit']);
            $routes->post('(:num)/update', 'CustomerController::update/$1', ['filter' => 'crmperm:customers.edit']);
            $routes->post('(:num)/status', 'CustomerController::status/$1', ['filter' => 'crmperm:customers.deactivate']);
        });

        // enquiries, activities, visits, requirements
        $routes->group('enquiries', ['filter' => 'crmperm:enquiries.view'], static function ($routes) {
            $routes->get('', 'EnquiryController::index');
            $routes->get('new', 'EnquiryController::new', ['filter' => 'crmperm:enquiries.create']);
            $routes->post('create', 'EnquiryController::create', ['filter' => 'crmperm:enquiries.create']);
            $routes->get('(:num)', 'EnquiryController::show/$1');
            $routes->post('(:num)/assign', 'EnquiryController::assign/$1', ['filter' => 'crmperm:enquiries.assign,enquiries.reassign']);
            $routes->post('(:num)/status', 'EnquiryController::status/$1', ['filter' => 'crmperm:enquiries.edit,enquiries.close,enquiries.reopen']);
            $routes->post('(:num)/won', 'EnquiryController::won/$1', ['filter' => 'crmperm:enquiries.close']);
            $routes->post('(:num)/lost', 'EnquiryController::lost/$1', ['filter' => 'crmperm:enquiries.close']);
            $routes->post('(:num)/hold', 'EnquiryController::hold/$1', ['filter' => 'crmperm:enquiries.close']);
            $routes->post('(:num)/reopen', 'EnquiryController::reopen/$1', ['filter' => 'crmperm:enquiries.reopen']);
            $routes->post('(:num)/contact', 'EnquiryController::contact/$1', ['filter' => 'crmperm:followups.manage']);
            $routes->post('(:num)/followups', 'EnquiryController::scheduleFollowup/$1', ['filter' => 'crmperm:followups.manage']);
            $routes->post('(:num)/visits', 'EnquiryController::scheduleVisit/$1', ['filter' => 'crmperm:visits.manage']);
            $routes->post('(:num)/requirements', 'EnquiryController::addRequirement/$1', ['filter' => 'crmperm:enquiries.edit']);
            $routes->post('requirements/(:num)/update', 'EnquiryController::updateRequirement/$1', ['filter' => 'crmperm:enquiries.edit']);
            $routes->post('requirements/(:num)/drop', 'EnquiryController::dropRequirement/$1', ['filter' => 'crmperm:enquiries.edit']);
        });
        $routes->group('followups', ['filter' => 'crmperm:followups.manage'], static function ($routes) {
            $routes->get('', 'ActivityController::followups');
            $routes->post('(:num)/complete', 'ActivityController::completeFollowup/$1');
            $routes->post('(:num)/reschedule', 'ActivityController::rescheduleFollowup/$1');
        });
        $routes->group('visits', ['filter' => 'crmperm:visits.manage'], static function ($routes) {
            $routes->get('', 'ActivityController::visits');
            $routes->get('(:num)', 'ActivityController::visit/$1');
            $routes->post('(:num)/complete', 'ActivityController::completeVisit/$1');
            $routes->post('(:num)/measurements', 'ActivityController::saveMeasurements/$1');
            $routes->post('(:num)/documents', 'ActivityController::uploadDocument/$1');
        });
        $routes->get('documents/(:num)', 'ActivityController::document/$1');

        // quotations (editor supports manual variant / size / price; every save is a tracked version)
        $routes->group('quotations', ['filter' => 'crmperm:quotations.view'], static function ($routes) {
            $routes->get('', 'QuotationController::index');
            $routes->get('new', 'QuotationController::new', ['filter' => 'crmperm:quotations.create']);
            $routes->post('create', 'QuotationController::create', ['filter' => 'crmperm:quotations.create']);
            $routes->post('preview', 'QuotationController::preview', ['filter' => 'crmperm:quotations.create']);
            $routes->get('(:num)', 'QuotationController::show/$1');
            $routes->get('(:num)/edit', 'QuotationController::edit/$1', ['filter' => 'crmperm:quotations.create']);
            $routes->post('(:num)/update', 'QuotationController::update/$1', ['filter' => 'crmperm:quotations.create']);
            $routes->get('(:num)/revise', 'QuotationController::reviseForm/$1', ['filter' => 'crmperm:quotations.create']);
            $routes->post('(:num)/revise', 'QuotationController::revise/$1', ['filter' => 'crmperm:quotations.create']);
            $routes->post('(:num)/send', 'QuotationController::send/$1', ['filter' => 'crmperm:quotations.send']);
            $routes->post('(:num)/resend', 'QuotationController::resend/$1', ['filter' => 'crmperm:quotations.send']);
            $routes->post('(:num)/response', 'QuotationController::response/$1', ['filter' => 'crmperm:quotations.create']);
            $routes->get('(:num)/compare', 'QuotationController::compare/$1');
            $routes->get('(:num)/pdf/(:num)', 'QuotationController::pdf/$1/$2');
            $routes->get('(:num)/preview/(:num)', 'QuotationController::previewVersion/$1/$2');
        });

        // negotiations & discount approvals
        $routes->group('negotiations', ['filter' => 'crmperm:quotations.view'], static function ($routes) {
            $routes->get('', 'NegotiationController::index');
            $routes->post('request', 'NegotiationController::request', ['filter' => 'crmperm:negotiations.create']);
            $routes->get('(:num)', 'NegotiationController::show/$1');
            $routes->post('(:num)/decide', 'NegotiationController::decide/$1', ['filter' => 'crmperm:discounts.approve_manager,discounts.approve_admin']);
            $routes->post('(:num)/resubmit', 'NegotiationController::resubmit/$1', ['filter' => 'crmperm:negotiations.create']);
        });

        // sales orders
        $routes->group('orders', ['filter' => 'crmperm:orders.view'], static function ($routes) {
            $routes->get('', 'OrderController::index');
            $routes->post('create', 'OrderController::create', ['filter' => 'crmperm:orders.manage,enquiries.close']);
            $routes->get('(:num)', 'OrderController::show/$1');
            $routes->post('(:num)/status', 'OrderController::status/$1', ['filter' => 'crmperm:orders.manage']);
        });

        // history · reports
        $routes->get('history', 'HistoryController::index', ['filter' => 'crmperm:history.view']);
        $routes->get('history/export', 'HistoryController::export', ['filter' => 'crmperm:history.view']);
        $routes->get('history/enquiry/(:num)', 'HistoryController::enquiry/$1', ['filter' => 'crmperm:history.view']);
        $routes->group('reports', ['filter' => 'crmperm:reports.view,quotations.view'], static function ($routes) {
            $routes->get('', 'ReportController::index');
            $routes->get('(:segment)', 'ReportController::show/$1');
            $routes->get('(:segment)/xlsx', 'ReportController::xlsx/$1', ['filter' => 'crmperm:reports.export']);
            $routes->get('(:segment)/pdf', 'ReportController::pdf/$1', ['filter' => 'crmperm:reports.export']);
        });

        // product & price master
        $routes->group('products', ['filter' => 'crmperm:products.view'], static function ($routes) {
            $routes->get('', 'ProductController::index');
            $routes->get('new', 'ProductController::new', ['filter' => 'crmperm:products.manage']);
            $routes->post('save', 'ProductController::save', ['filter' => 'crmperm:products.manage']);
            $routes->get('(:num)', 'ProductController::show/$1');
            $routes->get('(:num)/edit', 'ProductController::edit/$1', ['filter' => 'crmperm:products.manage']);
            $routes->post('(:num)/save', 'ProductController::save/$1', ['filter' => 'crmperm:products.manage']);
        });

        // excel import
        $routes->group('imports', ['filter' => 'crmperm:customers.import'], static function ($routes) {
            $routes->get('', 'ImportController::index');
            $routes->get('template', 'ImportController::template');
            $routes->post('upload', 'ImportController::upload');
            $routes->get('(:num)', 'ImportController::show/$1');
            $routes->post('(:num)/mapping', 'ImportController::mapping/$1');
            $routes->post('(:num)/validate', 'ImportController::validateRows/$1');
            $routes->post('(:num)/run', 'ImportController::run/$1');
            $routes->get('(:num)/errors', 'ImportController::errors/$1');
        });

        // email log & manual email
        $routes->group('emails', ['filter' => 'crmperm:emails.view'], static function ($routes) {
            $routes->get('', 'EmailController::index');
            $routes->get('(:num)', 'EmailController::show/$1');
            $routes->post('send', 'EmailController::send', ['filter' => 'crmperm:emails.send']);
            $routes->post('(:num)/retry', 'EmailController::retry/$1', ['filter' => 'crmperm:emails.send']);
        });

        // administration
        $routes->group('users', ['filter' => 'crmperm:users.manage,teams.manage'], static function ($routes) {
            $routes->get('', 'UserController::index');
            $routes->post('create', 'UserController::create', ['filter' => 'crmperm:users.manage']);
            $routes->post('(:num)/active', 'UserController::active/$1', ['filter' => 'crmperm:users.manage']);
            $routes->post('(:num)/password', 'UserController::password/$1', ['filter' => 'crmperm:users.manage']);
            $routes->post('teams/create', 'UserController::createTeam', ['filter' => 'crmperm:teams.manage']);
            $routes->post('teams/(:num)/members', 'UserController::addMember/$1', ['filter' => 'crmperm:teams.manage']);
        });
        $routes->get('audit', 'AdminController::audit', ['filter' => 'crmperm:audit.view']);
        $routes->get('settings', 'AdminController::settings', ['filter' => 'crmperm:settings.manage']);
        $routes->post('settings', 'AdminController::saveSettings', ['filter' => 'crmperm:settings.manage']);
        $routes->get('demo', 'AdminController::demo', ['filter' => 'crmperm:demo.reset']);
        $routes->post('demo/reset', 'AdminController::demoReset', ['filter' => 'crmperm:demo.reset']);
        $routes->get('health', 'AdminController::health', ['filter' => 'crmperm:settings.manage']);

        // Global Command Launcher Search (Ctrl+K)
        $routes->get('search', 'SearchController::search');

        // Auto QA Command Centre & Product Tour
        $routes->group('qa', ['filter' => 'crmperm:settings.manage'], static function ($routes) {
            $routes->get('', 'QaController::index');
            $routes->post('run', 'QaController::run');
        });
        $routes->group('tour', static function ($routes) {
            $routes->get('get', 'QaController::getTour');
            $routes->post('update', 'QaController::updateTour');
            $routes->post('reset', 'QaController::resetTour');
        });
    });
});
