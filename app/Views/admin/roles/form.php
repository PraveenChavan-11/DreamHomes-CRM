<div class="max-w-4xl mx-auto space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">
        <?= !empty($role) ? 'Configure Role: ' . esc($role['name']) : 'Create New Access Role' ?>
      </h1>
      <p class="text-xs text-slate-500 mt-1">
        Define role credentials and toggle exact module permissions.
      </p>
    </div>
    <a href="<?= base_url('admin/roles') ?>" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
      &larr; Back to Roles
    </a>
  </div>

  <!-- Form Card -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
    <form action="<?= !empty($role) ? base_url('admin/roles/update/' . $role['id']) : base_url('admin/roles/create') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Role Name -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role Name *</label>
          <input type="text" name="name" required value="<?= esc(old('name', $role['name'] ?? '')) ?>" placeholder="e.g. Talent Partner / HR Associate" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Role Slug -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role Slug (Unique Identifier) *</label>
          <input type="text" name="slug" <?= (!empty($role) && $role['slug'] === 'super_admin') ? 'readonly class="w-full text-xs rounded-xl border-slate-200 bg-slate-100 text-slate-500 p-3 cursor-not-allowed"' : 'required class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3"' ?> value="<?= esc(old('slug', $role['slug'] ?? '')) ?>" placeholder="e.g. hr_associate">
        </div>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Description</label>
        <textarea name="description" rows="2" placeholder="Brief explanation of this role's responsibilities..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3"><?= esc(old('description', $role['description'] ?? '')) ?></textarea>
      </div>

      <!-- Granular Permissions Matrix -->
      <div class="pt-4 border-t border-slate-100">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider">Granular Privilege Matrix</h2>
            <p class="text-[11px] text-slate-500">Toggle permissions granted to users with this role.</p>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" onclick="toggleAllPerms(true)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
              Select All
            </button>
            <button type="button" onclick="toggleAllPerms(false)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] transition">
              Clear All
            </button>
          </div>
        </div>

        <div class="space-y-5">
          <?php
          $assignedPermIds = !empty($role['permission_ids']) ? $role['permission_ids'] : (old('permissions') ?: []);
          foreach ($groupedPermissions as $categoryName => $perms):
          ?>
            <div class="bg-slate-50/70 rounded-2xl p-5 border border-slate-200/80">
              <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-primary"></span>
                <span><?= esc($categoryName) ?> Module</span>
              </h3>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <?php foreach ($perms as $p):
                  $isChecked = in_array((int)$p['id'], $assignedPermIds, true);
                ?>
                  <label class="flex items-start gap-2.5 p-3 rounded-xl bg-white border border-slate-200 hover:border-brand-primary transition cursor-pointer">
                    <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= $isChecked ? 'checked' : '' ?> class="perm-checkbox mt-0.5 rounded text-brand-primary focus:ring-brand-primary">
                    <div class="text-xs">
                      <span class="font-bold text-slate-800 block"><?= esc($p['name']) ?></span>
                      <span class="text-[10px] font-mono text-slate-400"><?= esc($p['code']) ?></span>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Buttons -->
      <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <a href="<?= base_url('admin/roles') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
          Cancel
        </a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
          <?= !empty($role) ? 'Save Role Changes' : 'Create Access Role' ?>
        </button>
      </div>

    </form>
  </div>

</div>

<script>
function toggleAllPerms(checked) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => {
    cb.checked = checked;
  });
}
</script>
