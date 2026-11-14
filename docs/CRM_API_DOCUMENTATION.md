# CRM Route / API Reference

The CRM is a **server-rendered application**; it has no separate public REST API yet. Every endpoint below is an HTML or file-download route under `/crm`. This file lists the live route table (generated from `php spark routes`) and the conventions that make the service layer ready to expose as a JSON API later.

## Conventions

* **Authentication:** session cookie created by `POST /crm/login`. Every route except login / forgot-password / reset-password sits behind `crmauth` (session present, not idle-expired, account still active; roles and permissions are re-read from the database on every request, so revocations apply immediately). AJAX callers receive JSON `401 {"success":false,"code":"UNAUTHORIZED"}`; a missing permission returns `403` (JSON `{"code":"FORBIDDEN"}` for AJAX, an HTML page otherwise).
* **Authorization:** `crmperm:a,b` means *any one of* the listed permissions (see [CRM_ROLE_PERMISSION_MATRIX.md](CRM_ROLE_PERMISSION_MATRIX.md)). Record-level access (own / team / all) is then enforced by `AccessScope` inside the controller/service; an inaccessible record returns 404/validation error rather than leaking existence.
* **CSRF:** all `POST` routes require the CSRF token (`csrf_field()` in forms; `csrf_token()` / `csrf_hash()` exposed as `<meta name="csrf-name|csrf-token">` for scripts). CSRF is cookie-based.
* **Responses:** successful mutations redirect (`303`) with a flash message; business-rule violations redirect back with the message, per-field `errors` and the old input. Downloads (`/pdf/…`, `/xlsx`, `/export`, `/errors`) return files with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
* **Pagination:** `?page=N`, 25 rows per page on listings (filters are preserved in the pager links).
* **Rate limiting:** public auth routes carry `ratelimit:<attempts>,<seconds>`. See *Known limitations* for the database table it needs.
* **Notification counter:** `GET /crm/notifications/count` returns `{"count": <unread>}` (used by the bell icon).
* **Future API:** all business logic lives in `Crm\Services\*` static methods that take an *actor* array and throw `CrmException` (with a field → message `errors` map). A JSON controller can wrap them unchanged.

## Route table

<!-- ROUTES-TABLE -->
| Method | Path | Handler | Guards (filters) |
|---|---|---|---|
| GET | `/crm/login` | `AuthController::login` | `ratelimit:20,60` |
| GET | `/crm/forgot-password` | `AuthController::forgot` | `ratelimit:10,60` |
| GET | `/crm/reset-password` | `AuthController::reset` | `ratelimit:10,60` |
| GET | `/crm/profile` | `AuthController::profile` | `crmauth` |
| GET | `/crm` | `DashboardController::index` | `crmauth`, `crmperm:dashboard.view` |
| GET | `/crm/calendar` | `DashboardController::calendar` | `crmauth` |
| GET | `/crm/notifications` | `DashboardController::notifications` | `crmauth` |
| GET | `/crm/notifications/count` | `DashboardController::notificationCount` | `crmauth` |
| GET | `/crm/customers` | `CustomerController::index` | `crmauth`, `crmperm:customers.view` |
| GET | `/crm/customers/export` | `CustomerController::export` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.export` |
| GET | `/crm/customers/new` | `CustomerController::new` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.create` |
| GET | `/crm/customers/{id}` | `CustomerController::show` | `crmauth`, `crmperm:customers.view` |
| GET | `/crm/customers/{id}/edit` | `CustomerController::edit` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.edit` |
| GET | `/crm/enquiries` | `EnquiryController::index` | `crmauth`, `crmperm:enquiries.view` |
| GET | `/crm/enquiries/new` | `EnquiryController::new` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.create` |
| GET | `/crm/enquiries/{id}` | `EnquiryController::show` | `crmauth`, `crmperm:enquiries.view` |
| GET | `/crm/followups` | `ActivityController::followups` | `crmauth`, `crmperm:followups.manage` |
| GET | `/crm/visits` | `ActivityController::visits` | `crmauth`, `crmperm:visits.manage` |
| GET | `/crm/visits/{id}` | `ActivityController::visit` | `crmauth`, `crmperm:visits.manage` |
| GET | `/crm/documents/{id}` | `ActivityController::document` | `crmauth` |
| GET | `/crm/quotations` | `QuotationController::index` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/quotations/new` | `QuotationController::new` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| GET | `/crm/quotations/{id}` | `QuotationController::show` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/quotations/{id}/edit` | `QuotationController::edit` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| GET | `/crm/quotations/{id}/revise` | `QuotationController::reviseForm` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| GET | `/crm/quotations/{id}/compare` | `QuotationController::compare` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/quotations/{id}/pdf/{id2}` | `QuotationController::pdf` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/quotations/{id}/preview/{id2}` | `QuotationController::previewVersion` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/negotiations` | `NegotiationController::index` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/negotiations/{id}` | `NegotiationController::show` | `crmauth`, `crmperm:quotations.view` |
| GET | `/crm/orders` | `OrderController::index` | `crmauth`, `crmperm:orders.view` |
| GET | `/crm/orders/{id}` | `OrderController::show` | `crmauth`, `crmperm:orders.view` |
| GET | `/crm/history` | `HistoryController::index` | `crmauth`, `crmperm:history.view` |
| GET | `/crm/history/export` | `HistoryController::export` | `crmauth`, `crmperm:history.view` |
| GET | `/crm/history/enquiry/{id}` | `HistoryController::enquiry` | `crmauth`, `crmperm:history.view` |
| GET | `/crm/reports` | `ReportController::index` | `crmauth`, `crmperm:reports.view,quotations.view` |
| GET | `/crm/reports/{key}` | `ReportController::show` | `crmauth`, `crmperm:reports.view,quotations.view` |
| GET | `/crm/reports/{key}/xlsx` | `ReportController::xlsx` | `crmauth`, `crmperm:reports.view,quotations.view`, `crmperm:reports.export` |
| GET | `/crm/reports/{key}/pdf` | `ReportController::pdf` | `crmauth`, `crmperm:reports.view,quotations.view`, `crmperm:reports.export` |
| GET | `/crm/products` | `ProductController::index` | `crmauth`, `crmperm:products.view` |
| GET | `/crm/products/new` | `ProductController::new` | `crmauth`, `crmperm:products.view`, `crmperm:products.manage` |
| GET | `/crm/products/{id}` | `ProductController::show` | `crmauth`, `crmperm:products.view` |
| GET | `/crm/products/{id}/edit` | `ProductController::edit` | `crmauth`, `crmperm:products.view`, `crmperm:products.manage` |
| GET | `/crm/imports` | `ImportController::index` | `crmauth`, `crmperm:customers.import` |
| GET | `/crm/imports/template` | `ImportController::template` | `crmauth`, `crmperm:customers.import` |
| GET | `/crm/imports/{id}` | `ImportController::show` | `crmauth`, `crmperm:customers.import` |
| GET | `/crm/imports/{id}/errors` | `ImportController::errors` | `crmauth`, `crmperm:customers.import` |
| GET | `/crm/emails` | `EmailController::index` | `crmauth`, `crmperm:emails.view` |
| GET | `/crm/emails/{id}` | `EmailController::show` | `crmauth`, `crmperm:emails.view` |
| GET | `/crm/users` | `UserController::index` | `crmauth`, `crmperm:users.manage,teams.manage` |
| GET | `/crm/audit` | `AdminController::audit` | `crmauth`, `crmperm:audit.view` |
| GET | `/crm/settings` | `AdminController::settings` | `crmauth`, `crmperm:settings.manage` |
| GET | `/crm/demo` | `AdminController::demo` | `crmauth`, `crmperm:demo.reset` |
| GET | `/crm/health` | `AdminController::health` | `crmauth`, `crmperm:settings.manage` |
| POST | `/crm/login` | `AuthController::login` | `ratelimit:20,60` |
| POST | `/crm/forgot-password` | `AuthController::forgot` | `ratelimit:10,60` |
| POST | `/crm/reset-password` | `AuthController::reset` | `ratelimit:10,60` |
| POST | `/crm/logout` | `AuthController::logout` | `crmauth` |
| POST | `/crm/profile/password` | `AuthController::changePassword` | `crmauth` |
| POST | `/crm/notifications/read` | `DashboardController::markRead` | `crmauth` |
| POST | `/crm/notifications/{id}/read` | `DashboardController::markRead` | `crmauth` |
| POST | `/crm/customers/create` | `CustomerController::create` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.create` |
| POST | `/crm/customers/{id}/update` | `CustomerController::update` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.edit` |
| POST | `/crm/customers/{id}/status` | `CustomerController::status` | `crmauth`, `crmperm:customers.view`, `crmperm:customers.deactivate` |
| POST | `/crm/enquiries/create` | `EnquiryController::create` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.create` |
| POST | `/crm/enquiries/{id}/assign` | `EnquiryController::assign` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.assign,enquiries.reassign` |
| POST | `/crm/enquiries/{id}/status` | `EnquiryController::status` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.edit,enquiries.close,enquiries.reopen` |
| POST | `/crm/enquiries/{id}/won` | `EnquiryController::won` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.close` |
| POST | `/crm/enquiries/{id}/lost` | `EnquiryController::lost` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.close` |
| POST | `/crm/enquiries/{id}/hold` | `EnquiryController::hold` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.close` |
| POST | `/crm/enquiries/{id}/reopen` | `EnquiryController::reopen` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.reopen` |
| POST | `/crm/enquiries/{id}/contact` | `EnquiryController::contact` | `crmauth`, `crmperm:enquiries.view`, `crmperm:followups.manage` |
| POST | `/crm/enquiries/{id}/followups` | `EnquiryController::scheduleFollowup` | `crmauth`, `crmperm:enquiries.view`, `crmperm:followups.manage` |
| POST | `/crm/enquiries/{id}/visits` | `EnquiryController::scheduleVisit` | `crmauth`, `crmperm:enquiries.view`, `crmperm:visits.manage` |
| POST | `/crm/enquiries/{id}/requirements` | `EnquiryController::addRequirement` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.edit` |
| POST | `/crm/enquiries/requirements/{id}/update` | `EnquiryController::updateRequirement` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.edit` |
| POST | `/crm/enquiries/requirements/{id}/drop` | `EnquiryController::dropRequirement` | `crmauth`, `crmperm:enquiries.view`, `crmperm:enquiries.edit` |
| POST | `/crm/followups/{id}/complete` | `ActivityController::completeFollowup` | `crmauth`, `crmperm:followups.manage` |
| POST | `/crm/followups/{id}/reschedule` | `ActivityController::rescheduleFollowup` | `crmauth`, `crmperm:followups.manage` |
| POST | `/crm/visits/{id}/complete` | `ActivityController::completeVisit` | `crmauth`, `crmperm:visits.manage` |
| POST | `/crm/visits/{id}/measurements` | `ActivityController::saveMeasurements` | `crmauth`, `crmperm:visits.manage` |
| POST | `/crm/visits/{id}/documents` | `ActivityController::uploadDocument` | `crmauth`, `crmperm:visits.manage` |
| POST | `/crm/quotations/create` | `QuotationController::create` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| POST | `/crm/quotations/preview` | `QuotationController::preview` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| POST | `/crm/quotations/{id}/update` | `QuotationController::update` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| POST | `/crm/quotations/{id}/revise` | `QuotationController::revise` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| POST | `/crm/quotations/{id}/send` | `QuotationController::send` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.send` |
| POST | `/crm/quotations/{id}/resend` | `QuotationController::resend` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.send` |
| POST | `/crm/quotations/{id}/response` | `QuotationController::response` | `crmauth`, `crmperm:quotations.view`, `crmperm:quotations.create` |
| POST | `/crm/negotiations/request` | `NegotiationController::request` | `crmauth`, `crmperm:quotations.view`, `crmperm:negotiations.create` |
| POST | `/crm/negotiations/{id}/decide` | `NegotiationController::decide` | `crmauth`, `crmperm:quotations.view`, `crmperm:discounts.approve_manager,discounts.approve_admin` |
| POST | `/crm/negotiations/{id}/resubmit` | `NegotiationController::resubmit` | `crmauth`, `crmperm:quotations.view`, `crmperm:negotiations.create` |
| POST | `/crm/orders/create` | `OrderController::create` | `crmauth`, `crmperm:orders.view`, `crmperm:orders.manage,enquiries.close` |
| POST | `/crm/orders/{id}/status` | `OrderController::status` | `crmauth`, `crmperm:orders.view`, `crmperm:orders.manage` |
| POST | `/crm/products/save` | `ProductController::save` | `crmauth`, `crmperm:products.view`, `crmperm:products.manage` |
| POST | `/crm/products/{id}/save` | `ProductController::save` | `crmauth`, `crmperm:products.view`, `crmperm:products.manage` |
| POST | `/crm/imports/upload` | `ImportController::upload` | `crmauth`, `crmperm:customers.import` |
| POST | `/crm/imports/{id}/mapping` | `ImportController::mapping` | `crmauth`, `crmperm:customers.import` |
| POST | `/crm/imports/{id}/validate` | `ImportController::validateRows` | `crmauth`, `crmperm:customers.import` |
| POST | `/crm/imports/{id}/run` | `ImportController::run` | `crmauth`, `crmperm:customers.import` |
| POST | `/crm/emails/send` | `EmailController::send` | `crmauth`, `crmperm:emails.view`, `crmperm:emails.send` |
| POST | `/crm/emails/{id}/retry` | `EmailController::retry` | `crmauth`, `crmperm:emails.view`, `crmperm:emails.send` |
| POST | `/crm/users/create` | `UserController::create` | `crmauth`, `crmperm:users.manage,teams.manage`, `crmperm:users.manage` |
| POST | `/crm/users/{id}/active` | `UserController::active` | `crmauth`, `crmperm:users.manage,teams.manage`, `crmperm:users.manage` |
| POST | `/crm/users/{id}/password` | `UserController::password` | `crmauth`, `crmperm:users.manage,teams.manage`, `crmperm:users.manage` |
| POST | `/crm/users/teams/create` | `UserController::createTeam` | `crmauth`, `crmperm:users.manage,teams.manage`, `crmperm:teams.manage` |
| POST | `/crm/users/teams/{id}/members` | `UserController::addMember` | `crmauth`, `crmperm:users.manage,teams.manage`, `crmperm:teams.manage` |
| POST | `/crm/settings` | `AdminController::saveSettings` | `crmauth`, `crmperm:settings.manage` |
| POST | `/crm/demo/reset` | `AdminController::demoReset` | `crmauth`, `crmperm:demo.reset` |
