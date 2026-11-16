<?php

namespace Crm\Services;

/**
 * Product Tour & User Onboarding Engine
 * Handles first-login detection, role-aware tours, progress persistence and dynamic step delivery.
 */
class TourService
{
    /**
     * Default role-specific guided tours
     */
    private const TOURS = [
        'master_enterprise_tour' => [
            'role' => 'all',
            'title' => 'Drem Homes CRM — Complete 20-Step Walkthrough',
            'description' => 'Comprehensive end-to-end demonstration of all 20 modules from Lead capture to Delivery, Payment, and After-Sales.',
            'version' => 2,
            'steps' => [
                ['target' => '.brand', 'title' => '1. Welcome to Drem Homes CRM', 'content' => 'Premium enterprise sales operations platform designed for Dream Homes Interior Solutions (DHIS Group).', 'position' => 'bottom'],
                ['target' => '.nav a[href$="/crm"]', 'title' => '2. Dashboard Overview', 'content' => 'Live business KPIs, revenue won, quotation pipeline, and team performance calculated directly from database records.', 'position' => 'right'],
                ['target' => '.side', 'title' => '3. Sidebar Navigation', 'content' => 'Clean 20-module workflow categorized into commercial sales, execution operations, reports, and master controls.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/leads"]', 'title' => '4. Understanding Leads', 'content' => 'Track incoming prospects across 11 stages from New Lead to Qualified with visual Kanban cards.', 'position' => 'right'],
                ['target' => '#cmd-k-trigger', 'title' => '5. Creating a New Lead', 'content' => 'Use Ctrl+K Command Launcher or Quick Lead Capture to record customer expectations and budget in seconds.', 'position' => 'bottom'],
                ['target' => '.nav a[href*="crm/customers"]', 'title' => '6. Managing Customers', 'content' => 'Complete Customer 360 view for Individuals, Architects, Builders, and Corporate accounts with multi-address support.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/masters"]', 'title' => '7. Adding Products & Variants', 'content' => 'Configurable architectural catalogue featuring Vitrum, Saint-Gobain, Grohe, Häfele, and Mr. Kitchen.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/visits"]', 'title' => '8. Scheduling Site Visits', 'content' => 'Dispatch technical representatives to site locations with appointment dates and address tracking.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/measurements"]', 'title' => '9. Recording Measurements', 'content' => 'Record millimeter dimensions, room tags, and custom opening calculations with immutable revision versioning.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/quotations"]', 'title' => '10. Preparing Quotations', 'content' => 'Dynamic proposal builder supporting custom sizes, items, taxes, and installation charges with server-side math.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/quotations"]', 'title' => '11. Understanding Quotation Approval', 'content' => 'Automatic margin checks route requests above authorized salesperson limits to Sales Managers or Directors.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/quotations"]', 'title' => '12. Generating & Downloading A4 PDFs', 'content' => 'Generate pixel-perfect A4 portrait commercial proposals with DHIS branding, GST breakdown, and INR words.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/orders"]', 'title' => '13. Converting Quotations to Sales Orders', 'content' => 'Single-click conversion locks quote items and initiates procurement across 17 commercial statuses.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/payments"]', 'title' => '14. Recording Payments', 'content' => 'Record advance, milestone, or full payments via UPI, NEFT, Cheque or Cash with official receipt generation.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/deliveries"]', 'title' => '15. Tracking Deliveries', 'content' => 'Issue delivery challans, assign drivers, vehicle numbers, and verify on-site delivery completion.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/installations"]', 'title' => '16. Managing Installations', 'content' => 'Assign technicians, monitor work in progress, and capture client sign-off on completed installations.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/service"]', 'title' => '17. Handling After-Sales Requests', 'content' => 'Log customer complaints, repair requests, and 10-year profile warranty claims with resolution logs.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/reports"]', 'title' => '18. Viewing Reports & Analytics', 'content' => 'Deep business intelligence on conversion funnels, salesperson rankings, lead-source ROI, and XLSX exports.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/masters"]', 'title' => '19. Managing Master Data', 'content' => 'Unified Master Data Management for all Organisation, Customer, Product, Sales, and System settings.', 'position' => 'right'],
                ['target' => '.nav a[href*="crm/settings"]', 'title' => '20. User Settings & Permissions', 'content' => 'Configure multi-tier RBAC, audit logs, background scheduler jobs, and system health checks.', 'position' => 'right'],
            ],
        ],
        'executive_onboarding' => [
            'role' => 'sales_executive',
            'title' => 'Sales Executive Quickstart',
            'description' => 'Master your daily sales workflow: Leads, Customer Contact, Follow-ups, and Quotations.',
            'version' => 1,
            'steps' => [
                [
                    'target' => '.brand',
                    'title' => 'Welcome to DHIS Sales CRM',
                    'content' => 'Your dedicated interior sales workspace powered by TalentHub Consultant. Let’s take a quick 1-minute tour.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.nav a[href*="crm/enquiries"]',
                    'title' => 'Enquiries Workspace',
                    'content' => 'All new incoming leads and assigned enquiries live here. You can view them in a table or as an interactive pipeline.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/followups"]',
                    'title' => 'Daily Follow-ups',
                    'content' => 'Never miss a customer touchpoint. Track Today’s calls, upcoming meetings, and overdue alerts right here.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/quotations"]',
                    'title' => 'Quotations & Pricing',
                    'content' => 'Generate itemized quotes with custom sizes, variants, and discounts within your authorized limits.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/orders"]',
                    'title' => 'Confirmed Sales Orders',
                    'content' => 'Accepted quotations are converted directly into confirmed sales orders ready for operations.',
                    'position' => 'right',
                ],
                [
                    'target' => '#cmd-k-trigger',
                    'title' => 'Quick Command Launcher',
                    'content' => 'Press Ctrl + K (or Cmd + K) anytime to jump directly to customers, enquiries, or create new leads in seconds.',
                    'position' => 'bottom',
                ],
            ],
        ],
        'manager_onboarding' => [
            'role' => 'sales_manager',
            'title' => 'Sales Manager Command Tour',
            'description' => 'Oversee team pipeline, manage workload distribution, approve discounts and drive conversions.',
            'version' => 1,
            'steps' => [
                [
                    'target' => '.brand',
                    'title' => 'Manager Operations Portal',
                    'content' => 'Welcome! As Sales Manager, you have complete visibility over team performance and approval queues.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.kpi',
                    'title' => 'Team KPI Snapshot',
                    'content' => 'Monitor team active enquiries, conversion rates, won sales revenue, and pending discounts at a glance.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.nav a[href*="crm/enquiries"]',
                    'title' => 'Team Pipeline Board',
                    'content' => 'Track enquiry movement across all 7 stages from initial contact to site measurement and closure.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/quotations"]',
                    'title' => 'Discounts & Approvals',
                    'content' => 'Review and decide on discount requests proposed by your team according to authorized policy limits.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/reports"]',
                    'title' => 'Conversion & Team Reports',
                    'content' => 'Analyze executive win rates, enquiry aging, and lead-source ROI with real-time exportable reports.',
                    'position' => 'right',
                ],
            ],
        ],
        'admin_onboarding' => [
            'role' => 'admin',
            'title' => 'Enterprise Administrator Overview',
            'description' => 'System configuration, rule management, QA command centre, and user administration.',
            'version' => 1,
            'steps' => [
                [
                    'target' => '.brand',
                    'title' => 'Administrator Command Hub',
                    'content' => 'Welcome, Administrator. Configure system rules, product master rates, security, and audit controls.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.nav a[href*="crm/settings"]',
                    'title' => 'Unified Settings Hub',
                    'content' => 'Access Users & Teams, Products & Pricing, Assignment Rules, Email Logs, and System Health in one place.',
                    'position' => 'right',
                ],
                [
                    'target' => '.nav a[href*="crm/reports"]',
                    'title' => 'Auto QA & Reports',
                    'content' => 'Run comprehensive automated system integrity audits and view enterprise business intelligence.',
                    'position' => 'right',
                ],
            ],
        ],
    ];

    /**
     * Get the appropriate active tour for the current user
     */
    public static function getTourForUser(array $user): ?array
    {
        $userId = (int) ($user['id'] ?? 0);
        if (! $userId) {
            return null;
        }

        $roles = $user['roles'] ?? [];
        $tourKey = 'master_enterprise_tour';

        $tour = self::TOURS[$tourKey] ?? self::TOURS['executive_onboarding'];

        // Check progress
        $db = Crm::db();
        $prog = $db->table('crm_tour_progress')
            ->where('user_id', $userId)
            ->where('tour_key', $tourKey)
            ->get()
            ->getRowArray();

        $isCompleted = ! empty($prog['is_completed']);
        $isSkipped = ! empty($prog['is_skipped']);

        return [
            'key' => $tourKey,
            'title' => $tour['title'],
            'description' => $tour['description'],
            'version' => $tour['version'],
            'steps' => $tour['steps'],
            'is_completed' => $isCompleted,
            'is_skipped' => $isSkipped,
            'last_step' => (int) ($prog['last_step'] ?? 0),
        ];
    }

    /**
     * Update user tour progress
     */
    public static function updateProgress(int $userId, string $tourKey, int $step, bool $completed = false, bool $skipped = false): void
    {
        $db = Crm::db();
        $now = Crm::now();
        $data = [
            'last_step' => $step,
            'is_completed' => $completed ? 1 : 0,
            'is_skipped' => $skipped ? 1 : 0,
            'completed_at' => $completed ? $now : null,
            'updated_at' => $now,
        ];

        $exists = $db->table('crm_tour_progress')
            ->where('user_id', $userId)
            ->where('tour_key', $tourKey)
            ->countAllResults();

        if ($exists) {
            $db->table('crm_tour_progress')
                ->where('user_id', $userId)
                ->where('tour_key', $tourKey)
                ->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['tour_key'] = $tourKey;
            $data['version'] = 1;
            $data['created_at'] = $now;
            $db->table('crm_tour_progress')->insert($data);
        }
    }

    /**
     * Reset a tour for the user to restart it
     */
    public static function resetTour(int $userId, string $tourKey): void
    {
        Crm::db()->table('crm_tour_progress')
            ->where('user_id', $userId)
            ->where('tour_key', $tourKey)
            ->delete();
    }
}

