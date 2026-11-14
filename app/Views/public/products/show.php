<?php
/**
 * Reusable product detail template. Every text value is escaped on output (no HTML is ever stored).
 * Flagship products (is_flagship = 1) get the richer layout: hero capability chips, modules grid,
 * role-based benefits and a "what is included in the demo" panel.
 *
 * @var array       $product    product row + children (features, modules, benefits, audiences, faqs, sections, media)
 * @var bool        $preview    admin draft preview (no enquiry form, noindex)
 * @var array       $screens    [['url','caption','alt'], ...] real images only
 * @var string|null $videoEmbed validated YouTube/Vimeo embed URL
 * @var string|null $signupUrl  validated demo/sign-up URL
 */
$p        = $product;
$flagship = !empty($p['is_flagship']);
$name     = (string) $p['name'];
$arrow    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>';
$paras    = static function (?string $text): array {
    return array_values(array_filter(array_map('trim', preg_split('/\n{2,}/', (string) $text) ?: [])));
};
$icons = [
    'users'     => 'M17 20h5v-2a3 3 0 00-5.4-1.8M17 20H7m10 0v-2M7 20H2v-2a3 3 0 015.4-1.8M7 20v-2m0 0a5 5 0 019.3 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    'clock'     => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'calendar'  => 'M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z',
    'wallet'    => 'M3 7a2 2 0 012-2h13a1 1 0 011 1v2M3 7v10a2 2 0 002 2h14a1 1 0 001-1v-3m-4-4h5v4h-5a2 2 0 010-4zM3 7h16',
    'layers'    => 'M12 3l9 5-9 5-9-5 9-5zm-9 9l9 5 9-5M3 16l9 5 9-5',
    'briefcase' => 'M21 13V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6m18 0v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5m18 0H3m6-8V3h6v2',
    'target'    => 'M12 21a9 9 0 100-18 9 9 0 000 18zm0-5a4 4 0 100-8 4 4 0 000 8zm0-2a2 2 0 100-4 2 2 0 000 4z',
    'file'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z',
    'shield'    => 'M12 3l8 3v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3zm-3 9l2 2 4-4',
    'lock'      => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4',
    'check'     => 'M5 13l4 4L19 7',
];
$icon = static function (?string $k) use ($icons): string {
    $d = $icons[$k ?? ''] ?? $icons['check'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
};

// Primary / secondary calls to action
$ctaType  = $p['primary_cta_type'] ?? 'enquiry';
$primary  = ['label' => $p['cta_label'] ?: 'Enquire about ' . $name, 'href' => '#enquiry', 'external' => false];
if ($ctaType === 'demo' && !empty($signupUrl)) {
    $primary = ['label' => $p['cta_label'] ?: 'Start free demo', 'href' => $signupUrl, 'external' => true];
} elseif ($ctaType === 'external' && !empty($p['cta_url']) && \App\Services\ProductService::validateUrl($p['cta_url'], true)) {
    $primary = ['label' => $p['cta_label'] ?: 'Learn more', 'href' => $p['cta_url'], 'external' => !str_starts_with($p['cta_url'], '/')];
}
$secondary = $primary['href'] === '#enquiry' ? null : ['label' => $flagship ? 'Talk to our team' : 'Enquire about ' . $name, 'href' => '#enquiry'];
$extAttr   = static fn (array $c) => $c['external'] ? ' target="_blank" rel="noopener noreferrer"' : '';
$modules   = $p['modules'];
$mkBenefitGroups = static function (array $benefits): array {
    $g = [];
    foreach ($benefits as $b) { $g[$b['audience'] ?: 'Everyone'][] = $b; }
    return $g;
};
?>
<link rel="stylesheet" href="<?= base_url('assets/css/products.css') ?>?v=<?= file_exists(FCPATH . 'assets/css/products.css') ? filemtime(FCPATH . 'assets/css/products.css') : 1 ?>">

<?php if (!empty($preview)): ?>
  <div class="tp-preview" role="status">Admin preview &mdash; status: <strong><?= esc(ucfirst($p['status'])) ?></strong>. This page is not indexed and the enquiry form is disabled here.</div>
<?php endif; ?>

<!-- HERO -->
<section class="th-hero tp-hero<?= $flagship ? ' tp-hero--flagship' : '' ?>">
  <div class="page-width th-hero__grid">
    <div>
      <p class="th-crumbs"><a href="<?= base_url() ?>">Home</a> / <a href="<?= base_url('products') ?>">Products</a> / <?= esc($name) ?></p>
      <span class="th-eyebrow"><?= esc($p['category'] ?: 'Product') ?></span>
      <h1 class="th-h1"><?= esc($name) ?><?php if (!empty($p['tagline'])): ?><span class="tp-hero__tag"><?= esc($p['tagline']) ?></span><?php endif; ?></h1>
      <?php if (!empty($p['short_description'])): ?><p class="th-lead"><?= esc($p['short_description']) ?></p><?php endif; ?>
      <div class="th-btn-row" style="margin-top:1.5rem">
        <a class="th-btn th-btn--light" href="<?= esc($primary['href']) ?>"<?= $extAttr($primary) ?>><?= esc($primary['label']) ?> <?= $arrow ?></a>
        <?php if ($secondary): ?><a class="th-btn th-btn--outline-light" href="<?= esc($secondary['href']) ?>"><?= esc($secondary['label']) ?></a><?php endif; ?>
        <?php if ($flagship): ?><a class="th-btn th-btn--outline-light" href="#s-ways-to-adopt">Ways to Adopt</a><?php endif; ?>
        <?php if (!empty($p['docs_url'])): ?><a class="th-btn th-btn--outline-light" href="<?= esc($p['docs_url']) ?>" target="_blank" rel="noopener noreferrer">Documentation</a><?php endif; ?>
      </div>
      <?php if ($flagship && $modules): ?>
        <ul class="th-chips tp-hero__chips" aria-label="Areas covered">
          <?php foreach (array_slice($modules, 0, 8) as $m): ?><li><?= esc($m['title']) ?></li><?php endforeach; ?>
          <?php if (count($modules) > 8): ?><li>+<?= count($modules) - 8 ?> more</li><?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="tp-hero__side">
      <?php if (!empty($heroUrl)): ?>
        <img src="<?= esc($heroUrl) ?>" alt="<?= esc($name) ?> product image" class="tp-hero__img" decoding="async">
      <?php else: ?>
        <div class="tp-hero__panel">
          <?php if (!empty($logoUrl)): ?><img src="<?= esc($logoUrl) ?>" alt="<?= esc($name) ?> logo" width="72" height="72" class="tp-hero__logo" decoding="async"><?php endif; ?>
          <p class="tp-hero__panel-name"><?= esc($name) ?></p>
          <?php if (!empty($p['category'])): ?><p class="tp-hero__panel-cat"><?= esc($p['category']) ?></p><?php endif; ?>
          <?php if ($flagship && !empty($signupUrl)): ?>
            <ul class="tp-hero__points">
              <li>Free 7-day demo workspace</li>
              <li>Role-based access for every user type</li>
              <li>Separate workspace for each organisation</li>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- OVERVIEW -->
<?php if ($paras($p['long_description'])): ?>
<section class="th-section th-section--white" id="overview">
  <div class="page-width tp-narrow">
    <span class="th-eyebrow">Overview</span>
    <h2 class="th-h2">What is <?= esc($name) ?>?</h2>
    <?php foreach ($paras($p['long_description']) as $para): ?><p class="th-prose"><?= nl2br(esc($para)) ?></p><?php endforeach; ?>
    <?php if (!empty($p['pricing_summary'])): ?>
      <p class="tp-pricing">
        <strong>Plans and pricing:</strong> <?= esc($p['pricing_summary']) ?>
        <?php if ($flagship): ?>
          <a href="#s-ways-to-adopt" class="tp-pricing__jump">
            <span>Compare adoption models</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
          </a>
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- KEY FEATURES -->
<?php if ($p['features']): ?>
<section class="th-section" id="features">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Key features</span><h2 class="th-h2">What <?= esc($name) ?> helps you do</h2></div>
    <ul class="th-grid th-grid--3 tp-list">
      <?php foreach ($p['features'] as $f): ?>
        <li class="th-card th-card--static">
          <span class="th-icon"><?= $icon($f['icon']) ?></span>
          <h3 class="th-h3"><?= esc($f['title']) ?></h3>
          <?php if (!empty($f['description'])): ?><p><?= esc($f['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- MODULES -->
<?php if ($modules && $flagship): ?>
<section class="th-section th-section--white" id="modules">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Modules</span><h2 class="th-h2">One platform, the whole employee lifecycle</h2>
      <p class="th-lead">Modules share the same employee data, so a change in one place shows up everywhere it matters.</p></div>
    <ul class="tp-modules">
      <?php foreach ($modules as $i => $m): ?>
        <li class="tp-module"><span class="tp-module__n" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div><h3 class="tp-module__t"><?= esc($m['title']) ?></h3><?php if (!empty($m['description'])): ?><p><?= esc($m['description']) ?></p><?php endif; ?></div></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php elseif ($modules): ?>
<section class="th-section th-section--white" id="modules">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Modules</span><h2 class="th-h2">What is included</h2></div>
    <ul class="th-chips">
      <?php foreach ($modules as $m): ?><li title="<?= esc($m['description'] ?? '') ?>"><?= esc($m['title']) ?></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- BENEFITS -->
<?php if ($p['benefits']): ?>
<section class="th-section" id="benefits">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Benefits</span><h2 class="th-h2"><?= $flagship ? 'What each role gets' : 'Why use ' . esc($name) ?></h2></div>
    <?php if ($flagship): ?>
      <div class="tp-roles">
        <?php foreach ($mkBenefitGroups($p['benefits']) as $aud => $items): ?>
          <section class="th-card th-card--static tp-role" aria-labelledby="role-<?= esc(\App\Services\ProductService::slugify($aud), 'attr') ?>">
            <h3 class="th-h3 tp-role__t" id="role-<?= esc(\App\Services\ProductService::slugify($aud), 'attr') ?>"><?= esc($aud) ?></h3>
            <ul class="tp-role__list">
              <?php foreach ($items as $b): ?>
                <li><strong><?= esc($b['title']) ?></strong><?php if (!empty($b['description'])): ?><span><?= esc($b['description']) ?></span><?php endif; ?></li>
              <?php endforeach; ?>
            </ul>
          </section>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <ul class="th-grid th-grid--3 tp-list">
        <?php foreach ($p['benefits'] as $b): ?>
          <li class="th-card th-card--static"><h3 class="th-h3"><?= esc($b['title']) ?></h3><?php if (!empty($b['description'])): ?><p><?= esc($b['description']) ?></p><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- WHO IT IS FOR -->
<?php if ($p['audiences']): ?>
<section class="th-section th-section--white" id="audience">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Who it is for</span><h2 class="th-h2">Built for the people who use it every day</h2></div>
    <ul class="th-grid th-grid--2 tp-list">
      <?php foreach ($p['audiences'] as $a): ?>
        <li class="th-card th-card--static"><h3 class="th-h3"><?= esc($a['title']) ?></h3><?php if (!empty($a['description'])): ?><p><?= esc($a['description']) ?></p><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- SCREENSHOTS (real images only) -->
<?php if ($screens): ?>
<section class="th-section" id="screenshots">
  <div class="page-width">
    <div class="th-head"><span class="th-eyebrow">Screenshots</span><h2 class="th-h2">See <?= esc($name) ?></h2></div>
    <ul class="tp-gallery">
      <?php foreach ($screens as $s): ?>
        <li><figure class="tp-shot">
          <img src="<?= esc($s['url']) ?>" alt="<?= esc($s['alt']) ?>" loading="lazy" decoding="async">
          <?php if (!empty($s['caption'])): ?><figcaption><?= esc($s['caption']) ?></figcaption><?php endif; ?>
        </figure></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- VIDEO -->
<?php if (!empty($videoEmbed)): ?>
<section class="th-section th-section--white" id="video">
  <div class="page-width tp-narrow">
    <div class="th-head"><span class="th-eyebrow">Video</span><h2 class="th-h2"><?= esc($name) ?> in action</h2></div>
    <div class="tp-video">
      <iframe src="<?= esc($videoEmbed) ?>" title="<?= esc($name) ?> video" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"
              sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"></iframe>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- EXTRA SECTIONS -->
<?php foreach ($p['sections'] as $sec): if (empty($sec['title']) && empty($sec['body'])) { continue; } ?>
  <?php if ($sec['section_key'] === 'ways-to-adopt'): 
      $sParas = $paras($sec['body']);
      $saasLead = $sParas[0] ?? 'Subscription (software as a service). Your organisation uses WorkSphere as a managed service with its own separate workspace. We host it and keep it updated, and you pay a subscription based on the plan and the number of employees, billed monthly, quarterly or yearly. Start with a free 7-day demo, then a live evaluation period, and move to a paid plan only when you decide to.';
      $licLead = $sParas[1] ?? 'Licence with source code (for IT and software companies). A company that wants to own the product can license WorkSphere together with its source code, to host, brand, extend and offer to its own customers. What is handed over, the commercial terms and the support period are agreed with TalentHub in a written agreement before anything is delivered.';
      $helpText = $sParas[2] ?? 'Not sure which fits? Tell us about your organisation in the enquiry form below and we will recommend one.';
      $extraParas = array_slice($sParas, 3);
  ?>
  <section class="th-section tp-adopt-section" id="<?= esc('s-' . ($sec['section_key'] ?: 'ways-to-adopt'), 'attr') ?>" aria-labelledby="heading-ways-to-adopt">
    <div class="page-width">
      <div class="tp-adopt__head">
        <div class="tp-adopt__badge-wrap">
          <svg class="tp-adopt__badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
          <span class="tp-adopt__badge-text">Commercial &amp; Adoption Models</span>
        </div>
        <h2 class="th-h2 tp-adopt__title" id="heading-ways-to-adopt"><?= esc($sec['title']) ?></h2>
        <p class="tp-adopt__lead">Choose between fully managed cloud SaaS with automated updates, or complete source code licensing with white-labelling for your software business.</p>
      </div>

      <div class="tp-adopt__grid">
        <!-- 01: Subscription (SaaS) -->
        <article class="tp-adopt-card tp-adopt-card--saas" aria-labelledby="card-title-subscription">
          <div class="tp-adopt-card__top">
            <div class="tp-adopt-card__icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
            </div>
            <span class="tp-adopt-card__pill tp-adopt-card__pill--saas">Turnkey SaaS &bull; For Organisations</span>
          </div>

          <h3 class="tp-adopt-card__title" id="card-title-subscription">Subscription (software as a service)</h3>
          <p class="tp-adopt-card__target">Ideal for Employers, HR Teams &amp; Enterprises</p>
          
          <p class="tp-adopt-card__body-text"><?= nl2br(esc($saasLead)) ?></p>

          <ul class="tp-adopt-card__features" aria-label="Subscription plan highlights">
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Dedicated Workspace:</strong> <span>Separate isolated database and workspace for each organisation.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Zero Server Maintenance:</strong> <span>We host, secure, backup, and keep the software continuously updated.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Flexible Employee Tiers:</strong> <span>Scaled to your employee count with monthly, quarterly, or yearly billing.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Risk-Free Path:</strong> <span>7-day sandbox demo followed by live evaluation before any paid plan.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Complete Operating HRMS:</strong> <span>Attendance, biometric sync, Indian payroll, leave, performance &amp; letters.</span></div>
            </li>
          </ul>

          <div class="tp-adopt-card__actions">
            <?php if (!empty($signupUrl)): ?>
              <a href="<?= esc($signupUrl) ?>" target="_blank" rel="noopener noreferrer" class="th-btn th-btn--primary">
                <span>Start Free 7-Day Demo</span>
                <?= $arrow ?>
              </a>
            <?php endif; ?>
            <a href="<?= base_url('products/' . $p['slug'] . '?interest=subscription#enquiry') ?>" class="th-btn th-btn--ghost" data-select-engagement="subscription">
              <span>Subscribe for Organisation</span>
            </a>
            <p class="tp-adopt-card__guarantee">
              <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Instant workspace setup &bull; No credit card required &bull; Guided evaluation
            </p>
          </div>
        </article>

        <!-- 02: Licence with Source Code -->
        <article class="tp-adopt-card tp-adopt-card--licence" aria-labelledby="card-title-licence">
          <div class="tp-adopt-card__top">
            <div class="tp-adopt-card__icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            </div>
            <span class="tp-adopt-card__pill tp-adopt-card__pill--licence">Enterprise IP &bull; Full Source Code</span>
          </div>

          <h3 class="tp-adopt-card__title" id="card-title-licence">Licence with source code (for IT and software companies)</h3>
          <p class="tp-adopt-card__target">For IT Firms, MSPs, Software Houses &amp; Systems Integrators</p>

          <p class="tp-adopt-card__body-text"><?= nl2br(esc($licLead)) ?></p>

          <ul class="tp-adopt-card__features" aria-label="Source code licence highlights">
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Complete Source Code Handover:</strong> <span>Full modern codebase, database migrations, and developer documentation.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>White-Labelling &amp; Rebranding:</strong> <span>Re-brand, re-skin, and configure custom domains for your own enterprise clients.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Independent Hosting Freedom:</strong> <span>Host on your own cloud (AWS, Azure, GCP) or private customer infrastructure.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Commercial &amp; Extension Rights:</strong> <span>Build custom modules and offer WorkSphere as a solution or product to your clients.</span></div>
            </li>
            <li class="tp-adopt-card__feature">
              <svg class="tp-adopt-card__feature-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <div><strong>Written Agreement &amp; Support:</strong> <span>Commercial terms, deliverables, and support period agreed before anything is delivered.</span></div>
            </li>
          </ul>

          <div class="tp-adopt-card__actions">
            <a href="<?= base_url('products/' . $p['slug'] . '?interest=licence#enquiry') ?>" class="th-btn th-btn--light" data-select-engagement="licence">
              <span>Request Licence &amp; Source Code Scope</span>
              <?= $arrow ?>
            </a>
            <a href="<?= base_url('products/' . $p['slug'] . '?interest=partner#enquiry') ?>" class="th-btn th-btn--outline-light" data-select-engagement="partner">
              <span>Discuss Reselling &amp; White-Labelling</span>
            </a>
            <p class="tp-adopt-card__guarantee">
              <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              Individually scoped &bull; Tailored handover &bull; Binding written agreement
            </p>
          </div>
        </article>
      </div>

      <!-- Decision Helper -->
      <div class="tp-adopt__help-bar">
        <div class="tp-adopt__help-content">
          <div class="tp-adopt__help-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01"/></svg>
          </div>
          <p class="tp-adopt__help-text"><?= nl2br(esc($helpText)) ?></p>
        </div>
        <a href="#enquiry" class="th-btn th-btn--ghost th-btn--sm tp-adopt__help-btn">
          <span>Help Me Choose</span>
          <?= $arrow ?>
        </a>
      </div>

      <?php if ($extraParas): ?>
        <div class="tp-adopt__extra-notes" style="margin-top:1.5rem">
          <?php foreach ($extraParas as $ep): ?><p class="th-prose"><?= nl2br(esc($ep)) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php elseif ($sec['section_key'] === 'licence-for-it-companies'): 
      $lParas = $paras($sec['body']);
      $licIntro = $lParas[0] ?? 'A licence discussion normally covers: the rights you receive and any limits on them; the source code and documentation to be handed over; deployment and knowledge-transfer support; branding and white-labelling; customisation you want built; the support and maintenance period after delivery; and the price and payment schedule.';
      $licNotice = $lParas[1] ?? 'These are discussion points, not a fixed price list. We scope each licence individually and put the outcome in a written agreement. Organisations that only want to use WorkSphere do not need a licence: a subscription gives full use of the hosted service.';
      $extraLicParas = array_slice($lParas, 2);
      
      $discussionPoints = [
          [
              'num' => '1',
              'title' => 'Rights &amp; Permitted Limits',
              'desc'  => 'The rights you receive and any limits on them &mdash; clear boundaries on deployment, hosting, and commercial client redistribution agreed in writing.',
          ],
          [
              'num' => '2',
              'title' => 'Source Code &amp; Documentation',
              'desc'  => 'The source code and documentation to be handed over &mdash; modern application code repository, database schemas, and developer handover documentation.',
          ],
          [
              'num' => '3',
              'title' => 'Deployment &amp; Knowledge Transfer',
              'desc'  => 'Deployment and knowledge-transfer support &mdash; cloud environment setup assistance, dev-ops walkthroughs, and architecture knowledge transfer.',
          ],
          [
              'num' => '4',
              'title' => 'Branding &amp; White-Labelling',
              'desc'  => 'Branding and white-labelling &mdash; configuring custom corporate identity, logo marks, brand themes, domains, and client portal skins.',
          ],
          [
              'num' => '5',
              'title' => 'Customisation Built to Order',
              'desc'  => 'Customisation you want built &mdash; bespoke modules, custom statutory rules, third-party integrations, or industry-specific workflow adaptations.',
          ],
          [
              'num' => '6',
              'title' => 'Support &amp; Maintenance Period',
              'desc'  => 'The support and maintenance period after delivery &mdash; SLA-backed post-handover warranty, framework security updates, and technical advisory.',
          ],
          [
              'num' => '7',
              'title' => 'Price &amp; Payment Milestones',
              'desc'  => 'The price and payment schedule &mdash; transparent milestone-based commercial schedule aligned with delivery phases and technical sign-offs.',
          ],
      ];
  ?>
  <section class="th-section tp-licence-section" id="<?= esc('s-' . ($sec['section_key'] ?: 'licence-for-it-companies'), 'attr') ?>" aria-labelledby="heading-licence-it">
    <div class="page-width">
      <div class="tp-licence__head">
        <div class="tp-licence__badge-wrap">
          <svg class="tp-licence__badge-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          <span class="tp-licence__badge-text">IT &amp; Software Company Partnership</span>
        </div>
        <h2 class="th-h2 tp-licence__title" id="heading-licence-it"><?= esc($sec['title']) ?></h2>
        <p class="tp-licence__lead"><?= nl2br(esc($licIntro)) ?></p>
      </div>

      <!-- The 7 Discussion Points Grid -->
      <ul class="tp-licence__grid" aria-label="Licence discussion points">
        <?php foreach ($discussionPoints as $dp): ?>
          <li class="tp-licence-item">
            <span class="tp-licence-item__num" aria-hidden="true"><?= $dp['num'] ?></span>
            <div class="tp-licence-item__content">
              <h3 class="tp-licence-item__title"><?= $dp['title'] ?></h3>
              <p class="tp-licence-item__desc"><?= $dp['desc'] ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Transparency & Boundary Notice -->
      <aside class="tp-licence__notice" aria-label="Licensing terms clarification">
        <svg class="tp-licence__notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <p class="tp-licence__notice-p"><?= nl2br(esc($licNotice)) ?></p>
      </aside>

      <!-- Action Strip -->
      <div class="tp-licence__cta-bar">
        <div>
          <p class="tp-licence__cta-text">Looking to own, customize, or resell WorkSphere?</p>
          <p class="tp-licence__cta-sub">We scope each licence individually and put the outcome in a written agreement.</p>
        </div>
        <a href="<?= base_url('products/' . $p['slug'] . '?interest=licence#enquiry') ?>" class="th-btn th-btn--light tp-licence__cta-btn" data-select-engagement="licence">
          <span>Initiate Licence Discussion</span>
          <?= $arrow ?>
        </a>
      </div>

      <?php if ($extraLicParas): ?>
        <div class="tp-licence__extra-notes" style="margin-top:1.5rem">
          <?php foreach ($extraLicParas as $elp): ?><p class="th-prose"><?= nl2br(esc($elp)) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php else: ?>
  <section class="th-section" id="<?= esc('s-' . ($sec['section_key'] ?: 'extra'), 'attr') ?>">
    <div class="page-width tp-narrow">
      <?php if (!empty($sec['title'])): ?><h2 class="th-h2"><?= esc($sec['title']) ?></h2><?php endif; ?>
      <?php foreach ($paras($sec['body']) as $para): ?><p class="th-prose"><?= nl2br(esc($para)) ?></p><?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
<?php endforeach; ?>

<!-- DEMO INFORMATION (flagship) -->
<?php if ($flagship && !empty($signupUrl)): ?>
<section class="th-section th-section--white" id="demo">
  <div class="page-width tp-narrow">
    <span class="th-eyebrow">Free demo</span>
    <h2 class="th-h2">What is included in the 7-day demo</h2>
    <ul class="tp-checks">
      <li>A trial <?= esc($name) ?> workspace you can explore for 7 days.</li>
      <li>Sign-up takes place on the <?= esc($name) ?> website; you do not need to talk to us first.</li>
      <li>The demo is for evaluation only. Please do not enter real employee, salary or statutory data.</li>
      <li>Plans, pricing, data migration and customisation are discussed with our team, not in the demo.</li>
    </ul>
    <div class="th-btn-row" style="margin-top:1.25rem">
      <a class="th-btn th-btn--primary" href="<?= esc($signupUrl) ?>" target="_blank" rel="noopener noreferrer">Start free 7-day demo <?= $arrow ?></a>
      <a class="th-btn th-btn--ghost" href="#enquiry">Talk to our team</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<?php if ($p['faqs']): ?>
<section class="th-section" id="faq">
  <div class="page-width">
    <div class="th-head th-head--center"><span class="th-eyebrow">FAQ</span><h2 class="th-h2">Frequently asked questions</h2></div>
    <div class="th-faq">
      <?php foreach ($p['faqs'] as $f): ?>
        <details><summary><?= esc($f['question']) ?><svg class="th-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></summary><div class="th-faq__a"><?= nl2br(esc($f['answer'])) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ENQUIRY -->
<section class="th-section th-section--dark" id="enquiry">
  <div class="page-width tp-enq">
    <div class="tp-enq__info">
      <div>
        <span class="th-eyebrow"><?= $flagship ? 'Talk to our team' : 'Enquire' ?></span>
        <h2 class="th-h2 tp-enq__title">Interested in <?= esc($name) ?>?</h2>
        <p class="tp-enq__p">Tell us a little about your organisation and what you need. A TalentHub consultant will review your requirements and provide tailored deployment guidance with zero obligation.</p>
      </div>

      <!-- What to Expect / Consultation Journey -->
      <ul class="tp-enq__steps" aria-label="Consultation process">
        <li class="tp-enq__step">
          <span class="tp-enq__step-num">1</span>
          <div>
            <h3 class="tp-enq__step-title">Workflow Assessment</h3>
            <p class="tp-enq__step-desc">We review your workforce scale, shift policies, attendance devices, and payroll cycle.</p>
          </div>
        </li>
        <li class="tp-enq__step">
          <span class="tp-enq__step-num">2</span>
          <div>
            <h3 class="tp-enq__step-title">Tailored Demonstration</h3>
            <p class="tp-enq__step-desc">Guided walkthrough of WorkSphere configured to your company hierarchy and statutory tax rules.</p>
          </div>
        </li>
        <li class="tp-enq__step">
          <span class="tp-enq__step-num">3</span>
          <div>
            <h3 class="tp-enq__step-title">Seamless Onboarding</h3>
            <p class="tp-enq__step-desc">Zero-downtime employee data migration, statutory setup, and role-based staff training.</p>
          </div>
        </li>
      </ul>

      <!-- Enterprise Compliance & Security Tags -->
      <div class="tp-enq__trust-row">
        <span class="tp-enq__trust-tag"><svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Statutory Compliance Ready</span>
        <span class="tp-enq__trust-tag"><svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Role-Based Access Control</span>
        <span class="tp-enq__trust-tag"><svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Dedicated Support</span>
      </div>

      <!-- Self-Serve Demo Callout Box -->
      <?php if (!empty($signupUrl) && $flagship): ?>
        <div class="tp-enq__demo-box">
          <p class="tp-enq__demo-text">Prefer to look around yourself first?</p>
          <a class="tp-enq__demo-btn" href="<?= esc($signupUrl) ?>" target="_blank" rel="noopener noreferrer">
            <span>Start free 7-day demo</span>
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>
          </a>
        </div>
      <?php endif; ?>
    </div>
    <div class="tp-form-card">
      <?php if (!empty($preview)): ?>
        <p class="tp-form-note">The enquiry form is shown on the live page. It is disabled in preview.</p>
      <?php else: ?>
        <?php if (session()->getFlashdata('product_enquiry_error')): ?>
          <div class="tp-alert" role="alert"><?= esc(session()->getFlashdata('product_enquiry_error')) ?></div>
        <?php endif; ?>
        <form action="<?= esc($enquiryAction) ?>" method="POST" class="tp-form" novalidate>
          <?= csrf_field() ?>
          <div class="tp-field"><label for="pe-name">Your name <span aria-hidden="true">*</span></label>
            <input id="pe-name" type="text" name="client_name" required maxlength="150" autocomplete="name" value="<?= esc(old('client_name') ?? '') ?>"></div>
          <div class="tp-field"><label for="pe-company">Company / organisation</label>
            <input id="pe-company" type="text" name="company" maxlength="150" autocomplete="organization" value="<?= esc(old('company') ?? '') ?>"></div>
          <div class="tp-row">
            <div class="tp-field"><label for="pe-email">Email <span aria-hidden="true">*</span></label>
              <input id="pe-email" type="email" name="email" required maxlength="190" autocomplete="email" value="<?= esc(old('email') ?? '') ?>"></div>
            <div class="tp-field"><label for="pe-phone">Phone <span aria-hidden="true">*</span></label>
              <input id="pe-phone" type="tel" name="phone" required maxlength="25" autocomplete="tel" placeholder="+91 ..." value="<?= esc(old('phone') ?? '') ?>"></div>
          </div>
          <?php if ($flagship): ?>
          <div class="tp-field"><label for="pe-engagement">I am interested in</label>
            <select id="pe-engagement" name="engagement">
              <?php foreach (\App\Controllers\Web\ProductsController::ENGAGEMENTS as $val => $label): ?>
                <option value="<?= esc($val, 'attr') ?>"<?= (old('engagement') ?? ($_GET['interest'] ?? '')) === $val ? ' selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select></div>
          <?php endif; ?>
          <div class="tp-field"><label for="pe-msg">What do you need? <span aria-hidden="true">*</span></label>
            <textarea id="pe-msg" name="project_description" required minlength="10" maxlength="3900" rows="4" placeholder="Number of employees, modules you are interested in, timeline..."><?= esc(old('project_description') ?? '') ?></textarea></div>
          <?= $formProtection ?? '' ?>
          <div class="tp-consent">
            <input type="checkbox" name="consent" id="pe-consent" value="1" required>
            <label for="pe-consent">I consent to TalentHub contacting me about this enquiry in line with the <a href="<?= base_url('privacy') ?>" target="_blank" rel="noopener">Privacy Policy</a>.</label>
          </div>
          <button type="submit" class="th-btn th-btn--primary tp-submit">Send enquiry</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var triggers = document.querySelectorAll('[data-select-engagement]');
  var select = document.getElementById('pe-engagement');
  var enquiry = document.getElementById('enquiry');
  triggers.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      var val = this.getAttribute('data-select-engagement');
      if (select && val) {
        select.value = val;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        if (enquiry) {
          e.preventDefault();
          enquiry.scrollIntoView({ behavior: 'smooth' });
          select.classList.remove('tp-field--focus-pulse');
          void select.offsetWidth;
          select.classList.add('tp-field--focus-pulse');
          setTimeout(function () {
            select.focus();
          }, 350);
        }
      }
    });
  });
});
</script>
