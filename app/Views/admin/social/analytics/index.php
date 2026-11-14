<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Analytics &amp; Performance Reports</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Audited audience reach, cross-platform publishing metrics, and organic conversion tracking.</p>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= base_url('admin/social/analytics') ?>" class="flex flex-wrap items-center gap-2">
      <select name="range" onchange="this.form.submit()" class="text-xs font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-brand-accent">
        <option value="7_days" <?= ($selectedRange === '7_days') ? 'selected' : '' ?>>Last 7 Days</option>
        <option value="30_days" <?= ($selectedRange === '30_days') ? 'selected' : '' ?>>Last 30 Days</option>
        <option value="90_days" <?= ($selectedRange === '90_days') ? 'selected' : '' ?>>Last 90 Days</option>
        <option value="all" <?= ($selectedRange === 'all') ? 'selected' : '' ?>>All Time</option>
      </select>

      <select name="platform" onchange="this.form.submit()" class="text-xs font-semibold bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-700 focus:ring-2 focus:ring-brand-accent">
        <option value="all" <?= ($selectedPlatform === 'all') ? 'selected' : '' ?>>All Platforms</option>
        <option value="linkedin" <?= ($selectedPlatform === 'linkedin') ? 'selected' : '' ?>>LinkedIn</option>
        <option value="instagram" <?= ($selectedPlatform === 'instagram') ? 'selected' : '' ?>>Instagram</option>
      </select>
    </form>
  </div>

  <!-- KPI Grid -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Total Impressions</span>
      <span class="text-3xl font-extrabold text-slate-900 block mt-2"><?= number_format($kpis['total_impressions']) ?></span>
      <span class="text-[11px] text-slate-400 mt-1 block">API Telemetry</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Audience Reach</span>
      <span class="text-3xl font-extrabold text-slate-900 block mt-2"><?= number_format($kpis['total_reach']) ?></span>
      <span class="text-[11px] text-slate-400 mt-1 block">Unique Viewers</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Engagement Rate</span>
      <span class="text-3xl font-extrabold text-brand-primary block mt-2"><?= number_format($kpis['engagement_rate'], 2) ?>%</span>
      <span class="text-[11px] text-slate-400 mt-1 block">(Likes + Comm + Shares + Clicks) / Imp</span>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block">Lead Conversions</span>
      <span class="text-3xl font-extrabold text-emerald-600 block mt-2"><?= number_format($kpis['converted_leads']) ?></span>
      <span class="text-[11px] text-slate-400 mt-1 block"><?= $kpis['total_enquiries'] ?> total captured leads</span>
    </div>
  </div>

  <!-- Chart & Metrics Documentation -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
      <h2 class="text-base font-bold text-slate-900 mb-1">Cross-Platform Publishing Velocity</h2>
      <p class="text-xs text-slate-500 mb-4">Daily volume comparison between LinkedIn and Instagram feeds</p>
      <div class="h-64">
        <canvas id="analyticsTrendsChart"></canvas>
      </div>
    </div>

    <!-- Methodology & Attribution Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm space-y-3 text-xs">
      <h2 class="text-base font-bold text-slate-900 mb-1">Audited Formulas</h2>
      <p class="text-slate-500 leading-relaxed">
        All engagement and reach computations adhere to official platform specifications:
      </p>

      <div class="space-y-2 pt-2 border-t border-slate-100">
        <div>
          <span class="font-bold text-slate-800 block">LinkedIn Engagement Rate:</span>
          <span class="text-slate-600 font-mono text-[11px] block bg-slate-50 p-1.5 rounded border border-slate-200 mt-0.5">
            (clicks + likes + comments + shares) / impressions &times; 100
          </span>
        </div>

        <div>
          <span class="font-bold text-slate-800 block">Instagram Engagement Rate:</span>
          <span class="text-slate-600 font-mono text-[11px] block bg-slate-50 p-1.5 rounded border border-slate-200 mt-0.5">
            (likes + comments + saves) / reach &times; 100
          </span>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100">
        <span class="text-[11px] text-slate-400 block leading-tight">
          Note: Zero synthetic or fabricated figures are injected. Where external platform APIs restrict historical access, metrics are explicitly labelled as unavailable.
        </span>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctx = document.getElementById('analyticsTrendsChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($trends['labels']) ?>,
        datasets: [
          {
            label: 'LinkedIn',
            data: <?= json_encode($trends['linkedin']) ?>,
            borderColor: '#0A66C2',
            backgroundColor: 'rgba(10, 102, 194, 0.08)',
            fill: true,
            tension: 0.3,
          },
          {
            label: 'Instagram',
            data: <?= json_encode($trends['instagram']) ?>,
            borderColor: '#E4405F',
            backgroundColor: 'rgba(228, 64, 95, 0.08)',
            fill: true,
            tension: 0.3,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
});
</script>
<?= $this->endSection() ?>

