<?php
/** @var string $month @var int $first @var int $daysIn @var int $startDow @var array<string,list<array>> $events @var string $prev @var string $next @var string $today */
?>
<div class="pagehead"><h1>Calendar · <?= esc(date('F Y', $first)) ?></h1>
  <a class="btn alt sm" href="<?= site_url('crm/calendar?m=' . $prev) ?>">&larr; <?= esc(date('M', strtotime($prev . '-01'))) ?></a>
  <a class="btn alt sm" href="<?= site_url('crm/calendar') ?>">This month</a>
  <a class="btn alt sm" href="<?= site_url('crm/calendar?m=' . $next) ?>"><?= esc(date('M', strtotime($next . '-01'))) ?> &rarr;</a></div>
<p class="small muted"><span class="badge badge-blue">Follow-up</span> <span class="badge badge-green">Site visit</span> <span class="badge badge-red">Overdue</span></p>
<div class="cal" role="grid" aria-label="Calendar for <?= esc(date('F Y', $first)) ?>">
  <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?><div class="dh"><?= $d ?></div><?php endforeach; ?>
  <?php for ($i = 0; $i < $startDow; $i++): ?><div class="d dim"></div><?php endfor; ?>
  <?php for ($d = 1; $d <= $daysIn; $d++):
      $key = $month . '-' . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
      $ev  = $events[$key] ?? []; ?>
    <div class="d<?= $key === $today ? ' today' : '' ?>"><strong><?= $d ?></strong>
      <?php foreach (array_slice($ev, 0, 4) as $e): ?><a class="ev <?= esc($e['cls']) ?>" href="<?= site_url($e['url']) ?>" title="<?= esc($e['time'] . ' ' . $e['text'], 'attr') ?>"><?= esc($e['time']) ?> <?= esc($e['text']) ?></a><?php endforeach; ?>
      <?php if (count($ev) > 4): ?><span class="muted small">+<?= count($ev) - 4 ?> more</span><?php endif; ?></div>
  <?php endfor; ?>
  <?php $tail = (7 - (($startDow + $daysIn) % 7)) % 7; for ($i = 0; $i < $tail; $i++): ?><div class="d dim"></div><?php endfor; ?>
</div>
