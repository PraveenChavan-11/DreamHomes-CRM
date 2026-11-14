<?php

namespace Crm\Services;

/** Dashboard metrics computed live from transactions, scoped to what the viewer may see. No hard-coded numbers. */
final class DashboardService
{
    private static function owners(array $actor, string $col = 'e.current_owner_id'): string
    {
        $ids = AccessScope::visibleUserIds($actor);

        return $ids === null ? '1=1' : "{$col} IN (" . implode(',', array_map('intval', $ids)) . ')';
    }

    /** @return array<string,mixed> */
    public static function metrics(array $actor): array
    {
        $db = Crm::db();
        $o  = self::owners($actor);
        $fo = self::owners($actor, 'f.assigned_to');
        $now = Crm::now();
        $one = static fn (string $sql, array $b = []) => $db->query($sql, $b)->getRowArray();
        $m = [];

        $m['enquiries_total']  = (int) $one("SELECT COUNT(*) c FROM crm_enquiries e WHERE {$o}")['c'];
        $m['enquiries_active'] = (int) $one("SELECT COUNT(*) c FROM crm_enquiries e WHERE {$o} AND e.status IN ('new','assigned','contacted','qualified','site_visit','quotation_preparation','quotation_sent','negotiation')")['c'];
        $m['unassigned']       = (int) $one("SELECT COUNT(*) c FROM crm_enquiries e WHERE e.current_owner_id IS NULL AND e.status = 'new' AND {$o}" . (AccessScope::visibleUserIds($actor) === null ? '' : ' AND 1=0'))['c'];
        $m['by_stage']         = $db->query("SELECT e.status, COUNT(*) n FROM crm_enquiries e WHERE {$o} GROUP BY e.status")->getResultArray();
        $m['customers_total']  = (int) AccessScope::scopeCustomers($db->table('crm_customers c'), $actor)->countAllResults();

        $m['followups_pending']  = (int) $one("SELECT COUNT(*) c FROM crm_followups f WHERE f.status = 'pending' AND {$fo}")['c'];
        $m['followups_today']    = (int) $one("SELECT COUNT(*) c FROM crm_followups f WHERE f.status = 'pending' AND DATE(f.due_at) = DATE(?) AND {$fo}", [$now])['c'];
        $m['followups_upcoming'] = (int) $one("SELECT COUNT(*) c FROM crm_followups f WHERE f.status = 'pending' AND f.due_at > ? AND DATE(f.due_at) > DATE(?) AND {$fo}", [$now, $now])['c'];
        $m['followups_overdue']  = (int) $one("SELECT COUNT(*) c FROM crm_followups f WHERE ((f.status = 'pending' AND f.due_at < ?) OR f.status = 'missed') AND {$fo}", [$now])['c'];
        $m['visits_upcoming']    = (int) $one("SELECT COUNT(*) c FROM crm_site_visits v WHERE v.status = 'scheduled' AND v.scheduled_at >= ? AND " . self::owners($actor, 'v.assigned_to'), [$now])['c'];
        $m['visits_completed']   = (int) $one("SELECT COUNT(*) c FROM crm_site_visits v WHERE v.status = 'completed' AND " . self::owners($actor, 'v.assigned_to'))['c'];

        $qt = $one("SELECT COUNT(*) c, COALESCE(SUM(v.grand_total),0) s FROM crm_quotations q JOIN crm_quotation_versions v ON v.id = q.current_version_id JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE q.status <> 'cancelled' AND {$o}");
        $m['quotations_count'] = (int) $qt['c'];
        $m['quotations_value'] = $qt['s'];
        $pend = $one("SELECT COUNT(*) c, COALESCE(SUM(n.proposed_price),0) s FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE n.status IN ('pending','escalated','approved','returned') AND {$o}");
        $m['negotiation_open_count'] = (int) $pend['c'];
        $m['negotiation_open_value'] = $pend['s'];
        $m['approvals_pending'] = (int) $one("SELECT COUNT(*) c FROM crm_negotiations n JOIN crm_quotations q ON q.id = n.quotation_id JOIN crm_enquiries e ON e.id = q.enquiry_id WHERE n.status IN ('pending','escalated') AND {$o}")['c'];

        $won = $one("SELECT COUNT(*) c, COALESCE(SUM(e.final_value),0) s FROM crm_enquiries e WHERE e.status = 'won' AND {$o}");
        $m['won_count'] = (int) $won['c'];
        $m['won_value'] = $won['s'];
        $m['lost_count'] = (int) $one("SELECT COUNT(*) c FROM crm_enquiries e WHERE e.status = 'lost' AND {$o}")['c'];
        $m['conversion_pct'] = ($m['won_count'] + $m['lost_count']) > 0 ? round(100 * $m['won_count'] / ($m['won_count'] + $m['lost_count']), 1) : null;
        $m['orders_confirmed_value'] = $one("SELECT COALESCE(SUM(od.grand_total),0) s FROM crm_sales_orders od JOIN crm_enquiries e ON e.id = od.enquiry_id WHERE od.status IN ('confirmed','closed') AND {$o}")['s'];
        $m['orders_count'] = (int) $one("SELECT COUNT(*) c FROM crm_sales_orders od JOIN crm_enquiries e ON e.id = od.enquiry_id WHERE od.status <> 'cancelled' AND {$o}")['c'];

        $m['team_performance'] = $db->query("SELECT CONCAT(u.first_name,' ',u.last_name) name, u.id user_id, COUNT(e.id) enquiries,
              SUM(e.status = 'won') won, SUM(e.status = 'lost') lost, COALESCE(SUM(CASE WHEN e.status = 'won' THEN e.final_value END),0) won_value,
              SUM(e.status IN ('new','assigned','contacted','qualified','site_visit','quotation_preparation','quotation_sent','negotiation')) active
              FROM crm_enquiries e JOIN admin_users u ON u.id = e.current_owner_id WHERE {$o} GROUP BY u.id, u.first_name, u.last_name ORDER BY won_value DESC")->getResultArray();
        $m['lead_sources'] = $db->query("SELECT COALESCE(s.name,'Unknown') source, COUNT(*) n, SUM(e.status = 'won') won FROM crm_enquiries e LEFT JOIN crm_lead_sources s ON s.id = e.source_id WHERE {$o} GROUP BY s.name ORDER BY n DESC")->getResultArray();
        $m['monthly'] = $db->query("SELECT DATE_FORMAT(e.created_at,'%Y-%m') ym, COUNT(*) enquiries, SUM(e.status = 'won') won, COALESCE(SUM(CASE WHEN e.status = 'won' THEN e.final_value END),0) won_value
              FROM crm_enquiries e WHERE {$o} AND e.created_at >= DATE_SUB(DATE_FORMAT(?, '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym", [$now])->getResultArray();
        $m['product_performance'] = $db->query("SELECT COALESCE(c.name,'Other') category, COUNT(DISTINCT od.id) orders, COALESCE(SUM(i.line_total),0) value FROM crm_sales_order_items i
              JOIN crm_sales_orders od ON od.id = i.order_id JOIN crm_enquiries e ON e.id = od.enquiry_id LEFT JOIN crm_products p ON p.id = i.product_id LEFT JOIN crm_product_categories c ON c.id = p.category_id
              WHERE od.status <> 'cancelled' AND {$o} GROUP BY c.name ORDER BY value DESC")->getResultArray();
        $m['generated_at'] = $now;

        return $m;
    }
}
