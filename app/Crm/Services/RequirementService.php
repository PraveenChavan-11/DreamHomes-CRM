<?php

namespace Crm\Services;

/**
 * What the customer currently wants, per enquiry. Editable at every follow-up/negotiation step (different product, variant, size, quantity,
 * hand-typed price). Rows are never deleted: dropping marks status=dropped. Every change is appended to crm_requirement_changes and the
 * workflow timeline, and the active set pre-fills the next quotation version.
 */
final class RequirementService
{
    public const SIZE_UNITS = ['mm' => '0.00328084', 'cm' => '0.0328084', 'inch' => '0.08333333', 'ft' => '1'];
    private const FIELDS = ['product_id', 'description', 'variant', 'width', 'height', 'size_unit', 'nos', 'quantity', 'unit', 'expected_price', 'notes'];

    /** Billable quantity from a size: sqft = w×h×nos (in sq ft); rft = w×nos (in running ft); else null. */
    public static function quantityFromSize(?string $model, $width, $height, ?string $unit, $nos): ?string
    {
        if (! $width || ! $unit || ! isset(self::SIZE_UNITS[$unit])) {
            return null;
        }
        $f   = self::SIZE_UNITS[$unit];
        $n   = $nos !== null && $nos !== '' && Money::cmp((string) $nos, '0') > 0 ? (string) $nos : '1';
        $w   = bcmul(Money::norm((string) $width), $f, 8);
        if ($model === 'sqft' && $height) {
            $h = bcmul(Money::norm((string) $height), $f, 8);

            return Money::round(bcmul(bcmul($w, $h, 8), $n, 8), 3);
        }
        if ($model === 'rft') {
            return Money::round(bcmul($w, $n, 8), 3);
        }

        return null;
    }

    public static function validate(array $d): array
    {
        $e = [];
        if (trim((string) ($d['description'] ?? '')) === '' && empty($d['product_id'])) {
            $e['description'] = 'Choose a product or describe the item.';
        }
        foreach (['width', 'height', 'nos', 'quantity', 'expected_price'] as $k) {
            if (isset($d[$k]) && $d[$k] !== '' && (! is_numeric($d[$k]) || $d[$k] < 0)) {
                $e[$k] = 'Enter a non-negative number.';
            }
        }
        if (! empty($d['size_unit']) && ! isset(self::SIZE_UNITS[$d['size_unit']])) {
            $e['size_unit'] = 'Unit must be mm, cm, inch or ft.';
        }

        return $e;
    }

    private static function clean(array $d): array
    {
        $db = Crm::db();
        $p  = ! empty($d['product_id']) ? $db->table('crm_products')->where('id', (int) $d['product_id'])->get()->getRowArray() : null;
        if (! empty($d['product_id']) && ! $p) {
            throw CrmException::validation(['product_id' => 'Product not found.']);
        }
        $qty = $d['quantity'] ?? '';
        $auto = self::quantityFromSize($p['pricing_model'] ?? ((($d['unit'] ?? '') === 'sqft') ? 'sqft' : ((($d['unit'] ?? '') === 'rft') ? 'rft' : null)), $d['width'] ?? null, $d['height'] ?? null, $d['size_unit'] ?? null, $d['nos'] ?? null);
        if ($auto !== null) {
            $qty = $auto;   // size drives quantity; typed quantity is ignored for area/length-priced lines
        }
        $nz = static fn ($v) => ($v === '' || $v === null) ? null : $v;

        return [
            'product_id' => $p['id'] ?? null, 'description' => trim((string) (($d['description'] ?? '') !== '' ? $d['description'] : ($p['name'] ?? ''))), 'variant' => $nz($d['variant'] ?? null),
            'width' => $nz($d['width'] ?? null), 'height' => $nz($d['height'] ?? null), 'size_unit' => $nz($d['size_unit'] ?? null), 'nos' => $nz($d['nos'] ?? null),
            'quantity' => $nz($qty) ?? '1', 'unit' => $d['unit'] ?? ($p['unit'] ?? 'nos'), 'expected_price' => $nz($d['expected_price'] ?? null), 'notes' => $nz($d['notes'] ?? null),
        ];
    }

    private static function guard(int $enquiryId, array $actor): array
    {
        if (! AccessScope::can($actor, 'followups.manage') && ! AccessScope::can($actor, 'quotations.create')) {
            throw new CrmException('You do not have permission to edit customer requirements.');
        }
        $e = Crm::db()->table('crm_enquiries')->where('id', $enquiryId)->get()->getRowArray() ?? throw new CrmException('Enquiry not found.');
        if (! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
            throw new CrmException('You do not have access to this enquiry.');
        }
        if (! WorkflowService::isActive($e['status'])) {
            throw new CrmException('Requirements can only be edited on active enquiries.');
        }

        return $e;
    }

    private static function log(array $e, int $reqId, string $action, ?array $before, ?array $after, array $actor, ?string $reason, ?int $followupId): void
    {
        $db = Crm::db();
        $db->table('crm_requirement_changes')->insert([
            'enquiry_id' => $e['id'], 'requirement_id' => $reqId, 'followup_id' => $followupId, 'action' => $action, 'before_json' => $before ? json_encode($before) : null,
            'after_json' => $after ? json_encode($after) : null, 'reason' => $reason ? mb_substr($reason, 0, 500) : null, 'user_id' => $actor['id'], 'created_at' => Crm::now(),
        ]);
        AuditService::workflow('requirement.' . $action, 'enquiry', (int) $e['id'], [
            'customer_id' => (int) $e['customer_id'], 'enquiry_id' => (int) $e['id'], 'entity_ref' => $e['enquiry_no'], 'user_id' => $actor['id'], 'prev' => $before, 'new' => $after, 'reason' => $reason,
            'meta' => ['requirement_id' => $reqId, 'followup_id' => $followupId], 'is_demo' => (int) $e['is_demo'],
        ]);
    }

    public static function add(int $enquiryId, array $actor, array $d, ?string $reason = null, ?int $followupId = null): int
    {
        if ($err = self::validate($d)) {
            throw CrmException::validation($err);
        }

        return (int) Crm::tx(static function ($db) use ($enquiryId, $actor, $d, $reason, $followupId) {
            $e   = self::guard($enquiryId, $actor);
            $row = self::clean($d);
            $db->table('crm_requirements')->insert($row + ['enquiry_id' => $enquiryId, 'created_by' => $actor['id'], 'created_at' => Crm::now()]);
            $id = (int) $db->insertID();
            self::log($e, $id, 'added', null, $row, $actor, $reason, $followupId);

            return $id;
        });
    }

    public static function update(int $reqId, array $actor, array $d, ?string $reason = null, ?int $followupId = null): void
    {
        Crm::tx(static function ($db) use ($reqId, $actor, $d, $reason, $followupId) {
            $cur = $db->query('SELECT * FROM crm_requirements WHERE id = ? FOR UPDATE', [$reqId])->getRowArray() ?? throw new CrmException('Requirement not found.');
            if ($err = self::validate($d + array_intersect_key($cur, array_flip(self::FIELDS)))) {
                throw CrmException::validation($err);
            }
            $e   = self::guard((int) $cur['enquiry_id'], $actor);
            $new = self::clean($d + array_intersect_key($cur, array_flip(self::FIELDS)));
            $before = array_intersect_key($cur, $new);
            $diffB = $diffA = [];
            foreach ($new as $k => $v) {
                if ((string) ($before[$k] ?? '') !== (string) ($v ?? '')) {
                    $diffB[$k] = $before[$k] ?? null;
                    $diffA[$k] = $v;
                }
            }
            if (! $diffA) {
                return;
            }
            $db->table('crm_requirements')->where('id', $reqId)->update($new + ['status' => 'active']);
            self::log($e, $reqId, $cur['status'] === 'dropped' ? 'restored' : 'updated', $diffB, $diffA, $actor, $reason, $followupId);
        });
    }

    public static function drop(int $reqId, array $actor, string $reason, ?int $followupId = null): void
    {
        if (trim($reason) === '') {
            throw CrmException::validation(['reason' => 'A reason is required to drop an item.']);
        }
        Crm::tx(static function ($db) use ($reqId, $actor, $reason, $followupId) {
            $cur = $db->query('SELECT * FROM crm_requirements WHERE id = ? FOR UPDATE', [$reqId])->getRowArray() ?? throw new CrmException('Requirement not found.');
            $e   = self::guard((int) $cur['enquiry_id'], $actor);
            if ($cur['status'] === 'dropped') {
                return;
            }
            $db->table('crm_requirements')->where('id', $reqId)->update(['status' => 'dropped']);
            self::log($e, $reqId, 'dropped', ['description' => $cur['description'], 'variant' => $cur['variant'], 'quantity' => $cur['quantity']], null, $actor, $reason, $followupId);
        });
    }

    /** @return list<array<string,mixed>> */
    public static function active(int $enquiryId): array
    {
        return Crm::db()->table('crm_requirements')->where(['enquiry_id' => $enquiryId, 'status' => 'active'])->orderBy('id')->get()->getResultArray();
    }

    /** Active requirements as quotation input lines (variant/size/manual price carried over). */
    public static function toQuotationLines(int $enquiryId): array
    {
        return array_map(static fn ($r) => [
            'product_id' => $r['product_id'], 'description' => $r['description'], 'variant' => $r['variant'], 'width' => $r['width'], 'height' => $r['height'], 'size_unit' => $r['size_unit'],
            'nos' => $r['nos'], 'quantity' => $r['quantity'], 'unit' => $r['unit'], 'unit_price' => $r['expected_price'],
        ], self::active($enquiryId));
    }

    /** @return list<array<string,mixed>> newest first, with user names */
    public static function history(int $enquiryId, array $actor): array
    {
        if (! AccessScope::canSeeEnquiry($actor, $enquiryId)) {
            throw new CrmException('You do not have access to this enquiry.');
        }
        $u = HistoryService::userNames();

        return array_map(static fn ($r) => $r + ['user_name' => $r['user_id'] ? ($u[(int) $r['user_id']] ?? '?') : 'System', 'before' => json_decode((string) $r['before_json'], true), 'after' => json_decode((string) $r['after_json'], true)],
            Crm::db()->table('crm_requirement_changes')->where('enquiry_id', $enquiryId)->orderBy('id', 'DESC')->get()->getResultArray());
    }
}
