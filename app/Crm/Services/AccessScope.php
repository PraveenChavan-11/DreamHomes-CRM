<?php

namespace Crm\Services;

/**
 * Row-level visibility. Every controller must route record access through here (IDOR defence).
 *  all  : super_admin, admin, management
 *  team : sales_manager  -> self + members of teams they manage
 *  own  : sales_executive -> self only
 * Multiple roles => the widest scope wins.
 */
final class AccessScope
{
    /** Automation actor (cron/commands): bypasses permission & scope, still fully audited as source=cli. */
    public static function system(): array
    {
        return ['id' => null, 'name' => 'System', 'roles' => ['system'], 'permissions' => [], 'max_discount_pct' => '0'];
    }

    /** @param array{id:int|null,roles:list<string>,permissions:list<string>} $user */
    public static function level(array $user): string
    {
        $roles = $user['roles'] ?? [];
        if (array_intersect($roles, ['system', 'super_admin', 'admin', 'management'])) {
            return 'all';
        }
        if (in_array('sales_manager', $roles, true)) {
            return 'team';
        }

        return 'own';
    }

    public static function isSuper(array $user): bool
    {
        return (bool) array_intersect(['super_admin', 'system'], $user['roles'] ?? []);
    }

    public static function can(array $user, string $permission): bool
    {
        return self::isSuper($user) || in_array($permission, $user['permissions'] ?? [], true);
    }

    /** @return list<int>|null null = unrestricted */
    public static function visibleUserIds(array $user): ?array
    {
        $level = self::level($user);
        if ($level === 'all') {
            return null;
        }
        $ids = [(int) $user['id']];
        if ($level === 'team') {
            $db   = Crm::db();
            $rows = $db->query(
                'SELECT DISTINCT tm.user_id FROM crm_team_members tm JOIN crm_teams t ON t.id = tm.team_id
                 WHERE t.manager_id = ? AND tm.is_active = 1',
                [(int) $user['id']]
            )->getResultArray();
            foreach ($rows as $r) {
                $ids[] = (int) $r['user_id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /** Apply enquiry visibility to a query builder on table alias `e` (crm_enquiries). */
    public static function scopeEnquiries($builder, array $user, string $alias = 'e')
    {
        $ids = self::visibleUserIds($user);
        if ($ids !== null) {
            $builder->groupStart()->whereIn("{$alias}.current_owner_id", $ids)->orWhereIn("{$alias}.created_by", $ids)->groupEnd();
        }

        return $builder;
    }

    public static function canSeeEnquiry(array $user, int $enquiryId): bool
    {
        $ids = self::visibleUserIds($user);
        $row = Crm::db()->table('crm_enquiries')->select('current_owner_id, created_by')->where('id', $enquiryId)->get()->getRowArray();
        if (! $row) {
            return false;
        }

        return $ids === null || in_array((int) $row['current_owner_id'], $ids, true) || in_array((int) $row['created_by'], $ids, true);
    }

    public static function canSeeCustomer(array $user, int $customerId): bool
    {
        $ids = self::visibleUserIds($user);
        if ($ids === null) {
            return true;
        }
        $db = Crm::db();
        if ($db->table('crm_customers')->where('id', $customerId)->groupStart()->whereIn('owner_user_id', $ids)->orWhereIn('created_by', $ids)->groupEnd()->countAllResults() > 0) {
            return true;
        }

        return $db->table('crm_enquiries')->where('customer_id', $customerId)->groupStart()->whereIn('current_owner_id', $ids)->orWhereIn('created_by', $ids)->groupEnd()->countAllResults() > 0;
    }

    /** Scope customers query on alias `c`. */
    public static function scopeCustomers($builder, array $user, string $alias = 'c')
    {
        $ids = self::visibleUserIds($user);
        if ($ids !== null) {
            $in = implode(',', array_map('intval', $ids));
            $builder->where("({$alias}.owner_user_id IN ({$in}) OR {$alias}.created_by IN ({$in}) OR EXISTS (SELECT 1 FROM crm_enquiries ee WHERE ee.customer_id = {$alias}.id AND (ee.current_owner_id IN ({$in}) OR ee.created_by IN ({$in}))))", null, false);
        }

        return $builder;
    }
}
