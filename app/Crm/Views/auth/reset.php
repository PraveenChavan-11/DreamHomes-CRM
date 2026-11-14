<?php
/** @var string $token */
$heading = 'Set New Password';
$sub = 'Use at least 10 characters with uppercase, lowercase, numbers, and symbols.';
echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub'));
?>
<form method="post" action="<?= site_url('crm/reset-password') ?>" data-validate class="auth-form-inner space-y-5">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= esc($token) ?>">

  <div class="auth-field-group">
    <label for="password" class="auth-label">New Password</label>
    <div class="auth-input-wrapper">
      <span class="auth-input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </span>
      <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password" placeholder="••••••••••••" class="auth-input">
    </div>
  </div>

  <div class="auth-field-group">
    <label for="pc" class="auth-label">Confirm New Password</label>
    <div class="auth-input-wrapper">
      <span class="auth-input-icon">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
      </span>
      <input type="password" id="pc" name="password_confirm" required minlength="10" autocomplete="new-password" placeholder="••••••••••••" class="auth-input">
    </div>
  </div>

  <button type="submit" class="auth-submit-btn">
    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    <span>Update &amp; Secure Account</span>
  </button>
</form>

<div style="margin-top:20px; text-align:center;">
  <a href="<?= site_url('crm/login') ?>" class="auth-forgot-link" style="display:inline-flex;align-items:center;gap:6px">
    <span>&larr;</span>
    <span>Back to sign in</span>
  </a>
</div>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
