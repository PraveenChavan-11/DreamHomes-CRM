<?php

namespace Crm\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Crm\Services\Crm;
use Crm\Services\CrmException;
use Crm\Services\TargetService;

class TargetController extends CrmController
{
    public function index(): string
    {
        $this->authorize('reports.view', 'dashboard.view');
        $g = $this->request->getGet();
        $page = max(1, (int) ($g['page'] ?? 1));
        $res = TargetService::list($g, $this->user(), $page, 20);

        $db = Crm::db();
        $users = $db->table('admin_users')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $teams = $db->table('crm_teams')->select('id, name')->orderBy('name')->get()->getResultArray();

        return $this->render('targets/index', [
            'rows' => $res['rows'],
            'total' => $res['total'],
            'page' => $res['page'],
            'perPage' => $res['per_page'],
            'totalPages' => $res['total_pages'],
            'filters' => $g,
            'users' => $users,
            'teams' => $teams,
        ], 'Sales Targets & Performance', 'targets');
    }

    public function new(): string
    {
        $this->authorize('settings.manage', 'users.manage');
        $db = Crm::db();
        $users = $db->table('admin_users')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $teams = $db->table('crm_teams')->select('id, name')->orderBy('name')->get()->getResultArray();

        return $this->render('targets/form', [
            'mode' => 'new',
            'target' => [
                'frequency' => 'monthly',
                'period_start' => date('Y-m-01'),
                'period_end' => date('Y-m-t'),
                'period_label' => date('F Y'),
                'revenue_target' => 1000000.00,
            ],
            'users' => $users,
            'teams' => $teams,
        ], 'Assign Sales Target', 'targets');
    }

    public function create(): RedirectResponse
    {
        $this->authorize('settings.manage', 'users.manage');
        $post = $this->request->getPost();

        try {
            $t = TargetService::create($post, $this->uid());
            return redirect()->to(site_url("crm/targets/{$t['id']}"))->with('success', "Target {$t['target_no']} assigned successfully.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }

    public function show(int $id): string
    {
        $this->authorize('reports.view', 'dashboard.view');
        $target = TargetService::get($id) ?? throw new PageNotFoundException('Target record not found.');

        return $this->render('targets/show', [
            'target' => $target,
        ], 'Target Performance · ' . $target['period_label'] . ' (' . ($target['salesperson_name'] ?: ($target['team_name'] ?: 'All')) . ')', 'targets');
    }

    public function edit(int $id): string
    {
        $this->authorize('settings.manage', 'users.manage');
        $target = TargetService::get($id) ?? throw new PageNotFoundException('Target not found.');
        $db = Crm::db();
        $users = $db->table('admin_users')->select('id, name')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
        $teams = $db->table('crm_teams')->select('id, name')->orderBy('name')->get()->getResultArray();

        return $this->render('targets/form', [
            'mode' => 'edit',
            'target' => $target,
            'users' => $users,
            'teams' => $teams,
        ], 'Revise Target #' . $target['target_no'], 'targets');
    }

    public function update(int $id): RedirectResponse
    {
        $this->authorize('settings.manage', 'users.manage');
        $post = $this->request->getPost();

        try {
            $t = TargetService::update($id, $post, $this->uid());
            return redirect()->to(site_url("crm/targets/{$id}"))->with('success', "Target {$t['target_no']} updated.");
        } catch (CrmException $ex) {
            return redirect()->back()->withInput()->with('error', $ex->getMessage());
        }
    }
}
