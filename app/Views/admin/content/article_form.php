<div class="max-w-4xl mx-auto space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">
        <?= !empty($article) ? 'Edit Article: ' . esc($article['title']) : 'Publish New Industry Insight' ?>
      </h1>
      <p class="text-xs text-slate-500 mt-1">
        Share Pune market salary trends, tech talent indices, and consultancy commentary.
      </p>
    </div>
    <a href="<?= base_url('admin/content/articles') ?>" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
      &larr; Back to Articles
    </a>
  </div>

  <!-- Form Card -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
    <form action="<?= !empty($article) ? base_url('admin/content/articles/update/' . $article['id']) : base_url('admin/content/articles/store') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Title -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Article Title *</label>
          <input type="text" name="title" required value="<?= esc($article['title'] ?? '') ?>" placeholder="e.g. Pune Tech Salary Index Q4: Hinjawadi & Kharadi Benchmarks" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Category -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category *</label>
          <select name="category_id" required class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3 bg-slate-50">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (!empty($article) && $article['category_id'] == $c['id']) ? 'selected' : '' ?>>
                <?= esc($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Slug -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">URL Slug (Optional)</label>
          <input type="text" name="slug" value="<?= esc($article['slug'] ?? '') ?>" placeholder="leave empty to generate from title" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Featured Image -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Featured Image URL (Optional)</label>
          <input type="text" name="featured_image" value="<?= esc($article['featured_image'] ?? '') ?>" placeholder="e.g. /images/insights/salary-report.webp" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>
      </div>

      <!-- Summary -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Executive Summary / Excerpt *</label>
        <textarea name="summary" rows="3" required placeholder="A 2-3 sentence overview of this intelligence report..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3"><?= esc($article['summary'] ?? '') ?></textarea>
      </div>

      <!-- Content Body -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Full Article Content (HTML / Markdown) *</label>
        <textarea name="content" rows="12" required placeholder="Enter full article body text..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3 font-mono leading-relaxed"><?= esc($article['content'] ?? '') ?></textarea>
      </div>

      <!-- SEO Metadata & Google SERP Simulator -->
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
              <span class="text-[#5f6368] text-[11px]"> › insights › <?= esc($article['slug'] ?? 'sample-article') ?></span>
            </div>
          </div>
          <div id="art-serp-title" class="text-[17px] text-[#1a0dab] hover:underline cursor-pointer font-medium leading-snug line-clamp-1">
            <?= esc(!empty($article['meta_title']) ? $article['meta_title'] : (($article['title'] ?? 'Article Title') . ' | TalentHub Insights')) ?>
          </div>
          <div id="art-serp-desc" class="text-[13px] text-[#4d5156] leading-relaxed mt-1 line-clamp-2">
            <?= esc(!empty($article['meta_description']) ? $article['meta_description'] : ($article['summary'] ?? 'Pune recruitment intelligence, hiring benchmarks, and engineering insights.')) ?>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-bold text-slate-700">Meta Title (50–60 chars)</label>
              <span id="art-title-cnt" class="text-[10px] font-mono font-bold text-slate-400">0 / 60</span>
            </div>
            <input type="text" id="art-meta-title" name="meta_title" value="<?= esc($article['meta_title'] ?? '') ?>" placeholder="Default: Article Title | TalentHub Insights" maxlength="80" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">Search Keywords (Comma-Separated)</label>
            <input type="text" name="meta_keywords" value="<?= esc($article['meta_keywords'] ?? '') ?>" placeholder="e.g. Pune tech salaries, Hinjawadi hiring trends, IT salary index" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5">
          </div>
          <div class="sm:col-span-2">
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-bold text-slate-700">Meta Description (150–160 chars for high Google CTR)</label>
              <span id="art-desc-cnt" class="text-[10px] font-mono font-bold text-slate-400">0 / 160</span>
            </div>
            <textarea id="art-meta-desc" name="meta_description" rows="2" placeholder="Default: Article Summary..." maxlength="200" class="w-full text-xs rounded-xl border-slate-200 bg-white p-2.5 leading-relaxed"><?= esc($article['meta_description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Publishing & Notification Options -->
      <div class="pt-4 border-t border-slate-100 space-y-3">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="is_published" value="1" <?= (!empty($article['is_published']) || empty($article)) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Publish Immediately (Make public on /insights)</span>
        </label>

        <label class="flex items-center gap-2.5 text-xs font-bold text-emerald-800 bg-emerald-50 px-3.5 py-2.5 rounded-xl border border-emerald-200 cursor-pointer">
          <input type="checkbox" name="notify_subscribers" value="1" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-emerald-300">
          <span>🚀 Automatically email this insight to all verified newsletter subscribers</span>
        </label>
      </div>

      <!-- Buttons -->
      <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <a href="<?= base_url('admin/content/articles') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
          Cancel
        </a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
          <?= !empty($article) ? 'Save Changes' : 'Publish Article' ?>
        </button>
      </div>

    </form>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const tInput = document.getElementById('art-meta-title');
  const dInput = document.getElementById('art-meta-desc');
  const tCnt = document.getElementById('art-title-cnt');
  const dCnt = document.getElementById('art-desc-cnt');
  const sTitle = document.getElementById('art-serp-title');
  const sDesc = document.getElementById('art-serp-desc');

  if (tInput && dInput) {
    function updateArtSeo() {
      const tLen = tInput.value.trim().length;
      sTitle.innerText = tInput.value.trim() || '<?= esc(addslashes(($article['title'] ?? 'Article Title') . ' | TalentHub Insights')) ?>';
      tCnt.innerHTML = `<span class="${tLen >= 45 && tLen <= 65 ? 'text-emerald-600 font-bold' : 'text-slate-400'}">${tLen} / 60 chars</span>`;

      const dLen = dInput.value.trim().length;
      sDesc.innerText = dInput.value.trim() || '<?= esc(addslashes($article['summary'] ?? 'Pune recruitment intelligence, hiring benchmarks, and engineering insights.')) ?>';
      dCnt.innerHTML = `<span class="${dLen >= 135 && dLen <= 165 ? 'text-emerald-600 font-bold' : 'text-slate-400'}">${dLen} / 160 chars</span>`;
    }
    tInput.addEventListener('input', updateArtSeo);
    dInput.addEventListener('input', updateArtSeo);
    updateArtSeo();
  }
});
</script>
