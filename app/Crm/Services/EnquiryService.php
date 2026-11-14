<?php

namespace Crm\Services;

final class EnquiryService
{
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public static function validate(array $d): array
    {
        $e = [];
        if (empty($d['customer_id']) || ! Crm::db()->table('crm_customers')->where('id', (int) $d['customer_id'])->countAllResults()) {
            $e['customer_id'] = 'A valid customer is required.';
        }
        if (! empty($d['priority']) && ! in_array($d['priority'], self::PRIORITIES, true)) {
            $e['priority'] = 'Invalid priority.';
        }
        if (isset($d['est_budget']) && $d['est_budget'] !== '' && (! is_numeric($d['est_budget']) || $d['est_budget'] < 0)) {
            $e['est_budget'] = 'Budget must be a positive number.';
        }
        if (! empty($d['expected_purchase_date']) && strtotime($d['expected_purchase_date']) === false) {
            $e['expected_purchase_date'] = 'Invalid date.';
        }

        return $e;
    }

    public static function score(array $d): int
    {
        $score = 0;
        foreach (Crm::db()->table('crm_scoring_rules')->where('is_active', 1)->get()->getResultArray() as $r) {
            $v = $d[$r['field_name']] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            $hit = match ($r['operator']) {
                'eq'       => (string) $v === $r['match_value'],
                'gte'      => is_numeric($v) && (float) $v >= (float) $r['match_value'],
                'lte'      => is_numeric($v) && (float) $v <= (float) $r['match_value'],
                'contains' => stripos((string) $v, $r['match_value']) !== false,
                default    => false,
            };
            $score += $hit ? (int) $r['points'] : 0;
        }

        return $score;
    }

    public static function dedupeHash(array $d): string
    {
        return sha1((int) $d['customer_id'] . '|' . (int) ($d['product_category_id'] ?? 0) . '|' . strtolower(trim((string) ($d['project_name'] ?? ''))));
    }

    /** Open enquiries that look like the same request (same customer + category + project). */
    public static function findDuplicates(array $d): array
    {
        return Crm::db()->table('crm_enquiries')->select('id, enquiry_no, status, project_name')
            ->where('dedupe_hash', self::dedupeHash($d))->whereIn('status', WorkflowService::ACTIVE)->get()->getResultArray();
    }

    /**
     * Create an enquiry. Auto-assigns when no owner is given (unless $autoAssign=false). Never overwrites an explicit owner.
     * @return array{id:int,enquiry_no:string,owner_id:?int,assignment:?string}
     */
    public static function create(array $d, array $actor, bool $allowDuplicate = false, bool $autoAssign = true, int $isDemo = 0, ?string $createdAt = null): array
    {
        if ($err = self::validate($d)) {
            throw CrmException::validation($err);
        }
        if (! $allowDuplicate && ($dups = self::findDuplicates($d))) {
            throw DuplicateException::with('A similar open enquiry already exists for this customer.', $dups);
        }
        $by = $actor['id'] ?? null;

        return Crm::tx(static function ($db) use ($d, $actor, $by, $autoAssign, $isDemo, $createdAt) {
            $cust = $db->table('crm_customers')->where('id', (int) $d['customer_id'])->get()->getRowArray();
            if (($cust['status'] ?? '') !== 'active') {
                throw new CrmException('Enquiries can only be created for active customers.');
            }
            $no  = NumberSequenceService::next('enquiry');
            $row = [
                'enquiry_no' => $no, 'customer_id' => (int) $d['customer_id'], 'project_name' => $d['project_name'] ?? null,
                'product_category_id' => $d['product_category_id'] ?? null, 'requirements' => $d['requirements'] ?? null, 'site_location' => $d['site_location'] ?? $cust['city'],
                'est_budget' => ($d['est_budget'] ?? '') !== '' ? $d['est_budget'] : null, 'expected_purchase_date' => $d['expected_purchase_date'] ?? null,
                'source_id' => $d['source_id'] ?? null, 'source_channel' => $d['source_channel'] ?? 'manual', 'priority' => $d['priority'] ?? 'medium',
                'description' => $d['description'] ?? null, 'status' => 'new', 'team_id' => $d['team_id'] ?? null, 'territory_id' => $d['territory_id'] ?? null,
                'dedupe_hash' => self::dedupeHash($d), 'import_job_id' => $d['import_job_id'] ?? null, 'is_demo' => $isDemo, 'created_by' => $by,
            ];
            $row['lead_score'] = self::score($row);
            if ($createdAt) {
                $row['created_at'] = $createdAt;
            }
            $db->table('crm_enquiries')->insert($row);
            $id = (int) $db->insertID();
            if ($isDemo) {
                $db->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', ['crm_enquiries', $id]);
            }
            $db->table('crm_enquiry_status_history')->insert(['enquiry_id' => $id, 'from_status' => null, 'to_status' => 'new', 'user_id' => $by, 'reason' => 'Enquiry created', 'created_at' => $createdAt ?? Crm::now()]);
            AuditService::workflow('enquiry.created', 'enquiry', $id, [
                'customer_id' => (int) $d['customer_id'], 'enquiry_id' => $id, 'entity_ref' => $no, 'user_id' => $by, 'is_demo' => $isDemo, 'created_at' => $createdAt ?? Crm::now(),
                'new' => ['status' => 'new', 'source' => $row['source_channel'], 'priority' => $row['priority'], 'score' => $row['lead_score']],
            ]);

            $owner = null;
            $how   = null;
            if (! empty($d['current_owner_id'])) {
                self::applyAssignment($id, (int) $d['current_owner_id'], 'manual', 'Assigned at creation', $actor, true, $createdAt);
                $owner = (int) $d['current_owner_id'];
                $how   = 'manual';
            } elseif ($autoAssign) {
                $pick = AssignmentService::pick($row + ['city' => $cust['city'], 'pin_code' => $cust['pin_code']]);
                if ($pick) {
                    self::applyAssignment($id, $pick['user_id'], $pick['method'], $pick['reason'], $actor, true, $createdAt);
                    $owner = $pick['user_id'];
                    $how   = $pick['method'];
                } else {
                    // Fallback: no eligible salesperson. Leave unassigned and alert managers/admins.
                    NotificationService::notifyRoles(['sales_manager', 'admin'], 'enquiry.unassigned', "Enquiry {$no} needs manual assignment", 'No eligible salesperson was available.', "crm/enquiries/{$id}", "unassigned:{$id}");
                    $how = 'fallback_unassigned';
                }
            }

            return ['id' => $id, 'enquiry_no' => $no, 'owner_id' => $owner, 'assignment' => $how];
        });
    }

    /** Internal: write assignment + owner + status, notify. Caller has already authorised. */
    private static function applyAssignment(int $enquiryId, int $toUser, string $method, ?string $reason, array $actor, bool $initial, ?string $at = null): void
    {
        $db = Crm::db();
        $e  = $db->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray();
        $u  = $db->table('admin_users')->where('id', $toUser)->get()->getRowArray();
        if (! $u || (int) $u['is_active'] !== 1) {
            throw new CrmException('The selected salesperson is not active.');
        }
        $roles = array_column($db->query('SELECT r.slug FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ?', [$toUser])->getResultArray(), 'slug');
        if (! array_intersect($roles, ['sales_executive', 'sales_manager'])) {
            throw new CrmException('Enquiries can only be assigned to sales users.');
        }
        $from = $e['current_owner_id'] ? (int) $e['current_owner_id'] : null;
        $upd  = ['current_owner_id' => $toUser, 'team_id' => $u['team_id'] ?: $e['team_id']];
        $db->table('crm_enquiries')->where('id', $enquiryId)->update($upd);
        $db->table('crm_enquiry_assignments')->insert([
            'enquiry_id' => $enquiryId, 'from_user_id' => $from, 'to_user_id' => $toUser, 'method' => $method, 'reason' => $reason ? mb_substr($reason, 0, 255) : null,
            'assigned_by' => $actor['id'] ?? null, 'created_at' => $at ?? Crm::now(),
        ]);
        $isDemo = (int) $e['is_demo'];
        AuditService::workflow($initial ? 'enquiry.assigned' : 'enquiry.reassigned', 'enquiry', $enquiryId, [
            'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'] ?? null,
            'prev' => ['owner_id' => $from], 'new' => ['owner_id' => $toUser, 'method' => $method], 'reason' => $reason, 'is_demo' => $isDemo, 'created_at' => $at ?? Crm::now(),
        ]);
        if ($e['status'] === 'new') {
            WorkflowService::transition($enquiryId, 'assigned', AccessScope::system(), 'Assigned', []);
            if ($at) {
                $db->query('UPDATE crm_enquiry_status_history SET created_at = ? WHERE enquiry_id = ? AND to_status = ? ORDER BY id DESC LIMIT 1', [$at, $enquiryId, 'assigned']);
            }
        }
        NotificationService::notify($toUser, $initial ? 'enquiry.assigned' : 'enquiry.reassigned', ($initial ? 'New enquiry assigned: ' : 'Enquiry reassigned to you: ') . $e['enquiry_no'], $e['project_name'], "crm/enquiries/{$enquiryId}", "assign:{$enquiryId}:{$toUser}:" . ($from ?? 0), $isDemo);
        if ($from && $from !== $toUser) {
            NotificationService::notify($from, 'enquiry.reassigned_away', "Enquiry {$e['enquiry_no']} was reassigned", null, "crm/enquiries/{$enquiryId}", "assign-away:{$enquiryId}:{$from}:{$toUser}", $isDemo);
        }
    }

    /** Assign an enquiry that has NO owner. Refuses to overwrite — use reassign(). */
    public static function assign(int $enquiryId, int $toUser, array $actor, ?string $reason = null): void
    {
        if (! AccessScope::can($actor, 'enquiries.assign')) {
            throw new CrmException('You do not have permission to assign enquiries.');
        }
        Crm::tx(static function ($db) use ($enquiryId, $toUser, $actor, $reason) {
            $e = $db->query('SELECT * FROM crm_enquiries WHERE id = ? FOR UPDATE', [$enquiryId])->getRowArray() ?? throw new CrmException('Enquiry not found.');
            if ($e['current_owner_id']) {
                throw new CrmException('This enquiry already has an owner. Use reassignment instead.');
            }
            self::applyAssignment($enquiryId, $toUser, 'manual', $reason, $actor, true);
        });
    }

    public static function reassign(int $enquiryId, int $toUser, array $actor, string $reason): void
    {
        if (! AccessScope::can($actor, 'enquiries.reassign')) {
            throw new CrmException('You do not have permission to reassign enquiries.');
        }
        if (trim($reason) === '') {
            throw new CrmException('A reason is required to reassign an enquiry.');
        }
        Crm::tx(static function ($db) use ($enquiryId, $toUser, $actor, $reason) {
            $e = $db->query('SELECT * FROM crm_enquiries WHERE id = ? FOR UPDATE', [$enquiryId])->getRowArray() ?? throw new CrmException('Enquiry not found.');
            if (! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
                throw new CrmException('You do not have access to this enquiry.');
            }
            if (! WorkflowService::isActive($e['status'])) {
                throw new CrmException('Closed enquiries must be reopened before reassignment.');
            }
            if ((int) $e['current_owner_id'] === $toUser) {
                throw new CrmException('The enquiry is already assigned to that salesperson.');
            }
            self::applyAssignment($enquiryId, $toUser, 'reassign', $reason, $actor, false);
            // Pending follow-ups follow the new owner; completed ones stay attributed to who did them.
            $db->table('crm_followups')->where(['enquiry_id' => $enquiryId, 'status' => 'pending'])->update(['assigned_to' => $toUser]);
        });
    }
}
