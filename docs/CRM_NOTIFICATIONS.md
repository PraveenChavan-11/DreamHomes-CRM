# CRM Notifications

Service: `App\Services\CrmNotificationService`. Emails are queued in the existing `email_outbox` (sent/retried by the outbox worker, `php spark outbox:process`); lead saving never waits for email and never fails because of it.

## Recipient

Website setting `crm_notification_email` (default `projects@talenthubconsultant.com`). Change it in **CRM > Settings** (permission `crm.settings`); the value is validated as an email address (no CR/LF) and the change is audit-logged. **Send test notification** queues a clearly labelled `[TEST]` email. `lead_assigned` goes to the assignee's own admin email instead.

SMTP credentials stay in the server environment (see `OutboxService::configureMailer`), never in the UI or Git.

## The ten templates (seeded into `email_templates`, editable under Email Templates)

| Event | Template code | Trigger |
|---|---|---|
| New website enquiry | `crm_new_website_enquiry` | `/project-enquiry` submission (non-duplicate) |
| New product enquiry | `crm_new_product_enquiry` | call `notify('product_enquiry', $leadId)` from product enquiry flow; also WorkSphere `consultation_requested` |
| WorkSphere registration initiated | `crm_ws_registration_initiated` | event `registration_initiated` |
| WorkSphere email verified | `crm_ws_email_verified` | event `email_verified` |
| WorkSphere demo activated | `crm_ws_demo_activated` | `demo_activated` / `provisioning_succeeded` |
| WorkSphere provisioning failed | `crm_ws_provisioning_failed` | `provisioning_failed` (lead priority High) |
| WorkSphere demo expiring | `crm_ws_demo_expiring` | `demo_expiry_approaching` |
| WorkSphere converted | `crm_ws_converted` | `converted_to_paid` |
| Lead assigned | `crm_lead_assigned` | staff assigns a lead to another user |
| Follow-up due / overdue | `crm_followup_due` | template and `notify('followup_due', $leadId, [], $dateKey)` are ready; **no scheduler calls it yet** |

Variables: `{{lead_name}} {{company}} {{email}} {{phone}} {{product}} {{source}} {{status}} {{priority}} {{detail}} {{lead_url}}`.

## Safety properties

- Idempotency key `crm-L<leadId>-<event>-<hash(eventKey)>`: a retried request or duplicate WorkSphere delivery never sends twice. Distinct events for the same lead send separately (the WorkSphere event id is the key).
- All variables are HTML-escaped in the HTML body; the subject strips every control character (no header injection); no passwords, tokens or secrets are ever passed to a template.
- Each queued email adds an `Email` entry (with the outbox id) to the lead timeline; the lead page lists delivery status (`PENDING/SENT/FAILED`, attempts, last error).
- The existing `admin_contact_alert` to the contact desk is unchanged, except it is skipped when the desk address equals the CRM recipient (to avoid two identical emails).
- Staging sandbox mode of the outbox worker applies as for all other mail.
