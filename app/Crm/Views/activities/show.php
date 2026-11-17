<?php
/** @var array $activity */
$act = $activity;
?>
<div class="pagehead">
  <div>
    <h1>
      <?= esc($act['subject']) ?>
      <?= crm_badge($act['status']) ?>
      <span class="badge badge-blue"><?= esc(crm_label($act['activity_type'])) ?></span>
    </h1>
    <div class="muted small" style="font-weight:400">
      Activity #<?= esc($act['activity_no']) ?> · Date: <?= esc(crm_date($act['activity_date'])) ?>
      <?php if (! empty($act['assigned_user_name'])): ?> · Assigned: <strong><?= esc($act['assigned_user_name']) ?></strong><?php endif; ?>
    </div>
  </div>
  <div class="gap">
    <?php if ($act['status'] === 'scheduled'): ?>
      <button class="btn teal" type="button" data-modal="modal-complete">✓ Complete Activity</button>
      <button class="btn alt" type="button" data-modal="modal-reschedule">Reschedule</button>
    <?php endif; ?>
    <?php if (empty($act['converted_to_enquiry_id']) && crm_can('enquiries.create')): ?>
      <button class="btn alt" type="button" data-modal="modal-convert">⚡ Convert to Lead / Opportunity</button>
    <?php endif; ?>
    <?php if (crm_can('followups.manage')): ?>
      <a class="btn alt" href="<?= site_url('crm/activities/' . $act['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
    <a class="btn alt" href="<?= site_url('crm/activities') ?>">Back to List</a>
  </div>
</div>

<?php if (! empty($act['converted_to_enquiry_id'])): ?>
  <div class="card" style="margin-bottom:18px;background:#F0FDF4;border-color:#BBF7D0;padding:16px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <div>
        <strong style="color:#166534">✓ Converted to Sales Lead Opportunity</strong>
        <div class="small muted">This activity was formally qualified and converted on <?= esc(crm_dt($act['converted_at'])) ?>. Context and client notes were preserved.</div>
      </div>
      <a class="btn teal sm" href="<?= site_url('crm/enquiries/' . $act['converted_to_enquiry_id']) ?>">Open Opportunity <?= esc($act['converted_enquiry_no'] ?? '#' . $act['converted_to_enquiry_id']) ?> &rarr;</a>
    </div>
  </div>
<?php endif; ?>

<div class="grid g-side">
  <div class="grid">
    <div class="card">
      <div class="hd"><h2>Activity Specifications</h2></div>
      <div class="bd">
        <dl class="kv">
          <dt>Activity Number</dt><dd><strong><?= esc($act['activity_no']) ?></strong></dd>
          <dt>Activity Type</dt><dd><?= esc(crm_label($act['activity_type'])) ?></dd>
          <dt>Scheduled Date &amp; Time</dt><dd><?= esc(crm_dt($act['scheduled_at'] ?: $act['activity_date'])) ?></dd>
          <dt>Organisation / Account</dt>
          <dd>
            <?php if (! empty($act['organisation_id'])): ?>
              <a href="<?= site_url('crm/organisations/' . $act['organisation_id']) ?>"><strong><?= esc($act['organisation_name']) ?></strong></a>
            <?php else: ?>
              <span class="muted">Independent Activity (No Organisation Linked)</span>
            <?php endif; ?>
          </dd>
          <dt>Contact Person</dt>
          <dd>
            <?php if (! empty($act['contact_name'])): ?>
              <?= esc($act['contact_name']) ?> (<?= esc($act['contact_phone'] ?? '—') ?>)
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </dd>
          <dt>Architectural Project</dt>
          <dd>
            <?php if (! empty($act['project_name'])): ?>
              <a href="<?= site_url('crm/projects/' . $act['project_id']) ?>"><?= esc($act['project_name']) ?></a>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </dd>
          <dt>Location / Venue</dt><dd><?= esc($act['location'] ?: '—') ?></dd>
          <dt>Assigned Rep</dt><dd><strong><?= esc($act['assigned_user_name'] ?? '—') ?></strong></dd>
          <dt>Status</dt><dd><?= crm_badge($act['status']) ?></dd>
          <dt>Agenda &amp; Brief</dt><dd><?= nl2br(esc($act['description'] ?: '—')) ?></dd>
        </dl>
      </div>
    </div>

    <!-- Outcome Card -->
    <div class="card" id="complete">
      <div class="hd"><h2>Execution Outcome &amp; Minutes of Meeting</h2></div>
      <div class="bd">
        <?php if ($act['status'] === 'completed'): ?>
          <dl class="kv">
            <dt>Completion Status</dt><dd><span class="badge badge-green">Completed</span> on <?= esc(crm_dt($act['completed_at'])) ?></dd>
            <dt>Recorded Outcome</dt><dd><strong><?= esc(crm_label($act['outcome'])) ?></strong></dd>
            <dt>Minutes / Next Steps</dt><dd><?= nl2br(esc($act['outcome_notes'] ?: 'No additional notes recorded.')) ?></dd>
          </dl>
        <?php else: ?>
          <p class="muted">This activity is currently scheduled. Once you finish the interaction, click Complete to log outcomes, customer feedback, and next steps.</p>
          <button class="btn teal" type="button" data-modal="modal-complete">Complete Activity Now</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="grid" style="align-content:start">
    <div class="card">
      <div class="hd"><h2>Sales Governance Rules</h2></div>
      <div class="bd" style="font-size:13px;line-height:1.6">
        <p><strong>1. Independent Activity:</strong> Sales activities can exist freely without requiring an active lead.</p>
        <p><strong>2. Qualification:</strong> When client interest is verified, click <em>Convert to Lead</em> to transfer all context seamlessly.</p>
        <p><strong>3. Traceability:</strong> Conversion cannot be duplicated. The original activity will permanently link to the resulting opportunity.</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Complete Activity -->
<div class="modal" id="modal-complete" role="dialog" aria-modal="true" aria-label="Complete Activity">
  <div class="box">
    <div class="hd">
      <h3>Complete Sales Activity</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/activities/' . $act['id'] . '/complete') ?>">
      <?= csrf_field() ?>
      <div class="bd">
        <label for="outcome">Activity Outcome <span class="req">*</span></label>
        <select id="outcome" name="outcome" required style="margin-bottom:12px">
          <option value="interested_qualified">Interested &amp; Qualified (Ready for Quotation)</option>
          <option value="measurement_taken">Site Measurements Successfully Recorded</option>
          <option value="proposal_presented">Quotation Presented to Decision Maker</option>
          <option value="followup_required">Follow-up Meeting Required</option>
          <option value="not_interested">Not Interested at Present</option>
          <option value="budget_mismatch">Budget / Pricing Mismatch</option>
          <option value="rescheduled">Client Requested Reschedule</option>
        </select>

        <label for="outcome_notes">Outcome Notes &amp; Client Feedback <span class="req">*</span></label>
        <textarea id="outcome_notes" name="outcome_notes" rows="3" required placeholder="Detailed notes on what was discussed, client reaction, openings surveyed..."></textarea>
      </div>
      <div class="ft" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn alt" data-close>Cancel</button>
        <button type="submit" class="btn teal">Mark as Completed</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Reschedule Activity -->
<div class="modal" id="modal-reschedule" role="dialog" aria-modal="true" aria-label="Reschedule Activity">
  <div class="box">
    <div class="hd">
      <h3>Reschedule Sales Activity</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/activities/' . $act['id'] . '/reschedule') ?>">
      <?= csrf_field() ?>
      <div class="bd">
        <label for="new_date">New Scheduled Date <span class="req">*</span></label>
        <input type="date" id="new_date" name="new_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" style="margin-bottom:12px">

        <label for="reason">Reason for Rescheduling</label>
        <textarea id="reason" name="reason" rows="2" placeholder="e.g. Client requested postponement due to site civil work delay..."></textarea>
      </div>
      <div class="ft" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn alt" data-close>Cancel</button>
        <button type="submit" class="btn teal">Confirm Reschedule</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Convert to Lead -->
<div class="modal" id="modal-convert" role="dialog" aria-modal="true" aria-label="Convert to Lead">
  <div class="box">
    <div class="hd">
      <h3>Convert Activity to Sales Lead Opportunity</h3>
      <button type="button" class="btn alt sm" data-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('crm/activities/' . $act['id'] . '/convert') ?>">
      <?= csrf_field() ?>
      <div class="bd">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">
          This will generate a formal Opportunity Lead in the pipeline, transferring all activity details, account linkage, and notes without duplicating records.
        </p>
        <label for="lead_title">Opportunity Title <span class="req">*</span></label>
        <input type="text" id="lead_title" name="lead_title" required value="<?= esc($act['subject']) ?>" style="margin-bottom:12px">

        <label for="estimated_value">Estimated Commercial Value (₹)</label>
        <input type="number" step="1000" id="estimated_value" name="estimated_value" placeholder="e.g. 250000" style="margin-bottom:12px">

        <label for="stage">Initial Pipeline Stage</label>
        <select id="stage" name="stage">
          <option value="qualified">Qualified Opportunity</option>
          <option value="site_visit">Site Visit Scheduled</option>
          <option value="quotation_preparation">Quotation Preparation</option>
        </select>
      </div>
      <div class="ft" style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn alt" data-close>Cancel</button>
        <button type="submit" class="btn teal">Convert to Lead Now</button>
      </div>
    </form>
  </div>
</div>
