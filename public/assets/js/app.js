/**
 * TalentHub Consultant — Enterprise Client-Side JavaScript
 * Pure Vanilla JS — High-Performance, Zero jQuery, Accessible Microinteractions
 */

document.addEventListener('DOMContentLoaded', () => {
  // ── 1. NAVBAR SCROLL GLASSMORPHISM & PADDING ─────────────────────────────────
  const navbarHeader = document.getElementById('navbar-header');
  const navbarInner = document.getElementById('navbar-inner');

  function handleNavbarScroll() {
    if (!navbarHeader || !navbarInner) return;
    if (window.scrollY > 20) {
      navbarHeader.classList.add('py-2');
      navbarHeader.classList.remove('py-4');
      navbarInner.classList.add('glass-pill-scrolled');
      navbarInner.classList.remove('glass-pill');
    } else {
      navbarHeader.classList.remove('py-2');
      navbarHeader.classList.add('py-4');
      navbarInner.classList.remove('glass-pill-scrolled');
      navbarInner.classList.add('glass-pill');
    }
  }

  window.addEventListener('scroll', handleNavbarScroll, { passive: true });
  handleNavbarScroll();

  // ── 2. MOBILE NAVIGATION DRAWER & FOCUS MANAGEMENT ───────────────────────────
  const mobileToggleBtn = document.getElementById('mobile-menu-toggle');
  const mobileDrawer = document.getElementById('mobile-drawer');
  const mobileMenuOpenIcon = document.getElementById('mobile-menu-open-icon');
  const mobileMenuCloseIcon = document.getElementById('mobile-menu-close-icon');

  function openMobileMenu() {
    if (!mobileDrawer) return;
    mobileDrawer.classList.remove('hidden');
    if (mobileMenuOpenIcon) mobileMenuOpenIcon.classList.add('hidden');
    if (mobileMenuCloseIcon) mobileMenuCloseIcon.classList.remove('hidden');
    if (mobileToggleBtn) mobileToggleBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeMobileMenu() {
    if (!mobileDrawer) return;
    mobileDrawer.classList.add('hidden');
    if (mobileMenuOpenIcon) mobileMenuOpenIcon.classList.remove('hidden');
    if (mobileMenuCloseIcon) mobileMenuCloseIcon.classList.add('hidden');
    if (mobileToggleBtn) mobileToggleBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  function toggleMobileMenu() {
    if (!mobileDrawer) return;
    if (mobileDrawer.classList.contains('hidden')) {
      openMobileMenu();
    } else {
      closeMobileMenu();
    }
  }

  if (mobileToggleBtn) {
    mobileToggleBtn.addEventListener('click', toggleMobileMenu);
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (mobileDrawer && !mobileDrawer.classList.contains('hidden')) {
        closeMobileMenu();
      }
      // Also close any open modals
      closeActiveModals();
      closeMobileFilterDrawer();
    }
  });

  // ── 3. TOAST NOTIFICATION ENGINE ────────────────────────────────────────────
  window.showToast = function (message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'fixed bottom-5 right-5 z-[9999] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    toast.className = `pointer-events-auto flex items-start gap-3 p-4 rounded-2xl shadow-2xl border text-xs sm:text-sm font-semibold transition-all transform duration-300 translate-y-3 opacity-0 ${
      isSuccess
        ? 'bg-[#072412] border-[#37CE97]/40 text-white shadow-[#1D6A34]/25'
        : 'bg-red-950 border-red-500/40 text-white shadow-red-900/30'
    }`;

    const iconSvg = isSuccess
      ? `<svg class="w-5 h-5 text-[#37CE97] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`
      : `<svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;

    toast.innerHTML = `
      ${iconSvg}
      <div class="flex-1 leading-snug">${message}</div>
      <button type="button" class="text-white/60 hover:text-white transition-colors cursor-pointer ml-1 text-sm font-bold leading-none" aria-label="Close Notification">&times;</button>
    `;

    toast.querySelector('button').addEventListener('click', () => {
      toast.classList.add('opacity-0', 'translate-y-2');
      setTimeout(() => toast.remove(), 250);
    });

    container.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.remove('translate-y-3', 'opacity-0');
    });

    setTimeout(() => {
      if (toast.parentElement) {
        toast.classList.add('opacity-0', 'translate-y-3');
        setTimeout(() => toast.remove(), 300);
      }
    }, 5000);
  };

  // ── 4. SAVED JOBS (LOCALSTORAGE BOOKMARKS) ──────────────────────────────────
  window.initSavedJobs = function () {
    let savedList = [];
    try {
      savedList = JSON.parse(localStorage.getItem('talenthub_saved_jobs') || '[]');
    } catch {
      savedList = [];
    }

    // Update bookmark buttons state
    document.querySelectorAll('[data-save-job]').forEach((btn) => {
      const slug = btn.getAttribute('data-save-job');
      const isSaved = savedList.includes(slug);
      updateBookmarkButtonUI(btn, isSaved);

      btn.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleSaveJob(slug, btn);
      };
    });
  };

  function updateBookmarkButtonUI(btn, isSaved) {
    const icon = btn.querySelector('svg');
    if (isSaved) {
      btn.classList.add('border-[#1D6A34]', 'bg-[#EAF5EF]', 'text-[#1D6A34]');
      btn.classList.remove('border-[rgba(29,106,52,0.15)]', 'text-[#3d6350]');
      if (icon) icon.setAttribute('fill', 'currentColor');
      btn.setAttribute('title', 'Remove bookmark');
    } else {
      btn.classList.remove('border-[#1D6A34]', 'bg-[#EAF5EF]', 'text-[#1D6A34]');
      btn.classList.add('border-[rgba(29,106,52,0.15)]', 'text-[#3d6350]');
      if (icon) icon.setAttribute('fill', 'none');
      btn.setAttribute('title', 'Save Job');
    }
  }

  function toggleSaveJob(slug, btn) {
    let savedList = [];
    try {
      savedList = JSON.parse(localStorage.getItem('talenthub_saved_jobs') || '[]');
    } catch {
      savedList = [];
    }

    const index = savedList.indexOf(slug);
    if (index > -1) {
      savedList.splice(index, 1);
      updateBookmarkButtonUI(btn, false);
      window.showToast('Job removed from your saved bookmarks.', 'success');
    } else {
      savedList.push(slug);
      updateBookmarkButtonUI(btn, true);
      window.showToast('Job saved! You can review bookmarked jobs anytime.', 'success');
    }

    try {
      localStorage.setItem('talenthub_saved_jobs', JSON.stringify(savedList));
    } catch {}
  }

  // ── 5. SOCIAL / LINK SHARING ────────────────────────────────────────────────
  window.shareJob = async function (title, url, e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    const shareUrl = url || window.location.href;

    if (navigator.share) {
      try {
        await navigator.share({
          title: `${title} | TalentHub Consultant`,
          text: `Explore this verified career opportunity in Pune with TalentHub Consultant: ${title}`,
          url: shareUrl,
        });
        return;
      } catch (err) {
        if (err.name !== 'AbortError') {
          // Fallback to clipboard
        } else {
          return;
        }
      }
    }

    if (navigator.clipboard) {
      navigator.clipboard.writeText(shareUrl).then(() => {
        window.showToast('Direct opportunity link copied to your clipboard!', 'success');
      });
    } else {
      window.showToast(shareUrl, 'success');
    }
  };

  // ── 6. ACCESSIBLE MODALS & DRAWERS ──────────────────────────────────────────
  function closeActiveModals() {
    document.querySelectorAll('.modal-backdrop').forEach((modal) => {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    });
    document.body.style.overflow = '';
  }

  document.querySelectorAll('[data-modal-target]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        const autofocusInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (autofocusInput) autofocusInput.focus();
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((btn) => {
    btn.addEventListener('click', closeActiveModals);
  });

  document.querySelectorAll('.modal-backdrop').forEach((modal) => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeActiveModals();
      }
    });
  });

  // Mobile Filter Drawer for Jobs Page
  const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
  const mobileFilterDrawer = document.getElementById('mobile-filter-drawer');
  const mobileFilterClose = document.getElementById('mobile-filter-close');

  function openMobileFilterDrawer() {
    if (!mobileFilterDrawer) return;
    mobileFilterDrawer.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeMobileFilterDrawer() {
    if (!mobileFilterDrawer) return;
    mobileFilterDrawer.classList.add('hidden');
    document.body.style.overflow = '';
  }

  if (mobileFilterToggle) {
    mobileFilterToggle.addEventListener('click', openMobileFilterDrawer);
  }
  if (mobileFilterClose) {
    mobileFilterClose.addEventListener('click', closeMobileFilterDrawer);
  }

  // ── 7. ACCORDIONS (FAQ & DETAILS) ───────────────────────────────────────────
  document.querySelectorAll('.accordion-trigger').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const content = trigger.nextElementSibling;
      const icon = trigger.querySelector('.accordion-icon');
      const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

      trigger.setAttribute('aria-expanded', !isExpanded);
      if (content) {
        if (!isExpanded) {
          content.classList.remove('hidden');
          if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
          content.classList.add('hidden');
          if (icon) icon.style.transform = 'rotate(0deg)';
        }
      }
    });
  });

  // ── 8. PASSWORD VISIBILITY TOGGLE ───────────────────────────────────────────
  window.togglePasswordVisibility = function (fieldId = 'password', iconId = 'eye-icon') {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (!input) return;

    if (input.type === 'password') {
      input.type = 'text';
      if (icon) {
        icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>`;
      }
    } else {
      input.type = 'password';
      if (icon) {
        icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
      }
    }
  };

  // ── 9. AJAX FORMS WITH SPINNER & TOAST FEEDBACK ─────────────────────────────
  document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHtml = submitBtn ? submitBtn.innerHTML : 'Submit';

      try {
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span>Processing...</span>
          `;
        }

        const formData = new FormData(form);
        const action = form.getAttribute('action') || window.location.href;

        const res = await fetch(action, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: formData,
        });

        const json = await res.json().catch(() => ({}));

        // Synchronize CSRF token dynamically on every response to prevent 403 Forbidden desync
        if (json && json.csrf_token && json.csrf_hash) {
          document.querySelectorAll(`input[name="${json.csrf_token}"]`).forEach((input) => {
            input.value = json.csrf_hash;
          });
        }

        if (res.ok && json.success) {
          window.showToast(json.message || 'Submitted successfully!', 'success');
          
          // Google Analytics 4 Event Dispatching (Privacy-compliant without PII)
          if (typeof gtag === 'function') {
            try {
              if (action.includes('employer')) {
                gtag('event', 'generate_lead', { event_category: 'engagement', event_label: 'employer_mandate' });
              } else if (action.includes('career-enquiry')) {
                gtag('event', 'sign_up', { event_category: 'engagement', event_label: 'candidate_resume_bank' });
              } else if (action.includes('apply') || action.includes('applications')) {
                gtag('event', 'job_apply', { event_category: 'recruitment', event_label: 'candidate_application' });
              } else if (action.includes('contact')) {
                gtag('event', 'contact_submit', { event_category: 'engagement', event_label: 'contact_enquiry' });
              }
            } catch (gaErr) {
              console.debug('Analytics dispatch notice:', gaErr);
            }
          }

          form.reset();
          const parentModal = form.closest('.modal-backdrop');
          if (parentModal) {
            setTimeout(() => {
              closeActiveModals();
            }, 800);
          }
          if (json.redirect_url) {
            setTimeout(() => {
              window.location.href = json.redirect_url;
            }, 500);
          }
        } else {
          const errMsg = json.message || 'Submission failed. Please check your entries and try again.';
          window.showToast(errMsg, 'error');
          // Show per-field server messages inline when the API supplies them (forms.js).
          if (json.details && typeof window.thFormErrors === 'function') { window.thFormErrors(form, json.details); }
          if (json.redirect_url) {
            setTimeout(() => {
              window.location.href = json.redirect_url;
            }, 1200);
          }
        }
      } catch (err) {
        window.showToast('Network connection error. Please check your network and try again.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalHtml;
        }
      }
    });
  });

  // Run initializations
  window.initSavedJobs();

  // ── 10. WebMCP IN-BROWSER AGENT TOOL REGISTRATION (SEP-1649) ──────────────────
  function registerWebMCPTools() {
    const mc = navigator.modelContext || window.modelContext;
    if (!mc || typeof mc.registerTool !== 'function') return;

    try {
      mc.registerTool({
        name: 'search_pune_jobs',
        description: 'Search active job vacancies across Pune industrial and IT corridors (Hinjawadi, Kharadi, Chakan, Katraj).',
        inputSchema: {
          type: 'object',
          properties: {
            keyword: { type: 'string', description: 'Job title, skill, or keyword' },
            location: { type: 'string', description: 'Pune locality (e.g. Hinjawadi, Chakan, Kharadi)' }
          }
        },
        execute: async (params) => {
          const url = new URL('/api/v1/jobs', window.location.origin);
          if (params?.keyword) url.searchParams.set('search', params.keyword);
          if (params?.location) url.searchParams.set('location', params.location);
          const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
          return await res.json();
        }
      });

      mc.registerTool({
        name: 'get_talenthub_contact',
        description: 'Retrieve verified contact telephone number, official recruitment email, and Katraj Pune office address.',
        inputSchema: { type: 'object', properties: {} },
        execute: async () => {
          return {
            company: 'TalentHub Consultant',
            telephone: '+91 72761 46573',
            email: 'hr@talenthubconsultant.com',
            address: 'Office No. 4, Katraj-Kondhwa Road, Katraj, Pune, Maharashtra 411046',
            hours: 'Monday – Saturday: 9:30 AM – 7:00 PM IST',
            website: 'https://talenthubconsultant.com/'
          };
        }
      });
    } catch (e) {
      console.debug('WebMCP tool registration notice:', e);
    }
  }

  registerWebMCPTools();
});
