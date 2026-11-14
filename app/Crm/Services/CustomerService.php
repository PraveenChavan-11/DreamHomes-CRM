<?php

namespace Crm\Services;

final class CustomerService
{
    public static function normMobile(?string $m): string
    {
        $d = preg_replace('/\D+/', '', (string) $m);

        return strlen($d) > 10 ? substr($d, -10) : $d;
    }

    /** @return array<string,string> */
    public static function validate(array $d): array
    {
        $e = [];
        if (trim((string) ($d['name'] ?? '')) === '') {
            $e['name'] = 'Customer name is required.';
        }
        $m = self::normMobile($d['mobile'] ?? '');
        if (! preg_match('/^[6-9]\d{9}$/', $m)) {
            $e['mobile'] = 'Enter a valid 10-digit Indian mobile number.';
        }
        if (! empty($d['email']) && ! filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid email address.';
        }
        if (! empty($d['pin_code']) && ! preg_match('/^\d{6}$/', $d['pin_code'])) {
            $e['pin_code'] = 'PIN code must be 6 digits.';
        }
        if (! empty($d['gstin']) && ! preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z][A-Z\d]Z[A-Z\d]$/', strtoupper($d['gstin']))) {
            $e['gstin'] = 'GSTIN format is invalid.';
        }

        return $e;
    }

    /** @return list<array<string,mixed>> */
    public static function findDuplicates(array $d, ?int $excludeId = null): array
    {
        $db = Crm::db();
        $b  = $db->table('crm_customers')->select('id, customer_no, name, mobile, email, status')->groupStart();
        $m  = self::normMobile($d['mobile'] ?? '');
        $b->where('mobile_norm', $m !== '' ? $m : '-');
        if (! empty($d['email'])) {
            $b->orWhere('email_norm', strtolower(trim($d['email'])));
        }
        $b->groupEnd();
        if ($excludeId) {
            $b->where('id !=', $excludeId);
        }

        return $b->get()->getResultArray();
    }

    /** @param array<string,mixed> $d */
    public static function create(array $d, int $by, bool $allowDuplicate = false, int $isDemo = 0): int
    {
        if ($err = self::validate($d)) {
            throw CrmException::validation($err);
        }
        if (! $allowDuplicate && ($dups = self::findDuplicates($d))) {
            throw DuplicateException::with('A customer with the same mobile or email already exists.', $dups);
        }

        return (int) Crm::tx(static function ($db) use ($d, $by, $isDemo) {
            $no = NumberSequenceService::next('customer');
            $db->table('crm_customers')->insert([
                'customer_no' => $no, 'name' => trim($d['name']), 'customer_type' => $d['customer_type'] ?? 'individual', 'company_name' => $d['company_name'] ?? null,
                'primary_contact' => $d['primary_contact'] ?? null, 'mobile' => $d['mobile'], 'mobile_norm' => self::normMobile($d['mobile']), 'alt_mobile' => $d['alt_mobile'] ?? null,
                'email' => $d['email'] ?? null, 'email_norm' => ! empty($d['email']) ? strtolower(trim($d['email'])) : null,
                'billing_address' => $d['billing_address'] ?? null, 'site_address' => $d['site_address'] ?? null, 'city' => $d['city'] ?? null, 'state' => $d['state'] ?? null,
                'pin_code' => $d['pin_code'] ?? null, 'gstin' => ! empty($d['gstin']) ? strtoupper($d['gstin']) : null, 'source_id' => $d['source_id'] ?? null,
                'owner_user_id' => $d['owner_user_id'] ?? $by, 'status' => 'active', 'remarks' => $d['remarks'] ?? null, 'created_by' => $by, 'updated_by' => $by,
            ]);
            $id = (int) $db->insertID();
            if ($isDemo) {
                $db->query('INSERT IGNORE INTO crm_demo_registry (table_name,row_id) VALUES (?,?)', ['crm_customers', $id]);
            }
            AuditService::workflow('customer.created', 'customer', $id, ['customer_id' => $id, 'entity_ref' => $no, 'new' => ['name' => $d['name'], 'mobile' => $d['mobile']], 'user_id' => $by, 'is_demo' => $isDemo]);

            return $id;
        });
    }

    private const EDITABLE = ['name', 'customer_type', 'company_name', 'primary_contact', 'mobile', 'alt_mobile', 'email', 'billing_address', 'site_address', 'city', 'state', 'pin_code', 'gstin', 'source_id', 'owner_user_id', 'remarks'];

    public static function update(int $id, array $d, int $by, bool $allowDuplicate = false): void
    {
        $db  = Crm::db();
        $cur = $db->table('crm_customers')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Customer not found.');
        $d   = array_merge($cur, array_intersect_key($d, array_flip(self::EDITABLE)));
        if ($err = self::validate($d)) {
            throw CrmException::validation($err);
        }
        if (! $allowDuplicate && ($dups = self::findDuplicates($d, $id))) {
            throw DuplicateException::with('Another customer already uses that mobile or email.', $dups);
        }
        $set = array_intersect_key($d, array_flip(self::EDITABLE));
        $set['mobile_norm'] = self::normMobile($set['mobile']);
        $set['email_norm']  = ! empty($set['email']) ? strtolower(trim($set['email'])) : null;
        $set['gstin']       = ! empty($set['gstin']) ? strtoupper($set['gstin']) : null;
        $set['updated_by']  = $by;
        $diffPrev = $diffNew = [];
        foreach (self::EDITABLE as $f) {
            if ((string) ($cur[$f] ?? '') !== (string) ($set[$f] ?? '')) {
                $diffPrev[$f] = $cur[$f] ?? null;
                $diffNew[$f]  = $set[$f] ?? null;
            }
        }
        if (! $diffNew) {
            return;
        }
        Crm::tx(static function ($db) use ($id, $set, $cur, $diffPrev, $diffNew, $by) {
            $db->table('crm_customers')->where('id', $id)->update($set);
            AuditService::workflow('customer.updated', 'customer', $id, ['customer_id' => $id, 'entity_ref' => $cur['customer_no'], 'prev' => $diffPrev, 'new' => $diffNew, 'user_id' => $by]);
        });
    }

    public static function setStatus(int $id, string $status, int $by, ?string $reason = null): void
    {
        if (! in_array($status, ['active', 'inactive', 'archived'], true)) {
            throw new CrmException('Invalid customer status.');
        }
        $db  = Crm::db();
        $cur = $db->table('crm_customers')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Customer not found.');
        if ($cur['status'] === $status) {
            return;
        }
        Crm::tx(static function ($db) use ($id, $status, $cur, $by, $reason) {
            $db->table('crm_customers')->where('id', $id)->update(['status' => $status, 'updated_by' => $by]);
            AuditService::workflow('customer.' . match ($status) { 'active' => 'reactivated', 'inactive' => 'deactivated', default => 'archived' }, 'customer', $id, [
                'customer_id' => $id, 'entity_ref' => $cur['customer_no'], 'prev' => ['status' => $cur['status']], 'new' => ['status' => $status], 'reason' => $reason, 'user_id' => $by,
            ]);
        });
    }

    public static function hasTransactions(int $id): bool
    {
        $db = Crm::db();
        foreach (['crm_enquiries', 'crm_quotations', 'crm_sales_orders', 'crm_followups', 'crm_activities'] as $t) {
            if ($db->table($t)->where('customer_id', $id)->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }

    /** Hard delete is allowed ONLY for customers with no linked history; otherwise deactivate/archive. */
    public static function delete(int $id, int $by): void
    {
        if (self::hasTransactions($id)) {
            throw new CrmException('This customer has sales history and cannot be deleted. Deactivate or archive instead.');
        }
        $db = Crm::db();
        $c  = $db->table('crm_customers')->where('id', $id)->get()->getRowArray() ?? throw new CrmException('Customer not found.');
        Crm::tx(static function ($db) use ($id, $c, $by) {
            $db->table('crm_customers')->where('id', $id)->delete();
            AuditService::audit('customer.deleted', 'customer', $id, ['prev' => ['customer_no' => $c['customer_no'], 'name' => $c['name']], 'user_id' => $by]);
        });
    }
}
