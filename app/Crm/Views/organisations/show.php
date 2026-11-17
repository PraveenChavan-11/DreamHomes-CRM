<?php
/** @var array $organisation @var array|null $primary_contact @var list<array> $contacts @var list<array> $projects @var list<array> $enquiries @var list<array> $activities @var list<array> $quotations @var list<array> $approvals @var list<array> $orders @var list<array> $payments @var list<array> $fulfilment @var list<array> $communications @var list<array> $audit_logs @var array $stats @var string $activeTab */
$org = $organisation;
$base = site_url('crm/organisations/' . $org['id']);
$tab = $activeTab ?: 'overview';
$tabs = [
    'overview' => '360° Overview',
    'contacts' => 'Contacts (' . count($contacts) . ')',
    'projects' => 'Projects (' . count($projects) . ')',
    'enquiries' => 'Enquiries & Leads (' . count($enquiries) . ')',
    'activities' => 'Activities (' . count($activities) . ')',
    'quotations' => 'Quotations (' . count($quotations) . ')',
    'approvals' => 'Commercial Approvals (' . count($approvals) . ')',
    'orders' => 'Orders (' . count($orders) . ')',
    'payments' => 'Payments (' . count($payments) . ')',
    'communications' => 'Communications (' . count($communications) . ')',
    'audit' => 'Audit History',
];
?>
<div class="pagehead">
  <div>
    <h1>
      <?= esc($org['name']) ?>
      <?= crm_badge($org['account_status'] ?? ($org['status'] ?? 'active')) ?>
      <?php if (! empty($org['account_rating'])): ?>
        <span class="badge badge-<?= match($org['account_rating']) { 'Hot' => 'red', 'Warm' => 'amber', default => 'blue' } ?>"><?= esc($org['account_rating']) ?> Rating</span>
      <?php endif; ?>
    </h1>
    <div class="muted small" style="font-weight:400">
      <?= esc($org['org_no']) ?> · <?= esc($org['org_type'] ?? ($org['type'] ?? 'Corporate')) ?><?= !empty($org['industry']) ? ' · ' . esc($org['industry']) : '' ?><?= !empty($org['city']) ? ' · ' . esc($org['city']) : '' ?>
      <?php if (!empty($org['account_owner_name'] ?? ($org['owner_name'] ?? null))): ?> · Owner: <strong><?= esc($org['account_owner_name'] ?? $org['owner_name']) ?></strong><?php endif; ?>
    </div>
  </div>
  <div class="gap">
    <?php if (crm_can('enquiries.create')): ?>
      <a class="btn teal" href="<?= site_url('crm/enquiries/new?organisation_id=' . $org['id']) ?>">+ New Enquiry</a>
    <?php endif; ?>
    <?php if (crm_can('customers.create')): ?>
      <a class="btn alt" href="<?= site_url('crm/contacts/new?organisation_id=' . $org['id']) ?>">+ Add Contact</a>
    <?php endif; ?>
    <a class="btn alt" href="<?= site_url('crm/activities/new?organisation_id=' . $org['id']) ?>">+ Log Activity</a>
    <?php if (crm_can('customers.edit')): ?>
      <a class="btn alt" href="<?= $base ?>/edit">Edit Organisation</a>
    <?php endif; ?>
  </div>
</div>

<nav class="tabs" aria-label="Organisation sections" style="margin-bottom:16px">
<?php foreach ($tabs as $k => $label): ?>
  <a href="<?= $base ?>?tab=<?= $k ?>" class="<?= $tab === $k ? 'on' : '' ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
<?php endforeach; ?>
</nav>

<?php if ($tab === 'overview'): ?>
<div class="grid g-side">
  <div class="grid">
    <!-- Organisation Profile Card -->
    <div class="card">
      <div class="hd"><h2>Organisation Profile &amp; Statutory Compliance</h2></div>
      <div class="bd">
        <dl class="kv">
          <dt>Organisation Number</dt><dd><strong><?= esc($org['org_no']) ?></strong></dd>
          <dt>Organisation Type</dt><dd><?= esc($org['org_type'] ?? ($org['type'] ?? '—')) ?></dd>
          <dt>Industry / Category</dt><dd><?= esc($org['industry'] ?? '—') ?> (<?= esc($org['business_category'] ?? 'General') ?>)</dd>
          <dt>Website</dt><dd><?= !empty($org['website']) ? '<a href="' . esc($org['website']) . '" target="_blank" rel="noopener">' . esc($org['website']) . '</a>' : '—' ?></dd>
          <dt>GSTIN</dt><dd><?= esc($org['gstin'] ?: '—') ?></dd>
          <dt>PAN Number</dt><dd><?= esc($org['pan'] ?: '—') ?></dd>
          <dt>Primary Coordinates</dt><dd><?= esc($org['primary_phone'] ?? '—') ?><?= !empty($org['primary_email']) ? ' · <a href="mailto:' . esc($org['primary_email']) . '">' . esc($org['primary_email']) . '</a>' : '' ?></dd>
          <dt>Registered Address</dt><dd><?= nl2br(esc($org['registered_address'] ?: '—')) ?></dd>
          <dt>Billing Address</dt><dd><?= nl2br(esc($org['billing_address'] ?: '—')) ?></dd>
          <dt>Default Site Address</dt><dd><?= nl2br(esc($org['default_site_address'] ?: '—')) ?></dd>
          <dt>City / State / PIN</dt><dd><?= esc(trim(($org['city'] ?? '') . ', ' . ($org['state'] ?? '') . ' ' . ($org['pincode'] ?? ($org['pin_code'] ?? ''))) ?: '—') ?></dd>
          <dt>Account Owner</dt><dd><strong><?= esc($org['account_owner_name'] ?? ($org['owner_name'] ?? '—')) ?></strong></dd>
          <dt>Sales Territory</dt><dd><?= esc($org['territory_name'] ?? '—') ?></dd>
          <dt>Lead Source</dt><dd><?= esc($org['lead_source_name'] ?? ($org['source_name'] ?? '—')) ?></dd>
          <dt>Strategic Notes</dt><dd><?= nl2br(esc($org['notes'] ?: '—')) ?></dd>
        </dl>
      </div>
    </div>

    <!-- Primary Contact Card -->
    <div class="card">
      <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
        <h2>Primary Decision Maker / Point of Contact</h2>
        <?php if (! empty($primary_contact)): ?>
          <a class="btn sm alt" href="<?= site_url('crm/contacts/' . $primary_contact['id'] . '/edit') ?>">Edit Contact</a>
        <?php else: ?>
          <a class="btn sm teal" href="<?= site_url('crm/contacts/new?organisation_id=' . $org['id']) ?>">+ Add Primary Contact</a>
        <?php endif; ?>
      </div>
      <div class="bd">
        <?php if (! empty($primary_contact)): ?>
          <div style="display:flex;gap:16px;align-items:flex-start">
            <div style="width:48px;height:48px;border-radius:50%;background:var(--th-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px">
              <?= strtoupper(substr($primary_contact['first_name'], 0, 1) . substr($primary_contact['last_name'], 0, 1)) ?>
            </div>
            <div style="flex:1">
              <h3 style="margin:0 0 4px;font-size:16px">
                <?= esc($primary_contact['first_name'] . ' ' . $primary_contact['last_name']) ?>
                <span class="badge badge-green">Primary</span>
                <?php if ($primary_contact['is_decision_maker']): ?><span class="badge badge-blue">Decision Maker</span><?php endif; ?>
              </h3>
              <div class="muted small"><?= esc($primary_contact['designation'] ?? 'Contact') ?> · <?= esc($primary_contact['contact_role'] ?? 'General') ?></div>
              <div style="margin-top:8px;display:flex;gap:18px;font-size:13px">
                <div>📞 <strong><?= esc($primary_contact['mobile']) ?></strong></div>
                <?php if ($primary_contact['email']): ?><div>✉️ <a href="mailto:<?= esc($primary_contact['email']) ?>"><?= esc($primary_contact['email']) ?></a></div><?php endif; ?>
                <?php if (!empty($primary_contact['whatsapp'] ?? ($primary_contact['whatsapp_no'] ?? null))): ?><div>💬 WhatsApp: <?= esc($primary_contact['whatsapp'] ?? $primary_contact['whatsapp_no']) ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="empty">No primary contact designated yet. Add contacts to this organisation.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KPI Column -->
  <div class="grid" style="align-content:start;gap:12px">
    <div class="card kpi good">
      <div class="l">Confirmed Orders Value</div>
      <div class="v"><?= crm_money0($stats['total_revenue'] ?? 0) ?></div>
      <div class="s"><?= (int) ($stats['orders_count'] ?? 0) ?> sales orders</div>
    </div>
    <div class="card kpi">
      <div class="l">Collections Received</div>
      <div class="v"><?= crm_money0($stats['total_collected'] ?? 0) ?></div>
      <div class="s"><?= count($payments) ?> payments logged</div>
    </div>
    <div class="card kpi">
      <div class="l">Active Enquiries / Leads</div>
      <div class="v"><?= (int) ($stats['enquiries_count'] ?? 0) ?></div>
      <div class="s"><?= count(array_filter($enquiries, static fn($e) => $e['status'] === 'won')) ?> won</div>
    </div>
    <div class="card kpi">
      <div class="l">Quotations Generated</div>
      <div class="v"><?= count($quotations) ?></div>
      <div class="s"><?= count(array_filter($quotations, static fn($q) => ! empty($q['is_commercially_approved']))) ?> approved</div>
    </div>
    <div class="card kpi">
      <div class="l">Sales Activities Recorded</div>
      <div class="v"><?= count($activities) ?></div>
      <div class="s"><?= count(array_filter($activities, static fn($a) => $a['status'] === 'completed')) ?> completed</div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'contacts'): ?>
<div class="card">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Organisation Contacts (<?= count($contacts) ?>)</h2>
    <a class="btn sm teal" href="<?= site_url('crm/contacts/new?organisation_id=' . $org['id']) ?>">+ Add New Contact</a>
  </div>
  <div class="bd flush">
    <?php if (! $contacts): ?>
      <div class="empty">No contacts linked to this organisation yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Contact Person</th><th>Designation &amp; Role</th><th>Decision Maker</th><th>Phone &amp; WhatsApp</th><th>Email</th><th>Channel</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($contacts as $cnt): ?>
          <tr>
            <td>
              <strong><?= esc($cnt['first_name'] . ' ' . $cnt['last_name']) ?></strong>
              <?php if (! empty($cnt['is_primary'])): ?><span class="badge badge-green">Primary</span><?php endif; ?>
            </td>
            <td><?= esc($cnt['designation'] ?? '—') ?><div class="muted small"><?= esc(crm_label($cnt['contact_role'] ?? '')) ?></div></td>
            <td><?= ! empty($cnt['is_decision_maker']) ? '<span class="badge badge-blue">Key Decision Maker</span>' : 'Influencer / Staff' ?></td>
            <td><?= esc($cnt['mobile']) ?><?= $cnt['whatsapp_no'] ? '<div class="muted small">WA: ' . esc($cnt['whatsapp_no']) . '</div>' : '' ?></td>
            <td><?= $cnt['email'] ? '<a href="mailto:' . esc($cnt['email']) . '">' . esc($cnt['email']) . '</a>' : '—' ?></td>
            <td><?= esc(crm_label($cnt['preferred_channel'] ?? 'phone')) ?></td>
            <td><?= crm_badge($cnt['status'] ?? 'active') ?></td>
            <td class="nowrap">
              <a class="btn sm alt" href="<?= site_url('crm/contacts/' . $cnt['id'] . '/edit') ?>">Edit</a>
              <?php if (empty($cnt['is_primary'])): ?>
                <form method="post" action="<?= site_url('crm/contacts/' . $cnt['id'] . '/primary') ?>" style="display:inline">
                  <?= csrf_field() ?>
                  <button class="btn sm alt" type="submit" title="Make Primary Contact">Set Primary</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'projects'): ?>
<div class="card">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Architectural &amp; Interior Projects (<?= count($projects) ?>)</h2>
    <a class="btn sm teal" href="<?= site_url('crm/projects/new?organisation_id=' . $org['id']) ?>">+ New Project</a>
  </div>
  <div class="bd flush">
    <?php if (! $projects): ?>
      <div class="empty">No projects linked to this organisation.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Project</th><th>Type</th><th>Site Address</th><th class="num">Budget</th><th>Status</th><th>Start Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
          <tr>
            <td><a href="<?= site_url('crm/projects/' . $p['id']) ?>"><strong><?= esc($p['name']) ?></strong></a><div class="muted small"><?= esc($p['project_no'] ?? '#' . $p['id']) ?></div></td>
            <td><?= esc(crm_label($p['project_type'] ?? 'Residential')) ?></td>
            <td><?= esc($p['site_address'] ?? '—') ?></td>
            <td class="num"><strong><?= crm_money($p['budget'] ?? 0) ?></strong></td>
            <td><?= crm_badge($p['status'] ?? 'active') ?></td>
            <td><?= esc(crm_date($p['start_date'] ?? null)) ?></td>
            <td><a class="btn sm alt" href="<?= site_url('crm/projects/' . $p['id']) ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'enquiries'): ?>
<div class="card">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Enquiries &amp; Pipeline Opportunities (<?= count($enquiries) ?>)</h2>
    <a class="btn sm teal" href="<?= site_url('crm/enquiries/new?organisation_id=' . $org['id']) ?>">+ New Enquiry</a>
  </div>
  <div class="bd flush">
    <?php if (! $enquiries): ?>
      <div class="empty">No enquiries logged for this organisation.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Enquiry</th><th>Contact Person</th><th>Stage</th><th>Assigned Rep</th><th class="num">Est. Value</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($enquiries as $enq): ?>
          <tr>
            <td><a href="<?= site_url('crm/enquiries/' . $enq['id']) ?>"><strong><?= esc($enq['enquiry_no'] ?? '#' . $enq['id']) ?></strong></a><div class="muted small"><?= esc($enq['title'] ?? 'Interior requirement') ?></div></td>
            <td><?= esc($enq['contact_name'] ?? '—') ?></td>
            <td><span class="badge badge-blue"><?= esc(crm_label($enq['stage'] ?? 'contacted')) ?></span></td>
            <td><?= esc($enq['assigned_rep_name'] ?? '—') ?></td>
            <td class="num"><?= crm_money($enq['estimated_value'] ?? 0) ?></td>
            <td><?= crm_badge($enq['status'] ?? 'new') ?></td>
            <td><?= esc(crm_date($enq['created_at'])) ?></td>
            <td><a class="btn sm alt" href="<?= site_url('crm/enquiries/' . $enq['id']) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'activities'): ?>
<div class="card">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Sales Activities &amp; Interactions (<?= count($activities) ?>)</h2>
    <a class="btn sm teal" href="<?= site_url('crm/activities/new?organisation_id=' . $org['id']) ?>">+ Record Activity</a>
  </div>
  <div class="bd flush">
    <?php if (! $activities): ?>
      <div class="empty">No sales activities recorded yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Activity</th><th>Type</th><th>Contact Person</th><th>Scheduled For</th><th>Owner</th><th>Outcome / Notes</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($activities as $act): ?>
          <tr>
            <td>
              <a href="<?= site_url('crm/activities/' . $act['id']) ?>"><strong><?= esc($act['subject']) ?></strong></a>
              <div class="muted small"><?= esc($act['activity_no']) ?></div>
            </td>
            <td><?= esc(crm_label($act['activity_type'])) ?></td>
            <td><?= esc($act['contact_name'] ?? '—') ?></td>
            <td><?= esc(crm_dt($act['scheduled_at'])) ?></td>
            <td><?= esc($act['assigned_user_name'] ?? '—') ?></td>
            <td><?= esc(mb_strimwidth($act['outcome'] ?: ($act['description'] ?: '—'), 0, 45, '...')) ?></td>
            <td><?= crm_badge($act['status']) ?></td>
            <td><a class="btn sm alt" href="<?= site_url('crm/activities/' . $act['id']) ?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'quotations'): ?>
<div class="card">
  <div class="hd" style="display:flex;justify-content:space-between;align-items:center">
    <h2>Quotations &amp; Proposals (<?= count($quotations) ?>)</h2>
    <a class="btn sm teal" href="<?= site_url('crm/quotations/new?organisation_id=' . $org['id']) ?>">+ Create Quotation</a>
  </div>
  <div class="bd flush">
    <?php if (! $quotations): ?>
      <div class="empty">No commercial proposals created for this organisation yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Quotation #</th><th>Contact</th><th class="num">Amount</th><th class="num">Discount</th><th>Approval Status</th><th>Customer Status</th><th>Created</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($quotations as $q): ?>
          <tr>
            <td>
              <a href="<?= site_url('crm/quotations/' . $q['id']) ?>"><strong><?= esc($q['quotation_no']) ?></strong></a>
              <div class="muted small">v<?= (int) ($q['current_version_no'] ?? 1) ?></div>
            </td>
            <td><?= esc($q['contact_name'] ?? '—') ?></td>
            <td class="num"><strong><?= crm_money($q['grand_total']) ?></strong></td>
            <td class="num"><?= crm_money($q['discount_total']) ?></td>
            <td>
              <?php if (! empty($q['is_commercially_approved'])): ?>
                <span class="badge badge-green">✓ Commercially Approved</span>
              <?php else: ?>
                <span class="badge badge-amber"><?= esc(crm_label($q['approval_status'] ?? 'pending_approval')) ?></span>
              <?php endif; ?>
            </td>
            <td><?= crm_badge($q['status']) ?></td>
            <td><?= esc(crm_date($q['created_at'])) ?></td>
            <td class="nowrap">
              <a class="btn sm alt" href="<?= site_url('crm/quotations/' . $q['id']) ?>">Open</a>
              <a class="btn sm alt" target="_blank" rel="noopener" href="<?= site_url('crm/quotations/' . $q['id'] . '/pdf/' . (int) ($q['current_version_no'] ?? 1)) ?>">📄 PDF</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'approvals'): ?>
<div class="card">
  <div class="hd"><h2>Commercial Quotation Approval Requests (<?= count($approvals) ?>)</h2></div>
  <div class="bd flush">
    <?php if (! $approvals): ?>
      <div class="empty">No approval requests required or recorded for this organisation's proposals.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Approval #</th><th>Quotation</th><th>Requested By</th><th class="num">Original</th><th class="num">Requested</th><th class="num">Discount %</th><th>Approval Status</th><th>Decision Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($approvals as $apr): ?>
          <tr>
            <td><a href="<?= site_url('crm/approvals/' . $apr['id']) ?>"><strong><?= esc($apr['approval_no']) ?></strong></a></td>
            <td><a href="<?= site_url('crm/quotations/' . $apr['quotation_id']) ?>"><?= esc($apr['quotation_no']) ?> (v<?= (int) $apr['version_no'] ?>)</a></td>
            <td><?= esc($apr['requester_name']) ?></td>
            <td class="num"><?= crm_money($apr['original_amount']) ?></td>
            <td class="num"><strong><?= crm_money($apr['requested_amount']) ?></strong></td>
            <td class="num"><strong style="color:#B91C1C"><?= number_format((float) $apr['discount_pct'], 1) ?>%</strong></td>
            <td><?= crm_badge($apr['status']) ?></td>
            <td><?= esc(crm_dt($apr['decision_at'])) ?></td>
            <td><a class="btn sm alt" href="<?= site_url('crm/approvals/' . $apr['id']) ?>">Review</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'orders'): ?>
<div class="card">
  <div class="hd"><h2>Confirmed Sales Orders (<?= count($orders) ?>)</h2></div>
  <div class="bd flush">
    <?php if (! $orders): ?>
      <div class="empty">No confirmed sales orders for this organisation yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Order #</th><th>Contact</th><th class="num">Order Total</th><th>Status</th><th>Confirmed On</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><a href="<?= site_url('crm/orders/' . $o['id']) ?>"><strong><?= esc($o['order_no']) ?></strong></a></td>
            <td><?= esc($o['contact_name'] ?? '—') ?></td>
            <td class="num"><strong><?= crm_money($o['grand_total']) ?></strong></td>
            <td><?= crm_badge($o['status']) ?></td>
            <td><?= esc(crm_date($o['order_date'] ?? $o['created_at'])) ?></td>
            <td><a class="btn sm alt" href="<?= site_url('crm/orders/' . $o['id']) ?>">View Order</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'payments'): ?>
<div class="card">
  <div class="hd"><h2>Receipts &amp; Collections Received (<?= count($payments) ?>)</h2></div>
  <div class="bd flush">
    <?php if (! $payments): ?>
      <div class="empty">No payments received yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Receipt #</th><th>Order #</th><th class="num">Amount Paid</th><th>Payment Mode</th><th>Reference #</th><th>Payment Date</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $pay): ?>
          <tr>
            <td><strong><?= esc($pay['payment_no'] ?? '#' . $pay['id']) ?></strong></td>
            <td><a href="<?= site_url('crm/orders/' . $pay['order_id']) ?>">Order #<?= (int) $pay['order_id'] ?></a></td>
            <td class="num"><strong style="color:var(--th-primary)"><?= crm_money($pay['amount']) ?></strong></td>
            <td><?= esc(crm_label($pay['payment_method'] ?? 'UPI / Bank Transfer')) ?></td>
            <td><?= esc($pay['transaction_reference'] ?: '—') ?></td>
            <td><?= esc(crm_date($pay['payment_date'] ?? $pay['created_at'])) ?></td>
            <td><a class="btn sm alt" target="_blank" href="<?= site_url('crm/payments/' . $pay['id'] . '/receipt') ?>">📄 Receipt</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'communications'): ?>
<div class="card">
  <div class="hd"><h2>Communication &amp; Interaction History (<?= count($communications) ?>)</h2></div>
  <div class="bd flush">
    <?php if (! $communications): ?>
      <div class="empty">No communication logs recorded yet.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Channel</th><th>Direction</th><th>Subject / Description</th><th>Recipient / Contact</th><th>Initiated By</th><th>Date &amp; Time</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($communications as $com): ?>
          <tr>
            <td><?= esc(crm_label($com['channel'])) ?></td>
            <td><?= esc(crm_label($com['direction'])) ?></td>
            <td><strong><?= esc($com['subject']) ?></strong><div class="muted small"><?= esc(mb_strimwidth($com['body'] ?? '', 0, 60, '...')) ?></div></td>
            <td><?= esc($com['recipient_address'] ?? '—') ?></td>
            <td><?= esc($com['sender_name'] ?? 'System') ?></td>
            <td><?= esc(crm_dt($com['created_at'])) ?></td>
            <td><?= crm_badge($com['delivery_status'] ?? 'sent') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'audit'): ?>
<div class="card">
  <div class="hd"><h2>Account Audit Trail &amp; Governance Log</h2></div>
  <div class="bd flush">
    <?php $logsToRender = $audit_logs ?? ($audits ?? []); ?>
    <?php if (! $logsToRender): ?>
      <div class="empty">No audit events recorded for this organisation.</div>
    <?php else: ?>
      <div class="tablewrap"><table class="t">
        <thead><tr><th>Action</th><th>Performed By</th><th>Details / Changes</th><th>IP Address</th><th>Timestamp</th></tr></thead>
        <tbody>
        <?php foreach ($logsToRender as $log): ?>
          <tr>
            <td><strong><?= esc(crm_label($log['action'])) ?></strong></td>
            <td><?= esc($log['user_name'] ?? 'System') ?></td>
            <td><code><?= esc($log['details'] ?? '—') ?></code></td>
            <td><?= esc($log['ip_address'] ?? '127.0.0.1') ?></td>
            <td><?= esc(crm_dt($log['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
