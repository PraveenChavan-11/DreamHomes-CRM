<?php include __DIR__ . '/_ui.php'; use App\Services\Agent\AgentScopes; use App\Services\Agent\AgentTokenService;
$granted = AgentScopes::parse($agent['scopes']); ?>
<div class="space-y-6">
  <a href="<?= base_url('admin/agents') ?>" class="text-xs font-bold text-brand-primary hover:underline">← Back to External AI Agents</a>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900"><?= esc($agent['name']) ?></h1>
      <p class="text-xs text-slate-500"><?= esc(AgentScopes::PROVIDERS[$agent['provider']] ?? $agent['provider']) ?> · Policy: <strong>Draft only</strong> · <?= esc($agent['description'] ?? '') ?></p>
    </div>
    <div class="flex items-center gap-2"><?= $agent['status'] === 'active' ? th_chip('Enabled', 'green') : th_chip('Disabled', 'slate') ?> <?= th_token_chip($stats['token_state']) ?></div>
  </div>
  <?= th_flash() ?>
  <?php foreach ($stats['warnings'] as $w): ?><div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-xs font-semibold px-4 py-3" role="alert">⚠ <?= esc($w) ?></div><?php endforeach; ?>

  <div class="grid gap-4 md:grid-cols-4">
    <?php foreach ([['Drafts created', $stats['drafts']], ['Requests (30 days)', $stats['requests']], ['Failed / refused (30 days)', $stats['failed']], ['Last access', $agent['last_access_at'] ?? 'never']] as [$l, $v]): ?>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><span class="block text-[11px] font-bold text-slate-500 uppercase"><?= esc($l) ?></span><span class="block mt-1 text-lg font-extrabold text-slate-900"><?= esc(is_int($v) ? number_format($v) : $v) ?></span></div>
    <?php endforeach; ?>
  </div>

  <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="credential">
    <h2 id="credential" class="text-sm font-extrabold text-slate-900">Credential</h2>
    <p class="text-xs text-slate-600 mt-1">
      <?php if ($stats['token']): ?>Token <code>thk_<?= esc($stats['token']['token_prefix']) ?>_…</code> · created <?= esc($stats['token']['created_at']) ?> · <strong>expires <?= esc($stats['token']['expires_at']) ?></strong> · last used <?= esc($stats['token']['last_used_at'] ?? 'never') ?><?= $stats['token']['last_used_ip'] ? ' from ' . esc($stats['token']['last_used_ip']) : '' ?>.
      <?php else: ?>No active credential.<?php endif; ?>
      The full token is never shown again after it is issued.</p>
    <?php if ($canManage): ?>
      <div class="mt-4 flex flex-wrap items-end gap-6">
        <form method="post" action="<?= base_url('admin/agents/' . (int) $agent['id'] . '/rotate') ?>" class="flex flex-wrap items-end gap-3" onsubmit="return confirm('Issue a new credential? The current one stops working immediately.');">
          <?= csrf_field() ?>
          <label class="text-[11px] font-bold text-slate-600">Your password<input type="password" name="confirm_password" required autocomplete="current-password" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2 text-xs font-normal"></label>
          <label class="text-[11px] font-bold text-slate-600">Lifetime (days)<input type="number" name="ttl_days" value="<?= AgentTokenService::DEFAULT_TTL_DAYS ?>" min="1" max="<?= AgentTokenService::MAX_TTL_DAYS ?>" class="mt-1 block w-24 rounded-lg border border-slate-300 px-3 py-2 text-xs font-normal"></label>
          <button class="px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold"><?= $stats['token'] ? 'Rotate credential' : 'Issue new credential' ?></button>
        </form>
        <form method="post" action="<?= base_url('admin/agents/' . (int) $agent['id'] . '/revoke') ?>" onsubmit="return confirm('Revoke every credential for this agent right now?');">
          <?= csrf_field() ?><button class="px-4 py-2 rounded-xl border border-red-300 bg-white text-red-700 text-xs font-bold">Revoke immediately</button>
        </form>
        <form method="post" action="<?= base_url('admin/agents/' . (int) $agent['id'] . '/toggle') ?>">
          <?= csrf_field() ?><button class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold"><?= $agent['status'] === 'active' ? 'Disable agent' : 'Enable agent' ?></button>
        </form>
      </div>
    <?php endif; ?>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-labelledby="matrix">
    <h2 id="matrix" class="text-sm font-extrabold text-slate-900">Permission matrix</h2>
    <form method="post" action="<?= base_url('admin/agents/' . (int) $agent['id'] . '/update') ?>" class="mt-3">
      <?= csrf_field() ?>
      <table class="min-w-full text-xs">
        <thead class="text-slate-500 uppercase text-[10px]"><tr><th class="text-left py-2 w-10">Allow</th><th class="text-left py-2">Permission</th><th class="text-left py-2">Endpoint</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
          <?php foreach (AgentScopes::CATALOGUE as $scope => [$label, $desc, $endpoint]): ?>
            <tr><td class="py-2"><input type="checkbox" name="scopes[]" value="<?= esc($scope) ?>" <?= in_array($scope, $granted, true) ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?> aria-label="<?= esc($label) ?>"></td>
              <td class="py-2"><strong><?= esc($label) ?></strong><div class="text-slate-500"><?= esc($desc) ?></div></td><td class="py-2 font-mono text-[11px] text-slate-600"><?= esc($endpoint) ?></td></tr>
          <?php endforeach; ?>
          <tr class="bg-slate-50"><td class="py-2 text-center">🚫</td><td class="py-2" colspan="2"><strong>Never available to any agent:</strong> publishing, scheduling, approving, editing after creation, choosing a platform account, platform credentials, account configuration, user or admin management, database access.</td></tr>
        </tbody>
      </table>
      <?php if ($canManage): ?>
        <div class="mt-4 grid sm:grid-cols-3 gap-4">
          <label class="text-[11px] font-bold text-slate-600">Requests per minute<input type="number" name="rate_limit_per_minute" min="5" max="120" value="<?= (int) $agent['rate_limit_per_minute'] ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-normal"></label>
          <label class="text-[11px] font-bold text-slate-600">Drafts per day<input type="number" name="daily_draft_limit" min="1" max="100" value="<?= (int) $agent['daily_draft_limit'] ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-normal"></label>
          <label class="text-[11px] font-bold text-slate-600">Allowed IPs (optional)<input name="allowed_ips" value="<?= esc($agent['allowed_ips'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-normal"></label>
        </div>
        <button class="mt-4 px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">Save permissions &amp; limits</button>
      <?php endif; ?>
    </form>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white overflow-x-auto" aria-labelledby="drafts">
    <h2 id="drafts" class="px-5 pt-4 text-sm font-extrabold text-slate-900">Recent drafts by this agent</h2>
    <table class="min-w-full text-xs mt-2"><tbody class="divide-y divide-slate-100">
      <?php if (!$drafts): ?><tr><td class="px-5 py-6 text-slate-500">No drafts yet.</td></tr><?php endif; ?>
      <?php foreach ($drafts as $d): ?><tr><td class="px-5 py-2"><a class="text-brand-primary font-bold hover:underline" href="<?= base_url('admin/agents/approvals/' . (int) $d['id']) ?>">#<?= (int) $d['id'] ?> <?= esc($d['title']) ?></a></td><td class="px-5 py-2"><?= th_post_chip($d) ?></td><td class="px-5 py-2 text-slate-500"><?= esc($d['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table>
  </section>

  <section class="rounded-2xl border border-slate-200 bg-white overflow-x-auto" aria-labelledby="activity">
    <h2 id="activity" class="px-5 pt-4 text-sm font-extrabold text-slate-900">Activity log <span class="font-normal text-slate-500">(latest 60 · no tokens or request bodies are ever recorded)</span></h2>
    <table class="min-w-full text-xs mt-2">
      <thead class="bg-slate-50 text-slate-500 uppercase text-[10px]"><tr><th class="text-left px-4 py-2">Time (UTC)</th><th class="text-left px-4 py-2">Event</th><th class="text-left px-4 py-2">Request</th><th class="text-left px-4 py-2">Status</th><th class="text-left px-4 py-2">IP</th><th class="text-left px-4 py-2">Detail</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$activity): ?><tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">No activity yet.</td></tr><?php endif; ?>
        <?php foreach ($activity as $a): ?>
          <tr><td class="px-4 py-2 whitespace-nowrap"><?= esc($a['created_at']) ?></td><td class="px-4 py-2 font-semibold"><?= esc($a['event']) ?></td><td class="px-4 py-2 font-mono text-[11px]"><?= esc(trim(($a['method'] ?? '') . ' ' . ($a['path'] ?? ''))) ?></td><td class="px-4 py-2"><?= esc($a['status_code'] ?? '') ?></td><td class="px-4 py-2"><?= esc($a['ip'] ?? '') ?></td><td class="px-4 py-2 break-words"><?= esc($a['detail'] ?? '') ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
