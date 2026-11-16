<?php
/** @var list<array> $rows @var int $total @var int $per @var int $page @var array $g @var list<string> $cities @var list<array> $sources @var list<array> $owners */
$types = ['individual', 'company', 'architect', 'builder', 'other'];
$exportQs = http_build_query(array_filter($g, static fn ($v, $k) => $k !== 'page' && $v !== '', ARRAY_FILTER_USE_BOTH));
?>
<div class="pagehead"><h1>Customers <span class="muted small">(<?= number_format($total) ?>)</span></h1>
  <div class="gap">
    <?php if (crm_can('customers.export')): ?>
      <a class="btn alt" href="<?= site_url('crm/customers/export?' . $exportQs) ?>">Export Excel</a>
      <a class="btn alt" href="<?= site_url('crm/customers/export?' . $exportQs . '&format=csv') ?>">Export CSV</a>
    <?php endif; ?>
    <?php if (crm_can('customers.import')): ?><a class="btn alt" href="<?= site_url('crm/imports') ?>">Import</a><?php endif; ?>
    <?php if (crm_can('customers.create')): ?><a class="btn teal" href="<?= site_url('crm/customers/new') ?>">+ New customer</a><?php endif; ?>
  </div>
</div>
<form class="filters card" style="padding:14px" method="get" action="<?= site_url('crm/customers') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($g['q'] ?? '') ?>" placeholder="Name, number, mobile, email"></div>
  <div class="f"><label for="st">Status</label><select id="st" name="status"><option value="">All</option><?php foreach (['active', 'inactive', 'archived'] as $s): ?><option value="<?= $s ?>"<?= crm_sel($g['status'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="ty">Type</label><select id="ty" name="customer_type"><option value="">All</option><?php foreach ($types as $s): ?><option value="<?= $s ?>"<?= crm_sel($g['customer_type'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="ci">City</label><select id="ci" name="city"><option value="">All</option><?php foreach ($cities as $s): ?><option<?= crm_sel($g['city'] ?? '', $s) ?>><?= esc($s) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="so">Source</label><select id="so" name="source_id"><option value="">All</option><?php foreach ($sources as $s): ?><option value="<?= (int) $s['id'] ?>"<?= crm_sel($g['source_id'] ?? '', $s['id']) ?>><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="ow">Owner</label><select id="ow" name="owner_user_id"><option value="">All</option><?php foreach ($owners as $s): ?><option value="<?= (int) $s['id'] ?>"<?= crm_sel($g['owner_user_id'] ?? '', $s['id']) ?>><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/customers') ?>">Reset</a></div>
</form>
<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong>No customers match</strong>Try clearing the filters<?= crm_can('customers.create') ? ' or add a new customer.' : '.' ?></div>
<?php else: ?>
<div class="tablewrap"><table class="t"><thead><tr><th>Customer</th><th>Contact</th><th>City</th><th>Source</th><th>Owner</th><th class="num">Enquiries</th><th>Status</th><th>Created</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
  <tr><td><a href="<?= site_url('crm/customers/' . $r['id']) ?>"><strong><?= esc($r['name']) ?></strong></a><div class="muted small"><?= esc($r['customer_no']) ?> · <?= esc(crm_label($r['customer_type'])) ?><?= $r['company_name'] ? ' · ' . esc($r['company_name']) : '' ?></div></td>
    <td><?= esc($r['mobile']) ?><div class="muted small"><?= esc($r['email'] ?? '') ?></div></td><td><?= esc($r['city'] ?? '—') ?></td><td><?= esc($r['source_name'] ?? '—') ?></td><td><?= esc($r['owner_name'] ?? '—') ?></td>
    <td class="num"><?= (int) $r['enquiry_count'] ?></td><td><?= crm_badge($r['status']) ?></td><td class="nowrap"><?= esc(crm_date($r['created_at'])) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?= view('Crm\Views\partials\wa_pager', compact('total', 'per', 'page')) ?>
<?php endif; ?>
</div></div>
