<?php
include __DIR__ . '/_status.php';
$rows = $result['rows'];
$pages = (int) max(1, ceil($result['total'] / $result['per_page']));
$qs = static fn(array $o) => http_build_query(array_filter(array_merge(['queue' => $queue, 'status' => $status], $o), static fn($v) => $v !== null && $v !== ''));
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Background Jobs</h1>
    <p class="text-xs text-slate-500 mt-1">Social publishing jobs and outgoing emails, in one place.</p>
  </div>
  <?= th_system_tabs('jobs') ?>

  <?php if (session()->getFlashdata('success')): ?><div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold px-4 py-3" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
  <?php if (session()->getFlashdata('error')): ?><div class="rounded-xl border border-red-200 bg-red-50 text-red-800 text-xs font-semibold px-4 py-3" role="alert"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

  <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
    <?php foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed', 'stuck' => 'Stuck'] as $k => $label): ?>
      <a href="<?= $k === 'stuck' ? '#' : base_url('admin/system/jobs?' . $qs(['status' => $k, 'page' => null])) ?>" class="rounded-xl border border-slate-200 bg-white p-4 hover:border-brand-primary">
        <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide"><?= esc($label) ?></span>
        <span class="block text-2xl font-extrabold <?= ($k === 'failed' || $k === 'stuck') && $jobCounts[$k] > 0 ? 'text-red-700' : 'text-slate-900' ?>"><?= number_format($jobCounts[$k]) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="text-[11px] text-slate-500">Last social job completed: <?= esc($jobCounts['last_success'] ?? 'never') ?></p>

  <form method="get" class="flex flex-wrap items-end gap-3">
    <label class="text-[11px] font-bold text-slate-600">Queue
      <select name="queue" class="block mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
        <option value="">All</option><option value="social" <?= $queue === 'social' ? 'selected' : '' ?>>Social publishing</option><option value="email" <?= $queue === 'email' ? 'selected' : '' ?>>Email</option>
      </select></label>
    <label class="text-[11px] font-bold text-slate-600">Status
      <select name="status" class="block mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs">
        <option value="">All</option>
        <?php foreach (\App\Services\BackgroundJobService::STATUSES as $s): ?><option value="<?= esc($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc(ucfirst($s)) ?></option><?php endforeach; ?>
      </select></label>
    <button class="px-3.5 py-2 rounded-lg bg-brand-primary text-white text-xs font-bold">Filter</button>
    <?php if ($queue || $status): ?><a href="<?= base_url('admin/system/jobs') ?>" class="text-xs font-semibold text-slate-500 hover:underline py-2">Clear</a><?php endif; ?>
  </form>

  <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wide text-[10px]">
        <tr><th class="text-left px-4 py-3">Job</th><th class="text-left px-4 py-3">Type</th><th class="text-left px-4 py-3">Status</th><th class="text-left px-4 py-3">Created</th><th class="text-left px-4 py-3">Completed</th><th class="text-left px-4 py-3">Duration</th><th class="text-left px-4 py-3">Tries</th><th class="text-left px-4 py-3">Related record</th><th class="text-left px-4 py-3">Problem</th></tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$rows): ?><tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">No jobs match these filters.</td></tr><?php endif; ?>
        <?php foreach ($rows as $j): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-bold"><a class="text-brand-primary hover:underline" href="<?= base_url('admin/system/jobs/' . $j['queue'] . '/' . (int) $j['id']) ?>"><?= esc($j['queue']) ?> #<?= (int) $j['id'] ?></a></td>
            <td class="px-4 py-3"><?= esc($j['type']) ?></td>
            <td class="px-4 py-3"><?= th_status_badge($j['status']) ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= esc($j['created_at']) ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= esc($j['completed_at'] ?? '—') ?></td>
            <td class="px-4 py-3"><?= esc($j['duration_label']) ?></td>
            <td class="px-4 py-3"><?= (int) $j['attempts'] ?>/<?= (int) $j['max_attempts'] ?></td>
            <td class="px-4 py-3"><?php if ($j['record_path']): ?><a class="text-brand-primary hover:underline" href="<?= base_url($j['record_path']) ?>"><?= esc($j['record_label']) ?></a><?php else: ?>—<?php endif; ?></td>
            <td class="px-4 py-3 max-w-xs truncate" title="<?= esc($j['error_summary'] ?? '') ?>"><?= esc($j['error_summary'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between text-xs text-slate-600">
    <span><?= number_format($result['total']) ?> job(s) · page <?= (int) $result['page'] ?> of <?= $pages ?></span>
    <span class="flex gap-2">
      <?php if ($result['page'] > 1): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/system/jobs?' . $qs(['page' => $result['page'] - 1])) ?>">← Newer</a><?php endif; ?>
      <?php if ($result['page'] < $pages): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/system/jobs?' . $qs(['page' => $result['page'] + 1])) ?>">Older →</a><?php endif; ?>
    </span>
  </div>
</div>
