<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Cookie Consent &amp; Privacy Telemetry</h1>
      <p class="text-xs text-slate-500 mt-1">Audit log of visitor cookie preferences complying with GDPR, ePrivacy, and India Digital Personal Data Protection Act.</p>
    </div>
  </div>

  <!-- Telemetry Row -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Consent Interactions</div>
      <div class="text-3xl font-black text-slate-900 mt-1"><?= number_format($stats['total'] ?? 0) ?></div>
      <div class="text-[11px] text-slate-500 mt-1">Necessary cookies strictly enforced</div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Analytics Cookies Accepted</div>
      <div class="text-3xl font-black text-emerald-600 mt-1"><?= number_format($stats['analytics'] ?? 0) ?></div>
      <div class="text-[11px] text-slate-500 mt-1">
        <?= ($stats['total'] ?? 0) > 0 ? round((($stats['analytics'] ?? 0) / $stats['total']) * 100, 1) : 0 ?>% acceptance rate
      </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Marketing Cookies Accepted</div>
      <div class="text-3xl font-black text-blue-600 mt-1"><?= number_format($stats['marketing'] ?? 0) ?></div>
      <div class="text-[11px] text-slate-500 mt-1">
        <?= ($stats['total'] ?? 0) > 0 ? round((($stats['marketing'] ?? 0) / $stats['total']) * 100, 1) : 0 ?>% acceptance rate
      </div>
    </div>
  </div>

  <!-- Consent Audit Logs Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
      <h2 class="text-sm font-extrabold text-slate-900">Anonymized Consent Audit Logs (SHA-256 Hashed)</h2>
      <span class="text-[10px] font-bold text-slate-400 uppercase">PII Redacted</span>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Consent UUID</th>
            <th class="px-4 py-3.5 text-center">Necessary</th>
            <th class="px-4 py-3.5 text-center">Analytics</th>
            <th class="px-4 py-3.5 text-center">Marketing</th>
            <th class="px-4 py-3.5">Anonymized IP Hash</th>
            <th class="px-6 py-3.5 text-right">Recorded Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                No consent logs recorded yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $row): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4 font-mono text-[11px] text-slate-700"><?= esc($row['consent_uuid']) ?></td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                </td>
                <td class="px-4 py-4 text-center">
                  <?php if ($row['analytics']): ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Granted</span>
                  <?php else: ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Denied</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 text-center">
                  <?php if ($row['marketing']): ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Granted</span>
                  <?php else: ?>
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">Denied</span>
                  <?php endif; ?>
                </td>
                <td class="px-4 py-4 font-mono text-[10px] text-slate-400 max-w-[150px] truncate">
                  <?= esc($row['ip_hash'] ?? 'anonymized') ?>
                </td>
                <td class="px-6 py-4 text-right text-slate-500">
                  <?= date('M j, Y — g:i A', strtotime($row['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
