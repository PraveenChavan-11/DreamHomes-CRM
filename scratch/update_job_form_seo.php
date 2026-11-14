<?php
$path = 'app/Views/admin/jobs/form.php';
$content = file_get_contents($path);

$target = '      <!-- Status & Featured -->
      <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">';

$addition = '      <!-- Google Search Engine Optimization (SEO) & Social Graph -->
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
              <span class="text-[#5f6368] text-[11px]"> › jobs › <?= esc($job[\'slug\'] ?? \'sample-job\') ?></span>
            </div>
          </div>
          <div id="job-serp-title" class="text-[17px] text-[#1a0dab] hover:underline cursor-pointer font-medium leading-snug line-clamp-1">
            <?= esc(!empty($job[\'meta_title\']) ? $job[\'meta_title\'] : (($job[\'title\'] ?? \'Job Title\') . \' in \' . ($job[\'location\'] ?? \'Pune\') . \' | TalentHub\')) ?>
          </div>
          <div id="job-serp-desc" class="text-[13px] text-[#4d5156] leading-relaxed mt-1 line-clamp-2">
            <?= esc(!empty($job[\'meta_description\']) ? $job[\'meta_description\'] : substr(strip_tags($job[\'description\'] ?? \'Explore this verified career opportunity in Pune with TalentHub Consultant. Direct application with zero candidate fees.\'), 0, 160)) ?>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
          <!-- Meta Title -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700">Custom Meta Title (50–60 chars)</label>
              <span id="job-title-counter" class="text-[10px] font-mono font-bold text-slate-400">0 / 60</span>
            </div>
            <input type="text" id="job-meta-title" name="meta_title" value="<?= esc($job[\'meta_title\'] ?? \'\') ?>" placeholder="Default: <?= esc($job[\'title\'] ?? \'Role\') ?> in Pune | TalentHub" maxlength="80" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>

          <!-- Meta Keywords -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700">Search Keywords (Comma-Separated)</label>
            <input type="text" name="meta_keywords" value="<?= esc($job[\'meta_keywords\'] ?? \'\') ?>" placeholder="e.g. Java jobs Pune, Hinjawadi IT vacancies, Spring Boot developer" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary">
          </div>

          <!-- Meta Description -->
          <div class="md:col-span-2 space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-slate-700">Custom Meta Description (150–160 chars for high CTR)</label>
              <span id="job-desc-counter" class="text-[10px] font-mono font-bold text-slate-400">0 / 160</span>
            </div>
            <textarea id="job-meta-desc" name="meta_description" rows="2" placeholder="Leave blank to auto-generate from job description and requirements..." maxlength="200" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-none focus:border-brand-primary leading-relaxed"><?= esc($job[\'meta_description\'] ?? \'\') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Status & Featured -->
      <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100">';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$addition_norm = str_replace("\r\n", "\n", $addition);

if (strpos($content_norm, $target_norm) !== false) {
    // Add real-time counter script before </script>
    $oldEnd = '</script>';
    $newEnd = '  // Job SEO Real-time preview
  const jTitle = document.getElementById(\'job-meta-title\');
  const jDesc = document.getElementById(\'job-meta-desc\');
  const jTitleCnt = document.getElementById(\'job-title-counter\');
  const jDescCnt = document.getElementById(\'job-desc-counter\');
  const jSerpTitle = document.getElementById(\'job-serp-title\');
  const jSerpDesc = document.getElementById(\'job-serp-desc\');

  if (jTitle && jDesc) {
    function updateJobSeo() {
      const tLen = jTitle.value.trim().length;
      jSerpTitle.innerText = jTitle.value.trim() || \'<?= esc(addslashes(($job[\'title\'] ?? \'Job Title\') . \' in \' . ($job[\'location\'] ?? \'Pune\') . \' | TalentHub\')) ?>\';
      jTitleCnt.innerHTML = `<span class="${tLen >= 45 && tLen <= 65 ? \'text-emerald-600 font-bold\' : \'text-slate-400\'}">${tLen} / 60 chars</span>`;

      const dLen = jDesc.value.trim().length;
      jSerpDesc.innerText = jDesc.value.trim() || \'Explore this verified career opportunity in Pune with TalentHub Consultant. Direct application with zero candidate fees.\';
      jDescCnt.innerHTML = `<span class="${dLen >= 135 && dLen <= 165 ? \'text-emerald-600 font-bold\' : \'text-slate-400\'}">${dLen} / 160 chars</span>`;
    }
    jTitle.addEventListener(\'input\', updateJobSeo);
    jDesc.addEventListener(\'input\', updateJobSeo);
    updateJobSeo();
  }
</script>';

    $content_norm = str_replace($target_norm, $addition_norm, $content_norm);
    $content_norm = str_replace($oldEnd, $newEnd, $content_norm);
    file_put_contents($path, $content_norm);
    echo "jobs/form.php updated with Google SEO section and live SERP preview.\n";
} else {
    echo "FAILED: target not found in jobs/form.php\n";
}

