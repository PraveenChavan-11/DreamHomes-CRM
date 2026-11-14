<?php
use App\Services\ItLeadService as L;
/** @var array $crm  @var array $crmProducts @var array $crmSources */
$f = $crm['filters'];
$rg = $crm['range'];
$tile = static function (string $label, $value, string $sub = '', string $href = '') {
    $inner = '<p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">' . esc($label) . '</p><p class="mt-1 text-2xl font-extrabold text-slate-900">' . esc((string) $value) . '</p>'
        . ($sub !== '' ? '<p class="text-[11px] text-slate-500 mt-0.5">' . esc($sub) . '</p>' : '');
    $cls = 'block bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm';
    return $href !== '' ? '<a href="' . esc($href, 'attr') . '" class="' . $cls . ' hover:border-brand-primary transition">' . $inner . '</a>' : '<div class="' . $cls . '">' . $inner . '</div>';
};
$bars = static function (array $data, string $color) {
    if (!$data) {
        return '<p class="text-xs text-slate-400">No data in this period.</p>';
    }
    $max = max($data);
    $h = '<ul class="space-y-1.5">';
    foreach ($data as $k => $n) {
        $w = $max > 0 ? max(2, (int) round($n / $max * 100)) : 0;
        $h .= '<li class="text-xs"><div class="flex justify-between"><span class="text-slate-700">' . esc((string) $k) . '</span><span class="font-bold text-slate-900">' . (int) $n . '</span></div><div class="h-1.5 rounded bg-slate-100"><div class="h-1.5 rounded" style="width:' . $w . '%;background:' . $color . '"></div></div></li>';
    }
    return $h . '</ul>';
};
$statusLabelled = [];
foreach ($crm['by_status'] as $k => $n) {
    $statusLabelled[L::STATUSES[$k] ?? $k] = $n;
}
$sel = 'py-1.5 px-2.5 rounded-lg border border-slate-200 text-xs text-slate-700';
$fun = $crm['funnel'];
?>
<section aria-labelledby="crm-heading" class="mt-10 space-y-5">
  <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
    <div>
      <h2 id="crm-heading" class="text-lg font-extrabold text-slate-900">CRM &amp; WorkSphere pipeline</h2>
      <p class="text-xs text-slate-500">Live figures from the CRM for <?= esc($rg['from']) ?> to <?= esc($rg['to']) ?>. Open-pipeline tiles show the current state regardless of the range.</p>
    </div>
    <form method="GET" action="<?= base_url('admin/dashboard') ?>" class="flex flex-wrap items-center gap-2 text-xs">
      <?php if (!empty($_GET['period'])): ?><input type="hidden" name="period" value="<?= esc($_GET['period']) ?>"><?php endif; ?>
      <label class="sr-only" for="crm_range">Date range</label>
      <select id="crm_range" name="crm_range" class="<?= $sel ?>" onchange="document.getElementById('crm-custom').style.display = this.value === 'custom' ? 'flex' : 'none'">
        <?php foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days', 'custom' => 'Custom'] as $k => $v): ?><option value="<?= $k ?>" <?= $rg['range'] === (string) $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
      </select>
      <span id="crm-custom" class="items-center gap-1" style="display:<?= $rg['range'] === 'custom' ? 'flex' : 'none' ?>">
        <input aria-label="From date" type="date" name="crm_from" value="<?= esc($rg['from']) ?>" class="<?= $sel ?>"> – <input aria-label="To date" type="date" name="crm_to" value="<?= esc($rg['to']) ?>" class="<?= $sel ?>">
      </span>
      <select aria-label="Product" name="crm_product" class="<?= $sel ?>"><option value="">All products</option><?php foreach ($crmProducts as $p): ?><option value="<?= esc($p) ?>" <?= ($f['product'] ?? '') === $p ? 'selected' : '' ?>><?= esc($p) ?></option><?php endforeach; ?></select>
      <select aria-label="Source" name="crm_source" class="<?= $sel ?>"><option value="">All sources</option><?php foreach ($crmSources as $p): ?><option value="<?= esc($p) ?>" <?= ($f['source'] ?? '') === $p ? 'selected' : '' ?>><?= esc(str_replace('_', ' ', $p)) ?></option><?php endforeach; ?></select>
      <button class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-semibold">Apply</button>
    </form>
  </div>

  <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-4">
    <?= $tile('Leads in range', $crm['total'], $crm['new_today'] . ' today · ' . $crm['new_7d'] . ' in 7 d · ' . $crm['new_30d'] . ' in 30 d', base_url('admin/it-leads')) ?>
    <?= $tile('Conversion rate', $crm['conversion_rate'] !== null ? $crm['conversion_rate'] . '%' : 'n/a', $crm['conversion_rate'] !== null ? $crm['won'] . ' won / ' . $crm['lost'] . ' lost' : 'No closed leads yet') ?>
    <?= $tile('Overdue follow-ups', $crm['overdue_followups'], 'open leads', base_url('admin/it-leads?followup=due')) ?>
    <?= $tile('Unassigned open leads', $crm['unassigned'], '', base_url('admin/it-leads?assigned=none')) ?>
    <?= $tile('Avg first response', $crm['avg_first_contact_hours'] !== null ? $crm['avg_first_contact_hours'] . ' h' : 'n/a', $crm['avg_first_contact_hours'] !== null ? 'new → first status change' : 'No contacted leads yet') ?>
    <?= $tile('Pipeline value', $crm['pipeline_valued_leads'] ? '₹' . number_format($crm['pipeline_value']) : 'n/a', $crm['pipeline_valued_leads'] . ' open lead(s) with an estimate') ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 mb-3">New leads per day</h3>
      <?php if (array_sum($crm['trend']['values']) === 0): ?>
        <p class="text-xs text-slate-400 py-8 text-center">No leads received in this period.</p>
      <?php else: ?>
        <div class="relative h-56"><canvas id="crmTrend" role="img" aria-label="New leads per day, <?= esc($rg['from']) ?> to <?= esc($rg['to']) ?>"></canvas></div>
      <?php endif; ?>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 mb-3">WorkSphere funnel</h3>
      <?php if ($fun['initiated'] === 0 && $fun['failed_provisioning'] === 0): ?>
        <p class="text-xs text-slate-400">No WorkSphere registrations in this period.</p>
      <?php else: ?>
        <?= $bars(['Registered' => $fun['initiated'], 'Email verified' => $fun['verified'], 'Demo activated' => $fun['activated'], 'Converted to paid' => $fun['converted']], '#1D6A34') ?>
        <p class="text-xs mt-3 <?= $fun['failed_provisioning'] ? 'text-rose-700 font-semibold' : 'text-slate-500' ?>">Failed provisionings: <?= (int) $fun['failed_provisioning'] ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm"><h3 class="text-sm font-bold text-slate-900 mb-3">By status</h3><?= $bars($statusLabelled, '#0ea5e9') ?></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm"><h3 class="text-sm font-bold text-slate-900 mb-3">By source</h3><?= $bars($crm['by_source'], '#8b5cf6') ?></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm"><h3 class="text-sm font-bold text-slate-900 mb-3">By product</h3><?= $bars($crm['by_product'], '#f59e0b') ?></div>
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm"><h3 class="text-sm font-bold text-slate-900 mb-3">By category</h3><?= $bars($crm['by_category'], '#14b8a6') ?></div>
  </div>

  <?php $h = $crm['health']; ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?= $tile('Last WorkSphere event', $h['last_event'] ? date('d M, H:i', strtotime($h['last_event'])) : 'Never', $h['clients_active'] . ' active client(s)', base_url('admin/integrations')) ?>
    <?= $tile('Integration failures (24 h)', $h['failures_24h'], 'rejected or failed events', base_url('admin/integrations')) ?>
    <?= $tile('CRM emails failed', $h['email_failed'], 'after all retries') ?>
    <?= $tile('CRM emails pending', $h['email_pending'], 'waiting for the outbox worker') ?>
  </div>
</section>

<?php if (array_sum($crm['trend']['values']) > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('crmTrend');
  if (el && typeof Chart !== 'undefined') {
    new Chart(el, {
      type: 'bar',
      data: { labels: <?= json_encode($crm['trend']['labels']) ?>, datasets: [{ label: 'New leads', data: <?= json_encode($crm['trend']['values']) ?>, backgroundColor: '#1D6A34', borderRadius: 4 }] },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
  }
});
</script>
<?php endif; ?>
