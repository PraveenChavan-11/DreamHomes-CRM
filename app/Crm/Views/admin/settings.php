<?php /** @var array $spec $vals $assumed $arules $srules $events $mine $mail @var bool $demoOn */
$val = static fn (string $k) => old('s.' . $k) ?? null;
$tab = request()->getGet('tab') ?: 'general';
?>
<div class="pagehead">
  <div>
    <h1>Settings &amp; Administration</h1>
    <p class="muted small">Unified management for sales configuration, users, pricing master, data tools and system controls.</p>
  </div>
  <div class="gap">
    <a class="btn alt sm" href="<?= site_url('crm/health') ?>"><?= crm_icon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 16) ?> System health</a>
  </div>
</div>

<!-- Settings Hub Navigation Cards -->
<div class="grid g4 mb">
  <a class="card kpi <?= $tab === 'general' ? 'primary' : '' ?>" href="<?= site_url('crm/settings?tab=general') ?>">
    <div class="l">Configuration</div>
    <div class="v" style="font-size:20px">General &amp; Sales</div>
    <div class="s">Policies, taxes, quotas &amp; email</div>
  </a>
  <?php if (crm_can('users.manage', 'teams.manage')): ?>
  <a class="card kpi" href="<?= site_url('crm/users') ?>">
    <div class="l">Access &amp; Roles</div>
    <div class="v" style="font-size:20px">Users &amp; Teams</div>
    <div class="s">Manage staff and sales teams &rarr;</div>
  </a>
  <?php endif; ?>
  <?php if (crm_can('products.view')): ?>
  <a class="card kpi" href="<?= site_url('crm/products') ?>">
    <div class="l">Master Catalog</div>
    <div class="v" style="font-size:20px">Products &amp; Pricing</div>
    <div class="s">Standard rates &amp; minimum prices &rarr;</div>
  </a>
  <?php endif; ?>
  <a class="card kpi <?= $tab === 'workflow' ? 'primary' : '' ?>" href="<?= site_url('crm/settings?tab=workflow') ?>">
    <div class="l">Automation</div>
    <div class="v" style="font-size:20px">Rules &amp; Routing</div>
    <div class="s">Assignment &amp; lead scoring</div>
  </a>
</div>

<div class="grid g4 mb">
  <?php if (crm_can('customers.import')): ?>
  <a class="card kpi" href="<?= site_url('crm/imports') ?>">
    <div class="l">Data Tools</div>
    <div class="v" style="font-size:18px">Import Enquiries</div>
    <div class="s">Bulk upload from Excel &rarr;</div>
  </a>
  <?php endif; ?>
  <?php if (crm_can('emails.view')): ?>
  <a class="card kpi" href="<?= site_url('crm/emails') ?>">
    <div class="l">Communications</div>
    <div class="v" style="font-size:18px">Email Log</div>
    <div class="s">Delivery logs &amp; retry queue &rarr;</div>
  </a>
  <?php endif; ?>
  <?php if (crm_can('audit.view')): ?>
  <a class="card kpi" href="<?= site_url('crm/audit') ?>">
    <div class="l">Security</div>
    <div class="v" style="font-size:18px">Audit Trail</div>
    <div class="s">Activity and history events &rarr;</div>
  </a>
  <?php endif; ?>
  <?php if (crm_can('demo.reset')): ?>
  <a class="card kpi" href="<?= site_url('crm/demo') ?>">
    <div class="l">Demo Environment</div>
    <div class="v" style="font-size:18px">Demo Controls</div>
    <div class="s">Reset or reseed sample dataset &rarr;</div>
  </a>
  <?php endif; ?>
</div>

<nav class="tabs" aria-label="Settings categories">
  <a class="<?= $tab === 'general' ? 'on' : '' ?>" href="<?= site_url('crm/settings?tab=general') ?>">General &amp; Commercial Settings</a>
  <a class="<?= $tab === 'workflow' ? 'on' : '' ?>" href="<?= site_url('crm/settings?tab=workflow') ?>">Assignment &amp; Scoring Rules</a>
  <a class="<?= $tab === 'notifications' ? 'on' : '' ?>" href="<?= site_url('crm/settings?tab=notifications') ?>">My Notification Preferences</a>
</nav>

<form method="post" action="<?= site_url('crm/settings') ?>" data-validate><?= csrf_field() ?>

<?php if ($tab === 'general'): ?>
  <div class="flash info">Values marked <span class="badge badge-amber">assumed default</span> are defaults that can be fine-tuned to DHIS business policy. Saving a value confirms it. Every edit is logged in the audit trail.</div>

  <?php foreach ($spec as $group => $fields): ?>
  <div class="card mb">
    <div class="hd"><h2><?= esc($group) ?></h2></div>
    <div class="bd form">
    <?php foreach ($fields as $k => $s): [$type, $label] = $s; $hint = $s[2] ?? ''; $v = $vals[$k]; $name = 's[' . $k . ']'; $id = 'f-' . str_replace('.', '-', $k); $full = in_array($type, ['textarea'], true); ?>
      <div class="<?= $full ? 'full' : '' ?>">
        <label for="<?= $id ?>"><?= esc($label) ?> <?= ! empty($assumed[$k]) ? '<span class="badge badge-amber">assumed default</span>' : '' ?></label>
        <?php if ($type === 'bool'): ?>
          <input type="hidden" name="<?= $name ?>" value="0">
          <label style="font-weight:500;display:flex;align-items:center;gap:8px;margin-top:6px">
            <input id="<?= $id ?>" type="checkbox" name="<?= $name ?>" value="1" style="width:auto;min-height:0"<?= $v === '1' ? ' checked' : '' ?>> Enabled
          </label>
        <?php elseif ($type === 'enum'): ?>
          <select id="<?= $id ?>" name="<?= $name ?>">
            <?php foreach ($s[3] as $o): ?><option value="<?= esc($o) ?>"<?= crm_sel($v, $o) ?>><?= esc(crm_label($o)) ?></option><?php endforeach; ?>
          </select>
        <?php elseif ($type === 'textarea'): ?>
          <textarea id="<?= $id ?>" name="<?= $name ?>" rows="4"><?= esc($v) ?></textarea>
        <?php else: ?>
          <input id="<?= $id ?>" name="<?= $name ?>" value="<?= esc($v) ?>"<?= $type === 'email' ? ' type="email"' : ($type === 'int' ? ' type="number" step="1" min="' . $s[3][0] . '" max="' . $s[3][1] . '"' : ($type === 'dec' ? ' type="number" step="0.01" min="' . $s[3][0] . '" max="' . $s[3][1] . '"' : '')) ?>>
        <?php endif; ?>
        <?= crm_err($name) ?><?= $hint ? '<div class="hint">' . esc($hint) . '</div>' : '' ?>
      </div>
    <?php endforeach; ?>
    </div>
    <?php if ($group === 'Email'): ?>
      <div class="bd" style="border-top:1px solid var(--line-2)">
        <h3 class="small muted" style="text-transform:uppercase">SMTP transport configuration (read-only from server environment)</h3>
        <dl class="kv mt">
          <dt>Sender (email.fromEmail)</dt><dd><?= esc($mail['from']) ?></dd>
          <dt>SMTP host</dt><dd><?= esc($mail['host']) ?></dd>
          <dt>SMTP port</dt><dd><?= esc($mail['port']) ?></dd>
          <dt>SMTP password</dt><dd><?= esc($mail['password']) ?></dd>
        </dl>
        <p class="hint">Credentials are secured in the server configuration. Demo records are never transmitted to outside recipients.</p>
      </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

<?php elseif ($tab === 'workflow'): ?>
  <div class="card mb">
    <div class="hd">
      <h2>Enquiry Assignment Rules</h2>
      <span class="small muted">Rules are evaluated in priority order (lowest number first).</span>
    </div>
    <div class="bd flush tablewrap">
      <table class="t">
        <thead><tr><th>Mode</th><th>Scope</th><th class="num">Priority</th><th>Active</th></tr></thead>
        <tbody>
        <?php foreach ($arules as $r): ?>
          <tr>
            <td><strong><?= esc(crm_label($r['mode'])) ?></strong></td>
            <td class="small"><?= esc(trim(($r['team'] ? 'Team: ' . $r['team'] . ' ' : '') . ($r['category'] ? 'Category: ' . $r['category'] : '')) ?: 'All enquiries') ?></td>
            <td class="num"><input type="number" min="1" max="9999" name="arule[<?= (int) $r['id'] ?>][priority]" value="<?= (int) $r['priority'] ?>" aria-label="Priority" style="width:90px"></td>
            <td><input type="checkbox" name="arule[<?= (int) $r['id'] ?>][active]" value="1" aria-label="Active" style="width:auto;min-height:0"<?= (int) $r['is_active'] ? ' checked' : '' ?>></td>
          </tr>
        <?php endforeach; ?>
        <?php if (! $arules): ?><tr><td colspan="4" class="empty">No custom assignment rules configured — system default applies.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb">
    <div class="hd">
      <h2>Lead Scoring Rules</h2>
      <span class="small muted">Points are automatically credited when an enquiry matches these criteria.</span>
    </div>
    <div class="bd flush tablewrap">
      <table class="t">
        <thead><tr><th>Field</th><th>Condition</th><th class="num">Score Points</th><th>Active</th></tr></thead>
        <tbody>
        <?php foreach ($srules as $r): $op = ['eq' => '=', 'gte' => '≥', 'lte' => '≤', 'contains' => 'contains'][$r['operator']] ?? $r['operator']; ?>
          <tr>
            <td><strong><?= esc(crm_label($r['field_name'])) ?></strong></td>
            <td><?= esc($op . ' ' . $r['match_value']) ?></td>
            <td class="num"><input type="number" min="-100" max="100" name="srule[<?= (int) $r['id'] ?>][points]" value="<?= (int) $r['points'] ?>" aria-label="Points" style="width:90px"></td>
            <td><input type="checkbox" name="srule[<?= (int) $r['id'] ?>][active]" value="1" aria-label="Active" style="width:auto;min-height:0"<?= (int) $r['is_active'] ? ' checked' : '' ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($tab === 'notifications'): ?>
  <div class="card mb">
    <div class="hd">
      <h2>My Notification Preferences</h2>
      <span class="small muted">Configure in-app alerts and email notifications for your user account.</span>
    </div>
    <div class="bd flush tablewrap">
      <table class="t">
        <thead><tr><th>Event</th><th>In-App Notification</th><th>Email Notification</th></tr></thead>
        <tbody>
        <?php foreach ($events as $code => $label): $p = $mine[$code] ?? ['in_app' => 1, 'email' => 0]; ?>
          <tr>
            <td><strong><?= esc($label) ?></strong> <div class="small muted"><?= esc($code) ?></div></td>
            <td><input type="checkbox" name="pref[<?= esc($code) ?>][in_app]" value="1" aria-label="In-app <?= esc($label) ?>" style="width:auto;min-height:0"<?= (int) $p['in_app'] ? ' checked' : '' ?>></td>
            <td><input type="checkbox" name="pref[<?= esc($code) ?>][email]" value="1" aria-label="Email <?= esc($label) ?>" style="width:auto;min-height:0"<?= (int) $p['email'] ? ' checked' : '' ?>></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="gap mb" style="margin-top:20px">
  <button class="btn teal" type="submit">Save Changes</button>
  <span class="small muted">Demo mode is <strong><?= $demoOn ? 'ENABLED' : 'disabled' ?></strong> (controlled by server deployment configuration).</span>
</div>

</form>
