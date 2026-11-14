<?php include __DIR__ . '/_ui.php';
$locked = in_array($post['status'], ['published', 'processing', 'cancelled'], true);
$decidable = in_array($post['status'], ['draft', 'pending_approval'], true) && empty($post['review_decision']);
$attached = array_map('intval', array_column($post['media'], 'media_library_id')); ?>
<div class="space-y-6 max-w-5xl">
  <a href="<?= base_url('admin/agents/approvals') ?>" class="text-xs font-bold text-brand-primary hover:underline">← Back to AI Draft Approvals</a>
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900">Draft #<?= (int) $post['id'] ?> — <?= esc($post['title']) ?></h1>
      <p class="text-xs text-slate-500">Created by <strong><?= esc($post['agent_name'] ?? 'Unknown agent') ?></strong> (external AI agent) on <?= esc($post['created_at']) ?> · platform: <?= esc($post['primary_platform']) ?><?= $post['proposed_at'] ? ' · agent suggests ' . esc($post['proposed_at']) . ' UTC (suggestion only)' : '' ?></p>
    </div>
    <?= th_post_chip($post) ?>
  </div>
  <?= th_flash() ?>
  <?php if (!empty($post['review_note'])): ?><div class="rounded-xl border border-slate-200 bg-slate-50 text-xs px-4 py-3"><strong>Reviewer note:</strong> <?= esc($post['review_note']) ?></div><?php endif; ?>
  <?php if (($post['review_decision'] ?? '') === 'approved'): ?>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900 text-xs px-4 py-3">Approved <?= esc($post['approved_at'] ?? '') ?>. It has <strong>not</strong> been published. <a class="font-bold underline" href="<?= base_url('admin/social/posts/' . (int) $post['id']) ?>">Open in Social Media</a> to schedule or publish it. Editing the content below returns it to review.</div>
  <?php endif; ?>

  <?php if (!$locked): ?>
  <form method="post" action="<?= base_url('admin/agents/approvals/' . (int) $post['id'] . '/edit') ?>" class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4">
    <?= csrf_field() ?>
    <h2 class="text-sm font-extrabold text-slate-900">Content <span class="font-normal text-slate-500">— you can edit before deciding</span></h2>
    <label class="block text-xs font-bold text-slate-700">Internal title<input name="title" value="<?= esc($post['title']) ?>" maxlength="191" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
    <label class="block text-xs font-bold text-slate-700">Caption<textarea name="caption" rows="8" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"><?= esc($post['base_content']) ?></textarea></label>
    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block text-xs font-bold text-slate-700">Hashtags<input name="hashtags" value="<?= esc($post['base_hashtags'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
      <label class="block text-xs font-bold text-slate-700">Call to action<input name="cta" value="<?= esc($post['base_cta'] ?? '') ?>" maxlength="120" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
    </div>
    <label class="block text-xs font-bold text-slate-700">Destination URL<input name="destination_url" value="<?= esc($post['base_destination_url'] ?? '') ?>" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
    <fieldset>
      <legend class="text-xs font-bold text-slate-700">Media <span class="font-normal text-slate-500">(approved library items only)</span></legend>
      <div class="mt-2 grid sm:grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($library as $m): ?>
          <label class="flex items-center gap-2 text-xs rounded-lg border border-slate-200 px-2 py-1.5"><input type="checkbox" name="media_ids[]" value="<?= (int) $m['id'] ?>" <?= in_array((int) $m['id'], $attached, true) ? 'checked' : '' ?>> #<?= (int) $m['id'] ?> <?= esc($m['title']) ?> <span class="text-slate-400">(<?= esc($m['media_type']) ?>)</span></label>
        <?php endforeach; ?>
        <?php if (!$library): ?><p class="text-xs text-slate-500">No approved media in the library.</p><?php endif; ?>
      </div>
    </fieldset>
    <button class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-800 text-xs font-bold">Save edits</button>
  </form>

  <?php if ($post['platforms']): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-5"><h2 class="text-sm font-extrabold text-slate-900">Platform previews</h2>
      <div class="mt-3 grid md:grid-cols-2 gap-4"><?php foreach ($post['platforms'] as $p): ?>
        <div class="rounded-xl border border-slate-200 p-4"><div class="text-[11px] font-bold uppercase text-slate-500"><?= esc($p['platform']) ?></div><p class="mt-2 text-sm whitespace-pre-wrap"><?= esc($p['caption']) ?></p><p class="mt-2 text-xs text-brand-primary"><?= esc($p['hashtags'] ?? '') ?></p></div>
      <?php endforeach; ?></div></div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($decidable): ?>
  <form method="post" action="<?= base_url('admin/agents/approvals/' . (int) $post['id'] . '/decide') ?>" class="rounded-2xl border-2 border-brand-primary/30 bg-white p-5 space-y-3">
    <?= csrf_field() ?>
    <h2 class="text-sm font-extrabold text-slate-900">Your decision</h2>
    <label class="block text-xs font-bold text-slate-700">Note to the agent / team <span class="font-normal text-slate-500">(required to reject or request changes; visible to the agent)</span>
      <textarea name="note" rows="3" maxlength="500" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></textarea></label>
    <div class="flex flex-wrap gap-3">
      <button name="decision" value="approve" class="px-5 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold" onclick="return confirm('Approve this exact content? You will still need to schedule or publish it yourself.');">Approve</button>
      <button name="decision" value="changes" class="px-5 py-2.5 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 text-xs font-bold">Request changes</button>
      <button name="decision" value="reject" class="px-5 py-2.5 rounded-xl border border-red-300 bg-white text-red-700 text-xs font-bold" onclick="return confirm('Reject and close this draft?');">Reject</button>
    </div>
  </form>
  <?php elseif (!$locked && empty($post['review_decision'])): ?><p class="text-xs text-slate-500">This draft is not awaiting a decision.</p><?php endif; ?>
</div>
