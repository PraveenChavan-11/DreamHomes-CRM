<?php
$path = 'app/Views/admin/engagement/analytics/index.php';
$content = file_get_contents($path);

$target = '  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Campaign Analytics &amp; Conversion Telemetry</h1>
      <p class="text-xs text-slate-500 mt-1">Audit-verified impression events, user interactions, click-through rates, and goal completions.</p>
    </div>
  </div>';

$replacement = '  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Campaign Analytics &amp; Conversion Telemetry</h1>
      <p class="text-xs text-slate-500 mt-1">Audit-verified impression events, user interactions, click-through rates, and goal completions.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= base_url(\'admin/engagement/analytics/report\') ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-sm">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Executive B2B Report (PDF / Print)</span>
      </a>
    </div>
  </div>';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "analytics/index.php button added.\n";
} else {
    echo "FAILED: target not found in analytics/index.php.\n";
}

