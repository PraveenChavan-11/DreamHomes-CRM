<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\JobAlertService;
use App\Models\WebsiteSettingModel;

class JobAlertController extends BaseController
{
    private JobAlertService $jobAlertService;
    private WebsiteSettingModel $settingModel;

    public function __construct()
    {
        $this->jobAlertService = new JobAlertService();
        $this->settingModel    = new WebsiteSettingModel();
    }

    /**
     * Public page to unsubscribe from job alerts
     */
    public function unsubscribe()
    {
        $token = $this->request->getGet('token');
        $settings = $this->settingModel->getAllKeyed();
        $result = null;

        if (!empty($token)) {
            $result = $this->jobAlertService->unsubscribe($token);
        }

        return view('public/job_alerts/unsubscribe', [
            'settings' => $settings,
            'result'   => $result,
            'token'    => $token,
        ]);
    }
}
