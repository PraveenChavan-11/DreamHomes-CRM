<?php
/** @var string $mode @var array $enquiry @var ?array $quotation @var ?array $negotiation */
?>
<div class="pagehead">
  <h1><?= $mode === 'create' ? 'New quotation' : ($mode === 'edit' ? 'Edit draft ' . esc($quotation['quotation_no']) : 'Revise ' . esc($quotation['quotation_no'])) ?></h1>
  <a class="btn alt" href="<?= site_url('crm/enquiries/' . (int) $enquiry['id']) ?>">Back to enquiry</a>
</div>
<div class="card mb"><div class="bd">
  <dl class="kv">
    <dt>Customer</dt><dd><a href="<?= site_url('crm/customers/' . (int) $enquiry['customer_id']) ?>"><?= esc($enquiry['customer_name']) ?></a></dd>
    <dt>Enquiry</dt><dd><a href="<?= site_url('crm/enquiries/' . (int) $enquiry['id']) ?>"><?= esc($enquiry['enquiry_no']) ?></a> · <?= esc((string) $enquiry['project_name']) ?> <?= crm_badge($enquiry['status']) ?></dd>
    <?php if ($mode !== 'create'): ?><dt>Version</dt><dd>Editing from v<?= (int) $version['version_no'] ?> → <?= $mode === 'revise' ? '<strong>new version v' . ((int) $version['version_no'] + 1) . '</strong> (v' . (int) $version['version_no'] . ' and its PDF stay untouched)' : 'saved in place as a tracked draft change' ?></dd><?php endif; ?>
    <?php if ($negotiation): ?><dt>Negotiation</dt><dd><a href="<?= site_url('crm/negotiations/' . (int) $negotiation['id']) ?>"><?= esc($negotiation['negotiation_no']) ?></a> approved at <?= esc(number_format((float) $negotiation['requested_discount_pct'], 2)) ?>% — lines are pre-filled with that discount; adjust anything you need.</dd><?php endif; ?>
  </dl>
  <?php if ($mode === 'create' && in_array($enquiry['status'], ['new', 'assigned'], true)): ?><div class="flash err mt" role="alert">Record customer contact on the enquiry before quoting — the server will refuse to save until then.</div><?php endif; ?>
</div></div>
<?= view('Crm\Views\quotations\_editor', get_defined_vars()) ?>
