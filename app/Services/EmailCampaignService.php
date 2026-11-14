<?php

namespace App\Services;

use App\Models\EmailCampaignModel;
use App\Models\EmailDeliveryLogModel;
use App\Models\NewsletterSubscriberModel;
use App\Models\NewsletterPreferenceModel;
use App\Services\OutboxService;

class EmailCampaignService
{
    private EmailCampaignModel $campaignModel;
    private EmailDeliveryLogModel $deliveryLogModel;
    private NewsletterSubscriberModel $subscriberModel;
    private NewsletterPreferenceModel $preferenceModel;
    private OutboxService $outboxService;

    public function __construct()
    {
        $this->campaignModel   = new EmailCampaignModel();
        $this->deliveryLogModel = new EmailDeliveryLogModel();
        $this->subscriberModel = new NewsletterSubscriberModel();
        $this->preferenceModel = new NewsletterPreferenceModel();
        $this->outboxService   = new OutboxService();
    }

    /**
     * Render template with variable substitutions
     */
    public function renderTemplate(string $template, array $data): string
    {
        $search = [];
        $replace = [];
        foreach ($data as $key => $value) {
            $search[] = '{{' . $key . '}}';
            $replace[] = (string) $value;
        }
        return str_replace($search, $replace, $template);
    }

    /**
     * Dispatch an immediate test email to verify layout and variables
     */
    public function sendTestEmail(int $campaignId, string $testEmail): array
    {
        $campaign = $this->campaignModel->find($campaignId);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid recipient test email.'];
        }

        $dummyUnsubUrl = base_url('newsletter/unsubscribe?token=TEST_PREVIEW_TOKEN');
        $dummyPrefUrl  = base_url('newsletter/preferences?token=TEST_PREVIEW_TOKEN');

        $bodyHtml = str_replace(
            ['{{subscriber_name}}', '{{unsubscribe_url}}', '{{preferences_url}}'],
            ['Valued Subscriber (Test)', $dummyUnsubUrl, $dummyPrefUrl],
            $campaign['body_html']
        );

        $bodyText = str_replace(
            ['{{subscriber_name}}', '{{unsubscribe_url}}', '{{preferences_url}}'],
            ['Valued Subscriber (Test)', $dummyUnsubUrl, $dummyPrefUrl],
            $campaign['body_text'] ?: strip_tags($campaign['body_html'])
        );

        $subject = '[TEST PREVIEW] ' . $campaign['subject'];

        $emailService = $this->outboxService->configureMailer();
        $emailService->clear();
        $emailService->setTo($testEmail);
        $emailService->setSubject($subject);
        $emailService->setMessage($bodyHtml);
        if (!empty($bodyText)) {
            $emailService->setAltMessage($bodyText);
        }

        $sent = $emailService->send(false);
        if ($sent) {
            return ['success' => true, 'message' => "Test email successfully dispatched to {$testEmail}."];
        }

        $err = $emailService->printDebugger(['headers']);
        return ['success' => false, 'message' => 'Failed to send test email: ' . substr($err, 0, 200)];
    }

    /**
     * Broadcast campaign to verified subscribers (Suppression Enforced)
     */
    public function sendCampaign(int $campaignId): array
    {
        $campaign = $this->campaignModel->find($campaignId);
        if (!$campaign) {
            return ['success' => false, 'message' => 'Campaign not found.'];
        }

        if (in_array($campaign['status'], ['SENT', 'SENDING'], true)) {
            return ['success' => false, 'message' => 'Campaign has already been sent or is currently sending.'];
        }

        // Query only verified, active subscribers
        $subscribers = $this->subscriberModel
            ->where('status', 'subscribed')
            ->where('verified_at IS NOT NULL', null, false)
            ->findAll();

        if (empty($subscribers)) {
            return ['success' => false, 'message' => 'No active verified subscribers found matching criteria.'];
        }

        // Update campaign status to SENDING
        $this->campaignModel->update($campaignId, [
            'status'           => 'SENDING',
            'total_recipients' => count($subscribers),
        ]);

        $queued = 0;
        foreach ($subscribers as $sub) {
            // Strict suppression guard
            if ($this->subscriberModel->isSuppressed($sub['email'])) {
                continue;
            }

            $unsubToken = !empty($sub['unsubscribe_token_hash']) ? $sub['unsubscribe_token_hash'] : bin2hex(random_bytes(16));
            $unsubUrl   = base_url("newsletter/unsubscribe?token={$unsubToken}");
            $prefUrl    = base_url("newsletter/preferences?token={$unsubToken}");
            $name       = $sub['first_name'] ?: 'Subscriber';

            $renderedHtml = str_replace(
                ['{{subscriber_name}}', '{{unsubscribe_url}}', '{{preferences_url}}'],
                [esc($name), $unsubUrl, $prefUrl],
                $campaign['body_html']
            );

            $renderedText = str_replace(
                ['{{subscriber_name}}', '{{unsubscribe_url}}', '{{preferences_url}}'],
                [$name, $unsubUrl, $prefUrl],
                $campaign['body_text'] ?: strip_tags($campaign['body_html'])
            );

            // Log delivery entry
            $logId = (int) $this->deliveryLogModel->insert([
                'campaign_id'     => $campaignId,
                'subscriber_id'   => $sub['id'],
                'recipient_email' => $sub['email'],
                'subject'         => $campaign['subject'],
                'provider'        => 'smtp',
                'status'          => 'QUEUED',
                'attempt_count'   => 0,
            ]);

            // Enqueue into outbox with idempotency key
            $idempotencyKey = "camp_{$campaignId}_sub_{$sub['id']}";
            $this->outboxService->queueTemplate(
                'newsletter_welcome', // Will use custom content
                $sub['email'],
                $name,
                [],
                $idempotencyKey
            );

            // Directly overwrite outbox with the exact campaign HTML/Subject
            $db = \Config\Database::connect();
            $db->table('email_outbox')
                ->where('idempotency_key', $idempotencyKey)
                ->update([
                    'template_code' => 'campaign_' . $campaignId,
                    'subject'       => $campaign['subject'],
                    'body_html'     => $renderedHtml,
                    'body_text'     => $renderedText,
                ]);

            $queued++;
        }

        // Mark campaign sent
        $this->campaignModel->update($campaignId, [
            'status'     => 'SENT',
            'sent_at'    => date('Y-m-d H:i:s'),
            'sent_count' => $queued,
        ]);

        return [
            'success' => true,
            'message' => "Campaign successfully queued for {$queued} verified subscribers.",
            'queued'  => $queued,
        ];
    }
}
