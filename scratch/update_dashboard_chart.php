<?php
$path = 'app/Views/admin/dashboard/index.php';
$content = file_get_contents($path);

// 1. Update canvas container
$targetCanvas = '      <div class="relative h-56 w-full flex items-center justify-center">
        <canvas id="urgencyChart"></canvas>
      </div>';

$replaceCanvas = '      <div class="relative h-56 w-full flex items-center justify-center">
        <canvas id="urgencyChart"></canvas>
        <div id="urgencyCenterBadge" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
          <?php $totalUrgency = array_sum($chartData[\'urgency\'] ?? []); ?>
          <?php if ($totalUrgency > 0): ?>
            <span class="text-3xl font-black text-slate-800"><?= $totalUrgency ?></span>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Mandates</span>
          <?php else: ?>
            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-1">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <span class="text-xs font-bold text-slate-500">Awaiting Leads</span>
            <span class="text-[10px] text-slate-400">0 Active Mandates</span>
          <?php endif; ?>
        </div>
      </div>';

// 2. Update chart script
$targetJs = '  // 2. Corporate Urgency Doughnut Chart
  const urgencyCtx = document.getElementById(\'urgencyChart\');
  if (urgencyCtx && typeof Chart !== \'undefined\') {
    new Chart(urgencyCtx, {
      type: \'doughnut\',
      data: {
        labels: [\'Immediate\', \'15 Days\', \'30 Days\', \'Pipeline\'],
        datasets: [{
          data: [
            <?= (int) ($chartData[\'urgency\'][\'Immediate\'] ?? 0) ?>,
            <?= (int) ($chartData[\'urgency\'][\'15 Days\'] ?? 0) ?>,
            <?= (int) ($chartData[\'urgency\'][\'30 Days\'] ?? 0) ?>,
            <?= (int) ($chartData[\'urgency\'][\'Pipeline\'] ?? 0) ?>
          ],
          backgroundColor: [\'#ef4444\', \'#f59e0b\', \'#0284c7\', \'#10b981\'],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: \'72%\',
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: \'#072412\',
            padding: 10,
            cornerRadius: 10
          }
        }
      }
    });
  }';

$replaceJs = '  // 2. Corporate Urgency Doughnut Chart
  const urgencyCtx = document.getElementById(\'urgencyChart\');
  if (urgencyCtx && typeof Chart !== \'undefined\') {
    const rawUrgency = [
      <?= (int) ($chartData[\'urgency\'][\'Immediate\'] ?? 0) ?>,
      <?= (int) ($chartData[\'urgency\'][\'15 Days\'] ?? 0) ?>,
      <?= (int) ($chartData[\'urgency\'][\'30 Days\'] ?? 0) ?>,
      <?= (int) ($chartData[\'urgency\'][\'Pipeline\'] ?? 0) ?>
    ];
    const totalUrgency = rawUrgency.reduce((acc, curr) => acc + curr, 0);

    if (totalUrgency === 0) {
      new Chart(urgencyCtx, {
        type: \'doughnut\',
        data: {
          labels: [\'No Active Mandates\'],
          datasets: [{
            data: [1],
            backgroundColor: [\'#E2E8F0\'],
            borderWidth: 0,
            hoverOffset: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: \'74%\',
          plugins: {
            legend: { display: false },
            tooltip: {
              enabled: true,
              callbacks: {
                label: function() { return \'Awaiting Inbound Corporate Mandates\'; }
              }
            }
          }
        }
      });
    } else {
      new Chart(urgencyCtx, {
        type: \'doughnut\',
        data: {
          labels: [\'Immediate\', \'15 Days\', \'30 Days\', \'Pipeline\'],
          datasets: [{
            data: rawUrgency,
            backgroundColor: [\'#ef4444\', \'#f59e0b\', \'#0284c7\', \'#10b981\'],
            borderWidth: 0,
            hoverOffset: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: \'72%\',
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: \'#072412\',
              padding: 10,
              cornerRadius: 10
            }
          }
        }
      });
    }
  }';

$content_norm = str_replace("\r\n", "\n", $content);
$targetCanvas_norm = str_replace("\r\n", "\n", $targetCanvas);
$replaceCanvas_norm = str_replace("\r\n", "\n", $replaceCanvas);
$targetJs_norm = str_replace("\r\n", "\n", $targetJs);
$replaceJs_norm = str_replace("\r\n", "\n", $replaceJs);

if (strpos($content_norm, $targetCanvas_norm) !== false && strpos($content_norm, $targetJs_norm) !== false) {
    $content_norm = str_replace($targetCanvas_norm, $replaceCanvas_norm, $content_norm);
    $content_norm = str_replace($targetJs_norm, $replaceJs_norm, $content_norm);
    file_put_contents($path, $content_norm);
    echo "SUCCESS: dashboard/index.php urgencyChart updated!\n";
} else {
    echo "FAILED: markers not found in dashboard/index.php\n";
}

