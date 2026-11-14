<?php

namespace App\Services;

use App\Models\NewsletterSubscriberModel;
use App\Models\NewsletterPreferenceModel;
use App\Models\NewsletterActivityLogModel;
use App\Services\OutboxService;

class NewsletterService
{
    private NewsletterSubscriberModel $subscriberModel;
    private NewsletterPreferenceModel $preferenceModel;
    private NewsletterActivityLogModel $activityModel;
    private OutboxService $outboxService;

    public function __construct()
    {
        $this->subscriberModel = new NewsletterSubscriberModel();
        $this->preferenceModel = new NewsletterPreferenceModel();
        $this->activityModel   = new NewsletterActivityLogModel();
        $this->outboxService   = new OutboxService();
    }

    /**
     * Subscribe a new or returning email address
     */
    public function subscribe(
        string $email,
        ?string $firstName = null,
        bool $consent = true,
        string $source = 'web_form',
        array $categories = []
    ): array {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'code'    => 'INVALID_EMAIL',
                'message' => 'Please provide a valid corporate or personal email address.',
            ];
        }

        if (!$consent) {
            return [
                'success' => false,
                'code'    => 'CONSENT_REQUIRED',
                'message' => 'Explicit marketing consent is required to receive newsletters.',
            ];
        }

        $existing = $this->subscriberModel->findByEmail($email);

        // Generate cryptographically secure verification token & SHA-256 hash
        $rawVerifyToken = bin2hex(random_bytes(32));
        $verifyTokenHash = hash('sha256', $rawVerifyToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+48 hours'));

        // Generate persistent unsubscribe token & SHA-256 hash
        $rawUnsubToken = bin2hex(random_bytes(32));
        $unsubTokenHash = hash('sha256', $rawUnsubToken);

        $defaultCategories = !empty($categories) ? $categories : [
            'engineering'         => true,
            'it_tech'             => true,
            'leadership'          => true,
            'general_recruitment' => true,
        ];

        if ($existing) {
            if ($existing['status'] === 'subscribed') {
                return [
                    'success' => true,
                    'code'    => 'ALREADY_SUBSCRIBED',
                    'message' => 'You are already actively subscribed to TalentHub hiring and technology insights.',
                ];
            }

            // If unverified or unsubscribed, refresh verification token
            $this->subscriberModel->update($existing['id'], [
                'first_name'              => $firstName ?: $existing['first_name'],
                'status'                  => 'unverified',
                'verification_token_hash' => $verifyTokenHash,
                'verification_expires_at' => $expiresAt,
                'consent_given'           => 1,
                'consent_timestamp'       => date('Y-m-d H:i:s'),
                'consent_source'          => $source,
                'unsubscribed_at'         => null,
            ]);

            $subscriberId = (int) $existing['id'];
            $this->preferenceModel->setPreferences($subscriberId, $defaultCategories);
            $this->activityModel->logActivity($subscriberId, 'resubscription_requested', $source);
        } else {
            // New subscriber
            $subscriberId = (int) $this->subscriberModel->insert([
                'email'                   => $email,
                'first_name'              => $firstName,
                'status'                  => 'unverified',
                'verification_token_hash' => $verifyTokenHash,
                'verification_expires_at' => $expiresAt,
                'unsubscribe_token_hash'  => $unsubTokenHash,
                'consent_given'           => 1,
                'consent_timestamp'       => date('Y-m-d H:i:s'),
                'consent_source'          => $source,
            ]);

            $this->preferenceModel->setPreferences($subscriberId, $defaultCategories);
            $this->activityModel->logActivity($subscriberId, 'subscribed', $source);
        }

        // Send double opt-in verification email via outbox
        $verifyUrl = base_url("newsletter/verify?token={$rawVerifyToken}");
        $this->outboxService->queueTemplate(
            'newsletter_verification',
            $email,
            $firstName ?: 'Subscriber',
            [
                'subscriber_name'  => $firstName ?: 'Subscriber',
                'verification_url' => $verifyUrl,
            ],
            'nl_verif_' . md5($email . date('YmdH'))
        );

        return [
            'success'            => true,
            'code'               => 'VERIFICATION_SENT',
            'message'            => 'Thank you! A confirmation link has been sent to your email. Please verify to activate your subscription.',
            'subscriber_id'      => $subscriberId,
            'verification_token' => $rawVerifyToken,
        ];
    }

    /**
     * Verify double opt-in subscription token
     */
    public function verify(string $rawToken): array
    {
        $hash = hash('sha256', trim($rawToken));
        $subscriber = $this->subscriberModel->findByVerificationTokenHash($hash);

        if (!$subscriber) {
            return [
                'success' => false,
                'code'    => 'INVALID_TOKEN',
                'message' => 'Invalid or expired confirmation link.',
            ];
        }

        if (!empty($subscriber['verification_expires_at']) && strtotime($subscriber['verification_expires_at']) < time()) {
            return [
                'success' => false,
                'code'    => 'TOKEN_EXPIRED',
                'message' => 'This confirmation link has expired. Please subscribe again to receive a fresh verification link.',
            ];
        }

        // Generate persistent unsubscribe token if not set
        $rawUnsubToken = bin2hex(random_bytes(32));
        $unsubTokenHash = hash('sha256', $rawUnsubToken);

        $this->subscriberModel->update($subscriber['id'], [
            'status'                  => 'subscribed',
            'verified_at'             => date('Y-m-d H:i:s'),
            'verification_token_hash' => null,
            'verification_expires_at' => null,
            'unsubscribe_token_hash'  => !empty($subscriber['unsubscribe_token_hash']) ? $subscriber['unsubscribe_token_hash'] : $unsubTokenHash,
        ]);

        $this->activityModel->logActivity((int) $subscriber['id'], 'verified', 'web_link');

        // Queue welcome email
        $unsubUrl = base_url("newsletter/unsubscribe?token={$rawUnsubToken}");
        $prefUrl  = base_url("newsletter/preferences?token={$rawUnsubToken}");

        $this->outboxService->queueTemplate(
            'newsletter_welcome',
            $subscriber['email'],
            $subscriber['first_name'] ?: 'Subscriber',
            [
                'subscriber_name' => $subscriber['first_name'] ?: 'Subscriber',
                'preferences_url' => $prefUrl,
                'unsubscribe_url' => $unsubUrl,
            ],
            'nl_welcome_' . $subscriber['id']
        );

        return [
            'success'    => true,
            'code'       => 'VERIFIED',
            'message'    => 'Your subscription is successfully verified! Welcome to TalentHub Pune Recruitment Intelligence.',
            'subscriber' => $subscriber,
        ];
    }

    /**
     * Unsubscribe a user using secure token hash or email address
     */
    public function unsubscribe(?string $rawToken = null, ?string $reason = null, ?string $email = null): array
    {
        $subscriber = null;
        if (!empty($rawToken)) {
            $hash = hash('sha256', trim($rawToken));
            $subscriber = $this->subscriberModel->findByUnsubscribeTokenHash($hash);
        }

        if (!$subscriber && !empty($email)) {
            $subscriber = $this->subscriberModel->findByEmail(trim(strtolower($email)));
        }

        if (!$subscriber) {
            return [
                'success' => false,
                'code'    => 'INVALID_TOKEN',
                'message' => 'Invalid unsubscribe link or email address not found in our directory.',
            ];
        }

        $this->subscriberModel->update($subscriber['id'], [
            'status'          => 'unsubscribed',
            'unsubscribed_at' => date('Y-m-d H:i:s'),
        ]);

        $this->activityModel->logActivity((int) $subscriber['id'], 'unsubscribed', 'web_link', ['reason' => $reason]);

        return [
            'success'    => true,
            'code'       => 'UNSUBSCRIBED',
            'message'    => 'You have been successfully unsubscribed from TalentHub promotional emails.',
            'subscriber' => $subscriber,
        ];
    }

    /**
     * Resubscribe an unsubscribed user using their token or email
     */
    public function resubscribe(?string $rawToken = null, ?string $email = null): array
    {
        $subscriber = null;
        if (!empty($rawToken)) {
            $hash = hash('sha256', trim($rawToken));
            $subscriber = $this->subscriberModel->findByUnsubscribeTokenHash($hash);
        }

        if (!$subscriber && !empty($email)) {
            $subscriber = $this->subscriberModel->findByEmail(trim(strtolower($email)));
        }

        if (!$subscriber) {
            return [
                'success' => false,
                'code'    => 'INVALID_TOKEN',
                'message' => 'Invalid link or record not found.',
            ];
        }

        $this->subscriberModel->update($subscriber['id'], [
            'status'          => 'subscribed',
            'unsubscribed_at' => null,
            'verified_at'     => $subscriber['verified_at'] ?: date('Y-m-d H:i:s'),
        ]);

        $this->activityModel->logActivity((int) $subscriber['id'], 'resubscribed', 'web_link');

        return [
            'success'    => true,
            'code'       => 'RESUBSCRIBED',
            'message'    => 'Welcome back! Your newsletter subscription has been reactivated.',
            'subscriber' => $subscriber,
        ];
    }

    /**
     * Update category preferences
     */
    public function updatePreferences(string $rawToken, array $categories): array
    {
        $hash = hash('sha256', trim($rawToken));
        $subscriber = $this->subscriberModel->findByUnsubscribeTokenHash($hash);

        if (!$subscriber) {
            return [
                'success' => false,
                'code'    => 'INVALID_TOKEN',
                'message' => 'Invalid preferences token.',
            ];
        }

        $this->preferenceModel->setPreferences((int) $subscriber['id'], $categories);
        $this->activityModel->logActivity((int) $subscriber['id'], 'preferences_updated', 'web_link', $categories);

        return [
            'success'     => true,
            'code'        => 'PREFERENCES_UPDATED',
            'message'     => 'Your communication preferences have been successfully updated.',
            'preferences' => $this->preferenceModel->getPreferencesBySubscriberId((int) $subscriber['id']),
        ];
    }

    /**
     * Check suppression status
     */
    public function isSuppressed(string $email): bool
    {
        return $this->subscriberModel->isSuppressed($email);
    }
}
