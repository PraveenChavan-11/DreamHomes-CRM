<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\ContactEnquiryModel;
use App\Models\EmailOutboxModel;
use App\Models\EmployerEnquiryModel;
use App\Models\GeneralResumeSubmissionModel;
use App\Models\JobModel;
use App\Models\MailMessageIndexModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AdminBaseController extends BaseController
{
    protected ?array $adminUser = null;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Auto-provision schema and missing tables seamlessly
        \App\Services\DatabaseSchemaService::ensureSchema();

        $session = session();
        $this->adminUser = $session->get('admin_user');

        // Dynamically refresh roles & permissions for existing sessions
        if (!empty($this->adminUser['id']) && empty($this->adminUser['roles'])) {
            try {
                $userModel = new \App\Models\AdminUserModel();
                $this->adminUser['roles'] = $userModel->getUserRoles((int) $this->adminUser['id']);
                $this->adminUser['permissions'] = $userModel->getUserPermissions((int) $this->adminUser['id']);
                $session->set('admin_user', $this->adminUser);
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Ensure the user is authenticated as an admin.
     * Returns a redirect response or JSON response if unauthenticated, or null if ok.
     */
    protected function requireAuth(): ?ResponseInterface
    {
        if (empty($this->adminUser)) {
            if ($this->isApiRequest() || $this->request->isAJAX()) {
                return $this->respondError('Authentication required to access administrative resource.', 401, 'UNAUTHORIZED');
            }
            session()->setFlashdata('error', 'Please authenticate to access the administration dashboard.');
            return redirect()->to(base_url('admin/login'));
        }
        return null;
    }

    /**
     * Check if current user is Super Administrator
     */
    protected function isSuperAdmin(): bool
    {
        if (empty($this->adminUser)) {
            return false;
        }
        // Super Admin is decided ONLY by an assigned role. (A former shortcut treated user id 1 / username 'admin' as Super Admin
        // regardless of roles, which survived role removal; System Health now warns if no real Super Admin exists.)
        if (!empty($this->adminUser['roles'])) {
            foreach ($this->adminUser['roles'] as $r) {
                if (($r['slug'] ?? '') === 'super_admin' || ($r['name'] ?? '') === 'Super Administrator') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Enforce granular role and permission checks.
     * Super Admin bypasses all checks.
     */
    protected function requirePermission(string $permissionCode): ?ResponseInterface
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($this->isSuperAdmin()) {
            return null;
        }

        $perms = $this->adminUser['permissions'] ?? [];
        if (!in_array($permissionCode, $perms, true)) {
            if ($this->isApiRequest() || $this->request->isAJAX()) {
                return $this->respondError("Access restricted: required privilege '{$permissionCode}'.", 403, 'FORBIDDEN');
            }
            session()->setFlashdata('error', "Access restricted: required privilege '{$permissionCode}'.");
            return redirect()->to(base_url('admin'));
        }

        return null;
    }

    /**
     * Enforce that the user has at least one of the specified permissions.
     */
    protected function requireAnyPermission(array $permissionCodes): ?ResponseInterface
    {
        if ($redirect = $this->requireAuth()) {
            return $redirect;
        }

        if ($this->isSuperAdmin()) {
            return null;
        }

        $perms = $this->adminUser['permissions'] ?? [];
        foreach ($permissionCodes as $code) {
            if (in_array($code, $perms, true)) {
                return null;
            }
        }

        if ($this->isApiRequest() || $this->request->isAJAX()) {
            return $this->respondError("Access restricted: missing required module privilege.", 403, 'FORBIDDEN');
        }
        session()->setFlashdata('error', "Access restricted: you do not have permission to access this module.");
        return redirect()->to(base_url('admin'));
    }

    /**
     * Gather global admin sidebar badges and counts from live database tables.
     */
    protected function getAdminCounts(): array
    {
        $db = \Config\Database::connect();

        try {
            $newApps = $db->table('applications')->whereIn('status', ['APPLIED', 'applied', 'NEW', 'new', ''])->countAllResults();
        } catch (\Throwable $e) {
            $newApps = 0;
        }

        try {
            $totalResumes = $db->table('general_resume_submissions')->countAllResults();
        } catch (\Throwable $e) {
            $totalResumes = 0;
        }

        try {
            $newEmp = $db->table('employer_enquiries')->whereIn('status', ['NEW', 'new', 'UNREAD', 'unread'])->countAllResults();
        } catch (\Throwable $e) {
            $newEmp = 0;
        }

        try {
            $newContact = $db->table('contact_enquiries')->whereIn('status', ['UNREAD', 'unread', 'NEW', 'new', ''])->countAllResults();
        } catch (\Throwable $e) {
            $newContact = 0;
        }

        try {
            $pendingOutbox = $db->table('email_outbox')->whereIn('status', ['PENDING', 'pending', 'PROCESSING', 'processing', 'QUEUED', 'queued'])->countAllResults();
        } catch (\Throwable $e) {
            $pendingOutbox = 0;
        }

        try {
            $unreadMail = $db->table('mail_message_index')->where('is_read', 0)->countAllResults();
        } catch (\Throwable $e) {
            $unreadMail = 0;
        }

        try {
            $newItLeads = $db->tableExists('it_leads') ? $db->table('it_leads')->where('status', 'new')->where('deleted_at IS NULL')->countAllResults() : 0;
        } catch (\Throwable $e) {
            $newItLeads = 0;
        }

        return [
            'new_it_leads'       => $newItLeads,
            'new_applications'   => $newApps,
            'total_resumes'      => $totalResumes,
            'new_employer'       => $newEmp,
            'new_contact'        => $newContact,
            'pending_outbox'     => $pendingOutbox,
            'unread_mail'        => $unreadMail,
        ];
    }

    /**
     * Render an admin view inside the unified CodeIgniter 4 admin layout.
     */
    protected function renderAdminView(string $viewPath, array $data = [], string $activeMenu = 'dashboard'): string
    {
        $adminCounts = $this->getAdminCounts();
        try {
            $adminCounts['agent_pending'] = \Config\Database::connect()->tableExists('ai_agents') ? (new \App\Services\Agent\AgentApprovalService())->pendingCount() : 0;
        } catch (\Throwable) {
            $adminCounts['agent_pending'] = 0;
        }

        $mergedData = array_merge([
            'adminUser'   => $this->adminUser,
            'settings'    => $this->siteSettings,
            'counts'      => $adminCounts,
            'activeMenu'  => $activeMenu,
            'title'       => 'Admin Portal | ' . ($this->siteSettings['website_name'] ?? 'TalentHub Consultant'),
        ], $data);

        // saveData=false: do not leave this page's variables (notably `content`) in the shared renderer, otherwise a later
        // view() call in the same PHP process (tests, CLI) could render a previous page's content inside its layout.
        return view('admin/layouts/main', [
            'content'    => view($viewPath, $mergedData, ['saveData' => false]),
            'adminUser'  => $mergedData['adminUser'],
            'settings'   => $mergedData['settings'],
            'counts'     => $mergedData['counts'],
            'activeMenu' => $mergedData['activeMenu'],
            'title'      => $mergedData['title'],
        ], ['saveData' => false]);
    }
}

