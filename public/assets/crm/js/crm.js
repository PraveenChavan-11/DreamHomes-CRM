/* DHIS Sales CRM — progressive enhancement only. Pages work without JS; every figure is recomputed on the server. */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  var csrf = function () { var m = $('meta[name="csrf-token"]'); return m ? m.content : ''; };
  var csrfName = function () { var m = $('meta[name="csrf-name"]'); return m ? m.content : 'csrf_test_name'; };

  function toast(msg) {
    var t = document.createElement('div'); t.className = 'toast'; t.setAttribute('role', 'status'); t.textContent = msg;
    document.body.appendChild(t); setTimeout(function () { t.remove(); }, 4200);
  }
  window.crmToast = toast;

  // sidebar + user menu
  var burger = $('.burger'), side = $('.side');
  if (burger && side) burger.addEventListener('click', function () { side.classList.toggle('open'); });
  var um = $('.umenu');
  if (um) {
    $('button', um).addEventListener('click', function (e) { e.stopPropagation(); um.classList.toggle('open'); });
    document.addEventListener('click', function () { um.classList.remove('open'); });
  }

  // flash auto-dismiss for success
  $$('.flash.ok').forEach(function (f) { setTimeout(function () { f.style.display = 'none'; }, 7000); });

  // confirmation dialogs
  document.addEventListener('submit', function (e) {
    var f = e.target, msg = f.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    // duplicate-submission protection
    if (f.getAttribute('data-once') !== null || f.method.toLowerCase() === 'post') {
      if (f.dataset.sent === '1') { e.preventDefault(); return; }
      f.dataset.sent = '1';
      $$('button[type=submit],input[type=submit]', f).forEach(function (b) { b.disabled = true; b.dataset.label = b.textContent; if (b.tagName === 'BUTTON') b.textContent = 'Please wait…'; });
      setTimeout(function () { f.dataset.sent = '0'; $$('button[type=submit]', f).forEach(function (b) { b.disabled = false; if (b.dataset.label) b.textContent = b.dataset.label; }); }, 8000);
    }
  }, true);

  // client-side validation with inline messages (server validates again)
  $$('form[data-validate]').forEach(function (f) {
    f.setAttribute('novalidate', 'novalidate');
    f.addEventListener('submit', function (e) {
      var bad = false;
      $$('.field-error.js', f).forEach(function (n) { n.remove(); });
      $$('[required],[pattern],[type=email]', f).forEach(function (el) {
        el.classList.remove('bad');
        if (el.disabled || !el.checkValidity()) {
          bad = true; el.classList.add('bad');
          var d = document.createElement('div'); d.className = 'field-error js'; d.setAttribute('role', 'alert');
          d.textContent = el.dataset.msg || el.validationMessage || 'Please check this field.';
          el.parentNode.appendChild(d);
        }
      });
      if (bad) { e.preventDefault(); e.stopImmediatePropagation(); f.dataset.sent = '0'; var first = $('.bad', f); if (first) first.focus(); }
    }, true);
  });

  // modals
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-modal]');
    if (o) {
      e.preventDefault();
      var m = document.getElementById(o.getAttribute('data-modal'));
      if (m) {
        m.classList.add('open');
        var i = $('input:not([type=hidden]),select,textarea', m);
        if (i) i.focus();
        (o.getAttribute('data-fill') || '').split(',').forEach(function (kv) {
          var p = kv.split('=');
          if (p[0]) {
            var t = $('[name="' + p[0] + '"]', m);
            if (t) t.value = decodeURIComponent(p[1] || '');
          }
        });
        var a = o.getAttribute('data-action');
        if (a) {
          var fm = $('form', m);
          if (fm) fm.action = a;
        }
      }
      return;
    }
    var closeBtn = e.target.closest('[data-close], .modal-close, [data-modal-close], .btn-close, [data-dismiss="modal"], .close, [aria-label="Close"], [data-cancel]');
    if (closeBtn) {
      e.preventDefault();
      var mm = closeBtn.closest('.modal');
      if (mm) mm.classList.remove('open');
      return;
    }
    if (e.target.classList.contains('modal')) {
      e.target.classList.remove('open');
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') $$('.modal.open').forEach(function (m) { m.classList.remove('open'); });
  });

  // searchable select: <select data-search>
  $$('select[data-search]').forEach(function (s) {
    var box = document.createElement('input'); box.type = 'search'; box.placeholder = 'Type to filter…'; box.setAttribute('aria-label', 'Filter options'); box.style.marginBottom = '4px';
    s.parentNode.insertBefore(box, s);
    box.addEventListener('input', function () { var q = box.value.toLowerCase(); $$('option', s).forEach(function (o) { o.hidden = q && o.text.toLowerCase().indexOf(q) < 0 && !o.selected; }); });
  });

  // bar charts from <script type="application/json" data-chart="bar|line">
  $$('script[data-chart]').forEach(function (sc) {
    var cfg; try { cfg = JSON.parse(sc.textContent); } catch (x) { return; }
    var host = document.createElement('div'); sc.parentNode.insertBefore(host, sc.nextSibling);
    if (!cfg.labels || !cfg.labels.length) { host.className = 'empty'; host.textContent = 'No data for this period.'; return; }
    var type = sc.getAttribute('data-chart'), W = 640, H = 220, pad = 34, max = Math.max.apply(null, cfg.values.concat([1]));
    var ns = 'http://www.w3.org/2000/svg', svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H); svg.setAttribute('class', 'chart'); svg.setAttribute('role', 'img'); svg.setAttribute('aria-label', cfg.title || 'chart');
    function el(n, a, t) { var e = document.createElementNS(ns, n); for (var k in a) e.setAttribute(k, a[k]); if (t != null) e.textContent = t; svg.appendChild(e); return e; }
    for (var g = 0; g <= 4; g++) { var y = H - pad - (H - pad * 1.5) * g / 4; el('line', { x1: pad, x2: W - 8, y1: y, y2: y, stroke: '#e6ecf3' }); el('text', { x: 4, y: y + 4, 'font-size': 10, fill: '#5b6b82' }, short(max * g / 4)); }
    var step = (W - pad - 8) / cfg.labels.length, pts = [];
    cfg.labels.forEach(function (l, i) {
      var v = cfg.values[i], h = (H - pad * 1.5) * v / max, x = pad + step * i + step * .18, w = step * .64, y = H - pad - h;
      if (type === 'bar') { var r = el('rect', { x: x, y: y, width: w, height: Math.max(h, 0), rx: 3, fill: '#0e9aa7' }); var t = document.createElementNS(ns, 'title'); t.textContent = l + ': ' + (cfg.fmt === 'money' ? '₹' + Math.round(v).toLocaleString('en-IN') : v); r.appendChild(t); }
      else pts.push([x + w / 2, y]);
      el('text', { x: x + w / 2, y: H - 14, 'font-size': 10, fill: '#5b6b82', 'text-anchor': 'middle' }, l.length > 9 ? l.slice(0, 8) + '…' : l);
    });
    if (type === 'line' && pts.length) { el('polyline', { points: pts.map(function (p) { return p.join(','); }).join(' '), fill: 'none', stroke: '#0e9aa7', 'stroke-width': 2.5 }); pts.forEach(function (p) { el('circle', { cx: p[0], cy: p[1], r: 3.5, fill: '#0f2b4c' }); }); }
    host.appendChild(svg);
    function short(n) { return n >= 1e7 ? (n / 1e7).toFixed(1) + 'Cr' : n >= 1e5 ? (n / 1e5).toFixed(1) + 'L' : n >= 1e3 ? (n / 1e3).toFixed(0) + 'k' : Math.round(n); }
  });

  // kanban drag & drop: posts through a real form so CSRF + server validation stay authoritative
  var dragged = null;
  $$('.kcard[draggable]').forEach(function (c) {
    c.addEventListener('dragstart', function (e) { dragged = c; e.dataTransfer.setData('text/plain', c.dataset.id); });
  });
  $$('.kcol').forEach(function (col) {
    col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('over'); });
    col.addEventListener('dragleave', function () { col.classList.remove('over'); });
    col.addEventListener('drop', function (e) {
      e.preventDefault(); col.classList.remove('over');
      if (!dragged) return; var to = col.dataset.status, from = dragged.dataset.status;
      if (to === from) return;
      var allowed = (dragged.dataset.next || '').split(',');
      if (allowed.indexOf(to) < 0) { toast('That status change is not allowed from “' + from.replace(/_/g, ' ') + '”.'); return; }
      var reason = '';
      if (to === 'lost' || to === 'on_hold') { reason = window.prompt('Reason (required):') || ''; if (!reason.trim()) return; }
      var f = document.createElement('form'); f.method = 'post'; f.action = dragged.dataset.url;
      [[csrfName(), csrf()], ['to', to], ['reason', reason]].forEach(function (kv) { var i = document.createElement('input'); i.type = 'hidden'; i.name = kv[0]; i.value = kv[1]; f.appendChild(i); });
      document.body.appendChild(f); f.submit();
    });
  });

  // quotation line editor (estimates only)
  var lines = $('#lines');
  if (lines) {
    var tpl = $('#line-tpl'), body = $('tbody', lines);
    function num(v) { v = parseFloat(String(v).replace(/,/g, '')); return isNaN(v) ? 0 : v; }
    function r2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }
    function inr(n) { return '₹' + r2(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function recalc() {
      var sub = 0, disc = 0, taxable = 0, tax = 0, chg = 0;
      $$('tr.ln', body).forEach(function (tr) {
        var q = num($('[data-f=qty]', tr).value), p = num($('[data-f=price]', tr).value), d = num($('[data-f=disc]', tr).value), t = num($('[data-f=tax]', tr).value);
        var s = r2(q * p), dd = r2(s * d / 100), tx = r2(s - dd), ta = r2(tx * t / 100);
        $('[data-f=total]', tr).textContent = inr(tx + ta); sub += s; disc += dd; taxable += tx; tax += ta;
      });
      $$('.chg', lines.parentNode).forEach(function (r) { var a = num($('input[type=number]', r).value); chg += a; tax += r2(a * num($('input[type=number]', r).dataset.tax || 0) / 100); });
      var gross = taxable + chg + tax, grand = Math.floor(gross + .5);
      var set = function (id, v) { var n = document.getElementById(id); if (n) n.textContent = inr(v); };
      set('t-sub', sub); set('t-disc', -disc); set('t-taxable', taxable); set('t-chg', chg); set('t-tax', tax); set('t-grand', grand);
    }
    function wire(tr) {
      $$('input,select', tr).forEach(function (i) { i.addEventListener('input', recalc); });
      var sel = $('select[data-f=product]', tr);
      if (sel) sel.addEventListener('change', function () {
        var o = sel.options[sel.selectedIndex]; if (!o || !o.dataset.price) return;
        $('[data-f=price]', tr).value = o.dataset.price; $('[data-f=tax]', tr).value = o.dataset.tax; $('[data-f=unit]', tr).value = o.dataset.unit;
        var d = $('[data-f=desc]', tr); if (!d.value) d.value = o.dataset.name; $('[data-f=min]', tr).textContent = o.dataset.min ? 'min ' + inr(num(o.dataset.min)) : ''; recalc();
      });
      var rm = $('[data-rm]', tr); if (rm) rm.addEventListener('click', function () { if ($$('tr.ln', body).length > 1) { tr.remove(); renumber(); recalc(); } });
    }
    function renumber() { $$('tr.ln', body).forEach(function (tr, i) { $$('[name]', tr).forEach(function (n) { n.name = n.name.replace(/lines\[\d+\]/, 'lines[' + i + ']'); }); }); }
    function add() { var tr = tpl.content.firstElementChild.cloneNode(true); body.appendChild(tr); renumber(); wire(tr); recalc(); }
    $$('tr.ln', body).forEach(wire);
    var addBtn = $('#add-line'); if (addBtn) addBtn.addEventListener('click', add);
    $$('.chg input[type=number]').forEach(function (i) { i.addEventListener('input', recalc); });
    if (!$$('tr.ln', body).length) add(); else recalc();
  }

  // notification count polling (cheap JSON, only when tab visible)
  var bell = $('.bell .dot');
  if (bell && 'fetch' in window) {
    setInterval(function () {
      if (document.hidden) return;
      fetch(window.CRM_BASE + 'crm/notifications/count', { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; }).then(function (j) { if (j && typeof j.count === 'number') { bell.textContent = j.count > 99 ? '99+' : j.count; bell.style.display = j.count ? '' : 'none'; } }).catch(function () {});
    }, 60000);
  }

  // --------------------------------------------------------------------------
  // Global Command Launcher (Ctrl + K / Cmd + K)
  // --------------------------------------------------------------------------
  var cmdModal = $('#m-cmd-k');
  var cmdInput = $('#cmd-search-input');
  var cmdResults = $('#cmd-results-list');
  var cmdTrigger = $('#cmd-k-trigger');

  function openCommandLauncher() {
    if (!cmdModal) return;
    cmdModal.classList.add('open');
    if (cmdInput) {
      cmdInput.value = '';
      cmdInput.focus();
    }
  }

  function closeCommandLauncher() {
    if (cmdModal) cmdModal.classList.remove('open');
  }

  if (cmdTrigger) {
    cmdTrigger.addEventListener('click', openCommandLauncher);
  }

  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      if (cmdModal && cmdModal.classList.contains('open')) {
        closeCommandLauncher();
      } else {
        openCommandLauncher();
      }
    }
  });

  var debounceTimer = null;
  if (cmdInput && cmdResults) {
    cmdInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      var q = cmdInput.value.trim();
      if (!q) {
        cmdResults.innerHTML = '<div class="cmd-hint">Type at least 1 character to search across your authorized records...</div>';
        return;
      }
      debounceTimer = setTimeout(function () {
        fetch(window.CRM_BASE + 'crm/search?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var items = data.results || [];
            if (!items.length) {
              cmdResults.innerHTML = '<div class="cmd-hint">No records or actions found matching “' + q + '”.</div>';
              return;
            }
            var html = '';
            items.forEach(function (it) {
              html += '<a class="cmd-item" href="' + it.url + '">' +
                '<div>' +
                  '<div class="cmd-item-title">' + it.title + '</div>' +
                  (it.sub ? '<div class="cmd-item-sub">' + it.sub + '</div>' : '') +
                '</div>' +
                '<span class="cmd-item-cat">' + it.category + '</span>' +
              '</a>';
            });
            cmdResults.innerHTML = html;
          }).catch(function () {});
      }, 150);
    });
  }

  // --------------------------------------------------------------------------
  // Interactive Product Tour Engine
  // --------------------------------------------------------------------------
  var activeTour = null;
  var currentStepIdx = 0;
  var tourBackdrop = $('#tour-backdrop');
  var tourSpotlight = $('#tour-spotlight');
  var tourPopover = $('#tour-popover');
  var tourStepInd = $('#tour-step-indicator');
  var tourTitle = $('#tour-title');
  var tourContent = $('#tour-content');
  var tourPrev = $('#tour-prev-btn');
  var tourNext = $('#tour-next-btn');
  var tourSkip = $('#tour-skip-btn');
  var tourExit = $('#tour-exit-btn');
  var tourModuleRow = $('#tour-action-row');
  var tourModuleLink = $('#tour-module-link');
  var tourStartBtn = $('#btn-start-tour');
  var modalRestartBtn = $('#modal-restart-tour');

  function renderTourStep(idx) {
    if (!activeTour || !activeTour.steps || !activeTour.steps[idx]) {
      endTour(true);
      return;
    }
    currentStepIdx = idx;
    var step = activeTour.steps[idx];

    // Find target element with fallback selectors
    var targetEl = null;
    var selectors = (step.target || '').split(',').map(function (s) { return s.trim(); });
    for (var i = 0; i < selectors.length; i++) {
      if (selectors[i]) {
        try { targetEl = $(selectors[i]); } catch (ex) {}
        if (targetEl) break;
      }
    }

    tourStepInd.textContent = 'Step ' + (idx + 1) + ' of ' + activeTour.steps.length;
    tourTitle.textContent = step.title;
    tourContent.textContent = step.content;
    tourPrev.style.display = idx > 0 ? '' : 'none';
    tourNext.textContent = (idx === activeTour.steps.length - 1) ? 'Finish Tour ✓' : 'Next →';

    // Module direct link action
    if (tourModuleRow && tourModuleLink) {
      if (step.url) {
        tourModuleLink.href = window.CRM_BASE + step.url.replace(/^\/+/, '');
        var cleanTitle = step.title.replace(/^\d+\.\s*/, '');
        tourModuleLink.textContent = 'Open ' + cleanTitle + ' →';
        tourModuleRow.style.display = 'block';
      } else {
        tourModuleRow.style.display = 'none';
      }
    }

    tourBackdrop.style.display = 'block';
    tourPopover.style.display = 'block';

    var popWidth = Math.min(380, window.innerWidth - 32);
    var popHeight = tourPopover.offsetHeight || 260;

    // Scroll element into view if present
    if (targetEl) {
      var side = targetEl.closest('.side');
      if (side && window.innerWidth <= 860 && !side.classList.contains('open')) {
        side.classList.add('open');
      }
      try {
        targetEl.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
      } catch (ex) {}
    }

    var rect = targetEl && targetEl.getBoundingClientRect ? targetEl.getBoundingClientRect() : null;
    var isVisible = rect && (rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < window.innerHeight);

    if (isVisible) {
      var pad = 6;
      var spotTop = Math.max(0, rect.top - pad);
      var spotLeft = Math.max(0, rect.left - pad);
      var spotWidth = Math.min(window.innerWidth - spotLeft, rect.width + pad * 2);
      var spotHeight = Math.min(window.innerHeight - spotTop, rect.height + pad * 2);

      tourSpotlight.style.display = 'block';
      tourSpotlight.style.top = spotTop + 'px';
      tourSpotlight.style.left = spotLeft + 'px';
      tourSpotlight.style.width = spotWidth + 'px';
      tourSpotlight.style.height = spotHeight + 'px';

      var popTop = 0;
      var popLeft = 0;

      if (rect.right < 380 && window.innerWidth >= 768 && (rect.right + 20 + popWidth <= window.innerWidth)) {
        // Sidebar item: place immediately to the right of the spotlight
        popLeft = rect.right + 20;
        popTop = rect.top - 8;
      } else if (rect.bottom + popHeight + 20 <= window.innerHeight) {
        // Room below target
        popTop = rect.bottom + 14;
        popLeft = Math.max(16, rect.left);
      } else if (rect.top - popHeight - 20 >= 0) {
        // Room above target
        popTop = rect.top - popHeight - 14;
        popLeft = Math.max(16, rect.left);
      } else {
        // Center fallback
        popTop = Math.max(16, (window.innerHeight - popHeight) / 2);
        popLeft = Math.max(16, (window.innerWidth - popWidth) / 2);
      }

      // STRICT CLAMP to guarantee 100% viewport visibility on all screens
      popTop = Math.max(16, Math.min(window.innerHeight - popHeight - 16, popTop));
      popLeft = Math.max(16, Math.min(window.innerWidth - popWidth - 16, popLeft));

      tourPopover.style.top = popTop + 'px';
      tourPopover.style.left = popLeft + 'px';
      tourPopover.style.width = popWidth + 'px';
    } else {
      // Centered fallback if element not on page or hidden
      tourSpotlight.style.display = 'none';
      var popTop = Math.max(20, (window.innerHeight - popHeight) / 2);
      var popLeft = Math.max(16, (window.innerWidth - popWidth) / 2);
      tourPopover.style.top = popTop + 'px';
      tourPopover.style.left = popLeft + 'px';
      tourPopover.style.width = popWidth + 'px';
    }

    // Persist step
    var fd = new FormData();
    fd.append('tour_key', activeTour.key);
    fd.append('step', idx);
    fd.append(csrfName(), csrf());
    fetch(window.CRM_BASE + 'crm/tour/update', { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
  }

  function endTour(completed, skipped) {
    tourBackdrop.style.display = 'none';
    if (!activeTour) return;
    var fd = new FormData();
    fd.append('tour_key', activeTour.key);
    fd.append('step', currentStepIdx);
    if (completed) fd.append('completed', '1');
    if (skipped) fd.append('skipped', '1');
    fd.append(csrfName(), csrf());
    fetch(window.CRM_BASE + 'crm/tour/update', { method: 'POST', body: fd, credentials: 'same-origin' }).catch(function () {});
  }

  if (tourNext) {
    tourNext.addEventListener('click', function () {
      if (currentStepIdx < activeTour.steps.length - 1) {
        renderTourStep(currentStepIdx + 1);
      } else {
        endTour(true);
      }
    });
  }

  if (tourPrev) {
    tourPrev.addEventListener('click', function () {
      if (currentStepIdx > 0) renderTourStep(currentStepIdx - 1);
    });
  }

  if (tourSkip) {
    tourSkip.addEventListener('click', function () {
      endTour(false, true);
    });
  }

  if (tourExit) {
    tourExit.addEventListener('click', function () {
      endTour(false, true);
    });
  }

  // Clicking backdrop outside popover dismisses tour safely (never traps user)
  if (tourBackdrop) {
    tourBackdrop.addEventListener('click', function (e) {
      if (e.target === tourBackdrop) {
        endTour(false, true);
      }
    });
  }

  // Escape key always dismisses active tour
  window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && tourBackdrop && tourBackdrop.style.display !== 'none') {
      endTour(false, true);
    }
  });

  function startUserTour(force) {
    fetch(window.CRM_BASE + 'crm/tour/get', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (tour) {
        if (!tour || !tour.steps || !tour.steps.length) return;
        activeTour = tour;
        var params = new URLSearchParams(window.location.search);
        var urlStep = params.get('tour_step');
        if (urlStep !== null && !isNaN(parseInt(urlStep, 10))) {
          renderTourStep(parseInt(urlStep, 10));
          return;
        }
        if (force) {
          renderTourStep(0);
        } else if (!tour.is_completed && !tour.is_skipped) {
          var s = parseInt(tour.last_step, 10);
          renderTourStep((isNaN(s) || s < 0 || s >= tour.steps.length) ? 0 : s);
        }
      }).catch(function () {});
  }

  if (tourStartBtn) tourStartBtn.addEventListener('click', function () { startUserTour(true); });
  if (modalRestartBtn) modalRestartBtn.addEventListener('click', function () {
    var m = $('#m-support-help'); if (m) m.classList.remove('open');
    startUserTour(true);
  });

  // Auto trigger tour on first visit if not completed
  setTimeout(function () { startUserTour(false); }, 1000);

  // --------------------------------------------------------------------------
  // Service Worker Registration for PWA Standalone Operation
  // --------------------------------------------------------------------------
  if ('serviceWorker' in navigator && (location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1')) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }
})();
