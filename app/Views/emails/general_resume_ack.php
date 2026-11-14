<div style="font-size: 14px;">
  <p>Dear <strong><?= esc($candidate_name ?? 'Candidate') ?></strong>,</p>

  <p>Thank you for submitting your resume to the TalentHub Consultant talent pool.</p>

  <div class="info-box">
    <p style="margin: 0 0 8px 0;"><strong>Profile Summary Recorded:</strong></p>
    <ul style="margin: 0; padding-left: 20px;">
      <li>Candidate: <strong><?= esc($candidate_name ?? '') ?></strong></li>
      <li>Preferred Domain: <strong><?= esc($preferred_role ?? 'General') ?></strong></li>
      <li>Experience: <strong><?= esc($experience_years ?? '0') ?> Years</strong></li>
      <li>Preferred Location: <strong><?= esc($preferred_location ?? 'Pune') ?></strong></li>
    </ul>
  </div>

  <p>Your resume has been indexed in our secure candidate vault. When an active mandate matching your qualifications opens across Pune or Maharashtra, our consultants will reach out directly.</p>

  <p style="margin-top: 24px;">
    Warm regards,<br>
    <strong>Talent Acquisition Desk</strong><br>
    TalentHub Consultant Pune
  </p>
</div>

