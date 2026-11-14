# CRM User Acceptance Checklist

Use the demo accounts in `DEMO_ACCESS.md` on a **demo** server. Tick a row only after you have seen the result on screen. "Pass/Fail" and the tester's name/date are to be filled in by DHIS — nothing here has been signed off.

Tester: ____________ Date: ____________ Environment/URL: ____________

## A. Access & security
| # | Check | Pass |
|---|---|---|
| A1 | Each of the 12 demo users can sign in; wrong password is refused with a generic message | ☐ |
| A2 | A deactivated user cannot sign in (create a user, deactivate, try) | ☐ |
| A3 | After 5 wrong passwords the account is locked; Admin can reset the password | ☐ |
| A4 | Executive cannot open *Users*, *Settings*, *Audit*, *Demo* (403); management can open *Audit* but cannot change anything | ☐ |
| A5 | Executive A cannot see Executive B's enquiries (type the other enquiry's URL → not found) | ☐ |
| A6 | Manager 1 sees only the Windows team's records; Manager 2 only Bathrooms & Kitchens | ☐ |
| A7 | Idle for 30 minutes → redirected to login with a clear message | ☐ |

## B. Customers & enquiries
| # | Check | Pass |
|---|---|---|
| B1 | Create customer; duplicate mobile/email is detected | ☐ |
| B2 | Create enquiry → auto-assigned to an eligible executive; assignment history shows who/when/why | ☐ |
| B3 | Reassign with reason; previous owner stays visible in history | ☐ |
| B4 | Kanban pipeline reflects real stages; an invalid stage jump is refused | ☐ |
| B5 | Mark Lost (reason required), On hold (reason + review date), Reopen (reason) | ☐ |
| B6 | Excel import: upload → map → validate → import; error report downloads; re-uploading the same file is refused | ☐ |

## C. Follow-ups & visits
| # | Check | Pass |
|---|---|---|
| C1 | Record a call; schedule, complete and reschedule a follow-up; completed items remain in history | ☐ |
| C2 | Today / upcoming / overdue lists are correct | ☐ |
| C3 | Schedule and complete a site visit with measurements; revise measurements → version history kept | ☐ |

## D. Products, quotations, negotiation
| # | Check | Pass |
|---|---|---|
| D1 | Product list/search/filter; edit a price → *Price change history* shows old → new | ☐ |
| D2 | Minimum price above standard price is rejected | ☐ |
| D3 | Quotation: pick product, **change variant, size and unit price manually**; totals & tax recomputed by the server and match a hand calculation | ☐ |
| D4 | Net price below product minimum is blocked without approval | ☐ |
| D5 | PDF shows DHIS branding, customer, lines, tax split, total, terms, signature area | ☐ |
| D6 | Send → e-mail log row appears (status honest: queued/sent, never "delivered" unless confirmed); the sent version is locked | ☐ |
| D7 | Executive requests discount above own limit → Manager approves; executive cannot approve own request | ☐ |
| D8 | Rejected / returned / escalated paths work and are recorded | ☐ |
| D9 | Revised quotation creates a new version; version 1 still opens and downloads; *Compare* shows line and total differences | ☐ |

## E. Orders & closure
| # | Check | Pass |
|---|---|---|
| E1 | Accepted quotation → one sales order (second attempt refused); totals equal the accepted version | ☐ |
| E2 | Enquiry can be marked Won only with an accepted quotation/order | ☐ |

## F. History, reports, audit
| # | Check | Pass |
|---|---|---|
| F1 | Customer timeline lists every event with user, time, previous/new owner and status; each links to its source | ☐ |
| F2 | Global *Sales History* filters (date, salesperson, team, status…) and exports | ☐ |
| F3 | Dashboard figures equal the counts in the underlying lists | ☐ |
| F4 | Reports export to Excel and PDF and respect the chosen filters | ☐ |
| F5 | *Audit Trail* shows every action above; no passwords/tokens in details | ☐ |

## G. Administration
| # | Check | Pass |
|---|---|---|
| G1 | Create user (weak password refused), deactivate/reactivate, admin password reset, create team, add member | ☐ |
| G2 | *Settings*: invalid values are refused with field messages; a valid change is audited; SMTP password is never displayed | ☐ |
| G3 | *Email Log*: filters work; failed e-mail can be retried; log-only e-mails are labelled "not sent" | ☐ |
| G4 | *Demo Data*: reset is refused without the exact phrase; works only on the demo server | ☐ |
| G5 | *System Health* shows integrity "Clean" and a cron run after the first scheduled execution | ☐ |

## H. Responsive & quality
| # | Check | Pass |
|---|---|---|
| H1 | Every main screen is usable at tablet and phone width (menu button opens the sidebar) | ☐ |
| H2 | No browser-console errors on the dashboard, enquiry, quotation and negotiation screens | ☐ |
| H3 | No dead buttons or broken links when clicking through the sidebar | ☐ |

Sign-off: ____________ (name) ____________ (date)
