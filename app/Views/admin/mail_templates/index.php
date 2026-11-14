<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        <span>Hostinger SMTP Delivery</span>
      </div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Transactional Email Templates</h1>
      <p class="text-xs text-slate-500 mt-1">Configure automated acknowledgement, corporate notifications, candidate alerts, and password recovery communications</p>
    </div>
  </div>

  <!-- Templates Grid / Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Template Name &amp; Code</th>
            <th class="px-6 py-4">Subject Line</th>
            <th class="px-6 py-4">Status</th>
            <th class="px-6 py-4">Available Tags</th>
            <th class="px-6 py-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($templates)): ?>
            <tr>
              <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-xs">No email templates found in database.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($templates as $t): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 text-sm"><?= esc($t['name']) ?></div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5"><?= esc($t['code']) ?></div>
                </td>
                <td class="px-6 py-4 max-w-sm">
                  <div class="font-semibold text-slate-800 truncate" title="<?= esc($t['subject']) ?>">
                    <?= esc($t['subject']) ?>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <?php if (!empty($t['is_active'])): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                      ACTIVE
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                      DISABLED
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 max-w-xs text-[11px] font-mono text-slate-500">
                  <div class="truncate" title="<?= esc($t['variables_help'] ?? '') ?>">
                    <?= esc($t['variables_help'] ?? '{{job_title}}, {{candidate_name}}') ?>
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Test Trigger Modal Trigger -->
                    <button type="button" onclick="openTestModal(<?= esc($t['id']) ?>, '<?= esc($t['name']) ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-sm transition" title="Send Test Email">
                      <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                      <span>Test</span>
                    </button>

                    <!-- Edit Template Link -->
                    <a href="<?= base_url('admin/mail/templates/edit/' . $t['id']) ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-light text-brand-primary hover:bg-emerald-100 text-xs font-bold transition">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                      <span>Edit</span>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Test Send Modal -->
<div id="testModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h3 class="text-base font-extrabold text-slate-900">Dispatch Test Message</h3>
        <p id="modalTemplateName" class="text-xs text-slate-500 font-semibold mt-0.5"></p>
      </div>
      <button onclick="closeTestModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="testSendForm" method="POST" action="">
      <?= csrf_field() ?>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Target Recipient Email</label>
          <input type="email" name="test_email" value="<?= esc($adminUser['email'] ?? 'admin@talenthubconsultant.com') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          <p class="text-[11px] text-slate-400 mt-1">Mock recruitment tags will be rendered and delivered via Hostinger SMTP.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
          <button type="button" onclick="closeTestModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold transition shadow-sm">Send Test Message</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
function openTestModal(templateId, templateName) {
  document.getElementById('modalTemplateName').textContent = templateName;
  document.getElementById('testSendForm').action = '<?= base_url('admin/mail/templates/test/') ?>' + templateId;
  document.getElementById('testModal').classList.remove('hidden');
}

function closeTestModal() {
  document.getElementById('testModal').classList.add('hidden');
}
</script>

