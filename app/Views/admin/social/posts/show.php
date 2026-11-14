<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex items-center justify-between border-b border-slate-200 pb-5">
    <div>
      <div class="flex items-center gap-2">
        <a href="<?= base_url('admin/social/posts') ?>" class="text-xs font-semibold text-slate-400 hover:text-slate-600">&larr; Back to Posts</a>
        <span class="text-slate-300">/</span>
        <span class="text-xs font-bold text-brand-primary">Post #<?= esc($post['id']) ?></span>
      </div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight mt-1"><?= esc($post['title']) ?></h1>
      <?php if (($post['origin'] ?? 'admin') === 'agent'): ?>
        <p class="mt-1 inline-flex items-center gap-2 text-xs font-bold px-2.5 py-1 rounded-full bg-violet-50 text-violet-800 border border-violet-200">AI agent draft — <?= !empty($post['review_decision']) ? esc(str_replace('_', ' ', $post['review_decision'])) : 'not yet approved' ?>
          <a class="underline" href="<?= base_url('admin/agents/approvals/' . (int) $post['id']) ?>">review</a></p>
      <?php endif; ?>
    </div>

    <div class="flex items-center gap-2.5">
      <?php if ($post['status'] !== 'published'): ?>
        <form method="POST" action="<?= base_url('admin/social/posts/' . $post['id'] . '/publish') ?>" onsubmit="return confirm('Publish this post immediately?');">
          <?= csrf_field() ?>
          <button type="submit" class="px-4 py-2 bg-brand-primary text-white text-xs font-bold rounded-xl hover:bg-brand-hover transition shadow-sm">
            Publish Now
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Content & Platform Cards Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- LEFT: Post Copy & Media (2 cols) -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Copy Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Post Content &amp; Copy</h2>
        <div class="text-xs md:text-sm text-slate-800 leading-relaxed whitespace-pre-line p-4 bg-slate-50 rounded-xl border border-slate-200">
          <?= esc($post['base_content']) ?>
        </div>

        <?php if (!empty($post['base_hashtags'])): ?>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Hashtags:</span>
            <span class="text-xs font-semibold text-brand-primary"><?= esc($post['base_hashtags']) ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($post['base_cta'])): ?>
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Call to Action:</span>
            <span class="text-xs font-bold text-slate-700"><?= esc($post['base_cta']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Media Attachments -->
      <?php if (!empty($media)): ?>
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
          <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Attached Media (<?= count($media) ?>)</h2>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <?php foreach ($media as $m): ?>
              <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 aspect-video flex items-center justify-center">
                <?php if ($m['media_type'] === 'image'): ?>
                  <img src="<?= esc($m['file_url']) ?>" class="w-full h-full object-cover">
                <?php elseif ($m['media_type'] === 'video'): ?>
                  <video src="<?= esc($m['file_url']) ?>" controls class="w-full h-full object-cover"></video>
                <?php else: ?>
                  <span class="text-xs font-bold text-slate-500">📄 PDF Document</span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Execution Attempt Logs -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Publishing Attempts &amp; API Responses</h2>
        <?php if (empty($attempts)): ?>
          <p class="text-xs text-slate-400">No publishing attempts recorded yet.</p>
        <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($attempts as $att): ?>
              <div class="p-3.5 rounded-xl border <?= ($att['status'] === 'success') ? 'bg-emerald-50/50 border-emerald-200' : 'bg-rose-50/50 border-rose-200' ?> text-xs space-y-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-slate-800 capitalize"><?= esc($att['platform']) ?> — Attempt #<?= esc($att['attempt_number']) ?></span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase <?= ($att['status'] === 'success') ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' ?>">
                    <?= esc($att['status']) ?>
                  </span>
                </div>
                <div class="text-[11px] text-slate-500">
                  Executed at <?= esc($att['started_at']) ?> • HTTP <?= esc($att['http_status'] ?? '—') ?>
                </div>
                <?php if (!empty($att['error_message'])): ?>
                  <p class="text-rose-600 font-medium text-[11px] mt-1"><?= esc($att['error_message']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <!-- RIGHT: Status & Platform Targets (1 col) -->
    <div class="space-y-6">

      <!-- Status Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Publishing Status</h2>

        <div>
          <span class="text-xs text-slate-400 block">Overall Post State:</span>
          <span class="text-base font-extrabold text-slate-900 capitalize"><?= esc($post['status']) ?></span>
        </div>

        <?php if (!empty($post['scheduled_at'])): ?>
          <div>
            <span class="text-xs text-slate-400 block">Scheduled Time:</span>
            <span class="text-xs font-bold text-amber-700"><?= esc($post['scheduled_at']) ?> (IST)</span>
          </div>
        <?php endif; ?>

        <?php if (!empty($post['published_at'])): ?>
          <div>
            <span class="text-xs text-slate-400 block">Published At:</span>
            <span class="text-xs font-bold text-emerald-700"><?= esc($post['published_at']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Platform Targets -->
      <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Target Platforms</h2>

        <div class="space-y-3">
          <?php foreach ($platforms as $pp): ?>
            <div class="p-3 rounded-xl border border-slate-200 space-y-1.5 text-xs">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 capitalize"><?= esc($pp['platform']) ?></span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($pp['status'] === 'published') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>">
                  <?= esc(ucfirst($pp['status'])) ?>
                </span>
              </div>

              <?php if (!empty($pp['external_permalink'])): ?>
                <a href="<?= esc($pp['external_permalink']) ?>" target="_blank" class="text-brand-primary font-bold hover:underline block text-[11px] truncate">
                  View Live on <?= esc(ucfirst($pp['platform'])) ?> &rarr;
                </a>
              <?php endif; ?>

              <?php if (!empty($pp['error_message'])): ?>
                <p class="text-rose-600 text-[11px]"><?= esc($pp['error_message']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

  </div>

</div>
<?= $this->endSection() ?>

