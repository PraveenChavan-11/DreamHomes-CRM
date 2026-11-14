<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Services\IntegrationClientService;

/**
 * Admin page for signed integration clients (WorkSphere). integrations.view to look, integrations.manage to change.
 * Secrets are shown exactly once (flash on the redirect after create/rotate) and are never listed.
 */
class IntegrationsController extends AdminBaseController
{
    private function audit(string $action, string $key, ?array $data = null): void
    {
        (new AuditLogModel())->log($action, 'integration_client', $key, null, $data, (int) ($this->adminUser['id'] ?? 0));
    }

    public function index()
    {
        if ($r = $this->requireAnyPermission(['integrations.view', 'integrations.manage'])) {
            return $r;
        }
        $db = \Config\Database::connect();
        $clients = (new IntegrationClientService($db))->listClients();
        $events = $db->table('crm_integration_events')->select('event_id,key_id,event_type,correlation_id,received_at,processed_at,lead_id,result,error_code')
            ->orderBy('id', 'DESC')->limit(50)->get()->getResultArray();
        $since = date('Y-m-d H:i:s', time() - 86400);
        $health = [
            'last_received'  => $db->query("SELECT MAX(received_at) AS t FROM crm_integration_events WHERE result IN ('accepted','duplicate')")->getRow()->t ?? null,
            'failures_24h'   => $db->table('crm_integration_events')->where('result', 'failed')->where('received_at >=', $since)->countAllResults(),
            'accepted_24h'   => $db->table('crm_integration_events')->where('result', 'accepted')->where('received_at >=', $since)->countAllResults(),
            'auth_fail_24h'  => $db->table('crm_integration_events')->where('event_type', 'auth')->where('received_at >=', $since)->countAllResults(),
            'encryption_ok'  => IntegrationClientService::isConfigured(),
        ];
        return $this->renderAdminView('admin/integrations/index', [
            'title'     => 'Integrations — TalentHub Admin',
            'clients'   => $clients,
            'events'    => $events,
            'health'    => $health,
            'canManage' => $this->isSuperAdmin() || in_array('integrations.manage', $this->adminUser['permissions'] ?? [], true),
            'newSecret' => session()->getFlashdata('new_secret'),
            'endpoint'  => base_url('api/v1/integrations/worksphere/events'),
        ], 'integrations');
    }

    private function mutate(callable $fn)
    {
        if ($r = $this->requirePermission('integrations.manage')) {
            return $r;
        }
        try {
            $fn();
        } catch (\Throwable $e) {
            log_message('error', 'Integration admin action failed: ' . get_class($e));
            session()->setFlashdata('error', $e instanceof \InvalidArgumentException || $e instanceof \RuntimeException ? $e->getMessage() : 'Action failed.');
        }
        return redirect()->to(base_url('admin/integrations'));
    }

    public function create()
    {
        return $this->mutate(function () {
            $keyId = strtolower(trim((string) $this->request->getPost('key_id')));
            $svc = new IntegrationClientService();
            if ($svc->find($keyId)) {
                throw new \InvalidArgumentException('A client with this key id already exists - use Rotate instead.');
            }
            $res = $svc->createOrRotate($keyId, (string) $this->request->getPost('label'));
            $this->audit('INTEGRATION_CLIENT_CREATE', $keyId);
            session()->setFlashdata('new_secret', ['key_id' => $keyId, 'secret' => $res['secret']]);
            session()->setFlashdata('success', "Client '{$keyId}' created. Copy the secret now - it cannot be shown again.");
        });
    }

    public function rotate(int $id)
    {
        return $this->mutate(function () use ($id) {
            $svc = new IntegrationClientService();
            $row = \Config\Database::connect()->table('crm_integration_clients')->select('key_id')->where('id', $id)->get()->getRowArray();
            if (!$row) {
                throw new \InvalidArgumentException('Client not found.');
            }
            $res = $svc->createOrRotate($row['key_id']);
            $this->audit('INTEGRATION_CLIENT_ROTATE', $row['key_id']);
            session()->setFlashdata('new_secret', ['key_id' => $row['key_id'], 'secret' => $res['secret']]);
            session()->setFlashdata('success', 'Secret rotated. The previous secret stays valid for 24 hours so the sender can switch over.');
        });
    }

    public function toggle(int $id)
    {
        return $this->mutate(function () use ($id) {
            $db = \Config\Database::connect();
            $row = $db->table('crm_integration_clients')->select('key_id,is_active')->where('id', $id)->get()->getRowArray();
            if (!$row) {
                throw new \InvalidArgumentException('Client not found.');
            }
            $new = (int) $row['is_active'] === 1 ? 0 : 1;
            $db->table('crm_integration_clients')->where('id', $id)->update(['is_active' => $new]);
            $this->audit($new ? 'INTEGRATION_CLIENT_ACTIVATE' : 'INTEGRATION_CLIENT_DEACTIVATE', $row['key_id']);
            session()->setFlashdata('success', $new ? 'Client activated.' : 'Client deactivated; its requests are now rejected.');
        });
    }
}
