<?php
$heading = 'Reset Your Password';
$sub = 'Enter your account email and we will send a secure reset link valid for 60 minutes.';
echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub'));
?>
<form method="post" action="<?= site_url('crm/forgot-password') ?>" data-validate class="auth-form-inner space-y-5">
  <?= csrf_field() ?>

  <div class="auth-field-group">
    <label for="email" class="auth-label">Registered Email</label>
    <div class="auth-input-wrapper">
      <span class="auth-input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </span>
      <input type="email" id="email" name="email" value="<?= esc(old('email')) ?>" required autofocus autocomplete="email" placeholder="name@talenthubconsultant.com" class="auth-input" data-msg="Enter a valid email address.">
    </div>
  </div>

  <button type="submit" class="auth-submit-btn">
    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
    <span>Send Password Reset Link</span>
  </button>
</form>

<div style="margin-top:20px; text-align:center;">
  <a href="<?= site_url('crm/login') ?>" class="auth-forgot-link" style="display:inline-flex;align-items:center;gap:6px">
    <span>&larr;</span>
    <span>Back to sign in</span>
  </a>
</div>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
