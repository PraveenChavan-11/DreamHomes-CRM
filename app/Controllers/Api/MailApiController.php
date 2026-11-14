<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\HostingerMailService;
use App\Services\MailboxService;
use App\Services\MailRecruitmentService;
use App\Services\MailAnalyticsService;
use App\Services\MailTemplateService;
use App\Services\MailWebhookService;
use App\Models\MailboxConnectionModel;
use App\Models\MailMessageIndexModel;
use App\Models\MailLocalDraftModel;
use App\Models\MailActivityLogModel;
use App\Models\WebsiteSettingModel;
use CodeIgniter\HTTP\ResponseInterface;

class MailApiController extends BaseController
{
    protected HostingerMailService $apiService;
    protected MailboxService $boxService;
    protected MailRecruitmentService $recruitmentService;
    protected MailAnalyticsService $analyticsService;
    protected MailTemplateService $templateService;
    protected MailActivityLogModel $activityLog;

    public function __construct()
    {
        $this->apiService = new HostingerMailService();
        $this->boxService = new MailboxService($this->apiService);
        $this->recruitmentService = new MailRecruitmentService();
        $this->analyticsService = new MailAnalyticsService();
        $this->templateService = new MailTemplateService();
        $this->activityLog = new MailActivityLogModel();
    }

    /**
     * GET /api/v1/admin/mail/mailboxes
     */
    public function mailboxes(): ResponseInterface
    {
        $boxes = $this->boxService->getAccessibleMailboxes();
        return $this->respondJson([
            'mailboxes'   => $boxes,
            'configured'  => $this->apiService->isConfigured(),
            'default_box' => (new MailboxConnectionModel())->getDefaultMailbox(),
        ]);
    }

    /**
     * POST /api/v1/admin/mail/mailboxes/sync
     */
    public function syncMailboxes(): ResponseInterface
    {
        $result = $this->boxService->syncMailboxes();
        return $this->respondJson($result);
    }

    /**
     * GET /api/v1/admin/mail/folders
     */
    public function folders(): ResponseInterface
    {
        $boxId = $this->request->getGet('mailbox_id') ?? 'mb_hr_pune_primary';
        $res = $this->apiService->getFolders($boxId);
        return $this->respondJson($res);
    }

    /**
     * POST /api/v1/admin/mail/folders
     */
    public function createFolder(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $boxId = $input['mailbox_id'] ?? 'mb_hr_pune_primary';
        $folderName = trim($input['folder_name'] ?? '');

        if (empty($folderName)) {
            return $this->respondError('Folder name is required', 400);
        }

        $res = $this->apiService->createFolder($boxId, $folderName);
        $this->activityLog->record('FOLDER_CREATE', $boxId, null, ['folder' => $folderName]);
        return $this->respondJson($res);
    }

    /**
     * DELETE /api/v1/admin/mail/folders
     */
    public function deleteFolder(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getRawInput();
        $boxId = $input['mailbox_id'] ?? 'mb_hr_pune_primary';
        $folderName = trim($input['folder_name'] ?? '');

        if (empty($folderName)) {
            return $this->respondError('Folder name is required', 400);
        }

        $res = $this->apiService->deleteFolder($boxId, $folderName);
        $this->activityLog->record('FOLDER_DELETE', $boxId, null, ['folder' => $folderName]);
        return $this->respondJson($res);
    }

    /**
     * GET /api/v1/admin/mail/messages
     */
    public function messages(): ResponseInterface
    {
        $boxId    = $this->request->getGet('mailbox_id') ?? 'mb_hr_pune_primary';
        $folder   = $this->request->getGet('folder') ?? 'INBOX';
        $category = $this->request->getGet('category');
        $search   = $this->request->getGet('search');
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit    = min(50, max(1, (int) ($this->request->getGet('limit') ?? 20)));

        $params = [
            'page'     => $page,
            'limit'    => $limit,
            'search'   => $search,
            'category' => $category,
        ];

        $res = $this->apiService->listMessages($boxId, $folder, $params);
        return $this->respondJson($res);
    }

    /**
     * GET /api/v1/admin/mail/messages/detail
     */
    public function messageDetail(): ResponseInterface
    {
        $boxId  = $this->request->getGet('mailbox_id') ?? 'mb_hr_pune_primary';
        $folder = $this->request->getGet('folder') ?? 'INBOX';
        $uid    = $this->request->getGet('uid');

        if (empty($uid)) {
            return $this->respondError('Message UID is required', 400);
        }

        $res = $this->apiService->getMessage($boxId, $folder, $uid);
        if (!$res['success']) {
            return $this->respondError($res['message'] ?? 'Message not found', 404);
        }

        $msg = $res['data'];

        // Automatically mark as read if not already
        if (empty($msg['is_read'])) {
            $this->apiService->updateMessageFlags($boxId, $folder, $uid, ['is_read' => 1]);
            $msg['is_read'] = 1;
        }

        // Attach linked recruitment context
        $recContext = null;
        if (!empty($msg['recruitment_type']) && !empty($msg['recruitment_id'])) {
            $recContext = $this->recruitmentService->getRecruitmentContext($msg['recruitment_type'], (int) $msg['recruitment_id']);
        }
        $msg['recruitment_context'] = $recContext;

        $this->activityLog->record('READ', $boxId, $uid, ['subject' => $msg['subject'] ?? '']);

        return $this->respondJson($msg);
    }

    /**
     * POST /api/v1/admin/mail/messages/action
     */
    public function messageAction(): ResponseInterface
    {
        $input  = $this->request->getJSON(true) ?: $this->request->getPost();
        $boxId  = $input['mailbox_id'] ?? 'mb_hr_pune_primary';
        $folder = $input['folder'] ?? 'INBOX';
        $uid    = $input['uid'] ?? null;
        $action = $input['action'] ?? null;

        if (empty($uid) || empty($action)) {
            return $this->respondError('Message UID and action are required', 400);
        }

        switch ($action) {
            case 'mark_read':
                $res = $this->apiService->updateMessageFlags($boxId, $folder, $uid, ['is_read' => 1]);
                $this->activityLog->record('FLAG_READ', $boxId, $uid);
                return $this->respondJson($res);

            case 'mark_unread':
                $res = $this->apiService->updateMessageFlags($boxId, $folder, $uid, ['is_read' => 0]);
                $this->activityLog->record('FLAG_UNREAD', $boxId, $uid);
                return $this->respondJson($res);

            case 'flag':
                $isFlagged = (int) ($input['flagged'] ?? 1);
                $res = $this->apiService->updateMessageFlags($boxId, $folder, $uid, ['is_flagged' => $isFlagged]);
                $this->activityLog->record($isFlagged ? 'FLAG_STAR' : 'FLAG_UNSTAR', $boxId, $uid);
                return $this->respondJson($res);

            case 'move':
                $targetFolder = $input['target_folder'] ?? 'Trash';
                $res = $this->apiService->moveMessage($boxId, $folder, $uid, $targetFolder);
                $this->activityLog->record('MOVE', $boxId, $uid, ['target_folder' => $targetFolder]);
                return $this->respondJson($res);

            case 'delete':
                $res = $this->apiService->deleteMessage($boxId, $folder, $uid);
                $this->activityLog->record('DELETE', $boxId, $uid);
                return $this->respondJson($res);

            default:
                return $this->respondError('Unsupported message action', 400);
        }
    }

    /**
     * POST /api/v1/admin/mail/send
     */
    public function send(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $boxId = $input['mailbox_id'] ?? 'mb_hr_pune_primary';

        $to = $input['to'] ?? [];
        if (is_string($to)) {
            $to = [['email' => trim($to), 'name' => '']];
        }

        if (empty($to)) {
            return $this->respondError('At least one recipient email address is required', 422);
        }

        $subject = trim($input['subject'] ?? '');
        if (empty($subject)) {
            return $this->respondError('Subject line is required', 422);
        }

        $bodyHtml = $input['body_html'] ?? '';
        if (empty($bodyHtml)) {
            return $this->respondError('Email body content is required', 422);
        }

        $payload = [
            'to'               => $to,
            'cc'               => $input['cc'] ?? [],
            'bcc'              => $input['bcc'] ?? [],
            'subject'          => $subject,
            'body_html'        => $bodyHtml,
            'body_plain'       => strip_tags($bodyHtml),
            'in_reply_to'      => $input['in_reply_to'] ?? null,
            'thread_id'        => $input['thread_id'] ?? null,
            'category'         => $input['category'] ?? 'general',
            'recruitment_type' => $input['recruitment_type'] ?? null,
            'recruitment_id'   => $input['recruitment_id'] ?? null,
            'attachments'      => $input['attachments'] ?? [],
        ];

        $res = $this->apiService->sendEmail($boxId, $payload);
        if (!$res['success']) {
            return $this->respondError($res['message'] ?? 'Failed to send email via Hostinger Mail API', 500);
        }

        $this->activityLog->record('SEND', $boxId, $res['message_id'] ?? null, [
            'to'      => $to,
            'subject' => $subject,
        ]);

        return $this->respondJson($res, 'Email sent successfully via Hostinger Mail API');
    }

    /**
     * GET /api/v1/admin/mail/drafts
     */
    public function drafts(): ResponseInterface
    {
        $draftModel = new MailLocalDraftModel();
        $drafts = $draftModel->orderBy('updated_at', 'DESC')->findAll();
        return $this->respondJson($drafts);
    }

    /**
     * POST /api/v1/admin/mail/drafts
     */
    public function saveDraft(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $draftModel = new MailLocalDraftModel();

        $data = [
            'admin_user_id'       => session()->get('user_id') ?? 1,
            'mailbox_resource_id' => $input['mailbox_id'] ?? 'mb_hr_pune_primary',
            'to_recipients'       => is_array($input['to'] ?? null) ? json_encode($input['to']) : ($input['to'] ?? '[]'),
            'cc_recipients'       => !empty($input['cc']) ? json_encode($input['cc']) : null,
            'bcc_recipients'      => !empty($input['bcc']) ? json_encode($input['bcc']) : null,
            'subject'             => $input['subject'] ?? '',
            'body_html'           => $input['body_html'] ?? '',
            'body_text'           => strip_tags($input['body_html'] ?? ''),
            'attachments_json'    => !empty($input['attachments']) ? json_encode($input['attachments']) : null,
            'in_reply_to'         => $input['in_reply_to'] ?? null,
            'thread_id'           => $input['thread_id'] ?? null,
            'category'            => $input['category'] ?? 'general',
            'recruitment_type'    => $input['recruitment_type'] ?? null,
            'recruitment_id'      => $input['recruitment_id'] ?? null,
        ];

        $draftId = $input['draft_id'] ?? null;
        if (!empty($draftId)) {
            $draftModel->update($draftId, $data);
            $saved = $draftModel->find($draftId);
        } else {
            $insertedId = $draftModel->insert($data, true);
            $saved = $draftModel->find($insertedId);
        }

        $this->activityLog->record('DRAFT_SAVE', $data['mailbox_resource_id'], null, ['subject' => $data['subject']]);
        return $this->respondJson($saved, 'Draft saved successfully');
    }

    /**
     * DELETE /api/v1/admin/mail/drafts/(:num)
     */
    public function deleteDraft($id): ResponseInterface
    {
        $draftModel = new MailLocalDraftModel();
        $draftModel->delete($id);
        return $this->respondJson(['id' => $id], 'Draft deleted');
    }

    /**
     * GET /api/v1/admin/mail/templates
     */
    public function templates(): ResponseInterface
    {
        $templateModel = new \App\Models\EmailTemplateModel();
        $templates = $templateModel->where('is_active', 1)->findAll();
        return $this->respondJson($templates);
    }

    /**
     * POST /api/v1/admin/mail/templates/render
     */
    public function renderTemplate(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $code = $input['code'] ?? '';
        $vars = $input['variables'] ?? [];

        $rendered = $this->templateService->render($code, $vars);
        if (!$rendered) {
            return $this->respondError('Template not found or inactive', 404);
        }

        return $this->respondJson($rendered);
    }

    /**
     * GET /api/v1/admin/mail/analytics
     */
    public function analytics(): ResponseInterface
    {
        $metrics = $this->analyticsService->getDashboardMetrics();
        return $this->respondJson($metrics);
    }

    /**
     * GET /api/v1/admin/mail/settings
     */
    public function settings(): ResponseInterface
    {
        $config = config('HostingerMail');
        $settingModel = new WebsiteSettingModel();

        return $this->respondJson([
            'enabled'              => $config->enabled,
            'is_configured'        => $config->isConfigured(),
            'api_base_url'         => $config->apiBaseUrl,
            'masked_token'         => $config->getMaskedToken(),
            'default_mailbox_id'   => $config->defaultMailboxId,
            'webhook_secret'       => !empty($config->webhookSecret) ? '********' : '',
            'webhook_url'          => base_url('api/v1/webhooks/hostinger-mail'),
            'sync_interval_mins'   => $config->syncIntervalMinutes,
            'last_tested_status'   => $settingModel->getSetting('hostinger_mail_last_status', 'UNKNOWN'),
            'last_tested_at'       => $settingModel->getSetting('hostinger_mail_last_test_at'),
        ]);
    }

    /**
     * POST /api/v1/admin/mail/settings
     */
    public function updateSettings(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $settingModel = new WebsiteSettingModel();

        if (isset($input['api_token']) && $input['api_token'] !== '' && !str_contains($input['api_token'], '****')) {
            $settingModel->setSetting('hostinger_mail_api_token', trim($input['api_token']), 'email', 0);
        }

        if (isset($input['api_base_url'])) {
            $settingModel->setSetting('hostinger_mail_api_base_url', trim($input['api_base_url']), 'email', 0);
        }

        if (isset($input['default_mailbox_id'])) {
            $settingModel->setSetting('hostinger_mail_default_mailbox_id', trim($input['default_mailbox_id']), 'email', 0);
            (new MailboxConnectionModel())->where('id >', 0)->set('is_default', 0)->update();
            (new MailboxConnectionModel())->where('mailbox_resource_id', $input['default_mailbox_id'])->set('is_default', 1)->update();
        }

        if (isset($input['webhook_secret']) && $input['webhook_secret'] !== '' && !str_contains($input['webhook_secret'], '****')) {
            $settingModel->setSetting('hostinger_mail_webhook_secret', trim($input['webhook_secret']), 'email', 0);
        }

        if (isset($input['enabled'])) {
            $settingModel->setSetting('hostinger_mail_enabled', $input['enabled'] ? '1' : '0', 'email', 0);
        }

        $this->activityLog->record('SETTINGS_UPDATE', null, null, ['updated' => array_keys($input)]);

        return $this->respondJson(['success' => true], 'Hostinger Mail settings saved successfully');
    }

    /**
     * POST /api/v1/admin/mail/test-connection
     */
    public function testConnection(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $tokenToTest = (!empty($input['api_token']) && !str_contains($input['api_token'], '****')) ? trim($input['api_token']) : null;

        $res = $this->apiService->testConnection($tokenToTest);
        $settingModel = new WebsiteSettingModel();
        $settingModel->setSetting('hostinger_mail_last_status', $res['success'] ? 'SUCCESS' : 'FAILED', 'email', 0);
        $settingModel->setSetting('hostinger_mail_last_test_at', date('Y-m-d H:i:s'), 'email', 0);

        return $this->respondJson($res);
    }

    /**
     * POST /api/v1/admin/mail/link-recruitment
     */
    public function linkRecruitment(): ResponseInterface
    {
        $input  = $this->request->getJSON(true) ?: $this->request->getPost();
        $boxId  = $input['mailbox_id'] ?? 'mb_hr_pune_primary';
        $uid    = $input['uid'] ?? null;
        $folder = $input['folder'] ?? 'INBOX';
        $type   = $input['entity_type'] ?? null;
        $id     = (int) ($input['entity_id'] ?? 0);
        $notes  = $input['notes'] ?? null;

        if (empty($uid) || empty($type) || empty($id)) {
            return $this->respondError('UID, entity_type, and entity_id are required', 400);
        }

        $ok = $this->recruitmentService->linkRecord($boxId, $uid, $folder, $type, $id, $notes);
        if (!$ok) {
            return $this->respondError('Failed to link message to recruitment record', 404);
        }

        return $this->respondJson(['success' => true], 'Message linked to recruitment record');
    }

    /**
     * Public inbound webhook endpoint: POST /api/v1/webhooks/hostinger-mail
     */
    public function webhook(): ResponseInterface
    {
        $webhookService = new MailWebhookService();
        $suppliedSecret = $this->request->getHeaderLine('X-Hostinger-Webhook-Secret') ?: $this->request->getGet('secret');

        if (!$webhookService->verifySecret($suppliedSecret)) {
            return $this->respondError('Unauthorized webhook call: Invalid secret', 401);
        }

        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        if (empty($payload)) {
            return $this->respondError('Empty webhook payload', 400);
        }

        $result = $webhookService->processEvent($payload, $this->request->getIPAddress());
        return $this->respondJson($result, 'Webhook processed');
    }
}

