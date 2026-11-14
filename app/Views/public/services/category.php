<?php
/** @var array $category @var array $capabilities @var array $journey @var bool $isRecruit */
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>';
if ($isRecruit) {
    $primary   = ['Discuss Your Hiring Requirements', base_url('employers') . '#hiring-enquiry'];
    $secondary = ['Submit a Hiring Enquiry', base_url('contact?subject=' . rawurlencode('Hiring Talent'))];
    $jTitle    = 'Our recruitment delivery journey';
    $jLead     = 'Eight connected stages from first conversation to post-placement follow-up. Each stage ends with a clear output. Timelines depend on the role and market, and outcomes are not guaranteed.';
    $artKind   = 'recruitment';
    $what      = 'Capabilities';
    $faqs = [
      ['What types of hiring do you handle?', 'Permanent, contract, specialist, executive and high-volume hiring for IT and non-IT roles across India.'],
      ['How do we start a hiring engagement?', 'Share your requirement through the enquiry form. A consultant will contact you to confirm role details, expectations and timelines.'],
      ['Can you support contract or bulk hiring?', 'Yes. Contract staffing and structured high-volume hiring are part of our recruitment capabilities.'],
      ['Do you guarantee placements or joining dates?', 'No. We follow a structured process and agree follow-ups with you, but we do not guarantee placement, joining or timelines.'],
    ];
    $svcSchemaName = 'Recruitment & Talent Acquisition';
} else {
    $primary   = ['Discuss Your Project', base_url('project-enquiry')];
    $secondary = ['Request a Consultation', base_url('contact?subject=' . rawurlencode('IT Services & Software Development'))];
    $jTitle    = 'Our IT project delivery lifecycle';
    $jLead     = 'Eight stages from business discovery to ongoing improvement. Project delivery (stages 1–7) is separate from optional ongoing maintenance (stage 8), which is agreed as its own engagement.';
    $artKind   = 'it';
    $what      = 'Technology capabilities';
    $faqs = [
      ['What kinds of software do you build?', 'Custom software, websites, web and mobile applications, enterprise portals, APIs and ERP/HRMS workflows, built around your requirements.'],
      ['Which technologies do you work with?', 'PHP and CodeIgniter, Python and Django, React and modern frontend, REST APIs, and MySQL/PostgreSQL databases, chosen to suit the project.'],
      ['How long will my project take?', 'It depends on scope and complexity. After requirement analysis we agree a roadmap and timeline with you. We do not quote fixed timelines before understanding the scope.'],
      ['Do you provide support after go-live?', 'Yes. Support, maintenance and enhancements are available as optional engagements, separate from the project delivery.'],
    ];
    $svcSchemaName = 'IT Services & Software Development';
}
$schema = [
  '@context' => 'https://schema.org', '@type' => 'Service', 'name' => $svcSchemaName,
  'description' => $category['summary'], 'serviceType' => $svcSchemaName,
  'provider' => ['@type' => 'Organization', 'name' => 'TalentHub Consultant', 'url' => base_url()],
  'areaServed' => ['@type' => 'Country', 'name' => 'India'],
];
$crumbs = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [
  ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => base_url()],
  ['@type' => 'ListItem', 'position' => 2, 'name' => 'Services', 'item' => base_url('services')],
  ['@type' => 'ListItem', 'position' => 3, 'name' => $category['name'], 'item' => base_url('services/' . $category['slug'])],
]];
$flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG;
?>
<script type="application/ld+json"><?= json_encode($schema, $flags) ?></script>
<script type="application/ld+json"><?= json_encode($crumbs, $flags) ?></script>

<section class="th-hero">
  <div class="page-width th-hero__grid">
    <div>
      <p class="th-crumbs" data-hero><a href="<?= base_url() ?>">Home</a> / <a href="<?= base_url('services') ?>">Services</a> / <?= esc($category['name']) ?></p>
      <span class="th-eyebrow" data-hero style="--d:.05s">Service <?= $isRecruit ? '01' : '02' ?></span>
      <h1 class="th-h1" data-hero style="--d:.12s"><?= esc($category['name']) ?></h1>
      <p class="th-lead" data-hero style="--d:.2s;color:#fff;font-weight:600"><?= esc($category['tagline']) ?></p>
      <p class="th-lead" data-hero style="--d:.26s"><?= esc($category['summary']) ?></p>
      <div class="th-btn-row" style="margin-top:1.75rem" data-hero>
        <a class="th-btn th-btn--light" href="<?= $primary[1] ?>"><?= esc($primary[0]) ?> <?= $arrow ?></a>
        <a class="th-btn th-btn--outline-light" href="<?= $secondary[1] ?>"><?= esc($secondary[0]) ?></a>
      </div>
    </div>
    <div class="th-hero__art" data-hero style="--d:.3s"><?= view('public/partials/th_art', ['kind' => $artKind]) ?></div>
  </div>
</section>

<section class="th-section th-section--white">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow"><?= esc($what) ?></span>
      <h2 class="th-h2"><?= $isRecruit ? 'Recruitment capabilities under one service' : 'Capabilities within IT Services' ?></h2>
      <p class="th-lead"><?= $isRecruit ? 'These are ways we deliver recruitment, not separate services.' : 'These are capabilities within one service. Scope is agreed per engagement.' ?></p>
    </div>
    <ul class="th-chips" data-reveal>
      <?php foreach ($capabilities as $cap): ?><li><?= esc($cap) ?></li><?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="th-section" id="journey">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow">Delivery journey</span>
      <h2 class="th-h2"><?= esc($jTitle) ?></h2>
      <p class="th-lead"><?= esc($jLead) ?></p>
    </div>
    <ol class="th-journey" aria-label="<?= esc($jTitle) ?>">
      <li class="th-journey__progress" aria-hidden="true"></li>
      <?php foreach ($journey as $n => $s): ?>
        <li class="th-step" data-reveal style="--d:.04s">
          <span class="th-step__marker" aria-hidden="true"><?= sprintf('%02d', $n + 1) ?></span>
          <details <?= $n === 0 ? 'open' : '' ?>>
            <summary>
              <span>
                <span class="th-step__label">Step <?= sprintf('%02d', $n + 1) ?></span>
                <span class="th-step__title"><?= esc($s[0]) ?></span>
                <span class="th-step__desc"><?= esc($s[1]) ?></span>
              </span>
              <svg class="th-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </summary>
            <div class="th-step__more">
              <ul><?php foreach ($s[3] as $a): ?><li><?= esc($a) ?></li><?php endforeach; ?></ul>
              <span class="th-step__out"><?= $isRecruit ? 'Output' : 'Deliverable' ?>: <?= esc($s[2]) ?></span>
            </div>
          </details>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="th-section th-section--white">
  <div class="page-width">
    <div class="th-head th-head--center" data-reveal><span class="th-eyebrow">FAQ</span><h2 class="th-h2">Common questions</h2></div>
    <div class="th-faq">
      <?php foreach ($faqs as $f): ?>
        <details data-reveal><summary><?= esc($f[0]) ?><svg class="th-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></summary><div class="th-faq__a"><?= esc($f[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="th-section th-section--dark th-cta">
  <div class="page-width" data-reveal>
    <h2 class="th-h2"><?= $isRecruit ? 'Ready to discuss your hiring needs?' : 'Ready to discuss your project?' ?></h2>
    <p class="th-lead" style="margin:0 auto 1.5rem">Tell us what you need. A TalentHub consultant will respond to discuss scope and next steps.</p>
    <div class="th-btn-row">
      <a class="th-btn th-btn--light" href="<?= $primary[1] ?>"><?= esc($primary[0]) ?></a>
      <a class="th-btn th-btn--outline-light" href="<?= $secondary[1] ?>"><?= esc($secondary[0]) ?></a>
    </div>
  </div>
</section>
