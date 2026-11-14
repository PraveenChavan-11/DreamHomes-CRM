<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Hostinger Email Management Center</h1>
      <p class="text-xs text-slate-500 mt-1">Official Hostinger Mail API synchronization, candidate outreach &amp; recruitment mailboxes</p>
    </div>

    <div class="flex items-center gap-3">
      <form action="<?= base_url('admin/mail/sync') ?>" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-sm">
          <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          <span>Sync Now</span>
        </button>
      </form>
      <a href="<?= base_url('admin/mail/compose') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        <span>Compose Email</span>
      </a>
    </div>
  </div>

  <!-- Mailbox Quota Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Default Mailbox</p>
        <h4 class="text-sm font-bold text-slate-900 mt-1 font-mono">hr@talenthubconsultant.com</h4>
        <span class="inline-block mt-1 text-[10px] text-emerald-600 font-bold">&bull; Hostinger Webmail Ready</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-brand-light text-brand-primary flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Synced Messages</p>
        <h4 class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($messages) ?></h4>
        <span class="inline-block mt-1 text-[10px] text-slate-500 font-medium">Indexed in database</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pending Outbox</p>
        <h4 class="text-2xl font-extrabold text-slate-900 mt-1"><?= esc($outboxCount ?? 0) ?></h4>
        <span class="inline-block mt-1 text-[10px] text-amber-600 font-medium">Automatic delivery queue</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
      </div>
    </div>
  </div>

  <!-- Mail Search & Folder Navigation -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-2">
        <a href="<?= base_url('admin/mail?folder=INBOX') ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= ($folder === 'INBOX') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
          Inbox
        </a>
        <a href="<?= base_url('admin/mail?folder=Sent') ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold transition <?= ($folder === 'Sent') ? 'bg-brand-primary text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
          Sent
        </a>
        <a href="<?= base_url('admin/mail/settings') ?>" class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition">
          API Settings
        </a>
      </div>

      <form action="<?= base_url('admin/mail') ?>" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
        <input type="hidden" name="folder" value="<?= esc($folder) ?>">
        <div class="relative w-full sm:w-64">
          <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search subject or sender..." class="w-full pl-9 pr-4 py-1.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">Search</button>
      </form>
    </div>

    <!-- Email Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">From / Sender</th>
            <th class="px-6 py-3.5">Subject &amp; Snippet</th>
            <th class="px-6 py-3.5">Folder</th>
            <th class="px-6 py-3.5 text-right">Received Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($messages)): ?>
            <tr>
              <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-xs">
                No email messages found in this folder. Click "Sync Now" to fetch recent emails via Hostinger Mail API.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($messages as $m): ?>
              <tr class="hover:bg-slate-50/70 transition <?= empty($m['is_read']) ? 'bg-emerald-50/30 font-semibold' : '' ?>">
                <td class="px-6 py-4">
                  <a href="<?= base_url('admin/mail/view/' . $m['id']) ?>" class="block">
                    <?php 
                      $fromEmail = $m['from_email'] ?? $m['from_address'] ?? '';
                      $fromName  = !empty($m['from_name']) ? $m['from_name'] : $fromEmail;
                    ?>
                    <span class="text-slate-900 block"><?= esc($fromName) ?></span>
                    <span class="text-[11px] text-slate-400 font-normal"><?= esc($fromEmail) ?></span>
                  </a>
                </td>
                <td class="px-6 py-4 max-w-lg">
                  <a href="<?= base_url('admin/mail/view/' . $m['id']) ?>" class="block">
                    <span class="text-slate-900 block hover:text-brand-primary"><?= esc($m['subject'] ?: '(No Subject)') ?></span>
                    <span class="text-[11px] text-slate-500 font-normal line-clamp-1 mt-0.5"><?= esc($m['snippet'] ?? '') ?></span>
                  </a>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                    <?= esc($m['folder'] ?? 'INBOX') ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-slate-500 text-[11px]">
                  <?= esc(date('M d, H:i', strtotime($m['message_date'] ?? $m['received_at'] ?? $m['created_at'] ?? 'now'))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

