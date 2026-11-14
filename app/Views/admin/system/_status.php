<?php
/** Shared status badge for System pages. Colour is never the only signal: the label is always printed. */
if (!function_exists('th_status_badge')) {
    function th_status_badge(string $status): string
    {
        $map = [
            'healthy'             => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'bg-emerald-500', '✓'],
            'warning'             => ['bg-amber-50 text-amber-800 border-amber-200', 'bg-amber-500', '!'],
            'failed'              => ['bg-red-50 text-red-800 border-red-200', 'bg-red-500', '✕'],
            'not_configured'      => ['bg-slate-100 text-slate-700 border-slate-200', 'bg-slate-400', '–'],
            'permission_required' => ['bg-violet-50 text-violet-800 border-violet-200', 'bg-violet-500', '🔒'],
            'not_verified'        => ['bg-sky-50 text-sky-800 border-sky-200', 'bg-sky-500', '?'],
            // job statuses
            'pending'             => ['bg-sky-50 text-sky-800 border-sky-200', 'bg-sky-500', '…'],
            'processing'          => ['bg-indigo-50 text-indigo-800 border-indigo-200', 'bg-indigo-500', '⟳'],
            'completed'           => ['bg-emerald-50 text-emerald-800 border-emerald-200', 'bg-emerald-500', '✓'],
            'cancelled'           => ['bg-slate-100 text-slate-600 border-slate-200', 'bg-slate-400', '–'],
        ];
        [$cls, $dot, $icon] = $map[$status] ?? $map['not_verified'];
        $label = \App\Services\SystemHealthService::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
        return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold border ' . $cls . '">'
            . '<span class="w-1.5 h-1.5 rounded-full ' . $dot . '" aria-hidden="true"></span>' . esc($label) . '</span>';
    }
}
if (!function_exists('th_system_tabs')) {
    function th_system_tabs(string $active): string
    {
        $tabs = ['health' => ['System Health', 'admin/system/health'], 'jobs' => ['Background Jobs', 'admin/system/jobs'], 'scheduler' => ['Scheduler & Cron', 'admin/system/scheduler']];
        $h = '<nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3" aria-label="System sections">';
        foreach ($tabs as $k => [$label, $path]) {
            $on = $k === $active;
            $h .= '<a href="' . base_url($path) . '" class="px-3.5 py-2 rounded-lg text-xs font-bold ' . ($on ? 'bg-brand-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50') . '"' . ($on ? ' aria-current="page"' : '') . '>' . esc($label) . '</a>';
        }
        return $h . '</nav>';
    }
}
