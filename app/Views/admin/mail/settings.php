<div class="max-w-3xl mx-auto space-y-6">

  <div class="flex items-center gap-3">
    <a href="<?= base_url('admin/mail') ?>" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Hostinger Mail API Settings</h1>
      <p class="text-xs text-slate-500 mt-0.5">Manage official API keys for automated recruitment email synchronization</p>
    </div>
  </div>

  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8">
    <form action="<?= base_url('admin/mail/settings') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Hostinger Mail API Token</label>
        <input type="password" name="hostinger_mail_token" value="<?= esc($mailSettings['hostinger_mail_token'] ?? '') ?>" placeholder="Enter API token from Hostinger hPanel -> Emails -> Mail API" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none focus:border-brand-primary">
        <p class="text-[11px] text-slate-400 mt-1.5">
          API Base URL: <code class="font-bold text-brand-primary">https://api.mail.hostinger.com/api/v1/</code>. Can also be set in <code class="font-bold">.env</code> as <code class="font-bold">HOSTINGER_MAIL_API_TOKEN</code>.
        </p>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Default Outgoing Address</label>
        <input type="email" name="hostinger_default_from" value="<?= esc($mailSettings['hostinger_default_from'] ?? 'hr@talenthubconsultant.com') ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none focus:border-brand-primary">
      </div>

      <div class="flex items-center justify-end pt-4 border-t border-slate-100">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
          Save Settings
        </button>
      </div>

    </form>
  </div>

</div>

