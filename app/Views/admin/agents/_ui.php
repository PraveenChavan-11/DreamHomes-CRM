<?php
/** Small shared helpers for the External AI Agents screens. Meaning is always in the text, never colour alone. */
if (!function_exists('th_chip')) {
    function th_chip(string $text, string $tone = 'slate'): string
    {
        $t = ['green' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'amber' => 'bg-amber-50 text-amber-800 border-amber-200', 'red' => 'bg-red-50 text-red-800 border-red-200',
              'sky' => 'bg-sky-50 text-sky-800 border-sky-200', 'slate' => 'bg-slate-100 text-slate-700 border-slate-200', 'violet' => 'bg-violet-50 text-violet-800 border-violet-200'][$tone] ?? '';
        return '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border ' . $t . '">' . esc($text) . '</span>';
    }
}
if (!function_exists('th_flash')) {
    function th_flash(): string
    {
        $h = '';
        if ($m = session()->getFlashdata('success')) {
            $h .= '<div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3" role="status">' . esc($m) . '</div>';
        }
        if ($m = session()->getFlashdata('error')) {
            $h .= '<div class="rounded-xl border border-red-200 bg-red-50 text-red-800 text-xs font-semibold px-4 py-3" role="alert">' . esc($m) . '</div>';
        }
        return $h;
    }
}
if (!function_exists('th_token_chip')) {
    function th_token_chip(string $state): string
    {
        return match ($state) {
            'active'   => th_chip('Credential active', 'green'), 'expiring' => th_chip('Expires soon', 'amber'), 'expired' => th_chip('Credential expired', 'red'),
            'revoked'  => th_chip('Revoked', 'red'), default => th_chip('No credential', 'slate'),
        };
    }
}
if (!function_exists('th_post_chip')) {
    function th_post_chip(array $p): string
    {
        $d = $p['review_decision'] ?? null;
        return match (true) {
            $d === 'approved' => th_chip('Approved', 'green'), $d === 'rejected' => th_chip('Rejected', 'red'), $d === 'changes_requested' => th_chip('Changes requested', 'amber'),
            ($p['status'] ?? '') === 'pending_approval' => th_chip('Awaiting review', 'sky'), default => th_chip('Draft (not submitted)', 'slate'),
        };
    }
}
