<div class="space-y-8">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Frequently Asked Questions (FAQ)</h1>
      <p class="text-xs text-slate-500 mt-1">Manage FAQs published on the /faq public page</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form to Add New FAQ -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-5 h-fit">
      <h3 class="text-base font-bold text-slate-900">Add New FAQ</h3>
      <form action="<?= base_url('admin/content/faqs/store') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Category</label>
          <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
            <option value="General">General</option>
            <option value="Employers">Employers / Hiring</option>
            <option value="Candidates">Candidates / Job Seekers</option>
            <option value="Process">Recruitment Process</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Question *</label>
          <input type="text" name="question" required placeholder="e.g. How does TalentHub screen executive candidates?" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Answer *</label>
          <textarea name="answer" rows="4" required placeholder="Detailed answer..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary"></textarea>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Display Order</label>
          <input type="number" name="display_order" value="0" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold transition shadow-sm">
          Save FAQ
        </button>
      </form>
    </div>

    <!-- FAQs List -->
    <div class="lg:col-span-2 space-y-4">
      <?php if (empty($faqs)): ?>
        <div class="bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-400 text-xs">
          No FAQs created yet. Use the form on the left to add questions.
        </div>
      <?php else: ?>
        <?php foreach ($faqs as $f): ?>
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start justify-between gap-4">
            <div class="space-y-1.5">
              <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase">
                <?= esc($f['category'] ?? 'General') ?>
              </span>
              <h4 class="text-sm font-bold text-slate-900"><?= esc($f['question']) ?></h4>
              <p class="text-xs text-slate-600 leading-relaxed"><?= esc($f['answer']) ?></p>
            </div>
            <form action="<?= base_url('admin/content/faqs/delete/' . $f['id']) ?>" method="POST" onsubmit="return confirm('Delete this FAQ?');" class="shrink-0">
              <?= csrf_field() ?>
              <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition" title="Delete FAQ">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

