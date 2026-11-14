<?php

namespace App\Services;

use App\Models\MailWebhookEventModel;
use App\Models\MailMessageIndexModel;
use App\Models\MailboxConnectionModel;
use App\Models\NotificationModel;
use Config\HostingerMail;

class MailWebhookService
{
    protected MailWebhookEventModel $eventModel;
    protected MailMessageIndexModel $msgIndex;
    protected MailboxConnectionModel $boxModel;
    protected NotificationModel $notifModel;
    protected HostingerMail $config;

    public function __construct()
    {
        $this->eventModel = new MailWebhookEventModel();
        $this->msgIndex = new MailMessageIndexModel();
        $this->boxModel = new MailboxConnectionModel();
        $this->notifModel = new NotificationModel();
        $this->config = config('HostingerMail');
    }

    /**
     * Verify incoming webhook authenticity
     */
    public function verifySecret(?string $suppliedSecret): bool
    {
        if (empty($this->config->webhookSecret)) {
            return true; // No secret configured yet, accept payload
        }
        return hash_equals($this->config->webhookSecret, (string) $suppliedSecret);
    }

    /**
     * Process inbound Hostinger Mail webhook event safely and idempotently
     */
    public function processEvent(array $payload, ?string $ipAddress = null): array
    {
        $eventId = $payload['event_id'] ?? $payload['id'] ?? ('evt_' . md5(json_encode($payload)));
        $eventType = $payload['event'] ?? $payload['event_type'] ?? 'message.created';
        $mailboxId = $payload['mailbox_resource_id'] ?? $payload['mailbox_id'] ?? null;

        // 1. Idempotency check: has this event already been processed?
        $existing = $this->eventModel->where('event_id', $eventId)->first();
        if ($existing && $existing['status'] === 'PROCESSED') {
            return [
                'success' => true,
                'status'  => 'ALREADY_PROCESSED',
                'message' => 'Event already processed',
            ];
        }

        $now = date('Y-m-d H:i:s');
        $recordId = $existing ? $existing['id'] : $this->eventModel->insert([
            'event_id'            => $eventId,
            'event_type'          => $eventType,
            'mailbox_resource_id' => $mailboxId,
            'payload_json'        => json_encode($payload),
            'status'              => 'PENDING',
            'ip_address'          => $ipAddress,
            'created_at'          => $now,
        ], true);

        try {
            // If incoming message event
            if (in_array($eventType, ['message.created', 'incoming_email', 'email.received'])) {
                $msgData = $payload['message'] ?? $payload['data'] ?? $payload;
                $uid = $msgData['uid'] ?? $msgData['id'] ?? ('msg_wh_' . uniqid());
                $folder = $msgData['folder'] ?? 'INBOX';

                $box = $mailboxId ? $this->boxModel->getByResourceId($mailboxId) : $this->boxModel->getDefaultMailbox();
                $actualBoxId = $box ? $box['mailbox_resource_id'] : ($mailboxId ?? 'mb_hr_pune_primary');

                $existingMsg = $this->msgIndex->findByUid($actualBoxId, $folder, $uid);
                if (!$existingMsg) {
                    $fromEmail = $msgData['from']['email'] ?? ($msgData['from_email'] ?? 'unknown@candidate.com');
                    $fromName = $msgData['from']['name'] ?? ($msgData['from_name'] ?? 'Candidate');
                    $subject = $msgData['subject'] ?? '(No Subject)';
                    $snippet = substr(strip_tags($msgData['body']['html'] ?? ($msgData['body_html'] ?? $msgData['snippet'] ?? '')), 0, 160);

                    $newMsg = [
                        'mailbox_resource_id' => $actualBoxId,
                        'message_uid'         => $uid,
                        'folder'              => $folder,
                        'thread_id'           => $msgData['thread_id'] ?? ('th_' . uniqid()),
                        'internet_message_id' => $msgData['message_id'] ?? ('<' . $uid . '@talenthubconsultant.com>'),
                        'subject'             => $subject,
                        'from_name'           => $fromName,
                        'from_email'          => $fromEmail,
                        'to_recipients'       => json_encode($msgData['to'] ?? [['email' => $box['email'] ?? 'hr@talenthubconsultant.com']]),
                        'snippet'             => $snippet,
                        'body_html'           => $msgData['body']['html'] ?? ($msgData['body_html'] ?? '<p>' . htmlspecialchars($snippet) . '</p>'),
                        'body_text'           => $msgData['body']['plain'] ?? ($msgData['body_plain'] ?? $snippet),
                        'has_attachments'     => !empty($msgData['attachments']) ? 1 : 0,
                        'attachments_meta'    => !empty($msgData['attachments']) ? json_encode($msgData['attachments']) : null,
                        'is_read'             => 0,
                        'message_date'        => $now,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];

                    // Classify
                    (new MailRecruitmentService())->autoClassifyAndLink($newMsg);
                    $this->msgIndex->insert($newMsg);

                    // Create alert for recruitment desk
                    $this->notifModel->insert([
                        'user_id'     => null,
                        'role_target' => 'admin',
                        'category'    => 'EMAIL_RECEIVED',
                        'title'       => 'New Email: ' . $subject,
                        'message'     => "Received from {$fromName} ({$fromEmail}) in {$folder}",
                        'priority'    => 'NORMAL',
                        'link'        => '/admin',
                        'is_read'     => 0,
                    ]);
                }
            }

            $this->eventModel->update($recordId, [
                'status'       => 'PROCESSED',
                'processed_at' => date('Y-m-d H:i:s'),
            ]);

            return ['success' => true, 'status' => 'PROCESSED'];
        } catch (\Throwable $e) {
            $this->eventModel->update($recordId, [
                'status'        => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
