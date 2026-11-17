<?php
/** @var string $mode @var array $activity @var list<array> $organisations @var list<array> $contacts @var list<array> $users @var list<array> $projects */
$isEdit = ($mode === 'edit');
$types = [
    'customer_visit' => 'Site / Customer Visit',
    'phone_call' => 'Telephone Call',
    'architect_meeting' => 'Architect / Designer Meeting',
    'site_measurement' => 'Site Survey & Dimension Recording',
    'quotation_presentation' => 'Quotation & Commercial Presentation',
    'client_demo' => 'Showroom Demo / Sample Presentation',
    'follow_up' => 'Commercial Follow-up',
    'payment_collection' => 'Payment & Cheque Collection',
];
$action = $isEdit ? site_url('crm/activities/' . $activity['id'] . '/update') : site_url('crm/activities/create');
?>
<div class="pagehead">
  <div>
    <h1><?= $isEdit ? 'Edit Activity #' . esc($activity['activity_no']) : 'Record Sales Activity' ?></h1>
    <div class="muted small"><?= $isEdit ? 'Update scheduled interaction details, assigned salesperson, and agenda.' : 'Schedule or log a customer touchpoint, field visit, architect presentation, or follow-up.' ?></div>
  </div>
  <div class="gap">
    <a class="btn alt" href="<?= $isEdit ? site_url('crm/activities/' . $activity['id']) : site_url('crm/activities') ?>">Cancel</a>
  </div>
</div>

<form class="card" method="post" action="<?= $action ?>" style="max-width:860px">
  <?= csrf_field() ?>
  <div class="hd">
    <h2>1. Activity Classification &amp; Linkage</h2>
  </div>
  <div class="bd grid g2">
    <div>
      <label for="activity_type">Activity Type <span class="req">*</span></label>
      <select id="activity_type" name="activity_type" required>
        <?php foreach ($types as $k => $lbl): ?>
          <option value="<?= $k ?>"<?= crm_sel(crm_old('activity_type', $activity['activity_type'] ?? 'customer_visit'), $k) ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="organisation_id">Account / Organisation</label>
      <select id="organisation_id" name="organisation_id">
        <option value="">— Select Organisation (Optional) —</option>
        <?php foreach ($organisations as $o): ?>
          <option value="<?= (int) $o['id'] ?>"<?= crm_sel(crm_old('organisation_id', $activity['organisation_id'] ?? ''), $o['id']) ?>><?= esc($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="contact_id">Contact Person</label>
      <select id="contact_id" name="contact_id">
        <option value="">— Select Contact Person —</option>
        <?php foreach ($contacts as $c): ?>
          <option value="<?= (int) $c['id'] ?>"<?= crm_sel(crm_old('contact_id', $activity['contact_id'] ?? ''), $c['id']) ?>><?= esc($c['first_name'] . ' ' . $c['last_name']) ?> (<?= esc($c['mobile']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="project_id">Architectural Project</label>
      <select id="project_id" name="project_id">
        <option value="">— Select Project (Optional) —</option>
        <?php foreach ($projects as $p): ?>
          <option value="<?= (int) $p['id'] ?>"<?= crm_sel(crm_old('project_id', $activity['project_id'] ?? ''), $p['id']) ?>><?= esc($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="hd" style="border-top:1px solid var(--line)">
    <h2>2. Schedule &amp; Execution Coordinates</h2>
  </div>
  <div class="bd grid g2">
    <div style="grid-column:1/-1">
      <label for="subject">Subject / Meeting Title <span class="req">*</span></label>
      <input type="text" id="subject" name="subject" required value="<?= esc(crm_old('subject', $activity['subject'] ?? '')) ?>" placeholder="e.g. Initial product catalogue presentation &amp; preliminary opening survey">
      <?= crm_err('subject') ?>
    </div>
    <div>
      <label for="activity_date">Activity Date <span class="req">*</span></label>
      <input type="date" id="activity_date" name="activity_date" required value="<?= esc(crm_old('activity_date', $activity['activity_date'] ?? date('Y-m-d'))) ?>">
    </div>
    <div>
      <label for="scheduled_at">Scheduled Time</label>
      <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= esc(crm_old('scheduled_at', ! empty($activity['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($activity['scheduled_at'])) : date('Y-m-d\T10:00'))) ?>">
    </div>
    <div>
      <label for="assigned_user_id">Assigned Sales Executive <span class="req">*</span></label>
      <select id="assigned_user_id" name="assigned_user_id" required>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"<?= crm_sel(crm_old('assigned_user_id', $activity['assigned_user_id'] ?? crm_user()['id'] ?? ''), $u['id']) ?>><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="location">Location / Venue</label>
      <input type="text" id="location" name="location" value="<?= esc(crm_old('location', $activity['location'] ?? '')) ?>" placeholder="e.g. Site Office / Client Studio / Showroom">
    </div>
    <div style="grid-column:1/-1">
      <label for="description">Agenda / Activity Brief</label>
      <textarea id="description" name="description" rows="3" placeholder="Key topics to discuss, sample profiles to carry, specific opening sizes to verify..."><?= esc(crm_old('description', $activity['description'] ?? '')) ?></textarea>
    </div>
  </div>

  <div class="ft" style="display:flex;justify-content:flex-end;gap:12px;padding:16px 22px">
    <a class="btn alt" href="<?= site_url('crm/activities') ?>">Cancel</a>
    <button class="btn teal" type="submit"><?= $isEdit ? 'Save Changes' : 'Schedule Activity' ?></button>
  </div>
</form>
