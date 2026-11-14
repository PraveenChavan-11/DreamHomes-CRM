<?php /** @var array $u @var list<array> $history @var string $timeout */ ?>
<div class="pagehead"><h1>My profile</h1></div>
<div class="grid g2">
  <div class="card"><div class="hd"><h2>Account</h2></div><div class="bd">
    <dl class="kv">
      <dt>Name</dt><dd><?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?></dd>
      <dt>Username</dt><dd><?= esc($u['username'] ?? '—') ?></dd>
      <dt>Email</dt><dd><?= esc($u['email']) ?></dd>
      <dt>Phone</dt><dd><?= esc($u['phone'] ?? '—') ?></dd>
      <dt>Role</dt><dd><?= esc(implode(', ', array_map('crm_label', crm_user()['roles'] ?? []))) ?></dd>
      <dt>Discount limit</dt><dd><?= esc(number_format((float) ($u['max_discount_pct'] ?? 0), 1)) ?>%</dd>
      <dt>Last login</dt><dd><?= esc(crm_dt($u['last_login_at'] ?? null)) ?></dd>
      <dt>Session timeout</dt><dd><?= esc($timeout) ?> minutes of inactivity</dd>
    </dl></div></div>
  <div class="card"><div class="hd"><h2>Change password</h2></div><div class="bd">
    <form method="post" action="<?= site_url('crm/profile/password') ?>" data-validate class="form">
      <?= csrf_field() ?>
      <div class="full"><label class="req" for="cp">Current password</label><input id="cp" type="password" name="current_password" required autocomplete="current-password"></div>
      <div><label class="req" for="np">New password</label><input id="np" type="password" name="new_password" required minlength="10" autocomplete="new-password"><div class="hint">10+ chars, upper, lower, number, symbol.</div></div>
      <div><label class="req" for="cf">Confirm new password</label><input id="cf" type="password" name="confirm_password" required minlength="10" autocomplete="new-password"></div>
      <div class="full"><button class="btn" type="submit">Update password</button></div>
    </form></div></div>
</div>
<div class="card mt"><div class="hd"><h2>Recent sign-in activity</h2></div><div class="bd flush">
  <?php if (! $history): ?><div class="empty"><strong>No activity yet</strong></div><?php else: ?>
  <div class="tablewrap"><table class="t"><thead><tr><th>When</th><th>Event</th><th>IP address</th></tr></thead><tbody>
  <?php foreach ($history as $h): ?><tr><td class="nowrap"><?= esc(crm_dt($h['created_at'])) ?></td><td><?= crm_badge($h['event'] === 'failed' ? 'failed' : ($h['event'] === 'login' ? 'active' : 'grey')) ?></td><td><?= esc($h['ip_address']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div><?php endif; ?></div></div>
