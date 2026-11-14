<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\NewsletterService;
use App\Models\NewsletterSubscriberModel;
use App\Models\NewsletterPreferenceModel;

class NewsletterApiController extends ResourceController
{
    protected $format = 'json';
    private NewsletterService $newsletterService;
    private NewsletterSubscriberModel $subscriberModel;
    private NewsletterPreferenceModel $preferenceModel;

    public function __construct()
    {
        $this->newsletterService = new NewsletterService();
        $this->subscriberModel   = new NewsletterSubscriberModel();
        $this->preferenceModel   = new NewsletterPreferenceModel();
    }

    /**
     * POST /api/v1/newsletter/subscribe
     */
    public function subscribe()
    {
        // 1. Honeypot Anti-Spam Check
        $honeypot = $this->request->getPost('website_url') ?? $this->request->getVar('website_url');
        if (!empty($honeypot)) {
            // Silently discard spam bots with 200 OK
            return $this->respond([
                'success' => true,
                'message' => 'Subscription received.',
            ]);
        }

        // 2. Extract inputs
        $email = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        $firstName = (string) ($this->request->getPost('first_name') ?? $this->request->getVar('first_name') ?? '');
        $consent = (bool) ($this->request->getPost('consent') ?? $this->request->getVar('consent') ?? false);
        $source = (string) ($this->request->getPost('source') ?? $this->request->getVar('source') ?? 'web_form');
        $categories = (array) ($this->request->getPost('categories') ?? $this->request->getVar('categories') ?? []);

        // 3. Process via NewsletterService
        $result = $this->newsletterService->subscribe($email, $firstName, $consent, $source, $categories);

        if ($result['success']) {
            $result['redirect_url'] = base_url('thank-you?type=newsletter');
        } else {
            $result['redirect_url'] = base_url('submission-failed?type=newsletter&reason=validation');
        }

        $status = $result['success'] ? 200 : 422;
        return $this->respond($result, $status);
    }

    /**
     * POST /api/v1/newsletter/verify
     */
    public function verify()
    {
        $token = (string) ($this->request->getPost('token') ?? $this->request->getVar('token') ?? '');
        $email = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        if (empty($token) && empty($email)) {
            return $this->failValidationError('Missing verification token.');
        }

        $result = $this->newsletterService->verify($token);
        $status = $result['success'] ? 200 : 400;
        return $this->respond($result, $status);
    }

    /**
     * POST /api/v1/newsletter/unsubscribe
     */
    public function unsubscribe()
    {
        $token = (string) ($this->request->getPost('token') ?? $this->request->getVar('token') ?? '');
        $email = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        $reason = (string) ($this->request->getPost('reason') ?? $this->request->getVar('reason') ?? null);

        if (empty($token) && empty($email)) {
            return $this->failValidationError('Missing unsubscribe token.');
        }

        $result = $this->newsletterService->unsubscribe($token ?: null, $reason, $email ?: null);
        $status = $result['success'] ? 200 : 400;
        return $this->respond($result, $status);
    }

    /**
     * POST /api/v1/newsletter/resubscribe
     */
    public function resubscribe()
    {
        $token = (string) ($this->request->getPost('token') ?? $this->request->getVar('token') ?? '');
        $email = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        if (empty($token) && empty($email)) {
            return $this->failValidationError('Missing token.');
        }

        $result = $this->newsletterService->resubscribe($token ?: null, $email ?: null);
        $status = $result['success'] ? 200 : 400;
        return $this->respond($result, $status);
    }

    /**
     * GET /api/v1/newsletter/preferences
     */
    public function getPreferences()
    {
        $token = (string) ($this->request->getGet('token') ?? '');
        if (empty($token) && empty($email)) {
            return $this->failValidationError('Missing token parameter.');
        }

        $hash = hash('sha256', trim($token));
        $subscriber = $this->subscriberModel->findByUnsubscribeTokenHash($hash);

        if (!$subscriber) {
            return $this->failNotFound('Subscriber not found or invalid token.');
        }

        $prefs = $this->preferenceModel->getPreferencesBySubscriberId((int) $subscriber['id']);

        return $this->respond([
            'success'     => true,
            'email'       => $subscriber['email'],
            'preferences' => $prefs,
        ]);
    }

    /**
     * POST /api/v1/newsletter/preferences
     */
    public function updatePreferences()
    {
        $token = (string) ($this->request->getPost('token') ?? $this->request->getVar('token') ?? '');
        $email = (string) ($this->request->getPost('email') ?? $this->request->getVar('email') ?? '');
        $categories = (array) ($this->request->getPost('categories') ?? $this->request->getVar('categories') ?? []);

        if (empty($token) && empty($email)) {
            return $this->failValidationError('Missing token parameter.');
        }

        $result = $this->newsletterService->updatePreferences($token, $categories);
        $status = $result['success'] ? 200 : 400;
        return $this->respond($result, $status);
    }
}
