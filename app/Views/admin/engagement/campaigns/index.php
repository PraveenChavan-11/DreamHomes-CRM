<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-900 tracking-tight">Email Campaigns &amp; Broadcasts</h1>
      <p class="text-xs text-slate-500 mt-1">Compose branded newsletters, job vacancy broadcasts, and track delivery logs with suppression enforcement.</p>
    </div>
    <a href="<?= base_url('admin/engagement/campaigns/new') ?>" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover shadow-md shadow-brand-primary/20 transition self-start sm:self-auto">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      <span>Compose New Campaign</span>
    </a>
  </div>

  <!-- Telemetry Row -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Eligible Verified Audience</div>
      <div class="text-2xl font-black text-brand-primary mt-1"><?= number_format($verifiedCount ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Dispatched</div>
      <div class="text-2xl font-black text-slate-900 mt-1"><?= number_format($deliveryStats['sent'] ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Delivered</div>
      <div class="text-2xl font-black text-emerald-600 mt-1"><?= number_format($deliveryStats['delivered'] ?? 0) ?></div>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
      <div class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Failed / Bounced</div>
      <div class="text-2xl font-black text-red-500 mt-1"><?= number_format($deliveryStats['failed'] ?? 0) ?></div>
    </div>
  </div>

  <!-- Campaigns List -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-100">
          <tr>
            <th class="px-6 py-3.5">Campaign Name &amp; Subject</th>
            <th class="px-4 py-3.5">Type</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-4 py-3.5 text-right">Recipients</th>
            <th class="px-4 py-3.5 text-right">Sent Date</th>
            <th class="px-6 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (empty($campaigns)): ?>
            <tr>
              <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                <p class="font-bold text-slate-600 mb-1">No email campaigns created yet</p>
                <p class="text-[11px] text-slate-400 mb-4">Draft and broadcast quarterly market reports or urgent hiring updates.</p>
                <a href="<?= base_url('admin/engagement/campaigns/new') ?>" class="inline-block px-4 py-2 rounded-xl bg-brand-primary text-white text-xs font-bold hover:bg-brand-hover transition">Compose Campaign</a>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($campaigns as $c): ?>
              <?php
                $badge = match($c['status']) {
                  'SENT'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                  'SENDING'   => 'bg-blue-50 text-blue-700 border-blue-200',
                  'SCHEDULED' => 'bg-purple-50 text-purple-700 border-purple-200',
                  'PAUSED'    => 'bg-amber-50 text-amber-700 border-amber-200',
                  default     => 'bg-slate-50 text-slate-600 border-slate-200',
                };
              ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900"><?= esc($c['title']) ?></div>
                  <div class="text-[11px] text-slate-500 truncate max-w-xs"><?= esc($c['subject']) ?></div>
                </td>
                <td class="px-4 py-4 capitalize text-slate-700">
                  <?= esc(str_replace('_', ' ', $c['campaign_type'])) ?>
                </td>
                <td class="px-4 py-4 text-center">
                  <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>"><?= esc($c['status']) ?></span>
                </td>
                <td class="px-4 py-4 text-right font-medium text-slate-800">
                  <?= number_format($c['total_recipients'] ?? 0) ?>
                </td>
                <td class="px-4 py-4 text-right text-slate-500">
                  <?= !empty($c['sent_at']) ? date('M j, Y — g:i A', strtotime($c['sent_at'])) : '-' ?>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <!-- Logs -->
                    <a href="<?= base_url('admin/engagement/campaigns/logs/' . $c['id']) ?>" title="Delivery Logs" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-brand-primary transition">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>

                    <!-- Send Broadcast -->
                    <?php if ($c['status'] === 'DRAFT' || $c['status'] === 'PAUSED'): ?>
                      <form method="POST" action="<?= base_url('admin/engagement/campaigns/send/' . $c['id']) ?>" onsubmit="return confirm('Broadcast this campaign to all verified subscribers?');" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-brand-primary text-white text-[11px] font-bold hover:bg-brand-hover shadow-sm transition">
                          Send Broadcast
                        </button>
                      </form>
                    <?php endif; ?>

                    <!-- Edit -->
                    <a href="<?= base_url('admin/engagement/campaigns/edit/' . $c['id']) ?>" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-brand-primary transition">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
