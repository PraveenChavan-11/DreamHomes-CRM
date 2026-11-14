<?php

namespace App\Services;

use App\Models\MailboxConnectionModel;
use App\Models\MailActivityLogModel;

class MailboxService
{
    protected HostingerMailService $apiService;
    protected MailboxConnectionModel $boxModel;
    protected MailActivityLogModel $activityLog;

    public function __construct(?HostingerMailService $apiService = null)
    {
        $this->apiService = $apiService ?? new HostingerMailService();
        $this->boxModel = new MailboxConnectionModel();
        $this->activityLog = new MailActivityLogModel();
    }

    /**
     * Get all active mailboxes accessible to admin
     */
    public function getAccessibleMailboxes(?string $userRole = null): array
    {
        $boxes = $this->boxModel->orderBy('is_default', 'DESC')->findAll();

        if ($userRole === 'admin' || empty($userRole)) {
            return $boxes;
        }

        return array_values(array_filter($boxes, function ($box) use ($userRole) {
            if ($box['assigned_roles'] === 'all') return true;
            $roles = explode(',', $box['assigned_roles']);
            return in_array($userRole, $roles);
        }));
    }

    /**
     * Synchronize mailbox list and storage quotas from Hostinger Mail API
     */
    public function syncMailboxes(): array
    {
        $res = $this->apiService->getAccountInfo();
        if (!$res['success']) {
            return [
                'success' => false,
                'message' => 'Unable to fetch mailboxes from Hostinger: ' . ($res['message'] ?? 'Unknown error'),
            ];
        }

        $mailboxes = $res['data']['mailboxes'] ?? [];
        $syncedCount = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($mailboxes as $idx => $mb) {
            $resourceId = $mb['resource_id'] ?? $mb['id'] ?? null;
            $email = $mb['email'] ?? null;
            if (!$resourceId || !$email) continue;

            $existing = $this->boxModel->getByResourceId($resourceId);
            $quotaUsed = $mb['quota']['used'] ?? ($mb['quota_used'] ?? 0);
            $quotaTotal = $mb['quota']['total'] ?? ($mb['quota_total'] ?? 10737418240);
            $status = strtoupper($mb['status'] ?? 'ACTIVE');

            if ($existing) {
                $this->boxModel->update($existing['id'], [
                    'email'             => $email,
                    'status'            => $status,
                    'quota_used_bytes'  => $quotaUsed,
                    'quota_total_bytes' => $quotaTotal,
                    'last_synced_at'    => $now,
                    'sync_status'       => 'SUCCESS',
                    'sync_error'        => null,
                ]);
            } else {
                $this->boxModel->insert([
                    'mailbox_resource_id' => $resourceId,
                    'email'               => $email,
                    'display_name'        => $mb['display_name'] ?? ('TalentHub ' . ucfirst(explode('@', $email)[0])),
                    'status'              => $status,
                    'quota_used_bytes'    => $quotaUsed,
                    'quota_total_bytes'   => $quotaTotal,
                    'is_default'          => ($syncedCount === 0 && $this->boxModel->countAllResults() === 0) ? 1 : 0,
                    'assigned_roles'      => 'all',
                    'last_synced_at'      => $now,
                    'sync_status'         => 'SUCCESS',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
            $syncedCount++;
        }

        $this->activityLog->record('SYNC', null, null, ['synced_mailboxes' => $syncedCount]);

        return [
            'success'      => true,
            'synced_count' => $syncedCount,
            'message'      => "Synchronized {$syncedCount} mailbox(es) successfully.",
        ];
    }
}

