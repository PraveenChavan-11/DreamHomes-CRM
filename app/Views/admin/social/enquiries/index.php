<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Social Enquiries &amp; Leads</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Track, assign, qualify, and convert social-originated prospects from LinkedIn and Instagram.</p>
    </div>

    <div class="flex items-center gap-2.5">
      <a href="<?= base_url('admin/social/enquiries/export') ?>" class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Export CSV</span>
      </a>

      <button type="button" onclick="openManualLeadModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-xs font-bold hover:bg-brand-hover transition shadow-sm">
        <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Record Manual Lead</span>
      </button>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="px-5 py-3.5">Contact &amp; Organization</th>
            <th class="px-4 py-3.5">Channel</th>
            <th class="px-4 py-3.5">Inquiry Details</th>
            <th class="px-4 py-3.5">Status</th>
            <th class="px-5 py-3.5 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($enquiries)): ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-400">
                No social inquiries captured yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($enquiries as $enq): ?>
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-4">
                  <span class="font-bold text-slate-900 block text-sm leading-tight"><?= esc($enq['contact_name']) ?></span>
                  <?php if (!empty($enq['company'])): ?>
                    <span class="text-xs text-slate-500 font-medium block"><?= esc($enq['company']) ?></span>
                  <?php endif; ?>
                  <span class="text-[11px] text-slate-400 block mt-0.5"><?= esc($enq['email'] ?? $enq['phone'] ?? 'No contact provided') ?></span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <?php if ($enq['source_platform'] === 'linkedin'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">LinkedIn</span>
                  <?php elseif ($enq['source_platform'] === 'instagram'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700 border border-pink-200">Instagram</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700"><?= esc(ucfirst($enq['source_platform'])) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 max-w-sm">
                  <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-slate-100 text-slate-600 mr-1.5">
                    <?= esc($enq['enquiry_type']) ?>
                  </span>
                  <p class="text-slate-700 text-xs mt-1 line-clamp-2"><?= esc($enq['message']) ?></p>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <form method="POST" action="<?= base_url('admin/social/enquiries/' . $enq['id'] . '/status') ?>">
                    <?= csrf_field() ?>
                    <select name="lead_status" onchange="this.form.submit()" class="text-xs font-semibold rounded-lg border border-slate-200 py-1 px-2.5 bg-slate-50 text-slate-800 focus:ring-1 focus:ring-brand-accent">
                      <?php foreach ($statuses as $k => $label): ?>
                        <option value="<?= esc($k) ?>" <?= ($enq['lead_status'] === $k) ? 'selected' : '' ?>><?= esc($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </form>
                </td>
                <td class="px-5 py-4 text-right text-slate-400 whitespace-nowrap">
                  <?= esc(date('M d, Y', strtotime($enq['created_at']))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- MANUAL LEAD RECORD MODAL -->
<div id="manualLeadModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <h3 class="text-base font-extrabold text-slate-900">Record Social / External Lead</h3>
      <button type="button" onclick="closeManualLeadModal()" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
    </div>

    <form method="POST" action="<?= base_url('admin/social/enquiries/store') ?>" class="space-y-3">
      <?= csrf_field() ?>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Contact Name <span class="text-rose-500">*</span></label>
        <input type="text" name="contact_name" required placeholder="Full Name" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Email</label>
          <input type="email" name="email" placeholder="name@company.com" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Phone</label>
          <input type="tel" name="phone" placeholder="+91 ..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Source Platform</label>
          <select name="source_platform" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
            <option value="linkedin">LinkedIn</option>
            <option value="instagram">Instagram</option>
            <option value="website">Website Form</option>
            <option value="campaign">Marketing Campaign</option>
            <option value="manual">Direct DM / Referral</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Enquiry Type</label>
          <select name="enquiry_type" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
            <option value="recruitment">Tech Recruitment</option>
            <option value="it_services">IT Services</option>
            <option value="worksphere_hrms">WorkSphere HRMS</option>
            <option value="consulting">Technology Consulting</option>
            <option value="general">General</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Company / Organization</label>
        <input type="text" name="company" placeholder="e.g. Acme Tech Solutions" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Message / Requirements <span class="text-rose-500">*</span></label>
        <textarea name="message" required rows="3" placeholder="Summary of client requirement or message..." class="w-full text-xs rounded-xl border border-slate-300 p-2.5"></textarea>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeManualLeadModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cancel</button>
        <button type="submit" class="px-5 py-2.5 bg-brand-primary text-white text-xs font-extrabold rounded-xl hover:bg-brand-hover shadow-sm">
          Save Lead
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openManualLeadModal() {
  document.getElementById('manualLeadModal').classList.remove('hidden');
}
function closeManualLeadModal() {
  document.getElementById('manualLeadModal').classList.add('hidden');
}
</script>
<?= $this->endSection() ?>

