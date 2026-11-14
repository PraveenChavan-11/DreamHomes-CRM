<div style="font-size: 14px;">
  <p>Hello <strong><?= esc($username ?? 'Administrator') ?></strong>,</p>

  <p>A request was received to reset the password for your TalentHub Consultant administrative portal account.</p>

  <p style="text-align: center; margin: 24px 0;">
    <a href="<?= esc($reset_link ?? '#') ?>" class="btn">Reset Password</a>
  </p>

  <p style="font-size: 12px; color: #64748b;">
    This single-use reset link expires in 60 minutes. If you did not make this request, you can safely disregard this email.
  </p>
</div>

