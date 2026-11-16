<?php
/** @var array $f @var bool $kanban @var array $statuses @var array $categories @var array $sources @var array $teams @var array $users */
$qs = static fn (array $extra = []) => '?' . http_build_query(array_merge($f, $extra));
?>
<div class="pagehead">
  <h1><?= $kanban ? 'Sales pipeline' : 'Enquiries' ?></h1>
  <div class="gap">
    <a class="btn alt" href="<?= site_url('crm/enquiries' . $qs($kanban ? [] : ['view' => 'kanban'])) ?>"><?= $kanban ? 'List view' : 'Pipeline board' ?></a>
    <?php if (crm_can('customers.import')): ?><a class="btn alt" href="<?= site_url('crm/imports') ?>">Import from Excel</a><?php endif; ?>
    <?php if (crm_can('enquiries.create')): ?><a class="btn teal" href="<?= site_url('crm/enquiries/new') ?>">+ New enquiry</a><?php endif; ?>
  </div>
</div>

<form class="filters card" method="get" action="<?= site_url('crm/enquiries') ?>" style="padding:14px 18px">
  <?php if ($kanban): ?><input type="hidden" name="view" value="kanban"><?php endif; ?>
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($f['q'] ?? '') ?>" placeholder="No., project, customer, mobile"></div>
  <?php if (! $kanban): ?>
  <div class="f"><label for="status">Status</label><select id="status" name="status"><option value="">All</option><option value="active"<?= crm_sel($f['status'] ?? '', 'active') ?>>Active only</option>
    <?php foreach ($statuses as $s): ?><option value="<?= esc($s) ?>"<?= crm_sel($f['status'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <?php endif; ?>
  <div class="f"><label for="priority">Priority</label><select id="priority" name="priority"><option value="">All</option>
    <?php foreach (['low', 'medium', 'high', 'urgent'] as $s): ?><option value="<?= $s ?>"<?= crm_sel($f['priority'] ?? '', $s) ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="owner_id">Owner</label><select id="owner_id" name="owner_id"><option value="">Anyone</option>
    <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"<?= crm_sel($f['owner_id'] ?? '', $u['id']) ?>><?= esc($u['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="team_id">Team</label><select id="team_id" name="team_id"><option value="">All</option>
    <?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"<?= crm_sel($f['team_id'] ?? '', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="source_id">Source</label><select id="source_id" name="source_id"><option value="">All</option>
    <?php foreach ($sources as $t): ?><option value="<?= (int) $t['id'] ?>"<?= crm_sel($f['source_id'] ?? '', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="">All</option>
    <?php foreach ($categories as $t): ?><option value="<?= (int) $t['id'] ?>"<?= crm_sel($f['category_id'] ?? '', $t['id']) ?>><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="outcome">Outcome</label><select id="outcome" name="outcome"><option value="">Any</option>
    <?php foreach (['won', 'lost', 'on_hold'] as $s): ?><option value="<?= $s ?>"<?= crm_sel($f['outcome'] ?? '', $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="from">Created from</label><input id="from" type="date" name="from" value="<?= esc($f['from'] ?? '') ?>"></div>
  <div class="f"><label for="to">Created to</label><input id="to" type="date" name="to" value="<?= esc($f['to'] ?? '') ?>"></div>
  <div class="f"><label>&nbsp;</label><span class="gap"><button class="btn" type="submit">Filter</button><a class="btn alt" href="<?= site_url('crm/enquiries' . ($kanban ? '?view=kanban' : '')) ?>">Reset</a></span></div>
</form>

<?php if ($kanban): ?>
  <p class="muted small">Drag a card to the next allowed stage. Moving to Won, Lost or On&nbsp;hold asks for the required details first. Each column shows the 30 most recent matching enquiries.</p>
  <div class="kanban">
    <?php foreach ($cols as $st => $col): ?>
      <section class="kcol" data-status="<?= esc($st) ?>" aria-label="<?= esc(crm_label($st)) ?>">
        <h3><span><?= esc(crm_label($st)) ?></span><span><?= (int) $col['count'] ?></span></h3>
        <?php foreach ($col['rows'] as $r): ?>
          <article class="kcard" draggable="true" data-id="<?= (int) $r['id'] ?>" data-status="<?= esc($r['status']) ?>" data-next="<?= esc(implode(',', $col['next'])) ?>" data-url="<?= site_url('crm/enquiries/' . (int) $r['id'] . '/status') ?>">
            <a class="n" href="<?= site_url('crm/enquiries/' . (int) $r['id']) ?>"><?= esc($r['enquiry_no']) ?></a> <?= crm_badge($r['priority']) ?>
            <div><?= esc($r['project_name'] ?: '—') ?></div>
            <div class="muted small"><?= esc($r['customer_name']) ?></div>
            <div class="muted small"><?= esc($r['owner_name'] ?: 'Unassigned') ?><?= $r['est_budget'] !== null ? ' · ' . crm_money0($r['est_budget']) : '' ?></div>
            <?php if (crm_can('enquiries.edit', 'enquiries.close') && $col['next']): ?>
              <form method="post" action="<?= site_url('crm/enquiries/' . (int) $r['id'] . '/status') ?>" class="gap" style="margin-top:6px"><?= csrf_field() ?>
                <label class="small muted" for="mv<?= (int) $r['id'] ?>" style="position:absolute;left:-9999px">Move to</label>
                <select id="mv<?= (int) $r['id'] ?>" name="to" style="min-height:28px;padding:2px 6px;font-size:12px"><?php foreach ($col['next'] as $n): ?><option value="<?= esc($n) ?>"><?= esc(crm_label($n)) ?></option><?php endforeach; ?></select>
                <button class="btn alt sm" type="submit">Move</button></form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
        <?php if (! $col['rows']): ?><div class="muted small" style="padding:8px">Nothing here.</div><?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card"><div class="bd flush">
    <?php if (! $rows): ?>
      <div class="empty"><strong>No enquiries match these filters</strong>Adjust the filters or create a new enquiry.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Enquiry</th><th>Customer</th><th>Project</th><th>Category</th><th>Status</th><th>Priority</th><th class="num">Score</th><th>Owner</th><th class="num">Budget</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="nowrap"><a href="<?= site_url('crm/enquiries/' . (int) $r['id']) ?>"><strong><?= esc($r['enquiry_no']) ?></strong></a></td>
            <td><a href="<?= site_url('crm/customers/' . (int) $r['customer_id']) ?>"><?= esc($r['customer_name']) ?></a><div class="muted small"><?= esc($r['customer_no']) ?></div></td>
            <td><?= esc($r['project_name'] ?: '—') ?></td><td><?= esc($r['category_name'] ?: '—') ?></td>
            <td><?= crm_badge($r['status']) ?></td><td><?= crm_badge($r['priority']) ?></td><td class="num"><?= (int) $r['lead_score'] ?></td>
            <td><?= esc($r['owner_name'] ?: 'Unassigned') ?></td><td class="num"><?= $r['est_budget'] !== null ? crm_money0($r['est_budget']) : '—' ?></td>
            <td class="nowrap"><?= crm_date($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table></div>
      <?= $pager ?>
    <?php endif; ?>
  </div></div>
<?php endif; ?>
