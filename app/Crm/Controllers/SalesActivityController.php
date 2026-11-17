<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\SalesActivityService;
use Crm\Services\OrganisationService;
use Crm\Services\ContactService;

class SalesActivityController extends CrmController
{
    public function index(): string
    {
        $this->authorize('enquiries.view', 'followups.manage');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $res = SalesActivityService::list($g, $page, 20);
        $metrics = SalesActivityService::getDashboardMetrics($this->user());

        $db = Crm::db();
        $users = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();

        $viewMode = $g['view'] ?? 'list'; // list, calendar, timeline

        return $this->render('activities/index', [
            'rows' => $res['rows'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => $res['per_page'],
            'totalPages' => $res['total_pages'],
            'filters' => $g,
            'metrics' => $metrics,
            'users' => $users,
            'organisations' => $orgs,
            'viewMode' => $viewMode,
        ], 'Sales Activities', 'activities');
    }

    public function new(): string
    {
        $this->authorize('followups.manage');
        $g = $this->request->getGet();
        $db = Crm::db();

        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();
        $users = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $projects = $db->table('crm_projects')->select('id, name')->orderBy('name')->get()->getResultArray();

        $contacts = [];
        if (! empty($g['organisation_id'])) {
            $contacts = ContactService::forOrganisation((int) $g['organisation_id']);
        }

        return $this->render('activities/form', [
            'mode' => 'new',
            'activity' => [
                'organisation_id' => $g['organisation_id'] ?? null,
                'contact_id' => $g['contact_id'] ?? null,
                'project_id' => $g['project_id'] ?? null,
                'activity_type' => $g['type'] ?? 'customer_visit',
                'activity_date' => date('Y-m-d'),
                'status' => 'scheduled',
            ],
            'organisations' => $orgs,
            'contacts' => $contacts,
            'users' => $users,
            'projects' => $projects,
        ], 'Record Sales Activity', 'activities');
    }

    public function create(): RedirectResponse
    {
        $this->authorize('followups.manage');
        $post = $this->request->getPost();

        try {
            $act = SalesActivityService::create($post, $this->uid());
            $redirect = ! empty($post['return_to_org'])
                ? site_url("crm/organisations/{$act['organisation_id']}?tab=activities")
                : site_url("crm/activities/{$act['id']}");
            return redirect()->to($redirect)->with('success', "Sales Activity {$act['activity_no']} recorded.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function show(int $id): string
    {
        $this->authorize('enquiries.view', 'followups.manage');
        $act = SalesActivityService::get($id) ?? throw new PageNotFoundException('Activity not found.');

        return $this->render('activities/show', [
            'activity' => $act,
        ], 'Activity #' . $act['activity_no'], 'activities');
    }

    public function edit(int $id): string
    {
        $this->authorize('followups.manage');
        $act = SalesActivityService::get($id) ?? throw new PageNotFoundException('Activity not found.');
        $db = Crm::db();

        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();
        $users = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $projects = $db->table('crm_projects')->select('id, name')->orderBy('name')->get()->getResultArray();
        $contacts = $act['organisation_id'] ? ContactService::forOrganisation((int) $act['organisation_id']) : [];

        return $this->render('activities/form', [
            'mode' => 'edit',
            'activity' => $act,
            'organisations' => $orgs,
            'contacts' => $contacts,
            'users' => $users,
            'projects' => $projects,
        ], 'Edit Activity #' . $act['activity_no'], 'activities');
    }

    public function update(int $id): RedirectResponse
    {
        $this->authorize('followups.manage');
        $post = $this->request->getPost();

        try {
            $act = SalesActivityService::update($id, $post, $this->uid());
            return redirect()->to(site_url("crm/activities/{$id}"))->with('success', "Activity {$act['activity_no']} updated.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function complete(int $id): RedirectResponse
    {
        $this->authorize('followups.manage');
        $post = $this->request->getPost();

        try {
            SalesActivityService::complete($id, $post, $this->uid());
            return redirect()->to(site_url("crm/activities/{$id}"))->with('success', "Activity marked completed with outcome '" . crm_label($post['outcome'] ?? '') . "'.");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function reschedule(int $id): RedirectResponse
    {
        $this->authorize('followups.manage');
        $post = $this->request->getPost();
        $newDate = $post['new_date'] ?? date('Y-m-d');
        $reason = $post['reason'] ?? null;

        try {
            SalesActivityService::reschedule($id, $newDate, $reason, $this->uid());
            return redirect()->to(site_url("crm/activities/{$id}"))->with('success', "Activity rescheduled to " . crm_date($newDate) . ".");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function convertToLead(int $id): RedirectResponse
    {
        $this->authorize('enquiries.create');
        $post = $this->request->getPost();

        try {
            $res = SalesActivityService::convertToLead($id, $post, $this->uid());
            return redirect()->to(site_url("crm/enquiries/{$res['lead_id']}"))->with('success', "Successfully converted Activity to Opportunity {$res['enquiry_no']}.");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }
}
