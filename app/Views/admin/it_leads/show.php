<?php
use App\Services\ItLeadService as L;
/** @var array $lead @var array $acts @var array $dups @var array $staff @var array $emails @var array $events */
$id  = (int) $lead['id'];
$in  = 'w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary';
$btn = 'px-3.5 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold hover:opacity-90 transition';
$row = static fn (string $k, $v) => '<div class="flex justify-between gap-4 py-1.5 border-b border-slate-100 last:border-0"><dt class="text-slate-500">' . esc($k) . '</dt><dd class="font-medium text-slate-800 text-right break-words">' . ($v !== null && $v !== '' ? esc((string) $v) : '—') . '</dd></div>';
$isWs = ($lead['source_system'] ?? '') === 'worksphere';
$typeLabel = ['integration' => 'WorkSphere event', 'email' => 'Email', 'update' => 'Update'];
?>
<div class="space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <a href="<?= base_url('admin/it-leads') ?>" class="text-xs text-slate-500 hover:text-slate-700">&larr; All leads</a>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">#<?= $id ?> · <?= esc($lead['client_name']) ?></h1>
      <p class="text-xs text-slate-500"><?= esc(L::STATUSES[$lead['status']] ?? $lead['status']) ?> · <?= esc(str_replace('_', ' ', (string) $lead['source'])) ?> · received <?= esc(date('d M Y, H:i', strtotime($lead['created_at']))) ?></p>
    </div>
    <?php if ($canManage): ?>
      <div class="flex gap-2">
        <?php if ($lead['status'] !== 'spam'): ?>
        <form action="<?= base_url('admin/it-leads/' . $id . '/status') ?>" method="POST" onsubmit="return confirm('Mark this lead as spam?');"><?= csrf_field() ?><input type="hidden" name="status" value="spam">
          <button class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50">Mark as spam</button></form>
        <?php endif; ?>
        <form action="<?= base_url('admin/it-leads/' . $id . '/delete') ?>" method="POST" onsubmit="return confirm('Archive this lead? It is soft-deleted (hidden from the pipeline) and can be restored from the database.');">
          <?= csrf_field() ?>
          <button class="px-3.5 py-2 rounded-xl border border-rose-200 text-rose-600 text-xs font-bold hover:bg-rose-50">Archive lead</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($lead['duplicate_of'] || $dups || (int) $lead['enquiry_count'] > 1): ?>
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
      <p class="font-bold mb-1">Duplicate / related leads<?= (int) $lead['enquiry_count'] > 1 ? ' - this lead has ' . (int) $lead['enquiry_count'] . ' enquiries' : '' ?></p>
      <p class="mb-1">Same email or phone. Review before contacting; merge by keeping one lead and archiving the other (notes are not moved automatically).</p>
      <?php foreach ($dups as $d): ?><a class="underline mr-3" href="<?= base_url('admin/it-leads/' . (int) $d['id']) ?>">#<?= (int) $d['id'] ?> (<?= esc(L::STATUSES[$d['status']] ?? $d['status']) ?>)</a><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 mb-2">Enquiry</h2>
        <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line"><?= esc($lead['project_description']) ?></p>
      </div>

      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 mb-3">Timeline</h2>
        <?php if ($canManage): ?>
          <form action="<?= base_url('admin/it-leads/' . $id . '/note') ?>" method="POST" class="mb-4 flex gap-2">
            <?= csrf_field() ?>
            <label class="sr-only" for="note">Internal note</label>
            <textarea id="note" name="note" required maxlength="4000" rows="2" placeholder="Add an internal note…" class="<?= $in ?>"></textarea>
            <button class="<?= $btn ?> self-start">Add</button>
          </form>
        <?php endif; ?>
        <ol class="space-y-3">
          <?php foreach ($acts as $a): ?>
            <li class="text-xs border-l-2 <?= $a['type'] === 'note' ? 'border-brand-primary' : ($a['type'] === 'integration' ? 'border-teal-400' : 'border-slate-200') ?> pl-3">
              <p class="text-slate-800 whitespace-pre-line"><?= esc($a['body']) ?></p>
              <p class="text-[10px] text-slate-400 mt-0.5"><?= esc($typeLabel[$a['type']] ?? ucwords(str_replace('_', ' ', $a['type']))) ?> · <?= esc($a['author'] ?: 'System') ?> · <?= esc(date('d M Y, H:i', strtotime($a['created_at']))) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </div>

      <?php if ($emails): ?>
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <h2 class="text-sm font-bold text-slate-900 mb-2">Notification emails</h2>
        <ul class="text-xs space-y-1">
          <?php foreach ($emails as $m): ?><li><span class="font-mono"><?= esc($m['template_code']) ?></span> — <strong><?= esc($m['status']) ?></strong> (<?= (int) $m['attempts'] ?> attempt<?= (int) $m['attempts'] === 1 ? '' : 's' ?>)<?= $m['last_error'] ? ' <span class="text-rose-700">' . esc(mb_substr((string) $m['last_error'], 0, 100)) . '</span>' : '' ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>

    <div class="space-y-6">
      <?php if ($canManage): ?>
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
        <form action="<?= base_url('admin/it-leads/' . $id . '/status') ?>" method="POST" class="space-y-2"><?= csrf_field() ?>
          <label class="text-[11px] font-semibold text-slate-500" for="status">Status</label>
          <div class="flex gap-2"><select id="status" name="status" class="<?= $in ?>"><?php foreach (L::STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $lead['status'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
          <button class="<?= $btn ?>">Set</button></div>
          <input name="lost_reason" maxlength="255" value="<?= esc((string) $lead['lost_reason']) ?>" placeholder="Reason (required for Closed Lost)" aria-label="Lost reason" class="<?= $in ?>">
        </form>
        <form action="<?= base_url('admin/it-leads/' . $id . '/assign') ?>" method="POST" class="flex gap-2"><?= csrf_field() ?>
          <select name="assigned_to" aria-label="Assignee" class="<?= $in ?>"><option value="">Unassigned</option>
            <?php foreach ($staff as $u): $n = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['username']; ?><option value="<?= (int) $u['id'] ?>" <?= (int) $lead['assigned_to'] === (int) $u['id'] ? 'selected' : '' ?>><?= esc($n) ?></option><?php endforeach; ?></select>
          <button class="<?= $btn ?>">Assign</button></form>
        <form action="<?= base_url('admin/it-leads/' . $id . '/follow-up') ?>" method="POST" class="flex gap-2"><?= csrf_field() ?>
          <input type="date" name="next_follow_up_at" aria-label="Next follow-up" value="<?= esc($lead['next_follow_up_at'] ?? '') ?>" class="<?= $in ?>">
          <button class="<?= $btn ?>">Save</button></form>
        <form action="<?= base_url('admin/it-leads/' . $id . '/priority') ?>" method="POST" class="space-y-2"><?= csrf_field() ?>
          <select name="priority" aria-label="Priority" class="<?= $in ?>"><?php foreach (L::PRIORITIES as $k => $v): ?><option value="<?= $k ?>" <?= ($lead['priority'] ?? 'normal') === $k ? 'selected' : '' ?>><?= esc($v) ?> priority</option><?php endforeach; ?></select>
          <input name="estimated_value" inputmode="decimal" aria-label="Estimated value (INR)" placeholder="Estimated value (INR)" value="<?= esc((string) ($lead['estimated_value'] ?? '')) ?>" class="<?= $in ?>">
          <input name="tags" maxlength="200" aria-label="Tags (comma separated)" placeholder="Tags, comma separated" value="<?= esc((string) ($lead['tags'] ?? '')) ?>" class="<?= $in ?>">
          <button class="<?= $btn ?>">Save details</button></form>
      </div>
      <?php endif; ?>

      <dl class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm text-xs">
        <h2 class="text-sm font-bold text-slate-900 mb-2">Contact</h2>
        <?= $row('Status', L::STATUSES[$lead['status']] ?? $lead['status']) ?>
        <?= $row('Priority', L::PRIORITIES[$lead['priority']] ?? $lead['priority']) ?>
        <?= $row('Estimated value', $lead['estimated_value'] !== null ? '₹' . number_format((float) $lead['estimated_value']) : '') ?>
        <?= $row('Tags', $lead['tags']) ?>
        <?= $row('Lost reason', $lead['lost_reason']) ?>
        <?= $row('Company', $lead['company']) ?>
        <?= $row('Email', $lead['email'] . ($lead['verified_email_at'] ? ' (verified)' : '')) ?>
        <?= $row('Phone', $lead['phone']) ?>
        <?= $row('Location', $lead['location']) ?>
        <?= $row('Preferred contact', $lead['preferred_contact']) ?>
        <?= $row('Budget', $lead['budget_range']) ?>
        <?= $row('Timeline', $lead['timeline']) ?>
      </dl>
      <?php if ($isWs || $lead['demo_status']): ?>
      <dl class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm text-xs">
        <h2 class="text-sm font-bold text-slate-900 mb-2">WorkSphere demo</h2>
        <?= $row('Demo status', $lead['demo_status']) ?>
        <?= $row('Demo started', $lead['demo_started_at']) ?>
        <?= $row('Demo expires', $lead['demo_expires_at']) ?>
        <?= $row('Email verified', $lead['verified_email_at']) ?>
        <?= $row('Correlation ref', $lead['external_ref']) ?>
      </dl>
      <?php endif; ?>
      <dl class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm text-xs">
        <h2 class="text-sm font-bold text-slate-900 mb-2">Source &amp; consent</h2>
        <?= $row('Source system', $lead['source_system']) ?>
        <?= $row('Source', $lead['source']) ?>
        <?= $row('Product', $lead['product_slug']) ?>
        <?= $row('Source page', $lead['source_page']) ?>
        <?= $row('UTM source', $lead['utm_source']) ?>
        <?= $row('UTM medium', $lead['utm_medium']) ?>
        <?= $row('UTM campaign', $lead['utm_campaign']) ?>
        <?= $row('Consent given', $lead['consent_at']) ?>
      </dl>
      <?php if ($events): ?>
      <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm text-xs">
        <h2 class="text-sm font-bold text-slate-900 mb-2">Integration events</h2>
        <ul class="space-y-1"><?php foreach ($events as $e): ?><li><?= esc($e['event_type']) ?> — <?= esc($e['result']) ?> <span class="text-slate-400"><?= esc($e['received_at']) ?></span></li><?php endforeach; ?></ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
