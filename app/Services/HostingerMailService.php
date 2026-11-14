<?php

namespace App\Services;

use Config\HostingerMail;
use CodeIgniter\Log\Logger;
use App\Services\EnvironmentSafetyService;

class HostingerMailService
{
    protected HostingerMail $config;
    protected string $baseUrl;
    protected ?string $token;
    protected int $timeout;

    public function __construct(?HostingerMail $config = null)
    {
        $this->config = $config ?? config('HostingerMail');
        $this->baseUrl = rtrim($this->config->apiBaseUrl, '/') . '/api/v1';
        $this->token = !empty($this->config->apiToken) ? $this->config->apiToken : null;
        $this->timeout = $this->config->timeoutSeconds ?? 20;
    }

    /**
     * Check if service is enabled and configured with token
     */
    public function isConfigured(): bool
    {
        return $this->config->enabled && !empty($this->token);
    }

    /**
     * Test connection to Hostinger Mail API via GET /api/v1/me
     */
    public function testConnection(?string $overrideToken = null): array
    {
        $testToken = $overrideToken ?? $this->token;
        if (empty($testToken)) {
            return [
                'success' => false,
                'status'  => 401,
                'message' => 'Hostinger Mail API token is not configured. Please supply a valid Bearer token.',
                'data'    => null,
            ];
        }

        return $this->sendRequest('GET', '/me', [], null, $testToken);
    }

    /**
     * Discover account info and all accessible mailboxes
     * GET /api/v1/me
     */
    public function getAccountInfo(): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Simulated account info (Token pending configuration)',
                'data' => [
                    'id' => 'acc_talenthub_pune',
                    'email' => 'hr@talenthubconsultant.com',
                    'domain' => 'talenthubconsultant.com',
                    'mailboxes' => [
                        [
                            'resource_id' => 'mb_hr_pune_primary',
                            'email' => 'hr@talenthubconsultant.com',
                            'display_name' => 'TalentHub HR & Recruitment',
                            'status' => 'ACTIVE',
                            'quota' => [
                                'used' => 142606336,
                                'total' => 10737418240,
                            ],
                        ],
                        [
                            'resource_id' => 'mb_info_general',
                            'email' => 'info@talenthubconsultant.com',
                            'display_name' => 'TalentHub General Inquiries',
                            'status' => 'ACTIVE',
                            'quota' => [
                                'used' => 54525952,
                                'total' => 10737418240,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $this->sendRequest('GET', '/me');
    }

    /**
     * List all folders in a mailbox
     * GET /api/v1/mailboxes/{mailboxResourceId}/folders
     */
    public function getFolders(string $mailboxResourceId): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'data' => [
                    ['name' => 'INBOX', 'unread_count' => 1, 'total_count' => 3],
                    ['name' => 'Sent', 'unread_count' => 0, 'total_count' => 1],
                    ['name' => 'Drafts', 'unread_count' => 0, 'total_count' => 0],
                    ['name' => 'Spam', 'unread_count' => 0, 'total_count' => 0],
                    ['name' => 'Trash', 'unread_count' => 0, 'total_count' => 0],
                ],
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders';
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Create a folder in a mailbox
     * POST /api/v1/mailboxes/{mailboxResourceId}/folders
     */
    public function createFolder(string $mailboxResourceId, string $folderName): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'message' => "Folder '{$folderName}' created (simulated)"];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders';
        return $this->sendRequest('POST', $endpoint, [], ['name' => $folderName]);
    }

    /**
     * Delete a folder in a mailbox
     * DELETE /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}
     */
    public function deleteFolder(string $mailboxResourceId, string $folderName): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'message' => "Folder '{$folderName}' deleted (simulated)"];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folderName);
        return $this->sendRequest('DELETE', $endpoint);
    }

    /**
     * List messages in a folder with pagination and optional search
     * GET /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages
     */
    public function listMessages(string $mailboxResourceId, string $folder = 'INBOX', array $params = []): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            // Read from local MySQL cache
            $msgModel = new \App\Models\MailMessageIndexModel();
            $builder = $msgModel->where('mailbox_resource_id', $mailboxResourceId)
                                ->where('folder', $folder);

            if (!empty($params['search'])) {
                $s = $params['search'];
                $builder->groupStart()
                        ->like('subject', $s)
                        ->orLike('from_name', $s)
                        ->orLike('from_email', $s)
                        ->orLike('snippet', $s)
                        ->groupEnd();
            }

            if (!empty($params['category'])) {
                $builder->where('category', $params['category']);
            }

            $page = max(1, (int) ($params['page'] ?? 1));
            $limit = min(50, max(1, (int) ($params['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $total = $builder->countAllResults(false);
            $items = $builder->orderBy('message_date', 'DESC')->get($limit, $offset)->getResultArray();

            return [
                'success' => true,
                'simulated' => true,
                'data' => $items,
                'pagination' => [
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages';
        return $this->sendRequest('GET', $endpoint, $params);
    }

    /**
     * Get single message details
     * GET /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}
     */
    public function getMessage(string $mailboxResourceId, string $folder, string $uid): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            $msgModel = new \App\Models\MailMessageIndexModel();
            $msg = $msgModel->findByUid($mailboxResourceId, $folder, $uid);
            if (!$msg) {
                return ['success' => false, 'status' => 404, 'message' => 'Message not found'];
            }
            return ['success' => true, 'simulated' => true, 'data' => $msg];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages/' . rawurlencode($uid);
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Get plain text or raw body of a message
     * GET /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}/text
     */
    public function getMessageText(string $mailboxResourceId, string $folder, string $uid): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            $msgModel = new \App\Models\MailMessageIndexModel();
            $msg = $msgModel->findByUid($mailboxResourceId, $folder, $uid);
            return [
                'success' => true,
                'data'    => $msg['body_text'] ?? strip_tags($msg['body_html'] ?? ''),
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages/' . rawurlencode($uid) . '/text';
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Download message attachment
     * GET /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}/attachments/{attachmentId}
     */
    public function getAttachment(string $mailboxResourceId, string $folder, string $uid, string $attachmentId): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'data' => [
                    'id' => $attachmentId,
                    'name' => 'sample_attachment.pdf',
                    'content_type' => 'application/pdf',
                    'content_base64' => base64_encode('%PDF-1.4 sample resume document'),
                ],
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages/' . rawurlencode($uid) . '/attachments/' . rawurlencode($attachmentId);
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Update message flags (is_read, is_flagged)
     * PATCH /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}
     */
    public function updateMessageFlags(string $mailboxResourceId, string $folder, string $uid, array $flags): array
    {
        // Always update local cache
        $msgModel = new \App\Models\MailMessageIndexModel();
        $msg = $msgModel->findByUid($mailboxResourceId, $folder, $uid);
        if ($msg) {
            $updates = [];
            if (isset($flags['is_read'])) $updates['is_read'] = (int) $flags['is_read'];
            if (isset($flags['is_flagged'])) $updates['is_flagged'] = (int) $flags['is_flagged'];
            if (!empty($updates)) {
                $msgModel->update($msg['id'], $updates);
            }
        }

        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'message' => 'Flags updated locally'];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages/' . rawurlencode($uid);
        return $this->sendRequest('PATCH', $endpoint, [], $flags);
    }

    /**
     * Move message to a different folder
     * POST /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}/move
     */
    public function moveMessage(string $mailboxResourceId, string $sourceFolder, string $uid, string $targetFolder): array
    {
        // Update local database
        $msgModel = new \App\Models\MailMessageIndexModel();
        $msg = $msgModel->findByUid($mailboxResourceId, $sourceFolder, $uid);
        if ($msg) {
            $msgModel->update($msg['id'], ['folder' => $targetFolder]);
        }

        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'message' => "Message moved to {$targetFolder}"];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($sourceFolder) . '/messages/' . rawurlencode($uid) . '/move';
        return $this->sendRequest('POST', $endpoint, [], ['destination_folder' => $targetFolder]);
    }

    /**
     * Delete message
     * DELETE /api/v1/mailboxes/{mailboxResourceId}/folders/{folder}/messages/{uid}
     */
    public function deleteMessage(string $mailboxResourceId, string $folder, string $uid): array
    {
        // Delete from local cache
        $msgModel = new \App\Models\MailMessageIndexModel();
        $msg = $msgModel->findByUid($mailboxResourceId, $folder, $uid);
        if ($msg) {
            $msgModel->delete($msg['id']);
        }

        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'message' => 'Message deleted locally'];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/folders/' . rawurlencode($folder) . '/messages/' . rawurlencode($uid);
        return $this->sendRequest('DELETE', $endpoint);
    }

    /**
     * Send email via Hostinger SendApi
     * POST /api/v1/mailboxes/{mailboxResourceId}/send
     */
    public function sendEmail(string $mailboxResourceId, array $payload): array
    {
        // Structure payload according to Hostinger OpenAPI SendApi
        $sendData = [
            'to'          => $payload['to'] ?? [],
            'cc'          => $payload['cc'] ?? [],
            'bcc'         => $payload['bcc'] ?? [],
            'subject'     => $payload['subject'] ?? '',
            'body'        => [
                'html'  => $payload['body_html'] ?? '',
                'plain' => $payload['body_plain'] ?? strip_tags($payload['body_html'] ?? ''),
            ],
        ];

        if (!empty($payload['in_reply_to'])) {
            $sendData['in_reply_to'] = $payload['in_reply_to'];
        }
        if (!empty($payload['references'])) {
            $sendData['references'] = (array) $payload['references'];
        }
        if (!empty($payload['attachments'])) {
            $sendData['attachments'] = $payload['attachments'];
        }

        // Record in local SENT folder index
        $now = date('Y-m-d H:i:s');
        $msgUid = 'msg_sent_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
        $boxModel = new \App\Models\MailboxConnectionModel();
        $box = $boxModel->getByResourceId($mailboxResourceId) ?? $boxModel->first();
        $fromEmail = $box['email'] ?? 'hr@talenthubconsultant.com';
        $fromName = $box['display_name'] ?? 'TalentHub HR';

        $msgIndexModel = new \App\Models\MailMessageIndexModel();
        $msgIndexModel->insert([
            'mailbox_resource_id' => $mailboxResourceId,
            'message_uid'         => $msgUid,
            'folder'              => 'SENT',
            'thread_id'           => $payload['thread_id'] ?? ('th_' . uniqid()),
            'internet_message_id' => '<' . $msgUid . '@talenthubconsultant.com>',
            'in_reply_to'         => $payload['in_reply_to'] ?? null,
            'subject'             => $payload['subject'] ?? '(No Subject)',
            'from_name'           => $fromName,
            'from_email'          => $fromEmail,
            'to_recipients'       => json_encode($payload['to']),
            'cc_recipients'       => !empty($payload['cc']) ? json_encode($payload['cc']) : null,
            'bcc_recipients'      => !empty($payload['bcc']) ? json_encode($payload['bcc']) : null,
            'snippet'             => substr(strip_tags($payload['body_html'] ?? ''), 0, 160),
            'body_html'           => $payload['body_html'] ?? '',
            'body_text'           => $payload['body_plain'] ?? strip_tags($payload['body_html'] ?? ''),
            'has_attachments'     => !empty($payload['attachments']) ? 1 : 0,
            'attachments_meta'    => !empty($payload['attachments']) ? json_encode($payload['attachments']) : null,
            'is_read'             => 1,
            'category'            => $payload['category'] ?? 'general',
            'recruitment_type'    => $payload['recruitment_type'] ?? null,
            'recruitment_id'      => $payload['recruitment_id'] ?? null,
            'message_date'        => $now,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success'   => true,
                'simulated' => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'   => 'Email accepted and saved to Sent items (Simulated dispatch)',
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/send';
        $res = $this->sendRequest('POST', $endpoint, [], $sendData);
        if ($res['success']) {
            $res['message_id'] = $msgUid;
        }
        return $res;
    }

    /**
     * List registered webhooks for a mailbox
     * GET /api/v1/mailboxes/{mailboxResourceId}/webhooks
     */
    public function listWebhooks(string $mailboxResourceId): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return ['success' => true, 'simulated' => true, 'data' => []];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/webhooks';
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Register a new webhook for incoming email notifications
     * POST /api/v1/mailboxes/{mailboxResourceId}/webhooks
     */
    public function registerWebhook(string $mailboxResourceId, string $targetUrl): array
    {
        if (EnvironmentSafetyService::isEmailSandboxed()) {
            return [
                'success'    => true,
                'sandboxed'  => true,
                'simulated'  => true,
                'message_id' => 'sandbox-' . bin2hex(random_bytes(6)),
                'message'    => 'Email captured safely in Staging Email Sandbox (No external dispatch)',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Webhook URL registered (Simulated)',
                'data' => [
                    'id' => 'wh_simulated_' . uniqid(),
                    'target_url' => $targetUrl,
                    'events' => ['message.created'],
                ]
            ];
        }

        $endpoint = '/mailboxes/' . rawurlencode($mailboxResourceId) . '/webhooks';
        return $this->sendRequest('POST', $endpoint, [], [
            'url'    => $targetUrl,
            'events' => ['message.created'],
        ]);
    }

    /**
     * Core HTTP Client method using native cURL with full error handling, TLS verification, and rate limits
     */
    protected function sendRequest(string $method, string $path, array $queryParams = [], ?array $body = null, ?string $tokenOverride = null): array
    {
        $token = $tokenOverride ?? $this->token;
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $headers = [
            'Accept: application/json',
            'User-Agent: TalentHub-Enterprise-Recruitment-Email/1.0',
        ];

        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if ($body !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $jsonPayload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($jsonPayload);
        }

        // Resilient retry loop with exponential backoff (GAP-05)
        $maxRetries = 2;
        $attempt = 0;
        $response = false;
        $httpCode = 0;
        $curlError = '';

        do {
            $attempt++;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if ($body !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Success or client error (no retry needed for 4xx)
            if ($response !== false && $httpCode < 500) {
                break;
            }

            // Exponential backoff delay before retry
            if ($attempt <= $maxRetries) {
                usleep($attempt * 250000); // 250ms, 500ms
            }
        } while ($attempt <= $maxRetries);

        if ($response === false) {
            return [
                'success' => false,
                'status'  => 504,
                'message' => 'Network failure contacting Hostinger Mail API: ' . $curlError,
                'data'    => null,
            ];
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'status'  => $httpCode,
                'message' => $decoded['message'] ?? 'OK',
                'data'    => $decoded['data'] ?? $decoded,
            ];
        }

        // Structured error handling for Hostinger Mail API
        $errorMessage = $decoded['message'] ?? $decoded['error'] ?? 'Hostinger Mail API request failed';
        if ($httpCode === 401) {
            $errorMessage = 'Invalid or expired Hostinger Mail API token.';
        } elseif ($httpCode === 403) {
            $errorMessage = 'Access denied. The token does not have permission for this mailbox.';
        } elseif ($httpCode === 404) {
            $errorMessage = 'Resource not found on Hostinger Mail API.';
        } elseif ($httpCode === 429) {
            $errorMessage = 'Rate limit exceeded on Hostinger Mail API. Please wait a moment before retrying.';
        } elseif ($httpCode >= 500) {
            $errorMessage = 'Hostinger Mail API server error (HTTP ' . $httpCode . ').';
        }

        return [
            'success' => false,
            'status'  => $httpCode,
            'message' => $errorMessage,
            'data'    => $decoded,
        ];
    }
}

