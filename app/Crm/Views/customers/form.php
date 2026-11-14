<?php
/** @var array $c @var string $action @var string $mode @var list<array> $sources @var list<array> $owners */
$val = static fn (string $k) => old($k) !== null ? old($k) : ($c[$k] ?? '');
$dups = session()->getFlashdata('duplicates') ?: [];
$field = static function (string $k, string $label, string $type = 'text', bool $req = false, array $attr = []) use ($val) {
    $a = '';
    foreach ($attr as $n => $v) { $a .= ' ' . $n . '="' . esc($v, 'attr') . '"'; }

    return '<div><label class="' . ($req ? 'req' : '') . '" for="f_' . $k . '">' . esc($label) . '</label><input id="f_' . $k . '" type="' . $type . '" name="' . $k . '" value="' . esc($val($k), 'attr') . '"' . ($req ? ' required' : '') . $a . '>' . crm_err($k) . '</div>';
};
?>
<div class="pagehead"><h1><?= $mode === 'new' ? 'New customer' : 'Edit ' . esc($c['customer_no'] ?? '') ?></h1>
  <a class="btn alt" href="<?= site_url($mode === 'new' ? 'crm/customers' : 'crm/customers/' . (int) $c['id']) ?>">Cancel</a></div>
<?php if ($dups): ?>
<div class="flash err" role="alert" style="display:block"><strong>Possible duplicate<?= count($dups) > 1 ? 's' : '' ?> found.</strong> A customer with the same mobile or email already exists:
  <ul style="margin:6px 0 8px 18px"><?php foreach ($dups as $d): ?><li><a href="<?= site_url('crm/customers/' . (int) $d['id']) ?>" target="_blank" rel="noopener"><?= esc($d['customer_no'] . ' · ' . $d['name']) ?></a> — <?= esc($d['mobile']) ?> <?= esc($d['email'] ?? '') ?> (<?= esc($d['status']) ?>)</li><?php endforeach; ?></ul>
  Open the existing record instead, or tick "Save anyway" below and submit again.</div>
<?php endif; ?>
<form method="post" action="<?= esc($action, 'attr') ?>" data-validate class="card"><div class="bd"><?= csrf_field() ?>
  <div class="form">
    <?= $field('name', 'Customer name', 'text', true, ['maxlength' => 150]) ?>
    <div><label for="f_customer_type">Customer type</label><select id="f_customer_type" name="customer_type"><?php foreach (['individual', 'company', 'architect', 'builder', 'other'] as $t): ?><option value="<?= $t ?>"<?= crm_sel($val('customer_type') ?: 'individual', $t) ?>><?= esc(crm_label($t)) ?></option><?php endforeach; ?></select></div>
    <?= $field('company_name', 'Company name') ?>
    <?= $field('primary_contact', 'Primary contact person') ?>
    <?= $field('mobile', 'Mobile number', 'tel', true, ['inputmode' => 'tel', 'pattern' => '[0-9+ \-]{10,16}','data-msg' => 'Enter a valid 10-digit Indian mobile number.']) ?>
    <?= $field('alt_mobile', 'Alternate mobile', 'tel') ?>
    <?= $field('email', 'Email', 'email') ?>
    <?= $field('gstin', 'GSTIN (optional)', 'text', false, ['maxlength' => 15, 'style' => 'text-transform:uppercase']) ?>
    <div class="full"><label for="f_billing_address">Billing address</label><textarea id="f_billing_address" name="billing_address"><?= esc($val('billing_address')) ?></textarea></div>
    <div class="full"><label for="f_site_address">Site address</label><textarea id="f_site_address" name="site_address"><?= esc($val('site_address')) ?></textarea></div>
    <?= $field('city', 'City') ?>
    <?= $field('state', 'State') ?>
    <?= $field('pin_code', 'PIN code', 'text', false, ['inputmode' => 'numeric', 'pattern' => '[0-9]{6}', 'maxlength' => 6, 'data-msg' => 'PIN code must be 6 digits.']) ?>
    <div><label for="f_source_id">Customer source</label><select id="f_source_id" name="source_id"><option value="">—</option><?php foreach ($sources as $s): ?><option value="<?= (int) $s['id'] ?>"<?= crm_sel($val('source_id'), $s['id']) ?>><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
    <div><label for="f_owner_user_id">Relationship owner</label><select id="f_owner_user_id" name="owner_user_id"><?php $ow = $val('owner_user_id') ?: crm_user()['id']; foreach ($owners as $s): ?><option value="<?= (int) $s['id'] ?>"<?= crm_sel($ow, $s['id']) ?>><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
    <div class="full"><label for="f_remarks">Remarks</label><textarea id="f_remarks" name="remarks"><?= esc($val('remarks')) ?></textarea></div>
    <?php if ($dups): ?><div class="full"><label><input type="checkbox" name="allow_duplicate" value="1" style="width:auto;min-height:0"> Save anyway — I have checked the existing record(s) above</label></div><?php endif; ?>
    <div class="full gap"><button class="btn teal" type="submit"><?= $mode === 'new' ? 'Create customer' : 'Save changes' ?></button></div>
  </div></div></form>
