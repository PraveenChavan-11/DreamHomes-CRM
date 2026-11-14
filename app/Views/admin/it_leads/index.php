<?php
use App\Services\ItLeadService as L;
/** @var array $leads @var array $filters @var array $kpis @var array $staff @var array $products @var array $sources */
$badge = [
  'new' => 'bg-amber-100 text-amber-800', 'contacted' => 'bg-sky-100 text-sky-800', 'requirement_gathering' => 'bg-indigo-100 text-indigo-800',
  'qualified' => 'bg-cyan-100 text-cyan-800', 'demo_scheduled' => 'bg-teal-100 text-teal-800', 'demo_active' => 'bg-teal-200 text-teal-900',
  'proposal_sent' => 'bg-violet-100 text-violet-800', 'negotiation' => 'bg-orange-100 text-orange-800',
  'won' => 'bg-emerald-100 text-emerald-800', 'lost' => 'bg-slate-200 text-slate-700', 'on_hold' => 'bg-slate-100 text-slate-600', 'spam' => 'bg-rose-100 text-rose-700',
];
$prio = ['urgent' => 'bg-rose-100 text-rose-700', 'high' => 'bg-orange-100 text-orange-800', 'normal' => 'bg-slate-100 text-slate-600', 'low' => 'bg-slate-50 text-slate-500'];
$qs = static fn (array $extra = []) => http_build_query(array_filter(array_merge($_GET, $extra), static fn ($v) => $v !== '' && $v !== null));
$sel = 'py-2 px-3 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:border-brand-primary font-medium';
$sortLink = static function (string $key, string $label) use ($filters, $qs) {
    $dir = ($filters['sort'] === $key && strtolower($filters['dir']) !== 'asc') ? 'asc' : 'desc';
    $arrow = $filters['sort'] === $key ? (strtolower($filters['dir']) === 'asc' ? ' ▲' : ' ▼') : '';
    return '<a href="' . base_url('admin/it-leads?' . $qs(['sort' => $key, 'dir' => $dir, 'page' => null])) . '" class="hover:text-slate-800">' . esc($label) . $arrow . '</a>';
};
$activeFilters = array_filter($filters, static fn ($v, $k) => $v !== '' && !in_array($k, ['sort', 'dir'], true), ARRAY_FILTER_USE_BOTH);
?>
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">CRM Leads</h1>
      <p class="text-xs text-slate-500 mt-1">Project and product enquiries from the website and WorkSphere, tracked from first contact to Closed Won or Closed Lost.</p>
    </div>
    <div class="flex gap-2">
      <?php if (!empty($canSettings)): ?><a href="<?= base_url('admin/it-leads/settings') ?>" class="inline-flex items-center px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold">Settings</a><?php endif; ?>
      <?php if (!empty($canExport)): ?>
        <a href="<?= base_url('admin/it-leads?' . $qs(['export' => 'csv', 'page' => null])) ?>" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">Export CSV</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
    <?php foreach ([['Total', $kpis['total'], ''], ['New', $kpis['new'], 'status=new'], ['Open pipeline', $kpis['open'], ''], ['Closed Won', $kpis['won'], 'status=won'], ['Follow-ups due', $kpis['followups_due'], 'followup=due']] as [$label, $val, $link]): ?>
      <a href="<?= base_url('admin/it-leads' . ($link ? '?' . $link : '')) ?>" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-primary transition">
        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"><?= esc($label) ?></p>
        <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= (int) $val ?></p>
      </a>
    <?php endforeach; ?>
  </div>

  <form action="<?= base_url('admin/it-leads') ?>" method="GET" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-3">
    <label class="sr-only" for="f-search">Search</label>
    <input id="f-search" type="text" name="search" value="<?= esc($filters['search']) ?>" placeholder="Search name, company, email, phone, description…" class="w-full sm:w-72 px-4 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
    <select aria-label="Status" name="status" class="<?= $sel ?>"><option value="">All statuses</option>
      <?php foreach (L::STATUSES as $k => $v): ?><option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
    <select aria-label="Source" name="source" class="<?= $sel ?>"><option value="">All sources</option>
      <?php foreach ($sources as $s): ?><option value="<?= esc($s) ?>" <?= $filters['source'] === $s ? 'selected' : '' ?>><?= esc(str_replace('_', ' ', $s)) ?></option><?php endforeach; ?></select>
    <select aria-label="Product" name="product" class="<?= $sel ?>"><option value="">All products</option>
      <?php foreach ($products as $s): ?><option value="<?= esc($s) ?>" <?= $filters['product'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option><?php endforeach; ?></select>
    <select aria-label="Priority" name="priority" class="<?= $sel ?>"><option value="">Any priority</option>
      <?php foreach (L::PRIORITIES as $k => $v): ?><option value="<?= $k ?>" <?= $filters['priority'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
    <select aria-label="Category" name="category" class="<?= $sel ?>"><option value="">All services</option>
      <?php foreach (L::CATEGORIES as $k => $v): ?><option value="<?= $k ?>" <?= $filters['category'] === $k ? 'selected' : '' ?>><?= esc($v) ?></option><?php endforeach; ?></select>
    <select aria-label="Assignee" name="assigned" class="<?= $sel ?>"><option value="">Anyone</option><option value="none" <?= $filters['assigned'] === 'none' ? 'selected' : '' ?>>Unassigned</option>
      <?php foreach ($staff as $u): $n = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['username']; ?>
        <option value="<?= (int) $u['id'] ?>" <?= $filters['assigned'] === (string) $u['id'] ? 'selected' : '' ?>><?= esc($n) ?></option><?php endforeach; ?></select>
    <label class="text-xs text-slate-500 flex items-center gap-1">From <input type="date" name="date_from" value="<?= esc($filters['date_from']) ?>" class="<?= $sel ?>"></label>
    <label class="text-xs text-slate-500 flex items-center gap-1">To <input type="date" name="date_to" value="<?= esc($filters['date_to']) ?>" class="<?= $sel ?>"></label>
    <input aria-label="Tag" type="text" name="tag" value="<?= esc($filters['tag']) ?>" placeholder="tag" maxlength="30" class="w-24 px-3 py-2 rounded-xl border border-slate-200 text-xs">
    <select aria-label="Per page" name="per_page" onchange="this.form.submit()" class="<?= $sel ?>">
      <?php foreach ([10, 25, 50, 100] as $n): ?><option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?> / page</option><?php endforeach; ?></select>
    <label class="flex items-center gap-1.5 text-xs text-slate-600 font-medium"><input type="checkbox" name="followup" value="due" <?= $filters['followup'] === 'due' ? 'checked' : '' ?>> Follow-up overdue</label>
    <input type="hidden" name="sort" value="<?= esc($filters['sort']) ?>"><input type="hidden" name="dir" value="<?= esc($filters['dir']) ?>">
    <button class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">Filter</button>
    <?php if ($activeFilters): ?><a href="<?= base_url('admin/it-leads') ?>" class="text-xs text-slate-400 hover:text-slate-600">Clear</a><?php endif; ?>
    <span class="ml-auto text-xs text-slate-500 font-medium">Showing <span class="font-bold text-slate-800"><?= count($leads) ?></span> of <span class="font-bold text-slate-800"><?= (int) $totalCount ?></span></span>
  </form>

  <form id="bulk-form" action="<?= base_url('admin/it-leads/bulk') ?>" method="POST">
    <?= csrf_field() ?>
    <?php if ($canManage): ?>
    <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-sm flex flex-wrap items-center gap-2 mb-3 text-xs">
      <span class="font-semibold text-slate-600">With selected:</span>
      <select name="bulk_action" id="bulk-action" aria-label="Bulk action" class="<?= $sel ?>"><option value="status">Set status</option><option value="assign">Assign to</option></select>
      <select name="bulk_value" id="bulk-status" aria-label="Bulk status" class="<?= $sel ?>"><?php foreach (L::STATUSES as $k => $v): ?><option value="<?= $k ?>"><?= esc($v) ?></option><?php endforeach; ?></select>
      <select id="bulk-assign" aria-label="Bulk assignee" class="<?= $sel ?>" style="display:none" disabled><option value="">Unassigned</option>
        <?php foreach ($staff as $u): $n = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['username']; ?><option value="<?= (int) $u['id'] ?>"><?= esc($n) ?></option><?php endforeach; ?></select>
      <input name="bulk_reason" maxlength="255" placeholder="Reason (required for Closed Lost)" class="px-3 py-2 rounded-xl border border-slate-200 text-xs w-56">
      <button class="px-3.5 py-2 rounded-xl bg-brand-primary text-white font-bold" onclick="return confirm('Apply to all selected leads?');">Apply</button>
    </div>
    <script>
      (function () {
        var a = document.getElementById('bulk-action'), s = document.getElementById('bulk-status'), u = document.getElementById('bulk-assign');
        a.addEventListener('change', function () {
          var assign = a.value === 'assign';
          s.style.display = assign ? 'none' : ''; s.disabled = assign;
          u.style.display = assign ? '' : 'none'; u.disabled = !assign; u.name = assign ? 'bulk_value' : '';
        });
      })();
    </script>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-x-auto">
      <table class="min-w-full text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[10px]">
          <tr>
            <?php if ($canManage): ?><th class="px-3 py-3"><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('.lead-cb').forEach(function(c){c.checked=this.checked}.bind(this))"></th><?php endif; ?>
            <th class="text-left px-4 py-3"><?= $sortLink('name', 'Lead') ?></th><th class="text-left px-4 py-3">Source / product</th><th class="text-left px-4 py-3"><?= $sortLink('status', 'Status') ?></th>
            <th class="text-left px-4 py-3"><?= $sortLink('priority', 'Priority') ?></th><th class="text-left px-4 py-3">Assigned</th><th class="text-left px-4 py-3"><?= $sortLink('followup', 'Follow-up') ?></th><th class="text-left px-4 py-3"><?= $sortLink('created_at', 'Received') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php if (!$leads): ?>
          <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400"><?= $activeFilters ? 'No leads match these filters.' : 'No leads yet. Enquiries from the website and WorkSphere will appear here.' ?></td></tr>
        <?php endif; ?>
        <?php foreach ($leads as $l): $overdue = $l['next_follow_up_at'] && $l['next_follow_up_at'] <= date('Y-m-d') && !in_array($l['status'], L::CLOSED, true); ?>
          <tr class="hover:bg-slate-50/70">
            <?php if ($canManage): ?><td class="px-3 py-3"><input class="lead-cb" type="checkbox" name="ids[]" value="<?= (int) $l['id'] ?>" aria-label="Select lead <?= (int) $l['id'] ?>"></td><?php endif; ?>
            <td class="px-4 py-3">
              <a href="<?= base_url('admin/it-leads/' . (int) $l['id']) ?>" class="font-bold text-slate-900 hover:text-brand-primary">#<?= (int) $l['id'] ?> · <?= esc($l['client_name']) ?></a>
              <?php if ($l['duplicate_of']): ?><span class="ml-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-bold" title="Possible duplicate of #<?= (int) $l['duplicate_of'] ?>">DUP</span><?php endif; ?>
              <?php if ((int) $l['enquiry_count'] > 1): ?><span class="ml-1 px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 text-[10px] font-bold" title="Repeat enquiries"><?= (int) $l['enquiry_count'] ?>x</span><?php endif; ?>
              <div class="text-slate-500"><?= esc($l['company'] ?: '—') ?> · <?= esc($l['email']) ?></div>
            </td>
            <td class="px-4 py-3 text-slate-700"><?= esc(str_replace('_', ' ', (string) $l['source'])) ?><div class="text-slate-400"><?= esc($l['product_slug'] ?: (L::CATEGORIES[$l['service_category']] ?? $l['service_category'])) ?></div></td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full font-bold <?= $badge[$l['status']] ?? 'bg-slate-100' ?>"><?= esc(L::STATUSES[$l['status']] ?? $l['status']) ?></span></td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full font-bold <?= $prio[$l['priority']] ?? '' ?>"><?= esc(L::PRIORITIES[$l['priority']] ?? $l['priority']) ?></span></td>
            <td class="px-4 py-3 text-slate-700"><?= esc($l['assignee_name'] ?: 'Unassigned') ?></td>
            <td class="px-4 py-3 <?= $overdue ? 'text-rose-600 font-bold' : 'text-slate-600' ?>"><?= esc($l['next_follow_up_at'] ?: '—') ?><?= $overdue ? ' (overdue)' : '' ?></td>
            <td class="px-4 py-3 text-slate-500"><?= esc(date('d M Y, H:i', strtotime($l['created_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>

  <?php if ($totalPages > 1): ?>
    <div class="flex items-center justify-center gap-2 text-xs">
      <?php if ($page > 1): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/it-leads?' . $qs(['page' => $page - 1])) ?>">&larr; Prev</a><?php endif; ?>
      <span class="text-slate-500">Page <?= (int) $page ?> of <?= (int) $totalPages ?></span>
      <?php if ($page < $totalPages): ?><a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white" href="<?= base_url('admin/it-leads?' . $qs(['page' => $page + 1])) ?>">Next &rarr;</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>
