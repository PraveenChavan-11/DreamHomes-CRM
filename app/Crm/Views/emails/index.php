<?php /** @var array $rows $f $counts $templates $customers @var int $total $page $pages @var string $mode */ ?>
<div class="pagehead"><h1>Email Log</h1>
  <?php if (crm_can('emails.send')): ?><button class="btn teal" type="button" data-modal="m-send">+ Send manual email</button><?php endif; ?></div>
<?php if ($mode !== 'smtp'): ?><div class="flash info" role="status"><strong>Log-only mode.</strong> Emails are recorded but never transmitted to recipients (setting <code>email.mode</code>). Demo records are never transmitted in any mode.</div><?php endif; ?>
<div class="grid g4 mb">
<?php foreach (['queued' => 'Queued', 'sent' => 'Sent (SMTP accepted)', 'delivered' => 'Delivered (confirmed)', 'failed' => 'Failed'] as $k => $l): ?>
  <a class="card kpi <?= $k === 'failed' && ($counts[$k] ?? 0) ? 'warn' : '' ?>" href="<?= site_url('crm/emails?status=' . $k) ?>"><div class="l"><?= esc($l) ?></div><div class="v"><?= (int) ($counts[$k] ?? 0) ?></div></a>
<?php endforeach; ?></div>
<form class="filters" method="get" action="<?= site_url('crm/emails') ?>">
  <div class="f"><label for="q">Search</label><input id="q" type="search" name="q" value="<?= esc($f['q']) ?>" placeholder="Recipient, subject, customer"></div>
  <div class="f"><label for="st">Status</label><select id="st" name="status"><option value="">All</option>
    <?php foreach (['queued', 'sent', 'delivered', 'failed', 'bounced'] as $s): ?><option value="<?= $s ?>"<?= crm_sel($f['status'], $s) ?>><?= esc(crm_label($s)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="tp">Template</label><select id="tp" name="template"><option value="">All</option>
    <?php foreach ($templates as $t): ?><option value="<?= esc($t) ?>"<?= crm_sel($f['template'], $t) ?>><?= esc($t) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="fr">From</label><input id="fr" type="date" name="from" value="<?= esc($f['from']) ?>"></div>
  <div class="f"><label for="to">To</label><input id="to" type="date" name="to" value="<?= esc($f['to']) ?>"></div>
  <button class="btn" type="submit">Filter</button><a class="btn alt" href="<?= site_url('crm/emails') ?>">Reset</a>
</form>
<div class="card"><div class="bd flush tablewrap">
<?php if (! $rows): ?><div class="empty"><strong>No emails found</strong>Nothing matches these filters.</div><?php else: ?>
<table class="t"><thead><tr><th>#</th><th>When</th><th>Recipient</th><th>Subject</th><th>Template</th><th>Customer</th><th>Status</th><th class="num">Tries</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><a href="<?= site_url('crm/emails/' . $r['id']) ?>"><?= (int) $r['id'] ?></a></td><td class="nowrap"><?= crm_dt($r['created_at']) ?></td><td><?= esc($r['to_email']) ?></td>
<td><a href="<?= site_url('crm/emails/' . $r['id']) ?>"><?= esc($r['subject']) ?></a></td><td class="small"><?= esc($r['template_code'] ?: 'manual') ?></td>
<td><?= $r['cust_id'] ? '<a href="' . site_url('crm/customers/' . $r['cust_id']) . '">' . esc($r['customer_name']) . '</a>' : '—' ?></td>
<td><?= crm_badge($r['status']) ?><?= $r['provider'] === 'log_only' ? ' <span class="small muted" title="Not transmitted">not sent</span>' : '' ?></td><td class="num"><?= (int) $r['attempts'] ?></td></tr>
<?php endforeach; ?></tbody></table><?php endif; ?></div>
<?= view('Crm\Views\admin\_pager', ['page' => $page, 'pages' => $pages, 'total' => $total]) ?></div>

<?php if (crm_can('emails.send')): ?>
<div class="modal" id="m-send" role="dialog" aria-modal="true" aria-labelledby="m-send-t"><div class="box">
<form method="post" action="<?= site_url('crm/emails/send') ?>" data-validate><?= csrf_field() ?>
<div class="hd"><h3 id="m-send-t">Send manual email</h3><button type="button" class="btn alt sm" data-close aria-label="Close">✕</button></div>
<div class="bd form" style="grid-template-columns:1fr">
  <div><label for="s-cust">Customer (optional — fills recipient)</label><select id="s-cust" name="customer_id" data-search><option value="">— none —</option>
    <?php foreach ($customers as $c): ?><option value="<?= (int) $c['id'] ?>"><?= esc($c['name']) ?> — <?= esc($c['email']) ?></option><?php endforeach; ?></select></div>
  <div><label for="s-to">Recipient email</label><input id="s-to" type="email" name="to" value="<?= esc(old('to')) ?>" placeholder="Leave blank to use the customer's email"><?= crm_err('to') ?></div>
  <div><label class="req" for="s-sub">Subject</label><input id="s-sub" name="subject" required maxlength="255" value="<?= esc(old('subject')) ?>"><?= crm_err('subject') ?></div>
  <div><label class="req" for="s-body">Message</label><textarea id="s-body" name="body" required rows="6"><?= esc(old('body')) ?></textarea></div>
  <p class="hint">In log-only mode (default) this is recorded but not transmitted. Never email real customers from demo data.</p>
</div>
<div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Send / record</button></div></form></div></div>
<?php endif; ?>
