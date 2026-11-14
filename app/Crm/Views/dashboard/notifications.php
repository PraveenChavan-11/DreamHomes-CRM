<?php /** @var list<array> $rows @var int $total @var int $per @var int $page @var int $unread */ ?>
<div class="pagehead"><h1>Notifications</h1>
  <?php if ($unread): ?><form method="post" action="<?= site_url('crm/notifications/read') ?>"><?= csrf_field() ?><button class="btn alt" type="submit">Mark all as read (<?= $unread ?>)</button></form><?php endif; ?></div>
<div class="card"><div class="bd flush">
<?php if (! $rows): ?><div class="empty"><strong>You're all caught up</strong>New enquiries, approvals and reminders will appear here.</div>
<?php else: ?>
  <div class="tablewrap"><table class="t"><tbody>
  <?php foreach ($rows as $r): ?>
    <tr<?= $r['read_at'] ? '' : ' style="background:#f3fafb"' ?>>
      <td style="width:28px"><?= $r['read_at'] ? '' : '<span class="badge badge-blue" title="Unread">●</span>' ?></td>
      <td><strong><?= esc($r['title']) ?></strong><?php if ($r['body']): ?><div class="muted small"><?= esc($r['body']) ?></div><?php endif; ?></td>
      <td class="muted small nowrap"><?= esc(crm_dt($r['created_at'])) ?></td>
      <td class="right nowrap">
        <?php if (! $r['read_at'] || $r['link']): ?>
          <form method="post" action="<?= site_url('crm/notifications/' . (int) $r['id'] . '/read') ?>" style="display:inline"><?= csrf_field() ?><button class="btn alt sm" type="submit"><?= $r['link'] ? 'Open' : 'Mark read' ?></button></form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?></tbody></table></div>
  <?= view('Crm\Views\partials\wa_pager', compact('total', 'per', 'page')) ?>
<?php endif; ?>
</div></div>
