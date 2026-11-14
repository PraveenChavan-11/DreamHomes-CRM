<div class="max-w-2xl mx-auto space-y-6">

  <div>
    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Admin Profile &amp; Security</h1>
    <p class="text-xs text-slate-500 mt-0.5">Manage administrative credentials and update password</p>
  </div>

  <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8">
    <form action="<?= base_url('admin/profile/update') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <!-- Profile Info -->
      <div class="space-y-4">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">Administrator Details</h3>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Display Name *</label>
          <input type="text" name="name" required value="<?= esc($user['name'] ?? 'Administrator') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address *</label>
          <input type="email" name="email" required value="<?= esc($user['email'] ?? 'admin@talenthubconsultant.com') ?>" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none focus:border-brand-primary">
        </div>
      </div>

      <!-- Change Password -->
      <div class="space-y-4 pt-4 border-t border-slate-100">
        <div>
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Change Password</h3>
          <p class="text-[11px] text-slate-400">Leave blank if you do not wish to change your password.</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 mb-1">Current Password</label>
          <input type="password" name="current_password" placeholder="••••••••" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">New Password</label>
            <input type="password" name="new_password" placeholder="Min. 8 characters" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1">Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Repeat new password" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-primary">
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end pt-4 border-t border-slate-100">
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
          Save Credentials
        </button>
      </div>

    </form>
  </div>

</div>

