<?php

namespace Crm\Services;

/**
 * Deliveries & Installations Fulfilment Service
 * Manages dispatch scheduling, delivery challans, technician assignment, and on-site completion sign-off.
 */
final class FulfilmentService
{
    // ── DELIVERIES ──

    public static function listDeliveries(array $params = []): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_deliveries d')
            ->select('d.*, o.order_no, c.name as customer_name, c.mobile as customer_mobile')
            ->join('crm_sales_orders o', 'o.id = d.order_id', 'left')
            ->join('crm_customers c', 'c.id = d.customer_id', 'left');

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('d.delivery_no', $q)
                ->orLike('d.challan_no', $q)
                ->orLike('d.tracking_no', $q)
                ->orLike('d.driver_name', $q)
                ->orLike('o.order_no', $q)
                ->orLike('c.name', $q)
                ->groupEnd();
        }

        if (! empty($params['status'])) {
            $builder->where('d.status', $params['status']);
        }
        if (! empty($params['order_id'])) {
            $builder->where('d.order_id', (int) $params['order_id']);
        }

        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('d.id', 'DESC')->get($limit, $offset)->getResultArray();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public static function getDelivery(int $id): array
    {
        $db = Crm::db();
        return (array) $db->table('crm_deliveries d')
            ->select('d.*, o.order_no, o.grand_total as order_total, c.name as customer_name, c.company_name, c.mobile as customer_mobile, c.email as customer_email, c.billing_address, CONCAT(u.first_name, " ", u.last_name) as recorded_by_name')
            ->join('crm_sales_orders o', 'o.id = d.order_id', 'left')
            ->join('crm_customers c', 'c.id = d.customer_id', 'left')
            ->join('admin_users u', 'u.id = d.recorded_by', 'left')
            ->where('d.id', $id)
            ->get()->getRowArray() ?? throw new CrmException('Delivery record not found.');
    }

    public static function saveDelivery(array $data, ?int $id = null, ?array $actor = null): array
    {
        $db = Crm::db();
        $orderId = (int) ($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new CrmException('A valid sales order must be selected.');
        }

        $order = $db->table('crm_sales_orders')->where('id', $orderId)->get()->getRowArray() ?? throw new CrmException('Sales order not found.');

        $status = $data['status'] ?? 'pending';
        $payload = [
            'order_id' => $orderId,
            'customer_id' => (int) $order['customer_id'],
            'status' => $status,
            'scheduled_date' => ! empty($data['scheduled_date']) ? $data['scheduled_date'] : null,
            'dispatched_date' => ! empty($data['dispatched_date']) ? $data['dispatched_date'] : null,
            'delivered_date' => ! empty($data['delivered_date']) ? $data['delivered_date'] : null,
            'vehicle_details' => trim((string) ($data['vehicle_details'] ?? '')),
            'driver_name' => trim((string) ($data['driver_name'] ?? '')),
            'driver_phone' => trim((string) ($data['driver_phone'] ?? '')),
            'tracking_no' => trim((string) ($data['tracking_no'] ?? '')),
            'delivery_address' => trim((string) ($data['delivery_address'] ?? '')),
            'recipient_name' => trim((string) ($data['recipient_name'] ?? '')),
            'recipient_phone' => trim((string) ($data['recipient_phone'] ?? '')),
            'notes' => trim((string) ($data['notes'] ?? '')),
            'updated_at' => Crm::now(),
        ];

        if ($id === null) {
            $payload['delivery_no'] = NumberSequenceService::next('delivery');
            $payload['challan_no'] = NumberSequenceService::next('challan');
            $payload['recorded_by'] = $actor['id'] ?? null;
            $payload['created_at'] = Crm::now();
            $db->table('crm_deliveries')->insert($payload);
            $id = $db->insertID();
            AuditService::audit('delivery_created', 'crm_deliveries', $id, [], $payload);
        } else {
            $old = $db->table('crm_deliveries')->where('id', $id)->get()->getRowArray() ?? [];
            $db->table('crm_deliveries')->where('id', $id)->update($payload);
            AuditService::audit('delivery_updated', 'crm_deliveries', $id, $old, $payload);
        }

        return self::getDelivery($id);
    }

    // ── INSTALLATIONS ──

    public static function listInstallations(array $params = []): array
    {
        $db = Crm::db();
        $builder = $db->table('crm_installations i')
            ->select('i.*, o.order_no, c.name as customer_name, c.mobile as customer_mobile')
            ->join('crm_sales_orders o', 'o.id = i.order_id', 'left')
            ->join('crm_customers c', 'c.id = i.customer_id', 'left');

        $q = trim((string) ($params['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('i.installation_no', $q)
                ->orLike('i.technician_name', $q)
                ->orLike('i.customer_signoff_name', $q)
                ->orLike('o.order_no', $q)
                ->orLike('c.name', $q)
                ->groupEnd();
        }

        if (! empty($params['status'])) {
            $builder->where('i.status', $params['status']);
        }
        if (! empty($params['order_id'])) {
            $builder->where('i.order_id', (int) $params['order_id']);
        }

        $limit = max(1, min(100, (int) ($params['limit'] ?? 20)));
        $page = max(1, (int) ($params['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);
        $rows = $builder->orderBy('i.id', 'DESC')->get($limit, $offset)->getResultArray();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    public static function getInstallation(int $id): array
    {
        $db = Crm::db();
        return (array) $db->table('crm_installations i')
            ->select('i.*, o.order_no, o.grand_total as order_total, c.name as customer_name, c.company_name, c.mobile as customer_mobile, c.email as customer_email, CONCAT(u.first_name, " ", u.last_name) as recorded_by_name')
            ->join('crm_sales_orders o', 'o.id = i.order_id', 'left')
            ->join('crm_customers c', 'c.id = i.customer_id', 'left')
            ->join('admin_users u', 'u.id = i.recorded_by', 'left')
            ->where('i.id', $id)
            ->get()->getRowArray() ?? throw new CrmException('Installation record not found.');
    }

    public static function saveInstallation(array $data, ?int $id = null, ?array $actor = null): array
    {
        $db = Crm::db();
        $orderId = (int) ($data['order_id'] ?? 0);
        if ($orderId <= 0) {
            throw new CrmException('A valid sales order must be selected.');
        }

        $order = $db->table('crm_sales_orders')->where('id', $orderId)->get()->getRowArray() ?? throw new CrmException('Sales order not found.');

        $payload = [
            'order_id' => $orderId,
            'delivery_id' => ! empty($data['delivery_id']) ? (int) $data['delivery_id'] : null,
            'customer_id' => (int) $order['customer_id'],
            'status' => $data['status'] ?? 'pending',
            'scheduled_date' => ! empty($data['scheduled_date']) ? $data['scheduled_date'] : null,
            'started_at' => ! empty($data['started_at']) ? $data['started_at'] : null,
            'completed_at' => ! empty($data['completed_at']) ? $data['completed_at'] : null,
            'technician_name' => trim((string) ($data['technician_name'] ?? '')),
            'technician_phone' => trim((string) ($data['technician_phone'] ?? '')),
            'site_address' => trim((string) ($data['site_address'] ?? '')),
            'customer_signoff_name' => trim((string) ($data['customer_signoff_name'] ?? '')),
            'signoff_date' => ! empty($data['signoff_date']) ? $data['signoff_date'] : null,
            'remarks' => trim((string) ($data['remarks'] ?? '')),
            'updated_at' => Crm::now(),
        ];

        if ($id === null) {
            $payload['installation_no'] = NumberSequenceService::next('installation');
            $payload['recorded_by'] = $actor['id'] ?? null;
            $payload['created_at'] = Crm::now();
            $db->table('crm_installations')->insert($payload);
            $id = $db->insertID();
            AuditService::audit('installation_created', 'crm_installations', $id, [], $payload);
        } else {
            $old = $db->table('crm_installations')->where('id', $id)->get()->getRowArray() ?? [];
            $db->table('crm_installations')->where('id', $id)->update($payload);
            AuditService::audit('installation_updated', 'crm_installations', $id, $old, $payload);
        }

        return self::getInstallation($id);
    }
}
