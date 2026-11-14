<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotificationApiController extends BaseController
{
    private NotificationModel $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotificationModel();
    }

    public function index(): ResponseInterface
    {
        $user = session()->get('admin_user');
        $userId = $user['id'] ?? null;
        $role = $user['roles'][0]['slug'] ?? null;

        $notifs = $this->notifModel->getActiveForUser($userId, $role, 50);
        return $this->respondJson($notifs);
    }

    public function unreadCount(): ResponseInterface
    {
        $user = session()->get('admin_user');
        $userId = $user['id'] ?? null;
        $role = $user['roles'][0]['slug'] ?? null;

        $builder = $this->notifModel->where('is_read', 0);
        $builder->groupStart();
        if ($userId) $builder->where('user_id', $userId);
        if ($role) {
            $builder->orWhere('role_target', $role)->orWhere('role_target IS NULL');
        } else {
            $builder->orWhere('user_id IS NULL');
        }
        $builder->groupEnd();

        $count = $builder->countAllResults();
        return $this->respondJson(['count' => $count]);
    }

    public function markRead($id): ResponseInterface
    {
        $notif = $this->notifModel->find($id);
        if (!$notif) {
            return $this->respondError('Notification not found', 404);
        }

        $this->notifModel->update($id, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respondJson([], 'Notification marked as read');
    }

    public function markAllRead(): ResponseInterface
    {
        $user = session()->get('admin_user');
        $userId = $user['id'] ?? null;
        $role = $user['roles'][0]['slug'] ?? null;

        $builder = $this->notifModel->builder();
        $builder->where('is_read', 0);
        $builder->groupStart();
        if ($userId) $builder->where('user_id', $userId);
        if ($role) $builder->orWhere('role_target', $role)->orWhere('role_target IS NULL');
        $builder->groupEnd();

        $builder->update([
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respondJson([], 'All notifications marked as read');
    }
}

