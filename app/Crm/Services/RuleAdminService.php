<?php

namespace Crm\Services;

/** Admin edits to assignment rules, lead-scoring rules and the acting user's notification preferences. Every change is audited. */
final class RuleAdminService
{
    public const EVENTS = [
        'enquiry.assigned' => 'Enquiry assigned to me', 'enquiry.reassigned' => 'Enquiry reassigned', 'followup.due' => 'Follow-up due', 'followup.overdue' => 'Follow-up overdue',
        'visit.reminder' => 'Site visit reminder', 'discount.requested' => 'Discount approval requested', 'discount.approved' => 'Discount approved', 'discount.rejected' => 'Discount rejected',
        'quotation.accepted' => 'Customer accepted quotation', 'order.created' => 'Sales order created',
    ];

    /** @param array<int,array{priority?:mixed,active?:mixed}> $rules */
    public static function saveAssignmentRules(array $rules, array $actor): int
    {
        self::guard($actor);
        $db = Crm::db();
        $n  = 0;
        foreach ($rules as $id => $r) {
            $old = $db->table('crm_assignment_rules')->where('id', (int) $id)->get()->getRowArray();
            if (! $old) {
                continue;
            }
            $prio = max(1, min(9999, (int) ($r['priority'] ?? $old['priority'])));
            $act  = ! empty($r['active']) ? 1 : 0;
            if ($prio !== (int) $old['priority'] || $act !== (int) $old['is_active']) {
                $db->table('crm_assignment_rules')->where('id', (int) $id)->update(['priority' => $prio, 'is_active' => $act]);
                AuditService::audit('assignment_rule.updated', 'assignment_rule', $id, ['prev' => ['priority' => $old['priority'], 'active' => $old['is_active']], 'new' => ['priority' => $prio, 'active' => $act]]);
                $n++;
            }
        }

        return $n;
    }

    /** @param array<int,array{points?:mixed,active?:mixed}> $rules */
    public static function saveScoringRules(array $rules, array $actor): int
    {
        self::guard($actor);
        $db = Crm::db();
        $n  = 0;
        foreach ($rules as $id => $r) {
            $old = $db->table('crm_scoring_rules')->where('id', (int) $id)->get()->getRowArray();
            if (! $old) {
                continue;
            }
            $pts = max(-100, min(100, (int) ($r['points'] ?? $old['points'])));
            $act = ! empty($r['active']) ? 1 : 0;
            if ($pts !== (int) $old['points'] || $act !== (int) $old['is_active']) {
                $db->table('crm_scoring_rules')->where('id', (int) $id)->update(['points' => $pts, 'is_active' => $act]);
                AuditService::audit('scoring_rule.updated', 'scoring_rule', $id, ['prev' => ['points' => $old['points'], 'active' => $old['is_active']], 'new' => ['points' => $pts, 'active' => $act]]);
                $n++;
            }
        }

        return $n;
    }

    /** @param array<string,array{in_app?:mixed,email?:mixed}> $prefs */
    public static function saveMyPrefs(int $userId, array $prefs): void
    {
        $db = Crm::db();
        foreach (array_keys(self::EVENTS) as $ev) {
            $p = $prefs[$ev] ?? [];
            $db->query('INSERT INTO crm_notification_prefs (user_id,event_type,in_app,email) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE in_app = VALUES(in_app), email = VALUES(email)', [$userId, $ev, empty($p['in_app']) ? 0 : 1, empty($p['email']) ? 0 : 1]);
        }
        AuditService::audit('notification_prefs.updated', 'user', $userId, []);
    }

    private static function guard(array $actor): void
    {
        if (! AccessScope::can($actor, 'settings.manage')) {
            throw new CrmException('You do not have permission to change CRM configuration.');
        }
    }
}
