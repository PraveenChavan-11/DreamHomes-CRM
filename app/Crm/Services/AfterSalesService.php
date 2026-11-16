<?php

namespace Crm\Services;

/**
 * After-Sales Service & Complaints Management
 * Handles customer tickets, warranty claims, installation complaints, repairs and maintenance history.
 */
final class AfterSalesService
{
    public static function listTickets(array $params = []): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_service_requests s')
            ->select("s.*, c.name as customer_name, c.mobile as customer_mobile, o.order_no, CONCAT(u.first_name, ' ', u.last_name) as assigned_name")
            ->join('crm_customers c', 'c.id = s.customer_id', 'left')
            ->join('crm_sales_orders o', 'o.id = s.order_id', 'left')
            ->join('admin_users u', 'u.id = s.assigned_to', 'left');

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('s.ticket_no', $q)
                ->orLike('s.issue_description', $q)
                ->orLike('c.name', $q)
                ->orLike('c.mobile', $q)
                ->orLike('o.order_no', $q)
                ->groupEnd();
        }

        if (! empty($params['status'])) {
            $builder->where('s.status', $params['status']);
        }
        if (! empty($params['request_type'])) {
            $builder->where('s.request_type', $params['request_type']);
        }
        if (! empty($params['customer_id'])) {
            $builder->where('s.customer_id', (int) $params['customer_id']);
        }

        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('s.id', 'DESC')->get($limit, $offset)->getResultArray();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public static function getTicket(int $id): array
    {
        $db = Crm::db();
        return (array) $db->table('crm_service_requests s')
            ->select("s.*, c.name as customer_name, c.company_name, c.mobile as customer_mobile, c.email as customer_email, o.order_no, CONCAT(u.first_name, ' ', u.last_name) as assigned_name, CONCAT(cby.first_name, ' ', cby.last_name) as created_by_name")
            ->join('crm_customers c', 'c.id = s.customer_id', 'left')
            ->join('crm_sales_orders o', 'o.id = s.order_id', 'left')
            ->join('admin_users u', 'u.id = s.assigned_to', 'left')
            ->join('admin_users cby', 'cby.id = s.created_by', 'left')
            ->where('s.id', $id)
            ->get()->getRowArray() ?? throw new CrmException('Service ticket not found.');
    }

    public static function saveTicket(array $data, ?int $id = null, ?array $actor = null): array
    {
        $db = Crm::db();
        $customerId = (int) ($data['customer_id'] ?? 0);
        $description = trim((string) ($data['issue_description'] ?? ''));

        if ($customerId <= 0) {
            throw new CrmException('A valid customer must be selected.');
        }
        if ($description === '') {
            throw new CrmException('Issue description is required.');
        }

        $status = $data['status'] ?? 'open';
        $payload = [
            'customer_id' => $customerId,
            'order_id' => ! empty($data['order_id']) ? (int) $data['order_id'] : null,
            'product_id' => ! empty($data['product_id']) ? (int) $data['product_id'] : null,
            'request_type' => $data['request_type'] ?? 'service_request',
            'priority' => $data['priority'] ?? 'medium',
            'status' => $status,
            'issue_description' => $description,
            'assigned_to' => ! empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            'resolution_notes' => trim((string) ($data['resolution_notes'] ?? '')),
            'customer_feedback' => trim((string) ($data['customer_feedback'] ?? '')),
            'resolved_at' => ($status === 'resolved' || $status === 'closed') ? ($data['resolved_at'] ?? Crm::now()) : null,
            'closed_at' => ($status === 'closed') ? ($data['closed_at'] ?? Crm::now()) : null,
            'updated_at' => Crm::now(),
        ];

        if ($id === null) {
            $payload['ticket_no'] = NumberSequenceService::next('service');
            $payload['created_by'] = $actor['id'] ?? null;
            $payload['created_at'] = Crm::now();
            $db->table('crm_service_requests')->insert($payload);
            $id = $db->insertID();
            AuditService::audit('service_ticket_created', 'crm_service_requests', $id, [], $payload);
        } else {
            $old = $db->table('crm_service_requests')->where('id', $id)->get()->getRowArray() ?? [];
            $db->table('crm_service_requests')->where('id', $id)->update($payload);
            AuditService::audit('service_ticket_updated', 'crm_service_requests', $id, $old, $payload);
        }

        return self::getTicket($id);
    }
}
