<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex items-center justify-between border-b border-slate-200 pb-5">
    <div>
      <div class="flex items-center gap-2">
        <a href="<?= base_url('admin/social/posts') ?>" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Back to Posts</a>
        <span class="text-slate-300">/</span>
        <span class="text-xs font-bold text-brand-primary">Studio</span>
      </div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Post Creation Studio</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Compose, optimize, preview, schedule, and publish professional marketing content across LinkedIn & Instagram.</p>
    </div>

    <!-- Quick Assistant Trigger -->
    <button type="button" onclick="openAssistantModal()" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 hover:bg-amber-100 transition shadow-sm">
      <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
      <span>AI Content Assistant</span>
    </button>
  </div>

  <!-- Form & Live Preview Grid -->
  <form id="postComposerForm" method="POST" action="<?= base_url('admin/social/posts/store') ?>" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <?= csrf_field() ?>

    <!-- LEFT COLUMN: Content Editor (7 cols) -->
    <div class="lg:col-span-7 space-y-6">

      <!-- 1. Metadata Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">1. Campaign & Metadata</h2>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Internal Post Title <span class="text-rose-500">*</span></label>
            <input type="text" name="title" id="postTitle" required value="<?= old('title') ?>" placeholder="e.g. WorkSphere Enterprise Launch — Biometric Payroll Spotlight" class="w-full text-sm font-semibold rounded-xl border border-slate-300 px-3.5 py-2.5 focus:ring-2 focus:ring-brand-accent focus:border-brand-primary">
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Marketing Campaign</label>
              <select name="campaign_id" class="w-full text-xs font-medium rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent">
                <option value="">None (Standalone Organic Post)</option>
                <?php foreach ($campaigns as $camp): ?>
                  <option value="<?= $camp['id'] ?>"><?= esc($camp['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Post Objective</label>
              <select name="post_objective" class="w-full text-xs font-medium rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent">
                <option value="lead_generation">Lead Generation / Enquiries</option>
                <option value="brand_awareness">Brand Awareness</option>
                <option value="recruitment">Recruitment / Job Vacancy</option>
                <option value="product_demo">Product Walkthrough (WorkSphere)</option>
                <option value="thought_leadership">Thought Leadership & Insights</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Platform Selection & Caption Studio -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">2. Platform Routing & Copy</h2>

          <!-- Platform Radios -->
          <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
            <label class="cursor-pointer px-3 py-1 rounded-lg transition" id="tab_both_label">
              <input type="radio" name="platform" value="both" checked class="hidden" onchange="updatePlatformSelection('both')">
              <span>Both (LI + IG)</span>
            </label>
            <label class="cursor-pointer px-3 py-1 rounded-lg transition text-slate-600" id="tab_linkedin_label">
              <input type="radio" name="platform" value="linkedin" class="hidden" onchange="updatePlatformSelection('linkedin')">
              <span>LinkedIn</span>
            </label>
            <label class="cursor-pointer px-3 py-1 rounded-lg transition text-slate-600" id="tab_instagram_label">
              <input type="radio" name="platform" value="instagram" class="hidden" onchange="updatePlatformSelection('instagram')">
              <span>Instagram</span>
            </label>
          </div>
        </div>

        <!-- Base Caption -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="block text-xs font-bold text-slate-700">Primary Caption / Content <span class="text-rose-500">*</span></label>
            <span id="baseCharCount" class="text-[11px] font-mono text-slate-400">0 chars</span>
          </div>
          <textarea name="base_content" id="baseContent" rows="6" required placeholder="Write your professional marketing update, industry perspective, or recruitment announcement..." class="w-full text-xs font-normal leading-relaxed rounded-xl border border-slate-300 p-3.5 focus:ring-2 focus:ring-brand-accent focus:border-brand-primary" oninput="syncPreview()"><?= old('base_content') ?></textarea>
        </div>

        <!-- Hashtags & CTA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Hashtags</label>
            <input type="text" name="base_hashtags" id="baseHashtags" value="<?= old('base_hashtags', '#TalentHub #Recruitment #ITServices #SoftwareDevelopment') ?>" placeholder="#TalentHub #Recruitment #Tech" class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent" oninput="syncPreview()">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Call to Action (CTA)</label>
            <input type="text" name="base_cta" id="baseCta" value="<?= old('base_cta', 'Connect With TalentHub') ?>" placeholder="e.g. Schedule Consultation" class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent" oninput="syncPreview()">
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Destination URL (Optional Link Preview)</label>
          <input type="url" name="base_destination_url" id="baseDestUrl" value="<?= old('base_destination_url', 'https://talenthubconsultant.com') ?>" placeholder="https://talenthubconsultant.com/services" class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent" oninput="syncPreview()">
        </div>

        <!-- Platform-Specific Override Accordion -->
        <div class="pt-3 border-t border-slate-100">
          <button type="button" onclick="togglePlatformOverrides()" class="flex items-center justify-between w-full text-xs font-bold text-slate-600 hover:text-brand-primary">
            <span>Platform-Specific Caption Customization (Optional)</span>
            <span id="overrideArrow">&darr;</span>
          </button>

          <div id="platformOverridesBlock" class="hidden mt-4 space-y-4 pt-3 border-t border-dashed border-slate-200">
            <!-- LinkedIn override -->
            <div class="p-3.5 bg-blue-50/50 rounded-xl border border-blue-100">
              <span class="text-xs font-bold text-[#0A66C2] block mb-1">LinkedIn Specific Text</span>
              <textarea name="linkedin_caption" id="linkedinCaption" rows="3" placeholder="Leave empty to use primary caption above..." class="w-full text-xs rounded-lg border border-blue-200 p-2.5 bg-white" oninput="syncPreview()"></textarea>
            </div>
            <!-- Instagram override -->
            <div class="p-3.5 bg-pink-50/50 rounded-xl border border-pink-100">
              <span class="text-xs font-bold text-[#E4405F] block mb-1">Instagram Specific Caption</span>
              <textarea name="instagram_caption" id="instagramCaption" rows="3" placeholder="Leave empty to use primary caption above..." class="w-full text-xs rounded-lg border border-pink-200 p-2.5 bg-white" oninput="syncPreview()"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Rich Media Uploader & Attachment -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">3. Media Attachments</h2>
          <span class="text-xs text-slate-400">JPG, PNG, WebP, MP4, PDF (LinkedIn only)</span>
        </div>

        <!-- Drag & Drop Zone -->
        <div id="dropZone" class="border-2 border-dashed border-slate-300 hover:border-brand-primary rounded-2xl p-6 text-center cursor-pointer transition bg-slate-50/50" onclick="document.getElementById('studioMediaInput').click()">
          <input type="file" id="studioMediaInput" class="hidden" accept="image/*,video/mp4,application/pdf" onchange="handleStudioUpload(this.files)">
          <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          <span class="block text-xs font-bold text-slate-700">Click to upload or drag & drop media here</span>
          <span class="block text-[11px] text-slate-400 mt-1">Images max 10MB | Videos max 100MB | Documents max 20MB</span>
        </div>

        <!-- Selected Media Container -->
        <div id="selectedMediaList" class="grid grid-cols-3 sm:grid-cols-4 gap-3 pt-2">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- 4. Scheduling & Publishing Actions -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">4. Publish or Schedule</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Publishing Date & Time</label>
            <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent">
            <span class="text-[11px] text-slate-400 block mt-1">Timezone: Asia/Kolkata (IST)</span>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Internal Review Notes</label>
            <input type="text" name="internal_notes" placeholder="Optional notes for other administrators" class="w-full text-xs rounded-xl border border-slate-300 px-3 py-2.5 focus:ring-2 focus:ring-brand-accent">
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
          <button type="submit" name="submit_action" value="draft" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
            Save as Draft
          </button>

          <div class="flex items-center gap-2.5">
            <button type="submit" name="submit_action" value="schedule" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold shadow-sm transition">
              Schedule Post
            </button>
            <button type="submit" name="submit_action" value="publish_now" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-extrabold shadow-md shadow-brand-primary/20 transition flex items-center gap-2">
              <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              <span>Publish Now</span>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- RIGHT COLUMN: Live Interactive Previews (5 cols) -->
    <div class="lg:col-span-5 space-y-6">

      <!-- Preview Selector Tabs -->
      <div class="flex items-center justify-between border-b border-slate-200 pb-2">
        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Live Simulator</span>
        <div class="flex items-center gap-2">
          <button type="button" id="btnPreviewLI" onclick="switchPreviewMode('linkedin')" class="text-xs font-bold px-3 py-1 rounded-lg bg-blue-50 text-[#0A66C2] border border-blue-200">LinkedIn</button>
          <button type="button" id="btnPreviewIG" onclick="switchPreviewMode('instagram')" class="text-xs font-bold px-3 py-1 rounded-lg text-slate-500 hover:text-slate-800">Instagram</button>
        </div>
      </div>

      <!-- LINKEDIN DESKTOP PREVIEW -->
      <div id="previewLinkedInCard" class="bg-white rounded-2xl border border-slate-300 p-4 shadow-sm text-xs font-sans space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-full bg-brand-primary text-white flex items-center justify-center font-bold text-xs shrink-0">
              TH
            </div>
            <div>
              <span class="font-bold text-slate-900 block leading-tight">TalentHub Consultant</span>
              <span class="text-[11px] text-slate-500 block">IT Services &amp; Tech Recruitment Advisory • Promoted</span>
              <span class="text-[10px] text-slate-400">Just now • 🌐</span>
            </div>
          </div>
          <button type="button" class="text-slate-400">&bull;&bull;&bull;</button>
        </div>

        <!-- Post Commentary Text -->
        <div id="pvLiContent" class="text-slate-800 leading-relaxed whitespace-pre-line break-words">
          Write your post caption to preview how it renders on LinkedIn feeds...
        </div>

        <!-- Hashtags -->
        <div id="pvLiHashtags" class="text-[#0A66C2] font-semibold">
          #TalentHub #Recruitment #ITServices
        </div>

        <!-- Media Attachment Preview -->
        <div id="pvLiMediaBox" class="rounded-xl overflow-hidden bg-slate-100 border border-slate-200 min-h-[160px] flex items-center justify-center text-slate-400">
          <span class="text-xs">No media attached</span>
        </div>

        <!-- Reactions Bar -->
        <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-slate-500 text-[11px]">
          <span class="flex items-center gap-1">👍 💡 👏 <span>42</span></span>
          <span>8 comments • 4 reposts</span>
        </div>
      </div>

      <!-- INSTAGRAM FEED PREVIEW -->
      <div id="previewInstagramCard" class="hidden bg-white rounded-2xl border border-slate-300 shadow-sm text-xs overflow-hidden">
        <!-- IG Header -->
        <div class="flex items-center justify-between p-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-400 to-pink-600 p-[2px]">
              <div class="w-full h-full rounded-full bg-white flex items-center justify-center font-bold text-brand-primary text-[10px]">
                TH
              </div>
            </div>
            <div>
              <span class="font-bold text-slate-900 block leading-tight">talenthub_consultant</span>
              <span class="text-[10px] text-slate-400">Bangalore, India</span>
            </div>
          </div>
          <span class="text-slate-400 font-bold">&bull;&bull;&bull;</span>
        </div>

        <!-- Media Square Container -->
        <div id="pvIgMediaBox" class="aspect-square bg-slate-100 flex items-center justify-center text-slate-400 overflow-hidden">
          <span class="text-xs">No image or video attached</span>
        </div>

        <!-- IG Action Icons -->
        <div class="p-3 space-y-2">
          <div class="flex items-center justify-between text-slate-800">
            <div class="flex items-center gap-3">
              <svg class="w-5 h-5 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
              <svg class="w-5 h-5 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
              <svg class="w-5 h-5 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </div>
            <svg class="w-5 h-5 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
          </div>

          <!-- Likes -->
          <span class="font-bold text-slate-900 block">128 likes</span>

          <!-- Caption & Hashtags -->
          <div class="text-slate-800 leading-snug break-words">
            <span class="font-bold">talenthub_consultant</span>
            <span id="pvIgContent" class="ml-1">Previewing caption text...</span>
          </div>

          <div id="pvIgHashtags" class="text-[#0A66C2] text-[11px] font-medium pt-1">
            #TalentHub #Recruitment
          </div>
        </div>
      </div>

    </div>

  </form>

</div>

<!-- AI CONTENT ASSISTANT MODAL / DRAWER -->
<div id="assistantModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <h3 class="text-base font-extrabold text-slate-900">AI &amp; Template Content Assistant</h3>
      </div>
      <button type="button" onclick="closeAssistantModal()" class="text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
    </div>

    <div class="space-y-3">
      <div>
        <label class="block text-xs font-bold text-slate-700 mb-1">Business Vertical / Category</label>
        <select id="astCategory" class="w-full text-xs font-medium rounded-xl border border-slate-300 p-2.5">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Tone of Voice</label>
          <select id="astTone" class="w-full text-xs font-medium rounded-xl border border-slate-300 p-2.5">
            <?php foreach ($tones as $t): ?>
              <option value="<?= esc($t) ?>"><?= esc($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Specific Topic / Keyword</label>
          <input type="text" id="astTopic" placeholder="e.g. Biometric sync" class="w-full text-xs rounded-xl border border-slate-300 p-2.5">
        </div>
      </div>

      <button type="button" onclick="generateAssistantSuggestions()" class="w-full py-2.5 bg-brand-primary text-white text-xs font-bold rounded-xl hover:bg-brand-hover transition shadow-sm">
        Generate Post Suggestions
      </button>

      <!-- Results Preview Area -->
      <div id="astResults" class="hidden mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Suggested Caption:</span>
          <p id="astCaptionPreview" class="text-xs text-slate-800 whitespace-pre-line leading-relaxed"></p>
        </div>
        <div>
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Suggested Hashtags:</span>
          <span id="astHashtagsPreview" class="text-xs text-brand-primary font-semibold"></span>
        </div>
        <div class="pt-2 flex justify-end">
          <button type="button" onclick="applyAssistantContent()" class="px-4 py-2 bg-brand-accent text-brand-dark font-extrabold text-xs rounded-xl hover:bg-brand-accent/90 transition shadow-sm">
            Apply to Composer &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let currentUploadedMedia = [];
let generatedAssistantData = null;

function updatePlatformSelection(platform) {
  document.getElementById('tab_both_label').classList.remove('bg-white', 'shadow-sm', 'text-brand-primary');
  document.getElementById('tab_linkedin_label').classList.remove('bg-white', 'shadow-sm', 'text-brand-primary');
  document.getElementById('tab_instagram_label').classList.remove('bg-white', 'shadow-sm', 'text-brand-primary');

  if (platform === 'both') {
    document.getElementById('tab_both_label').classList.add('bg-white', 'shadow-sm', 'text-brand-primary');
  } else if (platform === 'linkedin') {
    document.getElementById('tab_linkedin_label').classList.add('bg-white', 'shadow-sm', 'text-brand-primary');
    switchPreviewMode('linkedin');
  } else {
    document.getElementById('tab_instagram_label').classList.add('bg-white', 'shadow-sm', 'text-brand-primary');
    switchPreviewMode('instagram');
  }
  syncPreview();
}

function switchPreviewMode(mode) {
  const liBtn = document.getElementById('btnPreviewLI');
  const igBtn = document.getElementById('btnPreviewIG');
  const liCard = document.getElementById('previewLinkedInCard');
  const igCard = document.getElementById('previewInstagramCard');

  if (mode === 'linkedin') {
    liBtn.className = 'text-xs font-bold px-3 py-1 rounded-lg bg-blue-50 text-[#0A66C2] border border-blue-200';
    igBtn.className = 'text-xs font-bold px-3 py-1 rounded-lg text-slate-500 hover:text-slate-800';
    liCard.classList.remove('hidden');
    igCard.classList.add('hidden');
  } else {
    igBtn.className = 'text-xs font-bold px-3 py-1 rounded-lg bg-pink-50 text-[#E4405F] border border-pink-200';
    liBtn.className = 'text-xs font-bold px-3 py-1 rounded-lg text-slate-500 hover:text-slate-800';
    igCard.classList.remove('hidden');
    liCard.classList.add('hidden');
  }
}

function togglePlatformOverrides() {
  const block = document.getElementById('platformOverridesBlock');
  block.classList.toggle('hidden');
}

function syncPreview() {
  const baseText = document.getElementById('baseContent').value;
  const baseHash = document.getElementById('baseHashtags').value;
  const liCustom = document.getElementById('linkedinCaption').value;
  const igCustom = document.getElementById('instagramCaption').value;

  document.getElementById('baseCharCount').innerText = `${baseText.length} chars`;

  // LinkedIn
  document.getElementById('pvLiContent').innerText = liCustom.trim() !== '' ? liCustom : (baseText || 'Write your caption to preview...');
  document.getElementById('pvLiHashtags').innerText = baseHash;

  // Instagram
  document.getElementById('pvIgContent').innerText = igCustom.trim() !== '' ? igCustom : (baseText || 'Write your caption to preview...');
  document.getElementById('pvIgHashtags').innerText = baseHash;
}

function handleStudioUpload(files) {
  if (!files || files.length === 0) return;
  const file = files[0];
  const formData = new FormData();
  formData.append('media_file', file);
  formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

  fetch('<?= base_url('admin/social/media/upload') ?>', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      addMediaToSelection(data.media);
    } else {
      alert(data.message || 'Media upload failed.');
    }
  })
  .catch(err => alert('Upload error: ' + err.message));
}

function addMediaToSelection(media) {
  currentUploadedMedia.push(media);
  renderMediaThumbnails();
}

function renderMediaThumbnails() {
  const list = document.getElementById('selectedMediaList');
  list.innerHTML = '';

  currentUploadedMedia.forEach((m, idx) => {
    const el = document.createElement('div');
    el.className = 'relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-square group';
    el.innerHTML = `
      <input type="hidden" name="media_ids[]" value="${m.id}">
      <img src="${m.thumbnail_url || m.file_url}" class="w-full h-full object-cover">
      <button type="button" onclick="removeMedia(${idx})" class="absolute top-1 right-1 bg-slate-900/80 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">&times;</button>
    `;
    list.appendChild(el);
  });

  // Update Previews
  if (currentUploadedMedia.length > 0) {
    const first = currentUploadedMedia[0];
    document.getElementById('pvLiMediaBox').innerHTML = `<img src="${first.file_url}" class="w-full max-h-64 object-cover">`;
    document.getElementById('pvIgMediaBox').innerHTML = `<img src="${first.file_url}" class="w-full h-full object-cover">`;
  } else {
    document.getElementById('pvLiMediaBox').innerHTML = '<span class="text-xs">No media attached</span>';
    document.getElementById('pvIgMediaBox').innerHTML = '<span class="text-xs">No image or video attached</span>';
  }
}

function removeMedia(idx) {
  currentUploadedMedia.splice(idx, 1);
  renderMediaThumbnails();
}

function openAssistantModal() {
  document.getElementById('assistantModal').classList.remove('hidden');
}

function closeAssistantModal() {
  document.getElementById('assistantModal').classList.add('hidden');
}

function generateAssistantSuggestions() {
  const cat = document.getElementById('astCategory').value;
  const tone = document.getElementById('astTone').value;
  const topic = document.getElementById('astTopic').value;

  const fd = new FormData();
  fd.append('category', cat);
  fd.append('tone', tone);
  fd.append('topic', topic);
  fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

  fetch('<?= base_url('admin/social/assistant/suggest') ?>', {
    method: 'POST',
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      generatedAssistantData = data.data;
      document.getElementById('astCaptionPreview').innerText = data.data.base.caption;
      document.getElementById('astHashtagsPreview').innerText = data.data.base.hashtags;
      document.getElementById('astResults').classList.remove('hidden');
    }
  });
}

function applyAssistantContent() {
  if (!generatedAssistantData) return;
  document.getElementById('baseContent').value = generatedAssistantData.base.caption;
  document.getElementById('baseHashtags').value = generatedAssistantData.base.hashtags;
  document.getElementById('baseCta').value = generatedAssistantData.base.cta;
  document.getElementById('linkedinCaption').value = generatedAssistantData.linkedin.caption;
  document.getElementById('instagramCaption').value = generatedAssistantData.instagram.caption;

  closeAssistantModal();
  syncPreview();
}
</script>
<?= $this->endSection() ?>

