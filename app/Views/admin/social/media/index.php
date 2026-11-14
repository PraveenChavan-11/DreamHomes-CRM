<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<?php
  $categories = [
      'corporate_branding' => 'Corporate branding',
      'product'            => 'Product',
      'campaign'           => 'Campaign',
      'event'              => 'Event',
      'other'              => 'Other',
  ];
  $fmtSize = static function (int $b): string {
      return $b >= 1048576 ? number_format($b / 1048576, 1) . ' MB' : number_format(max(1, $b) / 1024, 0) . ' KB';
  };
  $filtered = ($searchQuery !== '' || $filterCategory !== '' || $filterType !== '');
?>
<div class="space-y-6 pb-16">

  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Marketing Media Library</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5"><?= (int) $total ?> asset<?= (int) $total === 1 ? '' : 's' ?> available for campaigns and posts.</p>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div role="status" class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>

  <!-- Upload -->
  <form id="media-upload-form" class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm flex flex-col md:flex-row md:items-end gap-4" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="flex-1">
      <label for="media_file" class="block text-xs font-bold text-slate-700 mb-1">Upload asset</label>
      <input id="media_file" name="media_file" type="file" required accept="image/*,video/*,.pdf"
             class="block w-full text-sm text-slate-700 border border-slate-300 rounded-xl p-2">
    </div>
    <div>
      <label for="media_category" class="block text-xs font-bold text-slate-700 mb-1">Category</label>
      <select id="media_category" name="category" class="border border-slate-300 rounded-xl px-3 py-2.5 text-sm bg-white">
        <?php foreach ($categories as $k => $label): ?>
          <option value="<?= esc($k) ?>"><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button id="media-upload-btn" type="submit" class="px-5 py-2.5 rounded-xl bg-[#1D6A34] text-white text-sm font-bold hover:bg-[#1A532A] transition disabled:opacity-60">Upload</button>
  </form>
  <p id="media-upload-msg" class="text-sm" role="alert" aria-live="polite"></p>

  <!-- Filters -->
  <form method="get" class="flex flex-wrap items-end gap-3" role="search">
    <div>
      <label for="q" class="block text-xs font-bold text-slate-700 mb-1">Search</label>
      <input id="q" name="q" value="<?= esc($searchQuery, 'attr') ?>" placeholder="Title, tag or file name" class="border border-slate-300 rounded-xl px-3 py-2 text-sm">
    </div>
    <div>
      <label for="f_category" class="block text-xs font-bold text-slate-700 mb-1">Category</label>
      <select id="f_category" name="category" class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="">All</option>
        <?php foreach ($categories as $k => $label): ?>
          <option value="<?= esc($k) ?>" <?= $filterCategory === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="f_type" class="block text-xs font-bold text-slate-700 mb-1">Type</label>
      <select id="f_type" name="type" class="border border-slate-300 rounded-xl px-3 py-2 text-sm bg-white">
        <option value="">All</option>
        <?php foreach (['image' => 'Image', 'video' => 'Video', 'document' => 'Document'] as $k => $label): ?>
          <option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-bold text-slate-700 hover:bg-slate-50">Filter</button>
    <?php if ($filtered): ?>
      <a href="<?= base_url('admin/social/media') ?>" class="text-sm text-slate-500 underline py-2">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($mediaItems)): ?>
    <div class="bg-white rounded-3xl border border-dashed border-slate-300 p-10 text-center text-slate-500 text-sm">
      <?= $filtered ? 'No assets match these filters.' : 'No media yet. Upload your first asset above.' ?>
    </div>
  <?php else: ?>
    <ul class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
      <?php foreach ($mediaItems as $m): ?>
        <li class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm flex flex-col">
          <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden">
            <?php if (($m['media_type'] ?? '') === 'image'): ?>
              <img src="<?= esc($m['thumbnail_url'] ?: $m['file_url'], 'attr') ?>" alt="<?= esc($m['title'], 'attr') ?>" loading="lazy" class="w-full h-full object-cover">
            <?php else: ?>
              <span class="text-xs font-bold uppercase text-slate-500"><?= esc($m['media_type']) ?></span>
            <?php endif; ?>
          </div>
          <div class="p-3 text-xs space-y-1 flex-1">
            <p class="font-bold text-slate-900 truncate" title="<?= esc($m['title'], 'attr') ?>"><?= esc($m['title']) ?></p>
            <p class="text-slate-500"><?= esc($categories[$m['category']] ?? $m['category']) ?> · <?= esc($fmtSize((int) $m['file_size'])) ?></p>
          </div>
          <div class="px-3 pb-3 flex items-center justify-between gap-2">
            <a href="<?= esc($m['file_url'], 'attr') ?>" target="_blank" rel="noopener" class="text-xs font-bold text-[#1D6A34] underline">Open</a>
            <form method="post" action="<?= base_url('admin/social/media/' . (int) $m['id'] . '/delete') ?>"
                  onsubmit="return confirm('Delete this asset permanently?');">
              <?= csrf_field() ?>
              <button class="text-xs font-bold text-red-600 hover:underline" aria-label="Delete <?= esc($m['title'], 'attr') ?>">Delete</button>
            </form>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<script>
(function () {
  var form = document.getElementById('media-upload-form');
  var btn = document.getElementById('media-upload-btn');
  var msg = document.getElementById('media-upload-msg');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (btn.disabled) return;
    btn.disabled = true; msg.className = 'text-sm text-slate-600'; msg.textContent = 'Uploading…';
    fetch('<?= base_url('admin/social/media/upload') ?>', {
      method: 'POST', body: new FormData(form), credentials: 'same-origin',
      headers: {'X-Requested-With': 'XMLHttpRequest'}
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (d && d.success) { window.location.reload(); return; }
      msg.className = 'text-sm text-red-700'; msg.textContent = (d && d.message) || 'Upload failed.';
      btn.disabled = false;
    }).catch(function () {
      msg.className = 'text-sm text-red-700'; msg.textContent = 'Network error. Please try again.';
      btn.disabled = false;
    });
  });
})();
</script>
<?= $this->endSection() ?>
