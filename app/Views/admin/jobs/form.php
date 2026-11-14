<div class="max-w-4xl mx-auto space-y-6">

  <!-- Back / Header Bar -->
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/jobs') ?>" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
          <?= !empty($job) ? 'Edit Job Posting' : 'Create New Vacancy' ?>
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
          <?= !empty($job) ? esc($job['title']) : 'Fill out details to publish a new recruitment opportunity' ?>
        </p>
      </div>
    </div>
  </div>

  <!-- Main Form Card -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8">
    <form action="<?= !empty($job) ? base_url('admin/jobs/update/' . $job['id']) : base_url('admin/jobs/create') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <!-- Title & Slug -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Job Title *</label>
          <input type="text" name="title" required value="<?= esc($job['title'] ?? '') ?>" placeholder="e.g. Senior Manufacturing Engineer" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">URL Slug (Optional)</label>
          <input type="text" name="slug" value="<?= esc($job['slug'] ?? '') ?>" placeholder="Leave blank to auto-generate" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary font-mono text-xs">
        </div>
      </div>

      <!-- Category, Job Type, Work Mode, Location -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Category *</label>
          <select name="category_id" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= esc($cat['id']) ?>" <?= (!empty($job) && $job['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                <?= esc($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Job Type</label>
          <select name="job_type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
            <option value="FT" <?= (!empty($job) && in_array($job['job_type'], ['FT', 'Full-time'])) ? 'selected' : '' ?>>Full-Time Permanent</option>
            <option value="CT" <?= (!empty($job) && in_array($job['job_type'], ['CT', 'Contract'])) ? 'selected' : '' ?>>Contract Staffing</option>
            <option value="PT" <?= (!empty($job) && in_array($job['job_type'], ['PT', 'Part-time'])) ? 'selected' : '' ?>>Part-Time</option>
            <option value="IN" <?= (!empty($job) && in_array($job['job_type'], ['IN', 'Internship'])) ? 'selected' : '' ?>>Internship</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Work Arrangement</label>
          <select name="work_mode" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
            <option value="ONSITE" <?= (!empty($job) && ($job['work_mode'] ?? '') === 'ONSITE') ? 'selected' : '' ?>>Onsite (Pune)</option>
            <option value="HYBRID" <?= (!empty($job) && ($job['work_mode'] ?? '') === 'HYBRID') ? 'selected' : '' ?>>Hybrid</option>
            <option value="REMOTE" <?= (!empty($job) && ($job['work_mode'] ?? '') === 'REMOTE') ? 'selected' : '' ?>>Fully Remote</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Location *</label>
          <input type="text" name="location" required value="<?= esc($job['location'] ?? 'Pune, Maharashtra') ?>" placeholder="e.g. Hinjawadi, Pune" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
      </div>

      <!-- Experience & Salary -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Min Experience (Yrs)</label>
          <input type="number" min="0" max="30" name="experience_min" value="<?= esc($job['experience_min'] ?? 2) ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Max Experience (Yrs)</label>
          <input type="number" min="0" max="40" name="experience_max" value="<?= esc($job['experience_max'] ?? 5) ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Min Salary (Annual ₹)</label>
          <input type="number" step="10000" name="salary_min" value="<?= esc($job['salary_min'] ?? '') ?>" placeholder="e.g. 600000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Max Salary (Annual ₹)</label>
          <input type="number" step="10000" name="salary_max" value="<?= esc($job['salary_max'] ?? '') ?>" placeholder="e.g. 1200000" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
      </div>

      <!-- Skills & Salary Options -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Required Skills (Comma separated)</label>
          <input type="text" name="skills" value="<?= esc($job['skills'] ?? '') ?>" placeholder="e.g. Java, Spring Boot, Microservices, AWS" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>
        <div class="flex items-center gap-6 pt-5">
          <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
            <input type="checkbox" name="is_salary_public" value="1" <?= (!empty($job['is_salary_public'])) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
            <span>Show Salary Publicly</span>
          </label>
          <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
            <input type="checkbox" name="is_salary_negotiable" value="1" <?= (!isset($job['is_salary_negotiable']) || !empty($job['is_salary_negotiable'])) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
            <span>Salary Negotiable</span>
          </label>
        </div>
      </div>

      <!-- Job Description (TinyMCE) -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Job Description *</label>
        <textarea id="job-description" name="description" rows="6" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-primary"><?= esc($job['description'] ?? '') ?></textarea>
      </div>

      <!-- Key Responsibilities & Requirements -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Requirements &amp; Qualifications</label>
          <textarea name="requirements" rows="4" placeholder="List key candidate qualifications..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary"><?= esc($job['requirements'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Perks &amp; Benefits</label>
          <textarea name="benefits" rows="4" placeholder="List company benefits..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary"><?= esc($job['benefits'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- Google Search Engine Optimization (SEO) & Social Graph -->
      <div class="bg-slate-50/80 p-6 rounded-3xl border border-slate-200/80 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor"><path d="M12.24 10.285V14.4h6.887C18.2 17.15 15.645 19.2 12.24 19.2c-3.978 0-7.2-3.222-7.2-7.2s3.222-7.2 7.2-7.2c1.785 0 3.407.65 4.673 1.727l3.197-3.197C18.158 1.487 15.39 0.6 12.24 0.6 5.86 0.6.68 5.78.68 12.16s5.18 11.56 11.56 11.56c6.66 0 11.08-4.68 11.08-11.28 0-.76-.08-1.5-.22-2.155H12.24z"/></svg>
            <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Google Search Ranking &amp; Dynamic SEO</h3>
          </div>
          <span class="text-[10px] text-slate-400 font-bold">50-60 Title / 150-160 Snippet Standard</span>
        </div>

        <!-- Live SERP Preview -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 max-w-2xl font-sans shadow-sm">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[9px] font-black">T</div>
            <div class="text-[12px] text-[#202124] leading-none">
              <span class="font-medium">talenthubconsultant.com</span>
              <span class="text-[#5f6368] text-[11px]"> › jobs › <?= esc($job['slug'] ?? 'sample-job') ?></span>
            </div>
          </div>
          <div id="job-serp-title" class="text-[17px] text-[#1a0dab] hover:underline cursor-pointer font-medium leading-snug line-clamp-1">
            <?= esc(!empty($job['meta_title']) ? $job['meta_title'] : (($job['title'] ?? 'Job Title') . ' in ' . ($job['location'] ?? 'Pune') . ' | TalentHub')) ?>
          </div>
          <div id="job-serp-desc" class="text-[13px] text-[#4d5156] leading-relaxed mt-1 line-clamp-2">
            <?= esc(!empty($job['meta_description']) ? $job['meta_description'] : substr(strip_tags($job['description'] ?? 'Explore this verified career opportunity in Pune with TalentHub Consultant. Direct application with zero candidate fees.'), 0, 160)) ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
          <!-- Meta Title -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700">Custom Meta Title (50–60 chars)</label>
              <span id="job-title-counter" class="text-[10px] font-mono font-bold text-slate-400">0 / 60</span>
            </div>
            <input type="text" id="job-meta-title" name="meta_title" value="<?= esc($job['meta_title'] ?? '') ?>" placeholder="Default: <?= esc($job['title'] ?? 'Role') ?> in Pune | TalentHub" maxlength="80" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>

          <!-- Meta Keywords -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700">Search Keywords (Comma-Separated)</label>
            <input type="text" name="meta_keywords" value="<?= esc($job['meta_keywords'] ?? '') ?>" placeholder="e.g. Java jobs Pune, Hinjawadi IT vacancies, Spring Boot developer" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>

          <!-- Meta Description -->
          <div class="md:col-span-2 space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700">Custom Meta Description (150–160 chars for high CTR)</label>
              <span id="job-desc-counter" class="text-[10px] font-mono font-bold text-slate-400">0 / 160</span>
            </div>
            <textarea id="job-meta-desc" name="meta_description" rows="2" placeholder="Leave blank to auto-generate from job description and requirements..." maxlength="200" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary leading-relaxed"><?= esc($job['meta_description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Status & Featured -->
      <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">
        <div class="flex items-center gap-6">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Publish Status</label>
            <div class="flex items-center gap-4">
              <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                <input type="radio" name="status" value="PUBLISHED" <?= (!empty($job) && in_array(strtoupper($job['status']), ['PUBLISHED', 'ACTIVE'])) || empty($job) ? 'checked' : '' ?> class="text-brand-primary focus:ring-brand-primary">
                <span>Published (Live)</span>
              </label>
              <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                <input type="radio" name="status" value="CLOSED" <?= (!empty($job) && strtoupper($job['status']) === 'CLOSED') ? 'checked' : '' ?> class="text-brand-primary focus:ring-brand-primary">
                <span>Closed</span>
              </label>
              <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                <input type="radio" name="status" value="DRAFT" <?= (!empty($job) && strtoupper($job['status']) === 'DRAFT') ? 'checked' : '' ?> class="text-brand-primary focus:ring-brand-primary">
                <span>Draft</span>
              </label>
            </div>
          </div>

          <label class="inline-flex items-center gap-2.5 text-xs font-bold text-slate-700 cursor-pointer pt-5">
            <input type="checkbox" name="is_featured" value="1" <?= (!empty($job['is_featured'])) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
            <span>Mark as Featured Job</span>
          </label>

          <label class="inline-flex items-center gap-2.5 text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-200 cursor-pointer mt-4">
            <input type="checkbox" name="notify_subscribers" value="1" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-emerald-300">
            <span>🚀 Automatically email Job Alert to subscribed candidates</span>
          </label>
        </div>

        <div class="flex items-center gap-3 pt-5">
          <a href="<?= base_url('admin/jobs') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
            Cancel
          </a>
          <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
            <?= !empty($job) ? 'Save Changes' : 'Publish Job Opening' ?>
          </button>
        </div>
      </div>

    </form>
  </div>

</div>

<script>
  if (typeof tinymce !== 'undefined') {
    tinymce.init({
      selector: '#job-description',
      height: 300,
      menubar: false,
      plugins: 'link lists autolink',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link blockquote',
      branding: false
    });
  }
  // Job SEO Real-time preview
  const jTitle = document.getElementById('job-meta-title');
  const jDesc = document.getElementById('job-meta-desc');
  const jTitleCnt = document.getElementById('job-title-counter');
  const jDescCnt = document.getElementById('job-desc-counter');
  const jSerpTitle = document.getElementById('job-serp-title');
  const jSerpDesc = document.getElementById('job-serp-desc');

  if (jTitle && jDesc) {
    function updateJobSeo() {
      const tLen = jTitle.value.trim().length;
      jSerpTitle.innerText = jTitle.value.trim() || '<?= esc(addslashes(($job['title'] ?? 'Job Title') . ' in ' . ($job['location'] ?? 'Pune') . ' | TalentHub')) ?>';
      jTitleCnt.innerHTML = `<span class="${tLen >= 45 && tLen <= 65 ? 'text-emerald-600 font-bold' : 'text-slate-400'}">${tLen} / 60 chars</span>`;

      const dLen = jDesc.value.trim().length;
      jSerpDesc.innerText = jDesc.value.trim() || 'Explore this verified career opportunity in Pune with TalentHub Consultant. Direct application with zero candidate fees.';
      jDescCnt.innerHTML = `<span class="${dLen >= 135 && dLen <= 165 ? 'text-emerald-600 font-bold' : 'text-slate-400'}">${dLen} / 160 chars</span>`;
    }
    jTitle.addEventListener('input', updateJobSeo);
    jDesc.addEventListener('input', updateJobSeo);
    updateJobSeo();
  }
</script>
