<?php
/** @var list<array> $templates */
?>
<div class="pagehead">
  <div>
    <h1>Communication Centre · Email Templates (<?= count($templates) ?>)</h1>
    <div class="muted small">Configurable HTML email templates for sales milestones, quotation approvals, order confirmations, and receipts.</div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= site_url('crm/communication/logs') ?>">View Email Outbox Logs</a>
  </div>
</div>

<div class="card"><div class="bd flush">
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Template Code</th>
          <th>Name / Event Trigger</th>
          <th>Email Subject Line</th>
          <th>Dynamic Merge Variables</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($templates as $t): ?>
        <tr>
          <td><code><?= esc($t['template_code']) ?></code></td>
          <td><strong><?= esc($t['name']) ?></strong></td>
          <td><?= esc($t['subject']) ?></td>
          <td style="max-width:320px;font-size:12px;color:var(--text-muted)">
            <code><?= esc($t['variables']) ?></code>
          </td>
          <td><?= ! empty($t['is_active']) ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-grey">Disabled</span>' ?></td>
          <td>
            <?php if (crm_can('settings.manage')): ?>
              <a class="btn sm alt" href="<?= site_url('crm/communication/templates/' . $t['id'] . '/edit') ?>">Edit Template</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div></div>
