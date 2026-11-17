<?php

namespace Crm\Services;

/**
 * Customer Projects Service
 * Manages customer residential and commercial projects, site locations, budgets, and linkages.
 */
final class ProjectService
{
    public static function listProjects(array $params = [], ?array $actor = null): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_projects p')
            ->select("p.*, COALESCE(org.name, c.company_name, c.name) as organisation_name, org.org_no,
                      COALESCE(CONCAT(ct.first_name, ' ', ct.last_name), c.name) as contact_name, ct.mobile as contact_mobile,
                      c.name as customer_name, c.mobile as customer_mobile, CONCAT(u.first_name, ' ', u.last_name) as assigned_name")
            ->join('crm_customers c', 'c.id = p.customer_id', 'left')
            ->join('crm_organisations org', 'org.id = p.organisation_id', 'left')
            ->join('crm_contacts ct', 'ct.id = p.contact_id', 'left')
            ->join('admin_users u', 'u.id = p.assigned_to', 'left');

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('p.name', $q)
                ->orLike('p.project_no', $q)
                ->orLike('p.site_location', $q)
                ->orLike('org.name', $q)
                ->orLike('ct.first_name', $q)
                ->orLike('ct.last_name', $q)
                ->orLike('c.name', $q)
                ->orLike('c.mobile', $q)
                ->groupEnd();
        }

        if (! empty($params['customer_id'])) {
            $builder->where('p.customer_id', (int) $params['customer_id']);
        }
        if (! empty($params['status'])) {
            $builder->where('p.status', $params['status']);
        }

        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('p.id', 'DESC')->get($limit, $offset)->getResultArray();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public static function getProject(int $id): array
    {
        $db = Crm::db();
        $row = $db->table('crm_projects p')
            ->select("p.*, c.name as customer_name, c.company_name, c.mobile as customer_mobile, c.email as customer_email, CONCAT(u.first_name, ' ', u.last_name) as assigned_name")
            ->join('crm_customers c', 'c.id = p.customer_id', 'left')
            ->join('admin_users u', 'u.id = p.assigned_to', 'left')
            ->where('p.id', $id)
            ->get()->getRowArray() ?? throw new CrmException('Project not found.');

        // Fetch linked enquiries, site visits, quotations, orders
        $row['enquiries'] = $db->table('crm_enquiries')->where('project_id', $id)->orderBy('id', 'DESC')->get()->getResultArray();
        $row['site_visits'] = $db->table('crm_site_visits v')
            ->join('crm_enquiries e', 'e.id = v.enquiry_id', 'inner')
            ->where('e.project_id', $id)
            ->select('v.*, e.enquiry_no')
            ->orderBy('v.id', 'DESC')
            ->get()->getResultArray();

        return $row;
    }

    public static function saveProject(array $data, ?int $id = null, ?array $actor = null): array
    {
        $db = Crm::db();
        $name = trim((string) ($data['name'] ?? ''));
        $customerId = (int) ($data['customer_id'] ?? 0);

        if ($id !== null) {
            $existing = self::getProject($id);
            if ($customerId <= 0) {
                $customerId = (int) $existing['customer_id'];
            }
            if ($name === '') {
                $name = $existing['name'];
            }
        } else {
            if ($name === '') {
                throw new CrmException('Project name is required.');
            }
            if ($customerId <= 0) {
                throw new CrmException('A valid customer is required for the project.');
            }
        }

        $customer = $db->table('crm_customers')->where('id', $customerId)->get()->getRowArray() ?? throw new CrmException('Customer record does not exist.');

        $payload = [
            'customer_id' => $customerId,
            'name' => $name,
            'site_location' => trim((string) ($data['site_location'] ?? '')),
            'city' => trim((string) ($data['city'] ?? 'Pune')),
            'state' => trim((string) ($data['state'] ?? 'Maharashtra')),
            'pin_code' => trim((string) ($data['pin_code'] ?? '')),
            'status' => in_array($data['status'] ?? '', ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'], true) ? $data['status'] : 'planning',
            'budget' => ! empty($data['budget']) ? (float) $data['budget'] : null,
            'start_date' => ! empty($data['start_date']) ? $data['start_date'] : null,
            'completion_date' => ! empty($data['completion_date']) ? $data['completion_date'] : null,
            'assigned_to' => ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : ($actor['id'] ?? null),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
            'updated_at' => Crm::now(),
        ];

        if ($id === null) {
            $payload['project_no'] = NumberSequenceService::next('project');
            $payload['created_at'] = Crm::now();
            $db->table('crm_projects')->insert($payload);
            $id = $db->insertID();
            AuditService::audit('project_created', 'crm_projects', $id, [], $payload);
        } else {
            $old = $db->table('crm_projects')->where('id', $id)->get()->getRowArray() ?? [];
            $db->table('crm_projects')->where('id', $id)->update($payload);
            AuditService::audit('project_updated', 'crm_projects', $id, $old, $payload);
        }

        return self::getProject($id);
    }
}
