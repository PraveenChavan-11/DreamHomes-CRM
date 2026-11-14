<div class="space-y-6">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">Staff &amp; Admin Users</h1>
      <p class="text-xs text-slate-500 mt-1">
        Manage internal recruitment consultants, HR partners, content editors, and administrator accounts.
      </p>
    </div>

    <div class="flex items-center gap-3">
      <a href="<?= base_url('admin/roles') ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition shadow-sm">
        Manage Roles &amp; Permissions
      </a>
      <a href="<?= base_url('admin/users/new') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-primary text-white text-xs font-bold shadow-md shadow-brand-primary/20 hover:bg-brand-hover transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Add Staff User</span>
      </a>
    </div>
  </div>

  <!-- Users Table -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-extrabold border-b border-slate-100">
            <th class="py-3 px-6">User &amp; Contact</th>
            <th class="py-3 px-6">Assigned Roles</th>
            <th class="py-3 px-6">Status</th>
            <th class="py-3 px-6">Last Login</th>
            <th class="py-3 px-6 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php if (!empty($users)): ?>
            <?php foreach ($users as $u): ?>
              <tr class="hover:bg-slate-50/60 transition">
                <td class="py-4 px-6">
                  <div class="font-bold text-slate-800 text-sm">
                    <?= esc($u['first_name'] . ' ' . $u['last_name']) ?>
                  </div>
                  <div class="flex items-center gap-2 text-[11px] text-slate-500 mt-0.5">
                    <span>@<?= esc($u['username']) ?></span>
                    <span>&bull;</span>
                    <span><?= esc($u['email']) ?></span>
                    <?php if (!empty($u['email_otp_enabled'])): ?>
                      <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Email OTP 2FA
                      </span>
                    <?php endif; ?>
                  </div>
                </td>

                <td class="py-4 px-6">
                  <div class="flex flex-wrap gap-1.5">
                    <?php if (!empty($u['roles'])): ?>
                      <?php foreach ($u['roles'] as $r): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase <?= ($r['slug'] === 'super_admin') ? 'bg-amber-100 text-amber-800 border border-amber-200' : (($r['slug'] === 'hr_manager') ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700') ?>">
                          <?= esc($r['name']) ?>
                        </span>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <span class="text-slate-400 italic">No role assigned</span>
                    <?php endif; ?>
                  </div>
                </td>

                <td class="py-4 px-6">
                  <?php if (!empty($u['is_active'])): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                      Active
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                      Inactive / Suspended
                    </span>
                  <?php endif; ?>
                </td>

                <td class="py-4 px-6 text-slate-500 text-[11px]">
                  <?= !empty($u['last_login_at']) ? date('d M Y, h:i A', strtotime($u['last_login_at'])) : 'Never logged in' ?>
                </td>

                <td class="py-4 px-6 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a href="<?= base_url('admin/users/edit/' . $u['id']) ?>" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-800 font-bold hover:bg-slate-200 transition text-[11px]">
                      Edit
                    </a>

                    <?php if (empty($adminUser['id']) || (int)$adminUser['id'] !== (int)$u['id']): ?>
                      <form action="<?= base_url('admin/users/toggle/' . $u['id']) ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition text-[11px] font-semibold">
                          <?= !empty($u['is_active']) ? 'Deactivate' : 'Activate' ?>
                        </button>
                      </form>

                      <form action="<?= base_url('admin/users/delete/' . $u['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Permanently delete staff user <?= esc($u['username']) ?>?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-600 hover:bg-red-50 transition text-[11px] font-bold">
                          Delete
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="py-12 text-center text-slate-400">
                No users found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
