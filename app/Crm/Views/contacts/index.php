<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $filters @var list<array> $organisations */
$roles = ['Owner', 'Director', 'Purchase Manager', 'Project Manager', 'Architect', 'Interior Designer', 'Finance / Accounts', 'Site Engineer', 'Procurement Executive', 'Admin', 'Other'];
?>
<div class="pagehead">
  <div>
    <h1>Contacts Directory <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">All decision makers, architects, project leads, and procurement contacts linked to organisations.</div>
  </div>
  <div class="gap">
    <?php if (crm_can('customers.create')): ?>
      <a class="btn teal" href="<?= site_url('crm/contacts/new') ?>">+ New Contact</a>
    <?php endif; ?>
  </div>
</div>

<form class="filters card" style="padding:14px;margin-bottom:18px" method="get" action="<?= site_url('crm/contacts') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Name, phone, email, designation"></div>
  <div class="f"><label for="org">Organisation</label><select id="org" name="organisation_id"><option value="">All Organisations</option><?php foreach ($organisations as $o): ?><option value="<?= (int) $o['id'] ?>"<?= crm_sel($filters['organisation_id'] ?? '', $o['id']) ?>><?= esc($o['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="role">Contact Role</label><select id="role" name="contact_role"><option value="">All Roles</option><?php foreach ($roles as $r): ?><option value="<?= esc($r) ?>"<?= crm_sel($filters['contact_role'] ?? '', $r) ?>><?= esc($r) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="dm">Decision Maker</label><select id="dm" name="is_decision_maker"><option value="">All</option><option value="1"<?= crm_sel($filters['is_decision_maker'] ?? '', '1') ?>>Decision Makers Only</option></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/contacts') ?>">Reset</a></div>
</form>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No contacts match</strong>Try clearing filters or add a new contact person.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Contact Name</th>
          <th>Organisation</th>
          <th>Designation &amp; Role</th>
          <th>Decision Maker</th>
          <th>Mobile / WhatsApp</th>
          <th>Email</th>
          <th>Pref. Channel</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <strong><?= esc($r['first_name'] . ' ' . $r['last_name']) ?></strong>
            <?php if (! empty($r['is_primary'])): ?><span class="badge badge-green">Primary</span><?php endif; ?>
            <div class="muted small"><?= esc($r['contact_no']) ?></div>
          </td>
          <td>
            <a href="<?= site_url('crm/organisations/' . $r['organisation_id']) ?>"><strong><?= esc($r['org_name'] ?? ($r['organisation_name'] ?? '—')) ?></strong></a>
            <div class="muted small"><?= esc($r['org_type'] ?? '') ?></div>
          </td>
          <td>
            <div><?= esc($r['designation'] ?? '—') ?></div>
            <div class="muted small"><?= esc(crm_label($r['contact_role'] ?? '')) ?></div>
          </td>
          <td>
            <?= ! empty($r['is_decision_maker']) ? '<span class="badge badge-blue">Key Decision Maker</span>' : 'Staff / Influencer' ?>
          </td>
          <td>
            <div><?= esc($r['mobile']) ?></div>
            <?php if (! empty($r['whatsapp'] ?? ($r['whatsapp_no'] ?? null))): ?><div class="muted small">WA: <?= esc($r['whatsapp'] ?? $r['whatsapp_no']) ?></div><?php endif; ?>
          </td>
          <td>
            <?= $r['email'] ? '<a href="mailto:' . esc($r['email']) . '">' . esc($r['email']) . '</a>' : '—' ?>
          </td>
          <td><?= esc(crm_label($r['preferred_channel'] ?? 'phone')) ?></td>
          <td><?= crm_badge($r['status'] ?? 'active') ?></td>
          <td class="nowrap">
            <?php if (crm_can('customers.edit')): ?>
              <a class="btn sm alt" href="<?= site_url('crm/contacts/' . $r['id'] . '/edit') ?>">Edit</a>
            <?php endif; ?>
            <a class="btn sm alt" href="<?= site_url('crm/organisations/' . $r['organisation_id']) ?>">View Org 360°</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= view('Crm\Views\partials\wa_pager', ['total' => $total, 'per' => $perPage, 'page' => $page]) ?>
<?php endif; ?>
</div></div>
