<?php
/** @var int $total @var int $per @var int $page @var array $query */
$pages = (int) max(1, ceil($total / max(1, $per)));
if ($pages <= 1) {
    return;
}
$url = static fn (int $p) => '?' . http_build_query(array_merge($query, ['page' => $p]));
?>
<nav class="pager" aria-label="Pagination">
  <span class="muted small">Page <?= $page ?> of <?= $pages ?> · <?= number_format($total) ?> records</span>
  <?php if ($page > 1): ?><a class="btn alt sm" href="<?= esc($url($page - 1)) ?>" rel="prev">&larr; Previous</a><?php endif; ?>
  <?php if ($page < $pages): ?><a class="btn alt sm" href="<?= esc($url($page + 1)) ?>" rel="next">Next &rarr;</a><?php endif; ?>
</nav>
