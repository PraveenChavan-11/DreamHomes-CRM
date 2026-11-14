/* TalentHub polish layer: card spotlight and magnetic buttons (pointer devices only). Scroll progress now lives in motion.js. */
(function () {
  'use strict';
  var d = document;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  if (reduce || !fine) return;

  // Cursor-follow spotlight on cards across all pages
  var cardSel = '.th-card, .th-step-card, .card-premium, .tp-card, .th-service-card, .case-study-card, .tp-module, .th-story-card, [data-spotlight], .spotlight-card, .glass-card, .stat-card, .bento-card, .feature-card, .benefit-card, .tp-benefit, .contact-card, .job-card, .rounded-2xl.border, .rounded-3xl.border, .th-stat-card, .th-diff-card, .th-pillar-card, .th-culture-card, .tp-shot, .tp-role, .th-pillar, .culture-card, .perk-card';
  d.addEventListener('pointermove', function (e) {
    var c = e.target.closest && e.target.closest(cardSel);
    if (!c) return;
    var r = c.getBoundingClientRect();
    c.style.setProperty('--mx', (e.clientX - r.left) + 'px');
    c.style.setProperty('--my', (e.clientY - r.top) + 'px');
  }, { passive: true });

  // Magnetic pull on primary buttons
  [].forEach.call(d.querySelectorAll('.btn--primary, .btn--light, .th-btn--primary, .th-btn--light'), function (b) {
    b.addEventListener('pointermove', function (e) {
      var r = b.getBoundingClientRect();
      var x = (e.clientX - r.left - r.width / 2) * 0.18, y = (e.clientY - r.top - r.height / 2) * 0.28;
      b.style.translate = x.toFixed(1) + 'px ' + y.toFixed(1) + 'px';
    });
    b.addEventListener('pointerleave', function () { b.style.translate = ''; });
  });
})();
