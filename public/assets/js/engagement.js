/**
 * TalentHub Consultant — Master Engagement & Campaign Engine
 * Pure Vanilla JavaScript — High-Performance, Privacy-Compliant, GA4 Connected
 * Features: Smart Announcements, Modal Stacking Prevention, Frequency Controls, Double Opt-In Forms, Cookie Consent Manager
 */

(function () {
  'use strict';

  // ── 1. COOKIE CONSENT MANAGER ────────────────────────────────────────────────
  const COOKIE_STORAGE_KEY = 'th_cookie_consent_v1';

  function getCookieConsent() {
    try {
      const stored = localStorage.getItem(COOKIE_STORAGE_KEY);
      return stored ? JSON.parse(stored) : null;
    } catch (e) {
      return null;
    }
  }

  function saveCookieConsent(consent) {
    try {
      localStorage.setItem(COOKIE_STORAGE_KEY, JSON.stringify(consent));

      // Asynchronously log anonymized consent state to server
      fetch('/api/v1/cookies/consent', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          consent_uuid: consent.uuid || generateUuid(),
          analytics: Boolean(consent.analytics),
          marketing: Boolean(consent.marketing),
        }),
      }).catch(() => {});
    } catch (e) {
      console.debug('Failed to save cookie consent:', e);
    }
  }

  function generateUuid() {
    return 'th_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
  }

  function initCookieBanner() {
    const consent = getCookieConsent();
    if (consent) {
      applyConsent(consent);
      return;
    }

    // Build modern floating banner - high contrast solid opaque card, maximum z-index, no collisions
    const banner = document.createElement('div');
    banner.id = 'th-cookie-banner';
    banner.style.cssText = 'position: fixed !important; bottom: 20px !important; left: 20px !important; max-width: 440px !important; width: calc(100% - 40px) !important; background-color: #072412 !important; color: #ffffff !important; padding: 20px !important; border-radius: 16px !important; box-shadow: 0 25px 60px rgba(0,0,0,0.85) !important; border: 1.5px solid rgba(55,206,151,0.5) !important; z-index: 9999999 !important; font-family: inherit !important; display: block !important; box-sizing: border-box !important;';
    banner.innerHTML = `
      <div style="display: flex !important; align-items: flex-start !important; gap: 12px !important; margin-bottom: 14px !important;">
        <span style="width: 32px !important; height: 32px !important; border-radius: 10px !important; background-color: rgba(29,106,52,0.4) !important; color: #37CE97 !important; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; margin-top: 2px !important; border: 1px solid rgba(55,206,151,0.2) !important;">
          <svg style="width: 16px !important; height: 16px !important;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </span>
        <div>
          <h4 style="font-size: 13px !important; font-weight: 800 !important; color: #ffffff !important; letter-spacing: 0.02em !important; margin: 0 !important;">Privacy &amp; Cookie Preferences</h4>
          <p style="font-size: 11px !important; color: rgba(255,255,255,0.8) !important; line-height: 1.5 !important; margin: 4px 0 0 0 !important;">
            We use necessary cookies to operate TalentHub and optional analytics to measure recruitment traffic. No marketing tracking runs without consent.
          </p>
        </div>
      </div>
      <div style="display: flex !important; align-items: center !important; gap: 10px !important; padding-top: 4px !important;">
        <button id="th-cookie-accept-all" style="flex: 1 !important; padding: 10px 16px !important; border-radius: 10px !important; background-color: #37CE97 !important; color: #072412 !important; font-size: 12px !important; font-weight: 800 !important; border: none !important; cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 12px rgba(55,206,151,0.3) !important;">
          Accept All
        </button>
        <button id="th-cookie-reject-opt" style="flex: 1 !important; padding: 10px 16px !important; border-radius: 10px !important; background-color: rgba(255,255,255,0.12) !important; color: #ffffff !important; font-size: 12px !important; font-weight: 600 !important; border: 1px solid rgba(255,255,255,0.25) !important; cursor: pointer !important; text-align: center !important;">
          Essential Only
        </button>
      </div>
    `;

    document.body.appendChild(banner);

    document.getElementById('th-cookie-accept-all')?.addEventListener('click', () => {
      const c = { uuid: generateUuid(), necessary: true, analytics: true, marketing: true, timestamp: Date.now() };
      saveCookieConsent(c);
      applyConsent(c);
      banner.remove();
    });

    document.getElementById('th-cookie-reject-opt')?.addEventListener('click', () => {
      const c = { uuid: generateUuid(), necessary: true, analytics: false, marketing: false, timestamp: Date.now() };
      saveCookieConsent(c);
      applyConsent(c);
      banner.remove();
    });
  }

  function applyConsent(consent) {
    if (consent.analytics && typeof window.gtag === 'function') {
      window.gtag('consent', 'update', {
        'analytics_storage': 'granted',
      });
    }
  }

  window.openCookiePreferences = function () {
    localStorage.removeItem(COOKIE_STORAGE_KEY);
    initCookieBanner();
  };

  // ── 2. SMART ANNOUNCEMENT & POPUP MANAGER ─────────────────────────────────────
  async function loadAnnouncements() {
    try {
      const path = window.location.pathname;
      const isReturning = Boolean(localStorage.getItem('th_returning_visitor'));
      localStorage.setItem('th_returning_visitor', 'true');

      let audience = 'everyone';
      if (path.includes('/jobs') || path.includes('/candidates') || path.includes('/career-enquiry')) {
        audience = 'candidates';
      } else if (path.includes('/employers') || path.includes('/solutions')) {
        audience = 'employers';
      }

      const url = `/api/v1/announcements/active?audience=${encodeURIComponent(audience)}&page=${encodeURIComponent(path)}&returning=${isReturning ? '1' : '0'}`;
      const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
      });
      if (!res.ok) return;

      const data = await res.json();
      if (!data.success || !Array.isArray(data.announcements)) return;

      data.announcements.forEach((item) => {
        if (checkFrequencyEligible(item)) {
          renderAnnouncement(item);
        }
      });
    } catch (err) {
      console.debug('Announcement engine notice:', err);
    }
  }

  function checkFrequencyEligible(item) {
    const id = item.id;
    const freq = item.frequency || 'once_per_session';

    if (freq === 'every_visit') return true;

    if (freq === 'once_per_session') {
      return !sessionStorage.getItem('th_ann_' + id + '_dismissed');
    }

    if (freq === 'until_dismissed') {
      return !localStorage.getItem('th_ann_' + id + '_dismissed');
    }

    const lastShown = parseInt(localStorage.getItem('th_ann_' + id + '_last_shown') || '0', 10);
    const now = Date.now();

    if (freq === 'once_per_day') {
      return (now - lastShown) > 86400000;
    }
    if (freq === 'once_7_days') {
      return (now - lastShown) > (7 * 86400000);
    }
    if (freq === 'once_30_days') {
      return (now - lastShown) > (30 * 86400000);
    }

    return true;
  }

  function markDismissed(item) {
    const id = item.id;
    const freq = item.frequency || 'once_per_session';
    const now = Date.now();

    localStorage.setItem('th_ann_' + id + '_last_shown', now.toString());
    sessionStorage.setItem('th_ann_' + id + '_dismissed', 'true');

    if (freq === 'until_dismissed') {
      localStorage.setItem('th_ann_' + id + '_dismissed', 'true');
    }

    trackCampaignEvent(id, 'dismissal');
  }

  function trackCampaignEvent(id, type) {
    try {
      fetch('/api/v1/analytics/campaign-event', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ announcement_id: id, event_type: type }),
      }).catch(() => {});

      // GA4 Dispatch without PII
      if (typeof window.gtag === 'function') {
        window.gtag('event', 'campaign_' + type, {
          event_category: 'engagement',
          event_label: 'announcement_' + id,
        });
      }
    } catch (e) {}
  }

  let hasRenderedModalAnnouncement = false;

  function renderAnnouncement(item) {
    const format = item.display_format;

    if (format === 'center_modal' || format === 'newsletter_popup') {
      if (hasRenderedModalAnnouncement || document.getElementById('th-active-modal')) return; // Stacking prevention
      hasRenderedModalAnnouncement = true;
      renderCenterModal(item);
    } else if (format === 'top_banner') {
      renderTopBanner(item);
    } else if (format === 'bottom_banner') {
      renderBottomBanner(item);
    }

    trackCampaignEvent(item.id, 'impression');
  }

  function renderCenterModal(item) {
    if (document.getElementById('th-active-modal')) return; // Stacking prevention

    const modal = document.createElement('div');
    modal.id = 'th-active-modal';
    modal.style.cssText = 'position: fixed !important; inset: 0 !important; width: 100vw !important; height: 100vh !important; background-color: rgba(0, 0, 0, 0.75) !important; z-index: 99999999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 16px !important; transition: opacity 0.3s ease !important;';
    modal.className = 'opacity-0';

    const isNewsletter = item.display_format === 'newsletter_popup';

    modal.innerHTML = `
      <div style="background-color: #FFFFFF !important; color: #0f172a !important; z-index: 100000000 !important; position: relative !important; max-width: 520px !important; width: 100% !important; border-radius: 24px !important; padding: 28px !important; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.85) !important; border: 1px solid #e2e8f0 !important; box-sizing: border-box !important;" class="transform scale-95 transition-transform duration-300">
        <button id="th-modal-close" class="absolute top-4 right-4 p-2 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition" aria-label="Close announcement">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div style="background-color: #EAF5EF !important; color: #1D6A34 !important;" class="inline-block px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-3">
          ${escapeHtml(item.announcement_type ? item.announcement_type.replace('_', ' ') : 'Special Update')}
        </div>

        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug mb-3">
          ${escapeHtml(item.public_heading)}
        </h3>

        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
          ${escapeHtml(item.public_content)}
        </p>

        ${isNewsletter ? `
          <form id="th-popup-newsletter-form" class="space-y-3">
            <input type="email" id="th-popup-email" required placeholder="Enter your work or personal email..." class="w-full text-xs rounded-xl border border-slate-300 p-3 text-slate-900 focus:outline-none focus:border-[#1D6A34]">
            <button type="submit" style="background-color: #1D6A34 !important; color: #FFFFFF !important;" class="w-full py-3 px-4 rounded-xl text-white text-xs font-bold transition shadow-md hover:opacity-95">
              Subscribe for Free Updates
            </button>
          </form>
        ` : `
          <div style="display: flex !important; align-items: center !important; gap: 12px !important; width: 100% !important;">
            ${item.cta_url ? `
              <a href="${escapeHtml(item.cta_url)}" id="th-modal-cta" style="flex: 1 !important; display: inline-block !important; text-align: center !important; padding: 13px 22px !important; border-radius: 14px !important; background-color: #1D6A34 !important; color: #FFFFFF !important; font-size: 13px !important; font-weight: 800 !important; text-decoration: none !important; border: none !important; box-shadow: 0 4px 14px rgba(29,106,52,0.35) !important; cursor: pointer !important;">
                ${escapeHtml((item.cta_text && item.cta_text.trim()) ? item.cta_text : 'Explore Mandates')}
              </a>
            ` : ''}
            <button id="th-modal-dismiss" style="padding: 13px 20px !important; border-radius: 14px !important; background-color: #f1f5f9 !important; color: #334155 !important; font-size: 13px !important; font-weight: 700 !important; border: 1.5px solid #cbd5e1 !important; cursor: pointer !important;">
              Dismiss
            </button>
          </div>
        `}
      </div>
    `;

    document.body.appendChild(modal);

    // Fade-in animation
    setTimeout(() => {
      modal.classList.remove('opacity-0');
      const inner = modal.querySelector('div');
      if (inner) inner.classList.remove('scale-95');
    }, 100);

    const closeHandler = () => {
      markDismissed(item);
      modal.classList.add('opacity-0');
      setTimeout(() => modal.remove(), 300);
    };

    document.getElementById('th-modal-close')?.addEventListener('click', closeHandler);
    document.getElementById('th-modal-dismiss')?.addEventListener('click', closeHandler);
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeHandler();
    });

    document.getElementById('th-modal-cta')?.addEventListener('click', () => {
      trackCampaignEvent(item.id, 'click');
      markDismissed(item);
    });

    const popupNlForm = document.getElementById('th-popup-newsletter-form');
    if (popupNlForm) {
      popupNlForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const emailInput = document.getElementById('th-popup-email');
        if (!emailInput) return;

        try {
          const res = await fetch('/api/v1/newsletter/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: emailInput.value, consent: true, source: 'modal_popup' }),
          });
          const json = await res.json();
          if (json.success) {
            trackCampaignEvent(item.id, 'conversion');
            if (typeof window.showToast === 'function') {
              window.showToast(json.message, 'success');
            }
            closeHandler();
          } else {
            alert(json.message || 'Subscription failed.');
          }
        } catch (err) {
          alert('Network error.');
        }
      });
    }
  }

  function renderTopBanner(item) {
    if (document.getElementById('th-top-banner')) return;

    const banner = document.createElement('div');
    banner.id = 'th-top-banner';
    banner.className = 'bg-brand-dark text-white text-xs px-4 py-2.5 border-b border-brand-accent/20 z-[990] flex items-center justify-between gap-3 shadow-sm';
    banner.innerHTML = `
      <div class="flex items-center gap-2 max-w-4xl mx-auto overflow-hidden">
        <span class="w-2 h-2 rounded-full bg-brand-accent animate-pulse shrink-0"></span>
        <span class="font-bold text-brand-accent truncate">${escapeHtml(item.public_heading)}:</span>
        <span class="text-slate-200 truncate hidden sm:inline">${escapeHtml(item.public_content)}</span>
        ${item.cta_url ? `
          <a href="${escapeHtml(item.cta_url)}" id="th-top-banner-cta" class="underline text-white font-bold hover:text-brand-accent shrink-0 ml-1">
            ${escapeHtml(item.cta_text || 'Details &rarr;')}
          </a>
        ` : ''}
      </div>
      <button id="th-top-banner-close" class="text-slate-400 hover:text-white p-1 rounded transition shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    `;

    document.body.insertBefore(banner, document.body.firstChild);

    document.getElementById('th-top-banner-close')?.addEventListener('click', () => {
      markDismissed(item);
      banner.remove();
    });

    document.getElementById('th-top-banner-cta')?.addEventListener('click', () => {
      trackCampaignEvent(item.id, 'click');
      markDismissed(item);
    });
  }

  function renderBottomBanner(item) {
    if (document.getElementById('th-bottom-banner')) return;

    const banner = document.createElement('div');
    banner.id = 'th-bottom-banner';
    banner.className = 'fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-200/80 p-3 sm:p-4 z-[9980] flex items-center justify-between gap-4 shadow-xl';
    banner.innerHTML = `
      <div class="page-width flex items-center justify-between gap-4 w-full">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-brand-light text-brand-primary flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
          </div>
          <div>
            <h4 class="text-xs font-bold text-slate-900">${escapeHtml(item.public_heading)}</h4>
            <p class="text-[11px] text-slate-500 hidden sm:block">${escapeHtml(item.public_content)}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          ${item.cta_url ? `
            <a href="${escapeHtml(item.cta_url)}" id="th-bottom-banner-cta" class="py-1.5 px-3 rounded-lg bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover shadow-sm transition">
              ${escapeHtml(item.cta_text || 'Explore')}
            </a>
          ` : ''}
          <button id="th-bottom-banner-close" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(banner);

    document.getElementById('th-bottom-banner-close')?.addEventListener('click', () => {
      markDismissed(item);
      banner.remove();
    });

    document.getElementById('th-bottom-banner-cta')?.addEventListener('click', () => {
      trackCampaignEvent(item.id, 'click');
      markDismissed(item);
    });
  }

  // ── 3. NEWSLETTER AJAX HANDLERS ─────────────────────────────────────────────
  function initNewsletterForms() {
    document.querySelectorAll('form[data-newsletter="true"]').forEach((form) => {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerText : 'Subscribe';

        try {
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerText = 'Subscribing...';
          }

          const formData = new FormData(form);
          const email = formData.get('email');
          const firstName = formData.get('first_name') || '';
          const consent = formData.get('consent') || '1';

          const res = await fetch('/api/v1/newsletter/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              email: email,
              first_name: firstName,
              consent: Boolean(consent),
              source: form.getAttribute('data-source') || 'web_form',
            }),
          });

          const json = await res.json();
          if (json.success) {
            if (typeof window.showToast === 'function') {
              window.showToast(json.message, 'success');
            } else {
              alert(json.message);
            }
            form.reset();

            // GA4 Dispatch
            if (typeof window.gtag === 'function') {
              window.gtag('event', 'newsletter_subscribe', {
                event_category: 'engagement',
                event_label: 'newsletter_double_optin',
              });
            }

            // Navigate to dedicated Thank You confirmation page
            setTimeout(() => {
              window.location.href = json.redirect_url || '/thank-you?type=newsletter';
            }, 600);
          } else {
            const err = json.message || 'Subscription failed. Please verify your email.';
            if (typeof window.showToast === 'function') {
              window.showToast(err, 'error');
            } else {
              alert(err);
            }
          }
        } catch (err) {
          if (typeof window.showToast === 'function') {
            window.showToast('Network connection error.', 'error');
          }
        } finally {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerText = originalText;
          }
        }
      });
    });
  }

  // ── 4. CANDIDATE JOB ALERT MODAL ─────────────────────────────────────────────
  window.openJobAlertModal = function (prefillCategory) {
    let modal = document.getElementById('th-job-alert-modal');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'th-job-alert-modal';
      modal.style.cssText = 'position: fixed !important; inset: 0 !important; width: 100vw !important; height: 100vh !important; background-color: rgba(0,0,0,0.75) !important; z-index: 99999999 !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 16px !important; box-sizing: border-box !important;';
      modal.innerHTML = `
        <div style="background-color: #FFFFFF !important; color: #0F172A !important; z-index: 100000000 !important; position: relative !important; max-width: 460px !important; width: 100% !important; border-radius: 24px !important; padding: 28px !important; box-shadow: 0 25px 60px -15px rgba(0,0,0,0.85) !important; border: 1px solid #E2E8F0 !important; box-sizing: border-box !important;">
          <button id="th-alert-modal-close" style="position: absolute !important; top: 16px !important; right: 16px !important; padding: 8px !important; border-radius: 9999px !important; background: #F1F5F9 !important; border: none !important; color: #64748B !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important;">
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
          <div style="width: 44px !important; height: 44px !important; border-radius: 14px !important; background-color: #EAF5EF !important; color: #1D6A34 !important; display: flex !important; align-items: center !important; justify-content: center !important; margin-bottom: 16px !important;">
            <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <h3 style="font-size: 20px !important; font-weight: 900 !important; color: #0F172A !important; margin: 0 0 6px 0 !important; letter-spacing: -0.02em !important;">Get Pune Job Alerts</h3>
          <p style="font-size: 12px !important; color: #64748B !important; margin: 0 0 18px 0 !important; line-height: 1.5 !important;">Receive instant or weekly notifications when matching IT, engineering, or leadership mandates open in Pune.</p>

          <form id="th-job-alert-form" style="display: flex !important; flex-direction: column !important; gap: 12px !important;">
            <div>
              <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Your Email Address <span style="color: #EF4444 !important;">*</span></label>
              <input type="email" id="th-alert-email" required placeholder="name@example.com" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 12px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
            </div>
            <div>
              <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Target Sector / Role</label>
              <input type="text" id="th-alert-category" placeholder="e.g. Java, Project Engineer, HR, Finance" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 12px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
            </div>
            <div style="display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 10px !important;">
              <div>
                <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Preferred Location</label>
                <select id="th-alert-location" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 8px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
                  <option value="Pune">All Pune Corridors</option>
                  <option value="Hinjawadi">Hinjawadi IT Park</option>
                  <option value="Chakan">Chakan Industrial</option>
                  <option value="Kharadi">Kharadi / Viman Nagar</option>
                  <option value="Katraj">Katraj / Swargate</option>
                </select>
              </div>
              <div>
                <label style="display: block !important; font-size: 11px !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 4px !important;">Frequency</label>
                <select id="th-alert-freq" style="width: 100% !important; font-size: 12px !important; border-radius: 12px !important; border: 1px solid #CBD5E1 !important; padding: 10px 8px !important; color: #0F172A !important; background: #FFFFFF !important; box-sizing: border-box !important;">
                  <option value="weekly">Weekly Summary</option>
                  <option value="daily">Daily Digest</option>
                  <option value="immediate">Immediate</option>
                </select>
              </div>
            </div>
            <button type="submit" id="th-alert-submit" 
                    style="width: 100% !important; display: block !important; padding: 13px 20px !important; border-radius: 14px !important; background-color: #1D6A34 !important; color: #FFFFFF !important; font-size: 13px !important; font-weight: 800 !important; border: none !important; cursor: pointer !important; text-align: center !important; box-shadow: 0 4px 14px rgba(29, 106, 52, 0.35) !important; margin-top: 14px !important; letter-spacing: 0.02em !important;">
              Activate Job Alerts &rarr;
            </button>
          </form>
        </div>
      `;
      document.body.appendChild(modal);

      document.getElementById('th-alert-modal-close')?.addEventListener('click', () => modal.remove());
      modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.remove();
      });

      document.getElementById('th-job-alert-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('th-alert-email').value;
        const category = document.getElementById('th-alert-category').value;
        const location = document.getElementById('th-alert-location').value;
        const freq = document.getElementById('th-alert-freq').value;
        const btn = document.getElementById('th-alert-submit');

        btn.disabled = true;
        btn.innerText = 'Activating...';
        btn.style.backgroundColor = '#155227';
        btn.style.color = '#FFFFFF';

        try {
          const res = await fetch('/api/v1/job-alerts/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              email: email,
              job_category: category,
              preferred_location: location,
              frequency: freq,
            }),
          });
          const data = await res.json();
          if (data.success) {
            if (typeof window.showToast === 'function') {
              window.showToast(data.message, 'success');
            }
            if (typeof window.gtag === 'function') {
              window.gtag('event', 'job_alert_subscribe', {
                event_category: 'recruitment',
                event_label: category || 'all_jobs',
              });
            }
            modal.remove();
          } else {
            alert(data.message || 'Error subscribing.');
            btn.disabled = false;
            btn.innerHTML = 'Activate Job Alerts &rarr;';
            btn.style.backgroundColor = '#1D6A34';
            btn.style.color = '#FFFFFF';
          }
        } catch (err) {
          alert('Network connection error.');
          btn.disabled = false;
          btn.innerHTML = 'Activate Job Alerts &rarr;';
          btn.style.backgroundColor = '#1D6A34';
          btn.style.color = '#FFFFFF';
        }
      });
    }

    if (prefillCategory) {
      const catInput = document.getElementById('th-alert-category');
      if (catInput) catInput.value = prefillCategory;
    }
  };

  // Helper
  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
  }

  // ── 5. INITIALIZE ALL ENGINES ON DOM READY ───────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    initCookieBanner();
    loadAnnouncements();
    initNewsletterForms();
  });

})();
