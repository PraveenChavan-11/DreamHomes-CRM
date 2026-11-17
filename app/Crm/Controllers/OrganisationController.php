<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\OrganisationService;
use Crm\Services\ContactService;

class OrganisationController extends CrmController
{
    public function index(): string
    {
        $this->authorize('customers.view');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $res = OrganisationService::list($g, $page, 20);

        $db = Crm::db();
        $owners = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $territories = $db->table('crm_territories')->where('is_active', 1)->orderBy('name')->get()->getResultArray();

        return $this->render('organisations/index', [
            'rows' => $res['rows'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => $res['per_page'],
            'totalPages' => $res['total_pages'],
            'filters' => $g,
            'owners' => $owners,
            'territories' => $territories,
        ], 'Organisations & Business Accounts', 'organisations');
    }

    public function new(): string
    {
        $this->authorize('customers.create');
        $db = Crm::db();
        $owners = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $territories = $db->table('crm_territories')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $sources = $db->table('crm_lead_sources')->where('is_active', 1)->orderBy('name')->get()->getResultArray();

        return $this->render('organisations/form', [
            'mode' => 'new',
            'org' => [],
            'owners' => $owners,
            'territories' => $territories,
            'sources' => $sources,
        ], 'New Organisation Account', 'organisations');
    }

    public function create(): RedirectResponse
    {
        $this->authorize('customers.create');
        $post = $this->request->getPost();

        try {
            $org = OrganisationService::create($post, $this->uid());
            return redirect()->to(site_url("crm/organisations/{$org['id']}"))->with('success', "Organisation '{$org['name']}' ({$org['org_no']}) created successfully.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function show(int $id): string
    {
        $this->authorize('customers.view');
        $data = OrganisationService::get360($id);
        if (! $data || empty($data['organisation'])) {
            throw new PageNotFoundException('Organisation not found.');
        }

        $activeTab = $this->request->getGet('tab') ?: 'overview';
        $data['activeTab'] = $activeTab;

        return $this->render('organisations/show', $data, $data['organisation']['name'] . ' · 360° Account View', 'organisations');
    }

    public function edit(int $id): string
    {
        $this->authorize('customers.edit');
        $org = OrganisationService::get($id) ?? throw new PageNotFoundException('Organisation not found.');
        $db = Crm::db();
        $owners = $db->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM admin_users WHERE is_active = 1 ORDER BY first_name")->getResultArray();
        $territories = $db->table('crm_territories')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $sources = $db->table('crm_lead_sources')->where('is_active', 1)->orderBy('name')->get()->getResultArray();

        return $this->render('organisations/form', [
            'mode' => 'edit',
            'org' => $org,
            'owners' => $owners,
            'territories' => $territories,
            'sources' => $sources,
        ], 'Edit ' . $org['name'], 'organisations');
    }

    public function update(int $id): RedirectResponse
    {
        $this->authorize('customers.edit');
        $post = $this->request->getPost();

        try {
            $org = OrganisationService::update($id, $post, $this->uid());
            return redirect()->to(site_url("crm/organisations/{$id}"))->with('success', "Organisation '{$org['name']}' updated.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function status(int $id): RedirectResponse
    {
        $this->authorize('customers.deactivate');
        $status = $this->request->getPost('status') ?? 'inactive';
        try {
            OrganisationService::status($id, $status, $this->uid());
            return redirect()->to(site_url("crm/organisations/{$id}"))->with('success', "Organisation status updated to '{$status}'.");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }

    public function sync(): RedirectResponse
    {
        $this->authorize('customers.create');
        $n = OrganisationService::syncFromCustomers();
        return redirect()->to(site_url('crm/organisations'))->with('success', "Safely synchronised {$n} customer accounts into Organisations and Contacts.");
    }
}
