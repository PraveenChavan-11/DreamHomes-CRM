<?php
/**
 * TalentHub Consultant — Insights Article Detail View with Schema.org/Article
 * Professional Editorial Typography, Safe Sanitized Rich Text & Sharing Controls
 */

$postTitle = esc($post['title']);
$postDate = date('c', strtotime($post['published_at'] ?? 'now'));
$authorName = esc(($post['first_name'] ?? 'TalentHub') . ' ' . ($post['last_name'] ?? 'Research Team'));
$featuredImage = !empty($post['featured_image']) ? esc($post['featured_image']) : base_url('logo.png');
$rawCurrentUrl = current_url();
$cleanCanonical = preg_replace('#/index\.php(/|$)#i', '$1', $rawCurrentUrl);
$cleanCanonical = preg_replace('/\.php$/i', '', $cleanCanonical);
$cleanCanonical = preg_replace('#(?<!:)//+#', '/', $cleanCanonical);
$canonicalUrl = rtrim($cleanCanonical, '/');
$wordCount = str_word_count(strip_tags($post['content'] ?? ''));
$readTime = max(2, (int) ceil($wordCount / 200));
?>

<!-- Schema.org Article Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<?= $postTitle ?>",
  "description": "<?= esc($post['summary']) ?>",
  "image": "<?= $featuredImage ?>",
  "datePublished": "<?= $postDate ?>",
  "dateModified": "<?= date('c', strtotime($post['updated_at'] ?? $postDate)) ?>",
  "author": {
    "@type": "Organization",
    "name": "TalentHub Consultant"
  },
  "publisher": {
    "@type": "Organization",
    "name": "TalentHub Consultant",
    "logo": {
      "@type": "ImageObject",
      "url": "<?= base_url('logo.png') ?>"
    }
  },
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?= $canonicalUrl ?>"
  }
}
</script>

<div class="pt-32 th-page pb-20 bg-[#FAF9F5] min-h-screen">
  <div class="page-width max-w-4xl">

    <!-- BREADCRUMBS -->
    <nav class="flex items-center gap-2 text-xs text-[#3d6350] mb-6 font-semibold" aria-label="Breadcrumb">
      <a href="<?= base_url() ?>" class="hover:text-[#1D6A34] transition-colors">Home</a>
      <span>/</span>
      <a href="<?= base_url('insights') ?>" class="hover:text-[#1D6A34] transition-colors">Insights</a>
      <span>/</span>
      <span class="text-[#172B22] truncate max-w-sm font-bold"><?= $postTitle ?></span>
    </nav>

    <!-- ARTICLE HEADER -->
    <header class="space-y-4 mb-8">
      <div class="flex items-center gap-3">
        <span class="badge-primary text-xs">
          <?= esc($post['category_name'] ?? 'Recruitment Insights') ?>
        </span>
        <span class="text-xs text-[#3d6350] font-semibold"><?= $readTime ?> min read</span>
      </div>

      <h1 class="th-h1 th-h1--ink">
        <?= $postTitle ?>
      </h1>

      <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-b border-[rgba(29,106,52,0.12)] pb-4 text-xs text-[#3d6350]">
        <p class="flex items-center gap-2 font-medium">
          <span class="font-bold text-[#172B22]">By <?= $authorName ?></span>
          <span class="text-gray-300">•</span>
          <span>Published <?= date('F d, Y', strtotime($post['published_at'] ?? 'now')) ?></span>
        </p>

        <!-- Share Article Buttons (WhatsApp, LinkedIn, Facebook, Email, Copy Link) -->
        <div class="flex items-center gap-1.5">
          <span class="font-semibold text-[#172B22] text-xs mr-1">Share:</span>
          <!-- WhatsApp -->
          <a href="https://api.whatsapp.com/send?text=<?= urlencode($postTitle . ' ' . $canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="p-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition" title="Share on WhatsApp">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
          </a>
          <!-- LinkedIn -->
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="p-2 rounded-xl border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition" title="Share on LinkedIn">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
          </a>
          <!-- Facebook -->
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="p-2 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition" title="Share on Facebook">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8H6v4h3v12h5V12h3.642L18 8h-4V6.333C14 5.374 14.5 5 15.6 5H18V0h-3.808C10.597 0 9 1.583 9 4.615V8z"/></svg>
          </a>
          <!-- Email -->
          <a href="mailto:?subject=<?= rawurlencode($postTitle) ?>&body=<?= rawurlencode('Read this recruitment insight from TalentHub: ' . $canonicalUrl) ?>" class="p-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition" title="Share via Email">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          </a>
          <!-- Copy Link -->
          <button type="button" onclick="navigator.clipboard.writeText('<?= $canonicalUrl ?>'); if(typeof window.showToast==='function') window.showToast('Article link copied to clipboard!', 'success');" class="p-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition cursor-pointer" title="Copy Link">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          </button>
        </div>
      </div>
    </header>

    <?php if (!empty($post['featured_image'])): ?>
      <div class="card-premium overflow-hidden mb-10 shadow-md bg-white">
        <img src="<?= $featuredImage ?>" alt="<?= $postTitle ?>" class="w-full max-h-[460px] object-cover">
      </div>
    <?php endif; ?>

    <!-- ARTICLE BODY CONTENT -->
    <article class="card-premium p-8 sm:p-12 bg-white space-y-6 text-[#172B22] text-sm sm:text-base leading-relaxed font-sans shadow-sm">
      <?php if (!empty($post['summary'])): ?>
        <p class="text-base sm:text-lg text-[#3d6350] font-medium leading-relaxed italic border-l-4 border-[#37CE97] pl-4 my-2">
          <?= esc($post['summary']) ?>
        </p>
      <?php endif; ?>

      <div class="prose max-w-none text-[#172B22]/90 space-y-4">
        <?= $post['content'] ?>
      </div>
    </article>

    <!-- INLINE NEWSLETTER CALLOUT -->
    <div class="mt-8 card-premium p-6 sm:p-8 bg-white border border-[#1D6A34]/20 rounded-3xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="space-y-1 max-w-md">
        <span class="badge-primary text-[10px]">Newsletter Intelligence</span>
        <h3 class="text-lg font-bold text-[#172B22]">Never Miss Pune Hiring Trends</h3>
        <p class="text-xs text-[#3d6350] leading-relaxed">
          Join 1,000+ Pune engineering leaders and professionals receiving weekly Hinjawadi &amp; Chakan talent updates.
        </p>
      </div>
      <form data-newsletter="true" data-source="insights_article" class="w-full md:max-w-xs space-y-2">
        <div class="flex gap-2">
          <input type="email" name="email" required placeholder="Enter work email..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-[#1D6A34]">
          <input type="text" name="website_url" style="display:none;" tabindex="-1">
          <button type="submit" class="btn-primary text-xs font-bold py-2 px-4 shrink-0">
            Join
          </button>
        </div>
        <label class="flex items-center gap-1.5 text-[10px] text-slate-500 cursor-pointer">
          <input type="checkbox" name="consent" value="1" required checked class="rounded border-slate-300 text-brand-primary">
          <span>I consent to weekly updates</span>
        </label>
      </form>
    </div>

    <!-- INLINE RECRUITMENT CALLOUT -->
    <div class="mt-12 card-premium p-8 bg-[linear-gradient(145deg,#FAF9F5,#EAF5EF)] border border-[#1D6A34]/20 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
      <div class="space-y-1 max-w-md">
        <h3 class="text-lg font-bold text-[#172B22]">Partner with Pune's Recruitment Specialists</h3>
        <p class="text-xs text-[#3d6350] leading-relaxed">
          Need assistance executing your regional talent acquisition strategy or scaling your Pune tech teams?
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-3 shrink-0">
        <a href="<?= base_url('employers') ?>" class="btn-primary text-xs font-bold py-3 px-5">
          <span>Hire Talent</span>
        </a>
        <a href="<?= base_url('jobs') ?>" class="btn-outline text-xs font-bold py-3 px-5">
          <span>Explore Openings</span>
        </a>
      </div>
    </div>

    <!-- RELATED ARTICLES -->
    <?php if (!empty($relatedPosts)): ?>
      <div class="mt-16 space-y-6">
        <h3 class="text-xl font-bold text-[#172B22]">More Market Insights</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <?php foreach ($relatedPosts as $r): ?>
            <div class="card-premium p-5 bg-white flex flex-col justify-between">
              <div>
                <span class="badge-primary text-[10px] mb-2 inline-block"><?= esc($r['category_name'] ?? 'Trends') ?></span>
                <h4 class="text-sm font-bold text-[#172B22] mb-1.5 hover:text-[#1D6A34] leading-snug">
                  <a href="<?= base_url('insights/' . $r['slug']) ?>"><?= esc($r['title']) ?></a>
                </h4>
                <p class="text-xs text-[#3d6350] line-clamp-2"><?= esc($r['summary']) ?></p>
              </div>
              <div class="pt-3 mt-3 border-t border-[rgba(29,106,52,0.1)] flex justify-between items-center text-xs">
                <span class="text-[11px] text-[#3d6350]"><?= date('M d, Y', strtotime($r['published_at'] ?? 'now')) ?></span>
                <a href="<?= base_url('insights/' . $r['slug']) ?>" class="font-bold text-[#1D6A34] hover:underline">Read &rarr;</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>
