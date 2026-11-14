<?php
/** @var string $recipient @var array $categories @var array $recent */
?>
<div class="space-y-6 max-w-3xl">
  <div>
    <a href="<?= base_url('admin/it-leads') ?>" class="text-xs text-slate-500 hover:text-slate-700">&larr; CRM leads</a>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">CRM Settings</h1>
  </div>

  <form method="POST" action="<?= base_url('admin/it-leads/settings') ?>" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
    <?= csrf_field() ?>
    <label for="crm_notification_email" class="block text-sm font-bold text-slate-900">Notification recipient</label>
    <p class="text-xs text-slate-500">New enquiries and WorkSphere events are emailed here. SMTP credentials are configured on the server, not here.</p>
    <input id="crm_notification_email" type="email" name="crm_notification_email" required maxlength="190" value="<?= esc($recipient) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
    <button class="px-3.5 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold">Save recipient</button>
  </form>

  <form method="POST" action="<?= base_url('admin/it-leads/settings/test-notification') ?>" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between gap-4">
    <?= csrf_field() ?>
    <p class="text-xs text-slate-600">Queue a clearly labelled test email to <strong><?= esc($recipient) ?></strong> to verify delivery.</p>
    <button class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold hover:bg-slate-50">Send test notification</button>
  </form>

  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
    <table class="min-w-full text-xs">
      <caption class="text-left px-4 pt-4 text-sm font-bold text-slate-900">Recent CRM emails</caption>
      <thead class="text-slate-500 uppercase tracking-wider text-[10px]"><tr><th class="text-left px-4 py-2">Queued</th><th class="text-left px-4 py-2">Template</th><th class="text-left px-4 py-2">Status</th><th class="text-left px-4 py-2">Attempts</th><th class="text-left px-4 py-2">Last error</th></tr></thead>
      <tbody class="divide-y divide-slate-100">
        <?php if (!$recent): ?><tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No CRM emails queued yet.</td></tr><?php endif; ?>
        <?php foreach ($recent as $m): ?>
          <tr><td class="px-4 py-2"><?= esc($m['created_at']) ?></td><td class="px-4 py-2 font-mono"><?= esc($m['template_code']) ?></td><td class="px-4 py-2 font-bold"><?= esc($m['status']) ?></td><td class="px-4 py-2"><?= (int) $m['attempts'] ?></td><td class="px-4 py-2 text-rose-700"><?= esc(mb_substr((string) $m['last_error'], 0, 80)) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
    <h2 class="text-sm font-bold text-slate-900 mb-2">Enquiry categories</h2>
    <p class="text-xs text-slate-500 mb-2">Seeded from the built-in list (table <code>crm_categories</code>). Editing from the UI is not available yet.</p>
    <p class="text-xs text-slate-700"><?= esc(implode(' · ', array_map(static fn ($c) => $c['label'] . ($c['active'] ? '' : ' (inactive)'), $categories))) ?></p>
  </div>
</div>
