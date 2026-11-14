<?php include __DIR__ . '/_ui.php'; use App\Services\Agent\AgentScopes; ?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">External AI Agents</h1>
      <p class="text-xs text-slate-500 mt-1 max-w-3xl">Register ChatGPT, Claude or other automation so they can <strong>prepare draft posts</strong> through a secured API. Agents can never publish or schedule: every draft is reviewed and approved by a person, and only then can an administrator schedule or publish it. Registering an agent here does not connect any AI provider by itself — it only issues a credential you configure in that provider's own integration.</p>
    </div>
    <div class="flex gap-2 shrink-0">
      <a href="<?= base_url('admin/agents/approvals') ?>" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50">Draft approvals<?= $pending ? ' (' . (int) $pending . ')' : '' ?></a>
      <?php if ($canManage): ?><a href="<?= base_url('admin/agents/create') ?>" class="px-3.5 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">Register agent</a><?php endif; ?>
    </div>
  </div>
  <?= th_flash() ?>

  <div class="rounded-2xl border border-slate-200 bg-white overflow-x-auto">
    <table class="min-w-full text-xs">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wide text-[10px]"><tr>
        <th class="text-left px-4 py-3">Agent</th><th class="text-left px-4 py-3">Status</th><th class="text-left px-4 py-3">Authentication</th><th class="text-left px-4 py-3">Token expiry</th>
        <th class="text-left px-4 py-3">Last activity</th><th class="text-right px-4 py-3">Drafts</th><th class="text-right px-4 py-3">Requests (30d)</th><th class="text-right px-4 py-3">Failed (30d)</th>
      </tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$agents): ?><tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No agents registered yet.</td></tr><?php endif; ?>
        <?php foreach ($agents as $a): $s = $a['stats']; ?>
          <tr class="hover:bg-slate-50 align-top">
            <td class="px-4 py-3"><a class="font-bold text-brand-primary hover:underline" href="<?= base_url('admin/agents/' . (int) $a['id']) ?>"><?= esc($a['name']) ?></a>
              <div class="text-[11px] text-slate-500"><?= esc(AgentScopes::PROVIDERS[$a['provider']] ?? $a['provider']) ?> · Draft-only</div>
              <?php foreach ($s['warnings'] as $w): ?><div class="mt-1 text-[11px] font-semibold text-amber-700">⚠ <?= esc($w) ?></div><?php endforeach; ?></td>
            <td class="px-4 py-3"><?= $a['status'] === 'active' ? th_chip('Enabled', 'green') : th_chip('Disabled', 'slate') ?></td>
            <td class="px-4 py-3"><?= th_token_chip($s['token_state']) ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= esc($s['token']['expires_at'] ?? '—') ?></td>
            <td class="px-4 py-3 whitespace-nowrap"><?= esc($a['last_access_at'] ?? 'never') ?></td>
            <td class="px-4 py-3 text-right"><?= (int) $s['drafts'] ?></td>
            <td class="px-4 py-3 text-right"><?= number_format($s['requests']) ?></td>
            <td class="px-4 py-3 text-right <?= $s['failed'] ? 'text-red-700 font-bold' : '' ?>"><?= (int) $s['failed'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
