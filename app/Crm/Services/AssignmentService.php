<?php

namespace Crm\Services;

/** Eligible-salesperson selection: round-robin, workload, team and territory rules with fallback. Never overwrites an owner. */
final class AssignmentService
{
    /** @return list<int> ids of active sales executives (optionally restricted) ordered by id */
    public static function eligibleUsers(?int $teamId = null): array
    {
        $db = Crm::db();
        $b  = $db->table('admin_users u')->select('u.id')->distinct()
            ->join('user_roles ur', 'ur.user_id = u.id')->join('roles r', 'r.id = ur.role_id')
            ->where('u.is_active', 1)->where('r.slug', 'sales_executive');
        if ($teamId) {
            $b->join('crm_team_members tm', 'tm.user_id = u.id')->where('tm.team_id', $teamId)->where('tm.is_active', 1);
        }

        return array_map('intval', array_column($b->orderBy('u.id')->get()->getResultArray(), 'id'));
    }

    public static function openLoad(int $userId): int
    {
        return Crm::db()->table('crm_enquiries')->where('current_owner_id', $userId)->whereIn('status', WorkflowService::ACTIVE)->countAllResults();
    }

    /**
     * @param array<string,mixed> $enquiry row-like: team_id, territory_id, product_category_id, customer city/pin via 'city','pin_code'
     * @return array{user_id:int,method:string,reason:string}|null null => no eligible salesperson (caller handles fallback)
     */
    public static function pick(array $enquiry): ?array
    {
        $db    = Crm::db();
        $rules = $db->table('crm_assignment_rules')->where('is_active', 1)->orderBy('priority')->get()->getResultArray();
        foreach ($rules as $r) {
            if ($r['category_id'] && (int) $r['category_id'] !== (int) ($enquiry['product_category_id'] ?? 0)) {
                continue;
            }
            $team = $r['team_id'] ? (int) $r['team_id'] : (! empty($enquiry['team_id']) ? (int) $enquiry['team_id'] : null);
            if ($r['mode'] === 'territory') {
                $terr = self::matchTerritory($enquiry);
                if (! $terr) {
                    continue;
                }
                $team = $terr['team_id'] ? (int) $terr['team_id'] : $team;
            }
            $pool = self::eligibleUsers($team);
            if (! $pool) {
                continue;
            }
            $mode = $r['mode'];
            if ($mode === 'workload') {
                usort($pool, static fn ($a, $b) => [self::openLoad($a), $a] <=> [self::openLoad($b), $b]);

                return ['user_id' => $pool[0], 'method' => 'auto_workload', 'reason' => 'Fewest open enquiries'];
            }
            $scope = 'rr:' . ($team ?? 'all');
            $last  = $db->table('crm_assignment_state')->where('scope_key', $scope)->get()->getRowArray();
            $next  = $pool[0];
            if ($last && $last['last_user_id']) {
                foreach ($pool as $uid) {
                    if ($uid > (int) $last['last_user_id']) {
                        $next = $uid;
                        break;
                    }
                }
            }
            $db->query('INSERT INTO crm_assignment_state (scope_key, last_user_id) VALUES (?,?) ON DUPLICATE KEY UPDATE last_user_id = VALUES(last_user_id)', [$scope, $next]);
            $method = match ($mode) { 'team' => 'auto_team', 'territory' => 'auto_territory', default => 'auto_round_robin' };

            return ['user_id' => $next, 'method' => $method, 'reason' => 'Rule: ' . $mode . ($team ? " (team {$team})" : '')];
        }

        return null;
    }

    private static function matchTerritory(array $e): ?array
    {
        $db = Crm::db();
        foreach ($db->table('crm_territories')->where('is_active', 1)->get()->getResultArray() as $t) {
            if (! empty($e['territory_id']) && (int) $e['territory_id'] === (int) $t['id']) {
                return $t;
            }
            if ($t['pin_prefix'] && ! empty($e['pin_code']) && str_starts_with($e['pin_code'], $t['pin_prefix'])) {
                return $t;
            }
            if ($t['city'] && ! empty($e['city']) && strcasecmp($t['city'], $e['city']) === 0) {
                return $t;
            }
        }

        return null;
    }
}
