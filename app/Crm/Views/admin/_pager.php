<?php
/** @var int $page @var int $pages @var int $total */
if ($pages <= 1) { echo '<div class="pager small muted">' . (int) $total . ' record' . ($total === 1 ? '' : 's') . '</div>'; return; }
$q = $_GET; unset($q['page']);
$url = static fn (int $p) => '?' . http_build_query($q + ['page' => $p]);
?>
<nav class="pager" aria-label="Pagination">
  <span class="small muted"><?= (int) $total ?> records · page <?= $page ?> of <?= $pages ?></span>
  <?php if ($page > 1): ?><a class="btn alt sm" href="<?= esc($url($page - 1)) ?>">&laquo; Prev</a><?php endif; ?>
  <?php if ($page < $pages): ?><a class="btn alt sm" href="<?= esc($url($page + 1)) ?>">Next &raquo;</a><?php endif; ?>
</nav>
