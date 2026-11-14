<?php /** @var array $p $categories $taxes $models @var int|null $id */
$v = static fn (string $k, $d = '') => crm_old($k, $p[$k] ?? $d); ?>
<div class="pagehead"><h1><?= $id ? 'Edit product' : 'New product' ?></h1><a class="btn alt" href="<?= site_url($id ? 'crm/products/' . $id : 'crm/products') ?>">Cancel</a></div>
<form class="card" method="post" enctype="multipart/form-data" data-validate action="<?= site_url($id ? 'crm/products/' . $id . '/save' : 'crm/products/save') ?>">
<?= csrf_field() ?><div class="bd form">
  <div><label class="req" for="sku">SKU</label><input id="sku" name="sku" required maxlength="40" value="<?= esc($v('sku')) ?>"><?= crm_err('sku') ?></div>
  <div><label class="req" for="name">Name</label><input id="name" name="name" required maxlength="200" value="<?= esc($v('name')) ?>"><?= crm_err('name') ?></div>
  <div><label for="brand">Brand</label><input id="brand" name="brand" maxlength="80" value="<?= esc($v('brand')) ?>"></div>
  <div><label class="req" for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Select…</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= crm_sel($v('category_id'), $c['id']) ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select><?= crm_err('category_id') ?></div>
  <div><label for="pricing_model">Pricing model</label><select id="pricing_model" name="pricing_model">
    <?php foreach ($models as $m): ?><option value="<?= esc($m) ?>"<?= crm_sel($v('pricing_model'), $m) ?>><?= esc(crm_label($m)) ?></option><?php endforeach; ?></select>
    <div class="hint">unit/qty = per piece · sqft = per square foot · rft = per running foot · package = fixed bundle</div><?= crm_err('pricing_model') ?></div>
  <div><label for="unit">Unit</label><input id="unit" name="unit" list="units" maxlength="20" value="<?= esc($v('unit', 'nos')) ?>"><datalist id="units"><option>nos</option><option>sqft</option><option>rft</option><option>set</option></datalist></div>
  <div><label class="req" for="base_price">Base price (₹)</label><input id="base_price" name="base_price" type="number" step="0.01" min="0" required value="<?= esc($v('base_price')) ?>"><?= crm_err('base_price') ?></div>
  <div><label class="req" for="min_price">Minimum selling price (₹)</label><input id="min_price" name="min_price" type="number" step="0.01" min="0" required value="<?= esc($v('min_price')) ?>"><?= crm_err('min_price') ?></div>
  <div><label class="req" for="standard_price">Standard selling price (₹)</label><input id="standard_price" name="standard_price" type="number" step="0.01" min="0" required value="<?= esc($v('standard_price')) ?>"><?= crm_err('standard_price') ?></div>
  <div><label class="req" for="tax_rate_id">Tax rate</label><select id="tax_rate_id" name="tax_rate_id" required>
    <?php foreach ($taxes as $t): ?><option value="<?= (int) $t['id'] ?>"<?= crm_sel($v('tax_rate_id', $t['is_default'] ? $t['id'] : ''), $t['id']) ?>><?= esc($t['name']) ?> (<?= esc(rtrim(rtrim((string) $t['rate'], '0'), '.')) ?>%)</option><?php endforeach; ?></select><?= crm_err('tax_rate_id') ?></div>
  <div class="full"><label for="description">Description</label><textarea id="description" name="description"><?= esc($v('description')) ?></textarea></div>
  <div class="full"><label for="specifications">Specifications</label><textarea id="specifications" name="specifications"><?= esc($v('specifications')) ?></textarea></div>
  <div><label for="image_file">Image (JPG/PNG/WebP, max 2 MB)</label><input id="image_file" name="image_file" type="file" accept=".jpg,.jpeg,.png,.webp"><?= crm_err('image_file') ?>
    <?php if (! empty($p['image'])): ?><div class="hint">Current: <?= esc($p['image']) ?></div><?php endif; ?></div>
  <div><label>&nbsp;</label><label style="font-weight:500"><input type="checkbox" name="is_active" value="1" style="width:auto;min-height:0"<?= (int) ($p['is_active'] ?? 1) && (old('sku') === null || old('is_active')) ? ' checked' : '' ?>> Active (available in quotations)</label></div>
</div>
<div class="bd" style="border-top:1px solid var(--line-2)"><button class="btn teal" type="submit"><?= $id ? 'Save changes' : 'Create product' ?></button></div></form>
