<?php
// 1. Update ContentController.php
$contentCtrlPath = 'app/Controllers/Admin/ContentController.php';
$content = file_get_contents($contentCtrlPath);

// In storeArticle():
$targetStore = "            'meta_description' => trim((string) (\$this->request->getPost('meta_description') ?: \$this->request->getPost('summary'))),";
$replaceStore = "            'meta_description' => trim((string) (\$this->request->getPost('meta_description') ?: \$this->request->getPost('summary'))),
            'meta_keywords'    => trim((string) \$this->request->getPost('meta_keywords')),";

// In updateArticle():
$targetUpdate = "            'meta_description' => trim((string) (\$this->request->getPost('meta_description') ?: \$this->request->getPost('summary'))),";
// Both occurrences have this line, let's replace all
$content = str_replace($targetStore, $replaceStore, $content);
file_put_contents($contentCtrlPath, $content);
echo "ContentController updated for article SEO.\n";

// 2. Update article_form.php
$articleFormPath = 'app/Views/admin/content/article_form.php';
$formContent = file_get_contents($articleFormPath);

$targetSeoBox = '      <!-- SEO Metadata Accordion / Box -->
      <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 space-y-4">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Search Engine &amp; Social Optimization</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Meta Title</label>
            <input type="text" name="meta_title" value="<?= esc($article[\'meta_title\'] ?? \'\') ?>" placeholder="Default: Article Title" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-600 mb-1">Meta Description</label>
            <input type="text" name="meta_description" value="<?= esc($article[\'meta_description\'] ?? \'\') ?>" placeholder="Default: Article Summary" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
        </div>
      </div>';

$replaceSeoBox = '      <!-- SEO Metadata & Google SERP Simulator -->
      <div class="bg-slate-50 p-6 rounded-3xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-[#4285F4]" viewBox="0 0 24 24" fill="currentColor"><path d="M12.24 10.285V14.4h6.887C18.2 17.15 15.645 19.2 12.24 19.2c-3.978 0-7.2-3.222-7.2-7.2s3.222-7.2 7.2-7.2c1.785 0 3.407.65 4.673 1.727l3.197-3.197C18.158 1.487 15.39 0.6 12.24 0.6 5.86 0.6.68 5.78.68 12.16s5.18 11.56 11.56 11.56c6.66 0 11.08-4.68 11.08-11.28 0-.76-.08-1.5-.22-2.155H12.24z"/></svg>
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Google Search Ranking &amp; Dynamic SEO</h3>
          </div>
          <span class="text-[10px] text-slate-400 font-bold">50-60 Title / 150-160 Snippet Standard</span>
        </div>

        <!-- Live SERP Preview -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200 max-w-2xl font-sans shadow-sm">
          <div class="flex items-center gap-2 mb-1">
            <div class="w-4 h-4 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[9px] font-black">T</div>
            <div class="text-[12px] text-[#202124] leading-none">
              <span class="font-medium">talenthubconsultant.com</span>
              <span class="text-[#5f6368] text-[11px]"> › insights › <?= esc($article[\'slug\'] ?? \'sample-article\') ?></span>
            </div>
          </div>
          <div id="art-serp-title" class="text-[17px] text-[#1a0dab] hover:underline cursor-pointer font-medium leading-snug line-clamp-1">
            <?= esc(!empty($article[\'meta_title\']) ? $article[\'meta_title\'] : (($article[\'title\'] ?? \'Article Title\') . \' | TalentHub Insights\')) ?>
          </div>
          <div id="art-serp-desc" class="text-[13px] text-[#4d5156] leading-relaxed mt-1 line-clamp-2">
            <?= esc(!empty($article[\'meta_description\']) ? $article[\'meta_description\'] : ($article[\'summary\'] ?? \'Pune recruitment intelligence, hiring benchmarks, and engineering insights.\')) ?>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-bold text-slate-700">Meta Title (50–60 chars)</label>
              <span id="art-title-cnt" class="text-[10px] font-mono font-bold text-slate-400">0 / 60</span>
            </div>
            <input type="text" id="art-meta-title" name="meta_title" value="<?= esc($article[\'meta_title\'] ?? \'\') ?>" placeholder="Default: Article Title | TalentHub Insights" maxlength="80" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">Search Keywords (Comma-Separated)</label>
            <input type="text" name="meta_keywords" value="<?= esc($article[\'meta_keywords\'] ?? \'\') ?>" placeholder="e.g. Pune tech salaries, Hinjawadi hiring trends, IT salary index" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
          <div class="sm:col-span-2">
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-bold text-slate-700">Meta Description (150–160 chars for high Google CTR)</label>
              <span id="art-desc-cnt" class="text-[10px] font-mono font-bold text-slate-400">0 / 160</span>
            </div>
            <textarea id="art-meta-desc" name="meta_description" rows="2" placeholder="Default: Article Summary..." maxlength="200" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5 leading-relaxed"><?= esc($article[\'meta_description\'] ?? \'\') ?></textarea>
          </div>
        </div>
      </div>';

$formContent_norm = str_replace("\r\n", "\n", $formContent);
$targetSeoBox_norm = str_replace("\r\n", "\n", $targetSeoBox);
$replaceSeoBox_norm = str_replace("\r\n", "\n", $replaceSeoBox);

if (strpos($formContent_norm, $targetSeoBox_norm) !== false) {
    $formContent_norm = str_replace($targetSeoBox_norm, $replaceSeoBox_norm, $formContent_norm);

    $jsScript = '
<script>
document.addEventListener(\'DOMContentLoaded\', function () {
  const tInput = document.getElementById(\'art-meta-title\');
  const dInput = document.getElementById(\'art-meta-desc\');
  const tCnt = document.getElementById(\'art-title-cnt\');
  const dCnt = document.getElementById(\'art-desc-cnt\');
  const sTitle = document.getElementById(\'art-serp-title\');
  const sDesc = document.getElementById(\'art-serp-desc\');

  if (tInput && dInput) {
    function updateArtSeo() {
      const tLen = tInput.value.trim().length;
      sTitle.innerText = tInput.value.trim() || \'<?= esc(addslashes(($article[\'title\'] ?? \'Article Title\') . \' | TalentHub Insights\')) ?>\';
      tCnt.innerHTML = `<span class="${tLen >= 45 && tLen <= 65 ? \'text-emerald-600 font-bold\' : \'text-slate-400\'}">${tLen} / 60 chars</span>`;

      const dLen = dInput.value.trim().length;
      sDesc.innerText = dInput.value.trim() || \'<?= esc(addslashes($article[\'summary\'] ?? \'Pune recruitment intelligence, hiring benchmarks, and engineering insights.\')) ?>\';
      dCnt.innerHTML = `<span class="${dLen >= 135 && dLen <= 165 ? \'text-emerald-600 font-bold\' : \'text-slate-400\'}">${dLen} / 160 chars</span>`;
    }
    tInput.addEventListener(\'input\', updateArtSeo);
    dInput.addEventListener(\'input\', updateArtSeo);
    updateArtSeo();
  }
});
</script>
';
    $formContent_norm .= $jsScript;
    file_put_contents($articleFormPath, $formContent_norm);
    echo "article_form.php updated with Google SERP simulator.\n";
} else {
    echo "FAILED: targetSeoBox not found in article_form.php\n";
}

