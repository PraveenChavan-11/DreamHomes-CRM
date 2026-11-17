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
                [
                    'target' => '.brand',
                    'title' => '1. Welcome to Drem Homes CRM',
                    'content' => 'Premium enterprise sales operations platform designed for Dream Homes Interior Solutions (DHIS Group). Let us walk through the 20 integrated modules.',
                    'url' => 'crm',
                ],
                [
                    'target' => '.nav a[href$="/crm"], .nav a[href$="crm"]',
                    'title' => '2. Executive Dashboard',
                    'content' => 'Live business KPIs, revenue won, quotation pipeline, active enquiries, and team performance calculated directly from database records.',
                    'url' => 'crm',
                ],
                [
                    'target' => '.nav a[href*="crm/organisations"]',
                    'title' => '3. Organisations & Business Accounts',
                    'content' => 'Primary B2B account master for Developers, Architects, Interior Designers, and Corporates with 360° relationship history.',
                    'url' => 'crm/organisations',
                ],
                [
                    'target' => '.nav a[href*="crm/contacts"]',
                    'title' => '4. Contacts Directory',
                    'content' => 'Dedicated contacts directory for decision makers, purchase managers, and architects linked to business organisations.',
                    'url' => 'crm/contacts',
                ],
                [
                    'target' => '.nav a[href*="crm/activities"]',
                    'title' => '5. Sales Activities & Interactions',
                    'content' => 'Independent sales activities ledger tracking customer visits, telephone calls, architect presentations, and follow-ups.',
                    'url' => 'crm/activities',
                ],
                [
                    'target' => '.nav a[href*="crm/leads"]',
                    'title' => '6. Leads Pipeline (Kanban)',
                    'content' => 'Interactive 11-stage visual Kanban funnel. Drag-and-drop leads across Contact Pending, Contacted, Qualified, and Scheduled stages seamlessly.',
                    'url' => 'crm/leads',
                ],
                [
                    'target' => '.nav a[href*="crm/enquiries"]',
                    'title' => '7. Enquiries Management',
                    'content' => 'Central workspace for customer requirements, automated team round-robin assignment, activity logging, and stage transitions.',
                    'url' => 'crm/enquiries',
                ],
                [
                    'target' => '.nav a[href*="crm/customers"]',
                    'title' => '8. Customer 360 Repository',
                    'content' => 'Complete directory for Individuals, Architects, Builders, and Corporate accounts with GSTIN, billing, and multiple site addresses.',
                    'url' => 'crm/customers',
                ],
                [
                    'target' => '.nav a[href*="crm/projects"]',
                    'title' => '9. Architectural Projects',
                    'content' => 'Tracks interior villas, residential apartments, and commercial projects with site landmarks, budgets, and milestone progress.',
                    'url' => 'crm/projects',
                ],
                [
                    'target' => '.nav a[href*="crm/visits"], .nav a[href*="crm/measurements"]',
                    'title' => '10. Site Surveys & Measurements',
                    'content' => 'Schedule physical site visits and record millimeter architectural measurements with immutable version control (V1, V2, V3).',
                    'url' => 'crm/visits',
                ],
                [
                    'target' => '.nav a[href*="crm/quotations"]',
                    'title' => '11. Quotations & Pricing Engine',
                    'content' => 'Dynamic proposal builder supporting custom sizes, finish types, hardware, GST calculation, and server-side pricing.',
                    'url' => 'crm/quotations',
                ],
                [
                    'target' => '.nav a[href*="crm/approvals"]',
                    'title' => '12. Commercial Approval Centre',
                    'content' => 'Multi-level commercial governance matrix: requests routing automatically to Sales Managers or Admins with side-by-side comparison.',
                    'url' => 'crm/approvals',
                ],
                [
                    'target' => '.nav a[href*="crm/targets"]',
                    'title' => '13. Sales Targets & Performance',
                    'content' => 'Multi-frequency sales quotas (daily, weekly, monthly, quarterly, yearly) tracking actual revenue, orders, visits, and collections.',
                    'url' => 'crm/targets',
                ],
                [
                    'target' => '.nav a[href*="crm/orders"]',
                    'title' => '14. Sales Orders & Conversion',
                    'content' => 'Convert accepted quotations directly into confirmed sales orders with itemized billing, locking prices for operations.',
                    'url' => 'crm/orders',
                ],
                [
                    'target' => '.nav a[href*="crm/payments"]',
                    'title' => '15. Payments & Collections',
                    'content' => 'Record advance, progress, and handover payments via UPI, NEFT, Cheque or Cash with automated receipt generation.',
                    'url' => 'crm/payments',
                ],
                [
                    'target' => '.nav a[href*="crm/deliveries"], .nav a[href*="crm/installations"]',
                    'title' => '16. Deliveries & Installations',
                    'content' => 'Dispatch delivery challans, assign drivers, vehicle numbers, and verify on-site delivery and installation completion.',
                    'url' => 'crm/deliveries',
                ],
                [
                    'target' => '.nav a[href*="crm/service"]',
                    'title' => '17. After-Sales & Warranty Service',
                    'content' => 'Log customer service requests, maintenance tickets, and track 10-year profile warranty claims with resolution logs.',
                    'url' => 'crm/service',
                ],
                [
                    'target' => '.nav a[href*="crm/communication"]',
                    'title' => '18. Communication Centre',
                    'content' => 'Central notification engine with 28 editable HTML email templates, merge variables, outbox logs, and retry capability.',
                    'url' => 'crm/communication/templates',
                ],
                [
                    'target' => '.nav a[href*="crm/reports"]',
                    'title' => '19. Reports & Analytics Hub',
                    'content' => 'Deep business intelligence on conversion funnels, executive rankings, lead-source ROI, and instant Excel / CSV exports.',
                    'url' => 'crm/reports',
                ],
                [
                    'target' => '.nav a[href*="crm/settings"]',
                    'title' => '20. System Settings & Governance',
                    'content' => 'Configure automated assignment rules, commercial governance matrix, team memberships, and background scheduler.',
                    'url' => 'crm/settings',
                ],
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

