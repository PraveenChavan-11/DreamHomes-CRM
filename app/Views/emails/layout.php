<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= esc($subject ?? 'TalentHub Notification') ?></title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f8fafc; color: #1e293b; line-height: 1.6; }
    .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .email-header { background: #072412; padding: 32px 24px; text-align: center; }
    .email-body { padding: 32px 28px; }
    .email-footer { background: #f8fafc; padding: 24px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
    .btn { display: inline-block; padding: 12px 28px; background: #1D6A34; color: #ffffff !important; text-decoration: none; font-weight: bold; border-radius: 10px; font-size: 14px; margin-top: 16px; }
    .info-box { background: #f1f5f9; border-radius: 10px; padding: 16px 20px; margin: 20px 0; font-size: 13px; color: #334155; }
    .badge { display: inline-block; padding: 4px 10px; background: #EAF5EF; color: #1D6A34; font-size: 11px; font-weight: bold; border-radius: 6px; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1 style="color: #ffffff; font-size: 22px; margin: 0; font-weight: 800; letter-spacing: -0.5px;">TalentHub Consultant</h1>
      <p style="color: #37CE97; font-size: 12px; margin: 4px 0 0 0; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Recruitment &amp; Staffing Solutions &bull; Pune</p>
    </div>
    <div class="email-body">
      <?= $content ?? '' ?>
    </div>
    <div class="email-footer">
      <p style="margin: 0 0 8px 0; font-weight: 600; color: #334155;">TalentHub Consultant</p>
      <p style="margin: 0 0 8px 0;">Katraj, Pune &ndash; 411046, Maharashtra, India</p>
      <p style="margin: 0;">Phone: +91 72761 46573 &bull; Email: hr@talenthubconsultant.com</p>
      <p style="margin: 12px 0 0 0; font-size: 11px; color: #94a3b8;">&copy; <?= date('Y') ?> TalentHub Consultant. Confidential recruitment communication.</p>
    </div>
  </div>
</body>
</html>

