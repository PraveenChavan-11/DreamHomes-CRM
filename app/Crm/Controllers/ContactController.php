<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\ContactService;
use Crm\Services\OrganisationService;

class ContactController extends CrmController
{
    public function index(): string
    {
        $this->authorize('customers.view');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $res = ContactService::list($g, $page, 20);

        $db = Crm::db();
        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();

        return $this->render('contacts/index', [
            'rows' => $res['rows'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => $res['per_page'],
            'totalPages' => $res['total_pages'],
            'filters' => $g,
            'organisations' => $orgs,
        ], 'Contacts Directory', 'contacts');
    }

    public function new(): string
    {
        $this->authorize('customers.create');
        $orgId = (int) ($this->request->getGet('organisation_id') ?? 0);
        $db = Crm::db();
        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();

        return $this->render('contacts/form', [
            'mode' => 'new',
            'contact' => ['organisation_id' => $orgId],
            'organisations' => $orgs,
        ], 'New Contact Person', 'contacts');
    }

    public function create(): RedirectResponse
    {
        $this->authorize('customers.create');
        $post = $this->request->getPost();

        try {
            $contact = ContactService::create($post, $this->uid());
            $redirectUrl = ! empty($post['return_to_org'])
                ? site_url("crm/organisations/{$contact['organisation_id']}?tab=contacts")
                : site_url('crm/contacts');
            return redirect()->to($redirectUrl)->with('success', "Contact '{$contact['first_name']} {$contact['last_name']}' created successfully.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function edit(int $id): string
    {
        $this->authorize('customers.edit');
        $contact = ContactService::get($id) ?? throw new PageNotFoundException('Contact not found.');
        $db = Crm::db();
        $orgs = $db->table('crm_organisations')->select('id, name')->where('account_status', 'active')->orderBy('name')->get()->getResultArray();

        return $this->render('contacts/form', [
            'mode' => 'edit',
            'contact' => $contact,
            'organisations' => $orgs,
        ], 'Edit Contact · ' . $contact['first_name'] . ' ' . $contact['last_name'], 'contacts');
    }

    public function update(int $id): RedirectResponse
    {
        $this->authorize('customers.edit');
        $post = $this->request->getPost();

        try {
            $contact = ContactService::update($id, $post, $this->uid());
            $redirectUrl = ! empty($post['return_to_org'])
                ? site_url("crm/organisations/{$contact['organisation_id']}?tab=contacts")
                : site_url('crm/contacts');
            return redirect()->to($redirectUrl)->with('success', "Contact '{$contact['first_name']} {$contact['last_name']}' updated.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function setPrimary(int $id): RedirectResponse
    {
        $this->authorize('customers.edit');
        try {
            $contact = ContactService::get($id) ?? throw new PageNotFoundException('Contact not found.');
            ContactService::setPrimary($id);
            return redirect()->to(site_url("crm/organisations/{$contact['organisation_id']}?tab=contacts"))->with('success', "Set {$contact['first_name']} {$contact['last_name']} as primary contact.");
        } catch (CrmException $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }
}
