<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\NewsletterService;
use App\Models\WebsiteSettingModel;

class NewsletterController extends BaseController
{
    private NewsletterService $newsletterService;
    private WebsiteSettingModel $settingModel;

    public function __construct()
    {
        $this->newsletterService = new NewsletterService();
        $this->settingModel      = new WebsiteSettingModel();
    }

    /**
     * Handle public web GET for verification links
     */
    public function verify()
    {
        $token = $this->request->getGet('token');
        if (empty($token)) {
            return redirect()->to(base_url())->with('error', 'Missing confirmation token.');
        }

        $result = $this->newsletterService->verify($token);
        $settings = $this->settingModel->getAllKeyed();

        return view('public/newsletter/verify', [
            'settings' => $settings,
            'result'   => $result,
            'token'    => $token,
        ]);
    }

    /**
     * Handle public web GET for unsubscribe and preference management
     */
    public function unsubscribe()
    {
        $token = $this->request->getGet('token');
        $settings = $this->settingModel->getAllKeyed();

        return view('public/newsletter/unsubscribe', [
            'settings' => $settings,
            'token'    => $token,
        ]);
    }

    /**
     * Handle public web GET for preferences
     */
    public function preferences()
    {
        $token = $this->request->getGet('token');
        $settings = $this->settingModel->getAllKeyed();

        return view('public/newsletter/preferences', [
            'settings' => $settings,
            'token'    => $token,
        ]);
    }
}
