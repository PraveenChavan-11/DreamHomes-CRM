<?php
/** @var array $categories */
$arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>';
$faqs = [
  ['What does TalentHub do?', 'TalentHub is a recruitment and technology consulting partner. We help organisations hire the right people and build the right software, from one point of contact.'],
  ['Which kinds of hiring do you support?', 'Permanent, contract, specialist, executive and high-volume hiring across IT and non-IT roles, for startups, SMEs and enterprises across India.'],
  ['What kinds of software projects do you take on?', 'Custom websites, web applications, enterprise portals, mobile and progressive web apps, APIs, ERP/HRMS workflows and modernisation of existing systems.'],
  ['How long does a project or hiring engagement take?', 'It depends on scope, role seniority and market conditions. We confirm requirements first and agree realistic timelines in writing. We do not promise fixed placement or delivery dates in advance.'],
  ['What happens after I submit an enquiry?', 'A TalentHub consultant reviews your requirement and contacts you to discuss scope, expectations and next steps. There is no obligation.'],
  ['Do you offer support after delivery?', 'Yes, through optional maintenance and support engagements agreed separately from project delivery.'],
];
?>
<section class="th-hero">
  <div class="page-width th-hero__grid">
    <div>
      <p class="th-crumbs" data-hero style="--d:.02s"><a href="<?= base_url() ?>">Home</a> / Services</p>
      <span class="th-eyebrow" data-hero style="--d:.05s">Services</span>
      <h1 class="th-h1" data-hero style="--d:.12s">People. Technology. Business Transformation.</h1>
      <p class="th-lead" data-hero style="--d:.22s">From building exceptional teams to delivering powerful digital solutions, TalentHub helps businesses move forward with confidence.</p>
      <div class="th-btn-row" style="margin-top:1.75rem" data-hero>
        <a class="th-btn th-btn--light" href="<?= base_url('contact') ?>" style="--d:.32s">Discuss Your Requirements <?= $arrow ?></a>
        <a class="th-btn th-btn--outline-light" href="#two-services">Explore Our Services</a>
      </div>
    </div>
    <div class="th-hero__art" aria-hidden="false" data-hero style="--d:.3s"><?= view('public/partials/th_art', ['kind' => 'both']) ?></div>
  </div>
</section>

<section class="th-section" id="two-services">
  <div class="page-width">
    <div class="th-head th-head--center" data-reveal>
      <span class="th-eyebrow">Two services. One partner.</span>
      <h2 class="th-h2">Hire the right people. Build the right systems.</h2>
    </div>
    <div class="th-grid th-grid--2">
      <?php $i = 0; foreach ($categories as $c): $isR = $c['slug'] === 'recruitment'; $shown = array_slice($c['capabilities'], 0, 8); ?>
        <a href="<?= base_url('services/' . $c['slug']) ?>" class="th-card th-service-card" data-reveal style="--d:<?= $i++ * .12 ?>s" aria-label="<?= esc($c['name']) ?>">
          <div class="th-service-card__art"><?= view('public/partials/th_art', ['kind' => $isR ? 'recruitment' : 'it']) ?></div>
          <div class="th-service-card__body">
            <h3 class="th-h3" style="font-size:1.4rem"><?= esc($c['name']) ?></h3>
            <p style="color:var(--th-teal);font-weight:600"><?= esc($c['tagline']) ?></p>
            <p><?= esc($c['summary']) ?></p>
            <ul class="th-chips" aria-label="Capabilities">
              <?php foreach ($shown as $cap): ?><li><?= esc($cap) ?></li><?php endforeach; ?>
              <?php if (count($c['capabilities']) > count($shown)): ?><li>+<?= count($c['capabilities']) - count($shown) ?> more</li><?php endif; ?>
            </ul>
            <span class="th-link th-service-card__cta"><?= $isR ? 'Explore Recruitment' : 'Explore IT Services' ?> &rarr;</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="th-section th-section--white">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow">Why TalentHub</span>
      <h2 class="th-h2">Why organisations choose TalentHub</h2>
      <p class="th-lead">A practical, requirement-led partner for hiring and technology delivery.</p>
    </div>
    <div class="th-grid th-grid--3">
      <?php foreach ([
        ['Requirement-led engagement', 'We start with your business objective and agree scope before any work begins.'],
        ['Structured delivery', 'Defined stages with clear outputs, so you always know what happens next.'],
        ['Clear communication', 'A single point of contact and regular updates throughout the engagement.'],
        ['Talent and technology under one roof', 'Hire a team and build the systems they will use, with one partner.'],
        ['Business-focused solutions', 'Solutions designed around real operational needs, not generic templates.'],
        ['Quality-focused execution', 'Review and testing built into the process, not added at the end.'],
      ] as $k => $w): ?>
        <div class="th-card th-card--static" data-reveal style="--d:<?= ($k % 3) * .08 ?>s"><h3 class="th-h3"><?= esc($w[0]) ?></h3><p><?= esc($w[1]) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="th-section">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow">Our approach</span>
      <h2 class="th-h2">How we work with clients</h2>
      <p class="th-lead">Every engagement, recruitment or technology, follows the same disciplined arc.</p>
    </div>
    <ol class="th-flow">
      <?php foreach ([['Discover', 'Learn your goals'], ['Understand', 'Define requirements'], ['Plan', 'Agree scope and approach'], ['Execute', 'Hire or build'], ['Validate', 'Review and test'], ['Deliver', 'Hand over / place'], ['Improve', 'Follow up and refine']] as $k => $f): ?>
        <li data-reveal style="--d:<?= $k * .06 ?>s"><b><?= $k + 1 ?></b><strong><?= $f[0] ?></strong><span><?= $f[1] ?></span></li>
      <?php endforeach; ?>
    </ol>
    <p class="th-prose" style="margin-top:1.25rem">See the detailed delivery journeys: <a class="th-link" href="<?= base_url('services/recruitment#journey') ?>">recruitment</a> and <a class="th-link" href="<?= base_url('services/it-services#journey') ?>">IT project delivery</a>.</p>
  </div>
</section>


<section class="th-section">
  <div class="page-width">
    <div class="th-head th-head--center" data-reveal><span class="th-eyebrow">FAQ</span><h2 class="th-h2">Frequently asked questions</h2></div>
    <div class="th-faq">
      <?php foreach ($faqs as $f): ?>
        <details data-reveal><summary><?= esc($f[0]) ?><svg class="th-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></summary><div class="th-faq__a"><?= esc($f[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="th-section th-section--dark th-cta">
  <div class="page-width" data-reveal>
    <h2 class="th-h2">Have a Business Challenge? Let's Build the Right Solution.</h2>
    <p class="th-lead" style="margin:0 auto 1.5rem">Whether you need the right talent or a technology partner, start a conversation with TalentHub.</p>
    <div class="th-btn-row">
      <a class="th-btn th-btn--light" href="<?= base_url('contact') ?>">Start a Conversation</a>
      <a class="th-btn th-btn--outline-light" href="<?= base_url('contact') ?>">Contact TalentHub</a>
    </div>
  </div>
</section>
