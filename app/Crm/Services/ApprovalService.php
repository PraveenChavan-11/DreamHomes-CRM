<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class ApprovalService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    /**
     * Seed Default Commercial Approval Rules Matrix (Settings > Sales Governance).
     */
    public static function seedDefaultRules(): void
    {
        $db = self::db();
        if ($db->table('crm_approval_rules')->countAllResults() > 0) {
            return;
        }

        $now = Crm::now();
        $rules = [
            [
                'rule_code' => 'RULE-DISC-01',
                'rule_name' => 'Sales Executive Discretion (Up to 5%)',
                'rule_description' => 'Discounts up to 5% are within authorised representative limits.',
                'salesperson_role' => 'sales_rep',
                'min_discount_pct' => 0.00,
                'max_discount_pct' => 5.00,
                'requires_min_price_check' => 1,
                'requires_margin_check' => 1,
                'margin_floor_pct' => 15.00,
                'required_role' => 'sales_rep',
                'approval_level' => 1,
                'is_active' => 1,
                'priority' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rule_code' => 'RULE-DISC-02',
                'rule_name' => 'Sales Manager Approval (Above 5% up to 10%)',
                'rule_description' => 'Commercial offers between 5.01% and 10% require Sales Manager review.',
                'salesperson_role' => null,
                'min_discount_pct' => 5.01,
                'max_discount_pct' => 10.00,
                'requires_min_price_check' => 1,
                'requires_margin_check' => 1,
                'margin_floor_pct' => 15.00,
                'required_role' => 'sales_manager',
                'approval_level' => 1,
                'is_active' => 1,
                'priority' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rule_code' => 'RULE-DISC-03',
                'rule_name' => 'Senior Manager Dual Approval (Above 10% up to 15%)',
                'rule_description' => 'Discounts between 10.01% and 15% require multi-level approval: Sales Manager + Senior Manager.',
                'salesperson_role' => null,
                'min_discount_pct' => 10.01,
                'max_discount_pct' => 15.00,
                'requires_min_price_check' => 1,
                'requires_margin_check' => 1,
                'margin_floor_pct' => 12.00,
                'required_role' => 'senior_manager',
                'approval_level' => 2,
                'is_active' => 1,
                'priority' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rule_code' => 'RULE-DISC-04',
                'rule_name' => 'Business Head Highest Approval (Above 15%)',
                'rule_description' => 'Exceptional discounts exceeding 15% require Business Head / Admin approval.',
                'salesperson_role' => null,
                'min_discount_pct' => 15.01,
                'max_discount_pct' => 99.00,
                'requires_min_price_check' => 1,
                'requires_margin_check' => 1,
                'margin_floor_pct' => 10.00,
                'required_role' => 'business_head',
                'approval_level' => 3,
                'is_active' => 1,
                'priority' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $db->table('crm_approval_rules')->insertBatch($rules);
    }

    /**
     * Evaluate discount percentage against the Commercial Approval Rules Matrix.
     */
    public static function evaluateDiscount(float $discountPct, float $grandTotal = 0.0): array
    {
        self::seedDefaultRules();
        $db = self::db();
        $rules = $db->table('crm_approval_rules')->where('is_active', 1)->orderBy('priority', 'ASC')->get()->getResultArray();
        foreach ($rules as $r) {
            $minPct = (float) $r['min_discount_pct'];
            $maxPct = $r['max_discount_pct'] !== null ? (float) $r['max_discount_pct'] : 100.0;
            if ($discountPct >= $minPct && $discountPct <= $maxPct) {
                return [
                    'requires_approval' => ((int) $r['approval_level'] > 1 || $r['required_role'] !== 'sales_rep'),
                    'level_required' => (int) $r['approval_level'],
                    'rule' => $r,
                ];
            }
        }
        return ['requires_approval' => false, 'level_required' => 1];
    }

    /**
     * Evaluate a quotation version against the Commercial Approval Rules Matrix.
     */
    public static function evaluate(float $subtotal, float $discountTotal, float $grandTotal, array $actor, array $lines = []): array
    {
        self::seedDefaultRules();
        $db = self::db();

        $discountPct = $subtotal > 0 ? round(($discountTotal / $subtotal) * 100, 2) : 0.0;
        $reasons = [];
        $requiresApproval = false;
        $requiredRole = 'sales_rep';
        $approvalLevel = 1;
        $maxLevel = 1;

        // Fetch active rules ordered by priority DESC
        $rules = $db->table('crm_approval_rules')->where('is_active', 1)->orderBy('priority', 'ASC')->get()->getResultArray();

        $matchedRule = null;
        foreach ($rules as $r) {
            $minPct = (float) $r['min_discount_pct'];
            $maxPct = $r['max_discount_pct'] !== null ? (float) $r['max_discount_pct'] : 100.0;

            if ($discountPct >= $minPct && $discountPct <= $maxPct) {
                $matchedRule = $r;
                $level = (int) $r['approval_level'];
                if ($level > 1 || $r['required_role'] !== 'sales_rep') {
                    $requiresApproval = true;
                    $requiredRole = $r['required_role'];
                    $approvalLevel = 1; // starts at level 1
                    $maxLevel = $level;
                    $reasons[] = "Discount rate of {$discountPct}% exceeds sales representative limit ({$r['rule_name']}).";
                }
                break;
            }
        }

        // Check if any line breaches minimum selling price
        foreach ($lines as $idx => $line) {
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $minPrice = (float) ($line['min_selling_price'] ?? 0);
            if ($minPrice > 0 && $unitPrice < $minPrice) {
                $requiresApproval = true;
                $requiredRole = 'sales_manager';
                $reasons[] = "Line " . ($idx + 1) . ": Unit price (₹" . number_format($unitPrice, 2) . ") is below minimum permitted selling price (₹" . number_format($minPrice, 2) . ").";
            }
        }

        // Check if actor has manager role that can auto-authorise within limits
        $userRoles = $actor['roles'] ?? ['sales_rep'];
        if (in_array('admin', $userRoles, true) || in_array('business_head', $userRoles, true)) {
            // Business head / admin can proceed without self-approval if desired
            if ($discountPct <= 20.0) {
                $requiresApproval = false;
            }
        } elseif (in_array('sales_manager', $userRoles, true) && $discountPct <= 10.0) {
            $requiresApproval = false;
        }

        return [
            'requires_approval' => $requiresApproval,
            'discount_pct' => $discountPct,
            'discount_amount' => $discountTotal,
            'required_role' => $requiredRole,
            'approval_level' => $approvalLevel,
            'max_level' => $maxLevel,
            'matched_rule' => $matchedRule,
            'reasons' => $reasons,
        ];
    }

    /**
     * Submit quotation for manager approval.
     */
    public static function submitForApproval(int $quotationId, int $versionId, string $justification, array $actor): array
    {
        $db = self::db();
        $q = $db->table('crm_quotations')->where('id', $quotationId)->get()->getRowArray() ?? throw new CrmException('Quotation not found.');
        $v = $db->table('crm_quotation_versions')->where('id', $versionId)->get()->getRowArray() ?? throw new CrmException('Quotation version not found.');

        $justification = trim($justification);
        if ($justification === '') {
            throw new CrmException('Commercial justification is mandatory when requesting discount approval.');
        }

        $lines = $db->table('crm_quotation_items qi')
            ->select('qi.*, p.standard_price, p.min_selling_price, p.cost_price')
            ->join('crm_products p', 'p.id = qi.product_id', 'left')
            ->where('qi.version_id', $versionId)
            ->get()->getResultArray();

        $eval = self::evaluate((float) $v['subtotal'], (float) $v['discount_total'], (float) $v['grand_total'], $actor, $lines);

        $now = Crm::now();
        $approvalNo = NumberSequenceService::next('approval');
        $slaDue = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Invalidate any previous pending approvals for this quotation
        $db->table('crm_quotation_approvals')
            ->where('quotation_id', $quotationId)
            ->where('status', 'pending')
            ->update(['status' => 'superseded', 'updated_at' => $now]);

        $record = [
            'approval_no' => $approvalNo,
            'quotation_id' => $quotationId,
            'quotation_version_id' => $versionId,
            'organisation_id' => $q['organisation_id'] ?? null,
            'contact_id' => $q['contact_id'] ?? null,
            'salesperson_id' => (int) $actor['id'],
            'approval_level' => 1,
            'max_approval_level' => $eval['max_level'],
            'required_role' => $eval['required_role'] ?: 'sales_manager',
            'status' => 'pending',
            'proposed_selling_price' => (float) $v['taxable_total'],
            'standard_selling_price' => (float) $v['subtotal'],
            'requested_discount_pct' => $eval['discount_pct'],
            'requested_discount_amount' => (float) $v['discount_total'],
            'net_taxable_value' => (float) $v['taxable_total'],
            'grand_total' => (float) $v['grand_total'],
            'salesperson_justification' => $justification,
            'sla_due_at' => $slaDue,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_quotation_approvals')->insert($record);
        $approvalId = (int) $db->insertID();

        // Lock quotation from forwarding to customer
        $db->table('crm_quotations')->where('id', $quotationId)->update([
            'status' => 'pending_approval',
            'approval_status' => 'pending_level_1',
            'is_commercially_approved' => 0,
            'current_approval_id' => $approvalId,
            'updated_at' => $now,
        ]);

        $db->table('crm_quotation_versions')->where('id', $versionId)->update([
            'approval_status' => 'pending_level_1',
            'approval_id' => $approvalId,
        ]);

        // Send notifications to sales managers
        $managers = $db->table('admin_users u')
            ->select('u.id')
            ->join('user_roles ur', 'ur.user_id = u.id')
            ->join('roles r', 'r.id = ur.role_id')
            ->whereIn('r.name', ['admin', 'sales_manager', 'business_admin'])
            ->get()->getResultArray();

        foreach ($managers as $mgr) {
            NotificationService::trigger('quotation.approval_requested', [
                'recipient_id' => (int) $mgr['id'],
                'quotation_id' => $quotationId,
                'quotation_no' => $q['quotation_no'],
                'approval_id' => $approvalId,
                'approval_no' => $approvalNo,
                'salesperson_name' => $actor['name'] ?? 'Salesperson',
                'discount_pct' => $eval['discount_pct'],
                'amount' => $v['grand_total'],
                'message' => "Quotation {$q['quotation_no']} submitted for discount approval ({$eval['discount_pct']}%) by {$actor['name']}.",
            ]);
        }

        AuditService::record('quotation.approval_submitted', 'crm_quotations', $quotationId, [
            'approval_no' => $approvalNo,
            'discount_pct' => $eval['discount_pct'],
            'justification' => $justification,
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * List approval requests with tab filtering.
     */
    public static function list(array $filters = [], array $actor = [], int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $b = $db->table('crm_quotation_approvals qa')
            ->select("qa.*, q.quotation_no, q.status AS quotation_status,
                CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name, o.name AS organisation_name,
                c.first_name, c.last_name, CONCAT(app.first_name, ' ', app.last_name) AS approver_name")
            ->join('crm_quotations q', 'q.id = qa.quotation_id')
            ->join('admin_users u', 'u.id = qa.salesperson_id')
            ->join('crm_organisations o', 'o.id = qa.organisation_id', 'left')
            ->join('crm_contacts c', 'c.id = qa.contact_id', 'left')
            ->join('admin_users app', 'app.id = qa.action_by_user_id', 'left');

        $tab = $filters['tab'] ?? 'pending';
        match ($tab) {
            'pending' => $b->where('qa.status', 'pending'),
            'my_approvals' => $b->where('qa.action_by_user_id', (int) ($actor['id'] ?? 0)),
            'approved' => $b->where('qa.status', 'approved'),
            'rejected' => $b->where('qa.status', 'rejected'),
            'returned' => $b->where('qa.status', 'revision_requested'),
            'escalated' => $b->where('qa.status', 'escalated'),
            default => null,
        };

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $b->groupStart()
                ->like('qa.approval_no', $term)
                ->orLike('q.quotation_no', $term)
                ->orLike('u.first_name', $term)
                ->orLike('u.last_name', $term)
                ->orLike('o.name', $term)
                ->groupEnd();
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('qa.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'counts' => [
                'pending' => $db->table('crm_quotation_approvals')->where('status', 'pending')->countAllResults(),
                'approved' => $db->table('crm_quotation_approvals')->where('status', 'approved')->countAllResults(),
                'rejected' => $db->table('crm_quotation_approvals')->where('status', 'rejected')->countAllResults(),
                'returned' => $db->table('crm_quotation_approvals')->where('status', 'revision_requested')->countAllResults(),
            ],
        ];
    }

    public static function get(int $approvalId, array $actor = []): ?array
    {
        $db = self::db();
        $row = $db->table('crm_quotation_approvals qa')
            ->select("qa.*, q.quotation_no, q.status AS quotation_status, q.enquiry_id,
                qv.version_no, qv.is_locked, CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name, u.email AS salesperson_email,
                o.name AS organisation_name, o.org_no, c.first_name, c.last_name, c.mobile AS contact_mobile,
                CONCAT(app.first_name, ' ', app.last_name) AS approver_name")
            ->join('crm_quotations q', 'q.id = qa.quotation_id')
            ->join('crm_quotation_versions qv', 'qv.id = qa.quotation_version_id')
            ->join('admin_users u', 'u.id = qa.salesperson_id')
            ->join('crm_organisations o', 'o.id = qa.organisation_id', 'left')
            ->join('crm_contacts c', 'c.id = qa.contact_id', 'left')
            ->join('admin_users app', 'app.id = qa.action_by_user_id', 'left')
            ->where('qa.id', $approvalId)
            ->get()->getRowArray();

        if (! $row) {
            return null;
        }

        // Quotation item breakdown
        $items = $db->table('crm_quotation_items qi')
            ->select('qi.*, p.name AS product_name, p.sku, p.standard_price, p.min_selling_price')
            ->join('crm_products p', 'p.id = qi.product_id', 'left')
            ->where('qi.version_id', $row['quotation_version_id'])
            ->get()->getResultArray();
        $row['items'] = $items;

        // Clarifications thread
        $clarifications = $db->table('crm_approval_clarifications ac')
            ->select("ac.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name")
            ->join('admin_users u', 'u.id = ac.sender_user_id')
            ->where('ac.approval_id', $approvalId)
            ->orderBy('ac.id', 'ASC')
            ->get()->getResultArray();
        $row['clarifications'] = $clarifications;

        return $row;
    }

    /**
     * Manager Decision: Approve
     */
    public static function approve(int $approvalId, ?string $remarks, array $actor): array
    {
        $db = self::db();
        $app = self::get($approvalId, $actor) ?? throw new CrmException('Approval request not found.');

        if ($app['status'] !== 'pending' && $app['status'] !== 'clarification_requested') {
            throw new CrmException("This approval request has already been {$app['status']}.");
        }

        // Self-approval restriction: Salesperson cannot approve own discount
        $actorId = (int) ($actor['id'] ?? 0);
        $roles = $actor['roles'] ?? [];
        if ((int) $app['salesperson_id'] === $actorId && ! in_array('admin', $roles, true)) {
            throw new CrmException('Security Policy: You cannot approve your own commercial discount request.');
        }

        $now = Crm::now();
        $curLevel = (int) $app['approval_level'];
        $maxLevel = (int) $app['max_approval_level'];

        if ($curLevel < $maxLevel) {
            // Advance to next level
            $nextLevel = $curLevel + 1;
            $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
                'approval_level' => $nextLevel,
                'status' => 'pending',
                'required_role' => $nextLevel === 2 ? 'senior_manager' : 'business_head',
                'action_by_user_id' => $actorId,
                'action_at' => $now,
                'manager_remarks' => $remarks ?: "Level {$curLevel} approved. Forwarded to Level {$nextLevel}.",
                'updated_at' => $now,
            ]);

            $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
                'approval_status' => "pending_level_{$nextLevel}",
                'updated_at' => $now,
            ]);

            // Notify higher managers
            NotificationService::trigger('quotation.approval_escalated', [
                'quotation_id' => $app['quotation_id'],
                'quotation_no' => $app['quotation_no'],
                'message' => "Quotation {$app['quotation_no']} approved at Level {$curLevel}. Awaiting Level {$nextLevel} approval.",
            ]);
        } else {
            // Final Approval complete!
            $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
                'status' => 'approved',
                'action_by_user_id' => $actorId,
                'action_at' => $now,
                'manager_remarks' => $remarks ?: 'Commercially approved by management.',
                'updated_at' => $now,
            ]);

            // Un-gate quotation: allow salesperson to forward to customer
            $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
                'status' => 'ready_to_send',
                'approval_status' => 'approved',
                'is_commercially_approved' => 1,
                'updated_at' => $now,
            ]);

            $db->table('crm_quotation_versions')->where('id', $app['quotation_version_id'])->update([
                'approval_status' => 'approved',
            ]);

            // Notify salesperson
            NotificationService::trigger('quotation.approved', [
                'recipient_id' => (int) $app['salesperson_id'],
                'quotation_id' => $app['quotation_id'],
                'quotation_no' => $app['quotation_no'],
                'approver_name' => $actor['name'] ?? 'Management',
                'message' => "Good news! Quotation {$app['quotation_no']} ({$app['requested_discount_pct']}% discount) has been APPROVED by {$actor['name']}. You may now send the official PDF to the customer.",
            ]);
        }

        AuditService::record('quotation.approved', 'crm_quotations', $app['quotation_id'], [
            'approval_no' => $app['approval_no'],
            'approver' => $actor['name'] ?? 'Manager',
            'remarks' => $remarks,
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * Manager Decision: Reject (Mandatory reason)
     */
    public static function reject(int $approvalId, string $reason, ?string $remarks, array $actor): array
    {
        $db = self::db();
        $app = self::get($approvalId, $actor) ?? throw new CrmException('Approval request not found.');

        $reason = trim($reason);
        if ($reason === '') {
            throw new CrmException('Rejection reason is mandatory when declining commercial approval.');
        }

        $now = Crm::now();
        $actorId = (int) ($actor['id'] ?? 0);

        $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'manager_remarks' => $remarks,
            'action_by_user_id' => $actorId,
            'action_at' => $now,
            'updated_at' => $now,
        ]);

        // Lock quotation permanently from customer forwarding
        $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
            'status' => 'rejected',
            'approval_status' => 'rejected',
            'is_commercially_approved' => 0,
            'updated_at' => $now,
        ]);

        $db->table('crm_quotation_versions')->where('id', $app['quotation_version_id'])->update([
            'approval_status' => 'rejected',
        ]);

        // Notify salesperson
        NotificationService::trigger('quotation.rejected', [
            'recipient_id' => (int) $app['salesperson_id'],
            'quotation_id' => $app['quotation_id'],
            'quotation_no' => $app['quotation_no'],
            'approver_name' => $actor['name'] ?? 'Management',
            'reason' => $reason,
            'message' => "Quotation {$app['quotation_no']} was REJECTED by {$actor['name']}. Reason: {$reason}. Please revise pricing accordingly.",
        ]);

        AuditService::record('quotation.rejected', 'crm_quotations', $app['quotation_id'], [
            'approval_no' => $app['approval_no'],
            'reason' => $reason,
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * Manager Decision: Return for Revision
     */
    public static function returnForRevision(int $approvalId, string $comments, array $actor): array
    {
        $db = self::db();
        $app = self::get($approvalId, $actor) ?? throw new CrmException('Approval request not found.');

        $comments = trim($comments);
        if ($comments === '') {
            throw new CrmException('Revision instructions are mandatory.');
        }

        $now = Crm::now();
        $actorId = (int) ($actor['id'] ?? 0);

        $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
            'status' => 'revision_requested',
            'manager_remarks' => $comments,
            'action_by_user_id' => $actorId,
            'action_at' => $now,
            'updated_at' => $now,
        ]);

        $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
            'status' => 'returned_for_revision',
            'approval_status' => 'revision_requested',
            'is_commercially_approved' => 0,
            'updated_at' => $now,
        ]);

        NotificationService::trigger('quotation.revision_requested', [
            'recipient_id' => (int) $app['salesperson_id'],
            'quotation_id' => $app['quotation_id'],
            'quotation_no' => $app['quotation_no'],
            'comments' => $comments,
            'message' => "Quotation {$app['quotation_no']} returned for revision by {$actor['name']}. Instructions: {$comments}.",
        ]);

        AuditService::record('quotation.revision_requested', 'crm_quotations', $app['quotation_id'], [
            'approval_no' => $app['approval_no'],
            'comments' => $comments,
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * Discussion thread: Request Clarification
     */
    public static function requestClarification(int $approvalId, string $message, array $actor): array
    {
        $db = self::db();
        $app = self::get($approvalId, $actor) ?? throw new CrmException('Approval request not found.');

        $message = trim($message);
        if ($message === '') {
            throw new CrmException('Clarification message cannot be empty.');
        }

        $actorId = (int) ($actor['id'] ?? 0);
        $role = in_array('sales_manager', $actor['roles'] ?? [], true) ? 'manager' : 'salesperson';

        $db->table('crm_approval_clarifications')->insert([
            'approval_id' => $approvalId,
            'sender_user_id' => $actorId,
            'sender_role' => $role,
            'message' => $message,
            'created_at' => Crm::now(),
        ]);

        $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
            'status' => 'clarification_requested',
            'updated_at' => Crm::now(),
        ]);

        $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
            'status' => 'clarification_required',
            'approval_status' => 'clarification_requested',
        ]);

        // Notify salesperson
        NotificationService::trigger('quotation.clarification_requested', [
            'recipient_id' => (int) $app['salesperson_id'],
            'quotation_id' => $app['quotation_id'],
            'quotation_no' => $app['quotation_no'],
            'message' => "Clarification requested for quotation {$app['quotation_no']}: '{$message}'.",
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * Discussion thread: Respond to Clarification
     */
    public static function respondClarification(int $approvalId, string $message, array $actor): array
    {
        $db = self::db();
        $app = self::get($approvalId, $actor) ?? throw new CrmException('Approval request not found.');

        $message = trim($message);
        if ($message === '') {
            throw new CrmException('Response message cannot be empty.');
        }

        $actorId = (int) ($actor['id'] ?? 0);

        $db->table('crm_approval_clarifications')->insert([
            'approval_id' => $approvalId,
            'sender_user_id' => $actorId,
            'sender_role' => 'salesperson',
            'message' => $message,
            'created_at' => Crm::now(),
        ]);

        $db->table('crm_quotation_approvals')->where('id', $approvalId)->update([
            'status' => 'pending',
            'updated_at' => Crm::now(),
        ]);

        $db->table('crm_quotations')->where('id', $app['quotation_id'])->update([
            'status' => 'pending_approval',
            'approval_status' => 'pending_level_1',
        ]);

        return self::get($approvalId, $actor);
    }

    /**
     * Invalidate approval when quotation terms are modified.
     */
    public static function invalidateOnPriceChange(int $quotationId, int $newVersionId, string $reason): void
    {
        $db = self::db();
        $now = Crm::now();

        $db->table('crm_quotation_approvals')
            ->where('quotation_id', $quotationId)
            ->whereIn('status', ['pending', 'approved', 'clarification_requested'])
            ->update([
                'status' => 'superseded',
                'manager_remarks' => "Invalidated due to commercial modifications in v{$newVersionId}: {$reason}",
                'updated_at' => $now,
            ]);

        $db->table('crm_quotations')->where('id', $quotationId)->update([
            'approval_status' => 'none',
            'is_commercially_approved' => 0,
            'current_approval_id' => null,
            'updated_at' => $now,
        ]);
    }
}
