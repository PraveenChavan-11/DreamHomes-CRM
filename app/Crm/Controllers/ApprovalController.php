<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ApprovalService;

class ApprovalController extends CrmController
{
    public function index(): string
    {
        $this->authorize('quotations.view');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $res = ApprovalService::list($g, $this->user(), $page, 20);

        return $this->render('approvals/index', [
            'rows' => $res['rows'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => $res['per_page'],
            'totalPages' => $res['total_pages'],
            'counts' => $res['counts'],
            'activeTab' => $g['tab'] ?? 'pending',
            'filters' => $g,
        ], 'Commercial Approval Centre', 'approvals');
    }

    public function show(int $id): string
    {
        $this->authorize('quotations.view');
        $app = ApprovalService::get($id, $this->user()) ?? throw new PageNotFoundException('Approval request not found.');

        return $this->render('approvals/show', [
            'app' => $app,
        ], 'Commercial Approval · ' . $app['approval_no'] . ' (' . $app['quotation_no'] . ')', 'approvals');
    }

    public function decide(int $id): RedirectResponse
    {
        $this->authorize('discounts.approve_manager', 'discounts.approve_admin', 'quotations.create');
        $action = $this->request->getPost('decision'); // approve, reject, return, clarify, escalate
        $remarks = $this->request->getPost('remarks');
        $reason = $this->request->getPost('rejection_reason');
        $actor = $this->user();

        try {
            match ($action) {
                'approve' => ApprovalService::approve($id, $remarks, $actor),
                'reject' => ApprovalService::reject($id, (string) ($reason ?: $remarks), $remarks, $actor),
                'return' => ApprovalService::returnForRevision($id, (string) $remarks, $actor),
                'clarify' => ApprovalService::requestClarification($id, (string) $remarks, $actor),
                default => throw new CrmException("Unknown approval decision '{$action}'."),
            };

            return redirect()->to(site_url("crm/approvals/{$id}"))->with('success', "Approval decision '" . strtoupper((string) $action) . "' recorded successfully.");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function clarify(int $id): RedirectResponse
    {
        $this->authorize('quotations.create');
        $message = trim((string) $this->request->getPost('message'));

        try {
            ApprovalService::respondClarification($id, $message, $this->user());
            return redirect()->to(site_url("crm/approvals/{$id}"))->with('success', 'Clarification response submitted to management.');
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }
}
