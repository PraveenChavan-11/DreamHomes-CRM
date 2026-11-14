<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Comments &amp; Engagement</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Official platform comment threads, moderation, and authorized company page replies.</p>
    </div>

    <!-- Feature Availability Pill -->
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
        Official Posts API Scopes Active
      </span>
    </div>
  </div>

  <!-- API Compliance & Feature Notice Card -->
  <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5 text-xs text-amber-900 space-y-1">
    <span class="font-bold block uppercase tracking-wider text-amber-800">Platform API Capability Notice</span>
    <p class="text-amber-700 leading-relaxed">
      TalentHub strictly operates via approved, official enterprise developer APIs (LinkedIn Posts &amp; Social Actions API / Meta Content Publishing &amp; Comments API). Direct messaging (DMs) and personal account scraping are deliberately omitted in strict accordance with Meta and LinkedIn developer platform policies.
    </p>
  </div>

  <!-- Comments Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Public Comments Stream</h2>
      <span class="text-xs font-bold text-slate-600"><?= count($comments) ?> comment(s)</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="px-5 py-3">Platform</th>
            <th class="px-4 py-3">Author</th>
            <th class="px-4 py-3">Comment Text</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-5 py-3 text-right">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($comments)): ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-400">
                No new comments fetched yet. Comments synchronize periodically when posts receive public audience interactions.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($comments as $c): ?>
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-3.5 whitespace-nowrap">
                  <?php if ($c['platform'] === 'linkedin'): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">LinkedIn</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-pink-700">Instagram</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3.5 font-bold text-slate-900"><?= esc($c['author_name']) ?></td>
                <td class="px-4 py-3.5 max-w-md">
                  <p class="text-slate-800"><?= esc($c['comment_text']) ?></p>
                  <?php if (!empty($c['our_reply_text'])): ?>
                    <div class="mt-1.5 p-2 rounded-lg bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                      <span class="font-bold text-brand-primary">TalentHub Reply:</span> <?= esc($c['our_reply_text']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <?php if ($c['has_replied']): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700">Replied</span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 text-right text-slate-400 whitespace-nowrap">
                  <?= esc(date('M d, H:i', strtotime($c['comment_date']))) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

