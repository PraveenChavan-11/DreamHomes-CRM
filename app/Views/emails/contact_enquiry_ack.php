<div style="font-size: 14px;">
  <p>Dear <strong><?= esc($name ?? 'Valued Contact') ?></strong>,</p>

  <p>Thank you for reaching out to TalentHub Consultant. We have received your inquiry regarding <strong><?= esc($subject ?? 'General Enquiry') ?></strong>.</p>

  <?php if (!empty($message)): ?>
    <div class="info-box">
      <strong>Your Message:</strong><br>
      <?= nl2br(esc($message)) ?>
    </div>
  <?php endif; ?>

  <p>Our team will review your enquiry and respond within 24 to 48 business hours.</p>

  <p style="margin-top: 24px;">
    Warm regards,<br>
    <strong>Client Support Desk</strong><br>
    TalentHub Consultant Pune
  </p>
</div>

