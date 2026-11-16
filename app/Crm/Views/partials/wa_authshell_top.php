<?php
/** Standalone shell used by login / forgot / reset with TalentHub brand palette. @var string $heading @var string $sub */
$v = static fn (string $f) => '?v=' . (is_file(FCPATH . $f) ? filemtime(FCPATH . $f) : 1);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#1E6872">
  <title><?= esc($heading) ?> · DHIS Sales CRM · Dream Homes Interior Solutions</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
  <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('favicon-192x192.png') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('favicon-180x180.png') ?>">
  <link rel="shortcut icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="icon" href="<?= base_url('favicon.png') ?>">

  <!-- Google Fonts: Inter & Plus Jakarta Sans (TalentHub Typography System) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= base_url('assets/crm/css/crm.css' . $v('assets/crm/css/crm.css')) ?>">
</head>
<body class="auth-body">
<div class="authwrap">

  <!-- ── LEFT: BRAND SHOWCASE PANEL (TALENTHUB THEME) ───────────────────────── -->
  <section class="authside">
    <!-- Ambient Glow Circles -->
    <div class="auth-ambient-1"></div>
    <div class="auth-ambient-2"></div>
    <div class="auth-grid-overlay"></div>

    <!-- Top Header / Brand Logo -->
    <div class="auth-side-header">
      <a href="<?= site_url('crm') ?>" class="auth-side-brand">
        <div class="auth-logo-plate">
          <img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" style="height:36px; width:auto; display:block;">
        </div>
        <div>
          <span class="auth-side-title">DHIS Sales CRM</span>
          <span class="auth-side-badge">Sales Operations Portal</span>
        </div>
      </a>
    </div>

    <!-- Center Value Proposition -->
    <div class="auth-side-body">
      <div class="auth-pill">
        <span class="auth-pill-dot"></span>
        <span>Enterprise Interior Sales CRM</span>
      </div>

      <h1 class="auth-headline">
        Empowering Interior Sales &amp; Client Project Delivery
      </h1>

      <p class="auth-subtext">
        Track every interior project lead from first enquiry to customer confirmation, site measurements, quotations, negotiations, and verified sales orders.
      </p>

      <div class="auth-stat-grid">
        <div class="auth-stat-card">
          <p class="auth-stat-num">7 Days</p>
          <p class="auth-stat-lbl">Turnaround Target</p>
        </div>
        <div class="auth-stat-card">
          <p class="auth-stat-num">100%</p>
          <p class="auth-stat-lbl">Encrypted Vault</p>
        </div>
      </div>
    </div>

    <!-- Bottom Security Notice -->
    <div class="auth-side-footer">
      <span>ISO-standard role-based authentication</span>
      <span class="auth-powered-by">
        <img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="" width="16" height="16">
        <span>Powered by TalentHub Pune</span>
      </span>
    </div>
  </section>

  <!-- ── RIGHT: LOGIN FORM PANEL ────────────────────────────────────────────── -->
  <section class="authform">
    <div class="box">

      <!-- Mobile Brand Header -->
      <div class="auth-mobile-brand">
        <div class="auth-mobile-logo-wrap">
          <img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" style="height:32px; width:auto;">
        </div>
        <div>
          <span style="font-weight:800; font-size:16px; color:#0F172A; display:block">DHIS Sales CRM</span>
          <span style="font-size:10px; color:#1D6A34; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; display:block">Sales Operations Center</span>
        </div>
      </div>

      <div class="auth-heading-wrap">
        <h2><?= esc($heading) ?></h2>
        <p class="muted"><?= esc($sub) ?></p>
      </div>

      <!-- Session Flash Messages -->
      <?php if ($m = session()->getFlashdata('error')): ?>
        <div class="flash err" role="alert">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <span><?= esc($m) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($m = session()->getFlashdata('success')): ?>
        <div class="flash ok" role="status">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          <span><?= esc($m) ?></span>
        </div>
      <?php endif; ?>
