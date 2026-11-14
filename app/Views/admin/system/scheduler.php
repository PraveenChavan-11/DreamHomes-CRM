<?php include __DIR__ . '/_status.php'; ?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Scheduler &amp; Cron</h1>
    <p class="text-xs text-slate-500 mt-1">Each scheduled command records a heartbeat when it actually runs. A cron line existing on the server is <strong>not</strong> treated as proof — only a recorded run is.</p>
  </div>
  <?= th_system_tabs('scheduler') ?>

  <div class="grid gap-4 md:grid-cols-2">
    <?php foreach ($jobs as $name => $j): ?>
      <section class="rounded-2xl border border-slate-200 bg-white p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 class="text-sm font-extrabold text-slate-900"><?= esc($j['label']) ?></h2>
            <code class="text-[11px] text-slate-500">php spark <?= esc($name) ?></code>
            <?php if (!$j['required']): ?><span class="ml-2 text-[10px] font-bold text-slate-400 uppercase">optional</span><?php endif; ?>
          </div>
          <?= th_status_badge($j['status']) ?>
        </div>
        <p class="mt-2 text-xs text-slate-600"><?= esc($j['summary']) ?></p>
        <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-[11px]">
          <dt class="font-semibold text-slate-500">Expected every</dt><dd><?= (int) $j['interval'] ?> min</dd>
          <dt class="font-semibold text-slate-500">Last started</dt><dd><?= esc($j['last_started'] ?? 'never recorded') ?></dd>
          <dt class="font-semibold text-slate-500">Last successful</dt><dd><?= esc($j['last_success'] ?? 'never recorded') ?></dd>
          <dt class="font-semibold text-slate-500">Failures (24h)</dt><dd><?= (int) $j['failures_24h'] ?></dd>
          <dt class="font-semibold text-slate-500">Next expected</dt><dd><?= esc($j['next_expected'] ?? 'unknown') ?></dd>
          <?php if ($j['last_error']): ?><dt class="font-semibold text-slate-500">Last error</dt><dd class="break-words"><?= esc($j['last_error']) ?></dd><?php endif; ?>
        </dl>
      </section>
    <?php endforeach; ?>
  </div>

  <section class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-xs text-slate-700 space-y-2">
    <h2 class="text-sm font-extrabold text-slate-900">Setting up cron on Hostinger (hPanel → Advanced → Cron Jobs)</h2>
    <p>Add one entry per required job. Replace the path with your site's folder (the one that contains <code>spark</code>):</p>
    <pre class="bg-white border border-slate-200 rounded-lg p-3 overflow-x-auto text-[11px]">*/5  * * * *  cd /path/to/your/app &amp;&amp; php spark social:publish   &gt;&gt; /dev/null 2&gt;&amp;1
*/15 * * * *  cd /path/to/your/app &amp;&amp; php spark engagement:run   &gt;&gt; /dev/null 2&gt;&amp;1</pre>
    <p>This page cannot read your hosting's crontab. After saving the entries, wait for one interval and reload — the job will turn “Healthy” only once it has genuinely run.</p>
  </section>

  <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
    <h2 class="px-5 pt-4 text-sm font-extrabold text-slate-900">Recent runs</h2>
    <table class="min-w-full text-xs mt-2">
      <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="text-left px-4 py-2">Job</th><th class="text-left px-4 py-2">Result</th><th class="text-left px-4 py-2">Started</th><th class="text-left px-4 py-2">Duration</th><th class="text-left px-4 py-2">Items</th><th class="text-left px-4 py-2">Error</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$recent): ?><tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No runs recorded yet.</td></tr><?php endif; ?>
        <?php foreach ($recent as $r): $st = ['SUCCESS' => 'healthy', 'FAILED' => 'failed', 'RUNNING' => 'processing'][$r['status']] ?? 'not_verified'; ?>
          <tr><td class="px-4 py-2 font-semibold"><?= esc($r['job_name']) ?></td><td class="px-4 py-2"><?= th_status_badge($st) ?></td><td class="px-4 py-2 whitespace-nowrap"><?= esc($r['started_at']) ?></td><td class="px-4 py-2"><?= (int) $r['duration_ms'] ?> ms</td><td class="px-4 py-2"><?= (int) $r['items_processed'] ?></td><td class="px-4 py-2 break-words"><?= esc($r['error_details'] ?? '—') ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
