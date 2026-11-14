<?php
$fromEmail = $email['from_email'] ?? $email['from_address'] ?? '';
$safeSubject = $email['subject'] ?? '(No Subject)';
$allowedTags = '<p><br><b><strong><i><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><div><span><table><tr><td><th><tbody><thead><hr>';
$cleanBody = !empty($email['body_html']) ? strip_tags($email['body_html'], $allowedTags) : '';
$cleanBody = preg_replace('/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleanBody);
$cleanBody = preg_replace('/href\s*=\s*["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $cleanBody);
?>

<div class="max-w-4xl mx-auto space-y-6">

  <!-- Back Button & Actions -->
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/mail') ?>" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">View Message</h1>
    </div>

    <a href="<?= base_url('admin/mail/compose?to=' . urlencode($fromEmail) . '&subject=' . urlencode('Re: ' . $safeSubject)) ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
      <span>Reply</span>
    </a>
  </div>

  <!-- Email Card -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <!-- Header Details -->
    <div class="p-6 md:p-8 border-b border-slate-100 bg-slate-50/50 space-y-4">
      <h2 class="text-xl font-bold text-slate-900"><?= esc($safeSubject) ?></h2>

      <div class="flex flex-wrap items-center justify-between gap-4 text-xs text-slate-600">
        <div>
          <span class="font-bold text-slate-900"><?= esc($email['from_name'] ?: $fromEmail) ?></span>
          <span class="text-slate-400 font-mono">&lt;<?= esc($fromEmail) ?>&gt;</span>
        </div>
        <div class="text-slate-400 text-[11px]">
          <?= esc(date('F d, Y - h:i A', strtotime($email['message_date'] ?? $email['received_at'] ?? $email['created_at'] ?? 'now'))) ?>
        </div>
      </div>

      <?php if (!empty($email['to_recipients']) || !empty($email['to_address'])): ?>
        <div class="text-xs text-slate-500">
          To: <span class="text-slate-700 font-mono"><?= esc($email['to_recipients'] ?? $email['to_address'] ?? '') ?></span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Body -->
    <div class="p-6 md:p-8 text-sm text-slate-800 leading-relaxed font-sans prose max-w-none">
      <?php if (!empty($cleanBody)): ?>
        <div><?= $cleanBody ?></div>
      <?php else: ?>
        <pre class="whitespace-pre-wrap font-sans text-xs text-slate-700"><?= esc($email['body_plain'] ?? $email['snippet'] ?? '') ?></pre>
      <?php endif; ?>
    </div>
  </div>

</div>

