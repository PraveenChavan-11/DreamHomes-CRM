<div style="font-size: 14px;">
  <h3 style="color: #1D6A34; margin: 0 0 12px 0;">New Job Application Received</h3>

  <p>A candidate has submitted an application through the website portal:</p>

  <div class="info-box">
    <ul style="margin: 0; padding-left: 20px;">
      <li>Candidate Name: <strong><?= esc($candidate_name ?? 'N/A') ?></strong></li>
      <li>Role Applied: <strong><?= esc($job_title ?? 'N/A') ?></strong></li>
      <li>Email: <strong><?= esc($email ?? 'N/A') ?></strong></li>
      <li>Phone: <strong><?= esc($phone ?? 'N/A') ?></strong></li>
      <li>Total Experience: <strong><?= esc($experience_years ?? '0') ?> Years</strong></li>
      <li>Current CTC: <strong><?= esc($current_ctc ?? 'Not Disclosed') ?></strong></li>
      <li>Expected CTC: <strong><?= esc($expected_ctc ?? 'Not Disclosed') ?></strong></li>
      <li>Notice Period: <strong><?= esc($notice_period ?? 'Immediate') ?></strong></li>
    </ul>
  </div>

  <p>
    <a href="<?= esc($admin_url ?? base_url('admin')) ?>" class="btn">View in Admin Portal</a>
  </p>
</div>

