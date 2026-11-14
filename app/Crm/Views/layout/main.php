<?php
/** @var string $content @var string $title @var string $active @var array $user */
$unread = \Crm\Services\NotificationService::unreadCount((int) $user['id']);
$initials = strtoupper(mb_substr($user['name'] ?? 'U', 0, 1) . (mb_substr(strrchr(' ' . ($user['name'] ?? ''), ' '), 1, 1) ?: ''));
$role = crm_label($user['roles'][0] ?? '');
$v = static fn (string $f) => '?v=' . (is_file(FCPATH . $f) ? filemtime(FCPATH . $f) : 1);
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="<?= csrf_hash() ?>"><meta name="csrf-name" content="<?= csrf_token() ?>">
<title><?= esc($title ? $title . ' · ' : '') ?>DHIS Sales CRM</title>
<link rel="icon" href="<?= base_url('favicon.png') ?>">
<link rel="stylesheet" href="<?= base_url('assets/crm/css/crm.css' . $v('assets/crm/css/crm.css')) ?>">
</head>
<body>
<div class="app">
  <aside class="side" id="side" aria-label="Main navigation">
    <a class="brand" href="<?= site_url('crm') ?>"><img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" width="200" height="101"></a>
    <nav class="nav">
      <?php foreach (crm_nav() as $g):
          $items = array_filter($g['items'], static fn ($i) => crm_can(...$i[3]));
          if (! $items) { continue; } ?>
        <h4><?= esc($g['group']) ?></h4>
        <?php foreach ($items as [$label, $url, $key, $perm, $icon]): ?>
          <a href="<?= site_url($url) ?>" class="<?= $active === $key ? 'on' : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= crm_icon($icon) ?><span><?= esc($label) ?></span></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="tal"><img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="" width="22" height="22"><span>A TalentHub product</span></div>
  </aside>
  <div class="main">
    <header class="top">
      <button class="burger" aria-label="Toggle menu" type="button"><?= crm_icon('M4 6h16M4 12h16M4 18h16', 22) ?></button>
      <div class="title"><?= esc($title ?: 'DHIS Sales CRM') ?></div>
      <a class="bell" href="<?= site_url('crm/notifications') ?>" aria-label="Notifications"><?= crm_icon('M15 17h5l-1.4-1.4a2 2 0 01-.6-1.4V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3C7.7 6.2 6 8.4 6 11v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 22) ?><span class="dot" <?= $unread ? '' : 'style="display:none"' ?>><?= $unread > 99 ? '99+' : $unread ?></span></a>
      <div class="umenu">
        <button type="button" aria-haspopup="true"><span class="avatar"><?= esc($initials) ?></span><span><?= esc($user['name'] ?? '') ?> <span class="muted small">· <?= esc($role) ?></span></span></button>
        <div class="pop" role="menu">
          <a href="<?= site_url('crm/profile') ?>" role="menuitem">My profile &amp; password</a>
          <form method="post" action="<?= site_url('crm/logout') ?>"><?= csrf_field() ?><button type="submit" role="menuitem">Sign out</button></form>
        </div>
      </div>
    </header>
    <main class="content" id="main">
      <?php if ($m = session()->getFlashdata('success')): ?><div class="flash ok" role="status"><?= esc($m) ?></div><?php endif; ?>
      <?php if ($m = session()->getFlashdata('error')): ?><div class="flash err" role="alert"><?= esc($m) ?></div><?php endif; ?>
      <?php if ($m = session()->getFlashdata('info')): ?><div class="flash info"><?= esc($m) ?></div><?php endif; ?>
      <?= $content ?>
    </main>
    <footer class="foot"><img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="" width="18" height="18">DHIS Sales CRM · a TalentHub product · demo and placeholder data are fictional</footer>
  </div>
</div>
<script>window.CRM_BASE = <?= json_encode(base_url('/')) ?>;</script>
<script src="<?= base_url('assets/crm/js/crm.js' . $v('assets/crm/js/crm.js')) ?>" defer></script>
</body>
</html>
