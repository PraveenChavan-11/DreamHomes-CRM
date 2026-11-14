<?php $heading = 'Sign in'; $sub = 'Use your DHIS CRM username or email.'; echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub')); ?>
<form method="post" action="<?= site_url('crm/login') ?>" data-validate class="mt">
  <?= csrf_field() ?>
  <div class="mb"><label for="identity"><strong>Username or email</strong></label>
    <input id="identity" name="identity" type="text" value="<?= esc(old('identity')) ?>" required autocomplete="username" autofocus data-msg="Enter your username or email."></div>
  <div class="mb"><label for="password"><strong>Password</strong></label>
    <input id="password" name="password" type="password" required autocomplete="current-password" data-msg="Enter your password."></div>
  <button class="btn teal" type="submit" style="width:100%;justify-content:center">Sign in</button>
</form>
<p class="small mt"><a href="<?= site_url('crm/forgot-password') ?>">Forgot your password?</a></p>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
