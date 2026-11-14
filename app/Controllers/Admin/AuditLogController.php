<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;

class AuditLogController extends AdminBaseController
{
    public function index()
    {
        if ($redirect = $this->requirePermission('audit.view')) {
            return $redirect;
        }

        $auditModel = new AuditLogModel();
        $db = \Config\Database::connect();

        $action = $this->request->getGet('action');
        $entityType = $this->request->getGet('entity_type');
        $userId = $this->request->getGet('user_id');
        $search = $this->request->getGet('search');
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $filters = [];
        if (!empty($action)) {
            $filters['action'] = $action;
        }
        if (!empty($entityType)) {
            $filters['entity_type'] = $entityType;
        }
        if (!empty($userId)) {
            $filters['user_id'] = (int) $userId;
        }
        if (!empty($search)) {
            $filters['search'] = $search;
        }

        // CSV Export Handler - Restricted to Super Administrator to safeguard sensitive audit logs
        if ($this->request->getGet('export') === 'csv') {
            $isSuperAdmin = false;
            foreach ($this->adminUser['roles'] ?? [] as $r) {
                if (($r['slug'] ?? '') === 'super_admin' || ($r['name'] ?? '') === 'Super Administrator') {
                    $isSuperAdmin = true;
                    break;
                }
            }
            if (!$isSuperAdmin) {
                session()->setFlashdata('error', 'Security restriction: Only Super Administrators may export security audit logs.');
                return redirect()->to(base_url('admin/audit-logs'));
            }

            $records = $auditModel->getLogsWithDetails($filters, 2000, 0);

            $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM
            $csvData .= "Log ID,Timestamp,Actor User,Action,Entity Type,Entity ID,IP Address,User Agent,Old Values,New Values\n";

            foreach ($records as $row) {
                $time = str_replace('"', '""', $row['created_at'] ?? '');
                $actor = str_replace('"', '""', $row['user_name'] ?? ($row['user_email'] ?? 'System/Guest'));
                $act = str_replace('"', '""', $row['action'] ?? '');
                $ent = str_replace('"', '""', $row['entity_type'] ?? '');
                $entId = str_replace('"', '""', (string) ($row['entity_id'] ?? ''));
                $ip = str_replace('"', '""', $row['ip_address'] ?? '');
                $ua = str_replace('"', '""', $row['user_agent'] ?? '');
                $old = str_replace('"', '""', $row['old_values'] ?? '');
                $new = str_replace('"', '""', $row['new_values'] ?? '');

                $csvData .= "\"{$row['id']}\",\"{$time}\",\"{$actor}\",\"{$act}\",\"{$ent}\",\"{$entId}\",\"{$ip}\",\"{$ua}\",\"{$old}\",\"{$new}\"\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_audit_logs_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        $totalCount = $auditModel->countLogsWithDetails($filters);
        $logs = $auditModel->getLogsWithDetails($filters, $perPage, $offset);
        $totalPages = ceil($totalCount / $perPage);

        // Fetch distinct actions and entity types for dropdown filter
        $availableActions = $db->table('audit_logs')->select('DISTINCT(action) as action')->orderBy('action', 'ASC')->get()->getResultArray();
        $availableEntities = $db->table('audit_logs')->select('DISTINCT(entity_type) as entity_type')->orderBy('entity_type', 'ASC')->get()->getResultArray();
        $adminUsers = $db->table('admin_users')->select("id, username, email, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as name")->orderBy('first_name', 'ASC')->get()->getResultArray();

        return $this->renderAdminView('admin/audit_logs/index', [
            'logs'              => $logs,
            'action'            => $action,
            'entityType'        => $entityType,
            'userId'            => $userId,
            'search'            => $search,
            'perPage'           => $perPage,
            'page'              => $page,
            'totalCount'        => $totalCount,
            'totalPages'        => $totalPages,
            'availableActions'  => array_column($availableActions, 'action'),
            'availableEntities' => array_column($availableEntities, 'entity_type'),
            'adminUsers'        => $adminUsers,
            'title'             => 'Security Audit Logs — TalentHub Admin',
        ], 'audit_logs');
    }
}

