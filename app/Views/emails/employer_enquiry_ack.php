<div style="font-size: 14px;">
  <p>Dear <strong><?= esc($contact_person ?? 'Hiring Leader') ?></strong>,</p>

  <p>Thank you for submitting your recruitment requirement for <strong><?= esc($company_name ?? 'your organization') ?></strong>.</p>

  <div class="info-box">
    <p style="margin: 0 0 8px 0;"><strong>Hiring Mandate Parameters:</strong></p>
    <ul style="margin: 0; padding-left: 20px;">
      <li>Organization: <strong><?= esc($company_name ?? '') ?></strong></li>
      <li>Role Required: <strong><?= esc($job_title ?? 'Specified Role') ?></strong></li>
      <li>Target Openings: <strong><?= esc($openings_count ?? '1') ?></strong></li>
      <li>Location: <strong><?= esc($location ?? 'Pune') ?></strong></li>
    </ul>
  </div>

  <p>A Senior Account Lead has been assigned to your requirement and will contact you within 24 business hours with our sourcing strategy and SLA roadmap.</p>

  <p style="margin-top: 24px;">
    Warm regards,<br>
    <strong>Corporate Recruitment Division</strong><br>
    TalentHub Consultant Pune
  </p>
</div>

