<?php
/** @var list<array> $rows @var int $total @var int $page @var int $perPage @var int $totalPages @var array $filters */
?>
<div class="pagehead">
  <div>
    <h1>Communication Centre · Email Outbox &amp; Logs <span class="muted small">(<?= number_format($total) ?>)</span></h1>
    <div class="muted small">Full delivery telemetry, retry logs, and customer communication records.</div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= site_url('crm/communication/templates') ?>">Manage Email Templates</a>
  </div>
</div>

<form class="filters card" style="padding:14px;margin-bottom:18px" method="get" action="<?= site_url('crm/communication/logs') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Recipient, subject, code..."></div>
  <div class="f"><label for="st">Status</label><select id="st" name="status"><option value="">All Statuses</option><option value="sent"<?= crm_sel($filters['status'] ?? '', 'sent') ?>>Sent</option><option value="queued"<?= crm_sel($filters['status'] ?? '', 'queued') ?>>Queued</option><option value="failed"<?= crm_sel($filters['status'] ?? '', 'failed') ?>>Failed</option></select></div>
  <div class="f"><button class="btn" type="submit">Filter</button></div>
  <div class="f"><a class="btn alt" href="<?= site_url('crm/communication/logs') ?>">Reset</a></div>
</form>

<div class="card"><div class="bd flush">
<?php if (! $rows): ?>
  <div class="empty"><strong>No email logs found</strong>Outbox messages dispatched by event triggers will appear here.</div>
<?php else: ?>
  <div class="tablewrap">
    <table class="t">
      <thead>
        <tr>
          <th>Log #</th>
          <th>Recipient Email</th>
          <th>Account / Customer</th>
          <th>Template / Event</th>
          <th>Subject</th>
          <th>Status</th>
          <th>Sent At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>#<?= (int) $r['id'] ?></td>
          <td><strong><?= esc($r['to_email']) ?></strong></td>
          <td><?= esc($r['organisation_name'] ?: ($r['customer_name'] ?: '—')) ?></td>
          <td><code><?= esc($r['template_code'] ?? 'manual') ?></code></td>
          <td><?= esc($r['subject']) ?></td>
          <td><?= crm_badge($r['status']) ?></td>
          <td class="nowrap"><?= esc(crm_dt($r['sent_at'] ?: $r['created_at'])) ?></td>
          <td>
            <?php if ($r['status'] === 'failed' && crm_can('emails.send')): ?>
              <form method="post" action="<?= site_url('crm/communication/logs/' . $r['id'] . '/retry') ?>" style="display:inline">
                <?= csrf_field() ?>
                <button class="btn sm teal" type="submit">Retry Send</button>
              </form>
            <?php else: ?>
              <span class="muted small">Delivered</span>
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
