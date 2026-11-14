<?php $heading = 'Reset your password'; $sub = 'Enter your account email and we will send a reset link, valid for 60 minutes.'; echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub')); ?>
<form method="post" action="<?= site_url('crm/forgot-password') ?>" data-validate class="mt">
  <?= csrf_field() ?>
  <div class="mb"><label for="email"><strong>Email</strong></label>
    <input id="email" name="email" type="email" required autocomplete="email" data-msg="Enter a valid email address."></div>
  <button class="btn teal" type="submit" style="width:100%;justify-content:center">Send reset link</button>
</form>
<p class="small mt"><a href="<?= site_url('crm/login') ?>">&larr; Back to sign in</a></p>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
