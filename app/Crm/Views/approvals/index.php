<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $counts @var string $activeTab @var array $filters */
$tabs = [
    'pending' => 'Pending Approval (' . (int) ($counts['pending'] ?? 0) . ')',
    'my_queue' => 'Action Required by Me (' . (int) ($counts['my_queue'] ?? 0) . ')',
    'approved' => 'Approved (' . (int) ($counts['approved'] ?? 0) . ')',
    'rejected' => 'Rejected (' . (int) ($counts['rejected'] ?? 0) . ')',
    'returned' => 'Returned for Revision (' . (int) ($counts['returned'] ?? 0) . ')',
    'all' => 'All Requests (' . (int) ($counts['all'] ?? 0) . ')',
];
$base = site_url('crm/approvals');
?>
<div class="pagehead">
  <div>
    <h1>Commercial Quotation Approval Centre <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">Sales Governance: Review discount concessions, target margins, and commercial authorization.</div>
  </div>
  <div class="gap">
    <?php if (crm_can('settings.manage')): ?>
      <a class="btn alt" href="<?= site_url('crm/settings/governance') ?>">⚙️ Approval Matrix Rules</a>
    <?php endif; ?>
  </div>
</div>

<nav class="tabs" aria-label="Approval queues" style="margin-bottom:16px">
<?php foreach ($tabs as $k => $label): ?>
  <a href="<?= $base ?>?tab=<?= $k ?>" class="<?= $activeTab === $k ? 'on' : '' ?>"<?= $activeTab === $k ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
<?php endforeach; ?>
</nav>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No approval requests in this queue</strong>All proposals comply with standard commercial limits or have already been decided.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Approval #</th>
          <th>Quotation #</th>
          <th>Account / Customer</th>
          <th>Requested By</th>
          <th class="num">Catalogue Price</th>
          <th class="num">Proposed Net</th>
          <th class="num">Concession %</th>
          <th>Level Required</th>
          <th>Status</th>
          <th>SLA Due</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <a href="<?= site_url('crm/approvals/' . $r['id']) ?>"><strong><?= esc($r['approval_no']) ?></strong></a>
          </td>
          <td>
            <a href="<?= site_url('crm/quotations/' . $r['quotation_id']) ?>"><?= esc($r['quotation_no']) ?></a>
            <div class="muted small">v<?= (int) ($r['version_no'] ?? 1) ?></div>
          </td>
          <td>
            <?php $orgDisplay = $r['org_name'] ?? ($r['organisation_name'] ?? ''); ?>
            <?php if (! empty($orgDisplay)): ?>
              <a href="<?= site_url('crm/organisations/' . $r['organisation_id']) ?>"><strong><?= esc($orgDisplay) ?></strong></a>
            <?php endif; ?>
            <div class="muted small"><?= esc($r['contact_name'] ?? '—') ?></div>
          </td>
          <td><?= esc($r['salesperson_name'] ?? '—') ?></td>
          <td class="num"><?= crm_money($r['standard_selling_price']) ?></td>
          <td class="num"><strong><?= crm_money($r['proposed_selling_price']) ?></strong></td>
          <td class="num">
            <span style="color:#B91C1C;font-weight:700"><?= number_format((float) $r['requested_discount_pct'], 1) ?>%</span>
            <div class="muted small">− <?= crm_money($r['requested_discount_amount']) ?></div>
          </td>
          <td>
            <span class="badge badge-blue">Level <?= (int) $r['approval_level'] ?> of <?= (int) $r['max_approval_level'] ?></span>
          </td>
          <td><?= crm_badge($r['status']) ?></td>
          <td class="nowrap">
            <?php if (! empty($r['sla_due_at'])): ?>
              <span class="<?= strtotime($r['sla_due_at']) < time() && $r['status'] === 'pending' ? 'bad' : 'muted small' ?>">
                <?= esc(crm_dt($r['sla_due_at'])) ?>
              </span>
            <?php else: ?>
              <span class="muted small">—</span>
            <?php endif; ?>
          </td>
          <td class="nowrap">
            <a class="btn sm teal" href="<?= site_url('crm/approvals/' . $r['id']) ?>">Review Proposal</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $perPage, 'page' => $page]) ?>
<?php endif; ?>
</div></div>
