<?php $v = static fn (string $f) => '?v=' . (is_file(FCPATH . $f) ? filemtime(FCPATH . $f) : 1); ?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Not found · DHIS Sales CRM</title>
<link rel="stylesheet" href="<?= base_url('assets/crm/css/crm.css' . $v('assets/crm/css/crm.css')) ?>"></head>
<body><div class="authform" style="min-height:100vh"><div class="box" style="text-align:center">
  <div class="logo-plate" style="margin-bottom:18px"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions"></div>
  <h1>Page not found</h1><p class="muted">That record does not exist, or you do not have access to it.</p>
  <p><a class="btn" href="<?= site_url('crm') ?>">Back to dashboard</a></p></div></div></body></html>
