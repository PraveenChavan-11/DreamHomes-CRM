/*!
 * TalentHub motion + UI system. No dependencies.
 *
 * Progressive enhancement: the page is fully visible and usable without this file. It only adds the
 * `js-motion` class once it is running, and every effect is skipped under prefers-reduced-motion.
 * Animates transform / opacity / clip-path only. No scroll hijacking. All listeners hang off one
 * AbortController so they are removed on pagehide.
 *
 * Hooks (all optional data attributes):
 *   data-reveal="up|fade|left|right|scale|mask|text"  scroll reveal   (data-delay="120" in ms)
 *   data-stagger                                       children reveal one after another
 *   data-parallax="0.08"                               gentle vertical drift (transform only)
 *   data-count="1250" [data-count-suffix="+"]          animated counter (only for real figures)
 *   data-lightbox="group"                              <a href=image> opens the accessible lightbox
 */
(function () {
  'use strict';
  var d = document, root = d.documentElement, w = window;
  var mq = function (q) { return w.matchMedia ? w.matchMedia(q) : { matches: false, addEventListener: function () {} }; };
  var reduceMq = mq('(prefers-reduced-motion: reduce)');
  var reduce = reduceMq.matches;
  var ac = typeof AbortController === 'function' ? new AbortController() : null;
  var opt = function (o) { o = o || {}; if (ac) { o.signal = ac.signal; } return o; };
  var on = function (t, ev, fn, o) { t.addEventListener(ev, fn, opt(o)); };
  var qsa = function (sel, ctx) { return Array.prototype.slice.call((ctx || d).querySelectorAll(sel)); };
  w.addEventListener('pagehide', function () { if (ac) { ac.abort(); } });

  /* ───────────────────────── 1. Reveal system ───────────────────────── */
  function splitText(el) {
    // Word-by-word reveal. The full text stays on the element for assistive tech; the spans are decorative.
    if (el.getAttribute('data-split') === 'done') { return; }
    var text = el.textContent.replace(/\s+/g, ' ').trim();
    if (!text) { return; }
    el.setAttribute('aria-label', text);
    el.setAttribute('data-split', 'done');
    el.textContent = '';
    text.split(' ').forEach(function (word, i) {
      var outer = d.createElement('span');
      outer.className = 'tx-word';
      outer.setAttribute('aria-hidden', 'true');
      var inner = d.createElement('span');
      inner.textContent = word;
      inner.style.setProperty('--i', i);
      outer.appendChild(inner);
      el.appendChild(outer);
      if (i < text.split(' ').length - 1) { el.appendChild(d.createTextNode(' ')); }
    });
  }

  function initReveal() {
    var nodes = qsa('[data-reveal]');
    qsa('[data-stagger]').forEach(function (g) {
      qsa(':scope > *', g).forEach(function (c, i) {
        if (!c.hasAttribute('data-reveal')) { c.setAttribute('data-reveal', 'up'); nodes.push(c); }
        c.style.setProperty('--d', (Math.min(i, 8) * 70) + 'ms');
      });
    });
    nodes.forEach(function (el) {
      var dl = el.getAttribute('data-delay');
      if (dl) { el.style.setProperty('--d', parseInt(dl, 10) + 'ms'); }
      if (el.getAttribute('data-reveal') === 'text' && !reduce) { splitText(el); }
    });
    if (!nodes.length) { return; }
    if (reduce || !('IntersectionObserver' in w)) {
      nodes.forEach(function (el) { el.classList.add('is-in'); });
      root.classList.add('js-motion');
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    // Already on screen at load: mark visible in the same task as the class switch so nothing flashes.
    var vh = w.innerHeight || root.clientHeight;
    nodes.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.top < vh * 0.98 && r.bottom > 0) { el.classList.add('is-in', 'no-anim'); } else { io.observe(el); }
    });
    root.classList.add('js-motion');
    // After first frame, allow transitions again for above-the-fold items that carry stagger delays.
    requestAnimationFrame(function () { requestAnimationFrame(function () { qsa('.no-anim').forEach(function (el) { el.classList.remove('no-anim'); }); }); });
    // Safety net: if observation never fires (e.g. content injected oddly), never leave content hidden.
    setTimeout(function () { nodes.forEach(function (el) { if (!el.classList.contains('is-in')) { var r = el.getBoundingClientRect(); if (r.top < (w.innerHeight || 800) && r.bottom > 0) { el.classList.add('is-in'); } } }); }, 2500);
  }

  /* ───────────────────────── 2. Scroll progress + header state ───────────────────────── */
  var header = d.querySelector('[data-site-header]');
  var bar = null, barFill = null;
  function initProgress() {
    bar = d.createElement('div');
    bar.className = 'scroll-progress';
    bar.setAttribute('aria-hidden', 'true');
    barFill = d.createElement('i');
    bar.appendChild(barFill);
    d.body.appendChild(bar);
  }

  /* ───────────────────────── 3. Parallax (transform only, rAF-throttled) ───────────────────────── */
  var parallax = [];
  function initParallax() {
    if (reduce) { return; }
    qsa('[data-parallax]').forEach(function (el) {
      var f = parseFloat(el.getAttribute('data-parallax')) || 0.06;
      parallax.push({ el: el, f: f, vis: true });
    });
    if (!parallax.length) { return; }
    if ('IntersectionObserver' in w) {
      var po = new IntersectionObserver(function (es) {
        es.forEach(function (e) { parallax.forEach(function (p) { if (p.el === e.target) { p.vis = e.isIntersecting; } }); });
      }, { rootMargin: '10% 0px' });
      parallax.forEach(function (p) { po.observe(p.el); });
    }
  }
  function paintParallax() {
    var small = w.innerWidth < 768; // calmer on phones
    var vh = w.innerHeight;
    parallax.forEach(function (p) {
      if (!p.vis) { return; }
      var r = p.el.parentElement.getBoundingClientRect();
      var centre = r.top + r.height / 2 - vh / 2;
      p.el.style.transform = 'translate3d(0,' + (-centre * p.f * (small ? 0.5 : 1)).toFixed(1) + 'px,0)';
    });
  }

  /* One rAF-throttled scroll handler drives progress bar, header shadow and parallax. */
  var ticking = false;
  function frame() {
    ticking = false;
    var h = root.scrollHeight - w.innerHeight;
    if (barFill) { barFill.style.transform = 'scaleX(' + (h > 0 ? Math.min(1, w.scrollY / h) : 0).toFixed(4) + ')'; }
    if (header) { header.classList.toggle('is-scrolled', w.scrollY > 12); }
    if (parallax.length) { paintParallax(); }
  }
  function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(frame); } }

  /* ───────────────────────── 4. Counters (real figures only) ───────────────────────── */
  function initCounters() {
    var els = qsa('[data-count]');
    if (!els.length) { return; }
    var fmt = function (n) { try { return n.toLocaleString('en-IN'); } catch (e) { return String(n); } };
    function run(el) {
      var target = parseFloat(el.getAttribute('data-count'));
      if (isNaN(target)) { return; }
      var suffix = el.getAttribute('data-count-suffix') || '';
      if (reduce) { el.textContent = fmt(target) + suffix; return; }
      var dur = 1400, t0 = null;
      function step(ts) {
        if (t0 === null) { t0 = ts; }
        var p = Math.min(1, (ts - t0) / dur), eased = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(Math.round(target * eased)) + suffix;
        if (p < 1) { requestAnimationFrame(step); } else { el.textContent = fmt(target) + suffix; }
      }
      requestAnimationFrame(step);
    }
    if (reduce || !('IntersectionObserver' in w)) { els.forEach(run); return; }
    var co = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { run(e.target); co.unobserve(e.target); } });
    }, { threshold: 0.4 });
    els.forEach(function (el) { co.observe(el); });
  }

  /* ───────────────────────── 5. Header: services dropdown + mobile drawer ───────────────────────── */
  var focusable = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

  function initDropdowns() {
    qsa('.nav-dd').forEach(function (dd) {
      var btn = dd.querySelector('.nav-dd__btn');
      if (!btn) { return; }
      var closeTimer = null;
      function set(open) {
        dd.classList.toggle('is-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      }
      on(btn, 'click', function () { set(btn.getAttribute('aria-expanded') !== 'true'); });
      on(dd, 'keydown', function (e) { if (e.key === 'Escape') { set(false); btn.focus(); } });
      on(d, 'click', function (e) { if (!dd.contains(e.target)) { set(false); } });
      on(dd, 'focusout', function (e) { if (e.relatedTarget && !dd.contains(e.relatedTarget)) { set(false); } });
      // Pointer hover opens it for mouse users only; touch and keyboard always have the button.
      if (mq('(hover: hover) and (pointer: fine)').matches) {
        on(dd, 'pointerenter', function () { clearTimeout(closeTimer); set(true); });
        on(dd, 'pointerleave', function () { closeTimer = setTimeout(function () { set(false); }, 160); });
      }
    });
  }

  function initDrawer() {
    var toggle = d.querySelector('[data-drawer-toggle]');
    var drawer = d.getElementById('site-drawer');
    if (!toggle || !drawer) { return; }
    var scrim = d.querySelector('[data-drawer-scrim]');
    var lastFocus = null;
    function isOpen() { return drawer.classList.contains('is-open'); }
    function setOpen(open) {
      if (open === isOpen()) { return; }
      drawer.classList.toggle('is-open', open);
      if (scrim) { scrim.classList.toggle('is-open', open); }
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      root.classList.toggle('drawer-open', open);
      if (open) {
        lastFocus = d.activeElement;
        drawer.removeAttribute('inert');
        drawer.setAttribute('aria-hidden', 'false');
        var first = drawer.querySelector(focusable);
        if (first) { setTimeout(function () { first.focus(); }, 30); }
      } else {
        drawer.setAttribute('aria-hidden', 'true');
        drawer.setAttribute('inert', '');
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
      }
    }
    drawer.setAttribute('inert', '');
    drawer.setAttribute('aria-hidden', 'true');
    on(toggle, 'click', function () { setOpen(!isOpen()); });
    if (scrim) { on(scrim, 'click', function () { setOpen(false); }); }
    qsa('[data-drawer-close]', drawer).forEach(function (b) { on(b, 'click', function () { setOpen(false); }); });
    qsa('a[href]', drawer).forEach(function (a) { on(a, 'click', function () { setOpen(false); }); });
    on(d, 'keydown', function (e) {
      if (!isOpen()) { return; }
      if (e.key === 'Escape') { setOpen(false); return; }
      if (e.key === 'Tab') { // keep focus inside the drawer
        var f = qsa(focusable, drawer).filter(function (n) { return n.offsetParent !== null; });
        if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && d.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && d.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
    // Close automatically if the viewport grows past the mobile breakpoint.
    var desk = mq('(min-width: 1024px)');
    if (desk.addEventListener) { desk.addEventListener('change', function (e) { if (e.matches) { setOpen(false); } }, opt()); }
  }

  /* ───────────────────────── 6. Accessible lightbox ───────────────────────── */
  function initLightbox() {
    var links = qsa('a[data-lightbox]');
    if (!links.length) { return; }
    var dlg = d.createElement('div');
    dlg.className = 'lb';
    dlg.setAttribute('role', 'dialog');
    dlg.setAttribute('aria-modal', 'true');
    dlg.setAttribute('aria-label', 'Image viewer');
    dlg.hidden = true;
    dlg.innerHTML = '<button type="button" class="lb__btn lb__close" aria-label="Close image viewer">&times;</button>' +
      '<button type="button" class="lb__btn lb__prev" aria-label="Previous image">&#8249;</button>' +
      '<figure class="lb__fig"><img class="lb__img" alt=""><figcaption class="lb__cap"></figcaption></figure>' +
      '<button type="button" class="lb__btn lb__next" aria-label="Next image">&#8250;</button>';
    d.body.appendChild(dlg);
    var img = dlg.querySelector('.lb__img'), cap = dlg.querySelector('.lb__cap');
    var group = [], idx = 0, opener = null;
    function show(i) {
      idx = (i + group.length) % group.length;
      var a = group[idx], im = a.querySelector('img');
      img.src = a.getAttribute('href');
      img.alt = (im && im.getAttribute('alt')) || '';
      cap.textContent = a.getAttribute('data-caption') || '';
      dlg.classList.toggle('lb--single', group.length < 2);
    }
    function open(a) {
      var g = a.getAttribute('data-lightbox');
      group = links.filter(function (l) { return l.getAttribute('data-lightbox') === g; });
      opener = a;
      show(group.indexOf(a));
      dlg.hidden = false;
      root.classList.add('lb-open');
      requestAnimationFrame(function () { dlg.classList.add('is-open'); });
      dlg.querySelector('.lb__close').focus();
    }
    function close() {
      dlg.classList.remove('is-open');
      root.classList.remove('lb-open');
      setTimeout(function () { dlg.hidden = true; img.removeAttribute('src'); }, reduce ? 0 : 220);
      if (opener && opener.focus) { opener.focus(); }
    }
    links.forEach(function (a) { on(a, 'click', function (e) { e.preventDefault(); open(a); }); });
    on(dlg.querySelector('.lb__close'), 'click', close);
    on(dlg.querySelector('.lb__prev'), 'click', function () { show(idx - 1); });
    on(dlg.querySelector('.lb__next'), 'click', function () { show(idx + 1); });
    on(dlg, 'click', function (e) { if (e.target === dlg) { close(); } });
    on(d, 'keydown', function (e) {
      if (dlg.hidden) { return; }
      if (e.key === 'Escape') { close(); }
      else if (e.key === 'ArrowLeft') { show(idx - 1); }
      else if (e.key === 'ArrowRight') { show(idx + 1); }
      else if (e.key === 'Tab') {
        var f = qsa('.lb__btn', dlg).filter(function (b) { return b.offsetParent !== null; });
        if (!f.length) { return; }
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && d.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && d.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    });
  }

  /* ───────────────────────── 7. Smooth Accordions (Web Animations API) ───────────────────────── */
  function initAccordions() {
    var detailsList = qsa('.th-faq details, .th-step details, [data-accordion] details, details');
    detailsList.forEach(function (detail) {
      var summary = detail.querySelector('summary');
      if (!summary) return;

      var animation = null;
      var isClosing = false;
      var isExpanding = false;

      function onAnimationFinish(open) {
        detail.open = open;
        animation = null;
        isClosing = false;
        isExpanding = false;
        detail.style.height = '';
        detail.style.overflow = '';
      }

      function shrink() {
        isClosing = true;
        var startHeight = detail.offsetHeight + 'px';
        var endHeight = summary.offsetHeight + 'px';

        if (animation) animation.cancel();

        animation = detail.animate({
          height: [startHeight, endHeight]
        }, {
          duration: 320,
          easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
        });

        animation.onfinish = function () { onAnimationFinish(false); };
        animation.oncancel = function () { isClosing = false; };
      }

      function open() {
        detail.style.height = detail.offsetHeight + 'px';
        detail.open = true;
        w.requestAnimationFrame(function () { expand(); });
      }

      function expand() {
        isExpanding = true;
        var startHeight = detail.offsetHeight + 'px';
        var endHeight = (summary.offsetHeight + Array.prototype.slice.call(detail.children).reduce(function (acc, child) {
          return child !== summary ? acc + child.offsetHeight : acc;
        }, 0)) + 'px';

        if (animation) animation.cancel();

        animation = detail.animate({
          height: [startHeight, endHeight]
        }, {
          duration: 340,
          easing: 'cubic-bezier(0.16, 1, 0.3, 1)'
        });

        animation.onfinish = function () { onAnimationFinish(true); };
        animation.oncancel = function () { isExpanding = false; };
      }

      summary.addEventListener('click', function (e) {
        if (reduce || !detail.animate) return; // Fallback to native toggle
        e.preventDefault();
        detail.style.overflow = 'hidden';

        // Close other open details in the same parent accordion group
        var parentGroup = detail.closest('[data-accordion], .th-faq, .th-journey');
        if (parentGroup && !detail.open) {
          qsa('details[open]', parentGroup).forEach(function (other) {
            if (other !== detail && !other.classList.contains('is-closing')) {
              var otherSum = other.querySelector('summary');
              if (otherSum && other.animate) {
                other.style.overflow = 'hidden';
                var sH = other.offsetHeight + 'px';
                var eH = otherSum.offsetHeight + 'px';
                var a = other.animate({ height: [sH, eH] }, { duration: 280, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' });
                a.onfinish = function () {
                  other.open = false;
                  other.style.height = '';
                  other.style.overflow = '';
                };
              } else {
                other.open = false;
              }
            }
          });
        }

        if (isClosing || !detail.open) {
          open();
        } else if (isExpanding || detail.open) {
          shrink();
        }
      });
    });
  }

  /* ───────────────────────── 8. Legacy service-journey timeline (progress line + active step) ───────────────────────── */
  function initJourneys() {
    qsa('.th-journey').forEach(function (j) {
      var steps = qsa('.th-step', j), prog = j.querySelector('.th-journey__progress');
      function update() {
        var mid = w.innerHeight * 0.6, last = -1;
        steps.forEach(function (s, i) { var on_ = s.getBoundingClientRect().top < mid; s.classList.toggle('is-active', on_); if (on_) { last = i; } });
        if (prog) {
          if (last >= 0) { prog.style.height = Math.max(0, steps[last].getBoundingClientRect().top - j.getBoundingClientRect().top + 20) + 'px'; } else { prog.style.height = '0px'; }
        }
      }
      on(w, 'scroll', function () { requestAnimationFrame(update); }, { passive: true });
      on(w, 'resize', update, { passive: true });
      update();
      on(j, 'toggle', function (e) { if (e.target.open) { qsa('details[open]', j).forEach(function (o) { if (o !== e.target) { o.open = false; } }); } }, true);
    });
  }

  /* ───────────────────────── 9. Hirxpert Hero Slider Master Controller ───────────────────────── */
  function initHeroSlider() {
    var slider = d.getElementById('hero-slider');
    if (!slider) return;
    var slides = qsa('.th-hirx-slide', slider);
    if (!slides.length) return;
    var prevBtn = slider.querySelector('#th-hirx-prev');
    var nextBtn = slider.querySelector('#th-hirx-next');
    var mosaic = slider.querySelector('#th-hirx-mosaic');
    var watermark = slider.querySelector('#th-hirx-watermark');
    var current = 0;
    var timer = null;
    var slideDuration = 6500;
    var isWiping = false;

    function goToSlide(idx) {
      if (!slides.length) return;
      var nextIdx = (idx + slides.length) % slides.length;

      // Trigger Mosaic Dissolve Wipe
      if (mosaic && !isWiping && !reduce) {
        isWiping = true;
        mosaic.classList.remove('is-wiping');
        void mosaic.offsetWidth; // force reflow
        mosaic.classList.add('is-wiping');
        setTimeout(function () {
          mosaic.classList.remove('is-wiping');
          isWiping = false;
        }, 850);
      }

      current = nextIdx;

      // Update Slides
      slides.forEach(function (s, i) {
        var isActive = (i === current);
        s.classList.toggle('is-active', isActive);
      });

      // Update Ghost Watermark Text
      if (watermark) {
        var nextWord = slides[current].getAttribute('data-watermark') || 'Solutions.';
        watermark.classList.add('is-fading');
        setTimeout(function () {
          watermark.textContent = nextWord;
          watermark.classList.remove('is-fading');
        }, 320);
      }
    }

    function startTimer() {
      stopTimer();
      timer = setInterval(function () {
        goToSlide(current + 1);
      }, slideDuration);
    }

    function stopTimer() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function (e) {
        e.preventDefault();
        goToSlide(current - 1);
        startTimer();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        goToSlide(current + 1);
        startTimer();
      });
    }

    slider.addEventListener('mouseenter', stopTimer);
    slider.addEventListener('mouseleave', startTimer);

    // Touch Swipe Support
    var touchStartX = 0;
    slider.addEventListener('touchstart', function (e) {
      if (e.changedTouches && e.changedTouches[0]) {
        touchStartX = e.changedTouches[0].screenX;
      }
    }, { passive: true });

    slider.addEventListener('touchend', function (e) {
      if (!e.changedTouches || !e.changedTouches[0]) return;
      var diff = touchStartX - e.changedTouches[0].screenX;
      if (Math.abs(diff) > 40) {
        if (diff > 0) {
          goToSlide(current + 1);
        } else {
          goToSlide(current - 1);
        }
        startTimer();
      }
    }, { passive: true });

    // Keyboard navigation when hero is in viewport
    d.addEventListener('keydown', function (e) {
      var rect = slider.getBoundingClientRect();
      var inView = rect.top < w.innerHeight && rect.bottom > 0;
      if (!inView) return;
      if (e.key === 'ArrowLeft') {
        goToSlide(current - 1);
        startTimer();
      } else if (e.key === 'ArrowRight') {
        goToSlide(current + 1);
        startTimer();
      }
    });

    // Initialize first slide and timer
    goToSlide(0);
    startTimer();
  }

  /* ───────────────────────── Boot ───────────────────────── */
  function boot() {
    initReveal();
    initProgress();
    initParallax();
    initCounters();
    initDropdowns();
    initDrawer();
    initLightbox();
    initAccordions();
    initJourneys();
    initHeroSlider();
    on(w, 'scroll', onScroll, { passive: true });
    on(w, 'resize', onScroll, { passive: true });
    frame();
    // React to the user toggling reduced motion while the page is open.
    if (reduceMq.addEventListener) {
      reduceMq.addEventListener('change', function (e) { if (e.matches) { qsa('[data-reveal]').forEach(function (el) { el.classList.add('is-in'); }); parallax.forEach(function (p) { p.el.style.transform = ''; }); parallax = []; } }, opt());
    }
  }
  if (d.readyState === 'loading') { d.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
