<?php
/**
 * TalentHub Consultant — Administrator Login Email OTP Verification
 */
$content = '
  <div style="text-align: center; margin-bottom: 24px;">
    <span class="badge" style="background: #EAF5EF; color: #1D6A34; font-size: 11px; padding: 4px 12px; border-radius: 9999px; font-weight: 800; text-transform: uppercase;">Administrative Security Check</span>
    <h2 style="color: #0F172A; font-size: 20px; font-weight: 800; margin: 12px 0 6px 0;">Your Administrator One-Time Passcode</h2>
    <p style="color: #64748B; font-size: 13px; margin: 0;">Use the following 6-digit code to complete your login to the TalentHub Administration Portal.</p>
  </div>

  <div style="text-align: center; margin: 28px 0; padding: 24px; background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 16px;">
    <span style="font-family: monospace, Courier, sans-serif; font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #1D6A34;">' . esc($otp) . '</span>
    <p style="color: #64748B; font-size: 12px; margin: 12px 0 0 0; font-weight: 600;">Valid for 10 minutes &bull; Single-use only</p>
  </div>

  <div class="info-box" style="background: #F1F5F9; border-radius: 12px; padding: 14px 18px; font-size: 12px; color: #475569;">
    <strong style="color: #0F172A; display: block; margin-bottom: 4px;">Security Context:</strong>
    &bull; Login Identifier: <strong>' . esc($user['username'] ?? $user['email']) . '</strong><br>
    &bull; Requested At: ' . esc($time ?? date('M j, Y g:i A')) . '<br>
    &bull; Remote IP Address: ' . esc($ip ?? 'Unknown') . '
  </div>

  <p style="font-size: 12px; color: #94A3B8; text-align: center; margin-top: 24px;">
    If you did not initiate this sign-in attempt, please immediately change your administrative password and notify the Super Administrator.
  </p>
';

echo view('emails/layout', [
    'subject' => 'TalentHub Admin Verification Code: ' . $otp,
    'content' => $content,
]);

