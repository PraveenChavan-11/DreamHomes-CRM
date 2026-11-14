<div class="max-w-4xl mx-auto space-y-6">

  <div class="flex items-center gap-3">
    <a href="<?= base_url('admin/mail') ?>" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 transition">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Compose Email</h1>
  </div>

  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8">
    <form action="<?= base_url('admin/mail/send') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">From (Hostinger Account)</label>
          <select name="from" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none focus:border-brand-primary">
            <option value="hr@talenthubconsultant.com">hr@talenthubconsultant.com (TalentHub Recruitment)</option>
            <option value="careers@talenthubconsultant.com">careers@talenthubconsultant.com (Careers Portal)</option>
            <option value="info@talenthubconsultant.com">info@talenthubconsultant.com (General Operations)</option>
            <option value="contact@talenthubconsultant.com">contact@talenthubconsultant.com (Client Desk)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Recipient Email (To) *</label>
          <input type="email" name="to" required value="<?= esc($to ?? '') ?>" placeholder="candidate@example.com or client@company.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Subject *</label>
        <input type="text" name="subject" required value="<?= esc($subject ?? '') ?>" placeholder="e.g. Next Steps — Your Job Application with TalentHub" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-primary">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Message Body *</label>
        <textarea id="mail-body" name="body" rows="10" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-primary"></textarea>
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-slate-100">
        <a href="<?= base_url('admin/mail') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
          Discard
        </a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
          <span>Send Message</span>
        </button>
      </div>

    </form>
  </div>

</div>

<script>
  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#mail-body',
      height: 350,
      menubar: false,
      plugins: 'link lists autolink',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link',
      branding: false
    });
  }
</script>

