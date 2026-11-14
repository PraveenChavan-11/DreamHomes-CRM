<div class="space-y-6 max-w-5xl">

  <!-- Header Bar -->
  <div class="flex items-center justify-between">
    <div>
      <a href="<?= base_url('admin/mail/templates') ?>" class="text-xs font-semibold text-brand-primary hover:text-brand-hover inline-flex items-center gap-1 mb-2">
        &larr; Back to Templates
      </a>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Edit Email Template</h1>
      <p class="text-xs text-slate-500 mt-0.5"><?= esc($template['name']) ?> (<span class="font-mono text-slate-700"><?= esc($template['code']) ?></span>)</p>
    </div>
  </div>

  <form action="<?= base_url('admin/mail/templates/update/' . $template['id']) ?>" method="POST" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-6">

      <!-- Template Key & Name (Readonly Information) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100">
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Template Code</label>
          <input type="text" value="<?= esc($template['code']) ?>" readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono text-slate-600 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-600 mb-1">Template Label</label>
          <input type="text" value="<?= esc($template['name']) ?>" readonly class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-700 cursor-not-allowed">
        </div>
      </div>

      <!-- Subject Line -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Email Subject Line <span class="text-red-500">*</span></label>
        <input type="text" name="subject" value="<?= esc(old('subject', $template['subject'])) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-brand-primary font-medium">
        <p class="text-[11px] text-slate-400 mt-1">Supports variable interpolation such as <code class="text-brand-primary font-mono font-bold">{{job_title}}</code> or <code class="text-brand-primary font-mono font-bold">{{company_name}}</code>.</p>
      </div>

      <!-- Variable Reference Helper Box -->
      <?php if (!empty($template['variables_help'])): ?>
        <div class="p-4 rounded-2xl bg-brand-light/60 border border-brand-primary/20 text-xs text-brand-dark space-y-1">
          <div class="font-bold flex items-center gap-1.5 text-brand-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Available Variables For This Template:</span>
          </div>
          <div class="font-mono text-[11px] text-slate-700 font-semibold pt-1">
            <?= esc($template['variables_help']) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- HTML Body with TinyMCE -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-2">HTML Email Body <span class="text-red-500">*</span></label>
        <textarea id="body_html" name="body_html" rows="16" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-900 focus:outline-none focus:border-brand-primary"><?= esc(old('body_html', $template['body_html'])) ?></textarea>
      </div>

      <!-- Plain Text Fallback Body -->
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Plain Text Fallback Body (Optional)</label>
        <textarea name="body_text" rows="5" placeholder="Plain text representation for text-only email clients..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:border-brand-primary"><?= esc(old('body_text', $template['body_text'] ?? '')) ?></textarea>
      </div>

      <!-- Active Toggle -->
      <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" name="is_active" value="1" <?= (!empty($template['is_active'])) ? 'checked' : '' ?> class="sr-only peer">
          <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
        </label>
        <span class="text-xs font-bold text-slate-800">Template Active &amp; Dispatchable</span>
      </div>

    </div>

    <!-- Actions Footer -->
    <div class="flex items-center justify-between">
      <a href="<?= base_url('admin/mail/templates') ?>" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        Cancel
      </a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold transition shadow-md shadow-brand-primary/20">
        Save Template Changes
      </button>
    </div>

  </form>

</div>

<!-- TinyMCE Rich Editor Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#body_html',
      plugins: 'lists link code table',
      toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link table | code',
      menubar: false,
      height: 380,
      content_style: 'body { font-family: Inter, sans-serif; font-size: 14px; line-height: 1.6; }'
    });
  }
});
</script>

