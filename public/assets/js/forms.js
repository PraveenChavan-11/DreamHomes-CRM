/*!
 * TalentHub form enhancement. Progressive: every form still posts and validates on the server without this file.
 *  - associates labels with controls when markup lacks it (accessible names)
 *  - inline, field-level validation messages linked via aria-describedby / aria-invalid
 *  - focuses the first invalid field on submit
 *  - loading + anti-double-submit state for forms that post normally (AJAX forms are handled by app.js)
 *  - window.thFormErrors(form, {field: message}) lets app.js show server-side field errors inline
 * Success is never shown here: only app.js / the server decide that, after the request has been persisted.
 */
(function () {
  'use strict';
  var d = document;
  var uid = 0;
  var SKIP = 'input[type=hidden],input[type=submit],input[type=button],input[type=file],button,[name=website_url],[name=cf-turnstile-response],[tabindex="-1"]';

  function controls(form) {
    return Array.prototype.slice.call(form.querySelectorAll('input,select,textarea')).filter(function (el) { return !el.matches(SKIP); });
  }

  function ensureId(el) {
    if (!el.id) { el.id = 'f' + (++uid) + '-' + (el.name || 'field').replace(/[^a-z0-9_-]/gi, ''); }
    return el.id;
  }

  function labelFor(el) {
    if (el.id) { var l = d.querySelector('label[for="' + el.id + '"]'); if (l) { return l; } }
    var p = el.closest('label'); if (p) { return p; }
    var wrap = el.parentElement;
    for (var i = 0; i < 3 && wrap; i++, wrap = wrap.parentElement) {
      var c = wrap.querySelector(':scope > label'); if (c && !c.htmlFor) { return c; }
    }
    return null;
  }

  function humanName(el) {
    var l = labelFor(el);
    var t = l ? l.textContent : (el.getAttribute('aria-label') || el.placeholder || el.name || 'this field');
    return t.replace(/[*]/g, '').replace(/\(.*?\)/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
  }

  function message(el) {
    var v = el.validity, n = humanName(el);
    if (el.type === 'checkbox' && v.valueMissing) { return 'Please tick this box to continue.'; }
    if (v.valueMissing) { return el.tagName === 'SELECT' ? 'Please choose an option for ' + n + '.' : 'Please enter ' + (n.charAt(0) === 'e' || n.charAt(0) === 'a' ? 'an ' : 'a ') + n + '.'; }
    if (v.typeMismatch) { return el.type === 'email' ? 'Enter a valid email address, for example name@company.com.' : 'Enter a valid ' + el.type + '.'; }
    if (v.patternMismatch) { return el.title || 'Please match the requested format.'; }
    if (v.tooShort) { return 'Please enter at least ' + el.minLength + ' characters (currently ' + el.value.length + ').'; }
    if (v.tooLong) { return 'Please keep this to ' + el.maxLength + ' characters or fewer.'; }
    if (v.rangeUnderflow || v.rangeOverflow) { return 'Please enter a value within the allowed range.'; }
    return el.validationMessage || 'Please check this field.';
  }

  function errEl(el) {
    var id = ensureId(el) + '-err';
    var e = d.getElementById(id);
    if (!e) {
      e = d.createElement('p');
      e.id = id;
      e.className = 'field-error';
      e.setAttribute('role', 'alert');
      var host = el.type === 'checkbox' ? (el.closest('div') || el.parentElement) : el;
      host.insertAdjacentElement('afterend', e);
    }
    return e;
  }

  function setError(el, msg) {
    var e = errEl(el);
    e.textContent = msg;
    e.classList.add('is-visible');
    el.setAttribute('aria-invalid', 'true');
    var ids = (el.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
    if (ids.indexOf(e.id) < 0) { ids.push(e.id); el.setAttribute('aria-describedby', ids.join(' ')); }
  }

  function clearError(el) {
    el.removeAttribute('aria-invalid');
    var e = d.getElementById((el.id || '') + '-err');
    if (e) { e.textContent = ''; e.classList.remove('is-visible'); }
  }

  function check(el) {
    if (el.checkValidity()) { clearError(el); return true; }
    setError(el, message(el));
    return false;
  }

  function enhance(form) {
    if (form.dataset.thForm === '1' || form.matches('[data-no-enhance]')) { return; }
    form.dataset.thForm = '1';
    form.setAttribute('novalidate', '');
    var fields = controls(form);
    fields.forEach(function (el) {
      var l = labelFor(el);
      if (l && !l.htmlFor && !el.closest('label')) { l.htmlFor = ensureId(el); }
      if (el.required) { el.setAttribute('aria-required', 'true'); }
      var touched = false;
      el.addEventListener('blur', function () { touched = true; if (el.value !== '' || el.required) { check(el); } });
      el.addEventListener('input', function () { if (touched || el.getAttribute('aria-invalid') === 'true') { check(el); } });
      el.addEventListener('change', function () { if (touched || el.getAttribute('aria-invalid') === 'true') { check(el); } });
    });
  }

  function init() {
    Array.prototype.slice.call(d.querySelectorAll('main form, footer form')).forEach(function (f) {
      if (f.matches('[data-newsletter]')) { return; }
      enhance(f);
    });
  }

  // Validate before any other submit handler (app.js AJAX handler included) gets to send the request.
  d.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.dataset.thForm !== '1') { return; }
    var bad = null;
    controls(form).forEach(function (el) { if (!check(el) && !bad) { bad = el; } });
    if (bad) {
      e.preventDefault();
      e.stopImmediatePropagation();
      bad.focus({ preventScroll: false });
      return;
    }
    if (!form.matches('[data-ajax="true"]')) {
      if (form.dataset.busy === '1') { e.preventDefault(); return; }
      form.dataset.busy = '1';
      var btn = form.querySelector('button[type=submit],input[type=submit]');
      if (btn) {
        btn.setAttribute('aria-busy', 'true');
        if (btn.tagName === 'BUTTON') { btn.dataset.label = btn.innerHTML; btn.innerHTML = '<span class="btn-spinner" aria-hidden="true"></span><span>Sending…</span>'; }
      }
    }
  }, true);

  // Restore if the user returns via the back button (bfcache).
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) { return; }
    Array.prototype.slice.call(d.querySelectorAll('form[data-busy="1"]')).forEach(function (f) {
      f.dataset.busy = '0';
      var b = f.querySelector('[aria-busy="true"]');
      if (b) { b.removeAttribute('aria-busy'); if (b.dataset.label) { b.innerHTML = b.dataset.label; } }
    });
  });

  window.thFormErrors = function (form, errors) {
    if (!form || !errors || typeof errors !== 'object') { return false; }
    var first = null;
    Object.keys(errors).forEach(function (name) {
      var el = form.querySelector('[name="' + name + '"]');
      if (el && !el.matches(SKIP)) { setError(el, String(errors[name])); if (!first) { first = el; } }
    });
    if (first) { first.focus(); }
    return !!first;
  };

  if (d.readyState === 'loading') { d.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
