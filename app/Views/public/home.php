<?php
/**
 * TalentHub Consultant — Master Homepage Experience
 * Launch UI Inspired Architecture — Premium, Fully Dynamic, Accessible & Lead-Generating
 */

$hero = $content['hero'] ?? [];
?>
<?php $arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true" class="w-4 h-4 transition-transform"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg>'; ?>
<!-- 01 HERO SLIDER (HIRXPERT THEME MASTER ARCHITECTURE) -->
<section class="th-hero th-hirx-hero" aria-labelledby="home-h1" id="hero-slider">
  <!-- Ghost Watermark Background Word -->
  <div class="th-hirx-watermark" id="th-hirx-watermark" aria-hidden="true">Solutions.</div>

  <!-- Abstract Mosaic Dissolve Transition Overlay -->
  <div class="th-hirx-mosaic" id="th-hirx-mosaic" aria-hidden="true">
    <?php for ($i = 0; $i < 24; $i++): ?>
      <div class="th-mosaic-tile" style="--tile-i: <?= $i ?>;"></div>
    <?php endfor; ?>
  </div>

  <!-- Slides Track -->
  <div class="th-hirx-track">

    <!-- SLIDE 1 -->
    <div class="th-hirx-slide is-active" data-index="0" data-watermark="Solutions.">
      <div class="th-hirx-slide-bg" style="background-image: url('<?= base_url('images/hirxpert-slide-1.jpg') ?>');"></div>
      <div class="th-hirx-slide-overlay"></div>
      
      <div class="page-width th-hirx-slide-grid">
        <div class="th-hirx-slide-left">
          <div class="th-hirx-tag" data-hirx-layer="1">
            <span class="th-hirx-tag-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V6m0 0l-4 4m4-4l4 4M5 12a7 7 0 0114 0"/></svg>
            </span>
            <span>MAKE YOUR VISION TO REALITY</span>
          </div>

          <h1 class="th-hirx-h1" id="home-h1" data-hirx-layer="2">
            Building Better Teams. Creating Better Technology.
          </h1>

          <p class="th-hirx-lead" data-hirx-layer="3">
            TalentHub connects businesses with the right people and delivers digital solutions that simplify operations, strengthen capabilities and accelerate growth.
          </p>

          <div class="th-hirx-actions" data-hirx-layer="4">
            <a class="th-hirx-btn th-hirx-btn--primary font-bold" href="<?= base_url('contact') ?>">
              <span>Discuss Your Requirements</span>
              <span class="th-hirx-btn-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
              </span>
            </a>
            <a class="th-hirx-btn th-hirx-btn--outline font-bold" href="<?= base_url('services') ?>">
              <span>Explore Our Services</span>
            </a>
          </div>
        </div>

        <div class="th-hirx-slide-visual hidden lg:block" data-hirx-layer="5"></div>
      </div>
    </div>

    <!-- SLIDE 2 -->
    <div class="th-hirx-slide" data-index="1" data-watermark="Strategy.">
      <div class="th-hirx-slide-bg" style="background-image: url('<?= base_url('images/hirxpert-slide-3.jpg') ?>');"></div>
      <div class="th-hirx-slide-overlay"></div>

      <div class="page-width th-hirx-slide-grid">
        <div class="th-hirx-slide-left">
          <div class="th-hirx-tag" data-hirx-layer="1">
            <span class="th-hirx-tag-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V6m0 0l-4 4m4-4l4 4M5 12a7 7 0 0114 0"/></svg>
            </span>
            <span>MAKE YOUR VISION TO REALITY</span>
          </div>

          <h2 class="th-hirx-h1" data-hirx-layer="2">
            Partner in Modern<br>HR Solutions.
          </h2>

          <p class="th-hirx-lead" data-hirx-layer="3">
            We believe in thinking ahead and creating solutions that meet today's challenges and pave the way for future success across your organization.
          </p>

          <div class="th-hirx-actions" data-hirx-layer="4">
            <a class="th-hirx-btn th-hirx-btn--primary font-bold" href="<?= base_url('contact') ?>">
              <span>Discuss Your Requirements</span>
              <span class="th-hirx-btn-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
              </span>
            </a>
            <a class="th-hirx-btn th-hirx-btn--outline font-bold" href="<?= base_url('services/recruitment') ?>">
              <span>Recruitment Services</span>
            </a>
          </div>
        </div>

        <div class="th-hirx-slide-visual hidden lg:block" data-hirx-layer="5"></div>
      </div>
    </div>

    <!-- SLIDE 3 -->
    <div class="th-hirx-slide" data-index="2" data-watermark="Excellence.">
      <div class="th-hirx-slide-bg" style="background-image: url('<?= base_url('images/hirxpert-slide-2.jpg') ?>');"></div>
      <div class="th-hirx-slide-overlay"></div>

      <div class="page-width th-hirx-slide-grid">
        <div class="th-hirx-slide-left">
          <div class="th-hirx-tag" data-hirx-layer="1">
            <span class="th-hirx-tag-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V6m0 0l-4 4m4-4l4 4M5 12a7 7 0 0114 0"/></svg>
            </span>
            <span>MAKE YOUR VISION TO REALITY</span>
          </div>

          <h2 class="th-hirx-h1" data-hirx-layer="2">
            HR &amp; Technology<br>Excellence Starts Here.
          </h2>

          <p class="th-hirx-lead" data-hirx-layer="3">
            Build custom software, scalable cloud architectures, APIs and business software engineered around how your enterprise actually operates.
          </p>

          <div class="th-hirx-actions" data-hirx-layer="4">
            <a class="th-hirx-btn th-hirx-btn--primary font-bold" href="<?= base_url('contact') ?>">
              <span>Discuss Your Requirements</span>
              <span class="th-hirx-btn-circle">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
              </span>
            </a>
            <a class="th-hirx-btn th-hirx-btn--outline font-bold" href="<?= base_url('services/it-services') ?>">
              <span>Explore IT Solutions</span>
            </a>
          </div>
        </div>

        <div class="th-hirx-slide-visual hidden lg:block" data-hirx-layer="5"></div>
      </div>
    </div>

  </div>

  <!-- HIRXPERT BOTTOM BAR: SCROLL TO EXPLORE (LEFT) & HOTLINE + SLIDE ARROWS (RIGHT) -->
  <div class="th-hirx-bottom-bar page-width">
    <div class="th-hirx-bottom-left">
      <a href="#what-we-do" class="th-hirx-scroll-pill" aria-label="Scroll to explore">
        <span class="th-hirx-scroll-circle">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        </span>
        <span class="th-hirx-scroll-label">Scroll To Explore</span>
      </a>
    </div>

    <div class="th-hirx-bottom-right">
      <div class="th-hirx-hotline-wrap">
        <span class="th-hirx-hotline-lbl">Pan-India Hiring Hotline</span>
        <a href="tel:+917276146573" class="th-hirx-phone-link">+(91) 72761-46573</a>
      </div>
      <div class="th-hirx-arrows">
        <button type="button" class="th-hirx-nav-btn" id="th-hirx-prev" aria-label="Previous Slide">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" class="th-hirx-nav-btn" id="th-hirx-next" aria-label="Next Slide">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>
  </div>
</section>

<!-- 02 BUSINESS POSITIONING -->
<section class="th-section" id="what-we-do">
  <div class="page-width">
    <div class="th-head th-head--center" data-reveal>
      <span class="th-eyebrow">What we do</span>
      <h2 class="th-h2">One Partner. Two Powerful Capabilities.</h2>
      <p class="th-lead">TalentHub is a recruitment and technology consulting partner helping startups, SMEs and enterprises hire the right people, develop digital solutions and modernise business operations.</p>
    </div>
    <div class="th-grid th-grid--2">
      <a href="<?= base_url('services/recruitment') ?>" class="th-card th-service-card" data-reveal>
        <div class="th-service-card__art"><?= view('public/partials/th_art', ['kind' => 'recruitment']) ?></div>
        <div class="th-service-card__body">
          <span class="th-eyebrow">Service 01</span>
          <h3 class="th-h3" style="font-size:1.4rem">Recruitment &amp; Talent Acquisition</h3>
          <p>Structured permanent, contract, specialist and high-volume hiring across India, from sourcing and screening to offer and joining coordination.</p>
          <span class="th-link th-service-card__cta">Explore Recruitment &rarr;</span>
        </div>
      </a>
      <a href="<?= base_url('services/it-services') ?>" class="th-card th-service-card" data-reveal style="--d:.12s">
        <div class="th-service-card__art"><?= view('public/partials/th_art', ['kind' => 'it']) ?></div>
        <div class="th-service-card__body">
          <span class="th-eyebrow">Service 02</span>
          <h3 class="th-h3" style="font-size:1.4rem">IT Services &amp; Software Development</h3>
          <p>Custom websites, web applications, enterprise portals, APIs and business software designed around real operational requirements.</p>
          <span class="th-link th-service-card__cta">Explore IT Services &rarr;</span>
        </div>
      </a>
    </div>
  </div>
</section>

<!-- 03 WHY TALENTHUB -->
<section class="th-section th-section--white">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow">Why TalentHub</span>
      <h2 class="th-h2">A Practical Partner for Your Business Goals.</h2>
    </div>
    <div class="th-grid th-grid--3">
      <?php
      $why = [
        ['Requirement-led engagement', 'We begin with your objectives and agree scope before work starts.', 'M9 12l2 2 4-4M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Structured delivery', 'Defined stages with clear outputs at every step.', 'M4 6h16M4 12h10M4 18h6'],
        ['Clear communication', 'One point of contact and regular progress updates.', 'M8 10h8M8 14h5M21 12c0 4-4 7-9 7a10 10 0 01-3-.5L4 20l1.5-4C4.5 14.8 3 13.5 3 12c0-4 4-7 9-7s9 3 9 7z'],
        ['Business-focused solutions', 'Built around how your organisation actually operates.', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6'],
        ['Talent and technology expertise', 'Hire a team and build the systems they will use, with one partner.', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21v-1a6 6 0 016-6h4a6 6 0 016 6v1'],
        ['Quality-focused execution', 'Review and testing are part of the process, not an afterthought.', 'M5 13l4 4L19 7'],
      ];
      foreach ($why as $k => $w): ?>
        <div class="th-card th-card--static" data-reveal style="--d:<?= ($k % 3) * .08 ?>s">
          <span class="th-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $w[2] ?>"/></svg></span>
          <h3 class="th-h3"><?= esc($w[0]) ?></h3><p><?= esc($w[1]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 04 HOW WE WORK (REAL-TIME ANIMATED FLOW WITH MOVING DASHES & PULSES) -->
<section class="th-section" aria-labelledby="how-we-work-heading">
  <div class="page-width">
    <div class="th-head" data-reveal>
      <span class="th-eyebrow">How We Work</span>
      <h2 class="th-h2" id="how-we-work-heading">From First Conversation to Continuous Improvement.</h2>
      <p class="th-lead">A transparent, four-phase delivery methodology connecting requirement discovery to long-term operational success.</p>
    </div>

    <div class="th-process-wrap">

      <!-- DESKTOP REAL-TIME ANIMATED CONNECTORS (Visible on md/lg screens) -->
      <!-- Connector 1: Step 01 -> Step 02 (Top Row Left-to-Right) -->
      <svg class="th-connector-desktop" style="top: 22%; left: 45%; width: 10%; height: 36px; overflow: visible;" viewBox="0 0 100 36" fill="none">
        <path d="M 0 18 L 82 18" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
        <polygon points="80,12 94,18 80,24" fill="#1D6A34"/>
        <circle r="4" fill="#37CE97">
          <animateMotion dur="1.6s" repeatCount="indefinite" path="M 0 18 L 82 18"/>
        </circle>
      </svg>

      <!-- Connector 2: Step 02 down to Step 03 (Curved Zigzag Connector from Step 2 to Step 3) -->
      <svg class="th-connector-desktop" style="top: 46%; left: 18%; width: 64%; height: 75px; overflow: visible;" viewBox="0 0 600 75" fill="none">
        <path d="M 520 0 C 520 38, 80 38, 80 68" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
        <polygon points="74,58 80,72 86,58" fill="#1D6A34"/>
        <circle r="4" fill="#37CE97">
          <animateMotion dur="2.4s" repeatCount="indefinite" path="M 520 0 C 520 38, 80 38, 80 68"/>
        </circle>
      </svg>

      <!-- Connector 3: Step 03 -> Step 04 (Bottom Row Left-to-Right) -->
      <svg class="th-connector-desktop" style="bottom: 22%; left: 45%; width: 10%; height: 36px; overflow: visible;" viewBox="0 0 100 36" fill="none">
        <path d="M 0 18 L 82 18" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
        <polygon points="80,12 94,18 80,24" fill="#1D6A34"/>
        <circle r="4" fill="#37CE97">
          <animateMotion dur="1.6s" repeatCount="indefinite" path="M 0 18 L 82 18"/>
        </circle>
      </svg>

      <!-- PROCESS GRID: 2x2 on Desktop, 1 Column on Mobile -->
      <div class="th-process-grid">

        <!-- STEP 01 -->
        <div class="th-step-card" data-reveal style="--d:0s">
          <div class="flex items-center justify-between gap-3 mb-3">
            <span class="th-step-badge">01</span>
            <span class="w-8 h-8 rounded-xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </span>
          </div>
          <h3 class="th-h3 text-lg font-extrabold text-[#172B22]">Discover &amp; Scope</h3>
          <p class="text-xs sm:text-sm text-[#3d6350] leading-relaxed mt-1.5">
            We analyze your exact hiring mandates or software specifications, aligning on required technical capabilities, budgets and timelines in writing.
          </p>
          <span class="th-step-tag">Agreed Scope Brief</span>
        </div>

        <!-- MOBILE CONNECTOR 1 -> 2 -->
        <div class="th-connector-mobile">
          <svg width="32" height="36" viewBox="0 0 32 36" fill="none">
            <path d="M 16 0 L 16 28" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
            <polygon points="10,22 16,32 22,22" fill="#1D6A34"/>
            <circle r="3.5" fill="#37CE97"><animateMotion dur="1.2s" repeatCount="indefinite" path="M 16 0 L 16 28"/></circle>
          </svg>
        </div>

        <!-- STEP 02 -->
        <div class="th-step-card" data-reveal style="--d:0.1s">
          <div class="flex items-center justify-between gap-3 mb-3">
            <span class="th-step-badge">02</span>
            <span class="w-8 h-8 rounded-xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <h3 class="th-h3 text-lg font-extrabold text-[#172B22]">Source &amp; Architect</h3>
          <p class="text-xs sm:text-sm text-[#3d6350] leading-relaxed mt-1.5">
            For recruitment: multi-channel candidate screening, skill verification and salary benchmarking. For IT: architecture blueprints, database design and sprint roadmaps.
          </p>
          <span class="th-step-tag">Vetted Shortlists &amp; Blueprints</span>
        </div>

        <!-- MOBILE CONNECTOR 2 -> 3 -->
        <div class="th-connector-mobile">
          <svg width="32" height="36" viewBox="0 0 32 36" fill="none">
            <path d="M 16 0 L 16 28" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
            <polygon points="10,22 16,32 22,22" fill="#1D6A34"/>
            <circle r="3.5" fill="#37CE97"><animateMotion dur="1.2s" repeatCount="indefinite" path="M 16 0 L 16 28"/></circle>
          </svg>
        </div>

        <!-- STEP 03 -->
        <div class="th-step-card" data-reveal style="--d:0.2s">
          <div class="flex items-center justify-between gap-3 mb-3">
            <span class="th-step-badge">03</span>
            <span class="w-8 h-8 rounded-xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <h3 class="th-h3 text-lg font-extrabold text-[#172B22]">Execute &amp; Validate</h3>
          <p class="text-xs sm:text-sm text-[#3d6350] leading-relaxed mt-1.5">
            Direct client-candidate interview scheduling with structured feedback, or agile development sprints with continuous integration, unit testing and security checks.
          </p>
          <span class="th-step-tag">Interviews &amp; Sprint Builds</span>
        </div>

        <!-- MOBILE CONNECTOR 3 -> 4 -->
        <div class="th-connector-mobile">
          <svg width="32" height="36" viewBox="0 0 32 36" fill="none">
            <path d="M 16 0 L 16 28" class="th-flow-dash" stroke="#1D6A34" stroke-width="2.5" stroke-linecap="round"/>
            <polygon points="10,22 16,32 22,22" fill="#1D6A34"/>
            <circle r="3.5" fill="#37CE97"><animateMotion dur="1.2s" repeatCount="indefinite" path="M 16 0 L 16 28"/></circle>
          </svg>
        </div>

        <!-- STEP 04 -->
        <div class="th-step-card" data-reveal style="--d:0.3s">
          <div class="flex items-center justify-between gap-3 mb-3">
            <span class="th-step-badge">04</span>
            <span class="w-8 h-8 rounded-xl bg-[#EAF5EF] text-[#1D6A34] flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
          </div>
          <h3 class="th-h3 text-lg font-extrabold text-[#172B22]">Deliver, Onboard &amp; Support</h3>
          <p class="text-xs sm:text-sm text-[#3d6350] leading-relaxed mt-1.5">
            Candidate offer coordination, joining assistance and replacement assurance. For IT: production deployment, training, handover and ongoing technical maintenance.
          </p>
          <span class="th-step-tag">Joining Assurance &amp; Support</span>
        </div>

      </div>
    </div>
  </div>
</section>

<!-- 05 RECRUITMENT SHOWCASE -->
<section class="th-section th-section--white">
  <div class="page-width th-hero__grid" style="align-items:center">
    <div data-reveal>
      <span class="th-eyebrow">Recruitment &amp; Talent Acquisition</span>
      <h2 class="th-h2">The right talent. The right opportunity. The right connection.</h2>
      <p class="th-lead">An eight-step journey from hiring consultation to post-placement follow-up, covering permanent, contract, executive, technical and bulk hiring, campus recruitment and RPO.</p>
      <ul class="th-chips" style="margin:1.25rem 0"><?php foreach (['Permanent', 'Contract', 'IT and Non-IT', 'Executive Search', 'Bulk Hiring', 'RPO'] as $c): ?><li><?= $c ?></li><?php endforeach; ?></ul>
      <div class="th-btn-row"><a class="th-btn th-btn--primary" href="<?= base_url('employers') ?>#hiring-enquiry">Discuss Your Hiring Requirements <?= $arrow ?></a><a class="th-btn th-btn--ghost" href="<?= base_url('services/recruitment') ?>">See the process</a></div>
    </div>
    <div class="th-hero__art" style="background:linear-gradient(140deg,#e8f6ee,#f4fbf7);border-radius:20px;padding:1.5rem;aspect-ratio:auto" data-reveal="scale"><?= view('public/partials/th_art', ['kind' => 'recruitment']) ?></div>
  </div>
</section>

<!-- 06 TECHNOLOGY SHOWCASE -->
<section class="th-section">
  <div class="page-width th-hero__grid" style="align-items:center">
    <div class="th-hero__art" style="background:linear-gradient(140deg,#e8f6ee,#f4fbf7);border-radius:20px;padding:1.5rem;aspect-ratio:auto;order:2" data-reveal="scale"><?= view('public/partials/th_art', ['kind' => 'it']) ?></div>
    <div data-reveal>
      <span class="th-eyebrow">IT Services &amp; Software Development</span>
      <h2 class="th-h2">Turning business requirements into powerful digital solutions.</h2>
      <p class="th-lead">From discovery and architecture to development, testing, go-live and support, we build software around your operational requirements.</p>
      <ul class="th-chips" style="margin:1.25rem 0"><?php foreach (['Custom Software', 'Websites and Web Apps', 'Enterprise Portals', 'Mobile and PWA', 'APIs and Integration', 'ERP and HRMS Workflows'] as $c): ?><li><?= $c ?></li><?php endforeach; ?></ul>
      <div class="th-btn-row"><a class="th-btn th-btn--primary" href="<?= base_url('project-enquiry') ?>">Discuss Your Project <?= $arrow ?></a><a class="th-btn th-btn--ghost" href="<?= base_url('services/it-services') ?>">See the lifecycle</a></div>
    </div>
  </div>
</section>

<!-- 06a WORKSPHERE FLAGSHIP PRODUCT SPOTLIGHT -->
<section class="th-section th-section--dark" id="worksphere-spotlight" style="background:linear-gradient(135deg, #072412 0%, #1A532A 60%, #1B5F52 100%);color:#fff;position:relative;overflow:hidden">
  <div class="page-width th-hero__grid" style="align-items:center">
    <div data-reveal>
      <div style="display:inline-flex;align-items:center;gap:.6rem;background:rgba(55,206,151,0.15);border:1px solid rgba(55,206,151,0.3);border-radius:9999px;padding:.3rem .9rem;margin-bottom:1rem">
        <img src="<?= base_url('assets/img/products/worksphere-mark.png') ?>" alt="" width="20" height="20" style="width:20px;height:20px;border-radius:4px" onerror="this.style.display='none'">
        <span style="font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#37CE97">Flagship Enterprise SaaS</span>
      </div>
      <h2 class="th-h2" style="color:#fff;margin-top:0">WorkSphere &mdash; Enterprise HRMS, Payroll &amp; Workforce Platform</h2>
      <p class="th-lead" style="color:rgba(255,255,255,0.85);margin-bottom:1.25rem">An integrated workforce operating system engineered by TalentHub for growing enterprises &mdash; unifying biometric attendance, Indian statutory payroll, shifts, leave approvals, projects, and talent performance in one secure cloud platform.</p>
      <ul class="th-chips" style="margin:1.25rem 0">
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">Biometric &amp; Shift Rostering</li>
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">1-Click Indian Payroll</li>
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">Statutory (PF, ESIC, PT, TDS)</li>
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">Projects &amp; Timesheets</li>
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">Performance OKRs</li>
        <li style="background:rgba(255,255,255,0.12);color:#fff;border-color:rgba(255,255,255,0.2)">Audit-Grade Security</li>
      </ul>
      <div class="th-btn-row" style="margin-top:1.5rem">
        <a class="th-btn th-btn--light" href="<?= base_url('products/worksphere') ?>">Explore WorkSphere <?= $arrow ?></a>
        <a class="th-btn th-btn--outline-light" href="https://www.worksphere.talenthubconsultant.com" target="_blank" rel="noopener noreferrer">Launch Live Cloud Demo</a>
        <a class="th-btn th-btn--ghost" href="mailto:projects@talenthubconsultant.com" style="color:#37CE97">Technical Enquiries</a>
      </div>
    </div>
    <div class="th-hero__art" style="background:rgba(0,0,0,0.25);border-radius:20px;padding:1rem;aspect-ratio:auto" data-reveal="scale">
      <?= view('public/partials/th_art', ['kind' => 'worksphere']) ?>
    </div>
  </div>
</section>

<!-- 06b SUCCESS STORIES THAT INSPIRE (Hirxpert-style 3-column showcase) -->
<section class="th-section th-section--white th-stories" aria-labelledby="stories-h2">
  <div class="page-width th-stories__grid">

    <!-- Left: heading card overlapping image -->
    <div class="th-stories__left" data-reveal>
      <div class="th-stories__head">
        <span class="th-stories__eyebrow">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V6m0 0l-4 4m4-4l4 4M5 12a7 7 0 0114 0"/></svg>
          Solutions we provide
        </span>
        <h2 class="th-stories__h2" id="stories-h2">Success Stories that Inspire</h2>
      </div>
      <figure class="th-stories__photo">
        <img src="<?= base_url('images/hirxpert-slide-2.jpg') ?>" alt="Hiring team collaborating in a modern office" loading="lazy" width="2500" height="1667">
        <figcaption class="th-stories__stat"><strong>15k+</strong><span>Active Talent Bank</span></figcaption>
      </figure>
    </div>

    <!-- Center: person with concentric rings + donut card -->
    <div class="th-stories__center" data-reveal="scale" style="--d:.1s">
      <div class="th-stories__person">
        <span class="th-stories__ring th-stories__ring--1" aria-hidden="true"></span>
        <span class="th-stories__ring th-stories__ring--2" aria-hidden="true"></span>
        <span class="th-stories__ring th-stories__ring--3" aria-hidden="true"></span>
        <span class="th-stories__dot th-stories__dot--a" aria-hidden="true"></span>
        <span class="th-stories__dot th-stories__dot--b" aria-hidden="true"></span>
        <span class="th-stories__dot th-stories__dot--c" aria-hidden="true"></span>
        <img src="<?= base_url('images/hero-professional.jpg') ?>" alt="Smiling TalentHub client professional reviewing a tablet" loading="lazy" width="1440" height="960">
      </div>
      <div class="th-stories__donut-card">
        <svg class="th-donut" viewBox="0 0 120 120" role="img" aria-label="Share of engagements by focus area">
          <circle cx="60" cy="60" r="44" fill="none" stroke="#e3ebe7" stroke-width="22"/>
          <circle class="th-donut__seg th-donut__seg--1" cx="60" cy="60" r="44" fill="none" stroke="#0e5448" stroke-width="22" pathLength="100" style="--len:46;--off:0" stroke-dasharray="46 54" stroke-dashoffset="0" transform="rotate(-90 60 60)"/>
          <circle class="th-donut__seg th-donut__seg--2" cx="60" cy="60" r="44" fill="none" stroke="#18857a" stroke-width="22" pathLength="100" style="--len:31;--off:-46" stroke-dasharray="31 69" stroke-dashoffset="-46" transform="rotate(-90 60 60)"/>
          <circle class="th-donut__seg th-donut__seg--3" cx="60" cy="60" r="44" fill="none" stroke="#f7c948" stroke-width="22" pathLength="100" style="--len:23;--off:-77" stroke-dasharray="23 77" stroke-dashoffset="-77" transform="rotate(-90 60 60)"/>
        </svg>
        <ul class="th-donut__legend">
          <li><i style="background:#0e5448"></i>Talent Acquisition</li>
          <li><i style="background:#18857a"></i>Software Delivery</li>
          <li><i style="background:#f7c948"></i>Business Growth</li>
        </ul>
      </div>
    </div>

    <!-- Right: checklist + appointment card -->
    <div class="th-stories__right" data-reveal style="--d:.2s">
      <div class="th-stories__point">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 13l4 4L14 7M10 15l2 2L22 7"/></svg>Employer Branding</h3>
        <p>A strong employer brand helps attract top talent and improves employee retention.</p>
      </div>
      <div class="th-stories__point">
        <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 13l4 4L14 7M10 15l2 2L22 7"/></svg>Industry Knowledge</h3>
        <p>Hands-on experience in workforce management, organisational development and compliance.</p>
      </div>
      <div class="th-stories__appt">
        <h3>Make Appointment</h3>
        <div class="th-stories__rating" aria-label="Rated 5 out of 5 by clients">
          <span class="th-stories__avatars"><i>T</i><i>H</i></span>
          <span class="th-stories__stars" aria-hidden="true">★★★★★</span>
        </div>
        <a class="th-stories__btn" href="<?= base_url('contact') ?>">Contact Us
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
        </a>
      </div>
    </div>

  </div>
</section>


<!-- ── SMART ENGAGEMENT: INLINE ANNOUNCEMENT CONTAINER ──────────────────────── -->
<div id="talenthub-inline-announcement" class="page-width empty:hidden"></div>

<!-- ── 8. FEATURED OPPORTUNITIES (DYNAMIC FROM MYSQL) ────────────────────────── -->
<section class="th-section bg-[#FAF9F5] border-b border-[rgba(29,106,52,0.12)]">
  <div class="page-width">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-4">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Current Mandates</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold text-[#172B22] mt-1">Featured Opportunities</h2>
        <p class="text-sm text-[#3d6350] mt-1">Active, verified vacancies across India.</p>
      </div>
      <a href="<?= base_url('jobs') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1D6A34] hover:underline">
        <span>View All Active Roles</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php if (!empty($featuredJobs)): ?>
        <?php foreach ($featuredJobs as $job): ?>
          <div class="card-premium p-7 bg-white rounded-3xl border border-[rgba(29,106,52,0.14)] shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between gap-2 mb-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#EAF5EF] text-[#1D6A34] border border-[#1D6A34]/20">
                  <?= esc($job['category_name'] ?? 'Professional') ?>
                </span>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                  <?= esc($job['job_type'] === 'FT' ? 'Full-Time' : ($job['job_type'] === 'CT' ? 'Contract' : ($job['job_type'] === 'PT' ? 'Part-Time' : 'Internship'))) ?>
                </span>
              </div>

              <h3 class="text-lg font-bold text-[#172B22] mb-2 hover:text-[#1D6A34] transition-colors leading-snug">
                <a href="<?= base_url('jobs/' . $job['slug']) ?>"><?= esc($job['title']) ?></a>
              </h3>

              <p class="text-xs text-[#3d6350] flex flex-wrap items-center gap-2 mb-4 font-medium">
                <span class="inline-flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 text-[#1D6A34]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <?= esc($job['location'] ?? 'India') ?>
                </span>
                <span class="text-slate-300">•</span>
                <span>Exp: <?= esc($job['experience_label'] ?? ($job['experience_min'] . '–' . $job['experience_max'] . ' yrs')) ?></span>
                <span class="text-slate-300">•</span>
                <span>Mode: <?= esc($job['work_mode'] ?? 'ONSITE') ?></span>
              </p>

              <p class="text-xs text-[#3d6350] line-clamp-2 leading-relaxed mb-4">
                <?= esc(substr(strip_tags($job['description']), 0, 150)) ?>...
              </p>
            </div>

            <div class="pt-4 border-t border-[rgba(29,106,52,0.1)] flex items-center justify-between">
              <?php if (!empty($job['is_salary_public']) && !empty($job['salary_min'])): ?>
                <span class="text-xs font-bold text-[#1D6A34]">₹<?= number_format((float) $job['salary_min']) ?> - ₹<?= number_format((float) $job['salary_max']) ?></span>
              <?php else: ?>
                <span class="text-xs font-semibold text-[#3d6350]">Salary Competitive</span>
              <?php endif; ?>

              <a href="<?= base_url('jobs/' . $job['slug']) ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#1D6A34] hover:underline">
                <span>View Details &amp; Apply</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-span-2 text-center p-12 bg-white rounded-3xl border border-dashed border-[rgba(29,106,52,0.2)]">
          <p class="text-sm text-[#3d6350] mb-3">No active public vacancies match currently.</p>
          <a href="<?= base_url('career-enquiry') ?>" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-[#1D6A34] text-white text-xs font-bold shadow transition">Submit Resume for Upcoming Roles</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── 9. DUAL CONVERSION CTAS (EMPLOYER & CANDIDATE) ────────────────────────── -->
<section class="th-section th-section--white border-b border-[rgba(29,106,52,0.12)]">
  <div class="page-width">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

      <!-- Employer Lead Gen CTA Card -->
      <div class="p-8 sm:p-10 rounded-3xl bg-[linear-gradient(135deg,#072412,#1D6A34)] text-white shadow-xl relative overflow-hidden flex flex-col justify-between">
        <div class="relative z-10 space-y-3">
          <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/10 text-[#37CE97] border border-white/10 inline-block">For Corporate Clients</span>
          <h3 class="text-2xl sm:text-3xl font-extrabold leading-tight text-white">Need to Close Critical Mandates Fast?</h3>
          <p class="text-xs sm:text-sm text-white/80 leading-relaxed max-w-md">
            Partner with TalentHub for pre-vetted candidate shortlists, with fees and replacement terms agreed in writing before work begins.
          </p>
        </div>
        <div class="relative z-10 pt-6">
          <a href="<?= base_url('employers') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#37CE97] hover:bg-emerald-300 text-[#072412] text-xs font-extrabold shadow-lg shadow-[#37CE97]/25 transition">
            <span>Submit Corporate Hiring Requirement</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </a>
        </div>
        <div class="absolute -right-16 -bottom-16 w-60 h-60 bg-[#37CE97]/20 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- Candidate Registration CTA Card -->
      <div class="p-8 sm:p-10 rounded-3xl bg-white border border-slate-200/90 text-slate-900 shadow-xl relative overflow-hidden flex flex-col justify-between">
        <div class="relative z-10 space-y-3">
          <span class="px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200 inline-block">For Job Seekers</span>
          <h3 class="text-2xl sm:text-3xl font-extrabold leading-tight text-slate-900">Looking for Your Next Career Move?</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-md">
            Submit your resume to our confidential talent repository. No fees for candidates, salary guidance and direct interview scheduling.
          </p>
        </div>
        <div class="relative z-10 pt-6">
          <a href="<?= base_url('career-enquiry') ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white text-xs font-extrabold shadow-lg shadow-[#1D6A34]/20 transition">
            <span>Drop Your Resume in Talent Bank</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </a>
        </div>
        <div class="absolute -right-16 -bottom-16 w-60 h-60 bg-emerald-50 rounded-full blur-3xl pointer-events-none"></div>
      </div>

    </div>
  </div>
</section>

<!-- ── 10. TESTIMONIALS (VERIFIED PUNE PLACEMENTS & REVIEWS) ─────────── -->
<?= view('public/partials/testimonials_section', ['testimonials' => $testimonials ?? []]) ?>

<!-- ── 11. INSIGHTS & ARTICLES (CONDITIONAL RENDERING FROM MYSQL) ───────────── -->
<?php if (!empty($recentBlogs)): ?>
<section class="th-section th-section--white border-b border-[rgba(29,106,52,0.12)]">
  <div class="page-width">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Recruitment Intelligence</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold text-[#172B22] mt-1">Latest Industry Insights</h2>
        <p class="text-sm text-[#3d6350] mt-1">Salary benchmarks, hiring market trends, and career guidance for professionals across India.</p>
      </div>
      <a href="<?= base_url('insights') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1D6A34] hover:underline">
        <span>View All Articles</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($recentBlogs as $post): ?>
        <div class="p-7 rounded-3xl bg-[#FAF9F5] border border-[rgba(29,106,52,0.12)] shadow-sm hover:shadow-md hover:-translate-y-1 transition-all flex flex-col justify-between">
          <div>
            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#EAF5EF] text-[#1D6A34] border border-[#1D6A34]/20 mb-3">
              <?= esc($post['category_name'] ?? 'Industry Trends') ?>
            </span>
            <h3 class="text-base font-bold text-[#172B22] mb-2 hover:text-[#1D6A34] transition-colors leading-snug">
              <a href="<?= base_url('insights/' . $post['slug']) ?>"><?= esc($post['title']) ?></a>
            </h3>
            <p class="text-xs text-[#3d6350] line-clamp-2 leading-relaxed mb-4">
              <?= esc(substr(strip_tags($post['summary'] ?? $post['content'] ?? ''), 0, 130)) ?>...
            </p>
          </div>
          <div class="pt-4 border-t border-[rgba(29,106,52,0.1)] flex items-center justify-between text-xs">
            <span class="text-[11px] text-[#3d6350] font-semibold"><?= date('M d, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
            <a href="<?= base_url('insights/' . $post['slug']) ?>" class="font-bold text-[#1D6A34] hover:underline inline-flex items-center gap-1">
              <span>Read Article</span>
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 09 FAQ (admin-managed $faqs when available, otherwise defaults) -->
<?php
$homeFaqs = !empty($faqs) ? array_map(fn ($f) => [$f['question'], $f['answer']], array_slice($faqs, 0, 6)) : [
  ['How do recruitment engagements work?', 'We confirm your requirement, define the candidate profile, source and screen candidates, coordinate interviews and support selection through to joining, as agreed.'],
  ['Do you support contract staffing?', 'Yes. Contract staffing is one of our recruitment capabilities alongside permanent, executive and bulk hiring.'],
  ['What software projects do you take on?', 'Custom software, websites, web applications, enterprise portals, mobile apps, APIs and ERP/HRMS workflows.'],
  ['How long does a project take?', 'It depends on scope. We agree a roadmap and timeline after requirement analysis, and do not promise fixed dates in advance.'],
  ['Do you offer support after go-live?', 'Yes, through optional maintenance and support engagements agreed separately.'],
  ['How do I start?', 'Submit an enquiry. A TalentHub consultant will contact you to discuss your requirement and next steps.'],
];
?>
<section class="th-section">
  <div class="page-width">
    <div class="th-head th-head--center" data-reveal><span class="th-eyebrow">Got questions?</span><h2 class="th-h2">Frequently asked questions</h2></div>
    <div class="th-faq">
      <?php foreach ($homeFaqs as $f): ?>
        <details data-reveal><summary><?= esc($f[0]) ?><svg class="th-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></summary><div class="th-faq__a"><?= esc($f[1]) ?></div></details>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:1.5rem"><a class="th-link" href="<?= base_url('faq') ?>">View all FAQs</a></p>
  </div>
</section>

<!-- ── 13. DIRECT INTERACTIVE CONTACT SECTION ───────────────────────────────── -->
<section class="th-section th-section--white border-b border-[rgba(29,106,52,0.12)]">
  <div class="page-width">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

      <!-- Left Contact Info (5 Cols) -->
      <div class="lg:col-span-5 space-y-6">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-[#1D6A34]">Immediate Assistance</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-[#172B22] mt-1">Connect With Talent Hub</h2>
          <p class="text-xs sm:text-sm text-[#3d6350] mt-2 leading-relaxed">
            Our team, based in Pune, Maharashtra, supports employers and candidates across India. Reach out and we will respond as soon as possible.
          </p>
        </div>

        <div class="p-6 rounded-3xl bg-[#FAF9F5] border border-[rgba(29,106,52,0.15)] space-y-4 text-xs text-[#3d6350]">
          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
              <p class="font-bold text-[#172B22]">Headquarters Address</p>
              <p class="mt-0.5 leading-relaxed"><?= esc($settings['office_address'] ?? 'Katraj, Pune — 411046, Maharashtra, India') ?></p>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div>
              <p class="font-bold text-[#172B22]">Phone Consultation</p>
              <p class="mt-0.5"><a href="tel:<?= preg_replace('/[^0-9+]/', '', $settings['contact_phone'] ?? '+917276146573') ?>" class="text-[#1D6A34] font-bold hover:underline"><?= esc($settings['contact_phone'] ?? '+91 72761 46573') ?></a></p>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <p class="font-bold text-[#172B22]">Customer & Corporate Enquiries</p>
              <p class="mt-0.5"><a href="mailto:<?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold hover:underline"><?= esc($settings['contact_email'] ?? 'hr@talenthubconsultant.com') ?></a></p>
            </div>
          </div>

          <div class="flex items-start gap-3.5">
            <div class="p-2.5 rounded-xl bg-[#EAF5EF] text-[#1D6A34] shrink-0 mt-0.5 border border-[#1D6A34]/15">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <div>
              <p class="font-bold text-[#172B22]">Candidate Careers & Resumes</p>
              <p class="mt-0.5"><a href="mailto:<?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?>" class="text-[#1D6A34] font-bold hover:underline"><?= esc($settings['hiring_email'] ?? 'hiring@talenthubconsultant.com') ?></a></p>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $settings['whatsapp_number'] ?? '917276146573') ?>"
             target="_blank" rel="noopener noreferrer"
             class="inline-flex items-center justify-center gap-2 w-full px-6 py-3.5 rounded-xl border border-[rgba(29,106,52,0.3)] bg-white hover:bg-[#EAF5EF] text-xs font-bold text-[#172B22] shadow-sm transition">
            <svg class="w-4 h-4 text-[#1D6A34]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
            <span>Direct WhatsApp Inquiry: <?= esc($settings['whatsapp_number'] ?? '+91 72761 46573') ?></span>
          </a>

          <?php if (!empty($settings['whatsapp_channel_url'])): ?>
          <a href="<?= esc($settings['whatsapp_channel_url']) ?>"
             target="_blank" rel="noopener noreferrer"
             class="btn-whatsapp-channel inline-flex items-center justify-center gap-2 w-full px-6 py-3.5 rounded-xl text-xs font-bold shadow-md transition"
             style="background-color: #25D366 !important; color: #FFFFFF !important; border: 1.5px solid #20BA59 !important;">
            <svg class="w-4 h-4" style="color: #FFFFFF !important; fill: currentColor !important;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.82.49 3.53 1.34 5L2 22l5.18-1.32c1.42.79 3.06 1.24 4.82 1.24 5.52 0 10-4.48 10-10S17.52 2 12 2zm.02 18c-1.57 0-3.04-.44-4.3-1.21l-.31-.19-3.19.82.85-3.08-.2-.33A7.95 7.95 0 014 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-7.98 8z"/></svg>
            <span style="color: #FFFFFF !important; font-weight: 700 !important;">Join Our WhatsApp Channel – Get Latest Job Updates</span>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right Interactive Form (7 Cols) -->
      <div class="lg:col-span-7">
        <div class="p-8 sm:p-10 rounded-3xl bg-white border border-[rgba(29,106,52,0.18)] shadow-xl shadow-[#1D6A34]/5 space-y-6">
          <div>
            <h3 class="text-xl font-bold text-[#172B22]">Send an Online Enquiry</h3>
            <p class="text-xs text-[#3d6350] mt-1">Submit your mandate or candidate inquiry directly to our recruiters.</p>
          </div>

          <form action="<?= base_url('contact/submit') ?>" method="POST" data-ajax="true" class="space-y-4">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">First Name *</label>
                <input type="text" name="first_name" required placeholder="e.g. Ramesh" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Last Name</label>
                <input type="text" name="last_name" placeholder="e.g. Kulkarni" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Email Address *</label>
                <input type="email" name="email" required placeholder="e.g. ramesh@company.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
              </div>
              <div>
                <label class="block text-xs font-bold text-[#172B22] mb-1">Phone / WhatsApp *</label>
                <input type="tel" name="phone" required placeholder="e.g. +91 98230 00000" pattern="^(\+?[0-9\s\-\(\)]{8,20})$" title="Please enter a valid 10-digit Indian (+91) or international phone number" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
                <span class="text-[10px] text-[#3d6350] mt-0.5 block">Accepts Indian (+91) and international numbers</span>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1">Enquiry Subject</label>
              <select name="subject" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
                <option value="Corporate Staffing Mandate">Corporate Staffing Mandate</option>
                <option value="Executive Leadership Search">Executive Leadership Search</option>
                <option value="Candidate Career Enquiry">Candidate Career Enquiry</option>
                <option value="General Partnership">General Partnership</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-[#172B22] mb-1">Your Message or Hiring Requirement <span class="text-xs text-slate-400 font-normal">(Optional)</span></label>
              <textarea name="message" rows="4" placeholder="Briefly describe your requirements or inquiry (optional)..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary"></textarea>
            </div>

            <!-- Anti-Spam, Honeypot & CAPTCHA Protection -->
            <?= $formProtection ?? '' ?>

            <div class="flex items-start gap-2 pt-1">
              <input type="checkbox" name="consent" id="home-consent" value="1" required class="mt-0.5 rounded text-[#1D6A34] focus:ring-[#37CE97]">
              <label for="home-consent" class="text-[11px] text-[#3d6350] leading-snug cursor-pointer">
                I affirmatively consent to TalentHub Consultant contacting me regarding this inquiry under the <a href="<?= base_url('privacy') ?>" target="_blank" class="text-[#1D6A34] font-bold underline">Privacy Policy (v1.0-DPDP)</a>.
              </label>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-[#1D6A34] hover:bg-[#155227] text-white text-xs font-bold shadow-md shadow-[#1D6A34]/25 transition">
              <span>Submit Enquiry to Recruitment Desk</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- 10 FINAL CTA -->
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
