<div class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
        <a href="<?= base_url('admin/engagement/campaigns') ?>" class="hover:text-brand-primary">Email Campaigns</a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">Delivery Logs</span>
      </div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Delivery Logs: <?= esc($campaign['title']) ?></h1>
    </div>
    <a href="<?= base_url('admin/engagement/campaigns') ?>" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
      &larr; Back to Campaigns
    </a>
  </div>

  <!-- Delivery Logs Table -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Recipient</th>
            <th class="px-4 py-3.5">Provider</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-center">Attempts</th>
            <th class="px-4 py-3.5">Error Notice</th>
            <th class="px-6 py-3.5 text-right">Timestamp</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                No delivery logs recorded for this campaign yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $l): ?>
              <?php
                $badge = match($l['status']) {
                  'DELIVERED' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                  'SENT'      => 'bg-blue-50 text-blue-700 border-blue-200',
                  'QUEUED'    => 'bg-purple-50 text-purple-700 border-purple-200',
                  'FAILED'    => 'bg-red-50 text-red-700 border-red-200',
                  default     => 'bg-slate-50 text-slate-600 border-slate-200',
                };
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4 font-bold text-slate-900"><?= esc($l['recipient_email']) ?></td>
                <td class="px-4 py-4 uppercase text-slate-600"><?= esc($l['provider']) ?></td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>"><?= esc($l['status']) ?></span>
                </td>
                <td class="px-4 py-4 text-center font-bold text-slate-700"><?= (int) $l['attempt_count'] ?></td>
                <td class="px-4 py-4 text-slate-500 max-w-xs truncate"><?= esc($l['error_message'] ?: 'None') ?></td>
                <td class="px-6 py-4 text-right text-slate-500"><?= date('M j, Y — g:i A', strtotime($l['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
