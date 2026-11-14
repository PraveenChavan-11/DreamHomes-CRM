<?php
/** @var array $clients @var array $events @var array $health @var bool $canManage @var ?array $newSecret @var string $endpoint */
$btn = 'px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold';
$pill = static fn (string $r) => ['accepted' => 'bg-emerald-100 text-emerald-800', 'duplicate' => 'bg-sky-100 text-sky-800', 'failed' => 'bg-rose-100 text-rose-800'][$r] ?? 'bg-slate-100 text-slate-700';
?>
<div class="space-y-6">
  <div>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Integrations</h1>
    <p class="text-xs text-slate-500 mt-1">Signed server-to-server clients, e.g. the WorkSphere event feed. Endpoint: <code class="bg-slate-100 px-1.5 py-0.5 rounded"><?= esc($endpoint) ?></code></p>
  </div>

  <?php if (!$health['encryption_ok']): ?>
    <div role="alert" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-semibold">
      <code>encryption.key</code> is not configured on this server. The receiver rejects every call (fail closed) and secrets cannot be created until it is set. See docs/WORKSPHERE_INTEGRATION_RECEIVER.md.
    </div>
  <?php endif; ?>

  <?php if ($newSecret): ?>
    <div role="alert" class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-xs text-amber-900 space-y-2">
      <p class="font-bold">Copy this secret now. It is shown once and cannot be retrieved later.</p>
      <p>Key id: <code class="bg-white px-1.5 py-0.5 rounded"><?= esc($newSecret['key_id']) ?></code></p>
      <p>Secret: <code class="bg-white px-1.5 py-0.5 rounded break-all select-all"><?= esc($newSecret['secret']) ?></code></p>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php foreach ([['Last event received', $health['last_received'] ? date('d M, H:i', strtotime($health['last_received'])) : 'Never'], ['Accepted (24 h)', $health['accepted_24h']], ['Failures (24 h)', $health['failures_24h']], ['Auth failures (24 h)', $health['auth_fail_24h']]] as [$l, $v]): ?>
      <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm"><p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"><?= esc($l) ?></p><p class="text-xl font-extrabold text-slate-900 mt-1"><?= esc((string) $v) ?></p></div>
    <?php endforeach; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
    <table class="min-w-full text-xs">
      <caption class="text-left px-4 pt-4 text-sm font-bold text-slate-900">Clients</caption>
      <thead class="text-slate-500 uppercase tracking-wider text-[10px]"><tr><th class="text-left px-4 py-3">Key id</th><th class="text-left px-4 py-3">Label</th><th class="text-left px-4 py-3">State</th><th class="text-left px-4 py-3">Last used</th><th class="text-left px-4 py-3">Rotated</th><th class="px-4 py-3"></th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$clients): ?><tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No integration clients yet.</td></tr><?php endif; ?>
        <?php foreach ($clients as $c): ?>
          <tr>
            <td class="px-4 py-3 font-mono"><?= esc($c['key_id']) ?></td>
            <td class="px-4 py-3"><?= esc($c['label']) ?></td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full font-bold <?= $c['is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td class="px-4 py-3 text-slate-600"><?= esc($c['last_used_at'] ?: 'Never') ?></td>
            <td class="px-4 py-3 text-slate-600"><?= esc($c['rotated_at'] ?: '—') ?><?= !empty($c['prev_expires_at']) && strtotime($c['prev_expires_at']) > time() ? ' (old secret valid until ' . esc($c['prev_expires_at']) . ')' : '' ?></td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <?php if ($canManage): ?>
                <form class="inline" method="POST" action="<?= base_url('admin/integrations/' . (int) $c['id'] . '/rotate') ?>" onsubmit="return confirm('Rotate the secret? The previous secret keeps working for 24 hours.');"><?= csrf_field() ?><button class="<?= $btn ?>">Rotate secret</button></form>
                <form class="inline" method="POST" action="<?= base_url('admin/integrations/' . (int) $c['id'] . '/toggle') ?>" onsubmit="return confirm('Change active state of this client?');"><?= csrf_field() ?><button class="<?= $btn ?>"><?= $c['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($canManage): ?>
    <form method="POST" action="<?= base_url('admin/integrations/create') ?>" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap gap-3 items-end">
      <?= csrf_field() ?>
      <div><label for="key_id" class="block text-[11px] font-semibold text-slate-500 mb-1">Key id (a-z, 0-9, - _)</label><input id="key_id" name="key_id" required pattern="[a-z0-9][a-z0-9_\-]{2,62}" placeholder="worksphere-prod" class="px-3 py-2 rounded-xl border border-slate-200 text-xs"></div>
      <div><label for="label" class="block text-[11px] font-semibold text-slate-500 mb-1">Label</label><input id="label" name="label" maxlength="120" placeholder="WorkSphere production" class="px-3 py-2 rounded-xl border border-slate-200 text-xs"></div>
      <button class="px-3.5 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">Create client</button>
    </form>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
    <table class="min-w-full text-xs">
      <caption class="text-left px-4 pt-4 text-sm font-bold text-slate-900">Recent events (latest 50)</caption>
      <thead class="text-slate-500 uppercase tracking-wider text-[10px]"><tr><th class="text-left px-4 py-3">Received</th><th class="text-left px-4 py-3">Client</th><th class="text-left px-4 py-3">Type</th><th class="text-left px-4 py-3">Result</th><th class="text-left px-4 py-3">Error code</th><th class="text-left px-4 py-3">Lead</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$events): ?><tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No events received yet.</td></tr><?php endif; ?>
        <?php foreach ($events as $e): ?>
          <tr>
            <td class="px-4 py-2 text-slate-600"><?= esc($e['received_at']) ?></td>
            <td class="px-4 py-2 font-mono"><?= esc($e['key_id']) ?></td>
            <td class="px-4 py-2"><?= esc($e['event_type']) ?></td>
            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full font-bold <?= $pill($e['result']) ?>"><?= esc(ucfirst($e['result'])) ?></span></td>
            <td class="px-4 py-2 font-mono text-rose-700"><?= esc($e['error_code'] ?: '') ?></td>
            <td class="px-4 py-2"><?php if ($e['lead_id']): ?><a class="underline" href="<?= base_url('admin/it-leads/' . (int) $e['lead_id']) ?>">#<?= (int) $e['lead_id'] ?></a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
