<div style="font-size: 14px;">
  <p>Dear <strong><?= esc($candidate_name ?? 'Candidate') ?></strong>,</p>

  <p>Thank you for submitting your application for the position of <strong><?= esc($job_title ?? 'Open Position') ?></strong> with TalentHub Consultant.</p>

  <div class="info-box">
    <p style="margin: 0 0 8px 0;"><strong>Application Reference Details:</strong></p>
    <ul style="margin: 0; padding-left: 20px;">
      <li>Role: <strong><?= esc($job_title ?? '') ?></strong></li>
      <li>Submitted: <strong><?= date('d M Y, h:i A') ?></strong></li>
      <li>Notice Period: <strong><?= esc($notice_period ?? 'N/A') ?></strong></li>
    </ul>
  </div>

  <p>Our talent acquisition consultants are evaluating your credentials against the client mandate. If shortlisted, a recruiter will contact you within 3 to 5 business days to schedule an interview briefing.</p>

  <p style="margin-top: 24px; padding: 12px; background: #EAF5EF; border-radius: 8px; font-size: 12px; color: #1D6A34;">
    <strong>Candidate Protection Guarantee:</strong> TalentHub Consultant never charges any fees from job seekers for placement, registrations, or interviews.
  </p>

  <p style="margin-top: 24px;">
    Warm regards,<br>
    <strong>Talent Acquisition Desk</strong><br>
    TalentHub Consultant Pune
  </p>
</div>

