<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EmailOutboxModel;
use App\Models\EmailTemplateModel;
use App\Services\OutboxService;
use CodeIgniter\HTTP\ResponseInterface;

class EmailApiController extends BaseController
{
    private EmailTemplateModel $templateModel;
    private EmailOutboxModel $outboxModel;

    public function __construct()
    {
        $this->templateModel = new EmailTemplateModel();
        $this->outboxModel = new EmailOutboxModel();
    }

    public function indexTemplates(): ResponseInterface
    {
        return $this->respondJson($this->templateModel->findAll());
    }

    public function showTemplate($id): ResponseInterface
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            return $this->respondError('Template not found', 404);
        }
        return $this->respondJson($template);
    }

    public function updateTemplate($id): ResponseInterface
    {
        $template = $this->templateModel->find($id);
        if (!$template) {
            return $this->respondError('Template not found', 404);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $updates = [];
        if (isset($input['subject'])) $updates['subject'] = $input['subject'];
        if (isset($input['body_html'])) $updates['body_html'] = $input['body_html'];
        if (isset($input['body_text'])) $updates['body_text'] = $input['body_text'];
        if (isset($input['is_active'])) $updates['is_active'] = (int) $input['is_active'];

        $this->templateModel->update($id, $updates);
        return $this->respondJson($this->templateModel->find($id), 'Template updated successfully');
    }

    public function outbox(): ResponseInterface
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));
        $offset = ($page - 1) * $limit;

        $builder = $this->outboxModel->builder();
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }
        if ($search = $this->request->getGet('search')) {
            $builder->groupStart()->like('recipient_email', $search)->orLike('subject', $search)->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $logs = $builder->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();

        return $this->respondJson($logs, 'Outbox logs retrieved', 200, [
            'pagination' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ]
        ]);
    }

    public function resend($id): ResponseInterface
    {
        $item = $this->outboxModel->find($id);
        if (!$item) {
            return $this->respondError('Outbox record not found', 404);
        }

        $this->outboxModel->update($id, [
            'status'     => 'PENDING',
            'attempts'   => 0,
            'last_error' => null,
        ]);

        $service = new OutboxService();
        $results = $service->processQueue(1);

        return $this->respondJson($results, 'Email outbox item queued for retry');
    }
}

