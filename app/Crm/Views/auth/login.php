<?php $heading = 'Sign in'; $sub = 'Sign in to access your sales workspace.'; echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub')); ?>
<form method="post" action="<?= site_url('crm/login') ?>" data-validate class="mt">
  <?= csrf_field() ?>
  <div class="mb">
    <label for="identity"><strong>Username or email</strong></label>
    <input id="identity" name="identity" type="text" value="<?= esc(old('identity')) ?>" required autocomplete="username" autofocus data-msg="Enter your username or email." placeholder="name@talenthubconsultant.com">
  </div>
  <div class="mb">
    <label for="password"><strong>Password</strong></label>
    <div style="position:relative">
      <input id="password" name="password" type="password" required autocomplete="current-password" data-msg="Enter your password." style="padding-right:40px">
      <button type="button" id="toggle-pw" aria-label="Toggle password visibility" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--muted);cursor:pointer;padding:4px">
        <?= crm_icon('M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z', 18) ?>
      </button>
    </div>
  </div>
  <button class="btn teal" type="submit" style="width:100%;justify-content:center;padding:10px;font-size:14px">Sign in</button>
</form>
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;font-size:13px">
  <a href="<?= site_url('crm/forgot-password') ?>">Forgot password?</a>
  <span class="muted small">TalentHub Partner</span>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('toggle-pw');
  var inp = document.getElementById('password');
  if (btn && inp) {
    btn.addEventListener('click', function () {
      var isPw = inp.type === 'password';
      inp.type = isPw ? 'text' : 'password';
      btn.style.opacity = isPw ? '1' : '0.6';
    });
  }
});
</script>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
