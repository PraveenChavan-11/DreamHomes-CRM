<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="max-w-3xl space-y-6 pb-16">

  <!-- Header -->
  <div class="border-b border-slate-200 pb-5">
    <div class="flex items-center gap-2">
      <a href="<?= base_url('admin/social/campaigns') ?>" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Back to Campaigns</a>
    </div>
    <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= esc($title) ?></h1>
  </div>

  <form method="POST" action="<?= !empty($campaign['id']) ? base_url('admin/social/campaigns/' . $campaign['id'] . '/save') : base_url('admin/social/campaigns/save') ?>" class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
    <?= csrf_field() ?>

    <div>
      <label class="block text-xs font-bold text-slate-700 mb-1">Campaign Name <span class="text-rose-500">*</span></label>
      <input type="text" name="name" required value="<?= esc($campaign['name'] ?? old('name')) ?>" placeholder="e.g. Q1 Tech Recruitment Drive" class="w-full text-sm font-semibold rounded-xl border border-slate-300 px-3.5 py-2.5 focus:ring-2 focus:ring-brand-accent">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Objective</label>
        <select name="objective" class="w-full text-xs font-medium rounded-xl border border-slate-300 p-2.5">
          <option value="recruitment" <?= (($campaign['objective'] ?? '') === 'recruitment') ? 'selected' : '' ?>>Recruitment Campaign</option>
          <option value="brand_awareness" <?= (($campaign['objective'] ?? '') === 'brand_awareness') ? 'selected' : '' ?>>Brand Awareness</option>
          <option value="lead_generation" <?= (($campaign['objective'] ?? '') === 'lead_generation') ? 'selected' : '' ?>>Lead Generation</option>
          <option value="hrms_promotion" <?= (($campaign['objective'] ?? '') === 'hrms_promotion') ? 'selected' : '' ?>>HRMS / WorkSphere Promotion</option>
          <option value="it_services_promotion" <?= (($campaign['objective'] ?? '') === 'it_services_promotion') ? 'selected' : '' ?>>IT Services Promotion</option>
          <option value="product_launch" <?= (($campaign['objective'] ?? '') === 'product_launch') ? 'selected' : '' ?>>Product Launch</option>
          <option value="educational" <?= (($campaign['objective'] ?? '') === 'educational') ? 'selected' : '' ?>>Educational Content</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
        <select name="status" class="w-full text-xs font-medium rounded-xl border border-slate-300 p-2.5">
          <option value="draft" <?= (($campaign['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
          <option value="active" <?= (($campaign['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
          <option value="paused" <?= (($campaign['status'] ?? '') === 'paused') ? 'selected' : '' ?>>Paused</option>
          <option value="completed" <?= (($campaign['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Completed</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-slate-700 mb-1">Description &amp; Strategy</label>
      <textarea name="description" rows="3" placeholder="Outline campaign messaging and goals..." class="w-full text-xs rounded-xl border border-slate-300 p-3"><?= esc($campaign['description'] ?? old('description')) ?></textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Start Date</label>
        <input type="date" name="start_date" value="<?= esc($campaign['start_date'] ?? '') ?>" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">End Date</label>
        <input type="date" name="end_date" value="<?= esc($campaign['end_date'] ?? '') ?>" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
      </div>
    </div>

    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
      <a href="<?= base_url('admin/social/campaigns') ?>" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Cancel</a>
      <button type="submit" class="px-5 py-2.5 bg-brand-primary text-white text-xs font-extrabold rounded-xl hover:bg-brand-hover shadow-sm transition">
        Save Campaign
      </button>
    </div>
  </form>

</div>
<?= $this->endSection() ?>

