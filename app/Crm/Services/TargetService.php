<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class TargetService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    public static function list(array $filters = [], array $actor = [], int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $b = $db->table('crm_sales_targets t')
            ->select("t.*, CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name, tm.name AS team_name, CONCAT(assigner.first_name, ' ', assigner.last_name) AS assigner_name")
            ->join('admin_users u', 'u.id = t.user_id', 'left')
            ->join('crm_teams tm', 'tm.id = t.team_id', 'left')
            ->join('admin_users assigner', 'assigner.id = t.assigned_by_user_id', 'left');

        if (! empty($filters['user_id'])) {
            $b->where('t.user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['team_id'])) {
            $b->where('t.team_id', (int) $filters['team_id']);
        }
        if (! empty($filters['frequency'])) {
            $b->where('t.frequency', $filters['frequency']);
        }
        if (! empty($filters['status'])) {
            $b->where('t.status', $filters['status']);
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('t.period_start', 'DESC')->orderBy('t.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        // Attach live performance calculations to each target
        foreach ($rows as &$r) {
            $perf = self::computeActuals($r);
            $r['achieved_revenue'] = $perf['revenue'];
            $r['revenue_pct'] = (float) $r['revenue_target'] > 0 ? round(($perf['revenue'] / (float) $r['revenue_target']) * 100, 1) : 0;
            $r['remaining_revenue'] = max(0, (float) $r['revenue_target'] - $perf['revenue']);
            $r['achieved_orders'] = $perf['orders'];
            $r['orders_pct'] = (int) $r['confirmed_orders_target'] > 0 ? round(($perf['orders'] / (int) $r['confirmed_orders_target']) * 100, 1) : 0;
            $r['achieved_collections'] = $perf['collections'];
            $r['collections_pct'] = (float) $r['collections_target'] > 0 ? round(($perf['collections'] / (float) $r['collections_target']) * 100, 1) : 0;
            $r['achieved_activities'] = $perf['activities'];
            $r['achieved_quotations'] = $perf['quotations'];
            $r['pipeline_value'] = $perf['pipeline'];
        }
        unset($r);

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function get(int $id): ?array
    {
        $db = self::db();
        $row = $db->table('crm_sales_targets t')
            ->select("t.*, CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name, u.email AS salesperson_email, tm.name AS team_name, CONCAT(assigner.first_name, ' ', assigner.last_name) AS assigner_name")
            ->join('admin_users u', 'u.id = t.user_id', 'left')
            ->join('crm_teams tm', 'tm.id = t.team_id', 'left')
            ->join('admin_users assigner', 'assigner.id = t.assigned_by_user_id', 'left')
            ->where('t.id', $id)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }

        $perf = self::computeActuals($row);
        $row['actuals'] = $perf;
        $row['revenue_pct'] = (float) $row['revenue_target'] > 0 ? round(($perf['revenue'] / (float) $row['revenue_target']) * 100, 1) : 0;
        $row['remaining_revenue'] = max(0, (float) $row['revenue_target'] - $perf['revenue']);

        return $row;
    }

    public static function find(int $id): ?array
    {
        return self::get($id);
    }

    public static function calculateActuals(int $id): array
    {
        $target = self::get($id) ?? throw new CrmException('Target not found.');
        $actuals = $target['actuals'] ?? self::computeActuals($target);
        return [
            'actual_revenue' => $actuals['revenue'] ?? 0.0,
            'revenue_pct' => $target['revenue_pct'] ?? 0.0,
            'actual_orders' => $actuals['orders'] ?? 0,
            'actual_activities' => $actuals['activities'] ?? 0,
            'actual_quotations' => $actuals['quotations'] ?? 0,
            'actual_collections' => $actuals['collections'] ?? 0.0,
            'pipeline_value' => $actuals['pipeline'] ?? 0.0,
        ];
    }

    public static function create(array $data, int $actorId): array
    {
        $db = self::db();
        $targetNo = NumberSequenceService::next('target');
        $now = Crm::now();

        $record = [
            'target_no' => $targetNo,
            'user_id' => ! empty($data['user_id']) ? (int) $data['user_id'] : null,
            'team_id' => ! empty($data['team_id']) ? (int) $data['team_id'] : null,
            'frequency' => $data['frequency'] ?? 'monthly',
            'period_start' => $data['period_start'] ?? date('Y-m-01'),
            'period_end' => $data['period_end'] ?? date('Y-m-t'),
            'period_label' => $data['period_label'] ?? date('F Y'),
            'revenue_target' => ! empty($data['revenue_target']) ? (float) $data['revenue_target'] : 0,
            'confirmed_orders_target' => ! empty($data['confirmed_orders_target']) ? (int) $data['confirmed_orders_target'] : 0,
            'organisations_target' => ! empty($data['organisations_target']) ? (int) $data['organisations_target'] : 0,
            'contacts_target' => ! empty($data['contacts_target']) ? (int) $data['contacts_target'] : 0,
            'leads_target' => ! empty($data['leads_target']) ? (int) $data['leads_target'] : 0,
            'qualified_leads_target' => ! empty($data['qualified_leads_target']) ? (int) $data['qualified_leads_target'] : 0,
            'site_visits_target' => ! empty($data['site_visits_target']) ? (int) $data['site_visits_target'] : 0,
            'meetings_target' => ! empty($data['meetings_target']) ? (int) $data['meetings_target'] : 0,
            'calls_target' => ! empty($data['calls_target']) ? (int) $data['calls_target'] : 0,
            'followups_target' => ! empty($data['followups_target']) ? (int) $data['followups_target'] : 0,
            'quotations_target' => ! empty($data['quotations_target']) ? (int) $data['quotations_target'] : 0,
            'quotations_approved_target' => ! empty($data['quotations_approved_target']) ? (int) $data['quotations_approved_target'] : 0,
            'quotations_accepted_target' => ! empty($data['quotations_accepted_target']) ? (int) $data['quotations_accepted_target'] : 0,
            'conversion_rate_target' => ! empty($data['conversion_rate_target']) ? (float) $data['conversion_rate_target'] : 0,
            'collections_target' => ! empty($data['collections_target']) ? (float) $data['collections_target'] : 0,
            'assigned_by_user_id' => $actorId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_sales_targets')->insert($record);
        $id = (int) $db->insertID();

        // Notify user if target is assigned to a specific salesperson
        if (! empty($record['user_id'])) {
            NotificationService::trigger('target.assigned', [
                'recipient_id' => (int) $record['user_id'],
                'target_id' => $id,
                'target_no' => $targetNo,
                'period' => $record['period_label'],
                'revenue_target' => $record['revenue_target'],
                'message' => "New sales target assigned for {$record['period_label']}: Revenue target ₹" . number_format($record['revenue_target'], 2),
            ]);
        }

        AuditService::record('target.assigned', 'crm_sales_targets', $id, [
            'target_no' => $targetNo,
            'period' => $record['period_label'],
        ]);

        return self::get($id);
    }

    public static function update(int $id, array $data, int $actorId): array
    {
        $db = self::db();
        $target = self::get($id) ?? throw new CrmException('Target not found.');

        $fields = [
            'revenue_target' => isset($data['revenue_target']) ? (float) $data['revenue_target'] : $target['revenue_target'],
            'confirmed_orders_target' => isset($data['confirmed_orders_target']) ? (int) $data['confirmed_orders_target'] : $target['confirmed_orders_target'],
            'organisations_target' => isset($data['organisations_target']) ? (int) $data['organisations_target'] : $target['organisations_target'],
            'contacts_target' => isset($data['contacts_target']) ? (int) $data['contacts_target'] : $target['contacts_target'],
            'leads_target' => isset($data['leads_target']) ? (int) $data['leads_target'] : $target['leads_target'],
            'site_visits_target' => isset($data['site_visits_target']) ? (int) $data['site_visits_target'] : $target['site_visits_target'],
            'calls_target' => isset($data['calls_target']) ? (int) $data['calls_target'] : $target['calls_target'],
            'quotations_target' => isset($data['quotations_target']) ? (int) $data['quotations_target'] : $target['quotations_target'],
            'collections_target' => isset($data['collections_target']) ? (float) $data['collections_target'] : $target['collections_target'],
            'revision_notes' => $data['revision_notes'] ?? $target['revision_notes'],
            'status' => $data['status'] ?? $target['status'],
            'updated_at' => Crm::now(),
        ];

        $db->table('crm_sales_targets')->where('id', $id)->update($fields);
        AuditService::record('target.revised', 'crm_sales_targets', $id, ['changes' => array_keys($data)]);

        return self::get($id);
    }

    /**
     * Compute real-time actuals from transactions for a given target period.
     */
    public static function computeActuals(array $target): array
    {
        $db = self::db();
        $start = $target['period_start'] . ' 00:00:00';
        $end = $target['period_end'] . ' 23:59:59';
        $userId = ! empty($target['user_id']) ? (int) $target['user_id'] : null;

        // 1. Confirmed Orders & Revenue
        $ordersQuery = $db->table('crm_sales_orders')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->whereNotIn('status', ['cancelled']);
        if ($userId) {
            $ordersQuery->where('salesperson_id', $userId);
        }
        $orders = $ordersQuery->get()->getResultArray();
        $revenue = 0.0;
        foreach ($orders as $o) {
            $revenue += (float) ($o['grand_total'] ?? 0);
        }

        // 2. Collections (Payments Received)
        $payQuery = $db->table('crm_payments p')
            ->select('p.*')
            ->where('p.payment_date >=', $target['period_start'])
            ->where('p.payment_date <=', $target['period_end'])
            ->where('p.status', 'confirmed');
        if ($userId) {
            $payQuery->join('crm_sales_orders so', 'so.id = p.order_id', 'left')->where('so.salesperson_id', $userId);
        }
        $payments = $payQuery->get()->getResultArray();
        $collections = 0.0;
        foreach ($payments as $p) {
            $collections += (float) ($p['amount'] ?? 0);
        }

        // 3. Sales Activities Completed
        $actQuery = $db->table('crm_sales_activities')
            ->where('activity_date >=', $target['period_start'])
            ->where('activity_date <=', $target['period_end'])
            ->where('status', 'completed');
        if ($userId) {
            $actQuery->where('assigned_user_id', $userId);
        }
        $activitiesCount = $actQuery->countAllResults();

        // 4. Quotations Prepared & Approved
        $quoteQuery = $db->table('crm_quotations')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end);
        if ($userId) {
            $quoteQuery->where('created_by', $userId);
        }
        $quotationsCount = $quoteQuery->countAllResults();

        // 5. Active Pipeline Value (Open enquiries)
        $pipeQuery = $db->table('crm_enquiries')
            ->whereNotIn('status', ['won', 'lost', 'cancelled']);
        if ($userId) {
            $pipeQuery->where('current_owner_id', $userId);
        }
        $openLeads = $pipeQuery->get()->getResultArray();
        $pipelineVal = 0.0;
        foreach ($openLeads as $l) {
            $pipelineVal += (float) ($l['est_budget'] ?? 0);
        }

        return [
            'revenue' => $revenue,
            'orders' => count($orders),
            'collections' => $collections,
            'activities' => $activitiesCount,
            'quotations' => $quotationsCount,
            'pipeline' => $pipelineVal,
        ];
    }
}
