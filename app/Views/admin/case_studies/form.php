<?php
use App\Services\CaseStudyService as CS;
/** @var array|null $cs @var array $images @var bool $canManage @var array $formErrors @var array $statuses */
$isNew = empty($cs);
$id    = (int) ($cs['id'] ?? 0);
$P     = $cs ?? [];
$v     = static fn (string $k, $d = '') => old($k) ?? ($P[$k] ?? $d);
$val   = static fn (string $k, $d = '') => esc((string) $v($k, $d));
$chk   = static fn (string $k) => (old($k) !== null ? !empty(old($k)) : !empty($P[$k] ?? 0)) ? 'checked' : '';
$err   = static fn (string $k) => isset($formErrors[$k]) ? '<p class="text-[11px] text-rose-600 font-semibold mt-1" id="err-' . esc($k, 'attr') . '">' . esc($formErrors[$k]) . '</p>' : '';
$in    = 'w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:border-brand-primary bg-white min-h-[40px]';
$lbl   = 'block text-[11px] font-bold text-slate-700 mb-1';
$card  = 'bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4';
$hint  = 'text-[11px] text-slate-500 mt-1';
?>
<div class="space-y-6 max-w-4xl">
  <div>
    <a href="<?= base_url('admin/case-studies') ?>" class="text-xs text-slate-500 hover:text-slate-700">&larr; All case studies</a>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1"><?= $isNew ? 'New case study' : 'Edit: ' . esc($cs['title']) ?></h1>
    <?php if (!$isNew): ?><p class="text-xs text-slate-500">Status: <strong><?= esc($statuses[$cs['status']] ?? $cs['status']) ?></strong><?php if ($cs['status'] === 'published'): ?> · <a class="underline" href="<?= base_url('case-studies/' . $cs['slug']) ?>" target="_blank" rel="noopener">View live</a><?php endif; ?></p><?php endif; ?>
  </div>

  <?php if ($formErrors): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-700" role="alert">
      <p class="font-bold mb-1">Please fix the following:</p>
      <ul class="list-disc pl-5"><?php foreach ($formErrors as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form action="<?= base_url($isNew ? 'admin/case-studies/create' : 'admin/case-studies/' . $id . '/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
    <?= csrf_field() ?>
    <fieldset <?= $canManage ? '' : 'disabled' ?> class="space-y-6 min-w-0">
      <section class="<?= $card ?>" aria-labelledby="cs-basics">
        <h2 id="cs-basics" class="text-sm font-extrabold text-slate-900">1. Project</h2>
        <p class="text-[11px] text-slate-500">Only add projects that are real and that you are permitted to show. Do not include confidential client data, or figures you cannot substantiate.</p>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="cs-title">Title *</label><input id="cs-title" class="<?= $in ?>" name="title" required maxlength="180" value="<?= $val('title') ?>"><?= $err('title') ?></div>
          <div><label class="<?= $lbl ?>" for="cs-slug">URL slug</label><input id="cs-slug" class="<?= $in ?>" name="slug" maxlength="100" pattern="[a-z0-9\-]*" value="<?= $val('slug') ?>" placeholder="auto-generated from the title"></div>
          <div><label class="<?= $lbl ?>" for="cs-client">Client / organisation (optional)</label><input id="cs-client" class="<?= $in ?>" name="client_name" maxlength="150" value="<?= $val('client_name') ?>"></div>
          <div><label class="<?= $lbl ?>" for="cs-cat">Category</label><input id="cs-cat" class="<?= $in ?>" name="category" maxlength="80" value="<?= $val('category') ?>" placeholder="e.g. Web application, Recruitment, ERP / HRMS"></div>
        </div>
        <div><label class="<?= $lbl ?>" for="cs-summary">Summary (card text; required to publish)</label><textarea id="cs-summary" class="<?= $in ?>" name="summary" rows="3" maxlength="600"><?= $val('summary') ?></textarea><?= $err('summary') ?></div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="cs-tech">Technologies (comma separated)</label><input id="cs-tech" class="<?= $in ?>" name="technologies" maxlength="500" value="<?= $val('technologies') ?>" placeholder="e.g. PHP, CodeIgniter 4, MySQL"></div>
          <div><label class="<?= $lbl ?>" for="cs-url">Public project link (optional)</label><input id="cs-url" type="url" class="<?= $in ?>" name="project_url" maxlength="500" value="<?= $val('project_url') ?>" placeholder="https://"><?= $err('project_url') ?></div>
        </div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="cs-story">
        <h2 id="cs-story" class="text-sm font-extrabold text-slate-900">2. The story (plain text; a blank line starts a new paragraph)</h2>
        <div><label class="<?= $lbl ?>" for="cs-ch">Challenge</label><textarea id="cs-ch" class="<?= $in ?>" name="challenge" rows="5" maxlength="5000"><?= $val('challenge') ?></textarea></div>
        <div><label class="<?= $lbl ?>" for="cs-so">Solution</label><textarea id="cs-so" class="<?= $in ?>" name="solution" rows="5" maxlength="5000"><?= $val('solution') ?></textarea></div>
        <div><label class="<?= $lbl ?>" for="cs-ou">Outcome</label><textarea id="cs-ou" class="<?= $in ?>" name="outcome" rows="5" maxlength="5000"><?= $val('outcome') ?></textarea><p class="<?= $hint ?>">State only results you can verify.</p></div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="cs-media">
        <h2 id="cs-media" class="text-sm font-extrabold text-slate-900">3. Images (JPG, PNG or WebP, max 5 MB each)</h2>
        <div>
          <label class="<?= $lbl ?>" for="cs-cover">Cover image</label>
          <?php if (!empty($P['cover_path']) && ($u = CS::assetUrl($P['cover_path']))): ?>
            <img src="<?= esc($u) ?>" alt="Current cover image" class="w-48 rounded-xl border border-slate-200 mb-2">
            <label class="flex items-center gap-2 text-[11px] text-slate-600 mb-2"><input type="checkbox" name="remove_cover" value="1"> Remove the current cover</label>
          <?php endif; ?>
          <input id="cs-cover" type="file" name="cover_file" accept="image/jpeg,image/png,image/webp" class="text-xs">
        </div>
        <div>
          <p class="<?= $lbl ?>">Gallery (up to <?= CS::MAX_GALLERY ?>)</p>
          <?php foreach ($images as $im): $iid = (int) $im['id']; ?>
            <div class="flex flex-wrap items-start gap-3 py-3 border-t border-slate-100">
              <img src="<?= esc(CS::assetUrl($im['path']) ?? '') ?>" alt="<?= esc($im['alt_text'] ?: 'Gallery image') ?>" class="w-24 h-16 object-cover rounded-lg border border-slate-200">
              <div class="grid sm:grid-cols-2 gap-2 flex-1 min-w-[220px]">
                <div><label class="<?= $lbl ?>" for="gc-<?= $iid ?>">Caption</label><input id="gc-<?= $iid ?>" class="<?= $in ?>" name="gallery[<?= $iid ?>][caption]" maxlength="255" value="<?= esc((string) $im['caption']) ?>"></div>
                <div><label class="<?= $lbl ?>" for="ga-<?= $iid ?>">Alt text (describe the image)</label><input id="ga-<?= $iid ?>" class="<?= $in ?>" name="gallery[<?= $iid ?>][alt_text]" maxlength="255" value="<?= esc((string) $im['alt_text']) ?>"></div>
              </div>
              <label class="flex items-center gap-2 text-[11px] text-rose-700"><input type="checkbox" name="gallery_remove[]" value="<?= $iid ?>"> Remove</label>
            </div>
          <?php endforeach; ?>
          <div class="grid sm:grid-cols-3 gap-3 pt-3 border-t border-slate-100">
            <div><label class="<?= $lbl ?>" for="gn-file">Add image</label><input id="gn-file" type="file" name="gallery_new[]" accept="image/jpeg,image/png,image/webp" multiple class="text-xs"></div>
            <div><label class="<?= $lbl ?>" for="gn-cap">Caption (first new image)</label><input id="gn-cap" class="<?= $in ?>" name="gallery_new_caption[]" maxlength="255"></div>
            <div><label class="<?= $lbl ?>" for="gn-alt">Alt text (first new image)</label><input id="gn-alt" class="<?= $in ?>" name="gallery_new_alt[]" maxlength="255"></div>
          </div>
        </div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="cs-pub">
        <h2 id="cs-pub" class="text-sm font-extrabold text-slate-900">4. Publishing and SEO</h2>
        <div class="grid sm:grid-cols-3 gap-4">
          <div><label class="<?= $lbl ?>" for="cs-status">Status</label>
            <select id="cs-status" name="status" class="<?= $in ?>"><?php foreach ($statuses as $k => $label): ?><option value="<?= esc($k) ?>" <?= $v('status', 'draft') === $k ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
          <div><label class="<?= $lbl ?>" for="cs-order">Display order</label><input id="cs-order" type="number" class="<?= $in ?>" name="display_order" value="<?= $val('display_order', '0') ?>"></div>
          <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 pt-6"><input type="checkbox" name="is_featured" value="1" <?= $chk('is_featured') ?>> Feature on the homepage</label>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="cs-st">SEO title</label><input id="cs-st" class="<?= $in ?>" name="seo_title" maxlength="200" value="<?= $val('seo_title') ?>"></div>
          <div><label class="<?= $lbl ?>" for="cs-sd">SEO description</label><input id="cs-sd" class="<?= $in ?>" name="seo_description" maxlength="320" value="<?= $val('seo_description') ?>"></div>
        </div>
      </section>
    </fieldset>

    <?php if ($canManage): ?>
      <div class="flex items-center gap-3 sticky bottom-0 bg-slate-50/95 backdrop-blur py-3 border-t border-slate-200">
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:opacity-90 transition">Save case study</button>
        <a href="<?= base_url('admin/case-studies') ?>" class="text-xs text-slate-500 hover:text-slate-700">Cancel</a>
      </div>
    <?php endif; ?>
  </form>
</div>
