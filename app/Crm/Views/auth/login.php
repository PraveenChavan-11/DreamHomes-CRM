<?php
$heading = 'Sign In to Dashboard';
$sub = 'Enter your sales or administrative credentials to continue.';
echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub'));
?>
<form method="post" action="<?= site_url('crm/login') ?>" data-validate class="auth-form-inner space-y-5">
  <?= csrf_field() ?>

  <div class="auth-field-group">
    <label for="identity" class="auth-label">Username or Email</label>
    <div class="auth-input-wrapper">
      <span class="auth-input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </span>
      <input type="text" id="identity" name="identity" value="<?= esc(old('identity')) ?>" required autofocus autocomplete="username" placeholder="admin or salesperson@talenthubconsultant.com" class="auth-input" data-msg="Enter your username or email.">
    </div>
  </div>

  <div class="auth-field-group">
    <div class="auth-label-row">
      <label for="password" class="auth-label">Password</label>
      <a href="<?= site_url('crm/forgot-password') ?>" class="auth-forgot-link">Forgot password?</a>
    </div>
    <div class="auth-input-wrapper">
      <span class="auth-input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </span>
      <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••••••" class="auth-input has-toggle" data-msg="Enter your password.">
      <button type="button" id="toggle-pw" class="auth-eye-btn" aria-label="Toggle password visibility">
        <svg id="eye-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      </button>
    </div>
  </div>

  <div class="auth-checkbox-row">
    <label class="auth-checkbox-label">
      <input type="checkbox" name="remember" class="auth-checkbox">
      <span>Remember this workstation</span>
    </label>
  </div>

  <button type="submit" class="auth-submit-btn">
    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
    <span>Authenticate to Dashboard</span>
  </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('toggle-pw');
  var inp = document.getElementById('password');
  if (btn && inp) {
    btn.addEventListener('click', function () {
      var isPw = inp.type === 'password';
      inp.type = isPw ? 'text' : 'password';
      btn.classList.toggle('is-visible', isPw);
    });
  }
});
</script>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
