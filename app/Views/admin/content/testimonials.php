<div class="space-y-8">

  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Client &amp; Candidate Testimonials</h1>
      <p class="text-xs text-slate-500 mt-1">Manage social proof and reviews displayed on the public website</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Form to Add New Testimonial -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-5 h-fit">
      <h3 class="text-base font-bold text-slate-900">Add Testimonial</h3>
      <form action="<?= base_url('admin/content/testimonials/store') ?>" method="POST" class="space-y-4">
        <?= csrf_field() ?>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Client / Candidate Name *</label>
          <input type="text" name="client_name" required placeholder="e.g. Rajesh Sharma" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Role / Designation</label>
          <input type="text" name="client_role" placeholder="e.g. Head of Talent Acquisition" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Company Name</label>
          <input type="text" name="company_name" placeholder="e.g. Austin Realty" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Testimonial Quote *</label>
          <textarea name="quote" rows="4" required placeholder="Share their feedback..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary"></textarea>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Star Rating</label>
          <select name="rating" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
            <option value="5">5 Stars (Excellent)</option>
            <option value="4">4 Stars (Very Good)</option>
            <option value="3">3 Stars (Good)</option>
          </select>
        </div>

        <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold transition shadow-sm">
          Save Testimonial
        </button>
      </form>
    </div>

    <!-- Testimonials List -->
    <div class="lg:col-span-2 space-y-4">
      <?php if (empty($testimonials)): ?>
        <div class="bg-white p-12 rounded-3xl border border-slate-200 text-center text-slate-400 text-xs">
          No testimonials created yet. Add one on the left to display on the public website.
        </div>
      <?php else: ?>
        <?php foreach ($testimonials as $t): 
          $tName = $t['name'] ?? $t['client_name'] ?? 'Anonymous';
          $tRole = $t['designation'] ?? $t['client_role'] ?? 'Professional';
          $tCompany = $t['company'] ?? $t['company_name'] ?? 'Pune Enterprise';
          $tQuote = trim((string)($t['content'] ?? $t['quote'] ?? ''));
          if ($tQuote === '') {
              $tQuote = 'Experienced seamless recruitment consulting and professional executive placement with TalentHub Consultant.';
          }
        ?>
          <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-bold text-slate-900"><?= esc($tName) ?></h4>
                  <p class="text-xs text-slate-500"><?= esc($tRole) ?><?= !empty($tCompany) ? ' &bull; <span class="font-semibold text-brand-primary">' . esc($tCompany) . '</span>' : '' ?></p>
                </div>
                <div class="flex text-amber-400 text-xs">
                  <?php for ($i = 0; $i < (int)($t['rating'] ?? 5); $i++): ?>★<?php endfor; ?>
                </div>
              </div>
              <p class="text-xs text-slate-700 italic leading-relaxed">"<?= esc($tQuote) ?>"</p>
            </div>

            <div class="flex items-center justify-end pt-3 border-t border-slate-100">
              <form action="<?= base_url('admin/content/testimonials/delete/' . $t['id']) ?>" method="POST" onsubmit="return confirm('Delete this testimonial?');">
                <?= csrf_field() ?>
                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-semibold">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

