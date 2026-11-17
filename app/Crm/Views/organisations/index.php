<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $filters @var list<array> $owners @var list<array> $territories */
$types = ['Individual', 'Corporate', 'Builder / Developer', 'Architect', 'Interior Designer', 'Contractor', 'Dealer / Distributor', 'Institutional', 'Referral Partner', 'Other'];
$statuses = ['active', 'inactive', 'on_hold', 'archived'];
$ratings = ['Hot', 'Warm', 'Cold'];
?>
<div class="pagehead">
  <div>
    <h1>Organisations &amp; Business Accounts <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">Primary B2B accounts, developers, architects &amp; institutional clients with 360° relationship history.</div>
  </div>
  <div class="gap">
    <form method="post" action="<?= site_url('crm/organisations/sync') ?>" style="display:inline" data-confirm="Sync all customers without an organisation into the Organisations registry?">
      <?= csrf_field() ?>
      <button class="btn alt" type="submit" title="Sync legacy customers into organisations">⚡ Sync from Customers</button>
    </form>
    <?php if (crm_can('customers.create')): ?>
      <a class="btn teal" href="<?= site_url('crm/organisations/new') ?>">+ New Organisation</a>
    <?php endif; ?>
  </div>
</div>

<form class="filters card" style="padding:14px;margin-bottom:18px" method="get" action="<?= site_url('crm/organisations') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Name, Org #, email, phone, GSTIN"></div>
  <div class="f"><label for="type">Org Type</label><select id="type" name="type"><option value="">All Types</option><?php foreach ($types as $t): ?><option value="<?= esc($t) ?>"<?= crm_sel($filters['type'] ?? '', $t) ?>><?= esc($t) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="status">Status</label><select id="status" name="status"><option value="">All Statuses</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>"<?= crm_sel($filters['status'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="rating">Rating</label><select id="rating" name="rating"><option value="">All Ratings</option><?php foreach ($ratings as $r): ?><option value="<?= $r ?>"<?= crm_sel($filters['rating'] ?? '', $r) ?>><?= esc($r) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="owner">Account Owner</label><select id="owner" name="owner_user_id"><option value="">All Owners</option><?php foreach ($owners as $o): ?><option value="<?= (int) $o['id'] ?>"<?= crm_sel($filters['owner_user_id'] ?? '', $o['id']) ?>><?= esc($o['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/organisations') ?>">Reset</a></div>
</form>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No organisations match</strong>Try clearing filters or register a new business organisation.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Organisation</th>
          <th>Type / Industry</th>
          <th>Primary Contact</th>
          <th>Location</th>
          <th>GSTIN / PAN</th>
          <th>Owner / Rating</th>
          <th class="num">Contacts</th>
          <th class="num">Projects</th>
          <th class="num">Orders</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <a href="<?= site_url('crm/organisations/' . $r['id']) ?>"><strong><?= esc($r['name']) ?></strong></a>
            <div class="muted small"><?= esc($r['org_no']) ?><?= ! empty($r['website']) ? ' · <a href="' . esc($r['website']) . '" target="_blank" rel="noopener">' . esc($r['website']) . '</a>' : '' ?></div>
          </td>
          <td>
            <div><?= esc($r['type'] ?? 'Corporate') ?></div>
            <div class="muted small"><?= esc($r['industry'] ?? 'Real Estate / Interior') ?></div>
          </td>
          <td>
            <div><strong><?= esc($r['primary_contact_name'] ?? '—') ?></strong></div>
            <div class="muted small"><?= esc($r['primary_phone'] ?? '') ?><?= $r['primary_email'] ? ' · ' . esc($r['primary_email']) : '' ?></div>
          </td>
          <td>
            <div><?= esc($r['city'] ?? '—') ?></div>
            <div class="muted small"><?= esc($r['state'] ?? '') ?></div>
          </td>
          <td>
            <div><?= esc($r['gstin'] ?: '—') ?></div>
            <div class="muted small"><?= esc($r['pan'] ?: '') ?></div>
          </td>
          <td>
            <div><?= esc($r['owner_name'] ?? '—') ?></div>
            <?php if (! empty($r['account_rating'])): ?>
              <span class="badge badge-<?= match($r['account_rating']) { 'Hot' => 'red', 'Warm' => 'amber', default => 'blue' } ?>"><?= esc($r['account_rating']) ?></span>
            <?php endif; ?>
          </td>
          <td class="num"><a href="<?= site_url('crm/organisations/' . $r['id'] . '?tab=contacts') ?>"><?= (int) ($r['contact_count'] ?? 0) ?></a></td>
          <td class="num"><a href="<?= site_url('crm/organisations/' . $r['id'] . '?tab=projects') ?>"><?= (int) ($r['project_count'] ?? 0) ?></a></td>
          <td class="num"><a href="<?= site_url('crm/organisations/' . $r['id'] . '?tab=orders') ?>"><strong><?= (int) ($r['order_count'] ?? 0) ?></strong></a></td>
          <td><?= crm_badge($r['status'] ?? 'active') ?></td>
          <td class="nowrap">
            <a class="btn sm alt" href="<?= site_url('crm/organisations/' . $r['id']) ?>">View 360°</a>
            <?php if (crm_can('customers.edit')): ?>
              <a class="btn sm alt" href="<?= site_url('crm/organisations/' . $r['id'] . '/edit') ?>">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $perPage, 'page' => $page]) ?>
<?php endif; ?>
</div></div>
