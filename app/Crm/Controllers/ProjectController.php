<?php

namespace Crm\Controllers;

use Crm\Services\Crm;
use Crm\Services\ProjectService;

/**
 * Customer Projects Controller
 * Handles customer projects, site locations, budgets and associated inquiries.
 */
class ProjectController extends CrmController
{
    public function index(): string
    {
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) $this->request->getGet('status'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = ProjectService::listProjects($params, $this->user());
        $customers = Crm::db()->table('crm_customers')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $salesUsers = Crm::db()->table('admin_users')->where('is_active', 1)->orderBy('first_name')->get()->getResultArray();

        return $this->render('projects/index', [
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'customers' => $customers,
            'salesUsers' => $salesUsers,
        ], 'Customer Projects', 'projects');
    }

    public function show(int $id): string
    {
        $project = ProjectService::getProject($id);
        return $this->render('projects/show', [
            'project' => $project,
        ], $project['name'] . ' · Project Details', 'projects');
    }

    public function save(?int $id = null)
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($post, $id) {
            $p = ProjectService::saveProject($post, $id, $this->user());
            return 'Project ' . $p['project_no'] . ' saved successfully.';
        }, 'Project saved successfully.', site_url('crm/projects'));
    }
}
