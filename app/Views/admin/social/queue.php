<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Publishing Queue &amp; Scheduler Status</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Database-backed asynchronous queue runner, concurrency locks, and exponential backoff retry monitors.</p>
    </div>

    <!-- Health Pill: driven by the heartbeat the social:publish command records on every real run -->
    <?php
      $hb = (new \App\Services\SystemHealthService())->schedulerJobs()['social:publish'];
      $hbCls = ['healthy' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'warning' => 'bg-amber-50 text-amber-800 border-amber-200', 'failed' => 'bg-red-50 text-red-800 border-red-200'][$hb['status']] ?? 'bg-sky-50 text-sky-800 border-sky-200';
      $hbTxt = ['healthy' => 'Cron worker: running', 'warning' => 'Cron worker: missed runs', 'failed' => 'Cron worker: stopped'][$hb['status']] ?? 'Cron worker: not verified';
    ?>
    <div class="flex items-center gap-2">
      <a href="<?= base_url('admin/system/scheduler') ?>" title="<?= esc($hb['summary']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $hbCls ?>">
        <?= esc($hbTxt) ?>
      </a>
    </div>
  </div>

  <!-- Cron Instruction Card -->
  <div class="bg-brand-dark text-white rounded-2xl p-5 shadow-sm border border-brand-accent/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <span class="text-xs font-bold text-brand-accent uppercase tracking-wider block">Production Cron Configuration (Hostinger hPanel / cPanel)</span>
      <p class="text-xs text-slate-300 mt-0.5">Execute this background command every 5 minutes to trigger the publishing queue. Replace the path with your site folder (the one containing <code>spark</code>).</p>
    </div>
    <div class="bg-black/30 rounded-xl px-4 py-2 text-xs font-mono text-brand-accent border border-white/10 select-all">
      */5 * * * * cd /path/to/your/app &amp;&amp; php spark social:publish &gt;&gt; /dev/null 2&gt;&amp;1
    </div>
  </div>

  <!-- Queue Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Scheduled Jobs Pipeline</h2>
      <span class="text-xs font-bold text-slate-600"><?= count($jobs) ?> total job(s)</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="px-5 py-3">Job ID</th>
            <th class="px-4 py-3">Post Title</th>
            <th class="px-4 py-3">Target Platform</th>
            <th class="px-4 py-3">Scheduled At</th>
            <th class="px-4 py-3">Attempts</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($jobs)): ?>
            <tr>
              <td colspan="7" class="py-12 text-center text-slate-400">
                Queue is currently empty. No pending scheduled jobs.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($jobs as $j): ?>
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-3.5 font-mono text-slate-500">#<?= esc($j['id']) ?></td>
                <td class="px-4 py-3.5 font-bold text-slate-900">
                  <a href="<?= base_url('admin/social/posts/' . $j['post_id']) ?>" class="hover:text-brand-primary">
                    <?= esc($j['post_title'] ?? ('Post #' . $j['post_id'])) ?>
                  </a>
                </td>
                <td class="px-4 py-3.5 capitalize font-medium text-slate-700"><?= esc($j['platform']) ?></td>
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-600"><?= esc($j['scheduled_at']) ?></td>
                <td class="px-4 py-3.5 font-mono text-slate-700"><?= esc($j['attempts']) ?> / <?= esc($j['max_attempts']) ?></td>
                <td class="px-4 py-3.5">
                  <?php if ($j['status'] === 'completed'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">Completed</span>
                  <?php elseif ($j['status'] === 'processing'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 animate-pulse">Processing</span>
                  <?php elseif ($j['status'] === 'failed'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700">Failed</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">Queued</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 text-right space-x-2">
                  <?php if ($j['status'] === 'failed'): ?>
                    <form method="POST" action="<?= base_url('admin/social/queue/' . $j['id'] . '/retry') ?>" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="text-xs font-bold text-brand-primary hover:underline">Retry</button>
                    </form>
                  <?php elseif ($j['status'] === 'queued'): ?>
                    <form method="POST" action="<?= base_url('admin/social/queue/' . $j['id'] . '/cancel') ?>" class="inline">
                      <?= csrf_field() ?>
                      <button type="submit" class="text-xs font-bold text-rose-500 hover:underline">Cancel</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

