<?php /** @var string $tab @var array $can $users $teams $assignable $roles $perms $grant $logins $assignableRoles @var int $uid */
$tabs = ['users' => 'Users', 'teams' => 'Teams', 'roles' => 'Roles & permissions', 'logins' => 'Login history'];
$me = crm_user()['id'] ?? 0; ?>
<div class="pagehead">
  <h1>Users &amp; Teams</h1>
  <div class="gap">
    <?php if ($tab === 'users' && $can['users']): ?><button class="btn teal" type="button" data-modal="m-user">+ New user</button><?php endif; ?>
    <?php if ($tab === 'teams' && $can['teams']): ?><button class="btn teal" type="button" data-modal="m-team">+ New team</button><?php endif; ?>
  </div>
</div>
<div class="tabs" role="tablist">
<?php foreach ($tabs as $k => $l): if (in_array($k, ['users', 'logins'], true) && ! $can['users']) { continue; } ?>
  <a class="<?= $tab === $k ? 'on' : '' ?>" href="<?= site_url('crm/users?tab=' . $k) ?>"><?= esc($l) ?></a><?php endforeach; ?></div>

<?php if ($tab === 'users'): ?>
<div class="card"><div class="bd flush tablewrap"><table class="t"><thead><tr><th>Name</th><th>Username / email</th><th>Role</th><th>Team</th><th class="num">Discount limit</th><th>Last login</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($users as $u): $locked = $u['locked_until'] && strtotime($u['locked_until']) > time(); ?>
<tr><td><strong><?= esc($u['first_name'] . ' ' . $u['last_name']) ?></strong><div class="small muted"><?= esc($u['employee_code'] ?: '') ?></div></td>
<td><?= esc($u['username']) ?><div class="small muted"><?= esc($u['email']) ?></div></td><td><?= esc(crm_label(explode(',', (string) $u['roles'])[0])) ?></td><td class="small"><?= esc($u['teams'] ?: '—') ?></td>
<td class="num"><?= esc(rtrim(rtrim((string) $u['max_discount_pct'], '0'), '.') ?: '0') ?>%</td>
<td class="small nowrap"><?= crm_dt($u['last_login_at']) ?><?= (int) $u['failed_login_count'] ? '<div class="muted">' . (int) $u['failed_login_count'] . ' failed attempt(s)</div>' : '' ?></td>
<td><?= crm_badge((int) $u['is_active'] ? 'active' : 'inactive') ?><?= $locked ? ' ' . crm_badge('failed') . '<span class="small muted"> locked</span>' : '' ?></td>
<td class="right nowrap"><a class="btn alt sm" href="<?= site_url('crm/users?tab=logins&uid=' . $u['id']) ?>">Logins</a>
<button class="btn alt sm" type="button" data-modal="m-pw" data-action="<?= site_url('crm/users/' . $u['id'] . '/password') ?>">Reset password</button>
<?php if ((int) $u['id'] !== (int) $me): ?>
<form method="post" action="<?= site_url('crm/users/' . $u['id'] . '/active') ?>" style="display:inline" data-confirm="<?= (int) $u['is_active'] ? 'Deactivate this user? They will no longer be able to sign in; history is kept.' : 'Reactivate this user?' ?>"><?= csrf_field() ?>
<input type="hidden" name="active" value="<?= (int) $u['is_active'] ? 0 : 1 ?>"><button class="btn sm <?= (int) $u['is_active'] ? 'danger' : 'alt' ?>" type="submit"><?= (int) $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div></div>

<?php elseif ($tab === 'teams'): ?>
<?php if (! $teams): ?><div class="card"><div class="empty"><strong>No teams yet</strong>Create a team and add members; managers see their team's enquiries.</div></div><?php endif; ?>
<div class="grid g2">
<?php foreach ($teams as $t): ?>
<div class="card"><div class="hd"><h2><?= esc($t['name']) ?></h2><?= crm_badge((int) $t['is_active'] ? 'active' : 'inactive') ?></div><div class="bd">
  <p class="small muted">Manager: <strong><?= esc($t['manager'] ?: 'Not set') ?></strong> · <?= (int) $t['members'] ?> member<?= (int) $t['members'] === 1 ? '' : 's' ?></p>
  <ul style="margin:8px 0 14px;padding-left:18px"><?php foreach ($t['member_rows'] as $m): ?><li><?= esc($m['name']) ?></li><?php endforeach; ?></ul>
  <?php if ($can['teams']): ?><form method="post" class="gap" action="<?= site_url('crm/users/teams/' . $t['id'] . '/members') ?>" data-validate><?= csrf_field() ?>
    <select name="user_id" required aria-label="Add member to <?= esc($t['name']) ?>" style="flex:1;min-width:160px"><option value="">Add member…</option><?php foreach ($assignable as $a): ?><option value="<?= (int) $a['id'] ?>"><?= esc($a['name']) ?></option><?php endforeach; ?></select>
    <button class="btn alt sm" type="submit">Add</button></form><?php endif; ?></div></div>
<?php endforeach; ?></div>

<?php elseif ($tab === 'roles'): ?>
<div class="card"><div class="hd"><h2>Role &amp; permission matrix</h2><span class="small muted">Read-only. Super Admin holds every permission.</span></div><div class="bd flush tablewrap">
<table class="t"><thead><tr><th>Permission</th><?php foreach ($roles as $r): ?><th class="num"><?= esc($r['name']) ?></th><?php endforeach; ?></tr></thead><tbody>
<?php $cat = ''; foreach ($perms as $p): if ($p['category'] !== $cat) { $cat = $p['category']; echo '<tr><td colspan="' . (count($roles) + 1) . '" style="background:#f7f9fc"><strong>' . esc(crm_label($cat)) . '</strong></td></tr>'; } ?>
<tr><td><?= esc($p['name']) ?> <span class="small muted"><?= esc($p['code']) ?></span></td>
<?php foreach ($roles as $r): $has = $r['slug'] === 'super_admin' || ! empty($grant[$r['slug']][$p['code']]); ?><td class="num" style="text-align:center"><?= $has ? '<span class="badge badge-green" title="Granted">✓</span>' : '<span class="muted" title="Not granted">–</span>' ?></td><?php endforeach; ?></tr>
<?php endforeach; ?></tbody></table></div></div>

<?php else: ?>
<div class="card"><div class="hd"><h2>Recent sign-in activity<?= $uid ? ' (one user)' : '' ?></h2><?php if ($uid): ?><a class="btn alt sm" href="<?= site_url('crm/users?tab=logins') ?>">Show all</a><?php endif; ?></div><div class="bd flush tablewrap">
<?php if (! $logins): ?><div class="empty">No sign-in events recorded.</div><?php else: ?>
<table class="t"><thead><tr><th>When</th><th>User</th><th>Event</th><th>IP address</th><th>Browser</th></tr></thead><tbody>
<?php foreach ($logins as $l): ?><tr><td class="nowrap"><?= crm_dt($l['created_at']) ?></td><td><?= esc($l['uname'] ?: $l['email']) ?></td><td><?= crm_badge(in_array($l['event'], ['failed', 'locked'], true) ? 'failed' : ($l['event'] === 'login' ? 'active' : 'grey')) ?> <span class="small"><?= esc(crm_label($l['event'])) ?></span></td><td><?= esc($l['ip_address']) ?></td><td class="small muted"><?= esc(mb_substr((string) $l['user_agent'], 0, 70)) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php endif; ?>

<?php if ($can['users']): ?>
<div class="modal" id="m-user" role="dialog" aria-modal="true" aria-labelledby="m-user-t"><div class="box" style="max-width:640px">
<form method="post" action="<?= site_url('crm/users/create') ?>" data-validate autocomplete="off"><?= csrf_field() ?>
<div class="hd"><h3 id="m-user-t">New user</h3><button type="button" class="btn alt sm" data-close aria-label="Close">✕</button></div>
<div class="bd form">
  <div><label class="req" for="u-fn">First name</label><input id="u-fn" name="first_name" required maxlength="100" value="<?= esc(old('first_name')) ?>"><?= crm_err('first_name') ?></div>
  <div><label class="req" for="u-ln">Last name</label><input id="u-ln" name="last_name" required maxlength="100" value="<?= esc(old('last_name')) ?>"><?= crm_err('last_name') ?></div>
  <div><label class="req" for="u-un">Username</label><input id="u-un" name="username" required maxlength="50" pattern="[A-Za-z0-9._\-]{3,50}" value="<?= esc(old('username')) ?>"><?= crm_err('username') ?></div>
  <div><label class="req" for="u-em">Email</label><input id="u-em" type="email" name="email" required value="<?= esc(old('email')) ?>"><?= crm_err('email') ?></div>
  <div><label for="u-ph">Phone</label><input id="u-ph" name="phone" maxlength="30" value="<?= esc(old('phone')) ?>"></div>
  <div><label for="u-ec">Employee code</label><input id="u-ec" name="employee_code" maxlength="30" value="<?= esc(old('employee_code')) ?>"></div>
  <div><label class="req" for="u-role">Role</label><select id="u-role" name="role" required><option value="">Select…</option><?php foreach ($assignableRoles as $s): ?><option value="<?= esc($s) ?>"<?= crm_sel(old('role'), $s) ?>><?= esc(\Crm\Services\ReferenceData::ROLES[$s][0]) ?></option><?php endforeach; ?></select></div>
  <div><label for="u-team">Team</label><select id="u-team" name="team_id"><option value="">— none —</option><?php foreach ($teams as $t): ?><option value="<?= (int) $t['id'] ?>"><?= esc($t['name']) ?></option><?php endforeach; ?></select></div>
  <div><label for="u-dl">Discount limit % (optional)</label><input id="u-dl" type="number" step="0.01" min="0" max="100" name="max_discount_pct" placeholder="Role default"></div>
  <div><label class="req" for="u-pw">Temporary password</label><input id="u-pw" type="password" name="password" required minlength="10" autocomplete="new-password"><div class="hint">Must satisfy the password policy; shown only once to you.</div></div>
</div>
<div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Create user</button></div></form></div></div>
<div class="modal" id="m-pw" role="dialog" aria-modal="true" aria-labelledby="m-pw-t"><div class="box">
<form method="post" action="" data-validate autocomplete="off"><?= csrf_field() ?>
<div class="hd"><h3 id="m-pw-t">Reset password</h3><button type="button" class="btn alt sm" data-close aria-label="Close">✕</button></div>
<div class="bd"><label class="req" for="pw-new">New temporary password</label><input id="pw-new" type="password" name="new_password" required minlength="10" autocomplete="new-password"><div class="hint">Also clears any failed-login lock.</div></div>
<div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Reset password</button></div></form></div></div>
<?php endif; ?>
<?php if ($can['teams']): ?>
<div class="modal" id="m-team" role="dialog" aria-modal="true" aria-labelledby="m-team-t"><div class="box">
<form method="post" action="<?= site_url('crm/users/teams/create') ?>" data-validate><?= csrf_field() ?>
<div class="hd"><h3 id="m-team-t">New team</h3><button type="button" class="btn alt sm" data-close aria-label="Close">✕</button></div>
<div class="bd form" style="grid-template-columns:1fr">
  <div><label class="req" for="t-name">Team name</label><input id="t-name" name="name" required maxlength="100" value="<?= esc(old('name')) ?>"><?= crm_err('name') ?></div>
  <div><label for="t-mgr">Manager</label><select id="t-mgr" name="manager_id"><option value="">— none —</option><?php foreach ($assignable as $a): ?><option value="<?= (int) $a['id'] ?>"><?= esc($a['name']) ?></option><?php endforeach; ?></select></div>
</div>
<div class="ft"><button type="button" class="btn alt" data-close>Cancel</button><button class="btn teal" type="submit">Create team</button></div></form></div></div>
<?php endif; ?>
