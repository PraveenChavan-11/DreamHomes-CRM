<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">Roles &amp; Granular Permissions</h1>
      <p class="text-xs text-slate-500 mt-1">
        Define custom roles, assign module permissions, and control sidebar visibility and backend URL authorization.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/users') ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
        Staff Users
      </a>
      <a href="<?= base_url('admin/roles/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold shadow-md shadow-brand-primary/20 hover:bg-brand-hover transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>Create Role</span>
      </a>
    </div>
  </div>

  <!-- Roles Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($roles as $r): ?>
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= ($r['slug'] === 'super_admin') ? 'bg-amber-100 text-amber-800 border border-amber-200' : (($r['slug'] === 'hr_manager') ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700') ?>">
                @<?= esc($r['slug']) ?>
              </span>
              <h2 class="text-lg font-black text-slate-800 mt-1.5"><?= esc($r['name']) ?></h2>
            </div>
            <span class="px-3 py-1 rounded-xl bg-slate-50 border border-slate-200 text-slate-600 text-xs font-bold">
              <?= esc($r['user_count']) ?> user<?= $r['user_count'] === 1 ? '' : 's' ?>
            </span>
          </div>

          <p class="text-xs text-slate-500 leading-relaxed mb-4">
            <?= esc($r['description'] ?: 'No role description provided.') ?>
          </p>

          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-4">
            <div class="flex items-center justify-between text-xs mb-2">
              <span class="font-bold text-slate-700">Assigned Privileges:</span>
              <span class="font-extrabold text-brand-primary"><?= esc($r['permission_count']) ?> permissions</span>
            </div>
            <div class="flex flex-wrap gap-1">
              <?php if (!empty($r['permission_codes'])): ?>
                <?php foreach (array_slice($r['permission_codes'], 0, 6) as $pCode): ?>
                  <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-mono text-slate-600 font-semibold">
                    <?= esc($pCode) ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($r['permission_codes']) > 6): ?>
                  <span class="px-2 py-0.5 rounded bg-slate-200 text-[10px] font-semibold text-slate-600">
                    +<?= count($r['permission_codes']) - 6 ?> more
                  </span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-[11px] text-slate-400 italic">No permissions assigned</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <span class="text-[11px] text-slate-400">
            <?= ($r['slug'] === 'super_admin') ? '🛡️ Protected System Role' : 'Custom Configurable Role' ?>
          </span>

          <div class="flex items-center gap-2">
            <a href="<?= base_url('admin/roles/edit/' . $r['id']) ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-800 font-bold hover:bg-slate-200 transition text-[11px]">
              Configure Permissions
            </a>

            <?php if ($r['slug'] !== 'super_admin'): ?>
              <form action="<?= base_url('admin/roles/delete/' . $r['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Permanently delete role <?= esc($r['name']) ?>?')">
                <?= csrf_field() ?>
                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 transition text-[11px] font-bold">
                  Delete
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>
