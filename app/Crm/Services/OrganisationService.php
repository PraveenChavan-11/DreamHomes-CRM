<?php

namespace Crm\Services;

use CodeIgniter\Database\BaseConnection;

final class OrganisationService
{
    private static function db(): BaseConnection
    {
        return Crm::db();
    }

    public static function list(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $db = self::db();
        $b = $db->table('crm_organisations o')
            ->select("o.*, o.org_type AS type, o.account_status AS status, o.pincode AS pin_code,
                CONCAT(u.first_name, ' ', u.last_name) AS account_owner_name,
                CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
                t.name AS territory_name, s.name AS lead_source_name, s.name AS source_name,
                (SELECT COUNT(*) FROM crm_contacts c WHERE c.organisation_id = o.id) AS contacts_count,
                (SELECT COUNT(*) FROM crm_contacts c WHERE c.organisation_id = o.id) AS contact_count,
                (SELECT COUNT(*) FROM crm_enquiries e WHERE e.organisation_id = o.id) AS enquiries_count,
                (SELECT COUNT(*) FROM crm_projects p WHERE p.organisation_id = o.id) AS project_count,
                (SELECT COUNT(*) FROM crm_sales_orders so WHERE so.organisation_id = o.id) AS orders_count,
                (SELECT COUNT(*) FROM crm_sales_orders so WHERE so.organisation_id = o.id) AS order_count,
                (SELECT CONCAT(c.first_name, ' ', c.last_name) FROM crm_contacts c WHERE c.organisation_id = o.id AND c.is_primary = 1 LIMIT 1) AS primary_contact_name")
            ->join('admin_users u', 'u.id = o.account_owner_id', 'left')
            ->join('crm_territories t', 't.id = o.sales_territory_id', 'left')
            ->join('crm_lead_sources s', 's.id = o.lead_source_id', 'left');

        if (! empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $b->groupStart()
                ->like('o.name', $q)
                ->orLike('o.org_no', $q)
                ->orLike('o.primary_phone', $q)
                ->orLike('o.primary_email', $q)
                ->orLike('o.city', $q)
                ->orLike('o.gstin', $q)
                ->groupEnd();
        }
        if (! empty($filters['org_type'])) {
            $b->where('o.org_type', $filters['org_type']);
        } elseif (! empty($filters['type'])) {
            $b->where('o.org_type', $filters['type']);
        }
        if (! empty($filters['account_status'])) {
            $b->where('o.account_status', $filters['account_status']);
        } elseif (! empty($filters['status'])) {
            $b->where('o.account_status', $filters['status']);
        }
        if (! empty($filters['account_rating'])) {
            $b->where('o.account_rating', $filters['account_rating']);
        } elseif (! empty($filters['rating'])) {
            $b->where('o.account_rating', $filters['rating']);
        }
        if (! empty($filters['city'])) {
            $b->where('o.city', $filters['city']);
        }
        if (! empty($filters['account_owner_id'])) {
            $b->where('o.account_owner_id', (int) $filters['account_owner_id']);
        } elseif (! empty($filters['owner_user_id'])) {
            $b->where('o.account_owner_id', (int) $filters['owner_user_id']);
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('o.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function get(int $id): ?array
    {
        $db = self::db();
        $org = $db->table('crm_organisations o')
            ->select("o.*, o.org_type AS type, o.account_status AS status, o.pincode AS pin_code,
                      CONCAT(u.first_name, ' ', u.last_name) AS account_owner_name,
                      CONCAT(u.first_name, ' ', u.last_name) AS owner_name,
                      t.name AS territory_name,
                      s.name AS lead_source_name,
                      s.name AS source_name")
            ->join('admin_users u', 'u.id = o.account_owner_id', 'left')
            ->join('crm_territories t', 't.id = o.sales_territory_id', 'left')
            ->join('crm_lead_sources s', 's.id = o.lead_source_id', 'left')
            ->where('o.id', $id)
            ->get()->getRowArray();

        return $org ?: null;
    }

    public static function find(int $id): ?array
    {
        return self::get($id);
    }

    public static function get360View(int $id): array
    {
        return self::get360($id);
    }

    public static function create(array $data, int $actorId): array
    {
        $db = self::db();
        $name = trim((string) ($data['name'] ?? ($data['org_name'] ?? '')));
        if ($name === '') {
            throw new CrmException('Organisation name is required.');
        }

        // Duplicate check
        $exists = $db->table('crm_organisations')->where('name', $name)->countAllResults();
        if ($exists > 0) {
            throw new DuplicateException("An organisation named '{$name}' already exists.");
        }

        $orgNo = NumberSequenceService::next('organisation');
        $now = Crm::now();

        $record = [
            'org_no' => $orgNo,
            'name' => $name,
            'org_type' => $data['org_type'] ?? 'corporate',
            'industry' => $data['industry'] ?? null,
            'business_category' => $data['business_category'] ?? null,
            'website' => $data['website'] ?? null,
            'gstin' => ! empty($data['gstin']) ? strtoupper(trim($data['gstin'])) : null,
            'pan' => ! empty($data['pan']) ? strtoupper(trim($data['pan'])) : null,
            'primary_email' => $data['primary_email'] ?? null,
            'primary_phone' => $data['primary_phone'] ?? null,
            'alternate_phone' => $data['alternate_phone'] ?? null,
            'registered_address' => $data['registered_address'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'default_site_address' => $data['default_site_address'] ?? null,
            'city' => $data['city'] ?? 'Pune',
            'state' => $data['state'] ?? 'Maharashtra',
            'pincode' => $data['pincode'] ?? null,
            'country' => $data['country'] ?? 'India',
            'account_owner_id' => ! empty($data['account_owner_id']) ? (int) $data['account_owner_id'] : $actorId,
            'sales_territory_id' => ! empty($data['sales_territory_id']) ? (int) $data['sales_territory_id'] : null,
            'lead_source_id' => ! empty($data['lead_source_id']) ? (int) $data['lead_source_id'] : null,
            'account_status' => $data['account_status'] ?? 'active',
            'account_rating' => $data['account_rating'] ?? 'warm',
            'notes' => $data['notes'] ?? null,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $db->table('crm_organisations')->insert($record);
        $orgId = (int) $db->insertID();

        // Optionally create primary contact if details provided
        if (! empty($data['primary_contact_name']) || ! empty($data['primary_contact_mobile'])) {
            $parts = explode(' ', trim((string) ($data['primary_contact_name'] ?? 'Primary Contact')), 2);
            ContactService::create([
                'organisation_id' => $orgId,
                'first_name' => $parts[0] ?: 'Contact',
                'last_name' => $parts[1] ?? '',
                'designation' => $data['primary_contact_designation'] ?? 'Point of Contact',
                'mobile' => $data['primary_contact_mobile'] ?? ($data['primary_phone'] ?? '9800000000'),
                'email' => $data['primary_contact_email'] ?? ($data['primary_email'] ?? null),
                'contact_role' => $data['primary_contact_role'] ?? 'owner',
                'is_primary' => 1,
                'is_decision_maker' => 1,
            ], $actorId);
        }

        AuditService::record('organisation.created', 'crm_organisations', $orgId, [
            'org_no' => $orgNo,
            'name' => $name,
            'org_type' => $record['org_type'],
        ]);

        return self::get($orgId);
    }

    public static function update(int $id, array $data, int $actorId): array
    {
        $db = self::db();
        $org = self::get($id) ?? throw new CrmException('Organisation not found.');

        $name = trim((string) ($data['name'] ?? $org['name']));
        if ($name === '') {
            throw new CrmException('Organisation name is required.');
        }

        // Duplicate check excluding current
        $exists = $db->table('crm_organisations')->where('name', $name)->where('id !=', $id)->countAllResults();
        if ($exists > 0) {
            throw new DuplicateException("Another organisation named '{$name}' already exists.");
        }

        $fields = [
            'name' => $name,
            'org_type' => $data['org_type'] ?? $org['org_type'],
            'industry' => $data['industry'] ?? $org['industry'],
            'business_category' => $data['business_category'] ?? $org['business_category'],
            'website' => $data['website'] ?? $org['website'],
            'gstin' => ! empty($data['gstin']) ? strtoupper(trim($data['gstin'])) : null,
            'pan' => ! empty($data['pan']) ? strtoupper(trim($data['pan'])) : null,
            'primary_email' => $data['primary_email'] ?? $org['primary_email'],
            'primary_phone' => $data['primary_phone'] ?? $org['primary_phone'],
            'alternate_phone' => $data['alternate_phone'] ?? $org['alternate_phone'],
            'registered_address' => $data['registered_address'] ?? $org['registered_address'],
            'billing_address' => $data['billing_address'] ?? $org['billing_address'],
            'default_site_address' => $data['default_site_address'] ?? $org['default_site_address'],
            'city' => $data['city'] ?? $org['city'],
            'state' => $data['state'] ?? $org['state'],
            'pincode' => $data['pincode'] ?? $org['pincode'],
            'country' => $data['country'] ?? $org['country'],
            'account_owner_id' => ! empty($data['account_owner_id']) ? (int) $data['account_owner_id'] : $org['account_owner_id'],
            'sales_territory_id' => ! empty($data['sales_territory_id']) ? (int) $data['sales_territory_id'] : $org['sales_territory_id'],
            'lead_source_id' => ! empty($data['lead_source_id']) ? (int) $data['lead_source_id'] : $org['lead_source_id'],
            'account_status' => $data['account_status'] ?? $org['account_status'],
            'account_rating' => $data['account_rating'] ?? $org['account_rating'],
            'notes' => $data['notes'] ?? $org['notes'],
            'updated_at' => Crm::now(),
        ];

        $db->table('crm_organisations')->where('id', $id)->update($fields);

        AuditService::record('organisation.updated', 'crm_organisations', $id, [
            'changes' => array_keys($data),
        ]);

        return self::get($id);
    }

    public static function status(int $id, string $status, int $actorId): bool
    {
        $db = self::db();
        $valid = ['active', 'inactive', 'archived'];
        if (! in_array($status, $valid, true)) {
            throw new CrmException("Invalid account status '{$status}'.");
        }
        $db->table('crm_organisations')->where('id', $id)->update([
            'account_status' => $status,
            'updated_at' => Crm::now(),
        ]);
        AuditService::record('organisation.status_changed', 'crm_organisations', $id, ['new_status' => $status]);
        return true;
    }

    /**
     * 360-Degree Relationship View: aggregates all associated contacts, projects,
     * leads/enquiries, sales activities, site visits, quotations, approvals,
     * sales orders, payments, deliveries, installations, services, and communications.
     */
    public static function get360(int $id): array
    {
        $db = self::db();
        $org = self::get($id) ?? throw new CrmException('Organisation not found.');

        // Contacts
        $contacts = $db->table('crm_contacts')
            ->select('*, whatsapp, whatsapp AS whatsapp_no, mobile, mobile AS mobile_number')
            ->where('organisation_id', $id)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Projects
        $projects = $db->table('crm_projects')->where('customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')->orWhere('id IN (SELECT project_id FROM crm_sales_activities WHERE organisation_id = ' . $id . ' AND project_id IS NOT NULL)')->get()->getResultArray();

        // Leads & Enquiries
        $enquiries = $db->table('crm_enquiries e')
            ->select("e.*, CONCAT(u.first_name, ' ', u.last_name) AS owner_name, p.name AS project_name")
            ->join('admin_users u', 'u.id = e.current_owner_id', 'left')
            ->join('crm_projects p', 'p.id = e.project_id', 'left')
            ->where('e.organisation_id', $id)
            ->orWhere('e.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('e.id', 'DESC')
            ->get()->getResultArray();

        // Sales Activities
        $activities = $db->table('crm_sales_activities sa')
            ->select("sa.*,
                COALESCE(sa.discussion_summary, sa.product_interest, sa.activity_no) AS subject,
                sa.discussion_summary AS description,
                sa.activity_date AS scheduled_at,
                CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) AS contact_name,
                CONCAT(u.first_name, ' ', u.last_name) AS assigned_user_name,
                c.first_name, c.last_name, c.mobile AS contact_mobile")
            ->join('admin_users u', 'u.id = sa.assigned_user_id', 'left')
            ->join('crm_contacts c', 'c.id = sa.contact_id', 'left')
            ->where('sa.organisation_id', $id)
            ->orderBy('sa.activity_date', 'DESC')
            ->orderBy('sa.id', 'DESC')
            ->get()->getResultArray();

        // Site Visits
        $visits = $db->table('crm_site_visits sv')
            ->select("sv.*, CONCAT(u.first_name, ' ', u.last_name) AS assigned_user_name, e.enquiry_no")
            ->join('admin_users u', 'u.id = sv.assigned_to', 'left')
            ->join('crm_enquiries e', 'e.id = sv.enquiry_id', 'left')
            ->where('e.organisation_id', $id)
            ->orWhere('e.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('sv.id', 'DESC')
            ->get()->getResultArray();

        // Quotations & Versions
        $quotations = $db->table('crm_quotations q')
            ->select("q.*, e.enquiry_no, qv.grand_total, qv.discount_total, qv.subtotal, qv.version_no, qv.version_no AS current_version_no, qv.is_locked,
                CONCAT(COALESCE(ct.first_name, ''), ' ', COALESCE(ct.last_name, '')) AS contact_name,
                CONCAT(u.first_name, ' ', u.last_name) AS author_name")
            ->join('crm_enquiries e', 'e.id = q.enquiry_id', 'left')
            ->join('crm_quotation_versions qv', 'qv.id = q.current_version_id', 'left')
            ->join('crm_contacts ct', 'ct.id = q.contact_id', 'left')
            ->join('admin_users u', 'u.id = q.created_by', 'left')
            ->where('q.organisation_id', $id)
            ->orWhere('e.organisation_id', $id)
            ->orWhere('e.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('q.id', 'DESC')
            ->get()->getResultArray();

        // Approvals
        $approvals = $db->table('crm_quotation_approvals qa')
            ->select("qa.*, q.quotation_no, qv.version_no,
                CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name,
                CONCAT(u.first_name, ' ', u.last_name) AS requester_name,
                qa.standard_selling_price AS original_amount,
                qa.proposed_selling_price AS requested_amount,
                qa.requested_discount_pct AS discount_pct,
                qa.action_at AS decision_at,
                CONCAT(app.first_name, ' ', app.last_name) AS approver_name")
            ->join('crm_quotations q', 'q.id = qa.quotation_id', 'left')
            ->join('crm_quotation_versions qv', 'qv.id = qa.quotation_version_id', 'left')
            ->join('admin_users u', 'u.id = qa.salesperson_id', 'left')
            ->join('admin_users app', 'app.id = qa.action_by_user_id', 'left')
            ->where('qa.organisation_id', $id)
            ->orWhere('qa.quotation_id IN (SELECT id FROM crm_quotations WHERE organisation_id = ' . $id . ')')
            ->orderBy('qa.id', 'DESC')
            ->get()->getResultArray();

        // Orders
        $orders = $db->table('crm_sales_orders so')
            ->select("so.*, q.quotation_no,
                CONCAT(COALESCE(ct.first_name, ''), ' ', COALESCE(ct.last_name, '')) AS contact_name,
                CONCAT(u.first_name, ' ', u.last_name) AS salesperson_name")
            ->join('crm_quotations q', 'q.id = so.quotation_id', 'left')
            ->join('crm_contacts ct', 'ct.id = so.contact_id', 'left')
            ->join('admin_users u', 'u.id = so.salesperson_id', 'left')
            ->where('so.organisation_id', $id)
            ->orWhere('so.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('so.id', 'DESC')
            ->get()->getResultArray();

        // Payments
        $payments = $db->table('crm_payments p')
            ->select('p.*, so.order_no')
            ->join('crm_sales_orders so', 'so.id = p.order_id', 'left')
            ->where('p.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orWhere('so.organisation_id', $id)
            ->orderBy('p.id', 'DESC')
            ->get()->getResultArray();

        // Deliveries & Installations
        $deliveries = $db->table('crm_deliveries d')
            ->select('d.*, so.order_no')
            ->join('crm_sales_orders so', 'so.id = d.order_id', 'left')
            ->where('so.organisation_id', $id)
            ->orWhere('so.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('d.id', 'DESC')
            ->get()->getResultArray();

        $installations = $db->table('crm_installations ins')
            ->select('ins.*, so.order_no')
            ->join('crm_sales_orders so', 'so.id = ins.order_id', 'left')
            ->where('so.organisation_id', $id)
            ->orWhere('so.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orderBy('ins.id', 'DESC')
            ->get()->getResultArray();

        // After-Sales Service Requests
        $services = $db->table('crm_service_requests sr')
            ->select('sr.*, so.order_no')
            ->join('crm_sales_orders so', 'so.id = sr.order_id', 'left')
            ->where('sr.customer_id IN (SELECT id FROM crm_customers WHERE organisation_id = ' . $id . ')')
            ->orWhere('so.organisation_id', $id)
            ->orderBy('sr.id', 'DESC')
            ->get()->getResultArray();

        // Communication Logs
        $communications = CommunicationService::getHistoryForOrganisation($id);

        // Audit History
        $audits = $db->table('crm_audit_events')
            ->where('entity_type', 'crm_organisations')
            ->where('entity_id', $id)
            ->orderBy('id', 'DESC')
            ->limit(30)
            ->get()->getResultArray();

        // Primary contact
        $primaryContact = $db->table('crm_contacts')
            ->where('organisation_id', $id)
            ->where('is_primary', 1)
            ->get()->getRowArray();
        if (! $primaryContact && $contacts) {
            $primaryContact = $contacts[0];
        }

        // Stats calculation
        $totalRev = (float) $db->table('crm_sales_orders')
            ->selectSum('grand_total')
            ->where('organisation_id', $id)
            ->get()->getRow()->grand_total;

        $totalColl = (float) $db->table('crm_payments p')
            ->selectSum('p.amount')
            ->join('crm_sales_orders so', 'so.id = p.order_id', 'left')
            ->where('so.organisation_id', $id)
            ->get()->getRow()->amount;

        $stats = [
            'total_revenue' => $totalRev,
            'orders_count' => count($orders),
            'total_collected' => $totalColl,
            'enquiries_count' => count($enquiries),
            'quotations_count' => count($quotations),
            'activities_count' => count($activities),
        ];

        // Format activities with aliases if needed
        foreach ($activities as &$act) {
            $act['subject'] = $act['discussion_summary'] ?? ($act['subject'] ?? 'Sales Activity');
            $act['scheduled_at'] = ! empty($act['scheduled_at']) ? $act['scheduled_at'] : ($act['activity_date'] . ' ' . ($act['start_time'] ?? '10:00:00'));
            $act['description'] = $act['discussion_summary'] ?? ($act['description'] ?? '');
            $act['contact_name'] = trim(($act['first_name'] ?? '') . ' ' . ($act['last_name'] ?? '')) ?: '—';
        }
        unset($act);

        // Format quotations with aliases
        foreach ($quotations as &$q) {
            $q['current_version_no'] = $q['version_no'] ?? 1;
            $q['contact_name'] = $q['contact_name'] ?? ($primaryContact ? ($primaryContact['first_name'] . ' ' . $primaryContact['last_name']) : '—');
        }
        unset($q);

        // Format approvals with aliases
        foreach ($approvals as &$apr) {
            $apr['original_amount'] = $apr['standard_selling_price'] ?? 0;
            $apr['requested_amount'] = $apr['proposed_selling_price'] ?? 0;
            $apr['discount_pct'] = $apr['requested_discount_pct'] ?? 0;
            $apr['decision_at'] = $apr['action_at'] ?? $apr['created_at'];
            $apr['requester_name'] = $apr['salesperson_name'] ?? 'Sales Rep';
            $apr['version_no'] = $apr['quotation_version_id'] ?? 1;
        }
        unset($apr);

        // Format communications with aliases
        foreach ($communications as &$com) {
            $com['channel'] = $com['communication_type'] ?? 'email';
            $com['body'] = $com['message_body'] ?? '';
            $com['recipient_address'] = $com['contact_name'] ?? ($com['contact_id'] ?? 'Client');
            $com['delivery_status'] = $com['outcome'] ?? 'sent';
            $com['sender_name'] = $com['logged_by_name'] ?? 'System';
        }
        unset($com);

        // Format audits with aliases
        foreach ($audits as &$aud) {
            $aud['action'] = $aud['event_type'] ?? 'update';
            $aud['user_name'] = $aud['user_id'] ? 'User #' . $aud['user_id'] : 'System';
            $aud['details'] = $aud['metadata'] ?? ($aud['summary'] ?? '—');
            $aud['ip_address'] = $aud['ip_address'] ?? '127.0.0.1';
        }
        unset($aud);

        return [
            'organisation' => $org,
            'org' => $org,
            'primary_contact' => $primaryContact,
            'contacts' => $contacts,
            'projects' => $projects,
            'enquiries' => $enquiries,
            'activities' => $activities,
            'visits' => $visits,
            'quotations' => $quotations,
            'approvals' => $approvals,
            'orders' => $orders,
            'payments' => $payments,
            'deliveries' => $deliveries,
            'installations' => $installations,
            'services' => $services,
            'communications' => $communications,
            'audits' => $audits,
            'audit_logs' => $audits,
            'stats' => $stats,
        ];
    }

    /**
     * Safely migrates existing customers into Organisations and Contacts.
     */
    public static function syncFromCustomers(): int
    {
        $db = self::db();
        $customers = $db->table('crm_customers')->where('organisation_id IS NULL')->get()->getResultArray();
        $migrated = 0;
        foreach ($customers as $c) {
            $name = trim((string) $c['name']);
            if ($name === '') {
                continue;
            }

            // Check if organisation with this name already exists
            $existing = $db->table('crm_organisations')->where('name', $name)->get()->getRowArray();
            if ($existing) {
                $orgId = (int) $existing['id'];
            } else {
                $orgNo = NumberSequenceService::next('organisation');
                $orgType = match ($c['customer_type'] ?? 'individual') {
                    'builder' => 'builder_developer',
                    'architect' => 'architect',
                    'interior' => 'interior_designer',
                    'corporate', 'company' => 'corporate',
                    default => 'individual',
                };
                $db->table('crm_organisations')->insert([
                    'org_no' => $orgNo,
                    'name' => $name,
                    'org_type' => $orgType,
                    'primary_phone' => $c['mobile'] ?? null,
                    'primary_email' => $c['email'] ?? null,
                    'billing_address' => $c['billing_address'] ?? null,
                    'city' => $c['city'] ?? 'Pune',
                    'state' => $c['state'] ?? 'Maharashtra',
                    'pincode' => $c['pincode'] ?? null,
                    'gstin' => $c['gstin'] ?? null,
                    'pan' => $c['pan'] ?? null,
                    'account_owner_id' => $c['owner_user_id'] ?? null,
                    'lead_source_id' => $c['source_id'] ?? null,
                    'account_status' => $c['status'] ?? 'active',
                    'created_at' => $c['created_at'] ?? Crm::now(),
                    'updated_at' => Crm::now(),
                ]);
                $orgId = (int) $db->insertID();
            }

            // Create Contact under this organisation
            $contactExists = $db->table('crm_contacts')->where('organisation_id', $orgId)->where('mobile', $c['mobile'])->get()->getRowArray();
            if ($contactExists) {
                $contactId = (int) $contactExists['id'];
            } else {
                $contactNo = NumberSequenceService::next('contact');
                $parts = explode(' ', $name, 2);
                $db->table('crm_contacts')->insert([
                    'contact_no' => $contactNo,
                    'organisation_id' => $orgId,
                    'first_name' => $parts[0] ?: 'Contact',
                    'last_name' => $parts[1] ?? '',
                    'designation' => $c['designation'] ?? 'Primary Stakeholder',
                    'mobile' => $c['mobile'] ?: '9800000000',
                    'email' => $c['email'] ?? null,
                    'contact_role' => 'owner',
                    'contact_types' => 'primary,commercial',
                    'is_decision_maker' => 1,
                    'is_primary' => 1,
                    'created_at' => $c['created_at'] ?? Crm::now(),
                    'updated_at' => Crm::now(),
                ]);
                $contactId = (int) $db->insertID();
            }

            // Link customer to organisation and primary contact
            $db->table('crm_customers')->where('id', $c['id'])->update([
                'organisation_id' => $orgId,
                'primary_contact_id' => $contactId,
            ]);

            // Link related enquiries, quotations, orders
            $db->table('crm_enquiries')->where('customer_id', $c['id'])->where('organisation_id IS NULL')->update([
                'organisation_id' => $orgId,
                'contact_id' => $contactId,
            ]);
            $db->table('crm_quotations')->where('customer_id', $c['id'])->where('organisation_id IS NULL')->update([
                'organisation_id' => $orgId,
                'contact_id' => $contactId,
            ]);
            $db->table('crm_sales_orders')->where('customer_id', $c['id'])->where('organisation_id IS NULL')->update([
                'organisation_id' => $orgId,
                'contact_id' => $contactId,
            ]);

            $migrated++;
        }

        return $migrated;
    }
}
