# CRM Demo Scenarios

All people, numbers and e-mail addresses are fictional. Credentials: `DEMO_ACCESS.md`. Run these on the demo server only. Each scenario names the login to use and what to point out. After a session you can restore the dataset with *Demo Data → Reset* (admin).

## 1. Happy path: enquiry to won sale (≈10 min)
See the walkthrough in `DEMO_ACCESS.md`. Highlight: auto-assignment, assignment history, manual editing of **variant / size / unit price** in the quotation, server-side recalculation, PDF, honest e-mail status, discount approval, **versioned** revision with compare, order from the accepted version, Won, customer timeline, global history, audit.

## 2. Manual product variant, size and price changes across versions
1. `demo.exec1` → a Windows enquiry → *New quotation*. Add "Slimline® Aluminium Sliding Window – 2 Track"; set variant "Bronze anodised", width 5 ft × height 4 ft (quantity becomes 20 sqft on the server), keep the catalogue price. Save as draft and **edit** it once more (draft edits are in place).
2. Send it → that version is **locked** (PDF hash stored).
3. Customer wants a different size and a custom price: *Revise* → change size to 6 × 4 ft and type a manual unit price. The line is flagged `manual`; if the net price falls below the product minimum the server blocks it unless an approved negotiation allows it.
4. Open *Compare versions*: shows the changed size, price and totals, the reason and who made the change; open version 1's PDF — unchanged.

## 3. Discount rejected, then returned and resubmitted
`demo.exec2` raises a 12 % request → `demo.manager1` **rejects** with remarks (remarks are mandatory) → show the request still visible in *Negotiations* with the full approval history. Raise another, have the manager **return** it, then `demo.exec2` **resubmits** a lower % → approved. Point out that the original quotation is never altered.

## 4. Discount above the manager limit (escalation to admin)
Request a percentage above the manager limit (default 15 %, an assumed value) → the request shows *admin* level; the manager cannot approve; `demo.admin1` can. Try `demo.admin1` approving a request they raised themselves → refused ("cannot decide your own request").

## 5. Lost enquiry
`demo.exec4` → an enquiry in *Quotation sent* → **Mark lost** (a lost reason is mandatory) → it leaves the active pipeline and appears in the Won/Lost report and the timeline. Then `demo.manager2` **reopens** it with a reason.

## 6. Reassignment and ownership history
`demo.manager1` reassigns an enquiry from `demo.exec1` to `demo.exec3` with a reason. `demo.exec1` no longer sees it; the assignment history keeps `exec1` as previous owner; historical events still show exec1's name.

## 7. Overdue follow-up and escalation
Open *Follow-ups* as `demo.exec3`: overdue items are listed. On a server with cron, `php spark crm:reminders` marks missed items, sends each reminder **once** (re-running creates no duplicates) and escalates items older than 24 h to the manager; check *System Health → Scheduled jobs* and the bell notifications.

## 8. Pending quotation
Find a quotation in status *sent* with no customer response yet and show it in the quotation list/report filtered by status; then record the customer's response (accepted or rejected) and show the status change in the timeline.

## 9. Access isolation
`demo.exec5` tries to open an enquiry belonging to `demo.exec1` (paste the URL) → not found. `demo.management` can see everything but every action button is absent and POSTs are refused.

## 10. Excel import
*Import Enquiries* → download the template, add two rows (one duplicate of an existing open enquiry), upload, map, validate (valid / invalid / duplicate counts), import the valid rows, download the error report; try uploading the same file again → refused.

## 11. Safe demo reset
`demo.admin1` → *Demo Data*: show the environment flags and registry counts; submit with the wrong phrase → refused; submit `RESET DEMO` → snapshot saved, demo rows rebuilt, integrity verified, reset logged with user and time. (You will be signed out because demo users are re-created.)
