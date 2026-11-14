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
        $tourKey = 'executive_onboarding';
        if (in_array('admin', $roles, true)) {
            $tourKey = 'admin_onboarding';
        } elseif (in_array('sales_manager', $roles, true)) {
            $tourKey = 'manager_onboarding';
        }

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

