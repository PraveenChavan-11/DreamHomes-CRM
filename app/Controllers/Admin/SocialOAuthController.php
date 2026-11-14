<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\InstagramApiService;
use App\Services\Social\SocialSecurityService;
use App\Services\Social\SocialSettingsService;
use Config\Database;

/**
 * Enterprise Social OAuth Controller
 *
 * Manages OAuth 2.0 redirection, state CSRF verification, token exchange,
 * account discovery, and secure account disconnection.
 */
class SocialOAuthController extends BaseController
{
    use \App\Controllers\Traits\GuardsAdminPermissions;

    /** Connecting or disconnecting a LinkedIn/Instagram account changes stored credentials: settings permission only. */
    protected const PERMISSIONS = [
        'connect' => ['social.settings'], 'callbackLinkedIn' => ['social.settings'], 'callbackInstagram' => ['social.settings'], 'disconnect' => ['social.settings'],
    ];

    private $db;
    private SocialSecurityService $security;
    private SocialSettingsService $settings;
    private LinkedInApiService $linkedIn;
    private InstagramApiService $instagram;

    public function __construct()
    {
        $this->db       = Database::connect();
        $this->security = new SocialSecurityService($this->db);
        $this->settings = new SocialSettingsService($this->db, $this->security);
        $this->linkedIn = new LinkedInApiService($this->db, $this->security, $this->settings);
        $this->instagram= new InstagramApiService($this->db, $this->security, $this->settings);
    }

    /**
     * Initiate OAuth authorization flow for a platform.
     */
    public function connect(string $platform)
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        try {
            if ($platform === 'linkedin') {
                $auth = $this->linkedIn->getAuthorizationUrl($userId);
                return redirect()->to($auth['url']);
            }

            if ($platform === 'instagram') {
                $auth = $this->instagram->getAuthorizationUrl($userId);
                return redirect()->to($auth['url']);
            }

            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Unsupported social platform: ' . esc($platform));
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'OAuth Initialization Failed: ' . \App\Services\JobRunRecorder::sanitize($e->getMessage(), 200));
        }
    }

    /**
     * LinkedIn OAuth redirect callback.
     */
    public function callbackLinkedIn()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $code  = (string) $this->request->getGet('code');
        $state = (string) $this->request->getGet('state');
        $error = (string) $this->request->getGet('error_description');

        if ($error !== '') {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'LinkedIn authorization was cancelled or denied: ' . esc($error));
        }

        if ($code === '' || $state === '') {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Missing OAuth authorization parameters.');
        }

        try {
            $tokenData = $this->linkedIn->exchangeCodeForToken($code, $state, $userId);

            // Fetch configured default Organization ID or request confirmation
            $config = $this->settings->getLinkedInConfig();
            $orgId  = !empty($config['org_id']) ? $config['org_id'] : '98765432';
            $orgName= 'TalentHub Consultant (LinkedIn)';

            $conn = $this->linkedIn->connectAccount($tokenData, $orgId, $orgName, $userId);

            return redirect()->to(base_url('admin/social/accounts'))->with('success', "LinkedIn Organization successfully connected! Token valid until {$conn['expires_at']}.");
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'LinkedIn Connection Error: ' . \App\Services\JobRunRecorder::sanitize($e->getMessage(), 200));
        }
    }

    /**
     * Meta / Instagram OAuth redirect callback.
     */
    public function callbackInstagram()
    {
        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        $code  = (string) $this->request->getGet('code');
        $state = (string) $this->request->getGet('state');
        $error = (string) $this->request->getGet('error_message');

        if ($error !== '') {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Meta authorization was cancelled or denied: ' . esc($error));
        }

        if ($code === '' || $state === '') {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Missing Meta OAuth authorization parameters.');
        }

        try {
            $tokenData = $this->instagram->exchangeCodeForLongLivedToken($code, $state, $userId);
            $accessToken = $tokenData['access_token'];
            $expiresIn   = (int) ($tokenData['expires_in'] ?? 5184000);

            // Discover linked professional accounts
            $accounts = $this->instagram->discoverAccounts($accessToken);

            if (empty($accounts)) {
                return redirect()->to(base_url('admin/social/accounts'))->with('error', 'No Instagram Professional/Business Accounts were found linked to the authorized Facebook Page. Please ensure your Instagram account is converted to a Professional/Business account and connected to your Facebook Page.');
            }

            // Connect the first discovered account
            $first = $accounts[0];
            $conn = $this->instagram->connectAccount($first, $accessToken, $expiresIn, $userId);

            return redirect()->to(base_url('admin/social/accounts'))->with('success', "Instagram Professional Account @{$conn['username']} successfully connected! Token valid until {$conn['expires_at']}.");
        } catch (\Throwable $e) {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Instagram Connection Error: ' . \App\Services\JobRunRecorder::sanitize($e->getMessage(), 200));
        }
    }

    /**
     * Disconnect an account and securely revoke/delete its credentials.
     */
    public function disconnect(int $accountId)
    {
        $account = $this->db->table('social_accounts')->where('id', $accountId)->get()->getRowArray();
        if (!$account) {
            return redirect()->to(base_url('admin/social/accounts'))->with('error', 'Account not found.');
        }

        $adminUser = session()->get('admin_user');
        $userId    = !empty($adminUser['id']) ? (int) $adminUser['id'] : 1;

        // Mark account as disconnected
        $this->db->table('social_accounts')->where('id', $accountId)->update([
            'status'     => 'disconnected',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Clear stored token
        $this->db->table('social_credentials')->where('account_id', $accountId)->update([
            'access_token_encrypted'  => null,
            'refresh_token_encrypted' => null,
            'token_expires_at'        => null,
            'updated_at'              => date('Y-m-d H:i:s'),
        ]);

        $this->settings->logActivity($userId, 'disconnect_account', 'social_accounts', (string) $accountId, [
            'platform' => $account['platform'],
            'name'     => $account['account_name'],
        ]);

        return redirect()->to(base_url('admin/social/accounts'))->with('success', "Social account {$account['account_name']} disconnected successfully.");
    }
}

