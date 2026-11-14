<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\JobAlertService;

class JobAlertApiController extends ResourceController
{
    protected $format = 'json';
    private JobAlertService $jobAlertService;

    public function __construct()
    {
        $this->jobAlertService = new JobAlertService();
    }

    /**
     * POST /api/v1/job-alerts/subscribe
     */
    public function subscribe()
    {
        // Honeypot check
        $honeypot = $this->request->getPost('website_url') ?? $this->request->getVar('website_url');
        if (!empty($honeypot)) {
            return $this->respond([
                'success' => true,
                'message' => 'Job alert preferences saved.',
            ]);
        }

        $email       = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        $category    = (string) ($this->request->getPost('job_category') ?? $this->request->getVar('job_category') ?? '');
        $location    = (string) ($this->request->getPost('preferred_location') ?? $this->request->getVar('preferred_location') ?? '');
        $experience  = (string) ($this->request->getPost('experience_level') ?? $this->request->getVar('experience_level') ?? '');
        $empType     = (string) ($this->request->getPost('employment_type') ?? $this->request->getVar('employment_type') ?? '');
        $frequency   = (string) ($this->request->getPost('frequency') ?? $this->request->getVar('frequency') ?? 'weekly');

        $result = $this->jobAlertService->subscribe($email, $category, $location, $experience, $empType, $frequency);

        $status = $result['success'] ? 200 : 422;
        return $this->respond($result, $status);
    }

    /**
     * POST /api/v1/job-alerts/unsubscribe
     */
    public function unsubscribe()
    {
        $token = (string) ($this->request->getPost('token') ?? $this->request->getVar('token') ?? '');
        if (empty($token)) {
            return $this->failValidationError('Missing unsubscribe token.');
        }

        $result = $this->jobAlertService->unsubscribe($token);
        $status = $result['success'] ? 200 : 400;
        return $this->respond($result, $status);
    }
}
