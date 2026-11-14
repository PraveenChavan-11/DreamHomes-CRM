<?php
/** @var list<array> $events presented workflow events (HistoryService::present) — chronological timeline. @var bool|null $showCustomer */
$showCustomer = $showCustomer ?? false;
$fmtVal = static function ($v): string {
    if (is_bool($v)) { return $v ? 'yes' : 'no'; }
    if (is_numeric($v) && ! preg_match('/^\d{10}$/', (string) $v)) { return (string) $v; }

    return (string) $v;
};
?>
<?php if (! $events): ?><div class="empty"><strong>No history yet</strong>Events appear here as the customer progresses.</div>
<?php else: ?>
<ol class="tl">
<?php foreach ($events as $ev):
    $extra = [];
    foreach (['new', 'prev'] as $side) {
        foreach (($ev[$side] ?? []) as $k => $v) {
            if (in_array($k, ['owner_id', 'status'], true) || is_array($v) || $v === null || $v === '') { continue; }
            if ($side === 'new' || ! isset($ev['new'][$k])) { $extra[$k] = $fmtVal($v); }
        }
    }
    $isApproval = str_starts_with($ev['event_type'], 'discount.') || str_starts_with($ev['event_type'], 'negotiation.');
    ?>
  <li>
    <div class="when"><?= esc(crm_dt($ev['created_at'])) ?> · <?= esc($ev['user_name']) ?></div>
    <div class="what"><?= esc($ev['label']) ?><?php if ($ev['entity_ref']): ?> <?php if ($ev['link']): ?><a class="small" href="<?= site_url($ev['link']) ?>"><?= esc($ev['entity_ref']) ?> &rarr;</a><?php else: ?><span class="muted small"><?= esc($ev['entity_ref']) ?></span><?php endif; ?><?php endif; ?></div>
    <div class="meta">
      <?php if ($ev['prev_owner'] || $ev['new_owner']): ?>Owner: <?= esc($ev['prev_owner'] ?? '—') ?> &rarr; <strong><?= esc($ev['new_owner'] ?? '—') ?></strong> · <?php endif; ?>
      <?php if ($ev['prev_status'] || $ev['new_status']): ?>Status: <?= esc(crm_label($ev['prev_status'])) ?> &rarr; <strong><?= esc(crm_label($ev['new_status'])) ?></strong> · <?php endif; ?>
      <?php if ($isApproval && $extra): ?><?php foreach (array_slice($extra, 0, 5, true) as $k => $v): ?><?= esc(crm_label($k)) ?>: <?= esc($v) ?> · <?php endforeach; ?><?php endif; ?>
      <?php if ($ev['reason']): ?>“<?= esc($ev['reason']) ?>”<?php endif; ?>
      <?php if ($showCustomer && ! empty($ev['customer_name'])): ?> · <?= esc($ev['customer_name']) ?><?php endif; ?>
    </div>
  </li>
<?php endforeach; ?>
</ol>
<?php endif; ?>
