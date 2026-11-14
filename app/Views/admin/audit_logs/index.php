<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 mb-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>Append-Only Forensic Trail</span>
      </div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Security &amp; Mutation Audit Trail</h1>
      <p class="text-xs text-slate-500 mt-1">Immutable forensic tracking of administrator logins, job lifecycle updates, and candidate pipeline transitions</p>
    </div>

    <!-- Export Action -->
    <div class="flex items-center gap-3">
      <?php
        $exportQuery = http_build_query(array_merge($_GET, ['export' => 'csv']));
      ?>
      <a href="<?= base_url('admin/audit-logs?' . $exportQuery) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Export Forensic CSV</span>
      </a>
    </div>
  </div>

  <!-- Filters -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-4">
    <form action="<?= base_url('admin/audit-logs') ?>" method="GET" class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
      <div class="relative w-full sm:w-64">
        <input type="text" name="search" value="<?= esc($search ?? '') ?>" placeholder="Search actor, action, IP..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <select name="action" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Actions</option>
        <?php foreach ($availableActions as $act): ?>
          <option value="<?= esc($act) ?>" <?= ($action === $act) ? 'selected' : '' ?>><?= esc($act) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="entity_type" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="">All Entity Types</option>
        <?php foreach ($availableEntities as $ent): ?>
          <option value="<?= esc($ent) ?>" <?= ($entityType === $ent) ? 'selected' : '' ?>><?= esc($ent) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="per_page" onchange="this.form.submit()" class="py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium">
        <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10 / page</option>
        <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25 / page</option>
        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50 / page</option>
        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100 / page</option>
      </select>

      <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
        Filter
      </button>

      <?php if (!empty($search) || !empty($action) || !empty($entityType)): ?>
        <a href="<?= base_url('admin/audit-logs') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a>
      <?php endif; ?>
    </form>

    <div class="text-xs text-slate-500 font-medium">
      Total Events: <span class="font-bold text-slate-800"><?= esc($totalCount ?? count($logs)) ?></span>
    </div>
  </div>

  <!-- Audit Logs Table -->
  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50/80 text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-6 py-4">Timestamp &amp; ID</th>
            <th class="px-6 py-4">Actor</th>
            <th class="px-6 py-4">Action</th>
            <th class="px-6 py-4">Entity</th>
            <th class="px-6 py-4">Client IP &amp; Device</th>
            <th class="px-6 py-4 text-right">Payload Diff</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-slate-700">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs">
                No audit log entries match your criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $log): ?>
              <?php
                $hasPayload = !empty($log['old_values']) || !empty($log['new_values']);
                $act = strtoupper($log['action'] ?? '');
              ?>
              <tr class="hover:bg-slate-50/60 transition font-mono">
                <td class="px-6 py-4 font-sans">
                  <div class="font-bold text-slate-900 text-xs"><?= esc(date('M d, Y H:i:s', strtotime($log['created_at'] ?? 'now'))) ?></div>
                  <div class="text-[10px] text-slate-400 font-mono">#<?= esc($log['id']) ?></div>
                </td>
                <td class="px-6 py-4 font-sans">
                  <div class="font-bold text-slate-800"><?= esc($log['user_name'] ?? 'System / Service') ?></div>
                  <div class="text-[10px] text-slate-400"><?= esc($log['user_email'] ?? 'cli@talenthub') ?></div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold font-sans
                    <?= str_contains($act, 'LOGIN') ? 'bg-sky-50 text-sky-700 border border-sky-200' : '' ?>
                    <?= str_contains($act, 'CREATE') ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' ?>
                    <?= str_contains($act, 'UPDATE') || str_contains($act, 'CHANGE') ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' ?>
                    <?= str_contains($act, 'DELETE') ? 'bg-red-50 text-red-700 border border-red-200' : '' ?>
                  ">
                    <?= esc($log['action']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 font-sans">
                  <div class="font-semibold text-slate-800 capitalize"><?= esc($log['entity_type'] ?? 'General') ?></div>
                  <?php if (!empty($log['entity_id'])): ?>
                    <div class="text-[10px] text-slate-400 font-mono">ID: <?= esc($log['entity_id']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-[11px]">
                  <div class="text-slate-800 font-mono"><?= esc($log['ip_address'] ?? '127.0.0.1') ?></div>
                  <div class="text-[10px] text-slate-400 truncate max-w-xs" title="<?= esc($log['user_agent'] ?? '') ?>">
                    <?= esc(substr($log['user_agent'] ?? '', 0, 40)) ?>...
                  </div>
                </td>
                <td class="px-6 py-4 text-right font-sans">
                  <?php if ($hasPayload): ?>
                    <button type="button" onclick="openPayloadModal('<?= esc($log['id']) ?>')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-semibold shadow-sm transition">
                      <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      <span>Diff</span>
                    </button>
                    <!-- Hidden Payload Containers -->
                    <div id="payload-old-<?= esc($log['id']) ?>" class="hidden"><?= esc($log['old_values'] ?? '{}') ?></div>
                    <div id="payload-new-<?= esc($log['id']) ?>" class="hidden"><?= esc($log['new_values'] ?? '{}') ?></div>
                  <?php else: ?>
                    <span class="text-[10px] text-slate-300">No Diff</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (!empty($totalPages) && $totalPages > 1): ?>
      <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs font-sans">
        <span class="text-slate-500">
          Showing page <?= esc($page) ?> of <?= esc($totalPages) ?>
        </span>
        <div class="flex items-center gap-2">
          <?php if ($page > 1): ?>
            <?php
              $prevQuery = http_build_query(array_merge($_GET, ['page' => $page - 1]));
            ?>
            <a href="<?= base_url('admin/audit-logs?' . $prevQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">&larr; Previous</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <?php
              $nextQuery = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= base_url('admin/audit-logs?' . $nextQuery) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold transition">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Forensic Diff Modal -->
<div id="diffModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
      <div>
        <h3 class="text-base font-extrabold text-slate-900">Forensic State Mutation Payload</h3>
        <p class="text-xs text-slate-500">Record diff of attributes altered during transaction</p>
      </div>
      <button onclick="closePayloadModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1 overflow-y-auto">
      <div>
        <div class="text-xs font-bold text-red-600 uppercase tracking-wider mb-1.5">Previous State (Old)</div>
        <pre id="modalOldValues" class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 whitespace-pre-wrap break-all"></pre>
      </div>
      <div>
        <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-1.5">Committed State (New)</div>
        <pre id="modalNewValues" class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs font-mono text-slate-800 whitespace-pre-wrap break-all"></pre>
      </div>
    </div>

    <div class="pt-3 border-t border-slate-100 text-right">
      <button onclick="closePayloadModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
        Close
      </button>
    </div>
  </div>
</div>

<script>
function openPayloadModal(logId) {
  const oldVal = document.getElementById('payload-old-' + logId)?.textContent || '{}';
  const newVal = document.getElementById('payload-new-' + logId)?.textContent || '{}';

  try {
    document.getElementById('modalOldValues').textContent = JSON.stringify(JSON.parse(oldVal), null, 2);
  } catch (e) {
    document.getElementById('modalOldValues').textContent = oldVal;
  }

  try {
    document.getElementById('modalNewValues').textContent = JSON.stringify(JSON.parse(newVal), null, 2);
  } catch (e) {
    document.getElementById('modalNewValues').textContent = newVal;
  }

  document.getElementById('diffModal').classList.remove('hidden');
}

function closePayloadModal() {
  document.getElementById('diffModal').classList.add('hidden');
}
</script>

