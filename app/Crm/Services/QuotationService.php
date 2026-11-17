<?php

namespace Crm\Services;

final class QuotationService
{
    private static function enquiryFor(int $enquiryId, array $actor): array
    {
        $e = Crm::db()->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
        if (! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
            throw new CrmException('You do not have access to this enquiry.');
        }

        return $e;
    }

    public static function loadQuotation(int $id, array $actor): array
    {
        $q = Crm::db()->table('crm_quotations')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Quotation not found.');
        if (! AccessScope::canSeeEnquiry($actor, (int) $q['enquiry_id'])) {
            throw new CrmException('You do not have access to this quotation.');
        }

        return $q;
    }

    /**
     * Normalise raw input lines against the product master and compute with the server-side calculator.
     * @param list<array<string,mixed>> $raw
     * @param list<array<string,mixed>> $charges
     * @param string|null $approvedCeilingPct discount % ceiling granted by an approved negotiation (null = none)
     * @param string|null $targetTotal negotiated GRAND TOTAL: an explicit, tracked reduction fitted onto the lines (counts as discount for the limits)
     * Per line, `line_total` (optional) is a manual LINE-TOTAL override: the unit price is back-solved from it (source = manual).
     * @return array<string,mixed> calculator result + 'meta_lines'
     */
    public static function price(array $raw, array $charges, array $actor, ?string $approvedCeilingPct = null, ?string $targetTotal = null): array
    {
        if (! $raw) {
            throw new CrmException('Add at least one line item.');
        }
        $db    = Crm::db();
        $lines = [];
        $meta  = [];
        $dtRow      = $db->table('crm_tax_rates')->where('is_default', 1)->get()->getRow();
        $defaultTax = $dtRow ? (string) $dtRow->rate : Settings::get('tax.default_rate');
        foreach ($raw as $i => $l) {
            $p = null;
            if (! empty($l['product_id'])) {
                $p = $db->table('crm_products p')->select('p.*, t.rate AS tax_rate')->join('crm_tax_rates t', 't.id = p.tax_rate_id')->where('p.id', (int) $l['product_id'])->get()->getRowArray()
                    ?? throw new CrmException('Line ' . ($i + 1) . ': product not found.');
                if ((int) $p['is_active'] !== 1) {
                    throw new CrmException('Line ' . ($i + 1) . ': product ' . $p['sku'] . ' is inactive.');
                }
            }
            $unitIn = $l['unit'] ?? ($p['unit'] ?? 'nos');
            $model  = $p['pricing_model'] ?? ($unitIn === 'sqft' ? 'sqft' : ($unitIn === 'rft' ? 'rft' : null));
            $sized  = RequirementService::quantityFromSize($model, $l['width'] ?? null, $l['height'] ?? null, $l['size_unit'] ?? null, $l['nos'] ?? null);
            $qty    = $sized ?? ($l['quantity'] ?? 1);
            $solvedDisc = null;
            $override = isset($l['line_total']) && trim((string) $l['line_total']) !== '' ? Money::round(Money::norm((string) $l['line_total'])) : null;
            if ($override !== null) {
                try {
                    $solved = QuotationCalculator::solveUnitPrice((string) $qty, (string) ($l['discount_pct'] ?? 0), (string) ($l['discount_amount'] ?? 0), (string) ($p['tax_rate'] ?? ($l['tax_rate'] ?? $defaultTax)), $override);
                } catch (CrmException $ex) {
                    throw new CrmException('Line ' . ($i + 1) . ': ' . $ex->getMessage());
                }
                $price      = $solved['unit_price'];
                $solvedDisc = $solved['discount_amount'];
            } else {
                $price = isset($l['unit_price']) && $l['unit_price'] !== '' ? (string) $l['unit_price'] : ($p['standard_price'] ?? null);
            }
            if ($price === null) {
                throw new CrmException('Line ' . ($i + 1) . ': unit price is required.');
            }
            $source = ($p === null || $override !== null || Money::cmp($price, (string) $p['standard_price']) !== 0) ? 'manual' : 'catalogue';
            $lines[] = [
                'quantity' => $qty, 'unit_price' => $price, 'discount_pct' => $l['discount_pct'] ?? 0, 'discount_amount' => $solvedDisc ?? ($l['discount_amount'] ?? 0),
                'tax_rate' => $p['tax_rate'] ?? ($l['tax_rate'] ?? $defaultTax),
            ];
            $meta[] = [
                'product_id' => $p['id'] ?? null, 'sku' => $p['sku'] ?? null, 'min_price' => $p['min_price'] ?? null, 'std_price' => $p['standard_price'] ?? null, 'unit' => $unitIn,
                'variant' => ($l['variant'] ?? '') !== '' ? $l['variant'] : null, 'width' => ($l['width'] ?? '') !== '' ? $l['width'] : null, 'height' => ($l['height'] ?? '') !== '' ? $l['height'] : null,
                'size_unit' => ($l['size_unit'] ?? '') !== '' ? $l['size_unit'] : null, 'nos' => ($l['nos'] ?? '') !== '' ? $l['nos'] : null, 'price_source' => $source, 'override_total' => $override,
                'description' => trim((string) (($l['description'] ?? '') !== '' ? $l['description'] : ($p['name'] ?? ''))), 'specifications' => ($l['specifications'] ?? '') !== '' ? $l['specifications'] : ($p['specifications'] ?? null),
            ];
            if ($meta[$i]['description'] === '') {
                throw new CrmException('Line ' . ($i + 1) . ': description is required.');
            }
        }
        $chargeIn = [];
        foreach ($charges as $c) {
            if (trim((string) ($c['name'] ?? '')) !== '' && Money::cmp((string) ($c['amount'] ?? 0), '0') > 0) {
                $chargeIn[] = ['name' => $c['name'], 'amount' => $c['amount'], 'tax_rate' => $c['tax_rate'] ?? $defaultTax];
            }
        }
        $roundOff = Settings::get('quotation.round_off') === '1';
        $calc     = QuotationCalculator::compute($lines, $chargeIn, $roundOff);
        $calc['target_total']      = null;
        $calc['adjustment_amount'] = '0.00';
        if ($targetTotal !== null && trim($targetTotal) !== '') {
            if (! is_numeric($targetTotal)) {
                throw CrmException::validation(['target_total' => 'Negotiated total must be a number.']);
            }
            try {
                $fit = QuotationCalculator::fitGrandTotal($lines, $chargeIn, $roundOff, $targetTotal);
            } catch (CrmException $ex) {
                throw CrmException::validation(['target_total' => $ex->getMessage()]);
            }
            $calc = $fit['calc'] + ['target_total' => Money::round($targetTotal), 'adjustment_amount' => $fit['adjustment'], 'target_variance' => $fit['variance']];
        }

        // ── authority checks (server-side; the UI cannot bypass these) ──
        // A hand-typed price below the catalogue standard price is an implicit discount: it counts against the limit exactly like a % discount.
        $markdown = '0.00';
        foreach ($calc['lines'] as $i => $cl) {
            $std = $meta[$i]['std_price'] ?? null;
            if ($std !== null && Money::cmp($cl['unit_price'], (string) $std) < 0) {
                $markdown = Money::round(Money::add($markdown, Money::mul(Money::sub((string) $std, $cl['unit_price']), $cl['quantity'])));
            }
        }
        $gross        = Money::add($calc['subtotal'], $markdown);
        $isAdminLevel = AccessScope::can($actor, 'discounts.approve_admin');

        // Check explicit manual price overrides below catalogue minimum first
        foreach ($calc['lines'] as $i => $cl) {
            $min = $meta[$i]['min_price'];
            if ($min !== null && Money::cmp($min, '0') > 0) {
                if (($meta[$i]['price_source'] ?? '') === 'manual' && bccomp(Money::round($cl['unit_price']), Money::round($min), 2) < 0 && $approvedCeilingPct === null && ! $isAdminLevel) {
                    $net = Money::div($cl['taxable_amount'], $cl['quantity']);
                    throw new CrmException('Line ' . ($i + 1) . ": net price {$net} is below the minimum selling price {$min}. Approval required.");
                }
            }
        }

        $overall      = Money::cmp($gross, '0') > 0 ? Money::round(Money::mul(Money::div(Money::add($calc['discount_total'], $markdown), $gross), '100'), 3) : '0.000';
        $limit        = $isAdminLevel ? '100' : (string) ($actor['max_discount_pct'] ?? '0');
        if ($approvedCeilingPct !== null) {
            $limit = bccomp($approvedCeilingPct, $limit, 3) > 0 ? $approvedCeilingPct : $limit;
        }
        if (bccomp($overall, Money::round($limit, 3), 3) > 0) {
            throw new CrmException("Effective discount {$overall}% (discounts, manual price reductions and negotiated total) exceeds the limit of " . Money::round($limit, 1) . '% allowed for you. Submit a negotiation / discount request for approval.');
        }
        foreach ($calc['lines'] as $i => $cl) {
            $min = $meta[$i]['min_price'];
            if ($min !== null && Money::cmp($min, '0') > 0) {
                $net = Money::div($cl['taxable_amount'], $cl['quantity']);
                if (bccomp(Money::round($net), Money::round($min), 2) < 0 && $approvedCeilingPct === null && ! $isAdminLevel) {
                    throw new CrmException('Line ' . ($i + 1) . ": net price {$net} is below the minimum selling price {$min}. Approval required.");
                }
            }
        }
        $calc['meta'] = $meta;
        $calc['overall_discount_pct'] = $overall;

        return $calc;
    }

    private static function writeVersionBody(int $versionId, array $calc): void
    {
        $db = Crm::db();
        $db->table('crm_quotation_items')->where('version_id', $versionId)->delete();
        $db->table('crm_quotation_charges')->where('version_id', $versionId)->delete();
        foreach ($calc['lines'] as $i => $cl) {
            $m = $calc['meta'][$i];
            $db->table('crm_quotation_items')->insert([
                'version_id' => $versionId, 'line_no' => $i + 1, 'product_id' => $m['product_id'], 'sku' => $m['sku'], 'description' => mb_substr($m['description'], 0, 500), 'specifications' => $m['specifications'],
                'variant' => $m['variant'] ?? null, 'width' => $m['width'] ?? null, 'height' => $m['height'] ?? null, 'size_unit' => $m['size_unit'] ?? null, 'nos' => $m['nos'] ?? null, 'price_source' => $m['price_source'] ?? 'catalogue', 'override_total' => $m['override_total'] ?? null, 'std_price' => $m['std_price'] ?? null,
                'quantity' => $cl['quantity'], 'unit' => $m['unit'], 'unit_price' => $cl['unit_price'], 'min_price' => $m['min_price'], 'discount_pct' => $cl['discount_pct'], 'discount_amount' => $cl['discount_amount'],
                'line_subtotal' => $cl['line_subtotal'], 'taxable_amount' => $cl['taxable_amount'], 'tax_rate' => $cl['tax_rate'], 'tax_amount' => $cl['tax_amount'], 'line_total' => $cl['line_total'],
            ]);
        }
        foreach ($calc['charges'] as $c) {
            $db->table('crm_quotation_charges')->insert(['version_id' => $versionId] + $c);
        }
        $db->table('crm_quotation_versions')->where('id', $versionId)->update([
            'subtotal' => $calc['subtotal'], 'discount_total' => $calc['discount_total'], 'taxable_total' => $calc['taxable_total'], 'tax_total' => $calc['tax_total'],
            'charges_total' => $calc['charges_total'], 'round_off' => $calc['round_off'], 'grand_total' => $calc['grand_total'],
            'target_total' => $calc['target_total'] ?? null, 'adjustment_amount' => $calc['adjustment_amount'] ?? '0.00',
        ]);
    }

    private static function versionMeta(array $m, ?array $prev = null): array
    {
        $validity = (int) Settings::get('quotation.validity_days');

        return [
            'terms'             => $m['terms'] ?? ($prev['terms'] ?? Settings::get('quotation.terms')),
            'payment_terms'     => $m['payment_terms'] ?? ($prev['payment_terms'] ?? Settings::get('quotation.payment_terms')),
            'delivery_estimate' => $m['delivery_estimate'] ?? ($prev['delivery_estimate'] ?? null),
            'valid_until'       => $m['valid_until'] ?? date('Y-m-d', strtotime("+{$validity} days")),
            'customer_remarks'  => $m['customer_remarks'] ?? ($prev['customer_remarks'] ?? null),
            'internal_remarks'  => $m['internal_remarks'] ?? ($prev['internal_remarks'] ?? null),
        ];
    }

    /** @return array{id:int,quotation_no:string,version_id:int,grand_total:string} */
    public static function create(int $enquiryId, array $actor, array $lines, array $charges = [], array $meta = [], int $isDemo = 0): array
    {
        if (! AccessScope::can($actor, 'quotations.create')) {
            throw new CrmException('You do not have permission to create quotations.');
        }

        return Crm::tx(static function ($db) use ($enquiryId, $actor, $lines, $charges, $meta, $isDemo) {
            $e = self::enquiryFor($enquiryId, $actor);
            if (! WorkflowService::isActive($e['status']) || in_array($e['status'], ['new', 'assigned'], true)) {
                throw new CrmException('Record customer contact before quoting (enquiry must be contacted or later, and active).');
            }
            $lines = $lines ?: RequirementService::toQuotationLines($enquiryId);
            $calc = self::price($lines, $charges, $actor, null, $meta['target_total'] ?? null);
            $eval = ApprovalService::evaluate((float) $calc['subtotal'], (float) $calc['discount_total'], (float) $calc['grand_total'], $actor, $lines);

            $no   = NumberSequenceService::next('quotation');
            $status = $eval['requires_approval'] ? 'pending_approval' : 'draft';
            $appStatus = $eval['requires_approval'] ? 'pending_level_1' : 'none';
            $isApproved = $eval['requires_approval'] ? 0 : 1;

            $db->table('crm_quotations')->insert([
                'quotation_no' => $no,
                'enquiry_id' => $enquiryId,
                'customer_id' => $e['customer_id'],
                'organisation_id' => $e['organisation_id'] ?? null,
                'contact_id' => $e['contact_id'] ?? null,
                'status' => $status,
                'approval_status' => $appStatus,
                'is_commercially_approved' => $isApproved,
                'created_by' => $actor['id'],
                'is_demo' => $isDemo,
            ]);
            $qid = (int) $db->insertID();
            $db->table('crm_quotation_versions')->insert([
                'quotation_id' => $qid,
                'version_no' => 1,
                'approval_status' => $appStatus,
                'created_by' => $actor['id'],
                'created_at' => Crm::now(),
            ] + self::versionMeta($meta));
            $vid = (int) $db->insertID();
            self::writeVersionBody($vid, $calc);
            $db->table('crm_quotations')->where('id', $qid)->update(['current_version_id' => $vid]);

            if ($eval['requires_approval']) {
                $justification = ! empty($meta['justification']) ? (string) $meta['justification'] : implode('; ', $eval['reasons']);
                ApprovalService::submitForApproval($qid, $vid, $justification, $actor);
            }
            if (in_array($e['status'], ['contacted', 'qualified', 'site_visit'], true)) {
                WorkflowService::transition($enquiryId, 'quotation_preparation', $actor, "Quotation {$no} prepared");
            }
            AuditService::workflow('quotation.created', 'quotation', $qid, [
                'customer_id' => (int) $e['customer_id'], 'enquiry_id' => $enquiryId, 'entity_ref' => $no, 'user_id' => $actor['id'], 'is_demo' => $isDemo,
                'new' => ['version' => 1, 'grand_total' => $calc['grand_total'], 'discount_total' => $calc['discount_total']],
            ]);
            if ($isDemo) {
                $db->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', ['crm_quotations', $qid]);
            }

            return ['id' => $qid, 'quotation_no' => $no, 'version_id' => $vid, 'grand_total' => $calc['grand_total']];
        });
    }

    public static function assertEditable(int $versionId): array
    {
        $v = Crm::db()->query('SELECT * FROM crm_quotation_versions WHERE id = ? FOR UPDATE', [$versionId])->getRowArray() ?? throw new CrmException('Version not found.');
        if ((int) $v['is_locked'] === 1) {
            throw new CrmException('This version has been sent or approved and is locked. Create a revision instead.');
        }

        return $v;
    }

    /** @param array<string,mixed> $meta may carry `target_total` (negotiated grand total) and `change_note` (why this draft was edited) */
    public static function updateDraft(int $versionId, array $actor, array $lines, array $charges = [], array $meta = []): void
    {
        Crm::tx(static function ($db) use ($versionId, $actor, $lines, $charges, $meta) {
            $v = self::assertEditable($versionId);
            $q = self::loadQuotation((int) $v['quotation_id'], $actor);
            if (! AccessScope::can($actor, 'quotations.create')) {
                throw new CrmException('You do not have permission to edit quotations.');
            }
            $approved = self::approvedCeiling((int) $v['quotation_id']);
            $calc = self::price($lines, $charges, $actor, $approved, $meta['target_total'] ?? null);
            $beforeItems   = $db->table('crm_quotation_items')->where('version_id', $versionId)->orderBy('line_no')->get()->getResultArray();
            $beforeCharges = $db->table('crm_quotation_charges')->where('version_id', $versionId)->get()->getResultArray();
            self::writeVersionBody($versionId, $calc);
            $db->table('crm_quotation_versions')->where('id', $versionId)->update(self::versionMeta($meta, $v));
            $after = $db->table('crm_quotation_versions')->where('id', $versionId)->get()->getRowArray();
            self::recordChangeSet((int) $q['id'], $beforeItems, $db->table('crm_quotation_items')->where('version_id', $versionId)->orderBy('line_no')->get()->getResultArray(), $v, $after,
                $beforeCharges, $db->table('crm_quotation_charges')->where('version_id', $versionId)->get()->getResultArray(), (int) $v['version_no'], (int) $v['version_no'],
                trim((string) ($meta['change_note'] ?? '')) !== '' ? (string) $meta['change_note'] : 'Draft edited', (int) $actor['id']);
            AuditService::workflow('quotation.draft_updated', 'quotation', (int) $q['id'], ['customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $q['quotation_no'], 'user_id' => $actor['id'], 'new' => ['version' => (int) $v['version_no'], 'grand_total' => $calc['grand_total']]]);
        });
    }

    /** Highest discount % an approved-but-unapplied negotiation grants on this quotation. */
    public static function approvedCeiling(int $quotationId, ?int $negotiationId = null): ?string
    {
        $b = Crm::db()->table('crm_negotiations')->selectMax('requested_discount_pct', 'p')->where('quotation_id', $quotationId)->where('status', 'approved');
        if ($negotiationId) {
            $b->where('id', $negotiationId);
        }
        $p = $b->get()->getRow()->p ?? null;

        return $p === null ? null : (string) $p;
    }

    /** Lock + render PDF + email (or record manual send). Idempotent per version. */
    public static function send(int $quotationId, array $actor, string $channel = 'email', ?string $toEmail = null): array
    {
        if (! AccessScope::can($actor, 'quotations.send')) {
            throw new CrmException('You do not have permission to send quotations.');
        }

        return Crm::tx(static function ($db) use ($quotationId, $actor, $channel, $toEmail) {
            $q = self::loadQuotation($quotationId, $actor);
            if (isset($q['is_commercially_approved']) && (int) $q['is_commercially_approved'] === 0) {
                throw new CrmException("Commercial Approval Required: Quotation {$q['quotation_no']} is currently " . crm_label($q['approval_status'] ?? 'pending_approval') . ". Only approved commercial offers can be forwarded to customers.");
            }
            $c = $db->table('crm_customers')->where('id', $q['customer_id'])->get()->getRowArray();
            $v = $db->query('SELECT * FROM crm_quotation_versions WHERE id = ? FOR UPDATE', [$q['current_version_id']])->getRowArray();
            if ((int) $v['is_locked'] === 1) {
                throw new CrmException('This version was already sent. Use resend, or create a revision.');
            }
            if (in_array($q['status'], ['accepted', 'rejected', 'cancelled'], true)) {
                throw new CrmException('This quotation is closed.');
            }
            $to = $toEmail ?: $c['email'];
            if ($channel === 'email' && ! $to) {
                throw new CrmException('Customer has no email address. Add one or choose manual delivery.');
            }
            $pdf = PdfService::renderQuotation((int) $v['id']);
            $db->table('crm_quotation_versions')->where('id', $v['id'])->update(['is_locked' => 1, 'locked_at' => Crm::now(), 'sent_at' => Crm::now(), 'pdf_path' => $pdf['path'], 'pdf_sha256' => $pdf['sha256']]);
            $db->table('crm_quotations')->where('id', $quotationId)->update(['status' => 'sent']);
            $e = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
            if (in_array($e['status'], ['quotation_preparation', 'negotiation'], true)) {
                WorkflowService::transition((int) $e['id'], 'quotation_sent', $actor, "Quotation {$q['quotation_no']} v{$v['version_no']} sent");
            }
            $emailId = null;
            if ($channel === 'email') {
                $emailId = self::emailQuotation($q, $v, $c, $pdf['path'], (string) $to, $actor);
            }
            AuditService::workflow('quotation.sent', 'quotation', $quotationId, [
                'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $q['quotation_no'], 'user_id' => $actor['id'], 'is_demo' => (int) $q['is_demo'],
                'new' => ['version' => (int) $v['version_no'], 'channel' => $channel, 'email_log_id' => $emailId, 'pdf_sha256' => $pdf['sha256']],
            ]);
            if ($e['current_owner_id'] && (int) $e['current_owner_id'] !== (int) $actor['id']) {
                NotificationService::notify((int) $e['current_owner_id'], 'quotation.sent', "Quotation {$q['quotation_no']} was sent", null, "crm/quotations/{$quotationId}", "qt-sent:{$v['id']}", (int) $q['is_demo']);
            }

            return ['version_id' => (int) $v['id'], 'pdf_path' => $pdf['path'], 'email_log_id' => $emailId];
        });
    }

    private static function emailQuotation(array $q, array $v, array $c, string $pdfPath, string $to, array $actor): int
    {
        $fresh = Crm::db()->table('crm_quotation_versions')->where('id', $v['id'])->get()->getRowArray();
        $sp    = Crm::db()->table('admin_users')->where('id', $actor['id'])->get()->getRowArray();

        return EmailService::queue($to, [
            'template' => 'quotation_sent', 'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'quotation_id' => (int) $q['id'], 'attachment' => $pdfPath,
            'user_id' => $actor['id'], 'is_demo' => (int) $q['is_demo'],
            'vars' => ['customer_name' => $c['name'], 'quotation_no' => $q['quotation_no'], 'version_no' => $v['version_no'], 'grand_total' => 'INR ' . Money::fmt($fresh['grand_total']),
                'valid_until' => $v['valid_until'] ? date('d M Y', strtotime($v['valid_until'])) : '-', 'salesperson' => $sp ? $sp['first_name'] . ' ' . $sp['last_name'] : '', 'company_name' => Settings::get('company.name')],
        ]);
    }

    public static function resend(int $quotationId, array $actor, ?string $toEmail = null): int
    {
        if (! AccessScope::can($actor, 'quotations.send')) {
            throw new CrmException('You do not have permission to send quotations.');
        }
        $q = self::loadQuotation($quotationId, $actor);
        $v = Crm::db()->table('crm_quotation_versions')->where('id', $q['current_version_id'])->get()->getRowArray();
        if ((int) $v['is_locked'] !== 1 || ! $v['pdf_path']) {
            throw new CrmException('Nothing to resend: the current version has not been sent yet.');
        }
        $c  = Crm::db()->table('crm_customers')->where('id', $q['customer_id'])->get()->getRowArray();
        $to = $toEmail ?: $c['email'];
        if (! $to) {
            throw new CrmException('Customer has no email address.');
        }
        $id = self::emailQuotation($q, $v, $c, PdfService::resolve((string) $v['pdf_path']) ?? $v['pdf_path'], $to, $actor);
        AuditService::workflow('quotation.resent', 'quotation', $quotationId, ['customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $q['quotation_no'], 'user_id' => $actor['id'], 'new' => ['version' => (int) $v['version_no'], 'email_log_id' => $id]]);

        return $id;
    }

    /**
     * New version after the previous one was sent/locked. Never mutates the earlier version, its items, PDF or timestamps.
     * @param int|null $negotiationId when given, its approved discount ceiling authorises this revision and it is marked applied.
     */
    public static function revise(int $quotationId, array $actor, array $lines, array $charges = [], array $meta = [], ?int $negotiationId = null, ?string $reason = null, ?int $followupId = null): array
    {
        if (! AccessScope::can($actor, 'quotations.create')) {
            throw new CrmException('You do not have permission to revise quotations.');
        }

        return Crm::tx(static function ($db) use ($quotationId, $actor, $lines, $charges, $meta, $negotiationId, $reason, $followupId) {
            $q = self::loadQuotation($quotationId, $actor);
            $q = $db->query('SELECT * FROM crm_quotations WHERE id = ? FOR UPDATE', [$quotationId])->getRowArray();
            if (in_array($q['status'], ['accepted', 'rejected', 'cancelled'], true)) {
                throw new CrmException('A closed quotation cannot be revised.');
            }
            $prev = $db->table('crm_quotation_versions')->where('id', $q['current_version_id'])->get()->getRowArray();
            if ((int) $prev['is_locked'] !== 1) {
                throw new CrmException('The current version is still a draft - edit it instead of revising.');
            }
            $ceiling = null;
            if ($negotiationId) {
                $n = $db->query('SELECT * FROM crm_negotiations WHERE id = ? FOR UPDATE', [$negotiationId])->getRowArray() ?? throw new CrmException('Negotiation not found.');
                if ((int) $n['quotation_id'] !== $quotationId || $n['status'] !== 'approved') {
                    throw new CrmException('Only an approved negotiation on this quotation can authorise a revision.');
                }
                $ceiling = (string) $n['requested_discount_pct'];
            }
            if (trim((string) $reason) === '' && $negotiationId) {
                $reason = 'Approved discount ' . $db->table('crm_negotiations')->where('id', $negotiationId)->get()->getRow()->negotiation_no;
            }
            if (trim((string) $reason) === '') {
                throw CrmException::validation(['reason' => 'State why the quotation is being revised (e.g. customer asked for a different variant or size).']);
            }
            $calc = self::price($lines, $charges, $actor, $ceiling, $meta['target_total'] ?? null);
            $eval = ApprovalService::evaluate((float) $calc['subtotal'], (float) $calc['discount_total'], (float) $calc['grand_total'], $actor, $lines);

            $vno  = (int) $prev['version_no'] + 1;
            $appStatus = ($eval['requires_approval'] && ! $negotiationId) ? 'pending_level_1' : 'approved';
            $isApproved = ($eval['requires_approval'] && ! $negotiationId) ? 0 : 1;

            $db->table('crm_quotation_versions')->insert(['quotation_id' => $quotationId, 'version_no' => $vno, 'approval_status' => $appStatus, 'created_by' => $actor['id'], 'created_at' => Crm::now(), 'revision_reason' => mb_substr((string) $reason, 0, 500), 'source_followup_id' => $followupId] + self::versionMeta($meta, $prev));
            $vid = (int) $db->insertID();
            self::writeVersionBody($vid, $calc);
            self::recordChanges($quotationId, (int) $prev['id'], $vid, (int) $prev['version_no'], $vno, (string) $reason, (int) $actor['id']);
            
            $qStatus = ($eval['requires_approval'] && ! $negotiationId) ? 'pending_approval' : 'revised';
            $db->table('crm_quotations')->where('id', $quotationId)->update([
                'current_version_id' => $vid,
                'status' => $qStatus,
                'approval_status' => $appStatus,
                'is_commercially_approved' => $isApproved,
            ]);

            if ($eval['requires_approval'] && ! $negotiationId) {
                ApprovalService::submitForApproval($quotationId, $vid, (string) $reason, $actor);
            }
            if ($negotiationId) {
                $db->table('crm_negotiations')->where('id', $negotiationId)->update(['status' => 'applied', 'final_discount_amount' => $calc['discount_total'], 'resulting_version_id' => $vid]);
            }
            AuditService::workflow('quotation.revised', 'quotation', $quotationId, [
                'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $q['quotation_no'] . '-V' . $vno, 'user_id' => $actor['id'], 'is_demo' => (int) $q['is_demo'],
                'prev' => ['version' => (int) $prev['version_no'], 'grand_total' => $prev['grand_total']], 'new' => ['version' => $vno, 'grand_total' => $calc['grand_total'], 'negotiation_id' => $negotiationId], 'reason' => $reason,
            ]);

            return ['version_id' => $vid, 'version_no' => $vno, 'grand_total' => $calc['grand_total']];
        });
    }

    /** Customer decision on the latest SENT version. */
    public static function recordResponse(int $quotationId, array $actor, string $response, ?string $remarks = null, ?string $at = null): void
    {
        if (! in_array($response, ['accepted', 'rejected'], true)) {
            throw new CrmException('Response must be accepted or rejected.');
        }
        if (! AccessScope::can($actor, 'quotations.send') && ! AccessScope::can($actor, 'quotations.create')) {
            throw new CrmException('You do not have permission to record customer responses.');
        }
        Crm::tx(static function ($db) use ($quotationId, $actor, $response, $remarks, $at) {
            $q = self::loadQuotation($quotationId, $actor);
            $q = $db->query('SELECT * FROM crm_quotations WHERE id = ? FOR UPDATE', [$quotationId])->getRowArray();
            $v = $db->table('crm_quotation_versions')->where('id', $q['current_version_id'])->get()->getRowArray();
            if ((int) $v['is_locked'] !== 1 || ! in_array($q['status'], ['sent', 'negotiation'], true)) {
                throw new CrmException('Only a sent quotation can be accepted or rejected (current status: ' . $q['status'] . ').');
            }
            $when = $at ?? Crm::now();
            $db->table('crm_quotations')->where('id', $quotationId)->update(['status' => $response, 'customer_response' => $response, 'responded_at' => $when]);
            $db->table('crm_negotiations')->where('quotation_id', $quotationId)->whereIn('status', ['pending', 'approved', 'escalated', 'returned'])->update(['status' => 'withdrawn']);
            AuditService::workflow('quotation.' . $response, 'quotation', $quotationId, [
                'customer_id' => (int) $q['customer_id'], 'enquiry_id' => (int) $q['enquiry_id'], 'entity_ref' => $q['quotation_no'], 'user_id' => $actor['id'], 'is_demo' => (int) $q['is_demo'],
                'prev' => ['status' => $q['status']], 'new' => ['status' => $response, 'version' => (int) $v['version_no'], 'grand_total' => $v['grand_total']], 'reason' => $remarks, 'created_at' => $when,
            ]);
            $e = $db->table('crm_enquiries')->where('id', $q['enquiry_id'])->get()->getRowArray();
            if ($e['current_owner_id']) {
                NotificationService::notify((int) $e['current_owner_id'], 'quotation.' . $response, "Customer {$response} quotation {$q['quotation_no']}", null, "crm/quotations/{$quotationId}", "qt-resp:{$quotationId}:{$response}", (int) $q['is_demo']);
            }
        });
    }

    private static function itemKey(array $i): string
    {
        return ($i['product_id'] ?: 'custom') . '|' . strtolower((string) ($i['variant'] ?? '')) . '|' . ($i['width'] !== null ? (float) $i['width'] : '') . 'x' . ($i['height'] !== null ? (float) $i['height'] : '') . '|' . ($i['product_id'] ? '' : strtolower($i['description']));
    }

    public static function itemLabel(array $i): string
    {
        $s = $i['description'];
        if (! empty($i['variant'])) {
            $s .= ' - ' . $i['variant'];
        }
        if ($i['width'] !== null && $i['height'] !== null) {
            $s .= ' (' . (float) $i['width'] . 'x' . (float) $i['height'] . ' ' . $i['size_unit'] . ($i['nos'] ? ', ' . (float) $i['nos'] . ' nos' : '') . ')';
        }

        return $s;
    }

    /** Fields compared line by line (product swap, variant, size, quantity, price, discount, tax, total). */
    private const DIFF_FIELDS = ['description', 'sku', 'variant', 'width', 'height', 'size_unit', 'nos', 'quantity', 'unit', 'unit_price', 'price_source', 'discount_pct', 'discount_amount', 'tax_rate', 'taxable_amount', 'tax_amount', 'line_total'];

    private static function fieldChanges(array $x, array $y): array
    {
        $d = [];
        foreach (self::DIFF_FIELDS as $f) {
            $a = $x[$f] ?? null;
            $b = $y[$f] ?? null;
            $same = is_numeric($a) && is_numeric($b) ? bccomp((string) $a, (string) $b, 3) === 0 : (string) $a === (string) $b;
            if (! $same) {
                $d[$f] = ['from' => $a, 'to' => $b];
            }
        }

        return $d;
    }

    /**
     * Line-level diff between two item sets: added / removed / modified with per-field before/after.
     * An added line that takes the place of a removed one (same line position or same product - a product/variant/size swap) carries
     * `replaces` (the old line label + field-level before/after) so the UI can show the swap as one change.
     * @return list<array<string,mixed>>
     */
    public static function diffItems(array $a, array $b): array
    {
        $am = $bm = [];
        foreach ($a as $i) {
            $am[self::itemKey($i)] = $i;
        }
        foreach ($b as $i) {
            $bm[self::itemKey($i)] = $i;
        }
        $out = [];
        $addedIdx = [];
        foreach ($bm as $k => $i) {
            if (! isset($am[$k])) {
                $addedIdx[$k] = count($out);
                $out[] = ['type' => 'added', 'item' => self::itemLabel($i), 'detail' => ['quantity' => $i['quantity'], 'unit_price' => $i['unit_price'], 'line_total' => $i['line_total']], 'line_no' => $i['line_no'] ?? null];
                continue;
            }
            $d = self::fieldChanges($am[$k], $i);
            if ($d) {
                $out[] = ['type' => 'modified', 'item' => self::itemLabel($i), 'detail' => $d];
            }
        }
        $removed = [];
        foreach ($am as $k => $i) {
            if (! isset($bm[$k])) {
                $removed[$k] = $i;
            }
        }
        $used = [];
        foreach ($addedIdx as $k => $pos) {
            $new = $bm[$k];
            $match = null;
            foreach ($removed as $rk => $old) {
                if (isset($used[$rk])) {
                    continue;
                }
                if (($old['line_no'] ?? null) == ($new['line_no'] ?? null)) {
                    $match = $rk;
                    break;
                }
            }
            if ($match === null && $new['product_id']) {
                foreach ($removed as $rk => $old) {
                    if (! isset($used[$rk]) && (int) $old['product_id'] === (int) $new['product_id']) {
                        $match = $rk;
                        break;
                    }
                }
            }
            if ($match !== null) {
                $used[$match] = true;
                $out[$pos]['detail']['replaces'] = ['item' => self::itemLabel($removed[$match]), 'fields' => self::fieldChanges($removed[$match], $new)];
            }
        }
        foreach ($removed as $rk => $i) {
            $row = ['type' => 'removed', 'item' => self::itemLabel($i), 'detail' => ['quantity' => $i['quantity'], 'unit_price' => $i['unit_price'], 'line_total' => $i['line_total']]];
            if (isset($used[$rk])) {
                $row['detail']['replaced'] = true;
            }
            $out[] = $row;
        }

        return $out;
    }

    /** Diff + persist (append-only) every item / charge / total / terms difference between two states of a quotation. */
    private static function recordChangeSet(int $quotationId, array $ia, array $ib, array $va, array $vb, array $ca, array $cb, int $fromNo, int $toNo, string $reason, int $userId): int
    {
        $db  = Crm::db();
        $n   = 0;
        $ins = static function (string $type, ?string $ref, array $detail) use ($db, $quotationId, $fromNo, $toNo, $reason, $userId, &$n) {
            $db->table('crm_quotation_changes')->insert(['quotation_id' => $quotationId, 'from_version_no' => $fromNo, 'to_version_no' => $toNo, 'change_type' => $type, 'item_ref' => $ref ? mb_substr($ref, 0, 255) : null, 'detail' => json_encode($detail), 'reason' => mb_substr($reason, 0, 500), 'user_id' => $userId, 'created_at' => Crm::now()]);
            $n++;
        };
        foreach (self::diffItems($ia, $ib) as $c) {
            $ins($c['type'], $c['item'], $c['detail']);
        }
        // additional charges, by name
        $cam = array_column($ca, null, 'name');
        $cbm = array_column($cb, null, 'name');
        foreach ($cbm as $name => $c) {
            if (! isset($cam[$name])) {
                $ins('added', 'Charge: ' . $name, ['amount' => $c['amount']]);
            } elseif (Money::cmp($cam[$name]['amount'], $c['amount']) !== 0) {
                $ins('modified', 'Charge: ' . $name, ['amount' => ['from' => $cam[$name]['amount'], 'to' => $c['amount']]]);
            }
        }
        foreach ($cam as $name => $c) {
            if (! isset($cbm[$name])) {
                $ins('removed', 'Charge: ' . $name, ['amount' => $c['amount']]);
            }
        }
        $t = [];
        foreach (['subtotal', 'discount_total', 'tax_total', 'charges_total', 'adjustment_amount', 'grand_total'] as $f) {
            if (Money::cmp((string) ($va[$f] ?? '0'), (string) ($vb[$f] ?? '0')) !== 0) {
                $t[$f] = ['from' => $va[$f] ?? '0', 'to' => $vb[$f] ?? '0'];
            }
        }
        if ((string) ($va['target_total'] ?? '') !== (string) ($vb['target_total'] ?? '')) {
            $t['target_total'] = ['from' => $va['target_total'] ?? null, 'to' => $vb['target_total'] ?? null];
        }
        if ($t) {
            $ins('totals', null, $t);
        }
        $terms = [];
        foreach (['terms', 'payment_terms', 'delivery_estimate', 'valid_until', 'customer_remarks'] as $f) {
            if ((string) ($va[$f] ?? '') !== (string) ($vb[$f] ?? '')) {
                $terms[$f] = ['from' => $va[$f] ?? null, 'to' => $vb[$f] ?? null];
            }
        }
        if ($terms) {
            $ins('terms', null, $terms);
        }

        return $n;
    }

    /** Persist the differences between two stored versions of a quotation (used by revise). */
    private static function recordChanges(int $quotationId, int $fromVid, int $toVid, int $fromNo, int $toNo, string $reason, int $userId): void
    {
        $db = Crm::db();
        self::recordChangeSet($quotationId,
            $db->table('crm_quotation_items')->where('version_id', $fromVid)->orderBy('line_no')->get()->getResultArray(), $db->table('crm_quotation_items')->where('version_id', $toVid)->orderBy('line_no')->get()->getResultArray(),
            $db->table('crm_quotation_versions')->where('id', $fromVid)->get()->getRowArray(), $db->table('crm_quotation_versions')->where('id', $toVid)->get()->getRowArray(),
            $db->table('crm_quotation_charges')->where('version_id', $fromVid)->get()->getResultArray(), $db->table('crm_quotation_charges')->where('version_id', $toVid)->get()->getResultArray(),
            $fromNo, $toNo, $reason, $userId);
    }

    /**
     * Full version history for the UI: every version (newest first) with its items, charges, author, reason, PDF state, the tracked changes
     * recorded against it (draft saves + the revision diff) and the negotiation that produced it.
     * @return list<array<string,mixed>>
     */
    public static function versions(int $quotationId, array $actor): array
    {
        self::loadQuotation($quotationId, $actor);
        $db    = Crm::db();
        $names = HistoryService::userNames();
        $log   = [];
        foreach (self::changeLog($quotationId, $actor) as $c) {
            $log[(int) $c['to_version_no']][] = $c;
        }
        $neg = [];
        foreach ($db->table('crm_negotiations')->where('quotation_id', $quotationId)->get()->getResultArray() as $n) {
            if ($n['resulting_version_id']) {
                $neg[(int) $n['resulting_version_id']] = $n;
            }
        }
        $out = [];
        foreach ($db->table('crm_quotation_versions')->where('quotation_id', $quotationId)->orderBy('version_no', 'DESC')->get()->getResultArray() as $v) {
            $v['items']        = $db->table('crm_quotation_items')->where('version_id', $v['id'])->orderBy('line_no')->get()->getResultArray();
            $v['charges']      = $db->table('crm_quotation_charges')->where('version_id', $v['id'])->get()->getResultArray();
            $v['author']       = $v['created_by'] ? ($names[(int) $v['created_by']] ?? '?') : 'System';
            $v['changes']      = $log[(int) $v['version_no']] ?? [];
            $v['negotiation']  = $neg[(int) $v['id']] ?? null;
            $out[] = $v;
        }

        return $out;
    }

    /** @return list<array<string,mixed>> change log of a quotation, newest version first */
    public static function changeLog(int $quotationId, array $actor): array
    {
        self::loadQuotation($quotationId, $actor);
        $u = HistoryService::userNames();

        return array_map(static fn ($r) => $r + ['user_name' => $r['user_id'] ? ($u[(int) $r['user_id']] ?? '?') : 'System', 'data' => json_decode((string) $r['detail'], true)],
            Crm::db()->table('crm_quotation_changes')->where('quotation_id', $quotationId)->orderBy('to_version_no', 'DESC')->orderBy('id')->get()->getResultArray());
    }

    /** Side-by-side differences between two versions of one quotation. */
    public static function compare(int $quotationId, int $versionA, int $versionB, array $actor): array
    {
        self::loadQuotation($quotationId, $actor);
        $db  = Crm::db();
        $get = static function (int $no) use ($db, $quotationId) {
            $v = $db->table('crm_quotation_versions')->where(['quotation_id' => $quotationId, 'version_no' => $no])->get()->getRowArray() ?? throw new CrmException("Version {$no} not found.");
            $v['items'] = $db->table('crm_quotation_items')->where('version_id', $v['id'])->orderBy('line_no')->get()->getResultArray();

            return $v;
        };
        $a = $get($versionA);
        $b = $get($versionB);
        $totals = [];
        foreach (['subtotal', 'discount_total', 'taxable_total', 'tax_total', 'charges_total', 'grand_total'] as $k) {
            $totals[$k] = ['a' => $a[$k], 'b' => $b[$k], 'delta' => Money::round(Money::sub($b[$k], $a[$k]))];
        }
        $changes = array_map(static fn ($c) => ['item' => $c['item'], 'change' => $c['type'], 'fields' => $c['detail']], self::diffItems($a['items'], $b['items']));

        return ['a' => $versionA, 'b' => $versionB, 'totals' => $totals, 'items' => $changes, 'reason' => $b['revision_reason']];
    }
}
