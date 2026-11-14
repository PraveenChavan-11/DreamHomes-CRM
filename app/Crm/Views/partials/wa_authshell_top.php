<?php
/** Standalone (no sidebar) shell used by login / forgot / reset. @var string $heading @var string $sub */
$v = static fn (string $f) => '?v=' . (is_file(FCPATH . $f) ? filemtime(FCPATH . $f) : 1);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= esc($heading) ?> · DHIS Sales CRM</title><link rel="icon" href="<?= base_url('favicon.png') ?>">
<link rel="stylesheet" href="<?= base_url('assets/crm/css/crm.css' . $v('assets/crm/css/crm.css')) ?>"></head>
<body><div class="authwrap">
  <section class="authside">
    <div class="logo-plate"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions"></div>
    <div><h1>One place for every enquiry, quotation and order.</h1>
      <p>Track each customer from first enquiry to confirmed sales order, with every price change, approval and revision on record.</p></div>
    <div class="small" style="color:#9fb6d1">DHIS Sales CRM · a TalentHub product</div>
  </section>
  <section class="authform"><div class="box">
    <div class="logo-plate" style="margin-bottom:14px"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" style="height:38px"></div>
    <h1><?= esc($heading) ?></h1><p class="muted"><?= esc($sub) ?></p>
    <?php if ($m = session()->getFlashdata('success')): ?><div class="flash ok" role="status"><?= esc($m) ?></div><?php endif; ?>
    <?php if ($m = session()->getFlashdata('error')): ?><div class="flash err" role="alert"><?= esc($m) ?></div><?php endif; ?>
