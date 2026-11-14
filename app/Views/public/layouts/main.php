<?php
/**
 * TalentHub Consultant — Master Public Layout View
 * Production-Hardened Server-Rendered Architecture
 */

$siteName = esc($settings['site_name'] ?? 'TalentHub Consultant');
$siteTagline = esc($settings['site_tagline'] ?? "Talent. Technology. Transformation.");
$siteDesc = esc($meta_description ?? ($settings['site_description'] ?? "TalentHub is a Pan-India recruitment and technology consulting partner helping organisations hire the right talent, build digital solutions and modernise business operations."));

// Build 100% clean canonical URL (strict prevention of index.php or .php in meta/canonical tags)
$rawCurrentUrl = current_url();
$cleanCanonical = preg_replace('#/index\.php(/|$)#i', '$1', $rawCurrentUrl);
$cleanCanonical = preg_replace('/\.php$/i', '', $cleanCanonical);
$cleanCanonical = preg_replace('#(?<!:)//+#', '/', $cleanCanonical);
$rootBaseUrl = rtrim(base_url(), '/');
$canonicalUrl = esc((rtrim($cleanCanonical, '/') === $rootBaseUrl) ? $rootBaseUrl . '/' : rtrim($cleanCanonical, '/'));

$logoLight = base_url(!empty($settings['logo_light']) ? $settings['logo_light'] : 'logo.png');
$logoDark = base_url(!empty($settings['logo_dark']) ? $settings['logo_dark'] : 'logo-white.png');
$faviconUrl = base_url('favicon.png');
$currentUri = $currentUri ?? uri_string();

// GA4 measurement id
$gaId = $settings['ga_measurement_id'] ?? 'G-K6J0RWMLBS';
$gaEnabled = !empty($gaId) && ($settings['ga_enabled'] ?? '1') !== '0';

// Tawk.to widget
$tawkPropertyId = !empty($settings['tawk_property_id']) ? $settings['tawk_property_id'] : '6ad65a04c40a0134c95ce3b6';
$tawkWidgetId = !empty($settings['tawk_widget_id']) ? $settings['tawk_widget_id'] : '1k5akv9ak';
$tawkEnabled = ($settings['tawk_enabled'] ?? '1') !== '0';

$isStaging = \App\Services\EnvironmentSafetyService::isStaging();
if ($isStaging) {
    $gaEnabled = false; // Strictly suppress GA4 in Staging
    $manifestFile = \App\Services\EnvironmentSafetyService::getManifestPath();
    $swFile = \App\Services\EnvironmentSafetyService::getServiceWorkerPath();
} else {
    $manifestFile = 'manifest.json';
    $swFile = 'sw.js';
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <!-- SEO Primary Metadata -->
  <title><?= esc($meta_title ?? "{$siteName} — {$siteTagline}") ?></title>
  <meta name="description" content="<?= $siteDesc ?>">
  <meta name="keywords" content="<?= esc($meta_keywords ?? 'recruitment consultancy India, pan India recruitment services, IT recruitment, IT staffing, technology consulting, digital transformation, software development') ?>">
  <meta name="author" content="TalentHub Consultant">
  <?php if ($isStaging): ?>
  <!-- Staging Multi-Directive Indexing Shield -->
  <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
  <?php else: ?>
  <?php $utilityPage = (bool) preg_match('#^(newsletter|application|privacy/request-cv|submission-failed|thank-you|interviews|api)#', trim((string) ($currentUri ?? ''), '/')); ?>
  <?php if (!empty($noindex)): /* e.g. draft preview or a product flagged no-index */ ?>
  <meta name="robots" content="noindex, nofollow">
  <?php else: ?>
  <meta name="robots" content="<?= $utilityPage ? 'noindex, follow' : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1' ?>">
  <?php endif; ?>
  <link rel="canonical" href="<?= !empty($canonical_url) ? esc($canonical_url) : $canonicalUrl ?>">
  <?php endif; ?>

  <!-- Open Graph / Facebook / WhatsApp / LinkedIn / Slack -->
  <?php
  $hasCustomOgImage = !empty($og_image);
  $shareImage = $hasCustomOgImage ? esc($og_image) : $faviconUrl;
  ?>
  <meta property="og:type" content="<?= esc($og_type ?? 'website') ?>">
  <meta property="og:url" content="<?= $canonicalUrl ?>">
  <meta property="og:site_name" content="<?= $siteName ?>">
  <meta property="og:title" content="<?= esc($meta_title ?? "{$siteName} — {$siteTagline}") ?>">
  <meta property="og:description" content="<?= $siteDesc ?>">
  <meta property="og:image" content="<?= $shareImage ?>">
  <meta property="og:image:secure_url" content="<?= $shareImage ?>">
  <?php if (!$hasCustomOgImage): ?>
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="192">
  <meta property="og:image:height" content="192">
  <meta property="og:image:alt" content="<?= $siteName ?>">
  <?php endif; ?>
  <link rel="image_src" href="<?= $shareImage ?>">

  <!-- Twitter / X -->
  <meta name="twitter:card" content="<?= $hasCustomOgImage ? 'summary_large_image' : 'summary' ?>">
  <meta name="twitter:title" content="<?= esc($meta_title ?? "{$siteName} — {$siteTagline}") ?>">
  <meta name="twitter:description" content="<?= $siteDesc ?>">
  <meta name="twitter:image" content="<?= $shareImage ?>">
  <meta name="twitter:image:alt" content="<?= $siteName ?>">

  <!-- Favicons for Google Search, Mobile Browsers & PWA (Strict Multiples of 48px Square) -->
  <link rel="icon" type="image/png" sizes="48x48" href="<?= base_url('favicon-48x48.png') ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= base_url('favicon-96x96.png') ?>">
  <link rel="icon" type="image/png" sizes="144x144" href="<?= base_url('favicon-144x144.png') ?>">
  <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('favicon-192x192.png') ?>">
  <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="shortcut icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png') ?>">
  <link rel="manifest" href="<?= base_url($manifestFile) ?>">
  <meta name="theme-color" content="#1D6A34">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="TalentHub">

  <!-- Fonts: self-hosted variable WOFF2 (Latin subset), declared in tokens.css; the two used on first paint are preloaded -->
  <link rel="preload" href="<?= base_url('assets/fonts/inter-latin-var.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="<?= base_url('assets/fonts/plus-jakarta-sans-latin-var.woff2') ?>" as="font" type="font/woff2" crossorigin>

  <!-- Design tokens, compiled Tailwind utilities, legacy layers, then the authoritative theme + motion layers -->
  <?php $asset = static fn (string $p): string => base_url($p) . '?v=' . (is_file(FCPATH . $p) ? filemtime(FCPATH . $p) : 1); ?>
  <link rel="stylesheet" href="<?= $asset('assets/css/tokens.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/custom.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/redesign.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/polish.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/motion.css') ?>">
  <link rel="stylesheet" href="<?= $asset('assets/css/theme.css') ?>">

  <!-- Google Analytics 4 (Dynamic & Consent-Compliant) -->
  <?php if ($gaEnabled): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($gaId) ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= esc($gaId) ?>', { 'anonymize_ip': true });
  </script>
  <?php endif; ?>

  <!-- Comprehensive Schema.org Multi-Type Graph (Completely Dynamic & Agent-Ready) -->
  <?php
  // Build dynamic social URLs for Schema.org sameAs and website links
  $socialUrlsList = [];
  if (!empty($settings['linkedin_url'])) {
      $socialUrlsList[] = $settings['linkedin_url'];
  }
  if (!empty($settings['instagram_url'])) {
      $socialUrlsList[] = $settings['instagram_url'];
  }
  if (!empty($settings['whatsapp_channel_url'])) {
      $socialUrlsList[] = $settings['whatsapp_channel_url'];
  }
  if (!empty($settings['facebook_url'])) {
      $socialUrlsList[] = $settings['facebook_url'];
  }
  if (!empty($settings['twitter_url'])) {
      $socialUrlsList[] = $settings['twitter_url'];
  }
  if (!empty($settings['youtube_url'])) {
      $socialUrlsList[] = $settings['youtube_url'];
  }
  $socialUrlsList[] = base_url();
  $sameAsList = array_values(array_unique(array_filter($socialUrlsList)));

  $dynamicSchemaGraph = [
      '@context' => 'https://schema.org',
      '@graph'   => [
          [
              '@type'              => ['EmploymentAgency', 'LocalBusiness', 'Organization'],
              '@id'                => base_url() . '#organization',
              'name'               => $siteName,
              'legalName'          => $siteName,
              'areaServed'         => ['@type' => 'Country', 'name' => 'India'],
              'url'                => base_url(),
              'logo'               => [
                  '@type' => 'ImageObject',
                  'url'   => $logoLight,
              ],
              'image'              => $logoLight,
              'description'        => $siteDesc,
              'telephone'          => $settings['contact_phone'] ?? '+91 72761 46573',
              'email'              => $settings['contact_email'] ?? 'hr@talenthubconsultant.com',
              'priceRange'         => '₹₹',
              'currenciesAccepted' => 'INR',
              'paymentAccepted'    => 'Bank Transfer, Online',
              'openingHours'       => 'Mo-Sa 09:30-19:00',
              'address'            => [
                  '@type'           => 'PostalAddress',
                  'streetAddress'   => $settings['office_address'] ?? 'Office No. 4, Katraj-Kondhwa Road, Katraj',
                  'addressLocality' => 'Pune',
                  'addressRegion'   => 'Maharashtra',
                  'postalCode'      => '411046',
                  'addressCountry'  => 'IN',
              ],
              'geo'                => [
                  '@type'     => 'GeoCoordinates',
                  'latitude'  => '18.4529',
                  'longitude' => '73.8553',
              ],
              'sameAs'             => $sameAsList,
              'contactPoint'       => [
                  [
                      '@type'             => 'ContactPoint',
                      'telephone'         => $settings['contact_phone'] ?? '+91 72761 46573',
                      'contactType'       => 'customer service and corporate recruitment',
                      'email'             => $settings['contact_email'] ?? 'hr@talenthubconsultant.com',
                      'areaServed'        => 'IN',
                      'availableLanguage' => ['English', 'Hindi', 'Marathi'],
                  ],
                  [
                      '@type'             => 'ContactPoint',
                      'telephone'         => $settings['contact_phone'] ?? '+91 72761 46573',
                      'contactType'       => 'candidate careers and job applications',
                      'email'             => $settings['hiring_email'] ?? 'hiring@talenthubconsultant.com',
                      'areaServed'        => 'IN',
                      'availableLanguage' => ['English', 'Hindi', 'Marathi'],
                  ],
              ],
          ],
          [
              '@type'           => 'WebSite',
              '@id'             => base_url() . '#website',
              'url'             => base_url(),
              'name'            => $siteName,
              'description'     => $siteDesc,
              'publisher'       => [
                  '@id' => base_url() . '#organization',
              ],
              'potentialAction' => [
                  '@type'       => 'SearchAction',
                  'target'      => [
                      '@type'       => 'EntryPoint',
                      'urlTemplate' => base_url('jobs') . '?search={search_term_string}',
                  ],
                  'query-input' => 'required name=search_term_string',
              ],
          ],
      ],
  ];
  ?>
  <?php
  $segs = array_values(array_filter(explode('/', trim((string) ($currentUri ?? ''), '/'))));
  if ($segs && $segs[0] !== 'services'):
      $bc = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => base_url()]];
      $path = '';
      foreach ($segs as $i => $sg) { $path .= '/' . $sg; $bc[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => ucwords(str_replace('-', ' ', $sg)), 'item' => base_url(ltrim($path, '/'))]; }
  ?>
  <script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $bc], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  <?php endif; ?>
  <?php foreach ((array) ($json_ld ?? []) as $ldBlock): /* page-specific structured data (e.g. product pages) */ ?>
  <script type="application/ld+json"><?= json_encode($ldBlock, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
  <?php endforeach; ?>
  <script type="application/ld+json">
  <?= json_encode($dynamicSchemaGraph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
  </script>
</head>
<body class="font-sans antialiased min-h-screen flex flex-col">

  <?php if ($isStaging): ?>
  <!-- TalentHub Staging Environment Alert Banner -->
  <aside class="sticky top-0 z-[9999] bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 text-slate-950 px-4 py-1.5 text-xs font-bold tracking-wide border-b border-amber-600 shadow-sm flex items-center justify-between">
    <div class="flex items-center gap-2 mx-auto">
      <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
      <span>STAGING TESTING ENVIRONMENT</span>
      <span class="hidden sm:inline font-semibold text-slate-900">&bull; Simulated Test Records &amp; Sandboxed Emails &bull; QA &amp; UAT Portal</span>
    </div>
  </aside>
  <?php endif; ?>

  <?php
  $isHome = ($currentUri === '' || $currentUri === '/' || $currentUri === 'home');
  $navLinks = [
      ['name' => 'About Us', 'url' => base_url('about'), 'slug' => 'about'],
      ['name' => 'Services', 'url' => base_url('services'), 'slug' => 'services', 'children' => [
          ['Recruitment & Talent Acquisition', base_url('services/recruitment'), 'Permanent, contract and specialist hiring'],
          ['IT Services & Software Development', base_url('services/it-services'), 'Custom software, web, mobile and APIs'],
      ]],
      ['name' => 'Products', 'url' => base_url('products'), 'slug' => 'products'],
      // Portfolio link appears only once at least one case study is published (nothing is shown for an empty portfolio).
      ...(\App\Services\CaseStudyService::hasPublished() ? [['name' => 'Case Studies', 'url' => base_url('case-studies'), 'slug' => 'case-studies']] : []),
      ['name' => 'Jobs', 'url' => base_url('jobs'), 'slug' => 'jobs'],
      ['name' => 'Employers', 'url' => base_url('employers'), 'slug' => 'employers'],
      ['name' => 'Candidates', 'url' => base_url('candidates'), 'slug' => 'candidates'],
      ['name' => 'Insights', 'url' => base_url('insights'), 'slug' => 'insights'],
      ['name' => 'Contact Us', 'url' => base_url('contact'), 'slug' => 'contact'],
  ];
  ?>

  <a class="skip-link" href="#main">Skip to main content</a>

  <!-- ── HEADER ─────────────────────────────────────────────────────────────── -->
  <header class="site-header" data-site-header id="navbar-header">
    <div class="wrap">
      <div class="site-header__bar">
        <a href="<?= base_url() ?>" class="brand" aria-label="<?= $siteName ?> — home">
          <img src="<?= $logoLight ?>" alt="<?= $siteName ?>" width="160" height="40" decoding="async" fetchpriority="high">
        </a>

        <nav class="nav" aria-label="Main">
          <?php foreach ($navLinks as $item):
              $isActive = $item['slug'] !== '' && str_starts_with($currentUri, $item['slug']); ?>
            <?php if (!empty($item['children'])): ?>
              <div class="nav-dd">
                <button type="button" class="nav-link nav-dd__btn" aria-expanded="false" aria-haspopup="true" aria-controls="nav-dd-services"<?= $isActive ? ' aria-current="page"' : '' ?>>
                  <span><?= $item['name'] ?></span>
                  <svg class="i-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="nav-dd__menu" id="nav-dd-services">
                  <a href="<?= $item['url'] ?>">All Services<small>Recruitment and technology, side by side</small></a>
                  <?php foreach ($item['children'] as $ch): ?>
                    <a href="<?= $ch[1] ?>"><?= $ch[0] ?><small><?= $ch[2] ?></small></a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php else: ?>
              <a href="<?= $item['url'] ?>" class="nav-link"<?= $isActive ? ' aria-current="page"' : '' ?>><?= $item['name'] ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </nav>

        <div class="header-actions">
          <a href="<?= base_url('project-enquiry') ?>" class="btn btn--primary btn--sm icon-shift"><span class="whitespace-nowrap">Hire &amp; Build</span><svg class="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg></a>
        </div>

        <button type="button" class="menu-btn" data-drawer-toggle aria-expanded="false" aria-controls="site-drawer" aria-label="Open menu">
          <svg class="i-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
          <svg class="i-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>
    </div>
  </header>

  <div class="drawer-scrim" data-drawer-scrim aria-hidden="true"></div>
  <aside class="site-drawer" id="site-drawer" aria-label="Site menu">
    <div class="site-drawer__head">
      <a href="<?= base_url() ?>" class="brand" aria-label="<?= $siteName ?> — home"><img src="<?= $logoLight ?>" alt="" width="140" height="35" decoding="async"></a>
      <button type="button" class="menu-btn" data-drawer-close aria-label="Close menu" style="display:inline-grid"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <nav aria-label="Mobile">
      <?php foreach ($navLinks as $item):
          $isActive = $item['slug'] !== '' && str_starts_with($currentUri, $item['slug']); ?>
        <a href="<?= $item['url'] ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><span><?= $item['name'] ?></span></a>
        <?php if (!empty($item['children'])): ?>
          <div class="sub">
            <?php foreach ($item['children'] as $ch): ?><a href="<?= $ch[1] ?>"><?= $ch[0] ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="site-drawer__foot">
      <a href="<?= base_url('project-enquiry') ?>" class="btn btn--primary">Hire &amp; Build</a>
      <a href="<?= base_url('employers') ?>" class="btn btn--ghost">Hire Talent with TalentHub</a>
    </div>
  </aside>

  <!-- ── MAIN PAGE CONTENT ─────────────────────────────────────────────────────── -->
  <main id="main" class="flex-grow" tabindex="-1">
    <?php
    if (isset($viewContent)) {
        echo $viewContent;
        unset($viewContent);
    } else {
        echo $this->renderSection('content') ?? '';
    }
    ?>
  </main>

  <!-- ── FOOTER ───────────────────────────────────────────────────────────────── -->
  <?php
  $socialPaths = [
      'wa' => 'M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z',
      'li' => 'M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76c-.97 0-1.75-.79-1.75-1.76s.78-1.75 1.75-1.75 1.75.78 1.75 1.75-.78 1.76-1.75 1.76m1.39 9.74v-8.37H5.07v8.37h2.78z',
      'ig' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
      'fb' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
      'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
      'yt' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
  ];
  $socials = array_filter([
      ['wa', 'WhatsApp', !empty($settings['whatsapp_number']) ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $settings['whatsapp_number']) : ''],
      ['li', 'LinkedIn', $settings['linkedin_url'] ?? ''],
      ['ig', 'Instagram', $settings['instagram_url'] ?? ''],
      ['fb', 'Facebook', $settings['facebook_url'] ?? ''],
      ['x', 'X', $settings['twitter_url'] ?? ''],
      ['yt', 'YouTube', $settings['youtube_url'] ?? ''],
  ], static fn ($r) => $r[2] !== '');
  $contactEmail = $settings['contact_email'] ?? 'hr@talenthubconsultant.com';
  $hiringEmail  = $settings['hiring_email'] ?? 'hiring@talenthubconsultant.com';
  $contactPhone = $settings['contact_phone'] ?? '+91 72761 46573';
  ?>
  <footer class="site-footer">
    <div class="wrap">
      <div class="footer-cta">
        <h2>Need the right people or the right software?</h2>
        <div class="btn-row">
          <a class="btn btn--light" href="<?= base_url('project-enquiry') ?>">Discuss Your Project</a>
          <a class="btn btn--outline-light" href="<?= base_url('employers') ?>">Share a Hiring Requirement</a>
        </div>
      </div>

      <div class="footer-grid">
        <div class="footer-brand">
          <a href="<?= base_url() ?>" aria-label="<?= $siteName ?> — home"><img src="<?= $logoDark ?>" alt="<?= $siteName ?>" width="160" height="40" loading="lazy" decoding="async" style="height:40px;width:auto"></a>
          <p><?= esc($settings['site_description'] ?? 'TalentHub is a Pan-India recruitment and technology consulting partner. We help organisations hire the right talent, build digital solutions and modernise operations, with an operations base in Pune, Maharashtra.') ?></p>
          <?php if ($socials): ?>
          <div class="footer-social" role="list" aria-label="Social media">
            <?php foreach ($socials as [$k, $label, $url]): ?>
              <a role="listitem" href="<?= esc($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= $k === 'wa' ? 'WhatsApp' : 'TalentHub on ' . $label . ' (opens in a new tab)' ?>" class="footer-social-icon<?= $k === 'wa' ? ' social-wa' : '' ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?= $socialPaths[$k] ?>"/></svg></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div>
          <h3>Company</h3>
          <ul>
            <li><a href="<?= base_url('about') ?>">About Us</a></li>
            <li><a href="<?= base_url('services') ?>">Services</a></li>
            <li><a href="<?= base_url('products') ?>">Products</a></li>
            <li><a href="<?= base_url('insights') ?>">Insights</a></li>
            <li><a href="<?= base_url('contact') ?>">Contact Us</a></li>
          </ul>
        </div>

        <div>
          <h3>Services</h3>
          <ul>
            <li><a href="<?= base_url('services/recruitment') ?>">Recruitment &amp; Talent Acquisition</a></li>
            <li><a href="<?= base_url('services/it-services') ?>">IT Services &amp; Software</a></li>
            <li><a href="<?= base_url('project-enquiry') ?>">Discuss Your Project</a></li>
            <li><a href="<?= base_url('jobs') ?>">Browse Jobs</a></li>
            <li><a href="<?= base_url('employers') ?>">For Employers</a></li>
            <li><a href="<?= base_url('candidates') ?>">For Candidates</a></li>
          </ul>
        </div>

        <div>
          <h3>Get in touch</h3>
          <ul class="footer-contact">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span><?= esc($settings['office_address'] ?? 'Katraj, Pune — 411046, Maharashtra, India') ?></span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.04 11.04 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6z"/></svg><a href="tel:<?= preg_replace('/[^0-9+]/', '', $contactPhone) ?>" style="min-height:0"><?= esc($contactPhone) ?></a></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span><small>Customer &amp; B2B Enquiries</small><a href="mailto:<?= esc($contactEmail) ?>" style="min-height:0"><?= esc($contactEmail) ?></a></span></li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span><small>Candidate Job Applications</small><a href="mailto:<?= esc($hiringEmail) ?>" style="min-height:0"><?= esc($hiringEmail) ?></a></span></li>
          </ul>
          <form data-newsletter="true" data-source="footer_compact" class="footer-news" aria-label="Newsletter sign-up">
            <label for="footer-compact-newsletter" class="sr-only">Email address for the newsletter</label>
            <input type="email" id="footer-compact-newsletter" name="email" required placeholder="Your email for insights" autocomplete="email">
            <input type="hidden" name="consent" value="1">
            <input type="text" name="website_url" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
            <button type="submit" aria-label="Subscribe to the newsletter" style="right: 6px !important; left: auto !important;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7M21 12H3"/></svg></button>
          </form>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="wrap footer-bottom__wrap">
        <nav class="footer-legal" aria-label="Legal">
          <a href="<?= base_url('privacy') ?>">Privacy Policy</a>
          <a href="<?= base_url('privacy/request-cv-deletion') ?>">Request CV Erasure (DPDP)</a>
          <a href="<?= base_url('terms') ?>">Terms of Service</a>
          <a href="<?= base_url('faq') ?>">FAQ</a>
          <a href="<?= base_url('sitemap.xml') ?>">XML Sitemap</a>
        </nav>
        <div class="footer-bottom__meta">
          <p style="margin:0">&copy; <?= date('Y') ?> <strong style="color:#fff">TalentHub Consultant</strong>. All rights reserved.</p>
          <p style="margin:0">Designed &amp; developed by <a href="https://praveenchavan.vercel.app/" target="_blank" rel="noopener noreferrer" style="color:var(--c-mint);font-weight:700">Praveen Chavan</a></p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container Root -->
  <div id="toast-container" class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4"></div>

  <!-- Cloudflare Turnstile CAPTCHA (Server-Secret Validated) -->
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

  <!-- Vanilla Client Scripts (Dynamic Cache Busting) -->
  <script src="<?= $asset('assets/js/motion.js') ?>" defer></script>
  <script src="<?= $asset('assets/js/polish.js') ?>" defer></script>
  <script src="<?= $asset('assets/js/forms.js') ?>" defer></script>
  <script src="<?= base_url('assets/js/app.js') ?>?v=<?= file_exists(FCPATH . 'assets/js/app.js') ? filemtime(FCPATH . 'assets/js/app.js') : time() ?>"></script>
  <script src="<?= base_url('assets/js/engagement.js') ?>?v=<?= file_exists(FCPATH . 'assets/js/engagement.js') ? filemtime(FCPATH . 'assets/js/engagement.js') : time() ?>"></script>

  <!-- Tawk.to Live Chat (Dynamic) -->
  <?php if ($tawkEnabled && !empty($tawkPropertyId)): ?>
  <script type="text/javascript">
    var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
    (function(){
      var s1 = document.createElement("script"), s0 = document.getElementsByTagName("script")[0];
      s1.async = true;
      s1.src = 'https://embed.tawk.to/<?= esc($tawkPropertyId) ?>/<?= esc($tawkWidgetId) ?>';
      s1.charset = 'UTF-8';
      s1.setAttribute('crossorigin','*');
      s0.parentNode.insertBefore(s1,s0);
    })();
  </script>
  <?php endif; ?>

  <!-- Progressive Web App Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('<?= base_url($swFile) ?>')
          .then(reg => console.debug('TalentHub PWA ServiceWorker active', reg.scope))
          .catch(err => console.debug('ServiceWorker notice:', err));
      });
    }
  </script>

</body>
</html>
