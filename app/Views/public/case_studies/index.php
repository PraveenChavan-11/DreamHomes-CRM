<?php
/** @var array $rows @var array $categories @var string $category */
?>
<section class="page-hero">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= base_url() ?>">Home</a><span aria-hidden="true">/</span><span aria-current="page">Case studies</span></nav>
    <p class="eyebrow">Portfolio</p>
    <h1 class="page-hero__title">Case studies</h1>
    <p class="page-hero__lead">Selected recruitment and technology projects, written up by the team that delivered them.</p>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <?php if (count($categories) > 1): ?>
      <nav class="filter" aria-label="Filter case studies by category">
        <a href="<?= base_url('case-studies') ?>" <?= $category === '' ? 'aria-current="true"' : '' ?>>All</a>
        <?php foreach ($categories as $c): ?>
          <a href="<?= base_url('case-studies?category=' . rawurlencode($c)) ?>" <?= $category === $c ? 'aria-current="true"' : '' ?>><?= esc($c) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <?php if (!$rows): ?>
      <div class="empty">
        <h2>No case studies are published yet</h2>
        <p>We add projects here only once they are complete and approved for sharing. In the meantime, see our <a href="<?= base_url('products') ?>">products</a> or <a href="<?= base_url('project-enquiry') ?>">tell us about your project</a>.</p>
      </div>
    <?php else: ?>
      <ul class="cs-grid" data-stagger aria-label="Case studies" style="list-style:none;padding:0;margin:0">
        <?php foreach ($rows as $r): $href = base_url('case-studies/' . $r['slug']); ?>
          <li class="cs-card card-lift img-zoom">
            <div class="cs-card__img">
              <?php if (!empty($r['cover_url'])): ?><img src="<?= esc($r['cover_url']) ?>" alt="" loading="lazy" decoding="async" width="800" height="500"><?php endif; ?>
            </div>
            <div class="cs-card__body">
              <?php if (!empty($r['category'])): ?><span class="badge badge--soft"><?= esc($r['category']) ?></span><?php endif; ?>
              <h2 class="h3"><a href="<?= esc($href) ?>"><?= esc($r['title']) ?></a></h2>
              <?php if (!empty($r['summary'])): ?><p><?= esc($r['summary']) ?></p><?php endif; ?>
              <span class="link-arrow icon-shift" aria-hidden="true">Read the case study <svg class="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<section class="sec sec--dark cta-band">
  <div class="wrap" data-reveal>
    <h2>Have something similar in mind?</h2>
    <p class="lead">Tell us what you need to build or who you need to hire, and we will come back with a practical next step.</p>
    <div class="btn-row">
      <a class="btn btn--light" href="<?= base_url('project-enquiry') ?>">Discuss Your Project</a>
      <a class="btn btn--outline-light" href="<?= base_url('employers') ?>">Share a Hiring Requirement</a>
    </div>
  </div>
</section>
