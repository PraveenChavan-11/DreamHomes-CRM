<?php
/** @var array $tpl */
?>
<div class="pagehead">
  <div>
    <h1>Edit Email Template · <?= esc($tpl['name']) ?></h1>
    <div class="muted small">Template Code: <code><?= esc($tpl['code'] ?? ($tpl['template_code'] ?? '')) ?></code></div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= site_url('crm/communication/templates') ?>">Cancel</a>
  </div>
</div>

<form class="card" method="post" action="<?= site_url('crm/communication/templates/' . $tpl['id'] . '/update') ?>" style="max-width:960px">
  <?= csrf_field() ?>
  <div class="hd">
    <h2>Email Template Specifications</h2>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="name">Template Display Name <span class="req">*</span></label>
      <input type="text" id="name" name="name" required value="<?= esc(crm_old('name', $tpl['name'])) ?>">
    </div>
    <div>
      <label for="template_code">System Trigger Code</label>
      <input type="text" id="template_code" value="<?= esc($tpl['code'] ?? ($tpl['template_code'] ?? '')) ?>" readonly style="background:#F1F5F9;cursor:not-allowed">
    </div>
    <div style="grid-column:1/-1">
      <label for="subject">Email Subject Line <span class="req">*</span></label>
      <input type="text" id="subject" name="subject" required value="<?= esc(crm_old('subject', $tpl['subject'])) ?>">
    </div>
    <div style="grid-column:1/-1">
      <label for="variables">Supported Dynamic Placeholders</label>
      <input type="text" id="variables" name="variables" value="<?= esc(crm_old('variables', $tpl['variables'])) ?>">
      <div class="muted small">Tokens like <code>{{customer_name}}</code>, <code>{{quotation_no}}</code>, <code>{{grand_total}}</code> will be populated automatically at dispatch time.</div>
    </div>
    <div style="grid-column:1/-1">
      <label for="body_html">HTML Email Content <span class="req">*</span></label>
      <textarea id="body_html" name="body_html" rows="12" style="font-family:monospace;font-size:13px;line-height:1.5" required><?= esc(crm_old('body_html', $tpl['body_html'])) ?></textarea>
    </div>
    <div style="grid-column:1/-1">
      <label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer">
        <input type="checkbox" name="is_active" value="1"<?= ! empty(crm_old('is_active', $tpl['is_active'])) ? ' checked' : '' ?>>
        Template is Active &amp; Ready for Automated Event Dispatch
      </label>
    </div>
  </div>

  <div class="ft" style="display:flex;justify-content:flex-end;gap:12px;padding:16px 22px">
    <a class="btn alt" href="<?= site_url('crm/communication/templates') ?>">Cancel</a>
    <button class="btn teal" type="submit">Save Template</button>
  </div>
</form>
