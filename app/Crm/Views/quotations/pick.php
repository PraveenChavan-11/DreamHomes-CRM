<?php /** @var list<array<string,mixed>> $enquiries */ ?>
<div class="pagehead"><h1>New quotation</h1></div>
<div class="card"><div class="hd"><h2>Choose the enquiry to quote</h2></div>
<?php if (! $enquiries): ?>
  <div class="empty"><strong>No enquiry is ready for a quotation</strong>Quotations are prepared for active enquiries that have been contacted. Record contact on an enquiry first.<div class="mt"><a class="btn" href="<?= site_url('crm/enquiries') ?>">Open enquiries</a></div></div>
<?php else: ?>
<form class="bd form" method="get" action="<?= site_url('crm/quotations/new') ?>" data-validate>
  <div class="full"><label class="req" for="enquiry">Enquiry</label>
    <select id="enquiry" name="enquiry" required data-msg="Choose an enquiry.">
      <option value="">Select…</option>
      <?php foreach ($enquiries as $e): ?><option value="<?= (int) $e['id'] ?>"><?= esc($e['enquiry_no'] . ' · ' . $e['customer_name'] . ' · ' . ($e['project_name'] ?: 'No project name') . ' (' . crm_label($e['status']) . ')') ?></option><?php endforeach; ?>
    </select></div>
  <div class="full"><button class="btn teal" type="submit">Continue</button></div>
</form>
<?php endif; ?></div>
