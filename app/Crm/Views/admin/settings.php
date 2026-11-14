<?php /** @var array $spec $vals $assumed $arules $srules $events $mine $mail @var bool $demoOn */
$val = static fn (string $k) => old('s.' . $k) ?? null; ?>
<div class="pagehead"><h1>Settings</h1><a class="btn alt" href="<?= site_url('crm/health') ?>">System health</a></div>
<div class="flash info">Values marked <span class="badge badge-amber">assumed default</span> are placeholders, not confirmed DHIS policy. Saving a value confirms it. Every change is written to the audit trail.</div>
<form method="post" action="<?= site_url('crm/settings') ?>" data-validate><?= csrf_field() ?>
<?php foreach ($spec as $group => $fields): ?>
<div class="card mb"><div class="hd"><h2><?= esc($group) ?></h2></div><div class="bd form">
<?php foreach ($fields as $k => $s): [$type, $label] = $s; $hint = $s[2] ?? ''; $v = $vals[$k]; $name = 's[' . $k . ']'; $id = 'f-' . str_replace('.', '-', $k); $full = in_array($type, ['textarea'], true); ?>
  <div class="<?= $full ? 'full' : '' ?>"><label for="<?= $id ?>"><?= esc($label) ?> <?= ! empty($assumed[$k]) ? '<span class="badge badge-amber">assumed default</span>' : '' ?></label>
  <?php if ($type === 'bool'): ?><input type="hidden" name="<?= $name ?>" value="0"><label style="font-weight:500"><input id="<?= $id ?>" type="checkbox" name="<?= $name ?>" value="1" style="width:auto;min-height:0"<?= $v === '1' ? ' checked' : '' ?>> Enabled</label>
  <?php elseif ($type === 'enum'): ?><select id="<?= $id ?>" name="<?= $name ?>"><?php foreach ($s[3] as $o): ?><option value="<?= esc($o) ?>"<?= crm_sel($v, $o) ?>><?= esc(crm_label($o)) ?></option><?php endforeach; ?></select>
  <?php elseif ($type === 'textarea'): ?><textarea id="<?= $id ?>" name="<?= $name ?>" rows="4"><?= esc($v) ?></textarea>
  <?php else: ?><input id="<?= $id ?>" name="<?= $name ?>" value="<?= esc($v) ?>"<?= $type === 'email' ? ' type="email"' : ($type === 'int' ? ' type="number" step="1" min="' . $s[3][0] . '" max="' . $s[3][1] . '"' : ($type === 'dec' ? ' type="number" step="0.01" min="' . $s[3][0] . '" max="' . $s[3][1] . '"' : '')) ?>><?php endif; ?>
  <?= crm_err($name) ?><?= $hint ? '<div class="hint">' . esc($hint) . '</div>' : '' ?></div>
<?php endforeach; ?></div>
<?php if ($group === 'Email'): ?><div class="bd" style="border-top:1px solid var(--line-2)"><h3 class="small muted" style="text-transform:uppercase">SMTP transport (read-only — configured in .env on the server)</h3>
  <dl class="kv mt"><dt>Sender (email.fromEmail)</dt><dd><?= esc($mail['from']) ?></dd><dt>SMTP host</dt><dd><?= esc($mail['host']) ?></dd><dt>SMTP port</dt><dd><?= esc($mail['port']) ?></dd><dt>SMTP password</dt><dd><?= esc($mail['password']) ?></dd></dl>
  <p class="hint">Credentials are never shown or stored in the database. Demo records are never transmitted even in smtp mode.</p></div><?php endif; ?>
</div>
<?php endforeach; ?>

<div class="card mb"><div class="hd"><h2>Assignment rules</h2><span class="small muted">Lowest priority number is tried first.</span></div><div class="bd flush tablewrap">
<table class="t"><thead><tr><th>Mode</th><th>Scope</th><th class="num">Priority</th><th>Active</th></tr></thead><tbody>
<?php foreach ($arules as $r): ?><tr><td><?= esc(crm_label($r['mode'])) ?></td><td class="small"><?= esc(trim(($r['team'] ? 'Team: ' . $r['team'] . ' ' : '') . ($r['category'] ? 'Category: ' . $r['category'] : '')) ?: 'All enquiries') ?></td>
<td class="num"><input type="number" min="1" max="9999" name="arule[<?= (int) $r['id'] ?>][priority]" value="<?= (int) $r['priority'] ?>" aria-label="Priority" style="width:90px"></td>
<td><input type="checkbox" name="arule[<?= (int) $r['id'] ?>][active]" value="1" aria-label="Active" style="width:auto;min-height:0"<?= (int) $r['is_active'] ? ' checked' : '' ?>></td></tr><?php endforeach; ?>
<?php if (! $arules): ?><tr><td colspan="4" class="empty">No rules — the default mode applies.</td></tr><?php endif; ?></tbody></table></div></div>

<div class="card mb"><div class="hd"><h2>Lead scoring rules</h2><span class="small muted">Points are added when a rule matches a new enquiry.</span></div><div class="bd flush tablewrap">
<table class="t"><thead><tr><th>Field</th><th>Condition</th><th class="num">Points</th><th>Active</th></tr></thead><tbody>
<?php foreach ($srules as $r): $op = ['eq' => '=', 'gte' => '≥', 'lte' => '≤', 'contains' => 'contains'][$r['operator']] ?? $r['operator']; ?><tr><td><?= esc(crm_label($r['field_name'])) ?></td><td><?= esc($op . ' ' . $r['match_value']) ?></td>
<td class="num"><input type="number" min="-100" max="100" name="srule[<?= (int) $r['id'] ?>][points]" value="<?= (int) $r['points'] ?>" aria-label="Points" style="width:90px"></td>
<td><input type="checkbox" name="srule[<?= (int) $r['id'] ?>][active]" value="1" aria-label="Active" style="width:auto;min-height:0"<?= (int) $r['is_active'] ? ' checked' : '' ?>></td></tr><?php endforeach; ?></tbody></table></div></div>

<div class="card mb"><div class="hd"><h2>My notification preferences</h2><span class="small muted">Applies to your account only.</span></div><div class="bd flush tablewrap">
<table class="t"><thead><tr><th>Event</th><th>In-app</th><th>Email</th></tr></thead><tbody>
<?php foreach ($events as $code => $label): $p = $mine[$code] ?? ['in_app' => 1, 'email' => 0]; ?><tr><td><?= esc($label) ?> <span class="small muted"><?= esc($code) ?></span></td>
<td><input type="checkbox" name="pref[<?= esc($code) ?>][in_app]" value="1" aria-label="In-app <?= esc($label) ?>" style="width:auto;min-height:0"<?= (int) $p['in_app'] ? ' checked' : '' ?>></td>
<td><input type="checkbox" name="pref[<?= esc($code) ?>][email]" value="1" aria-label="Email <?= esc($label) ?>" style="width:auto;min-height:0"<?= (int) $p['email'] ? ' checked' : '' ?>></td></tr><?php endforeach; ?></tbody></table></div></div>

<div class="gap mb"><button class="btn teal" type="submit">Save all settings</button><span class="small muted">Demo mode is <?= $demoOn ? 'ENABLED' : 'disabled' ?> (read-only here; controlled by deployment configuration).</span></div>
</form>
