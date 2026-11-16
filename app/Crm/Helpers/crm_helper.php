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
if (! function_exists('crm_inr_words')) {
    function crm_inr_convert_below_hundred(int $num, array $words): string
    {
        if ($num <= 20) {
            return $words[$num] ?? '';
        }
        $tens = ((int) floor($num / 10)) * 10;
        $units = $num % 10;
        return trim(($words[$tens] ?? '') . ' ' . ($words[$units] ?? ''));
    }

    function crm_inr_words(float $number): string
    {
        $no = (int) floor($number);
        $point = (int) round(($number - $no) * 100);
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];

        $str = [];
        if ($no == 0) {
            $str[] = 'Zero Rupees';
        } else {
            $crores = (int) floor($no / 10000000);
            $no %= 10000000;
            if ($crores > 0) {
                $str[] = crm_inr_convert_below_hundred($crores, $words) . ' Crore';
            }
            $lakhs = (int) floor($no / 100000);
            $no %= 100000;
            if ($lakhs > 0) {
                $str[] = crm_inr_convert_below_hundred($lakhs, $words) . ' Lakh';
            }
            $thousands = (int) floor($no / 1000);
            $no %= 1000;
            if ($thousands > 0) {
                $str[] = crm_inr_convert_below_hundred($thousands, $words) . ' Thousand';
            }
            $hundreds = (int) floor($no / 100);
            $no %= 100;
            if ($hundreds > 0) {
                $str[] = $words[$hundreds] . ' Hundred';
            }
            if ($no > 0) {
                $str[] = crm_inr_convert_below_hundred($no, $words);
            }
            $str[] = 'Rupees';
        }
        if ($point > 0) {
            $str[] = 'and ' . crm_inr_convert_below_hundred($point, $words) . ' Paise';
        }
        $str[] = 'Only';
        return implode(' ', array_filter($str));
    }
}
if (! function_exists('crm_nav')) {
    /**
     * DHIS Sales CRM Enterprise 20-Module Navigation Structure
     * @return list<array{label:string,url:string,key:string,perm:list<string>,icon:string}>
     */
    function crm_nav(): array
    {
        return [
            ['Dashboard', 'crm', 'dashboard', ['dashboard.view'], 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
            ['Leads', 'crm/leads', 'leads', ['leads.view', 'enquiries.view'], 'M13 10V3L4 14h7v7l9-11h-7z'],
            ['Enquiries', 'crm/enquiries', 'enquiries', ['enquiries.view'], 'M8 10h8M8 14h5M5 4h14a1 1 0 011 1v14l-4-3H5a1 1 0 01-1-1V5a1 1 0 011-1z'],
            ['Customers', 'crm/customers', 'customers', ['customers.view'], 'M17 20h5v-2a4 4 0 00-5-3.9M9 11a4 4 0 100-8 4 4 0 000 8zM2 20v-2a4 4 0 014-4h6a4 4 0 014 4v2'],
            ['Projects', 'crm/projects', 'projects', ['projects.view', 'customers.view'], 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['Site Visits', 'crm/visits', 'visits', ['visits.manage'], 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
            ['Measurements', 'crm/measurements', 'measurements', ['visits.manage'], 'M4 8h16M4 16h16M7 8v8M11 8v4M15 8v8M19 8v4'],
            ['Quotations', 'crm/quotations', 'quotations', ['quotations.view'], 'M9 12h6M9 16h6M7 3h7l5 5v13H7z'],
            ['Sales Orders', 'crm/orders', 'orders', ['orders.view'], 'M5 8h14l-1 12H6zM9 8V6a3 3 0 016 0v2'],
            ['Payments', 'crm/payments', 'payments', ['payments.view', 'orders.view'], 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ['Deliveries', 'crm/deliveries', 'deliveries', ['deliveries.view', 'orders.view'], 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2'],
            ['Installations', 'crm/installations', 'installations', ['installations.view', 'orders.view'], 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
            ['After-Sales Service', 'crm/service', 'service', ['service.view', 'customers.view'], 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'],
            ['Reports & Analytics', 'crm/reports', 'reports', ['reports.view', 'quotations.view'], 'M4 20V10M10 20V4M16 20v-8M22 20H2'],
            ['Masters', 'crm/masters', 'masters', ['masters.view', 'products.view', 'settings.manage'], 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['Team & Employees', 'crm/users', 'users', ['users.manage', 'teams.manage'], 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['Notifications', 'crm/notifications', 'notifications', ['dashboard.view'], 'M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3C7.7 6.2 6 8.4 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
            ['Audit Logs', 'crm/audit', 'audit', ['audit.view', 'settings.manage'], 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
            ['Help & Guided Tour', 'crm/tour/guide', 'tour_guide', ['dashboard.view'], 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
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
