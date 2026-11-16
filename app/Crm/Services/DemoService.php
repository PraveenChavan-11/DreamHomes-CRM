<?php

namespace Crm\Services;

/** Administrator-controlled demo reset. Touches ONLY demo-owned rows (crm_demo_registry + their children). Never truncates. */
final class DemoService
{
    public const CONFIRM_PHRASE = 'RESET DEMO';

    /** @return list<int> */
    private static function ids(string $sql): array
    {
        return array_map('intval', array_column(Crm::db()->query($sql)->getResultArray(), 'id'));
    }

    private static function in(array $ids): string
    {
        return $ids ? implode(',', $ids) : '0';
    }

    /** Recoverable JSON snapshot of everything that will be removed. @return string path */
    public static function snapshot(): string
    {
        $db   = Crm::db();
        $scope = self::scope();
        $q = static fn (string $t, string $col, array $ids) => $ids ? $db->query("SELECT * FROM {$t} WHERE {$col} IN (" . self::in($ids) . ')')->getResultArray() : [];
        $data = [
            'taken_at' => Crm::now(),
            'customers' => $q('crm_customers', 'id', $scope['cust']), 'enquiries' => $q('crm_enquiries', 'id', $scope['enq']), 'followups' => $q('crm_followups', 'enquiry_id', $scope['enq']),
            'activities' => $q('crm_activities', 'enquiry_id', $scope['enq']), 'visits' => $q('crm_site_visits', 'enquiry_id', $scope['enq']), 'quotations' => $q('crm_quotations', 'id', $scope['qt']),
            'versions' => $q('crm_quotation_versions', 'quotation_id', $scope['qt']), 'negotiations' => $q('crm_negotiations', 'quotation_id', $scope['qt']),
            'orders' => $q('crm_sales_orders', 'enquiry_id', $scope['enq']), 'products' => $q('crm_products', 'id', $scope['prod']), 'users' => array_map(static fn ($u) => array_diff_key($u, ['password_hash' => 1]), $q('admin_users', 'id', $scope['usr'])),
            'workflow_events' => $q('crm_workflow_events', 'enquiry_id', $scope['enq']),
        ];
        $dir = WRITEPATH . 'crm/snapshots/';
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $path = $dir . 'demo-' . date('Ymd-His') . '.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /** @return array{cust:list<int>,enq:list<int>,qt:list<int>,prod:list<int>,usr:list<int>} */
    private static function scope(): array
    {
        $cust = self::ids("SELECT row_id id FROM crm_demo_registry WHERE table_name = 'crm_customers'");
        $enq  = self::ids("SELECT row_id id FROM crm_demo_registry WHERE table_name = 'crm_enquiries'");
        if ($cust) {
            $enq = array_values(array_unique(array_merge($enq, self::ids('SELECT id FROM crm_enquiries WHERE customer_id IN (' . self::in($cust) . ')'))));
        }
        $qt = $enq ? self::ids('SELECT id FROM crm_quotations WHERE enquiry_id IN (' . self::in($enq) . ')') : [];

        return [
            'cust' => $cust, 'enq' => $enq, 'qt' => $qt,
            'prod' => self::ids("SELECT row_id id FROM crm_demo_registry WHERE table_name = 'crm_products'"),
            'usr'  => self::ids("SELECT row_id id FROM crm_demo_registry WHERE table_name = 'admin_users'"),
        ];
    }

    /** @return int rows removed */
    public static function purgeDemoRows(): int
    {
        $db = Crm::db();
        $s  = self::scope();
        $n  = 0;
        $del = static function (string $table, string $where) use ($db, &$n) {
            $db->query("DELETE FROM {$table} WHERE {$where}");
            $n += $db->affectedRows();
        };
        $E = self::in($s['enq']); $C = self::in($s['cust']); $Q = self::in($s['qt']); $U = self::in($s['usr']); $P = self::in($s['prod']);
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        $del('crm_service_requests', "customer_id IN ({$C}) OR order_id IN (SELECT id FROM crm_sales_orders WHERE enquiry_id IN ({$E}))");
        $del('crm_installations', "order_id IN (SELECT id FROM crm_sales_orders WHERE enquiry_id IN ({$E}))");
        $del('crm_deliveries', "order_id IN (SELECT id FROM crm_sales_orders WHERE enquiry_id IN ({$E}))");
        $del('crm_payments', "order_id IN (SELECT id FROM crm_sales_orders WHERE enquiry_id IN ({$E}))");
        $del('crm_projects', "customer_id IN ({$C})");
        $del('crm_customer_addresses', "customer_id IN ({$C})");
        $del('crm_email_log', "customer_id IN ({$C}) OR enquiry_id IN ({$E})");
        $del('crm_notifications', "user_id IN ({$U})");
        $del('crm_workflow_events', "customer_id IN ({$C}) OR enquiry_id IN ({$E})");
        $del('crm_site_measurements', "visit_id IN (SELECT id FROM crm_site_visits WHERE enquiry_id IN ({$E}))");
        $del('crm_site_visits', "enquiry_id IN ({$E})");
        $del('crm_activities', "enquiry_id IN ({$E}) OR customer_id IN ({$C})");
        $del('crm_followups', "enquiry_id IN ({$E}) OR customer_id IN ({$C})");
        $del('crm_sales_order_items', "order_id IN (SELECT id FROM crm_sales_orders WHERE enquiry_id IN ({$E}))");
        $del('crm_sales_orders', "enquiry_id IN ({$E})");
        $del('crm_discount_approvals', "negotiation_id IN (SELECT id FROM crm_negotiations WHERE quotation_id IN ({$Q}))");
        $del('crm_negotiations', "quotation_id IN ({$Q})");
        $del('crm_quotation_items', "version_id IN (SELECT id FROM crm_quotation_versions WHERE quotation_id IN ({$Q}))");
        $del('crm_quotation_charges', "version_id IN (SELECT id FROM crm_quotation_versions WHERE quotation_id IN ({$Q}))");
        $del('crm_quotation_versions', "quotation_id IN ({$Q})");
        $del('crm_quotations', "id IN ({$Q})");
        $del('crm_enquiry_assignments', "enquiry_id IN ({$E})");
        $del('crm_enquiry_status_history', "enquiry_id IN ({$E})");
        $del('crm_enquiries', "id IN ({$E})");
        $del('crm_customer_contacts', "customer_id IN ({$C})");
        $del('crm_customers', "id IN ({$C})");
        $del('crm_product_price_history', "product_id IN ({$P})");
        $del('crm_products', "id IN ({$P})");
        $del('crm_team_members', "user_id IN ({$U})");
        $del('crm_teams', "name LIKE 'Demo Team%'");
        $del('crm_login_history', "user_id IN ({$U})");
        $del('crm_notification_prefs', "user_id IN ({$U})");
        $del('user_roles', "user_id IN ({$U})");
        $del('admin_users', "id IN ({$U})");
        $db->query('DELETE FROM crm_assignment_state');
        $db->query('DELETE FROM crm_demo_registry');
        // Re-start numbering only when no real (non-demo) records remain, so demo numbers are repeatable.
        foreach (['customer' => 'crm_customers', 'enquiry' => 'crm_enquiries', 'quotation' => 'crm_quotations', 'order' => 'crm_sales_orders', 'negotiation' => 'crm_negotiations', 'visit' => 'crm_site_visits'] as $key => $table) {
            if ((int) $db->table($table)->countAllResults() === 0) {
                $db->query('UPDATE crm_number_sequences SET next_value = 1 WHERE seq_key = ?', [$key]);
            }
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');

        return $n;
    }

    /** @return array{snapshot:string,removed:int,created:array<string,int>,integrity:list<string>} */
    public static function reset(array $actor, string $confirm): array
    {
        DemoSeeder::assertDemoEnvironment();
        if (! AccessScope::can($actor, 'demo.reset')) {
            throw new CrmException('You are not authorised to reset demo data.');
        }
        if ($confirm !== self::CONFIRM_PHRASE) {
            throw new CrmException('Type ' . self::CONFIRM_PHRASE . ' to confirm.');
        }
        $snapshot = self::snapshot();
        $removed  = self::purgeDemoRows();
        $created  = (new DemoSeeder())->run(true);
        $problems = IntegrityChecker::run();
        Crm::db()->table('crm_demo_reset_log')->insert([
            'initiated_by' => $actor['id'] ?? null, 'snapshot_path' => $snapshot, 'rows_removed' => $removed, 'rows_created' => array_sum($created),
            'integrity_ok' => $problems ? 0 : 1, 'notes' => $problems ? mb_substr(implode('; ', $problems), 0, 500) : null, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        AuditService::audit('demo.reset', 'system', null, ['user_id' => $actor['id'] ?? null, 'new' => ['removed' => $removed, 'created' => array_sum($created), 'integrity_problems' => count($problems)], 'meta' => ['snapshot' => basename($snapshot)]]);

        return ['snapshot' => $snapshot, 'removed' => $removed, 'created' => $created, 'integrity' => $problems];
    }
}
