<?php /** @var string $token */ $heading = 'Choose a new password'; $sub = 'Use at least 10 characters with upper and lower case letters, a number and a symbol.'; echo view('Crm\Views\partials\wa_authshell_top', compact('heading', 'sub')); ?>
<form method="post" action="<?= site_url('crm/reset-password') ?>" data-validate class="mt">
  <?= csrf_field() ?><input type="hidden" name="token" value="<?= esc($token) ?>">
  <div class="mb"><label for="password"><strong>New password</strong></label><input id="password" name="password" type="password" required minlength="10" autocomplete="new-password"></div>
  <div class="mb"><label for="pc"><strong>Confirm password</strong></label><input id="pc" name="password_confirm" type="password" required minlength="10" autocomplete="new-password"></div>
  <button class="btn teal" type="submit" style="width:100%;justify-content:center">Update password</button>
</form>
<p class="small mt"><a href="<?= site_url('crm/login') ?>">&larr; Back to sign in</a></p>
<?= view('Crm\Views\partials\wa_authshell_bottom') ?>
