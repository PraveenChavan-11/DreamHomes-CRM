<?php include __DIR__ . '/_ui.php'; ?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">AI Draft Approvals</h1>
    <p class="text-xs text-slate-500 mt-1 max-w-3xl">Posts created by external AI agents wait here. Nothing an agent writes can be published until a person approves it — the publishing engine itself refuses unapproved agent content. Approving does <strong>not</strong> publish: after approval, schedule or publish it from the normal Social Media screens.</p>
  </div>
  <nav class="flex flex-wrap gap-2" aria-label="Filter">
    <?php foreach (['pending' => 'Needs review', 'approved' => 'Approved', 'closed' => 'Rejected / changes requested', 'all' => 'All'] as $k => $l): ?>
      <a href="<?= base_url('admin/agents/approvals?filter=' . $k) ?>" class="px-3.5 py-2 rounded-lg text-xs font-bold <?= $filter === $k ? 'bg-brand-primary text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>" <?= $filter === $k ? 'aria-current="page"' : '' ?>><?= esc($l) ?></a>
    <?php endforeach; ?>
  </nav>
  <?= th_flash() ?>
  <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="text-left px-4 py-3">Draft</th><th class="text-left px-4 py-3">Created by</th><th class="text-left px-4 py-3">Platform</th><th class="text-left px-4 py-3">Review</th><th class="text-left px-4 py-3">Created</th><th class="px-4 py-3"></th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$rows): ?><tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Nothing here.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-bold">#<?= (int) $r['id'] ?> <?= esc($r['title']) ?></td>
            <td class="px-4 py-3"><?= esc($r['agent_name'] ?? 'Unknown agent') ?> <span class="text-slate-500">(AI agent)</span></td>
            <td class="px-4 py-3"><?= esc(ucfirst($r['primary_platform'])) ?></td>
            <td class="px-4 py-3"><?= th_post_chip($r) ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= esc($r['created_at']) ?></td>
            <td class="px-4 py-3"><a class="px-3 py-1.5 rounded-lg bg-brand-primary text-white font-bold" href="<?= base_url('admin/agents/approvals/' . (int) $r['id']) ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
