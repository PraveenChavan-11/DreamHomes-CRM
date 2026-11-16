<div class="header-action">
  <div>
    <h2 style="margin:0 0 4px">Help Centre &amp; Interactive Product Tour</h2>
    <p class="muted" style="margin:0">Complete interactive user manual, architectural module guides, frequently asked questions, and system walkthrough.</p>
  </div>
  <div style="display:flex;gap:8px">
    <button type="button" class="btn btn-primary" id="btn-guide-start-tour">
      <?= crm_icon('M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 18) ?>
      <span>Launch 20-Step Interactive Tour</span>
    </button>
  </div>
</div>

<!-- Search in Help Centre -->
<div class="card" style="margin-top:20px;padding:16px">
  <input type="search" id="help-search" placeholder="Type a keyword to filter modules, workflows, or FAQs (e.g. quotation, GST, discount approval, payment receipt)..." style="width:100%;margin:0;font-size:14px;padding:10px 14px">
</div>

<!-- 20 Core Modules Architectural Manual -->
<div style="margin-top:24px">
  <h3 style="margin:0 0 16px;color:var(--th-dark);font-size:18px">Module Operations Manual (All 20 Modules)</h3>

  <div class="help-modules-grid" style="display:flex;flex-direction:column;gap:16px">
    
    <!-- 1. Dashboard -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">1. Executive Dashboard (`/crm`)</h4>
        <span class="badge badge-green">Executive &amp; Operations</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Displays consolidated live metrics across leads, pending quotations, confirmed sales orders, collections, upcoming follow-ups, and team performance.</p>
        <p><b>Why required:</b> Ensures business leaders and sales reps have instant operational visibility without manual report generation.</p>
        <p><b>Who uses it:</b> Business Owners, Sales Managers, Sales Executives, and Accounts Executives.</p>
        <p><b>How to open:</b> Click <b>Dashboard</b> on the top of the sidebar navigation.</p>
        <p><b>Connections:</b> Aggregates transactions from Enquiries, Quotations, Sales Orders, Follow-ups, and Payments in real time.</p>
      </div>
    </div>

    <!-- 2. Leads -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">2. Leads Pipeline (`/crm/leads`)</h4>
        <span class="badge badge-blue">Sales Team</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Provides an interactive 11-stage Kanban pipeline and tabular list for qualifying prospects from New Lead to Qualified.</p>
        <p><b>How to create:</b> Click <b>Capture New Lead</b> or press <kbd>Ctrl+K</kbd> anywhere in the application.</p>
        <p><b>Connections:</b> Moving a lead to 'Site Visit' or 'Quotation Prep' automatically schedules tasks and creates commercial opportunities.</p>
      </div>
    </div>

    <!-- 3. Enquiries -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">3. Enquiries Management (`/crm/enquiries`)</h4>
        <span class="badge badge-blue">Sales Operations</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Central workspace for customer requirements, automated team round-robin assignment, activity logging, and stage transitions.</p>
        <p><b>Connections:</b> Generates Site Visits, Quotations, and Sales Orders with complete audit logs.</p>
      </div>
    </div>

    <!-- 4. Customers -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">4. Customers &amp; 360° View (`/crm/customers`)</h4>
        <span class="badge badge-green">Enterprise Directory</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Comprehensive repository for Individuals, Architects, Builders, and Corporate accounts with GSTIN, billing, and site addresses.</p>
        <p><b>Customer 360:</b> Click any customer to view all their Projects, Leads, Quotations, Orders, Payments, Documents, and Complaints in one place.</p>
      </div>
    </div>

    <!-- 5. Projects -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">5. Customer Projects (`/crm/projects`)</h4>
        <span class="badge badge-grey">Project Management</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Tracks architectural sites (villas, residential apartments, commercial offices), location landmarks, budgets, and execution milestones.</p>
      </div>
    </div>

    <!-- 6. Site Visits -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">6. Site Visits (`/crm/visits`)</h4>
        <span class="badge badge-amber">Field Operations</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Schedules field representatives for physical site inspection, technical surveys, and client consultations.</p>
      </div>
    </div>

    <!-- 7. Measurements -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">7. Measurements &amp; Openings (`/crm/measurements`)</h4>
        <span class="badge badge-amber">Technical Survey</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Records precise millimeter dimensions (Width × Height), room locations, and versioned revisions with change logs.</p>
      </div>
    </div>

    <!-- 8. Quotations -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">8. Quotations &amp; Pricing (`/crm/quotations`)</h4>
        <span class="badge badge-green">Commercial Core</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Interactive proposal builder with support for custom sizes, variant SKUs, discounts, GST calculation, and multi-tier approval chains.</p>
        <p><b>Approval rules:</b> Discounts exceeding salesperson limits automatically trigger manager approval workflows.</p>
      </div>
    </div>

    <!-- 9. Sales Orders -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">9. Sales Orders (`/crm/orders`)</h4>
        <span class="badge badge-green">Commercial Execution</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Manages confirmed client commitments across 17 statuses from Advance Pending to In Procurement, Delivered, and Handover.</p>
      </div>
    </div>

    <!-- 10. Payments -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">10. Payments &amp; Receipts (`/crm/payments`)</h4>
        <span class="badge badge-green">Finance Desk</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Records advances, milestone settlements, full balances, and issues official print-ready PDF receipts with INR in words.</p>
      </div>
    </div>

    <!-- 11. Deliveries -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">11. Deliveries &amp; Logistics (`/crm/deliveries`)</h4>
        <span class="badge badge-grey">Fulfilment</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Dispatches fabricated windows, bathroom fixtures, and modular units with delivery challans and vehicle tracking.</p>
      </div>
    </div>

    <!-- 12. Installations -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">12. Site Installations (`/crm/installations`)</h4>
        <span class="badge badge-grey">Fulfilment</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Coordinates on-site technicians, tracks installation progress, and secures client sign-off handover certificates.</p>
      </div>
    </div>

    <!-- 13. After-Sales Service -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">13. After-Sales Service &amp; Complaints (`/crm/service`)</h4>
        <span class="badge badge-amber">Customer Service</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Logs customer complaints, warranty claims, repair tickets, and field maintenance visits with resolution history.</p>
      </div>
    </div>

    <!-- 14. Reports & Analytics -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">14. Reports &amp; Analytics (`/crm/reports`)</h4>
        <span class="badge badge-blue">Business Intelligence</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Real-time analytics on conversion funnels, salesperson performance, lead-source ROI, and one-click XLSX/PDF exports.</p>
      </div>
    </div>

    <!-- 15. Masters -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">15. Master Data Management (`/crm/masters`)</h4>
        <span class="badge badge-green">Governance &amp; Master Data</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Governs all standard company, customer, product, sales, and system parameters with duplicate protection and dependency checks.</p>
      </div>
    </div>

    <!-- 16. Team & Employees -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">16. Team &amp; Employees (`/crm/users`)</h4>
        <span class="badge badge-grey">Administration</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> User creation, role assignment, active status control, team structuring, and password management.</p>
      </div>
    </div>

    <!-- 17. Notifications -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">17. Notifications Desk (`/crm/notifications`)</h4>
        <span class="badge badge-blue">Communication</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Real-time in-app alerts for assigned leads, due follow-ups, quotation decisions, and order milestones.</p>
      </div>
    </div>

    <!-- 18. Audit Logs -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">18. Audit Logs (`/crm/audit`)</h4>
        <span class="badge badge-red">Security &amp; Compliance</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Immutable append-only log capturing user actions, IP addresses, correlation IDs, and state differences with sensitive data scrubbing.</p>
      </div>
    </div>

    <!-- 19. Help & Guided Tour -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">19. Help &amp; Guided Tour (`/crm/tour/guide`)</h4>
        <span class="badge badge-green">Onboarding</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Complete searchable knowledge base and interactive 20-step walkthrough for new users and client demonstrations.</p>
      </div>
    </div>

    <!-- 20. Settings -->
    <div class="card help-card" style="padding:20px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <h4 style="margin:0;color:var(--th-primary);font-size:16px">20. Settings &amp; Configuration (`/crm/settings`)</h4>
        <span class="badge badge-grey">Configuration</span>
      </div>
      <div style="margin-top:10px;font-size:13px;line-height:1.5">
        <p><b>What it does:</b> Company branding, GSTIN, assignment algorithms, email servers, demo data controls, and system health checks.</p>
      </div>
    </div>

  </div>
</div>

<!-- Frequently Asked Questions -->
<div style="margin-top:32px">
  <h3 style="margin:0 0 16px;color:var(--th-dark);font-size:18px">Frequently Asked Questions (FAQ)</h3>

  <div style="display:flex;flex-direction:column;gap:12px">
    <div class="card" style="padding:16px">
      <h4 style="margin:0 0 6px;color:var(--th-dark)">How does quotation discount approval work?</h4>
      <p class="muted small" style="margin:0">Sales Executives can apply discounts up to the executive limit configured in CRM Settings. Any discount exceeding this limit automatically locks the quote and routes an approval notification to the Sales Manager or Business Admin.</p>
    </div>

    <div class="card" style="padding:16px">
      <h4 style="margin:0 0 6px;color:var(--th-dark)">Can an unapproved quotation be sent to a client?</h4>
      <p class="muted small" style="margin:0">No. The system strictly guards the "Send Quotation" action on the server. Only quotes in "Draft" (within limit) or "Approved" state can be issued to clients.</p>
    </div>

    <div class="card" style="padding:16px">
      <h4 style="margin:0 0 6px;color:var(--th-dark)">How are custom window dimensions priced?</h4>
      <p class="muted small" style="margin:0">For architectural windows and doors, the system computes the exact square feet or square meters from the customer's millimeter dimensions and multiplies it by the selected profile series and glass specification rate.</p>
    </div>

    <div class="card" style="padding:16px">
      <h4 style="margin:0 0 6px;color:var(--th-dark)">Can an accepted quotation be converted into multiple sales orders?</h4>
      <p class="muted small" style="margin:0">No. Quotation-to-order conversion enforces unique 1-to-1 referential integrity. Once converted, the quotation is locked and linked directly to its confirmed Sales Order.</p>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('help-search');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.toLowerCase().trim();
      document.querySelectorAll('.help-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = (q === '' || text.includes(q)) ? 'block' : 'none';
      });
    });
  }

  const startTourBtn = document.getElementById('btn-guide-start-tour');
  if (startTourBtn) {
    startTourBtn.addEventListener('click', () => {
      const tourBtn = document.getElementById('btn-start-tour');
      if (tourBtn) {
        tourBtn.click();
      }
    });
  }
});
</script>
