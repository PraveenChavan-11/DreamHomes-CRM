<?php
include __DIR__ . '/_status.php';
use App\Services\BackgroundJobService as B;
?>
<div class="space-y-6 max-w-4xl">
  <a href="<?= base_url('admin/system/jobs') ?>" class="text-xs font-bold text-brand-primary hover:underline">← Back to Background Jobs</a>

  <?php if (session()->getFlashdata('success')): ?><div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="rounded-xl border border-red-200 bg-red-50 text-red-800 text-xs font-semibold px-4 py-3" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-extrabold text-slate-900"><?= esc(ucfirst($job['queue'])) ?> job #<?= (int) $job['id'] ?></h1>
    <?= th_status_badge($job['status']) ?>
  </div>

  <dl class="rounded-2xl border border-slate-200 bg-white p-5 grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 text-xs">
    <dt class="font-semibold text-slate-500">What it does</dt><dd><?= esc($job['type']) ?></dd>
    <dt class="font-semibold text-slate-500">Created</dt><dd><?= esc($job['created_at']) ?></dd>
    <dt class="font-semibold text-slate-500">Last started</dt><dd><?= esc($job['started_at'] ?? '—') ?></dd>
    <dt class="font-semibold text-slate-500">Completed</dt><dd><?= esc($job['completed_at'] ?? '—') ?></dd>
    <dt class="font-semibold text-slate-500">Duration</dt><dd><?= esc($job['duration_label']) ?></dd>
    <dt class="font-semibold text-slate-500">Attempts</dt><dd><?= (int) $job['attempts'] ?> of <?= (int) $job['max_attempts'] ?></dd>
    <dt class="font-semibold text-slate-500">Next attempt</dt><dd><?= esc($job['next_attempt_at'] ?? '—') ?></dd>
    <dt class="font-semibold text-slate-500">Related record</dt><dd><?php if ($job['record_path']): ?><a class="text-brand-primary hover:underline" href="<?= base_url($job['record_path']) ?>"><?= esc($job['record_label']) ?></a><?php else: ?>—<?php endif; ?></dd>
    <dt class="font-semibold text-slate-500">Problem</dt><dd class="break-words"><?= esc($job['error_summary'] ?? 'None recorded') ?></dd>
  </dl>
  <p class="text-[11px] text-slate-500">Error text is shortened and scrubbed of anything that looks like a password, token or server path. Message bodies and API payloads are never shown here.</p>

  <?php if ($canManage): ?>
    <div class="flex flex-wrap gap-3">
      <?php if (B::canRetry($job)): ?>
        <form method="post" action="<?= base_url('admin/system/jobs/' . $job['queue'] . '/' . (int) $job['id'] . '/retry') ?>" onsubmit="return confirm('Re-queue this job? It will run again on the next scheduler pass.');">
          <?= csrf_field() ?><button class="px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">Retry this job</button>
        </form>
      <?php endif; ?>
      <?php if (B::canCancel($job)): ?>
        <form method="post" action="<?= base_url('admin/system/jobs/' . $job['queue'] . '/' . (int) $job['id'] . '/cancel') ?>" onsubmit="return confirm('Cancel this waiting job? The post will not be published by this job.');">
          <?= csrf_field() ?><button class="px-4 py-2 rounded-xl border border-red-200 bg-white text-red-700 text-xs font-bold">Cancel this job</button>
        </form>
      <?php endif; ?>
      <?php if (!B::canRetry($job) && !B::canCancel($job)): ?><p class="text-xs text-slate-500">No actions available in the current state.</p><?php endif; ?>
    </div>
  <?php else: ?>
    <p class="text-xs text-slate-500">You have view-only access. Ask a Super Admin for “Retry/cancel background jobs” to change jobs.</p>
  <?php endif; ?>

  <?php if ($attempts): ?>
    <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
      <h2 class="px-5 pt-4 text-sm font-extrabold text-slate-900">Publish attempts</h2>
      <table class="min-w-full text-xs mt-2">
        <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="text-left px-4 py-2">#</th><th class="text-left px-4 py-2">Started</th><th class="text-left px-4 py-2">Result</th><th class="text-left px-4 py-2">HTTP</th><th class="text-left px-4 py-2">Message</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($attempts as $a): ?>
          <tr><td class="px-4 py-2"><?= (int) $a['attempt_number'] ?></td><td class="px-4 py-2"><?= esc($a['started_at']) ?></td><td class="px-4 py-2"><?= esc($a['status']) ?></td><td class="px-4 py-2"><?= esc($a['http_status'] ?? '—') ?></td><td class="px-4 py-2 break-words"><?= esc($a['error_message'] ?? '—') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
