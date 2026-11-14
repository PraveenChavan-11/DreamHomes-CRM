<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Crm\Services\AccessScope;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\EmailService;
use Crm\Services\HistoryService;
use Crm\Services\Settings;

/**
 * Email log + manual email. Transmission rules (log_only mode, demo rows never transmitted, honest statuses) live in EmailService.
 * Row visibility: roles with scope "all" see every email; others only emails they triggered or that relate to a customer/enquiry they can see.
 */
class EmailController extends CrmController
{
    private const PER = 25;

    public function index(): string
    {
        $db = Crm::db();
        $f  = [
            'status' => (string) $this->request->getGet('status'), 'template' => (string) $this->request->getGet('template'), 'q' => trim((string) $this->request->getGet('q')),
            'from' => (string) $this->request->getGet('from'), 'to' => (string) $this->request->getGet('to'),
        ];
        $b = $db->table('crm_email_log m')->join('crm_customers c', 'c.id = m.customer_id', 'left');
        $this->scope($b);
        if (in_array($f['status'], ['queued', 'sent', 'delivered', 'failed', 'bounced'], true)) {
            $b->where('m.status', $f['status']);
        }
        if ($f['template'] !== '') {
            $b->where('m.template_code', $f['template']);
        }
        if ($f['q'] !== '') {
            $b->groupStart()->like('m.to_email', $f['q'])->orLike('m.subject', $f['q'])->orLike('c.name', $f['q'])->groupEnd();
        }
        if ($f['from'] !== '' && strtotime($f['from'])) {
            $b->where('m.created_at >=', $f['from'] . ' 00:00:00');
        }
        if ($f['to'] !== '' && strtotime($f['to'])) {
            $b->where('m.created_at <=', $f['to'] . ' 23:59:59');
        }
        $total = (int) (clone $b)->countAllResults(false);
        $pages = max(1, (int) ceil($total / self::PER));
        $page  = min($this->page(), $pages);
        $rows  = $b->select('m.id, m.to_email, m.subject, m.template_code, m.status, m.provider, m.attempts, m.created_at, m.is_demo, c.name AS customer_name, c.id AS cust_id')
            ->orderBy('m.id', 'DESC')->limit(self::PER, ($page - 1) * self::PER)->get()->getResultArray();
        $counts = [];
        foreach ($db->query('SELECT status, COUNT(*) n FROM crm_email_log GROUP BY status')->getResultArray() as $r) {
            $counts[$r['status']] = (int) $r['n'];
        }

        return $this->render('emails/index', [
            'rows' => $rows, 'f' => $f, 'total' => $total, 'page' => $page, 'pages' => $pages, 'counts' => $counts, 'mode' => Settings::get('email.mode'),
            'templates' => array_column($db->table('crm_email_log')->distinct()->select('template_code')->where('template_code IS NOT NULL')->orderBy('template_code')->get()->getResultArray(), 'template_code'),
            'customers' => $this->myCustomers(),
        ], 'Email Log', 'emails');
    }

    public function show(int $id): string
    {
        $db = Crm::db();
        $b  = $db->table('crm_email_log m')->where('m.id', $id);
        $this->scope($b);
        $m = $b->select('m.*')->get()->getRowArray() ?? throw new PageNotFoundException();
        $names = HistoryService::userNames();
        $rel   = [
            'customer'  => $m['customer_id'] ? $db->table('crm_customers')->select('id, customer_no, name')->where('id', $m['customer_id'])->get()->getRowArray() : null,
            'enquiry'   => $m['enquiry_id'] ? $db->table('crm_enquiries')->select('id, enquiry_no')->where('id', $m['enquiry_id'])->get()->getRowArray() : null,
            'quotation' => $m['quotation_id'] ? $db->table('crm_quotations')->select('id, quotation_no')->where('id', $m['quotation_id'])->get()->getRowArray() : null,
        ];

        return $this->render('emails/show', ['m' => $m, 'rel' => $rel, 'by' => $m['triggered_by_user'] ? ($names[(int) $m['triggered_by_user']] ?? 'User #' . $m['triggered_by_user']) : null, 'mode' => Settings::get('email.mode')], 'Email #' . $id, 'emails');
    }

    public function send()
    {
        $user = $this->user();
        $p    = $this->pick($this->request->getPost(), ['to', 'subject', 'body', 'customer_id']);

        return $this->act(function () use ($user, $p) {
            $cid = (int) ($p['customer_id'] ?? 0);
            if ($cid && ! AccessScope::canSeeCustomer($user, $cid)) {
                throw new CrmException('You do not have access to that customer.');
            }
            $to = strtolower((string) ($p['to'] ?? ''));
            if ($cid && $to === '') {
                $to = (string) (Crm::db()->table('crm_customers')->select('email')->where('id', $cid)->get()->getRow()->email ?? '');
            }
            $id  = EmailService::queue($to, ['subject' => (string) ($p['subject'] ?? ''), 'body' => (string) ($p['body'] ?? ''), 'customer_id' => $cid ?: null, 'user_id' => (int) $user['id']]);
            $row = Crm::db()->table('crm_email_log')->select('status, provider_response')->where('id', $id)->get()->getRowArray();
            session()->setFlashdata('info', 'Status: ' . crm_label($row['status']) . ' — ' . ($row['provider_response'] ?? ''));

            return 'crm/emails/' . $id;
        }, 'Email recorded.');
    }

    public function retry(int $id)
    {
        $user = $this->user();

        return $this->act(function () use ($id, $user) {
            $b = Crm::db()->table('crm_email_log m')->where('m.id', $id);
            $this->scope($b);
            $m = $b->select('m.status')->get()->getRowArray() ?? throw new PageNotFoundException();
            if ($m['status'] !== 'failed') {
                throw new CrmException('Only failed emails can be retried. Queued emails in log-only mode are never transmitted.');
            }
            $status = EmailService::dispatch($id);
            session()->setFlashdata('info', 'Retry result: ' . crm_label($status) . '.');
        }, 'Retry attempted.', 'crm/emails/' . $id);
    }

    /** Restrict a log query (alias m) to what the current user may see. */
    private function scope($b): void
    {
        $user = $this->user();
        $ids  = AccessScope::visibleUserIds($user);
        if ($ids === null) {
            return;
        }
        $in = implode(',', array_map('intval', $ids));
        $b->groupStart()->whereIn('m.triggered_by_user', $ids)
            ->orWhere("m.enquiry_id IN (SELECT id FROM crm_enquiries WHERE current_owner_id IN ({$in}) OR created_by IN ({$in}))", null, false)->groupEnd();
    }

    /** @return list<array{id:int,name:string,email:?string}> */
    private function myCustomers(): array
    {
        if (! crm_can('emails.send')) {
            return [];
        }
        $b = Crm::db()->table('crm_customers c')->select('c.id, c.name, c.email')->where('c.email IS NOT NULL')->orderBy('c.name')->limit(300);
        AccessScope::scopeCustomers($b, $this->user(), 'c');

        return $b->get()->getResultArray();
    }
}
