<?php

namespace App\Controllers\Admin;

use App\Models\AuditLogModel;
use App\Models\ContactEnquiryModel;
use App\Models\EmployerEnquiryModel;
use App\Services\EnquiryAutomationService;

class EnquiriesController extends AdminBaseController
{
    private EnquiryAutomationService $automationService;

    public function __construct()
    {
        $this->automationService = new EnquiryAutomationService();
    }
    public function employers()
    {
        if ($redirect = $this->requireAnyPermission(['employers.manage', 'enquiries.manage'])) {
            return $redirect;
        }

        $empModel = new EmployerEnquiryModel();
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $query = $empModel->orderBy('created_at', 'DESC');
        if (!empty($status)) {
            $query->where('status', strtoupper($status));
        }
        if (!empty($search)) {
            $query->groupStart()
                ->like('company_name', $search)
                ->orLike('contact_person', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->orLike('required_roles', $search)
                ->groupEnd();
        }

        // CSV Export Handler
        if ($this->request->getGet('export') === 'csv') {
            $records = $query->findAll(2000);

            $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM
            $csvData .= "ID,Company Name,Contact Person,Email,Phone,Required Roles,Positions,Location,Urgency,Status,Received Date\n";

            foreach ($records as $row) {
                $comp = str_replace('"', '""', $row['company_name'] ?? '');
                $person = str_replace('"', '""', $row['contact_person'] ?? '');
                $email = str_replace('"', '""', $row['email'] ?? '');
                $phone = str_replace('"', '""', $row['phone'] ?? '');
                $roles = str_replace('"', '""', $row['required_roles'] ?? ($row['positions_needed'] ?? ''));
                $pos = str_replace('"', '""', (string) ($row['number_of_positions'] ?? '1'));
                $loc = str_replace('"', '""', $row['location'] ?? '');
                $urg = str_replace('"', '""', $row['urgency'] ?? '');
                $st = str_replace('"', '""', $row['status'] ?? '');
                $date = str_replace('"', '""', $row['created_at'] ?? '');

                $csvData .= "\"{$row['id']}\",\"{$comp}\",\"{$person}\",\"{$email}\",\"{$phone}\",\"{$roles}\",\"{$pos}\",\"{$loc}\",\"{$urg}\",\"{$st}\",\"{$date}\"\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_employer_mandates_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        $totalCount = (clone $query)->countAllResults(false);
        $enquiries = $query->findAll($perPage, $offset);
        $totalPages = ceil($totalCount / $perPage);

        return $this->renderAdminView('admin/enquiries/employers', [
            'enquiries'  => $enquiries,
            'status'     => $status,
            'search'     => $search,
            'perPage'    => $perPage,
            'page'       => $page,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'title'      => 'Employer Hiring Inquiries — TalentHub Admin',
        ], 'employer_enquiries');
    }

    public function updateEmployerStatus($id)
    {
        if ($redirect = $this->requireAnyPermission(['employers.manage', 'enquiries.manage'])) {
            return $redirect;
        }

        $rawStatus    = strtoupper(trim($this->request->getPost('status') ?? ''));
        $comment      = $this->request->getPost('comment') ?? $this->request->getPost('notes');
        $followUpDate = $this->request->getPost('follow_up_date');
        $adminUser    = session()->get('admin_user');
        $adminUserId  = $adminUser['id'] ?? null;

        $statusMap = [
            'NEW'           => 'NEW',
            'CONTACTED'     => 'CONTACTED',
            'IN_PROGRESS'   => 'IN_DISCUSSION',
            'IN_DISCUSSION' => 'IN_DISCUSSION',
            'MANDATE_WON'   => 'MANDATE_WON',
            'CLOSED'        => 'MANDATE_WON',
            'CLOSED_LOST'   => 'CLOSED_LOST',
        ];

        $targetStatus = $statusMap[$rawStatus] ?? null;

        if (!$targetStatus) {
            session()->setFlashdata('error', 'Invalid employer mandate status selected.');
            return redirect()->to(base_url('admin/enquiries/employers'));
        }

        $result = $this->automationService->updateEmployerStatus(
            (int) $id,
            $targetStatus,
            $comment,
            $followUpDate,
            $adminUserId
        );

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setJSON($result);
        }

        if ($result['success']) {
            session()->setFlashdata('success', $result['message']);
        } else {
            session()->setFlashdata('error', $result['message']);
        }

        return redirect()->to(base_url('admin/enquiries/employers'));
    }

    public function contact()
    {
        if ($redirect = $this->requirePermission('enquiries.manage')) {
            return $redirect;
        }

        $contactModel = new ContactEnquiryModel();
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');
        $perPage = (int) ($this->request->getGet('per_page') ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 25;
        }
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset = ($page - 1) * $perPage;

        $query = $contactModel->orderBy('created_at', 'DESC');
        if (!empty($status)) {
            $query->where('status', strtoupper($status));
        }
        if (!empty($search)) {
            $query->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike('subject', $search)
                ->orLike('message', $search)
                ->groupEnd();
        }

        // CSV Export Handler
        if ($this->request->getGet('export') === 'csv') {
            $records = $query->findAll(2000);

            $csvData = "\xEF\xBB\xBF"; // UTF-8 BOM
            $csvData .= "ID,First Name,Last Name,Email,Phone,Subject,Message,Status,Received Date\n";

            foreach ($records as $row) {
                $fname = str_replace('"', '""', $row['first_name'] ?? '');
                $lname = str_replace('"', '""', $row['last_name'] ?? '');
                $email = str_replace('"', '""', $row['email'] ?? '');
                $phone = str_replace('"', '""', $row['phone'] ?? '');
                $sub = str_replace('"', '""', $row['subject'] ?? '');
                $msg = str_replace('"', '""', preg_replace("/\r|\n/", " ", $row['message'] ?? ''));
                $st = str_replace('"', '""', $row['status'] ?? '');
                $date = str_replace('"', '""', $row['created_at'] ?? '');

                $csvData .= "\"{$row['id']}\",\"{$fname}\",\"{$lname}\",\"{$email}\",\"{$phone}\",\"{$sub}\",\"{$msg}\",\"{$st}\",\"{$date}\"\n";
            }

            return $this->response
                ->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="talenthub_contact_messages_' . date('Y-m-d_His') . '.csv"')
                ->setBody($csvData);
        }

        $totalCount = (clone $query)->countAllResults(false);
        $messages = $query->findAll($perPage, $offset);
        $totalPages = ceil($totalCount / $perPage);

        return $this->renderAdminView('admin/enquiries/contact', [
            'messages'   => $messages,
            'status'     => $status,
            'search'     => $search,
            'perPage'    => $perPage,
            'page'       => $page,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'title'      => 'Contact Messages — TalentHub Admin',
        ], 'contact_enquiries');
    }

    public function updateContactStatus($id)
    {
        if ($redirect = $this->requirePermission('enquiries.manage')) {
            return $redirect;
        }

        $rawStatus    = strtoupper(trim($this->request->getPost('status') ?? ''));
        $replyMessage = $this->request->getPost('reply_message') ?? $this->request->getPost('comment');
        $followUpDate = $this->request->getPost('follow_up_date');
        $adminUser    = session()->get('admin_user');
        $adminUserId  = $adminUser['id'] ?? null;

        $statusMap = [
            'UNREAD'      => 'UNREAD',
            'NEW'         => 'UNREAD',
            'READ'        => 'READ',
            'IN_PROGRESS' => 'READ',
            'RESOLVED'    => 'RESPONDED',
            'RESPONDED'   => 'RESPONDED',
            'SPAM'        => 'SPAM',
        ];

        $targetStatus = $statusMap[$rawStatus] ?? null;

        if (!$targetStatus) {
            session()->setFlashdata('error', 'Invalid contact status selected.');
            return redirect()->to(base_url('admin/enquiries/contact'));
        }

        $result = $this->automationService->updateContactStatus(
            (int) $id,
            $targetStatus,
            $replyMessage,
            $followUpDate,
            $adminUserId
        );

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setJSON($result);
        }

        if ($result['success']) {
            session()->setFlashdata('success', $result['message']);
        } else {
            session()->setFlashdata('error', $result['message']);
        }

        return redirect()->to(base_url('admin/enquiries/contact'));
    }
}
