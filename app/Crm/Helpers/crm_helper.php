<?php

use Crm\Services\AccessScope;
use Crm\Services\Money;

if (! function_exists('crm_user')) {
    function crm_user(): array
    {
        return session()->get('crm_user') ?? [];
    }
}
if (! function_exists('crm_can')) {
    function crm_can(string ...$perms): bool
    {
        $u = crm_user();
        foreach ($perms as $p) {
            if (AccessScope::can($u, $p)) {
                return true;
            }
        }

        return false;
    }
}
if (! function_exists('crm_money')) {
    function crm_money($v, bool $symbol = true): string
    {
        return ($symbol ? '₹' : '') . number_format((float) Money::norm($v === null ? '0' : (string) $v), 2, '.', ',');
    }
}
if (! function_exists('crm_money0')) {
    /** Whole-rupee Indian grouping (12,34,567). */
    function crm_money0($v): string
    {
        $n = (string) round((float) Money::norm((string) $v));
        $neg = str_starts_with($n, '-');
        $n = ltrim($n, '-');
        if (strlen($n) > 3) {
            $n = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3)) . ',' . substr($n, -3);
        }

        return '₹' . ($neg ? '-' : '') . $n;
    }
}
if (! function_exists('crm_date')) {
    function crm_date(?string $d, string $fmt = 'd M Y'): string
    {
        return $d && strtotime($d) ? date($fmt, strtotime($d)) : '—';
    }
}
if (! function_exists('crm_dt')) {
    function crm_dt(?string $d): string
    {
        return crm_date($d, 'd M Y, H:i');
    }
}
if (! function_exists('crm_label')) {
    function crm_label(?string $s): string
    {
        return $s === null || $s === '' ? '—' : ucwords(str_replace(['_', '.'], ' ', $s));
    }
}
if (! function_exists('crm_badge')) {
    function crm_badge(?string $status): string
    {
        $tone = match ($status) {
            'won', 'accepted', 'approved', 'completed', 'confirmed', 'active', 'delivered', 'applied', 'closed', 'sent' => 'green',
            'lost', 'rejected', 'cancelled', 'failed', 'bounced', 'missed', 'inactive', 'urgent' => 'red',
            'negotiation', 'pending', 'queued', 'pending_confirmation', 'on_hold', 'returned', 'escalated', 'draft', 'high', 'scheduled', 'rescheduled' => 'amber',
            'new', 'assigned', 'contacted', 'qualified', 'site_visit', 'quotation_preparation', 'quotation_sent', 'revised', 'medium' => 'blue',
            default => 'grey',
        };

        return '<span class="badge badge-' . $tone . '">' . esc(crm_label($status)) . '</span>';
    }
}
if (! function_exists('crm_nav')) {
    /**
     * DHIS Sales CRM Simplified Sidebar Navigation: Exactly 8 core items
     * @return list<array{label:string,url:string,key:string,perm:list<string>,icon:string}>
     */
    function crm_nav(): array
    {
        return [
            ['Dashboard', 'crm', 'dashboard', ['dashboard.view'], 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
            ['Enquiries', 'crm/enquiries', 'enquiries', ['enquiries.view'], 'M8 10h8M8 14h5M5 4h14a1 1 0 011 1v14l-4-3H5a1 1 0 01-1-1V5a1 1 0 011-1z'],
            ['Customers', 'crm/customers', 'customers', ['customers.view'], 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zM2 20v-2a4 4 0 014-4h6a4 4 0 014 4v2'],
            ['Follow-ups', 'crm/followups', 'followups', ['followups.manage'], 'M12 8v4l3 2M12 21a9 9 0 110-18 9 9 0 010 18z'],
            ['Quotations', 'crm/quotations', 'quotations', ['quotations.view'], 'M9 12h6M9 16h6M7 3h7l5 5v13H7z'],
            ['Sales Orders', 'crm/orders', 'orders', ['orders.view'], 'M5 8h14l-1 12H6zM9 8V6a3 3 0 016 0v2'],
            ['Reports', 'crm/reports', 'reports', ['reports.view', 'quotations.view'], 'M4 20V10M10 20V4M16 20v-8M22 20H2'],
            ['Settings', 'crm/settings', 'settings', ['settings.manage', 'users.manage', 'products.view', 'audit.view'], 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12a7 7 0 00-.1-1l2-1.5-2-3.4-2.3.9a7 7 0 00-1.7-1L14.5 3h-4l-.4 2.5a7 7 0 00-1.7 1l-2.3-.9-2 3.4 2 1.5a7 7 0 000 2l-2 1.5 2 3.4 2.3-.9a7 7 0 001.7 1l.4 2.5h4l.4-2.5a7 7 0 001.7-1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1z'],
        ];
    }
}
if (! function_exists('crm_icon')) {
    function crm_icon(string $path, int $size = 18): string
    {
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $path . '"/></svg>';
    }
}
if (! function_exists('crm_sel')) {
    function crm_sel($a, $b): string
    {
        return (string) $a === (string) $b ? ' selected' : '';
    }
}
if (! function_exists('crm_old')) {
    function crm_old(string $key, $default = '')
    {
        $v = old($key);

        return $v === null || $v === '' ? $default : $v;
    }
}
if (! function_exists('crm_err')) {
    function crm_err(string $field): string
    {
        $e = session()->getFlashdata('errors') ?? [];

        return isset($e[$field]) ? '<div class="field-error" role="alert">' . esc($e[$field]) . '</div>' : '';
    }
}
