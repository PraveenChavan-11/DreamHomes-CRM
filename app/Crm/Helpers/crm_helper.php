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
    /** @return list<array{group:string,items:list<array{label:string,url:string,key:string,perm:list<string>,icon:string}>}> */
    function crm_nav(): array
    {
        return [
            ['group' => 'Overview', 'items' => [
                ['Dashboard', 'crm', 'dashboard', ['dashboard.view'], 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
                ['Calendar', 'crm/calendar', 'calendar', ['followups.manage', 'visits.manage'], 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z'],
            ]],
            ['group' => 'Sales', 'items' => [
                ['Customers', 'crm/customers', 'customers', ['customers.view'], 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zM2 20v-2a4 4 0 014-4h6a4 4 0 014 4v2'],
                ['Enquiries', 'crm/enquiries', 'enquiries', ['enquiries.view'], 'M8 10h8M8 14h5M5 4h14a1 1 0 011 1v14l-4-3H5a1 1 0 01-1-1V5a1 1 0 011-1z'],
                ['Pipeline', 'crm/enquiries?view=kanban', 'pipeline', ['enquiries.view'], 'M4 5h4v14H4zM10 5h4v9h-4zM16 5h4v6h-4z'],
                ['Follow-ups', 'crm/followups', 'followups', ['followups.manage'], 'M12 8v4l3 2M12 21a9 9 0 110-18 9 9 0 010 18z'],
                ['Quotations', 'crm/quotations', 'quotations', ['quotations.view'], 'M9 12h6M9 16h6M7 3h7l5 5v13H7z'],
                ['Negotiations', 'crm/negotiations', 'negotiations', ['quotations.view'], 'M8 9l4-4 4 4M8 15l4 4 4-4'],
                ['Sales Orders', 'crm/orders', 'orders', ['orders.view'], 'M5 8h14l-1 12H6zM9 8V6a3 3 0 016 0v2'],
            ]],
            ['group' => 'Insight', 'items' => [
                ['Sales History', 'crm/history', 'history', ['history.view'], 'M12 8v5l3 2M3 12a9 9 0 109-9 9 9 0 00-7 3.4M3 4v4h4'],
                ['Reports', 'crm/reports', 'reports', ['reports.view', 'quotations.view'], 'M4 20V10M10 20V4M16 20v-8M22 20H2'],
            ]],
            ['group' => 'Administration', 'items' => [
                ['Products & Pricing', 'crm/products', 'products', ['products.view'], 'M20 7l-8-4-8 4v10l8 4 8-4zM4 7l8 4 8-4M12 11v10'],
                ['Import Enquiries', 'crm/imports', 'imports', ['customers.import'], 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16'],
                ['Email Log', 'crm/emails', 'emails', ['emails.view'], 'M3 7l9 6 9-6M4 5h16a1 1 0 011 1v12a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z'],
                ['Users & Teams', 'crm/users', 'users', ['users.manage', 'teams.manage'], 'M12 11a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0'],
                ['Audit Trail', 'crm/audit', 'audit', ['audit.view'], 'M9 12l2 2 4-4M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z'],
                ['Settings', 'crm/settings', 'settings', ['settings.manage'], 'M12 15a3 3 0 100-6 3 3 0 000 6zM19 12a7 7 0 00-.1-1l2-1.5-2-3.4-2.3.9a7 7 0 00-1.7-1L14.5 3h-4l-.4 2.5a7 7 0 00-1.7 1l-2.3-.9-2 3.4 2 1.5a7 7 0 000 2l-2 1.5 2 3.4 2.3-.9a7 7 0 001.7 1l.4 2.5h4l.4-2.5a7 7 0 001.7-1l2.3.9 2-3.4-2-1.5c.1-.3.1-.7.1-1z'],
                ['Demo Data', 'crm/demo', 'demo', ['demo.reset'], 'M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3zM4 7v5c0 1.7 3.6 3 8 3s8-1.3 8-3V7M4 12v5c0 1.7 3.6 3 8 3s8-1.3 8-3v-5'],
            ]],
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
