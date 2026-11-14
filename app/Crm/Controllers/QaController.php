<?php

namespace Crm\Controllers;

use Crm\Services\AutoQaService;
use Crm\Services\TourService;

/**
 * Auto QA Command Centre & Interactive Tour Controller
 */
class QaController extends CrmController
{
    /**
     * Display the Auto QA Command Centre dashboard
     */
    public function index(): string
    {
        $latestRun = AutoQaService::getLatestRun();
        if (! $latestRun) {
            // Run an initial audit if none exists
            $latestRun = AutoQaService::executeRun(null, (int) ($this->user()['id'] ?? null));
        }

        $catalogue = AutoQaService::getCheckCatalogue();
        $categories = array_values(array_unique(array_column($catalogue, 'category')));

        return $this->render('admin/qa', [
            'run' => $latestRun,
            'catalogue' => $catalogue,
            'categories' => $categories,
        ], 'Auto QA Command Centre', 'reports');
    }

    /**
     * Trigger on-demand QA execution via POST
     */
    public function run()
    {
        $category = trim((string) $this->request->getPost('category'));
        $res = AutoQaService::executeRun($category ?: null, (int) ($this->user()['id'] ?? null));

        if ($this->request->isAJAX()) {
            return $this->response->setJSON($res);
        }

        session()->setFlashdata('success', 'QA Audit complete: ' . $res['passed'] . ' checks passed, ' . $res['failed'] . ' failed (' . $res['health_score'] . '% health).');
        return redirect()->to(site_url('crm/qa'));
    }

    /**
     * Get tour definition for the current logged-in user
     */
    public function getTour()
    {
        $tour = TourService::getTourForUser($this->user());
        return $this->response->setJSON($tour ?: ['error' => 'No tour available']);
    }

    /**
     * Save tour progress or skip state
     */
    public function updateTour()
    {
        $tourKey = (string) $this->request->getPost('tour_key');
        $step = (int) $this->request->getPost('step');
        $completed = (bool) $this->request->getPost('completed');
        $skipped = (bool) $this->request->getPost('skipped');

        TourService::updateProgress((int) ($this->user()['id'] ?? 0), $tourKey, $step, $completed, $skipped);
        return $this->response->setJSON(['status' => 'ok']);
    }

    /**
     * Restart tour
     */
    public function resetTour()
    {
        $tourKey = (string) $this->request->getPost('tour_key');
        TourService::resetTour((int) ($this->user()['id'] ?? 0), $tourKey);
        return $this->response->setJSON(['status' => 'ok']);
    }
}

