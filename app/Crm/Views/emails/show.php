<?php /** @var array $m $rel @var string|null $by @var string $mode */
$honest = match ($m['status']) {
    'queued' => $m['provider'] === 'log_only' ? 'Recorded only — this email was NOT transmitted to the recipient.' : 'Waiting to be sent.',
    'sent' => 'Accepted by the SMTP server. Delivery to the inbox is not confirmed.',
    'delivered' => 'Delivery confirmed by the provider.',
    'bounced' => 'The provider reported a bounce.',
    default => 'The send attempt failed.',
}; ?>
<div class="pagehead"><h1>Email #<?= (int) $m['id'] ?> <?= crm_badge($m['status']) ?></h1><a class="btn alt" href="<?= site_url('crm/emails') ?>">&laquo; Email log</a>
  <?php if ($m['status'] === 'failed' && crm_can('emails.send')): ?>
  <form method="post" action="<?= site_url('crm/emails/' . $m['id'] . '/retry') ?>" data-confirm="Retry sending this email now?"><?= csrf_field() ?><button class="btn teal" type="submit">Retry now</button></form><?php endif; ?></div>
<div class="flash <?= $m['status'] === 'failed' || $m['status'] === 'bounced' ? 'err' : 'info' ?>"><?= esc($honest) ?></div>
<div class="grid g-side">
  <div class="card"><div class="hd"><h2><?= esc($m['subject']) ?></h2></div><div class="bd">
    <iframe title="Email body" sandbox="" referrerpolicy="no-referrer" style="width:100%;min-height:340px;border:1px solid var(--line);border-radius:8px;background:#fff" srcdoc="<?= esc((string) $m['body_html'], 'attr') ?>"></iframe></div></div>
  <div class="card"><div class="hd"><h2>Delivery record</h2></div><div class="bd"><dl class="kv">
    <dt>To</dt><dd><?= esc($m['to_email']) ?></dd><dt>From</dt><dd><?= esc($m['from_email']) ?></dd><dt>Template</dt><dd><?= esc($m['template_code'] ?: 'Manual / none') ?></dd>
    <dt>Customer</dt><dd><?= $rel['customer'] ? '<a href="' . site_url('crm/customers/' . $rel['customer']['id']) . '">' . esc($rel['customer']['name']) . '</a>' : '—' ?></dd>
    <dt>Enquiry</dt><dd><?= $rel['enquiry'] ? '<a href="' . site_url('crm/enquiries/' . $rel['enquiry']['id']) . '">' . esc($rel['enquiry']['enquiry_no']) . '</a>' : '—' ?></dd>
    <dt>Quotation</dt><dd><?= $rel['quotation'] ? '<a href="' . site_url('crm/quotations/' . $rel['quotation']['id']) . '">' . esc($rel['quotation']['quotation_no']) . '</a>' : '—' ?></dd>
    <dt>Triggered by</dt><dd><?= esc($by ?: ($m['triggered_by_automation'] ? 'Automation: ' . $m['triggered_by_automation'] : 'System')) ?></dd>
    <dt>Created</dt><dd><?= crm_dt($m['created_at']) ?></dd><dt>Attempts</dt><dd><?= (int) $m['attempts'] ?><?= $m['last_attempt_at'] ? ' · last ' . crm_dt($m['last_attempt_at']) : '' ?></dd>
    <dt>Sent (SMTP)</dt><dd><?= crm_dt($m['sent_at']) ?></dd><dt>Provider</dt><dd><?= esc($m['provider'] ?: '—') ?></dd>
    <dt>Provider response</dt><dd><?= esc($m['provider_response'] ?: '—') ?></dd>
    <dt>Failure reason</dt><dd><?= esc($m['failure_reason'] ?: '—') ?></dd>
    <dt>Retry</dt><dd><?= $m['status'] === 'failed' ? ($m['next_retry_at'] ? 'Automatic retry due ' . crm_dt($m['next_retry_at']) : 'No further automatic retries') : '—' ?></dd>
    <dt>Attachment</dt><dd><?= $m['attachment_path'] ? esc(basename($m['attachment_path'])) : '—' ?></dd><dt>Record type</dt><dd><?= (int) $m['is_demo'] ? 'Demo (never transmitted)' : 'Live' ?></dd></dl></div></div>
</div>
