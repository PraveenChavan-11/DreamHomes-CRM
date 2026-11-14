<?php
/** @var array $products @var array $categories @var string $category @var int|null $coming_soon_count */
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>';
$coming_soon_count = $coming_soon_count ?? (($category === '') ? 2 : 0);
?>
<link rel="stylesheet" href="<?= base_url('assets/css/products.css') ?>?v=<?= file_exists(FCPATH . 'assets/css/products.css') ? filemtime(FCPATH . 'assets/css/products.css') : 1 ?>">

<section class="th-hero">
  <div class="page-width">
    <p class="th-crumbs"><a href="<?= base_url() ?>">Home</a> / Products</p>
    <span class="th-eyebrow">Enterprise Platforms</span>
    <h1 class="th-h1">Software products engineered for modern scale</h1>
    <p class="th-lead">High-performance SaaS products and enterprise business systems built by TalentHub to power workforce automation, recruitment intelligence, and operational efficiency.</p>
  </div>
</section>

<section class="th-section" id="product-list">
  <div class="page-width">
    <?php if (count($categories) > 1): ?>
      <nav class="tp-filter" aria-label="Filter products by category">
        <a href="<?= base_url('products') ?>" class="tp-filter__link" <?= $category === '' ? 'aria-current="true"' : '' ?>>All Categories</a>
        <?php foreach ($categories as $c): ?>
          <a href="<?= base_url('products?category=' . rawurlencode($c)) ?>" class="tp-filter__link" <?= $category === $c ? 'aria-current="true"' : '' ?>><?= esc($c) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if (empty($products) && $coming_soon_count === 0): ?>
      <div class="th-card th-card--static tp-empty">
        <h2 class="th-h3">No products match this category</h2>
        <p>Please select another category or <a href="<?= base_url('products') ?>">view all products</a>.</p>
      </div>
    <?php else: ?>
      <ul class="tp-cards" aria-label="Enterprise Products">
        <?php foreach ($products as $p):
            $href   = base_url('products/' . $p['slug']);
            $chips  = $p['chips'] ?? ['Biometric Attendance', 'Statutory Payroll & TDS', 'Multi-Company RBAC'];
            $logoUrl = !empty($p['logo_url']) ? (str_starts_with($p['logo_url'], 'http') ? $p['logo_url'] : base_url($p['logo_url'])) : null;
        ?>
          <li class="th-card tp-card" data-spotlight="true">
            <div class="tp-card__top">
              <div class="tp-card__icon-wrap">
                <?php if ($logoUrl): ?>
                  <img src="<?= esc($logoUrl) ?>" alt="<?= esc($p['name']) ?> Mark" width="36" height="36" loading="lazy" class="tp-card__logo">
                <?php else: ?>
                  <svg class="tp-card__logo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                <?php endif; ?>
              </div>
              <span class="tp-pill tp-pill--live">
                <span class="tp-pill-dot" aria-hidden="true"></span>
                Live SaaS
              </span>
            </div>

            <div class="tp-card__meta">
              <?php if (!empty($p['category'])): ?>
                <span class="tp-card__cat"><?= esc($p['category']) ?></span>
              <?php endif; ?>
              <h2 class="tp-card__name">
                <a href="<?= esc($href) ?>" class="tp-card__link" style="color:inherit;text-decoration:none;"><?= esc($p['name']) ?></a>
              </h2>
            </div>

            <?php if (!empty($p['tagline'])): ?>
              <p class="tp-card__tagline"><?= esc($p['tagline']) ?></p>
            <?php endif; ?>

            <?php if (!empty($p['short_description'])): ?>
              <p class="tp-card__desc"><?= esc($p['short_description']) ?></p>
            <?php endif; ?>

            <div class="tp-card__footer">
              <a href="<?= esc($href) ?>" class="tp-card__action tp-card__action--live">
                <span>Explore <?= esc($p['name']) ?> &amp; Specs</span>
                <?= $arrow ?>
              </a>
            </div>
          </li>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $coming_soon_count; $i++): ?>
          <li class="th-card tp-card tp-card--placeholder" data-spotlight="true">
            <div class="tp-card__placeholder-body">
              <span class="tp-pill tp-pill--soon">
                <span class="tp-pill-dot" aria-hidden="true"></span>
                Coming Soon
              </span>
              <h2 class="tp-card__placeholder-heading">Coming Soon</h2>
            </div>
          </li>
        <?php endfor; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<section class="th-section th-section--dark th-cta">
  <div class="page-width">
    <h2 class="th-h2">Need custom enterprise software engineered?</h2>
    <p class="th-lead" style="margin:0 auto 1.5rem">Besides our proprietary SaaS products, TalentHub engineers bespoke web portals, mobile applications, and enterprise systems tailored to your workflows.</p>
    <div class="th-btn-row" style="justify-content:center">
      <a class="th-btn th-btn--light" href="<?= base_url('project-enquiry') ?>">Hire &amp; Build with Us <?= $arrow ?></a>
      <a class="th-btn th-btn--outline-light" href="<?= base_url('services/it-services') ?>">Explore Custom IT Services</a>
    </div>
  </div>
</section>
