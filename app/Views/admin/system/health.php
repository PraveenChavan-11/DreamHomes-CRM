<?php
include __DIR__ . '/_status.php';
use App\Services\SystemHealthService as H;
$titles = [
    'application' => 'Application', 'database' => 'Database', 'storage' => 'Storage',
    'scheduler' => 'Scheduler & Cron', 'jobs' => 'Background Jobs', 'email' => 'Email',
    'social' => 'Social Integrations', 'integrations' => 'External API Clients', 'admin_access' => 'Admin Access',
];
$links = ['scheduler' => 'admin/system/scheduler', 'jobs' => 'admin/system/jobs', 'social' => 'admin/social/accounts', 'integrations' => 'admin/integrations'];
$overallText = [
    H::HEALTHY      => 'Everything that can be checked is healthy.',
    H::WARNING      => 'Something needs attention.',
    H::FAILED       => 'At least one component has failed.',
    H::NOT_VERIFIED => 'Nothing has failed, but some components have no evidence of working yet (see "Not Verified").',
];
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">System Health</h1>
      <p class="text-xs text-slate-500 mt-1">Checked <?= esc($report['generated_at']) ?> · results are cached for <?= (int) $ttl ?> seconds. A status is “Healthy” only when there is real evidence — a saved configuration alone is never enough.</p>
    </div>
    <form method="post" action="<?= base_url('admin/system/health/refresh') ?>">
      <?= csrf_field() ?>
      <button class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm">Re-check now</button>
    </form>
  </div>

  <?= th_system_tabs('health') ?>

  <div class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-wrap items-center gap-4" role="status">
    <?= th_status_badge($report['overall']) ?>
    <p class="text-sm text-slate-700 font-medium"><?= esc($overallText[$report['overall']] ?? '') ?></p>
  </div>

  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($report['checks'] as $key => $c): ?>
      <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="chk-<?= esc($key) ?>">
        <div class="flex items-start justify-between gap-3">
          <h2 id="chk-<?= esc($key) ?>" class="text-sm font-extrabold text-slate-900"><?= esc($titles[$key] ?? $key) ?></h2>
          <?= th_status_badge($c['status']) ?>
        </div>
        <p class="mt-2 text-xs text-slate-600"><?= esc($c['summary']) ?></p>
        <?php if (!empty($c['details'])): ?>
          <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-[11px]">
            <?php foreach ($c['details'] as $k => $v): ?>
              <dt class="font-semibold text-slate-500"><?= esc($k) ?></dt>
              <dd class="text-slate-800 break-words"><?= esc((string) $v) ?></dd>
            <?php endforeach; ?>
          </dl>
        <?php endif; ?>
        <?php if (isset($links[$key])): ?>
          <a href="<?= base_url($links[$key]) ?>" class="mt-3 inline-block text-[11px] font-bold text-brand-primary hover:underline">Open details →</a>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>

  <p class="text-[11px] text-slate-500">This page only reads TalentHub's own database and disk. It does not call LinkedIn, Meta, Google or any external service, so “social” and “external API” statuses reflect stored data and recorded events, not a live ping.</p>
</div>
