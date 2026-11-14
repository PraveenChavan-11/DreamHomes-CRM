<?php
/** @var string $category */
/** @var string $service */
/** @var array $utm */
use App\Services\ItLeadService as L;
$old = static fn (string $k) => esc(old($k) ?? '');
$input = 'form-input-premium text-xs';
?>
<div class="pt-32 pb-20 bg-[#F9FBFA] min-h-screen">
  <div class="page-width max-w-3xl">
    <div class="mb-8 space-y-3">
      <p class="text-xs font-bold text-[#1D6A34] uppercase tracking-wider">Technology Projects</p>
      <h1 class="text-3xl sm:text-4xl font-extrabold text-[#172B22] tracking-tight">Discuss Your Project</h1>
      <p class="text-sm text-[#3d6350] leading-relaxed">Tell us what you want to build, fix or improve. We respond to project enquiries from organisations across India. Looking to hire instead? <a href="<?= base_url('employers') ?>" class="font-bold text-[#1D6A34] underline">Share a hiring requirement</a>.</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-xs font-semibold text-red-700"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="card-premium bg-white p-6 sm:p-8">
      <form action="<?= base_url('project-enquiry/submit') ?>" method="POST" data-ajax="true" toolname="submit_it_project_enquiry" tooldescription="Submit a technology project enquiry to TalentHub" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="service_slug" value="<?= esc($service) ?>">
        <input type="hidden" name="source_page" value="<?= esc($service ? 'services/' . $service : 'project-enquiry') ?>">
        <?php foreach ($utm as $k => $v): if ($v): ?><input type="hidden" name="<?= esc($k) ?>" value="<?= esc($v) ?>"><?php endif; endforeach; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Your Name *</label>
            <input type="text" name="client_name" required maxlength="150" value="<?= $old('client_name') ?>" class="<?= $input ?>"></div>
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Company / Organisation</label>
            <input type="text" name="company" maxlength="150" value="<?= $old('company') ?>" class="<?= $input ?>"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Email *</label>
            <input type="email" name="email" required maxlength="190" value="<?= $old('email') ?>" class="<?= $input ?>"></div>
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Phone *</label>
            <input type="tel" name="phone" required maxlength="25" value="<?= $old('phone') ?>" placeholder="+91 …" class="<?= $input ?>"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Location (city / country)</label>
            <input type="text" name="location" maxlength="120" value="<?= $old('location') ?>" class="<?= $input ?>"></div>
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Service Needed *</label>
            <select name="service_category" required class="<?= $input ?> cursor-pointer">
              <?php foreach (L::CATEGORIES as $slug => $label): ?>
                <option value="<?= esc($slug) ?>" <?= ($category === $slug) ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select></div>
        </div>
        <div><label class="block text-xs font-bold text-[#172B22] mb-1">Project Description *</label>
          <textarea name="project_description" required minlength="10" maxlength="4000" rows="5" placeholder="What do you need, who will use it, and any systems it must work with?" class="<?= $input ?>"><?= $old('project_description') ?></textarea></div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Budget Range (optional)</label>
            <select name="budget_range" class="<?= $input ?>"><option value="">Prefer not to say</option>
              <?php foreach (L::BUDGETS as $b): ?><option><?= esc($b) ?></option><?php endforeach; ?></select></div>
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Timeline</label>
            <select name="timeline" class="<?= $input ?>"><option value="">Not decided</option>
              <?php foreach (L::TIMELINES as $t): ?><option><?= esc($t) ?></option><?php endforeach; ?></select></div>
          <div><label class="block text-xs font-bold text-[#172B22] mb-1">Preferred Contact</label>
            <select name="preferred_contact" class="<?= $input ?>"><option value="">Any</option>
              <?php foreach (L::CONTACTS as $c): ?><option value="<?= esc($c) ?>"><?= esc(ucfirst($c)) ?></option><?php endforeach; ?></select></div>
        </div>

        <?= $formProtection ?? '' ?>

        <div class="flex items-start gap-2 pt-1">
          <input type="checkbox" name="consent" id="pe-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
          <label for="pe-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">I consent to TalentHub contacting me about this enquiry in line with the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy</a>.</label>
        </div>
        <button type="submit" class="w-full btn-primary text-xs font-bold py-3.5 shadow-lg shadow-[#1D6A34]/25">Send Project Enquiry</button>
      </form>
    </div>
  </div>
</div>
