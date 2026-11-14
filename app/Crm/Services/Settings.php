<?php

namespace Crm\Services;

/** Configurable business parameters. Seeded as *assumed defaults* (is_default_assumption=1) until DHIS confirms policy. */
final class Settings
{
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** key => [value, group, description] */
    public const DEFAULTS = [
        'discount.limit.executive_pct'   => ['5', 'approval', 'Max discount % a Sales Executive may apply without approval (ASSUMED default)'],
        'discount.limit.manager_pct'     => ['15', 'approval', 'Max discount % a Sales Manager may approve (ASSUMED default)'],
        'discount.limit.admin_pct'       => ['100', 'approval', 'Max discount % an Admin may approve'],
        'discount.allow_self_approval'   => ['0', 'approval', 'Allow approvers to approve their own requests'],
        'tax.default_rate'               => ['18', 'tax', 'Default GST % (ASSUMED default)'],
        'quotation.validity_days'        => ['15', 'quotation', 'Default quotation validity in days (ASSUMED default)'],
        'quotation.round_off'            => ['1', 'quotation', 'Round grand total to nearest rupee'],
        'session.timeout_minutes'        => ['30', 'security', 'Idle session timeout'],
        'auth.max_failed_logins'         => ['5', 'security', 'Failed logins before lock'],
        'auth.lock_minutes'              => ['15', 'security', 'Lock duration after too many failures'],
        'assignment.default_mode'        => ['round_robin', 'assignment', 'round_robin | workload | team | territory'],
        'followup.escalate_after_hours'  => ['24', 'followup', 'Escalate overdue follow-up to manager after N hours'],
        'visit.reminder_hours_before'    => ['24', 'followup', 'Site visit reminder lead time'],
        'email.mode'                     => ['log_only', 'email', 'log_only (never contacts recipient) | smtp'],
        'email.retry_max_attempts'       => ['3', 'email', 'Max send attempts'],
        'company.name'                   => ['Dream Homes Interior Solutions', 'company', 'Company name (from public website)'],
        'company.address'                => ['Showroom No. 204 & 103, Austin Plaza, Kaspate Vasti, Wakad, Pune - 411057', 'company', 'Address as published on dhisgroup.in'],
        'company.phone'                  => ['+91-9011194000', 'company', 'Phone as published on dhisgroup.in'],
        'company.email'                  => ['windows@dhisgroup.in', 'company', 'Email as published on dhisgroup.in'],
        'company.gstin'                  => ['', 'company', 'GSTIN - NOT SUPPLIED by DHIS; enter before issuing real quotations'],
        'company.state'                  => ['Maharashtra', 'company', 'Home state for CGST/SGST vs IGST split'],
        'quotation.terms'                => ["1. Prices cover the items listed; anything not listed is excluded.\n2. Taxes as applicable.\n3. Delivery timeline subject to site readiness.\n4. PLACEHOLDER terms - DHIS to supply approved wording.", 'quotation', 'Default T&C (placeholder)'],
        'quotation.payment_terms'        => ['As agreed with customer (placeholder - DHIS to supply).', 'quotation', 'Default payment terms (placeholder)'],
        'demo.enabled'                   => ['0', 'demo', 'Explicitly enables demo mode and demo reset'],
    ];

    public static function get(string $key, ?string $fallback = null): string
    {
        self::$cache ??= array_column(Crm::db()->table('crm_settings')->select('setting_key, setting_value')->get()->getResultArray(), 'setting_value', 'setting_key');

        return self::$cache[$key] ?? $fallback ?? (self::DEFAULTS[$key][0] ?? '');
    }

    public static function set(string $key, string $value, ?int $by = null): void
    {
        $old = self::get($key);
        Crm::db()->query(
            'INSERT INTO crm_settings (setting_key, setting_value, setting_group, is_default_assumption, updated_by) VALUES (?,?,?,0,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_default_assumption = 0, updated_by = VALUES(updated_by)',
            [$key, $value, self::DEFAULTS[$key][1] ?? 'general', $by]
        );
        self::$cache = null;
        AuditService::audit('setting.changed', 'setting', $key, ['prev' => ['value' => $old], 'new' => ['value' => $value], 'user_id' => $by ?? Crm::actorId()]);
    }

    public static function seedDefaults(): void
    {
        $db = Crm::db();
        foreach (self::DEFAULTS as $k => [$v, $g, $d]) {
            $db->query('INSERT IGNORE INTO crm_settings (setting_key, setting_value, setting_group, description, is_default_assumption) VALUES (?,?,?,?,1)', [$k, $v, $g, $d]);
        }
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
