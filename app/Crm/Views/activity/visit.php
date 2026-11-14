<?php
/** @var array $v @var array $versions @var ?array $current @var array $docs @var array $exts */
$done = $v['status'] === 'completed';
// Prefill rows from the current measurement version (stored as "W × H unit — notes").
$rows = [];
foreach (($current['items'] ?? []) as $label => $text) {
    $text = is_scalar($text) ? (string) $text : json_encode($text);
    if (preg_match('/^([\d.?]+) × ([\d.?]+) (mm|cm|inch|ft)(?: — (.*))?$/u', $text, $m)) {
        $rows[] = ['label' => $label, 'w' => $m[1] === '?' ? '' : $m[1], 'h' => $m[2] === '?' ? '' : $m[2], 'u' => $m[3], 'n' => $m[4] ?? ''];
    } else {
        $rows[] = ['label' => $label, 'w' => '', 'h' => '', 'u' => 'mm', 'n' => $text];
    }
}
if (! $rows) { $rows[] = ['label' => '', 'w' => '', 'h' => '', 'u' => 'mm', 'n' => '']; }
$rows = old('m_label') ? array_map(static fn ($i, $l) => ['label' => $l, 'w' => old('m_width')[$i] ?? '', 'h' => old('m_height')[$i] ?? '', 'u' => old('m_unit')[$i] ?? 'mm', 'n' => old('m_notes')[$i] ?? ''], array_keys((array) old('m_label')), (array) old('m_label')) : $rows;
$unitSel = static fn ($sel) => implode('', array_map(static fn ($u) => '<option value="' . $u . '"' . ($sel === $u ? ' selected' : '') . '>' . $u . '</option>', ['mm', 'cm', 'inch', 'ft']));
$mrows = static function (array $rows) use ($unitSel) {
    $h = '';
    foreach ($rows as $r) {
        $h .= '<tr class="mrow"><td><input name="m_label[]" value="' . esc($r['label']) . '" maxlength="120" aria-label="Item or room" placeholder="e.g. Living room window"></td>'
            . '<td><input name="m_width[]" type="number" step="0.01" min="0" value="' . esc($r['w']) . '" aria-label="Width"></td><td><input name="m_height[]" type="number" step="0.01" min="0" value="' . esc($r['h']) . '" aria-label="Height"></td>'
            . '<td><select name="m_unit[]" aria-label="Unit">' . $unitSel($r['u']) . '</select></td><td><input name="m_notes[]" value="' . esc($r['n']) . '" maxlength="200" aria-label="Notes"></td></tr>';
    }

    return $h;
};
$blank = [['label' => '', 'w' => '', 'h' => '', 'u' => 'mm', 'n' => ''], ['label' => '', 'w' => '', 'h' => '', 'u' => 'mm', 'n' => ''], ['label' => '', 'w' => '', 'h' => '', 'u' => 'mm', 'n' => '']];
$table = static fn (string $body) => '<div class="tablewrap"><table class="t lines"><thead><tr><th>Item / room</th><th>Width</th><th>Height</th><th>Unit</th><th>Notes</th></tr></thead><tbody id="mbody">' . $body . '</tbody></table></div>'
    . '<p class="hint">Leave unused rows blank. Add more rows by saving and revising.</p>';
?>
<div class="pagehead">
  <div style="flex:1"><h1><?= esc($v['visit_no']) ?> <span class="muted" style="font-weight:500">· Site visit</span></h1>
    <div class="gap" style="margin-top:6px"><?= crm_badge($v['status']) ?> <span class="muted small"><?= crm_dt($v['scheduled_at']) ?></span></div></div>
  <a class="btn alt" href="<?= site_url('crm/enquiries/' . (int) $v['enquiry_id'] . '?tab=visits') ?>">Back to enquiry</a>
</div>

<div class="grid g-side">
  <div class="grid">
    <div class="card"><div class="hd"><h2>Visit details</h2></div><div class="bd"><dl class="kv">
      <dt>Customer</dt><dd><?= esc($v['customer_name']) ?> · <?= esc($v['customer_mobile']) ?></dd>
      <dt>Enquiry</dt><dd><a href="<?= site_url('crm/enquiries/' . (int) $v['enquiry_id']) ?>"><?= esc($v['enquiry_no']) ?></a> · <?= esc($v['project_name'] ?: '') ?></dd>
      <dt>Assigned to</dt><dd><?= esc($v['user_name']) ?></dd><dt>Scheduled</dt><dd><?= crm_dt($v['scheduled_at']) ?></dd>
      <dt>Address</dt><dd><?= esc($v['address'] ?: '—') ?></dd><dt>Remarks</dt><dd><?= nl2br(esc($v['remarks'] ?: '—')) ?></dd>
      <?php if ($done): ?><dt>Completed</dt><dd><?= crm_dt($v['completed_at']) ?></dd>
      <dt>Technical observations</dt><dd><?= nl2br(esc($v['observations'] ?: '—')) ?></dd><dt>Product requirements</dt><dd><?= nl2br(esc($v['product_requirements'] ?: '—')) ?></dd>
      <dt>Follow-up action</dt><dd><?= esc($v['follow_up_action'] ?: '—') ?></dd><?php endif; ?>
    </dl></div></div>

    <?php if ($v['status'] === 'scheduled'): ?>
    <form class="card" method="post" action="<?= site_url('crm/visits/' . (int) $v['id'] . '/complete') ?>" data-validate data-confirm="Complete this visit? The measurements are saved as version 1.">
      <?= csrf_field() ?><div class="hd"><h2>Complete visit</h2></div><div class="bd">
      <h3 class="mb">Measurements</h3><?= crm_err('m_label') ?><?= crm_err('m_width') ?>
      <?= $table($mrows(old('m_label') ? $rows : $blank)) ?>
      <div class="form mt">
        <div class="full"><label for="obs">Technical observations</label><textarea id="obs" name="observations"><?= esc(old('observations')) ?></textarea></div>
        <div class="full"><label for="preq">Product requirements noted</label><textarea id="preq" name="product_requirements"><?= esc(old('product_requirements')) ?></textarea></div>
        <div class="full"><label for="fua">Follow-up action</label><input id="fua" name="follow_up_action" maxlength="255" value="<?= esc(old('follow_up_action')) ?>"></div>
      </div></div>
      <div class="bd" style="border-top:1px solid var(--line-2)"><button class="btn teal" type="submit">Complete visit</button></div>
    </form>
    <?php endif; ?>

    <?php if ($current !== null || $done): ?>
    <form class="card" method="post" action="<?= site_url('crm/visits/' . (int) $v['id'] . '/measurements') ?>" data-validate>
      <?= csrf_field() ?><div class="hd"><h2>Revise measurements</h2><span class="muted small">Saves a new version — earlier versions are never overwritten</span></div><div class="bd">
      <?= crm_err('m_label') ?><?= crm_err('m_width') ?>
      <?= $table($mrows(old('m_label') ? $rows : array_merge($rows, [['label' => '', 'w' => '', 'h' => '', 'u' => 'mm', 'n' => '']]))) ?>
      <div class="mt"><label for="revn" class="req" style="font-weight:600;font-size:13px">Reason for revision</label><input id="revn" name="revision_note" required maxlength="500" value="<?= esc(old('revision_note')) ?>"><?= crm_err('revision_note') ?></div></div>
      <div class="bd" style="border-top:1px solid var(--line-2)"><button class="btn" type="submit">Save new version</button></div>
    </form>
    <?php endif; ?>
  </div>

  <div class="grid">
    <div class="card"><div class="hd"><h3>Measurement versions</h3></div><div class="bd">
      <?php if (! $versions): ?><div class="empty">No measurements recorded.</div><?php else: foreach ($versions as $m): ?>
        <div style="margin-bottom:14px"><strong>Version <?= (int) $m['version_no'] ?></strong> <?= (int) $m['is_current'] ? '<span class="badge badge-green">Current</span>' : '<span class="badge badge-grey">Superseded</span>' ?>
          <div class="muted small"><?= crm_dt($m['created_at']) ?> · <?= esc($m['user_name']) ?><?= $m['notes'] ? ' · ' . esc($m['notes']) : '' ?></div>
          <ul style="margin:6px 0 0 18px;padding:0"><?php foreach ($m['items'] as $k => $val): ?><li><strong><?= esc($k) ?></strong>: <?= esc(is_scalar($val) ? (string) $val : json_encode($val)) ?></li><?php endforeach; ?></ul></div>
      <?php endforeach; endif; ?></div></div>

    <div class="card"><div class="hd"><h3>Photos &amp; documents</h3></div><div class="bd">
      <?php if (! $docs): ?><p class="muted">Nothing uploaded yet.</p><?php else: ?><ul style="margin:0 0 14px 18px;padding:0">
        <?php foreach ($docs as $d): ?><li><a href="<?= site_url('crm/documents/' . (int) $d['id']) ?>"><?= esc($d['original_name']) ?></a> <span class="muted small">· <?= number_format($d['size_bytes'] / 1024, 1) ?> KB · <?= esc($d['user_name']) ?> · <?= crm_dt($d['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
      <form method="post" action="<?= site_url('crm/visits/' . (int) $v['id'] . '/documents') ?>" enctype="multipart/form-data" data-validate>
        <?= csrf_field() ?><label for="document" class="small muted">Upload (<?= esc(implode(', ', $exts)) ?> · max 8 MB)</label>
        <input id="document" type="file" name="document" required accept=".<?= esc(implode(',.', $exts)) ?>"><?= crm_err('document') ?>
        <div class="mt"><button class="btn alt sm" type="submit">Upload</button></div>
      </form></div></div>
  </div>
</div>
