<?php
/**
 * Admin sidebar navigation — data-driven, grouped, collapsible, searchable.
 *
 * Expects: $activeMenu, $counts, $canAccess (Closure).
 * Only destinations that exist as routes are listed. Permissions are still
 * enforced server-side by RbacFilter; this only controls what is shown.
 */
$I = [ // heroicons-outline path data
    'home'     => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'briefcase'=> 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'chip'     => 'M9 3v2m6-2v2M9 19v2m6-2v2M3 9h2m-2 6h2m14-6h2m-2 6h2M7 7h10v10H7V7z',
    'cube'     => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'megaphone'=> 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    'mail'     => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'bell'     => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
    'doc'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'cog'      => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    'user'     => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
];

// Each group: key, label, icon, items[] = [label, path, activeKey(s), perms[], badgeKey|null]
$nav = [
    ['key' => 'recruitment', 'label' => 'Recruitment & Talent', 'icon' => 'briefcase',
     'perms' => ['jobs.publish', 'jobs.close', 'applications.view', 'applications.transition', 'candidates.view', 'candidates.manage'],
     'items' => [
        ['Job Postings',        'admin/jobs',         ['jobs'],         ['jobs.publish', 'jobs.close', 'applications.view'], null],
        ['Applications',        'admin/applications', ['applications'], ['applications.view', 'applications.transition'], 'new_applications'],
        ['General Resume Bank', 'admin/resume-bank',  ['resume_bank'],  ['candidates.view', 'candidates.manage'], 'total_resumes'],
     ]],
    ['key' => 'consulting', 'label' => 'Consulting, IT & Leads', 'icon' => 'chip',
     'perms' => ['it_leads.view', 'it_leads.manage', 'crm.view', 'crm.manage', 'employers.manage', 'enquiries.manage'],
     'items' => [
        ['CRM Leads',                'admin/it-leads',            ['it_leads'],           ['it_leads.view', 'it_leads.manage', 'crm.view', 'crm.manage'], 'new_it_leads'],
        ['CRM Settings',             'admin/it-leads/settings',   ['it_leads_settings'],  ['crm.settings'], null],
        ['Employer Hiring Requests', 'admin/enquiries/employers', ['employer_enquiries'], ['employers.manage', 'enquiries.manage'], 'new_employer'],
        ['Contact Messages',         'admin/enquiries/contact',   ['contact_enquiries'],  ['enquiries.manage'], 'new_contact'],
        ['Unified Leads',            'admin/engagement/leads',    ['engagement_leads'],   ['engagement.manage', 'enquiries.manage'], null],
     ]],
    ['key' => 'products', 'label' => 'Products & Solutions', 'icon' => 'cube',
     'perms' => ['products.view', 'products.manage', 'case_studies.view', 'case_studies.manage'],
     'items' => [
        ['Product Catalogue', 'admin/products',     ['products'],     ['products.view', 'products.manage'], null],
        ['Case Studies',      'admin/case-studies', ['case_studies'], ['case_studies.view', 'case_studies.manage'], null],
     ]],
    ['key' => 'social', 'label' => 'Social Media Marketing', 'icon' => 'megaphone',
     'perms' => ['social.view', 'social.create', 'social.publish', 'social.manage', 'social.settings', 'social.approve'],
     'items' => [
        ['Marketing Overview',      'admin/social',              ['social_overview'],    ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Create Post',             'admin/social/posts/create', ['social_create_post'], ['social.create', 'social.publish', 'social.manage'], null],
        ['All Posts',               'admin/social/posts',        ['social_posts'],       ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Content Calendar',        'admin/social/calendar',     ['social_calendar'],    ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Scheduled Posts',         'admin/social/scheduled',    ['social_scheduled'],   ['social.view', 'social.create', 'social.publish', 'social.manage'], 'scheduled_posts'],
        ['Drafts',                  'admin/social/drafts',       ['social_drafts'],      ['social.view', 'social.create', 'social.publish', 'social.manage'], 'draft_posts'],
        ['AI Draft Approvals',      'admin/agents/approvals',    ['agent_approvals'],    ['social.approve'], 'agent_pending'],
        ['Publishing Queue',        'admin/social/queue',        ['social_queue'],       ['social.view', 'social.create', 'social.publish', 'social.manage'], 'queued_jobs'],
        ['Media Library',           'admin/social/media',        ['social_media'],       ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Campaigns',               'admin/social/campaigns',    ['social_campaigns'],   ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Social Enquiries',        'admin/social/enquiries',    ['social_enquiries'],   ['social.enquiries', 'social.view', 'social.manage'], 'new_enquiries'],
        ['Comments & Engagement',   'admin/social/engagement',   ['social_engagement'],  ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Analytics & Reports',     'admin/social/analytics',    ['social_analytics'],   ['social.analytics', 'social.view', 'social.manage'], null],
        ['Connected Accounts',      'admin/social/accounts',     ['social_accounts'],    ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
        ['Integration Settings',    'admin/social/settings',     ['social_settings'],    ['social.settings'], null],
        ['Activity Logs',           'admin/social/logs',         ['social_logs'],        ['social.view', 'social.create', 'social.publish', 'social.manage'], null],
     ]],
    ['key' => 'comms', 'label' => 'Communication Centre', 'icon' => 'mail',
     'perms' => ['templates.manage', 'enquiries.manage', 'candidates.manage', 'engagement.view', 'engagement.manage', 'newsletter.manage', 'campaigns.send', 'settings.manage'],
     'items' => [
        ['Hostinger Mail Center',   'admin/mail',                         ['mail'],                      ['templates.manage', 'enquiries.manage', 'candidates.manage'], 'unread_mail'],
        ['Email Templates',         'admin/mail/templates',               ['email_templates'],           ['templates.manage'], null],
        ['Engagement Overview',     'admin/engagement',                   ['engagement_overview'],       ['engagement.view', 'engagement.manage', 'newsletter.manage', 'campaigns.send'], null],
        ['Announcements & Popups',  'admin/engagement/announcements',     ['engagement_announcements'],  ['engagement.manage'], 'active_campaigns'],
        ['1-Click Direct Notice',   'admin/engagement/quick-notice',      ['engagement_quick_notice'],   ['engagement.manage', 'campaigns.send'], null],
        ['Newsletter Subscribers',  'admin/engagement/subscribers',       ['engagement_subscribers'],    ['newsletter.manage', 'engagement.manage'], null],
        ['Job Alert Subscriptions', 'admin/engagement/job-alerts',        ['engagement_job_alerts'],     ['newsletter.manage', 'engagement.manage', 'jobs.publish'], null],
        ['Email Campaigns',         'admin/engagement/campaigns',         ['engagement_campaigns'],      ['campaigns.send'], null],
        ['Campaign Analytics',      'admin/engagement/analytics',         ['engagement_analytics'],      ['engagement.view', 'engagement.manage'], null],
        ['Cookie Consent Logs',     'admin/engagement/cookies',           ['engagement_cookies'],        ['settings.manage', 'engagement.manage'], null],
     ]],
    ['key' => 'content', 'label' => 'Media & Content', 'icon' => 'doc',
     'perms' => ['content.manage'],
     'items' => [
        ['Articles & Insights', 'admin/content/articles',     ['articles'],     ['content.manage'], null],
        ['Page Content',        'admin/content/pages',        ['pages'],        ['content.manage'], null],
        ['Testimonials',        'admin/content/testimonials', ['testimonials'], ['content.manage'], null],
        ['FAQs',                'admin/content/faqs',         ['faqs'],         ['content.manage'], null],
     ]],
    ['key' => 'system', 'label' => 'System Administration', 'icon' => 'cog',
     'perms' => [],
     'items' => [
        ['System Health',         'admin/system/health',    ['system_health'],    ['system.view'], null],
        ['Background Jobs',       'admin/system/jobs',      ['system_jobs'],      ['system.view'], null],
        ['Scheduler & Cron',      'admin/system/scheduler', ['system_scheduler'], ['system.view'], null],
        ['API Integrations',      'admin/integrations',     ['integrations'],     ['integrations.view', 'integrations.manage'], null],
        ['External AI Agents',    'admin/agents',           ['agents'],           ['agents.view'], null],
        ['Staff & Users',         'admin/users',      ['users'],      ['users.manage'], null],
        ['Roles & Permissions',   'admin/roles',      ['roles'],      ['users.manage'], null],
        ['Audit Logs',            'admin/audit-logs', ['audit_logs'], ['audit.view'], null],
        ['Website Settings',      'admin/settings',   ['settings'],   ['settings.manage'], null],
        ['My Profile & Password', 'admin/profile',    ['profile'],    [], null],
     ]],
];

$currentPath = trim((string) parse_url((string) current_url(), PHP_URL_PATH), '/');
// Longest-prefix match so sub-pages (admin/jobs/edit/3) highlight their parent item only.
$bestPath = '';
foreach ($nav as $g0) {
    foreach ($g0['items'] as $it0) {
        $p0 = trim($it0[1], '/');
        if (($currentPath === $p0 || str_starts_with($currentPath, $p0 . '/')) && strlen($p0) > strlen($bestPath)) {
            $bestPath = $p0;
        }
    }
}
$isActive = static function (array $item) use ($activeMenu, $bestPath): bool {
    return in_array($activeMenu, $item[2], true) || ($bestPath !== '' && trim($item[1], '/') === $bestPath);
};

// Pre-compute visible items per group and which group is open by default.
$visibleGroups = [];
foreach ($nav as $g) {
    if ($g['perms'] && !$canAccess(...$g['perms'])) {
        continue;
    }
    $items = [];
    foreach ($g['items'] as $it) {
        if ($it[3] && !$canAccess(...$it[3])) {
            continue;
        }
        $items[] = $it;
    }
    if (!$items) {
        continue;
    }
    // "admin/social" / "admin/mail" style roots must not shadow deeper pages in the active check.
    $g['items']  = $items;
    $g['active'] = false;
    foreach ($items as $it) {
        if ($isActive($it)) { $g['active'] = true; break; }
    }
    $visibleGroups[] = $g;
}
$dashActive = ($activeMenu === 'dashboard');
$ico = static fn(string $k) => '<svg class="nav-ico w-[18px] h-[18px] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="' . ($I[$k] ?? '') . '"/></svg>';
?>
<div class="px-3 pt-3 pb-2 nav-search-wrap">
  <label for="nav-search" class="sr-only">Search navigation</label>
  <input id="nav-search" type="search" autocomplete="off" placeholder="Search menu…  ( / )"
         class="w-full rounded-lg bg-white/10 border border-white/10 px-3 py-2 text-xs text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-brand-accent">
</div>

<nav id="admin-nav" aria-label="Admin navigation" class="flex-1 overflow-y-auto overscroll-contain px-3 pb-4 space-y-1">
  <a href="<?= base_url('admin') ?>" title="Dashboard" data-nav-item data-label="dashboard overview"
     class="nav-link <?= $dashActive ? 'is-active' : '' ?>" <?= $dashActive ? 'aria-current="page"' : '' ?>>
    <?= $ico('home') ?><span class="nav-label">Dashboard</span>
  </a>

  <?php foreach ($visibleGroups as $g): ?>
    <section class="nav-group" data-group="<?= esc($g['key']) ?>" data-active="<?= $g['active'] ? '1' : '0' ?>">
      <button type="button" class="nav-group-btn" aria-expanded="false" aria-controls="grp-<?= esc($g['key']) ?>" title="<?= esc($g['label']) ?>">
        <?= $ico($g['icon']) ?>
        <span class="nav-label flex-1 text-left"><?= esc($g['label']) ?></span>
        <svg class="nav-chev w-3.5 h-3.5 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>
      </button>
      <div id="grp-<?= esc($g['key']) ?>" class="nav-group-body" hidden>
        <?php foreach ($g['items'] as $it):
            $act   = $isActive($it);
            $badge = $it[4] ? ($counts[$it[4]] ?? 0) : 0; ?>
          <a href="<?= base_url($it[1]) ?>" data-nav-item data-label="<?= esc(strtolower($it[0] . ' ' . $g['label'])) ?>"
             class="nav-sub <?= $act ? 'is-active' : '' ?>" <?= $act ? 'aria-current="page"' : '' ?>>
            <span class="flex-1 truncate"><?= esc($it[0]) ?></span>
            <?php if (!empty($badge)): ?><span class="nav-badge"><?= esc($badge) ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <p id="nav-empty" class="hidden px-3 py-3 text-xs text-white/50">No menu items match your search.</p>
</nav>

<noscript><style>#admin-nav .nav-group-body[hidden]{display:block!important}</style></noscript>
<style>
  #admin-nav .nav-link,#admin-nav .nav-group-btn{display:flex;align-items:center;gap:.75rem;width:100%;padding:.6rem .75rem;border-radius:.75rem;font-size:.75rem;font-weight:600;color:#cbd5e1;transition:background .15s,color .15s}
  #admin-nav .nav-link:hover,#admin-nav .nav-group-btn:hover{background:rgba(255,255,255,.07);color:#fff}
  #admin-nav .nav-link:focus-visible,#admin-nav .nav-group-btn:focus-visible,#admin-nav .nav-sub:focus-visible{outline:2px solid #37CE97;outline-offset:1px}
  #admin-nav .is-active.nav-link{background:#1D6A34;color:#fff;box-shadow:0 4px 12px rgba(29,106,52,.4)}
  #admin-nav .nav-group[data-active="1"]>.nav-group-btn{color:#fff}
  #admin-nav .nav-group-btn[aria-expanded="true"] .nav-chev{transform:rotate(90deg)}
  #admin-nav .nav-ico{color:#94a3b8}
  #admin-nav .is-active .nav-ico,#admin-nav .nav-group[data-active="1"] .nav-ico{color:#37CE97}
  #admin-nav .nav-group-body{margin:.15rem 0 .35rem 1.35rem;padding-left:.6rem;border-left:1px solid rgba(255,255,255,.12);overflow:hidden}
  #admin-nav .nav-group-body.anim{transition:height .22s ease}
  #admin-nav .nav-sub{display:flex;align-items:center;gap:.5rem;padding:.45rem .65rem;border-radius:.6rem;font-size:.72rem;font-weight:600;color:#cbd5e1}
  #admin-nav .nav-sub:hover{background:rgba(255,255,255,.07);color:#fff}
  #admin-nav .nav-sub.is-active{background:#1D6A34;color:#fff}
  #admin-nav .nav-badge{min-width:1.15rem;padding:0 .35rem;border-radius:999px;background:#37CE97;color:#072412;font-size:.62rem;font-weight:800;text-align:center;line-height:1.1rem}
  /* Collapsed (icon-only) desktop mode */
  @media (min-width:768px){
    body.nav-collapsed #admin-sidebar{width:4.5rem}
    body.nav-collapsed #admin-sidebar .nav-label,body.nav-collapsed #admin-sidebar .nav-chev,body.nav-collapsed #admin-sidebar .nav-search-wrap,body.nav-collapsed #admin-sidebar .nav-brand-text,body.nav-collapsed #admin-sidebar .nav-user-text{display:none}
    body.nav-collapsed #admin-nav .nav-group-body{display:none}
    body.nav-collapsed #admin-nav .nav-link,body.nav-collapsed #admin-nav .nav-group-btn{justify-content:center;padding:.7rem}
  }
  @media (prefers-reduced-motion:reduce){#admin-nav *{transition:none!important}}
</style>

<script>
(function () {
  var nav = document.getElementById('admin-nav');
  if (!nav) return;
  var KEY_OPEN = 'th_nav_open', KEY_COLL = 'th_nav_collapsed';
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function load(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } }
  function save(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }

  var groups = [].slice.call(nav.querySelectorAll('.nav-group'));
  var openSet = load(KEY_OPEN, null);
  // Default: only the group holding the active page is open. A user's saved choice is
  // honoured, but the active group is always opened so the current page is visible.
  var open = {};
  groups.forEach(function (g) {
    var k = g.dataset.group;
    open[k] = openSet ? !!openSet[k] : false;
  });
  // Expand only the relevant section: when the current page belongs to a group, that group
  // alone is open. The saved choice applies on pages that belong to no group (e.g. Dashboard).
  var activeGroup = groups.filter(function (g) { return g.dataset.active === '1'; })[0];
  if (activeGroup) groups.forEach(function (g) { open[g.dataset.group] = (g === activeGroup); });

  function setOpen(g, state, animate) {
    var btn = g.querySelector('.nav-group-btn'), body = g.querySelector('.nav-group-body');
    btn.setAttribute('aria-expanded', state ? 'true' : 'false');
    if (!animate || reduce) { body.hidden = !state; body.style.height = ''; return; }
    body.classList.add('anim');
    if (state) {
      body.hidden = false; var h = body.scrollHeight; body.style.height = '0px';
      requestAnimationFrame(function () { body.style.height = h + 'px'; });
      setTimeout(function () { body.style.height = ''; }, 240);
    } else {
      body.style.height = body.scrollHeight + 'px';
      requestAnimationFrame(function () { body.style.height = '0px'; });
      setTimeout(function () { body.hidden = true; body.style.height = ''; }, 230);
    }
  }
  groups.forEach(function (g) {
    setOpen(g, open[g.dataset.group], false);
    g.querySelector('.nav-group-btn').addEventListener('click', function () {
      // Accordion: opening one group closes the others (keeps the sidebar short).
      var willOpen = this.getAttribute('aria-expanded') !== 'true';
      groups.forEach(function (o) { if (o !== g && open[o.dataset.group]) { open[o.dataset.group] = false; setOpen(o, false, true); } });
      open[g.dataset.group] = willOpen; setOpen(g, willOpen, true);
      save(KEY_OPEN, open);
    });
  });
  var activeLink = nav.querySelector('.is-active');
  if (activeLink && activeLink.scrollIntoView) activeLink.scrollIntoView({ block: 'nearest' });

  // Keyboard: arrow keys move between visible items.
  nav.addEventListener('keydown', function (e) {
    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
    var els = [].slice.call(nav.querySelectorAll('.nav-link,.nav-group-btn,.nav-group-body:not([hidden]) .nav-sub')).filter(function (x) { return x.offsetParent !== null; });
    var i = els.indexOf(document.activeElement); if (i < 0) return;
    e.preventDefault(); (els[i + (e.key === 'ArrowDown' ? 1 : -1)] || els[i]).focus();
  });

  // Search: filters items, force-expands matching groups, restores state when cleared.
  var search = document.getElementById('nav-search'), empty = document.getElementById('nav-empty');
  function filter() {
    var q = search.value.trim().toLowerCase(), any = false;
    groups.forEach(function (g) {
      var hit = false;
      g.querySelectorAll('[data-nav-item]').forEach(function (a) {
        var m = !q || a.dataset.label.indexOf(q) !== -1; a.style.display = m ? '' : 'none'; if (m) hit = true;
      });
      g.style.display = hit ? '' : 'none';
      var body = g.querySelector('.nav-group-body');
      if (q) { body.hidden = !hit; g.querySelector('.nav-group-btn').setAttribute('aria-expanded', hit ? 'true' : 'false'); }
      else setOpen(g, !!open[g.dataset.group], false);
      if (hit) any = true;
    });
    var dash = nav.querySelector(':scope > [data-nav-item]');
    if (dash) { var dm = !q || dash.dataset.label.indexOf(q) !== -1; dash.style.display = dm ? '' : 'none'; if (dm) any = true; }
    empty.classList.toggle('hidden', any);
  }
  search.addEventListener('input', filter);
  document.addEventListener('keydown', function (e) {
    var t = (e.target.tagName || '').toLowerCase();
    if (e.key === '/' && t !== 'input' && t !== 'textarea' && !e.target.isContentEditable) { e.preventDefault(); search.focus(); }
  });

  // Desktop collapsed/icon-only mode (preference remembered).
  function applyCollapsed(c) {
    document.body.classList.toggle('nav-collapsed', !!c);
    var b = document.getElementById('sidebar-collapse-toggle');
    if (b) b.setAttribute('aria-pressed', c ? 'true' : 'false');
  }
  // The toggle lives in the header, which is rendered after this script — bind once the DOM is ready.
  function bindToggle() {
    applyCollapsed(load(KEY_COLL, false));
    var tg = document.getElementById('sidebar-collapse-toggle');
    if (tg) tg.addEventListener('click', function () { var c = !document.body.classList.contains('nav-collapsed'); applyCollapsed(c); save(KEY_COLL, c); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindToggle); else bindToggle();
  // In collapsed mode clicking a group icon expands the sidebar so its children are reachable.
  nav.addEventListener('click', function (e) {
    var b = e.target.closest('.nav-group-btn');
    if (b && document.body.classList.contains('nav-collapsed')) { applyCollapsed(false); save(KEY_COLL, false); }
  }, true);
})();
</script>
