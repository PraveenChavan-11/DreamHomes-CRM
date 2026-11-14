<?php
/** @var array $cs @var ?string $cover @var array $images */
$paras = static fn (?string $t): array => array_values(array_filter(array_map('trim', preg_split('/\n{2,}/', (string) $t) ?: [])));
$techs = array_values(array_filter(array_map('trim', explode(',', (string) $cs['technologies']))));
$sections = ['Challenge' => $cs['challenge'], 'Solution' => $cs['solution'], 'Outcome' => $cs['outcome']];
?>
<section class="page-hero">
  <div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= base_url() ?>">Home</a><span aria-hidden="true">/</span><a href="<?= base_url('case-studies') ?>">Case studies</a><span aria-hidden="true">/</span><span aria-current="page"><?= esc($cs['title']) ?></span></nav>
    <?php if (!empty($cs['category'])): ?><p class="eyebrow"><?= esc($cs['category']) ?></p><?php endif; ?>
    <h1 class="page-hero__title"><?= esc($cs['title']) ?></h1>
    <?php if (!empty($cs['summary'])): ?><p class="page-hero__lead"><?= esc($cs['summary']) ?></p><?php endif; ?>
  </div>
</section>

<article class="sec">
  <div class="wrap">
    <?php if ($cover): ?>
      <figure class="cs-hero img-zoom" data-reveal="mask" style="margin:0 0 3rem;border-radius:var(--r-xl);overflow:hidden;box-shadow:var(--sh-2)">
        <img src="<?= esc($cover) ?>" alt="<?= esc($cs['title']) ?> — cover image" width="1200" height="675" decoding="async" fetchpriority="high" style="width:100%;height:auto;display:block">
      </figure>
    <?php endif; ?>

    <?php if ($cs['client_name'] || $techs || $cs['project_url']): ?>
      <dl class="cs-facts" data-reveal style="margin-bottom:3rem">
        <?php if ($cs['client_name']): ?><div><dt>Client</dt><dd><?= esc($cs['client_name']) ?></dd></div><?php endif; ?>
        <?php if ($cs['category']): ?><div><dt>Category</dt><dd><?= esc($cs['category']) ?></dd></div><?php endif; ?>
        <?php if ($techs): ?><div><dt>Technologies</dt><dd><?= esc(implode(', ', $techs)) ?></dd></div><?php endif; ?>
        <?php if ($cs['project_url']): ?><div><dt>Project</dt><dd><a href="<?= esc($cs['project_url']) ?>" target="_blank" rel="noopener noreferrer nofollow">Visit project (opens in a new tab)</a></dd></div><?php endif; ?>
      </dl>
    <?php endif; ?>

    <div class="prose">
      <?php foreach ($sections as $label => $body): if (!$paras($body)) { continue; } ?>
        <h2 data-reveal><?= esc($label) ?></h2>
        <?php foreach ($paras($body) as $p): ?><p data-reveal="fade"><?= nl2br(esc($p)) ?></p><?php endforeach; ?>
      <?php endforeach; ?>
    </div>

    <?php if ($images): ?>
      <h2 class="display" style="font-size:var(--fs-h3);margin:3.5rem 0 1.25rem">Gallery</h2>
      <div class="cs-gallery" data-stagger>
        <?php foreach ($images as $i => $im): ?>
          <a href="<?= esc($im['url']) ?>" data-lightbox="cs-<?= (int) $cs['id'] ?>" data-caption="<?= esc($im['caption']) ?>" class="img-zoom">
            <img src="<?= esc($im['url']) ?>" alt="<?= esc($im['alt']) ?>" loading="lazy" decoding="async" width="520" height="390">
            <span class="sr-only">Open image <?= $i + 1 ?> larger</span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</article>

<section class="sec sec--dark cta-band">
  <div class="wrap" data-reveal>
    <h2>Want results like this?</h2>
    <p class="lead">Talk to us about your requirement. No obligation.</p>
    <div class="btn-row">
      <a class="btn btn--light" href="<?= base_url('project-enquiry') ?>">Discuss Your Project</a>
      <a class="btn btn--outline-light" href="<?= base_url('case-studies') ?>">All case studies</a>
    </div>
  </div>
</section>
