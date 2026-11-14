<?php
/** @var int $total @var int $per @var int $page — GET-driven pager that keeps the current filters. */
$pages = max(1, (int) ceil($total / max(1, $per)));
if ($pages <= 1) {
    return;
}
$q   = service('request')->getGet() ?: [];
$url = static function (int $p) use ($q) {
    $q['page'] = $p;

    return current_url() . '?' . http_build_query($q);
};
$from = ($page - 1) * $per + 1;
$to   = min($total, $page * $per);
?>
<nav class="pager" aria-label="Pagination">
  <span class="muted small"><?= $from ?>–<?= $to ?> of <?= number_format($total) ?></span>
  <?php if ($page > 1): ?><a class="btn alt sm" href="<?= esc($url($page - 1), 'attr') ?>">&larr; Prev</a><?php endif; ?>
  <span class="small">Page <?= $page ?> / <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn alt sm" href="<?= esc($url($page + 1), 'attr') ?>">Next &rarr;</a><?php endif; ?>
</nav>
