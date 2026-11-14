<?php
/** @var array|null $product @var bool $canManage @var array $formErrors @var array $ctaTypes @var array $statuses @var callable $assetUrl */
$isNew = empty($product);
$id    = (int) ($product['id'] ?? 0);
$P     = $product ?? [];
$v     = static fn (string $k, $default = '') => old($k) ?? ($P[$k] ?? $default);
$val   = static fn (string $k, $d = '') => esc((string) $v($k, $d));
$chk   = static fn (string $k) => (old($k) !== null ? !empty(old($k)) : !empty($P[$k] ?? 0)) ? 'checked' : '';
$err   = static fn (string $k) => isset($formErrors[$k]) ? '<p class="text-[11px] text-rose-600 font-semibold mt-1" id="err-' . esc($k, 'attr') . '">' . esc($formErrors[$k]) . '</p>' : '';
$in    = 'w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:outline-none focus:border-brand-primary bg-white';
$lbl   = 'block text-[11px] font-bold text-slate-700 mb-1';
$card  = 'bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4';
$hint  = 'text-[11px] text-slate-500 mt-1';
$rows  = static fn (string $k) => old($k) ?? ($P[$k] ?? []);
$repeaters = [
    'features'  => ['Key features', 'Shown as feature cards.', [['title', 'Title', 160], ['description', 'Description', 600], ['icon', 'Icon (users, clock, calendar, wallet, layers, briefcase, target, file, shield, lock)', 40]]],
    'modules'   => ['Modules', 'Areas or modules the product covers (flagship layout shows them as a numbered grid).', [['title', 'Title', 160], ['description', 'Description', 600]]],
    'benefits'  => ['Benefits', 'Fill "Audience" (for example Employees, Managers) to group benefits by role on the flagship layout.', [['title', 'Title', 160], ['description', 'Description', 600], ['audience', 'Audience / role', 80]]],
    'audiences' => ['Who it is for', 'Use cases or user groups.', [['title', 'Title', 160], ['description', 'Description', 600]]],
    'faqs'      => ['FAQs', 'Also published as FAQ structured data for search engines.', [['question', 'Question', 255], ['answer', 'Answer', 4000]]],
    'sections'  => ['Extra sections', 'Optional free-text sections (plain text, blank line = new paragraph).', [['title', 'Section title', 160], ['body', 'Text', 4000]]],
];
?>
<div class="space-y-6 max-w-5xl">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <a href="<?= base_url('admin/products') ?>" class="text-xs text-slate-500 hover:text-slate-700">&larr; All products</a>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1"><?= $isNew ? 'New product' : 'Edit: ' . esc($product['name']) ?></h1>
      <?php if (!$isNew): ?><p class="text-xs text-slate-500">Status: <strong><?= esc($statuses[$product['status']] ?? $product['status']) ?></strong> · <a class="underline" href="<?= base_url('admin/products/' . $id . '/preview') ?>" target="_blank" rel="noopener">Preview</a><?php if ($product['status'] === 'published'): ?> · <a class="underline" href="<?= base_url('products/' . $product['slug']) ?>" target="_blank" rel="noopener">View live</a><?php endif; ?></p><?php endif; ?>
    </div>
  </div>

  <?php if ($formErrors): ?>
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-700" role="alert">
      <p class="font-bold mb-1">Please fix the following:</p>
      <ul class="list-disc pl-5"><?php foreach ($formErrors as $m): ?><li><?= esc($m) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form action="<?= base_url($isNew ? 'admin/products/create' : 'admin/products/' . $id . '/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-6" id="product-form">
    <?= csrf_field() ?>
    <fieldset <?= $canManage ? '' : 'disabled' ?> class="space-y-6 min-w-0">

      <section class="<?= $card ?>" aria-labelledby="sec-basics">
        <h2 id="sec-basics" class="text-sm font-extrabold text-slate-900">1. Basics</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="f-name">Product name *</label><input id="f-name" class="<?= $in ?>" name="name" required maxlength="150" value="<?= $val('name') ?>"><?= $err('name') ?></div>
          <div><label class="<?= $lbl ?>" for="f-slug">URL slug</label><input id="f-slug" class="<?= $in ?>" name="slug" maxlength="100" pattern="[a-z0-9\-]*" value="<?= $val('slug') ?>" placeholder="auto-generated from the name">
            <p class="<?= $hint ?>">Page address: /products/<em>slug</em>. Changing it on a published product breaks old links.</p></div>
          <div><label class="<?= $lbl ?>" for="f-tagline">Tagline</label><input id="f-tagline" class="<?= $in ?>" name="tagline" maxlength="255" value="<?= $val('tagline') ?>"></div>
          <div><label class="<?= $lbl ?>" for="f-category">Category</label><input id="f-category" class="<?= $in ?>" name="category" maxlength="80" value="<?= $val('category') ?>" placeholder="e.g. HRMS &amp; Payroll"></div>
        </div>
        <div><label class="<?= $lbl ?>" for="f-short">Short description (card and hero text; required to publish)</label>
          <textarea id="f-short" class="<?= $in ?>" name="short_description" rows="3" maxlength="600"><?= $val('short_description') ?></textarea><?= $err('short_description') ?></div>
        <div><label class="<?= $lbl ?>" for="f-long">Overview (plain text; a blank line starts a new paragraph)</label>
          <textarea id="f-long" class="<?= $in ?>" name="long_description" rows="7" maxlength="8000"><?= $val('long_description') ?></textarea></div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="sec-vis">
        <h2 id="sec-vis" class="text-sm font-extrabold text-slate-900">2. Visibility and order</h2>
        <div class="grid sm:grid-cols-3 gap-4">
          <div><label class="<?= $lbl ?>" for="f-status">Status</label>
            <select id="f-status" class="<?= $in ?>" name="status"><?php foreach ($statuses as $k => $label): ?><option value="<?= $k ?>" <?= (string) $v('status', 'draft') === $k ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select>
            <p class="<?= $hint ?>">Only Published products are visible to visitors.</p></div>
          <div><label class="<?= $lbl ?>" for="f-order">Display order</label><input id="f-order" type="number" min="0" max="9999" class="<?= $in ?>" name="display_order" value="<?= $val('display_order', '0') ?>"></div>
          <div class="space-y-2 pt-5">
            <label class="flex items-center gap-2 text-xs font-medium text-slate-700"><input type="checkbox" name="is_featured" value="1" <?= $chk('is_featured') ?>> Featured</label>
            <label class="flex items-center gap-2 text-xs font-medium text-slate-700"><input type="checkbox" name="is_flagship" value="1" <?= $chk('is_flagship') ?>> Flagship (rich layout)</label>
          </div>
        </div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="sec-cta">
        <h2 id="sec-cta" class="text-sm font-extrabold text-slate-900">3. Buttons and links</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="f-ctatype">Primary button</label>
            <select id="f-ctatype" class="<?= $in ?>" name="primary_cta_type"><?php foreach ($ctaTypes as $k => $label): ?><option value="<?= $k ?>" <?= (string) $v('primary_cta_type', 'enquiry') === $k ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
          <div><label class="<?= $lbl ?>" for="f-ctalabel">Button label</label><input id="f-ctalabel" class="<?= $in ?>" name="cta_label" maxlength="80" value="<?= $val('cta_label') ?>" placeholder="e.g. Start free 7-day demo"></div>
          <div><label class="<?= $lbl ?>" for="f-signup">Sign-up URL (demo button)</label><input id="f-signup" type="url" class="<?= $in ?>" name="signup_url" maxlength="500" value="<?= $val('signup_url') ?>" placeholder="https://..."><?= $err('signup_url') ?></div>
          <div><label class="<?= $lbl ?>" for="f-demo">Demo URL</label><input id="f-demo" type="url" class="<?= $in ?>" name="demo_url" maxlength="500" value="<?= $val('demo_url') ?>"><?= $err('demo_url') ?></div>
          <div><label class="<?= $lbl ?>" for="f-ctaurl">Custom button link</label><input id="f-ctaurl" class="<?= $in ?>" name="cta_url" maxlength="500" value="<?= $val('cta_url') ?>" placeholder="https://... or /contact"><?= $err('cta_url') ?></div>
          <div><label class="<?= $lbl ?>" for="f-docs">Documentation URL</label><input id="f-docs" type="url" class="<?= $in ?>" name="docs_url" maxlength="500" value="<?= $val('docs_url') ?>"><?= $err('docs_url') ?></div>
        </div>
        <div><label class="<?= $lbl ?>" for="f-price">Pricing note</label><input id="f-price" class="<?= $in ?>" name="pricing_summary" maxlength="255" value="<?= $val('pricing_summary') ?>" placeholder="Only state what is true, e.g. Talk to us for plans">
          <p class="<?= $hint ?>">Only http(s) links are accepted. Do not state prices, customer numbers or certifications unless they are true and current.</p></div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="sec-img">
        <h2 id="sec-img" class="text-sm font-extrabold text-slate-900">4. Images and video</h2>
        <p class="<?= $hint ?>">JPG, PNG or WebP only. Logo up to 3 MB, other images up to 5 MB, maximum 6000 px per side. SVG is not accepted. Use real images of the product only.</p>
        <div class="grid sm:grid-cols-3 gap-4">
          <?php foreach (['logo' => ['Logo', 'logo_path'], 'hero' => ['Hero image', 'hero_image_path'], 'og' => ['Social share image (1200x630 recommended)', 'og_image_path']] as $f => [$label, $col]): $cur = $assetUrl($product[$col] ?? null); ?>
            <div>
              <label class="<?= $lbl ?>" for="f-<?= $f ?>"><?= esc($label) ?></label>
              <?php if ($cur): ?><img src="<?= esc($cur) ?>" alt="Current <?= esc(strtolower($label)) ?>" class="h-20 w-auto max-w-full rounded-lg border border-slate-200 mb-2 bg-slate-50 object-contain">
                <label class="flex items-center gap-2 text-[11px] text-slate-600 mb-2"><input type="checkbox" name="remove_<?= $f ?>" value="1"> Remove current image</label><?php endif; ?>
              <input id="f-<?= $f ?>" type="file" name="<?= $f ?>_file" accept="image/jpeg,image/png,image/webp" class="text-[11px] w-full">
            </div>
          <?php endforeach; ?>
        </div>
        <div><label class="<?= $lbl ?>" for="f-video">Video URL (YouTube or Vimeo only)</label><input id="f-video" type="url" class="<?= $in ?>" name="hero_video_url" maxlength="500" value="<?= $val('hero_video_url') ?>" placeholder="https://www.youtube.com/watch?v=..."><?= $err('hero_video_url') ?></div>
      </section>

      <?php $n = 5; foreach ($repeaters as $key => [$title, $help, $fields]): ?>
        <section class="<?= $card ?>" aria-labelledby="sec-<?= $key ?>" data-repeater="<?= $key ?>">
          <div><h2 id="sec-<?= $key ?>" class="text-sm font-extrabold text-slate-900"><?= $n++ ?>. <?= esc($title) ?></h2><p class="<?= $hint ?>"><?= esc($help) ?></p></div>
          <div class="space-y-3" data-rows>
            <?php foreach ((array) $rows($key) as $i => $row): ?>
              <div class="rounded-xl border border-slate-200 p-3 grid gap-2 sm:grid-cols-2" data-row>
                <?php foreach ($fields as [$fk, $fl, $max]): $long = $max > 200; ?>
                  <div class="<?= $long ? 'sm:col-span-2' : '' ?>"><label class="<?= $lbl ?>"><?= esc($fl) ?></label>
                    <?php if ($long): ?><textarea class="<?= $in ?>" name="<?= $key ?>[<?= (int) $i ?>][<?= $fk ?>]" rows="2" maxlength="<?= $max ?>"><?= esc((string) ($row[$fk] ?? '')) ?></textarea>
                    <?php else: ?><input class="<?= $in ?>" name="<?= $key ?>[<?= (int) $i ?>][<?= $fk ?>]" maxlength="<?= $max ?>" value="<?= esc((string) ($row[$fk] ?? '')) ?>"><?php endif; ?></div>
                <?php endforeach; ?>
                <div class="sm:col-span-2"><button type="button" class="text-[11px] font-semibold text-rose-600 hover:underline" data-remove>Remove this item</button></div>
              </div>
            <?php endforeach; ?>
          </div>
          <template data-template>
            <div class="rounded-xl border border-slate-200 p-3 grid gap-2 sm:grid-cols-2" data-row>
              <?php foreach ($fields as [$fk, $fl, $max]): $long = $max > 200; ?>
                <div class="<?= $long ? 'sm:col-span-2' : '' ?>"><label class="<?= $lbl ?>"><?= esc($fl) ?></label>
                  <?php if ($long): ?><textarea class="<?= $in ?>" name="<?= $key ?>[__i__][<?= $fk ?>]" rows="2" maxlength="<?= $max ?>"></textarea>
                  <?php else: ?><input class="<?= $in ?>" name="<?= $key ?>[__i__][<?= $fk ?>]" maxlength="<?= $max ?>"><?php endif; ?></div>
              <?php endforeach; ?>
              <div class="sm:col-span-2"><button type="button" class="text-[11px] font-semibold text-rose-600 hover:underline" data-remove>Remove this item</button></div>
            </div>
          </template>
          <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 text-[11px] font-semibold text-slate-700 hover:bg-slate-50" data-add>+ Add item</button>
        </section>
      <?php endforeach; ?>

      <section class="<?= $card ?>" aria-labelledby="sec-media" data-repeater="media">
        <div><h2 id="sec-media" class="text-sm font-extrabold text-slate-900"><?= $n++ ?>. Screenshots and videos</h2>
          <p class="<?= $hint ?>">Only real screenshots of the product. Alt text describes the image for screen-reader users.</p></div>
        <div class="space-y-3" data-rows>
          <?php foreach ((array) $rows('media') as $i => $m): $isVideo = ($m['kind'] ?? '') === 'video'; $u = $isVideo ? null : $assetUrl($m['path_or_url'] ?? null); ?>
            <div class="rounded-xl border border-slate-200 p-3 grid gap-2 sm:grid-cols-2" data-row>
              <input type="hidden" name="media[<?= (int) $i ?>][kind]" value="<?= esc($isVideo ? 'video' : (($m['kind'] ?? '') === 'gallery' ? 'gallery' : 'screenshot')) ?>">
              <?php if ($isVideo): ?>
                <div class="sm:col-span-2"><label class="<?= $lbl ?>">Video URL (YouTube / Vimeo)</label><input class="<?= $in ?>" name="media[<?= (int) $i ?>][path_or_url]" value="<?= esc((string) $m['path_or_url']) ?>"></div>
              <?php else: ?>
                <input type="hidden" name="media[<?= (int) $i ?>][path_or_url]" value="<?= esc((string) ($m['path_or_url'] ?? '')) ?>">
                <?php if ($u): ?><div class="sm:col-span-2"><img src="<?= esc($u) ?>" alt="" class="h-24 w-auto rounded-lg border border-slate-200 bg-slate-50" loading="lazy"></div><?php endif; ?>
              <?php endif; ?>
              <div><label class="<?= $lbl ?>">Caption</label><input class="<?= $in ?>" name="media[<?= (int) $i ?>][caption]" maxlength="255" value="<?= esc((string) ($m['caption'] ?? '')) ?>"></div>
              <div><label class="<?= $lbl ?>">Alt text</label><input class="<?= $in ?>" name="media[<?= (int) $i ?>][alt_text]" maxlength="255" value="<?= esc((string) ($m['alt_text'] ?? '')) ?>"></div>
              <div class="sm:col-span-2"><button type="button" class="text-[11px] font-semibold text-rose-600 hover:underline" data-remove>Remove (image file is deleted on save)</button></div>
            </div>
          <?php endforeach; ?>
        </div>
        <template data-template>
          <div class="rounded-xl border border-slate-200 p-3 grid gap-2 sm:grid-cols-2" data-row>
            <input type="hidden" name="media[__i__][kind]" value="video">
            <div class="sm:col-span-2"><label class="<?= $lbl ?>">Video URL (YouTube / Vimeo)</label><input class="<?= $in ?>" name="media[__i__][path_or_url]" placeholder="https://www.youtube.com/watch?v=..."></div>
            <div><label class="<?= $lbl ?>">Caption</label><input class="<?= $in ?>" name="media[__i__][caption]" maxlength="255"></div>
            <div><label class="<?= $lbl ?>">Alt text</label><input class="<?= $in ?>" name="media[__i__][alt_text]" maxlength="255"></div>
            <div class="sm:col-span-2"><button type="button" class="text-[11px] font-semibold text-rose-600 hover:underline" data-remove>Remove this item</button></div>
          </div>
        </template>
        <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-300 text-[11px] font-semibold text-slate-700 hover:bg-slate-50" data-add>+ Add video link</button>

        <div class="border-t border-slate-100 pt-4 space-y-3" id="new-shots">
          <h3 class="text-xs font-bold text-slate-800">Upload new screenshots</h3>
          <div data-shot-rows class="space-y-3"></div>
          <template id="shot-template">
            <div class="rounded-xl border border-slate-200 p-3 grid gap-2 sm:grid-cols-3" data-shot>
              <div><label class="<?= $lbl ?>">Image (JPG, PNG, WebP, up to 5 MB)</label><input type="file" name="media_new[]" accept="image/jpeg,image/png,image/webp" class="text-[11px] w-full"></div>
              <div><label class="<?= $lbl ?>">Caption</label><input class="<?= $in ?>" name="media_new_caption[]" maxlength="255"></div>
              <div><label class="<?= $lbl ?>">Alt text</label><input class="<?= $in ?>" name="media_new_alt[]" maxlength="255"></div>
              <div class="sm:col-span-3"><button type="button" class="text-[11px] font-semibold text-rose-600 hover:underline" data-remove-shot>Remove</button></div>
            </div>
          </template>
          <button type="button" id="add-shot" class="px-3 py-1.5 rounded-lg border border-slate-300 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">+ Add screenshot upload</button>
        </div>
      </section>

      <section class="<?= $card ?>" aria-labelledby="sec-seo">
        <h2 id="sec-seo" class="text-sm font-extrabold text-slate-900"><?= $n++ ?>. SEO</h2>
        <div class="grid sm:grid-cols-2 gap-4">
          <div><label class="<?= $lbl ?>" for="f-seot">SEO title</label><input id="f-seot" class="<?= $in ?>" name="seo_title" maxlength="255" value="<?= $val('seo_title') ?>"><p class="<?= $hint ?>">Aim for under 60 characters. Falls back to the product name.</p></div>
          <div><label class="<?= $lbl ?>" for="f-canon">Canonical URL (optional)</label><input id="f-canon" type="url" class="<?= $in ?>" name="canonical_url" maxlength="500" value="<?= $val('canonical_url') ?>"><?= $err('canonical_url') ?><p class="<?= $hint ?>">Leave empty to use the product page address.</p></div>
        </div>
        <div><label class="<?= $lbl ?>" for="f-seod">Meta description</label><textarea id="f-seod" class="<?= $in ?>" name="seo_description" rows="2" maxlength="320"><?= $val('seo_description') ?></textarea><p class="<?= $hint ?>">Aim for 120-160 characters. Falls back to the short description.</p></div>
        <label class="flex items-center gap-2 text-xs font-medium text-slate-700"><input type="checkbox" name="no_index" value="1" <?= $chk('no_index') ?>> Ask search engines not to index this page (also removed from the sitemap)</label>
      </section>
    </fieldset>

    <div class="sticky bottom-0 bg-white/95 backdrop-blur border-t border-slate-200 -mx-1 px-1 py-3 flex flex-wrap items-center gap-3">
      <?php if ($canManage): ?><button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:opacity-90 transition" id="save-btn">Save product</button><?php endif; ?>
      <a href="<?= base_url('admin/products') ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold">Back to list</a>
      <?php if (!$canManage): ?><span class="text-xs text-slate-500">You have view-only access to products.</span><?php endif; ?>
    </div>
  </form>
</div>

<script>
(function () {
  var form = document.getElementById('product-form');
  if (!form) return;
  var counters = {};
  function nextIndex(box, key) {
    if (!(key in counters)) {
      var max = -1;
      box.querySelectorAll('[name^="' + key + '["]').forEach(function (el) {
        var m = el.name.match(/^[a-z_]+\[(\d+)\]/); if (m) max = Math.max(max, +m[1]);
      });
      counters[key] = max + 1;
    }
    return counters[key]++;
  }
  form.querySelectorAll('[data-repeater]').forEach(function (sec) {
    var key = sec.getAttribute('data-repeater');
    var rows = sec.querySelector('[data-rows]');
    var tpl = sec.querySelector('template[data-template]');
    var add = sec.querySelector('[data-add]');
    if (add) add.addEventListener('click', function () {
      var html = tpl.innerHTML.replace(/__i__/g, nextIndex(sec, key));
      var wrap = document.createElement('div'); wrap.innerHTML = html.trim();
      rows.appendChild(wrap.firstElementChild);
      var f = rows.lastElementChild.querySelector('input,textarea'); if (f) f.focus();
    });
    sec.addEventListener('click', function (e) {
      var b = e.target.closest('[data-remove]'); if (!b) return;
      var row = b.closest('[data-row]'); if (row) row.remove();
    });
  });
  var shotRows = form.querySelector('[data-shot-rows]');
  document.getElementById('add-shot').addEventListener('click', function () {
    var wrap = document.createElement('div'); wrap.innerHTML = document.getElementById('shot-template').innerHTML.trim();
    shotRows.appendChild(wrap.firstElementChild);
  });
  shotRows.addEventListener('click', function (e) {
    var b = e.target.closest('[data-remove-shot]'); if (b) b.closest('[data-shot]').remove();
  });
  // Prevent duplicate submissions.
  form.addEventListener('submit', function () {
    var s = document.getElementById('save-btn'); if (s) { s.disabled = true; s.textContent = 'Saving...'; }
  });
})();
</script>
