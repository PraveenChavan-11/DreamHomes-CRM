<div class="max-w-4xl mx-auto space-y-6">

  <!-- Breadcrumbs & Header -->
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/content/pages') ?>" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Page SEO Optimization</span>
          <span class="text-slate-300">&bull;</span>
          <span class="text-xs font-mono text-brand-primary font-bold">/<?= esc($page['slug'] === 'home' ? '' : $page['slug']) ?></span>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Edit SEO: <?= esc($page['title']) ?></h1>
      </div>
    </div>
    <a href="<?= $page['slug'] === 'home' ? base_url() : base_url($page['slug']) ?>" target="_blank" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition flex items-center gap-1.5 shadow-sm">
      <span>View Live</span>
      <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </a>
  </div>

  <!-- Interactive Google Search Preview (SERP Card) -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-7 space-y-3">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor"><path d="M12.24 10.285V14.4h6.887C18.2 17.15 15.645 19.2 12.24 19.2c-3.978 0-7.2-3.222-7.2-7.2s3.222-7.2 7.2-7.2c1.785 0 3.407.65 4.673 1.727l3.197-3.197C18.158 1.487 15.39 0.6 12.24 0.6 5.86 0.6.68 5.78.68 12.16s5.18 11.56 11.56 11.56c6.66 0 11.08-4.68 11.08-11.28 0-.76-.08-1.5-.22-2.155H12.24z"/></svg>
        <span class="text-xs font-bold text-slate-800">Google Search Result Preview (Live SERP Simulator)</span>
      </div>
      <span class="text-[10px] text-slate-400 font-semibold">Updates in Real-Time</span>
    </div>

    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/60 max-w-2xl font-sans">
      <div class="flex items-center gap-2 mb-1">
        <div class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[9px] font-black">T</div>
        <div class="text-[12px] text-[#202124] leading-none">
          <span class="font-medium">talenthubconsultant.com</span>
          <span class="text-[#5f6368] text-[11px]"> › <?= esc($page['slug'] === 'home' ? '' : $page['slug']) ?></span>
        </div>
      </div>
      <div id="serp-preview-title" class="text-[18px] text-[#1a0dab] hover:underline cursor-pointer font-medium leading-snug line-clamp-1">
        <?= esc($page['meta_title'] ?: $page['title']) ?>
      </div>
      <div id="serp-preview-desc" class="text-[13px] text-[#4d5156] leading-relaxed mt-1 line-clamp-2">
        <?= esc($page['meta_description'] ?: 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises.') ?>
      </div>
    </div>
  </div>

  <!-- Main Edit Form -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
    <form action="<?= base_url('admin/content/pages/update/' . $page['id']) ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <!-- Page Identification -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Page Title (Internal CMS Reference) *</label>
          <input type="text" name="title" required value="<?= esc($page['title']) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">URL Route</label>
          <input type="text" readonly value="/<?= esc($page['slug'] === 'home' ? '' : $page['slug']) ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-500 font-mono text-xs cursor-not-allowed">
        </div>
      </div>

      <!-- SEO Meta Title -->
      <div class="space-y-2 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            Google Search Meta Title (50–60 characters) *
          </label>
          <span id="title-counter" class="text-[11px] font-mono font-bold text-slate-500">
            0 / 60
          </span>
        </div>
        <input type="text" id="meta-title-input" name="meta_title" required value="<?= esc($page['meta_title'] ?? '') ?>" placeholder="e.g. TalentHub Consultant | Leading Job Consultancy in Pune" maxlength="80" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary font-medium">
        <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Recommended length is <strong>50–60 characters</strong>. Titles exceeding 60 characters are truncated with an ellipsis (...) in Google mobile and desktop search results.</span>
        </p>
      </div>

      <!-- SEO Meta Description -->
      <div class="space-y-2 pt-2 border-t border-slate-100">
        <div class="flex items-center justify-between">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            Search Result Meta Description Snippet (150–160 characters) *
          </label>
          <span id="desc-counter" class="text-[11px] font-mono font-bold text-slate-500">
            0 / 160
          </span>
        </div>
        <textarea id="meta-desc-input" name="meta_description" rows="3" required placeholder="High-CTR summary snippet that convinces visitors to click from Google Search results..." maxlength="200" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary leading-relaxed"><?= esc($page['meta_description'] ?? '') ?></textarea>
        <p class="text-[11px] text-slate-400 flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Recommended length is <strong>150–160 characters</strong>. A compelling call-to-action increases organic click-through rate (CTR) by up to 35%.</span>
        </p>
      </div>

      <!-- SEO Keywords -->
      <div class="space-y-2 pt-2 border-t border-slate-100">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
          Target Search Keywords (Comma-Separated)
        </label>
        <input type="text" name="meta_keywords" value="<?= esc($page['meta_keywords'] ?? '') ?>" placeholder="e.g. job consultancy Pune, placement agency Hinjawadi, recruitment consultants Pune" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        <p class="text-[11px] text-slate-400">Target primary and long-tail recruitment search queries for Pune, Hinjawadi, Chakan, and Kharadi.</p>
      </div>

      <!-- OpenGraph Social Image URL -->
      <div class="space-y-2 pt-2 border-t border-slate-100">
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
          Social Share Image URL (Open Graph / Twitter Card)
        </label>
        <input type="text" name="og_image" value="<?= esc($page['og_image'] ?? '') ?>" placeholder="e.g. /images/og-share-banner.webp (Leave empty for default brand favicon)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
        <p class="text-[11px] text-slate-400">Displays as the preview banner when this page is shared on LinkedIn, WhatsApp, and X.</p>
      </div>

      <!-- Buttons -->
      <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
        <a href="<?= base_url('admin/content/pages') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
          Cancel
        </a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Save &amp; Publish Page SEO</span>
        </button>
      </div>

    </form>
  </div>

</div>

<!-- Real-time SERP Preview & Character Counter Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const titleInput = document.getElementById('meta-title-input');
  const descInput = document.getElementById('meta-desc-input');
  const titleCounter = document.getElementById('title-counter');
  const descCounter = document.getElementById('desc-counter');
  const serpTitle = document.getElementById('serp-preview-title');
  const serpDesc = document.getElementById('serp-preview-desc');

  function updateTitle() {
    const len = titleInput.value.trim().length;
    serpTitle.innerText = titleInput.value.trim() || '<?= esc(addslashes($page['title'])) ?>';
    
    let colorClass = 'text-slate-500';
    let statusText = '';
    if (len >= 45 && len <= 65) {
      colorClass = 'text-emerald-600';
      statusText = ' • Optimal';
    } else if (len > 65) {
      colorClass = 'text-red-500';
      statusText = ' • Truncated';
    } else if (len > 0) {
      colorClass = 'text-amber-500';
      statusText = ' • Short';
    }
    titleCounter.innerHTML = `<span class="${colorClass}">${len} / 60 chars${statusText}</span>`;
  }

  function updateDesc() {
    const len = descInput.value.trim().length;
    serpDesc.innerText = descInput.value.trim() || 'TalentHub Consultant connects top engineering, tech, and leadership talent with Pune\'s leading enterprises.';
    
    let colorClass = 'text-slate-500';
    let statusText = '';
    if (len >= 135 && len <= 165) {
      colorClass = 'text-emerald-600';
      statusText = ' • Optimal';
    } else if (len > 165) {
      colorClass = 'text-red-500';
      statusText = ' • Truncated';
    } else if (len > 0) {
      colorClass = 'text-amber-500';
      statusText = ' • Short';
    }
    descCounter.innerHTML = `<span class="${colorClass}">${len} / 160 chars${statusText}</span>`;
  }

  titleInput.addEventListener('input', updateTitle);
  descInput.addEventListener('input', updateDesc);

  updateTitle();
  updateDesc();
});
</script>
