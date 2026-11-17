<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\CommunicationService;
use Crm\Services\EmailService;

class CommunicationController extends CrmController
{
    public function templates(): string
    {
        $this->authorize('emails.view', 'settings.manage');
        CommunicationService::seedAll28Templates();
        $db = Crm::db();
        $templates = $db->table('crm_email_templates')->orderBy('id', 'ASC')->get()->getResultArray();

        return $this->render('communication/templates', [
            'templates' => $templates,
        ], 'Communication Centre · Email Templates', 'communication');
    }

    public function editTemplate(int $id): string
    {
        $this->authorize('settings.manage');
        $db = Crm::db();
        $template = $db->table('crm_email_templates')->where('id', $id)->get()->getRowArray() ?? throw new PageNotFoundException('Template not found.');

        return $this->render('communication/template_form', [
            'tpl' => $template,
        ], 'Edit Email Template · ' . $template['name'], 'communication');
    }

    public function updateTemplate(int $id): RedirectResponse
    {
        $this->authorize('settings.manage');
        $db = Crm::db();
        $post = $this->request->getPost();

        $db->table('crm_email_templates')->where('id', $id)->update([
            'name' => trim((string) ($post['name'] ?? '')),
            'subject' => trim((string) ($post['subject'] ?? '')),
            'body_html' => $post['body_html'] ?? '',
            'variables' => $post['variables'] ?? '',
            'is_active' => ! empty($post['is_active']) ? 1 : 0,
            'updated_at' => Crm::now(),
        ]);

        return redirect()->to(site_url('crm/communication/templates'))->with('success', 'Email template updated successfully.');
    }

    public function logs(): string
    {
        $this->authorize('emails.view');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $perPage = 25;
        $db = Crm::db();

        $b = $db->table('crm_email_log el')
            ->select("el.*, c.name AS customer_name, o.name AS organisation_name, CONCAT(u.first_name, ' ', u.last_name) AS triggered_by_name")
            ->join('crm_customers c', 'c.id = el.customer_id', 'left')
            ->join('crm_organisations o', 'o.id = el.organisation_id', 'left')
            ->join('admin_users u', 'u.id = el.triggered_by_user', 'left');

        if (! empty($g['q'])) {
            $term = trim((string) $g['q']);
            $b->groupStart()
                ->like('el.to_email', $term)
                ->orLike('el.subject', $term)
                ->orLike('el.template_code', $term)
                ->groupEnd();
        }
        if (! empty($g['status'])) {
            $b->where('el.status', $g['status']);
        }

        $total = $b->countAllResults(false);
        $offset = max(0, ($page - 1) * $perPage);
        $rows = $b->orderBy('el.id', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return $this->render('communication/logs', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'filters' => $g,
        ], 'Communication Centre · Email Logs', 'communication');
    }

    public function retryLog(int $id): RedirectResponse
    {
        $this->authorize('emails.send');
        $db = Crm::db();
        $log = $db->table('crm_email_log')->where('id', $id)->get()->getRowArray() ?? throw new PageNotFoundException('Log not found.');

        if ($log['status'] !== 'failed') {
            return redirect()->back()->with('error', 'Only failed emails can be retried.');
        }

        EmailService::retryPending(10);
        return redirect()->back()->with('success', "Triggered retry queue for email #{$id}.");
    }

    public function recordCommunication(): RedirectResponse
    {
        $this->authorize('followups.manage');
        $post = $this->request->getPost();

        try {
            CommunicationService::log($post, $this->uid());
            $redirect = ! empty($post['return_url']) ? $post['return_url'] : site_url("crm/organisations/{$post['organisation_id']}?tab=communications");
            return redirect()->to($redirect)->with('success', 'Communication log recorded successfully.');
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }
}
