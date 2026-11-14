<div class="max-w-3xl mx-auto space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-black text-slate-800 tracking-tight">
        <?= !empty($user) ? 'Edit User: ' . esc($user['username']) : 'Add New Staff User' ?>
      </h1>
      <p class="text-xs text-slate-500 mt-1">
        Configure personal credentials, status, and role-based access permissions.
      </p>
    </div>
    <a href="<?= base_url('admin/users') ?>" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 transition">
      &larr; Back to Users
    </a>
  </div>

  <!-- Form Card -->
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">
    <form action="<?= !empty($user) ? base_url('admin/users/update/' . $user['id']) : base_url('admin/users/create') ?>" method="POST" class="space-y-6">
      <?= csrf_field() ?>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- First Name -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">First Name *</label>
          <input type="text" name="first_name" required value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" placeholder="e.g. Ramesh" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Last Name -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Last Name *</label>
          <input type="text" name="last_name" required value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" placeholder="e.g. Kulkarni" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Username -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username (Login ID) *</label>
          <input type="text" name="username" required value="<?= esc(old('username', $user['username'] ?? '')) ?>" placeholder="e.g. ramesh_hr" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Work Email Address *</label>
          <input type="email" name="email" required value="<?= esc(old('email', $user['email'] ?? '')) ?>" placeholder="ramesh@talenthubconsultant.com" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Phone -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Number</label>
          <input type="text" name="phone" value="<?= esc(old('phone', $user['phone'] ?? '')) ?>" placeholder="+91 98765 43210" class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>

        <!-- Password -->
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
            Password <?= !empty($user) ? '<span class="text-slate-400 font-normal lowercase">(leave blank to keep current)</span>' : '*' ?>
          </label>
          <input type="password" name="password" <?= empty($user) ? 'required' : '' ?> minlength="8" placeholder="Minimum 8 characters..." class="w-full text-xs rounded-xl border-slate-200 focus:border-brand-primary focus:ring-brand-primary p-3">
        </div>
      </div>

      <!-- Role Assignment -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
          Assign Role(s) *
        </label>
        <p class="text-[11px] text-slate-500 mb-3">
          The assigned role dictates which sidebar navigation tabs and administration actions this user can access.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php
          $assignedRoleIds = !empty($user['role_ids']) ? $user['role_ids'] : (old('roles') ?: []);
          foreach ($roles as $r):
            $isChecked = in_array((int)$r['id'], $assignedRoleIds, true);
          ?>
            <label class="flex items-start gap-3 p-3.5 rounded-xl border <?= $isChecked ? 'border-brand-primary bg-brand-primary/5' : 'border-slate-200 bg-slate-50/50' ?> hover:border-brand-primary transition cursor-pointer">
              <input type="checkbox" name="roles[]" value="<?= $r['id'] ?>" <?= $isChecked ? 'checked' : '' ?> class="mt-0.5 rounded text-brand-primary focus:ring-brand-primary">
              <div class="text-xs">
                <span class="font-bold text-slate-800 block"><?= esc($r['name']) ?></span>
                <span class="text-[11px] text-slate-500 leading-snug"><?= esc($r['description'] ?: 'Standard operational role.') ?></span>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Active Status -->
      <div class="pt-2 border-t border-slate-100">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="is_active" value="1" <?= (!empty($user['is_active']) || empty($user)) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Active User (Permitted to log in to Admin Portal)</span>
        </label>
      </div>

      <!-- Two-Factor Authentication via Email OTP -->
      <div class="pt-2 border-t border-slate-100">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="email_otp_enabled" value="1" <?= !empty($user['email_otp_enabled']) ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Require Email OTP Verification on Login (Two-Factor Authentication)</span>
        </label>
        <p class="text-[11px] text-slate-500 ml-6 mt-1">
          When enabled, logging in with this user's password sends a 6-digit one-time passcode to their registered work email address with 10-minute expiry.
        </p>
      </div>

      <!-- Buttons -->
      <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
        <a href="<?= base_url('admin/users') ?>" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
          Cancel
        </a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-primary hover:bg-brand-hover text-white text-xs font-bold shadow-md shadow-brand-primary/20 transition">
          <?= !empty($user) ? 'Save User Changes' : 'Create Staff User' ?>
        </button>
      </div>

    </form>
  </div>

</div>
