<?php
/** Standalone shell used by login / forgot / reset with #070D0E brand palette. @var string $heading @var string $sub */
$v = static fn (string $f) => '?v=' . (is_file(FCPATH . $f) ? filemtime(FCPATH . $f) : 1);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title><?= esc($heading) ?> · DHIS Sales CRM</title><link rel="icon" href="<?= base_url('favicon.png') ?>">
<link rel="stylesheet" href="<?= base_url('assets/crm/css/crm.css' . $v('assets/crm/css/crm.css')) ?>"></head>
<body><div class="authwrap">
  <section class="authside">
    <div class="logo-plate"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions"></div>
    <div>
      <h1 style="color:#FFFFFF;margin-bottom:12px">Simple, structured sales pipeline for modern interiors.</h1>
      <p style="color:#D2D9D9">Track every lead from first enquiry to customer confirmation, site measurements, quotations, and verified sales orders.</p>
    </div>
    <div class="small" style="color:#8E9C9E;display:flex;align-items:center;gap:8px">
      <img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="" width="20" height="20">
      <span>DHIS Sales CRM · Developed &amp; Powered by TalentHub Consultant</span>
    </div>
  </section>
  <section class="authform"><div class="box">
    <div class="logo-plate" style="margin-bottom:18px"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" style="height:42px"></div>
    <h1><?= esc($heading) ?></h1><p class="muted"><?= esc($sub) ?></p>
    <?php if ($m = session()->getFlashdata('success')): ?><div class="flash ok" role="status"><?= esc($m) ?></div><?php endif; ?>
    <?php if ($m = session()->getFlashdata('error')): ?><div class="flash err" role="alert"><?= esc($m) ?></div><?php endif; ?>
