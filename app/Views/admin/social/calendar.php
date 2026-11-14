<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Content Marketing Calendar</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Visualize publishing cadence, avoid campaign collisions, and reschedule posts effortlessly.</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/social/posts/create') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl text-xs font-bold hover:bg-brand-hover transition shadow-sm shadow-brand-primary/20">
        <svg class="w-4 h-4 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Schedule New Post</span>
      </a>
    </div>
  </div>

  <!-- Legend & Month Controls -->
  <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-4 text-xs font-semibold">
      <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Published</span>
      <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Scheduled</span>
      <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Draft</span>
    </div>

    <div class="flex items-center gap-2 text-xs font-bold">
      <span class="text-slate-700"><?= esc(date('F Y')) ?></span>
    </div>
  </div>

  <!-- Calendar Grid (Monthly View) -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <!-- Day Header -->
    <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200 text-center py-2 text-xs font-extrabold uppercase text-slate-500">
      <div>Sun</div>
      <div>Mon</div>
      <div>Tue</div>
      <div>Wed</div>
      <div>Thu</div>
      <div>Fri</div>
      <div>Sat</div>
    </div>

    <!-- Month Days Grid -->
    <?php
      $currentYear  = (int) date('Y');
      $currentMonth = (int) date('m');
      $daysInMonth  = (int) date('t');
      $firstDayOfWeek = (int) date('w', strtotime("{$currentYear}-{$currentMonth}-01"));

      // Group posts by date
      $postsByDate = [];
      foreach ($posts as $p) {
          $dateKey = !empty($p['scheduled_at']) ? substr($p['scheduled_at'], 0, 10) : substr($p['created_at'], 0, 10);
          $postsByDate[$dateKey][] = $p;
      }
    ?>

    <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 text-xs min-h-[560px]">
      <!-- Blank days prior to month start -->
      <?php for ($b = 0; $b < $firstDayOfWeek; $b++): ?>
        <div class="bg-slate-50/50 p-2 min-h-[100px]"></div>
      <?php endfor; ?>

      <!-- Month Days -->
      <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
        <?php
          $dayStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $d);
          $isToday = ($dayStr === date('Y-m-d'));
          $dayPosts = $postsByDate[$dayStr] ?? [];
        ?>
        <div class="p-2 min-h-[100px] flex flex-col justify-between hover:bg-slate-50/70 transition <?= $isToday ? 'bg-brand-light/30' : '' ?>">
          <div class="flex items-center justify-between mb-1">
            <span class="font-extrabold <?= $isToday ? 'w-5 h-5 rounded-full bg-brand-primary text-white flex items-center justify-center text-[10px]' : 'text-slate-700' ?>">
              <?= $d ?>
            </span>
            <?php if (!empty($dayPosts)): ?>
              <span class="text-[10px] font-bold text-slate-400"><?= count($dayPosts) ?></span>
            <?php endif; ?>
          </div>

          <!-- Post Pills -->
          <div class="space-y-1 overflow-y-auto max-h-20 scrollbar-none">
            <?php foreach ($dayPosts as $dp): ?>
              <?php
                $pillColor = 'bg-slate-100 text-slate-700 border-slate-200';
                if ($dp['status'] === 'published') $pillColor = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                elseif ($dp['status'] === 'scheduled') $pillColor = 'bg-amber-50 text-amber-800 border-amber-200';
              ?>
              <a href="<?= base_url('admin/social/posts/' . $dp['id']) ?>" class="block p-1 rounded-md text-[10px] font-bold border truncate <?= $pillColor ?>" title="<?= esc($dp['title']) ?>">
                <?= esc(mb_strimwidth($dp['title'], 0, 18, '..')) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>

</div>
<?= $this->endSection() ?>

