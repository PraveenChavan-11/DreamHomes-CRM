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
    <a class="brand" href="<?= site_url('crm') ?>">
      <div class="brand-plate">
        <img src="<?= base_url('assets/crm/img/dhis-logo.png') ?>" alt="Dream Homes Interior Solutions" width="180" height="91">
      </div>
      <div class="brand-tag">Sales Management Portal</div>
    </a>
    <nav class="nav">
      <h4>Main Menu</h4>
      <?php foreach (crm_nav() as [$label, $url, $key, $perm, $icon]):
          if (! crm_can(...$perm)) { continue; }
          $isCurrent = ($active === $key);
      ?>
        <a href="<?= site_url($url) ?>" class="<?= $isCurrent ? 'on' : '' ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
          <?= crm_icon($icon, 20) ?>
          <span><?= esc($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-footer">
      <button type="button" data-modal="m-support-help">
        <?= crm_icon('M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 18) ?>
        <span>Help &amp; Support</span>
      </button>
      <a href="<?= site_url('crm/profile') ?>">
        <?= crm_icon('M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 18) ?>
        <span>User Profile</span>
      </a>
      <form method="post" action="<?= site_url('crm/logout') ?>" style="margin:0">
        <?= csrf_field() ?>
        <button type="submit">
          <?= crm_icon('M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1', 18) ?>
          <span>Logout</span>
        </button>
      </form>
      <div class="side-attribution">
        <img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="TalentHub" width="18" height="18">
        <span>Powered by TalentHub</span>
      </div>
    </div>
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
          <a href="<?= site_url('crm/settings') ?>" role="menuitem">Settings</a>
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
    <footer class="foot">
      <img src="<?= base_url('assets/crm/img/talenthub-mark.png') ?>" alt="" width="18" height="18">
      <span>DHIS Sales CRM · Developed &amp; Powered by TalentHub Consultant · Demo data is fictional</span>
    </footer>
  </div>
</div>

<!-- Help & Support Modal -->
<div class="modal" id="m-support-help" role="dialog" aria-modal="true" aria-label="Help and Support">
  <div class="box">
    <div class="hd">
      <h3>Help &amp; Support</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <div class="bd">
      <p style="font-size:15px;font-weight:600;margin-bottom:8px">DHIS Sales CRM Support</p>
      <p class="muted small">For operational questions, system configuration changes, or workflow assistance, please contact your administrator or the TalentHub Consultant support desk.</p>
      <dl class="kv mt">
        <dt>Application</dt><dd>DHIS Sales CRM (Enterprise Edition)</dd>
        <dt>Client</dt><dd>DHIS Group – Dream Homes Interior Solutions</dd>
        <dt>Tech Partner</dt><dd>TalentHub Consultant</dd>
        <dt>Email</dt><dd><a href="mailto:support@talenthubconsultant.com">support@talenthubconsultant.com</a></dd>
        <dt>Documentation</dt><dd>Standard Operating Procedures are available from the Settings hub.</dd>
      </dl>
    </div>
    <div class="ft">
      <button type="button" class="btn alt" data-close>Close</button>
    </div>
  </div>
</div>

<script>window.CRM_BASE = <?= json_encode(base_url('/')) ?>;</script>
<script src="<?= base_url('assets/crm/js/crm.js' . $v('assets/crm/js/crm.js')) ?>" defer></script>
</body>
</html>
