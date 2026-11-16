<?php

namespace Crm\Controllers;

use Crm\Services\CrmException;
use Crm\Services\MasterService;

/**
 * Enterprise Master Data Management Controller
 * Powers all 5 Master categories with full CRUD, search, filter, export, and dependency-checked deactivation.
 */
class MasterController extends CrmController
{
    /** Overview of all Master Groups & Sub-modules */
    public function index(): string
    {
        return $this->render('masters/index', [
            'groups' => MasterService::MASTER_GROUPS,
        ], 'Master Data Management', 'masters');
    }

    /** View or manage specific master records */
    public function view(string $typeKey): string
    {
        $meta = MasterService::getMasterMeta($typeKey) ?? throw new \CodeIgniter\Exceptions\PageNotFoundException("Master {$typeKey} not found.");
        $params = [
            'q' => (string) $this->request->getGet('q'),
            'status' => (string) ($this->request->getGet('status') ?: 'all'),
            'page' => max(1, (int) $this->request->getGet('page')),
            'limit' => 20,
        ];
        $data = MasterService::listItems($typeKey, $params);

        return $this->render('masters/view', [
            'meta' => $meta,
            'rows' => $data['rows'],
            'total' => $data['total'],
            'page' => $data['page'],
            'limit' => $data['limit'],
            'params' => $params,
            'groups' => MasterService::MASTER_GROUPS,
        ], $meta['name'] . ' · Master Data', 'masters');
    }

    /** Save or update master record */
    public function save(string $typeKey, ?int $id = null)
    {
        $post = (array) $this->request->getPost();
        return $this->act(function () use ($typeKey, $post, $id) {
            MasterService::saveRecord($typeKey, $post, $id, $this->user());
        }, 'Master record saved successfully.', site_url("crm/masters/{$typeKey}"));
    }

    /** Toggle record active status */
    public function toggle(string $typeKey, int $id)
    {
        return $this->act(function () use ($typeKey, $id) {
            $active = MasterService::toggleActive($typeKey, $id, $this->user());
            return $active ? 'Record activated.' : 'Record deactivated.';
        }, 'Record status updated.', site_url("crm/masters/{$typeKey}"));
    }

    /** Export master records to CSV */
    public function export(string $typeKey)
    {
        $csv = MasterService::exportCsv($typeKey);
        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $typeKey . '-export-' . date('Ymd-His') . '.csv"')
            ->setBody($csv);
    }
}
