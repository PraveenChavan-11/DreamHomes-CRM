<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<div class="space-y-6 pb-16">

  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">Activity &amp; Audit Logs</h1>
      <p class="text-xs md:text-sm text-slate-500 mt-0.5">Immutable audit trail of social media publishing, API interactions, scheduling changes, and account authentication.</p>
    </div>

    <!-- Security Notice Pill -->
    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        Zero Token Exposure (Encrypted / Masked at Rest)
      </span>
    </div>
  </div>

  <!-- Summary Stats -->
  <?php
    $totalLogs = count($logs);
    $successCount = 0;
    $failureCount = 0;
    $warningCount = 0;
    foreach ($logs as $l) {
        $res = strtolower($l['result'] ?? 'success');
        if ($res === 'success') $successCount++;
        elseif ($res === 'failure') $failureCount++;
        else $warningCount++;
    }
  ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Recorded Events</div>
      <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($totalLogs) ?></div>
      <div class="text-[11px] text-slate-500 mt-0.5">Latest 100 entries</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Successful Actions</div>
      <div class="text-2xl font-extrabold text-emerald-600 mt-1"><?= number_format($successCount) ?></div>
      <div class="text-[11px] text-slate-500 mt-0.5">Normal executions</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Failures &amp; Errors</div>
      <div class="text-2xl font-extrabold text-rose-600 mt-1"><?= number_format($failureCount) ?></div>
      <div class="text-[11px] text-slate-500 mt-0.5">Publishing or auth issues</div>
    </div>
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
      <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Warnings &amp; Retries</div>
      <div class="text-2xl font-extrabold text-amber-600 mt-1"><?= number_format($warningCount) ?></div>
      <div class="text-[11px] text-slate-500 mt-0.5">Rate limit &amp; queue retries</div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3">
    <div class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" id="logFilterSearch" placeholder="Filter by action, entity or IP..."
        class="w-full md:w-64 text-xs px-3.5 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:border-transparent">

      <select id="logFilterResult" class="text-xs px-3 py-2 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-primary">
        <option value="">All Results</option>
        <option value="success">Success</option>
        <option value="failure">Failure</option>
        <option value="warning">Warning</option>
      </select>
    </div>

    <div class="text-xs text-slate-500 w-full md:w-auto text-right">
      Showing <span id="visibleLogCount" class="font-bold text-slate-700"><?= $totalLogs ?></span> events
    </div>
  </div>

  <!-- Audit Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs" id="logsTable">
        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
          <tr>
            <th class="px-5 py-3">Timestamp</th>
            <th class="px-4 py-3">Action</th>
            <th class="px-4 py-3">Entity</th>
            <th class="px-4 py-3">Result</th>
            <th class="px-4 py-3">User / Actor</th>
            <th class="px-4 py-3">IP / Client</th>
            <th class="px-5 py-3 text-right">Payload</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                No social marketing activity logs recorded yet. Events will appear here as administrators publish posts or update settings.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <?php
                $res = strtolower($log['result'] ?? 'success');
                $resultBadgeClass = match($res) {
                    'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'failure' => 'bg-rose-50 text-rose-700 border-rose-200',
                    default   => 'bg-amber-50 text-amber-700 border-amber-200',
                };
                $meta = [];
                if (!empty($log['metadata_json'])) {
                    $decoded = is_string($log['metadata_json']) ? json_decode($log['metadata_json'], true) : $log['metadata_json'];
                    if (is_array($decoded)) $meta = $decoded;
                }
              ?>
              <tr class="hover:bg-slate-50/70 transition-colors log-row"
                  data-action="<?= esc(strtolower($log['action'] ?? '')) ?>"
                  data-entity="<?= esc(strtolower(($log['entity_type'] ?? '') . ' ' . ($log['entity_id'] ?? ''))) ?>"
                  data-result="<?= esc($res) ?>"
                  data-ip="<?= esc(strtolower($log['ip_address'] ?? '')) ?>">
                <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 font-mono text-[11px]">
                  <?= esc(date('Y-m-d H:i:s', strtotime($log['created_at'] ?? 'now'))) ?>
                </td>
                <td class="px-4 py-3.5 font-semibold text-slate-900">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 text-slate-800 border border-slate-200">
                    <?= esc($log['action'] ?? 'unknown') ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-slate-700">
                  <span class="font-medium text-slate-900"><?= esc($log['entity_type'] ?? '—') ?></span>
                  <?php if (!empty($log['entity_id'])): ?>
                    <span class="text-slate-400 font-mono text-[10px]">#<?= esc($log['entity_id']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-3.5">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $resultBadgeClass ?>">
                    <?= esc(ucfirst($res)) ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-slate-600">
                  <?= !empty($log['user_id']) ? 'User #' . esc($log['user_id']) : '<span class="text-slate-400 italic">System / CLI</span>' ?>
                </td>
                <td class="px-4 py-3.5 text-slate-500 font-mono text-[11px]">
                  <?= esc($log['ip_address'] ?? '—') ?>
                </td>
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <?php if (!empty($meta)): ?>
                    <button type="button"
                            onclick='openMetaModal(<?= json_encode($log['action'] ?? 'Details') ?>, <?= json_encode($meta) ?>)'
                            class="inline-flex items-center gap-1 px-2 py-1 rounded text-[11px] font-medium text-brand-primary bg-brand-light hover:bg-emerald-100 border border-brand-primary/20 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View
                    </button>
                  <?php else: ?>
                    <span class="text-slate-300 text-[11px]">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Metadata Detail Modal -->
<div id="metaModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
    <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
      <h3 class="text-sm font-bold text-slate-800" id="metaModalTitle">Log Metadata Details</h3>
      <button type="button" onclick="closeMetaModal()" class="text-slate-400 hover:text-slate-600 p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <div class="p-4">
      <pre id="metaModalContent" class="text-xs font-mono bg-slate-900 text-slate-100 p-4 rounded-xl overflow-x-auto max-h-96 whitespace-pre-wrap"></pre>
    </div>
    <div class="p-3 bg-slate-50 border-t border-slate-200 text-right">
      <button type="button" onclick="closeMetaModal()" class="px-4 py-1.5 text-xs font-semibold rounded-xl bg-slate-200 text-slate-700 hover:bg-slate-300">
        Close
      </button>
    </div>
  </div>
</div>

<script>
function openMetaModal(action, data) {
  document.getElementById('metaModalTitle').innerText = 'Metadata: ' + action;
  document.getElementById('metaModalContent').innerText = JSON.stringify(data, null, 2);
  document.getElementById('metaModal').classList.remove('hidden');
}

function closeMetaModal() {
  document.getElementById('metaModal').classList.add('hidden');
}

// Client side search and result filter
const searchInput = document.getElementById('logFilterSearch');
const resultSelect = document.getElementById('logFilterResult');
const rows = document.querySelectorAll('.log-row');
const countDisplay = document.getElementById('visibleLogCount');

function filterLogs() {
  const query = (searchInput.value || '').toLowerCase().trim();
  const selectedResult = (resultSelect.value || '').toLowerCase().trim();
  let visible = 0;

  rows.forEach(row => {
    const action = row.getAttribute('data-action') || '';
    const entity = row.getAttribute('data-entity') || '';
    const ip = row.getAttribute('data-ip') || '';
    const res = row.getAttribute('data-result') || '';

    const matchesQuery = !query || action.includes(query) || entity.includes(query) || ip.includes(query);
    const matchesResult = !selectedResult || res === selectedResult;

    if (matchesQuery && matchesResult) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  if (countDisplay) {
    countDisplay.innerText = visible;
  }
}

if (searchInput) searchInput.addEventListener('input', filterLogs);
if (resultSelect) resultSelect.addEventListener('change', filterLogs);
</script>
<?= $this->endSection() ?>

