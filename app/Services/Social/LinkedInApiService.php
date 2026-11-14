<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise LinkedIn API Integration Service
 *
 * Supports official LinkedIn OAuth 2.0, Organization Page authorization,
 * Posts API publishing (text, single/multi-image, video, PDF documents),
 * organizational share statistics, comments & engagement, and transient error classification.
 */
class LinkedInApiService
{
    private BaseConnection $db;
    private SocialSecurityService $security;
    private SocialSettingsService $settings;

    public const OAUTH_AUTH_URL  = 'https://www.linkedin.com/oauth/v2/authorization';
    public const OAUTH_TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
    public const API_BASE_URL    = 'https://api.linkedin.com';

    public const DEFAULT_SCOPES = [
        'w_organization_social',
        'r_organization_social',
        'r_organization_admin',
        'openid',
        'profile',
        'email',
    ];

    private ?string $mockAccessToken = null;

    public function __construct(?BaseConnection $db = null, ?SocialSecurityService $security = null, ?SocialSettingsService $settings = null)
    {
        $this->db       = $db ?? Database::connect();
        $this->security = $security ?? new SocialSecurityService($this->db);
        $this->settings = $settings ?? new SocialSettingsService($this->db, $this->security);
    }

    /** Test hook to set mock access token for unit tests */
    public function setMockAccessToken(?string $token): void
    {
        $this->mockAccessToken = $token;
    }

    /**
     * Build OAuth 2.0 authorization URL.
     */
    public function getAuthorizationUrl(int $adminUserId, ?array $customScopes = null): array
    {
        $config = $this->settings->getLinkedInConfig();
        if (empty($config['client_id'])) {
            throw new \RuntimeException('LinkedIn Client ID is not configured. Please save credentials in Settings.');
        }

        $scopes = $customScopes ?? self::DEFAULT_SCOPES;
        $state  = $this->security->generateOAuthState('linkedin', $adminUserId, $config['redirect_uri']);

        $params = [
            'response_type' => 'code',
            'client_id'     => $config['client_id'],
            'redirect_uri'  => $config['redirect_uri'],
            'state'         => $state,
            'scope'         => implode(' ', $scopes),
        ];

        return [
            'url'   => self::OAUTH_AUTH_URL . '?' . http_build_query($params),
            'state' => $state,
        ];
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeCodeForToken(string $code, string $state, ?int $adminUserId = null): array
    {
        $stateValidation = $this->security->validateOAuthState($state, 'linkedin', $adminUserId);
        if (!$stateValidation['valid']) {
            throw new \InvalidArgumentException($stateValidation['message']);
        }

        $config = $this->settings->getLinkedInConfig();
        $redirectUri = $stateValidation['redirect_uri'] ?? $config['redirect_uri'];

        if ($this->mockAccessToken !== null) {
            return [
                'access_token'  => $this->mockAccessToken,
                'expires_in'    => 5184000, // 60 days
                'token_type'    => 'Bearer',
                'scope'         => implode(' ', self::DEFAULT_SCOPES),
            ];
        }

        $response = $this->httpPost(self::OAUTH_TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);

        if (empty($response['access_token'])) {
            $err = $response['error_description'] ?? $response['error'] ?? 'Failed to exchange authorization code for access token.';
            throw new \RuntimeException("LinkedIn OAuth error: {$err}");
        }

        return $response;
    }

    /**
     * Connect or update a LinkedIn Organization account.
     */
    public function connectAccount(array $tokenData, string $orgId, string $orgName, ?int $connectedBy = null): array
    {
        $accessToken  = $tokenData['access_token'];
        $expiresIn    = (int) ($tokenData['expires_in'] ?? 5184000);
        $expiresAt    = date('Y-m-d H:i:s', time() + $expiresIn);
        $refreshToken = $tokenData['refresh_token'] ?? null;
        $scopes       = $tokenData['scope'] ?? implode(' ', self::DEFAULT_SCOPES);

        $cleanOrgId = preg_replace('/[^0-9]/', '', $orgId);
        if ($cleanOrgId === '') {
            throw new \InvalidArgumentException('A valid numerical LinkedIn Organization ID is required.');
        }

        $existing = $this->db->table('social_accounts')
            ->where('platform', 'linkedin')
            ->where('external_id', $cleanOrgId)
            ->get()
            ->getRowArray();

        $accountData = [
            'platform'         => 'linkedin',
            'account_name'     => $orgName,
            'account_type'     => 'company',
            'external_id'      => $cleanOrgId,
            'profile_url'      => "https://www.linkedin.com/company/{$cleanOrgId}",
            'status'           => 'active',
            'connected_by'     => $connectedBy,
            'last_verified_at' => date('Y-m-d H:i:s'),
            'last_synced_at'   => date('Y-m-d H:i:s'),
            'last_error'       => null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $accountId = (int) $existing['id'];
            $this->db->table('social_accounts')->where('id', $accountId)->update($accountData);
        } else {
            $accountData['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('social_accounts')->insert($accountData);
            $accountId = (int) $this->db->insertID();
        }

        // Store encrypted credentials for this account
        $credData = [
            'account_id'              => $accountId,
            'platform'                => 'linkedin',
            'access_token_encrypted'  => $this->security->encryptSecret($accessToken),
            'refresh_token_encrypted' => $refreshToken ? $this->security->encryptSecret($refreshToken) : null,
            'token_expires_at'        => $expiresAt,
            'scopes'                  => $scopes,
            'api_version'             => '202401',
            'updated_at'              => date('Y-m-d H:i:s'),
        ];

        $existingCred = $this->db->table('social_credentials')
            ->where('account_id', $accountId)
            ->get()
            ->getRowArray();

        if ($existingCred) {
            $this->db->table('social_credentials')->where('id', $existingCred['id'])->update($credData);
        } else {
            $credData['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('social_credentials')->insert($credData);
        }

        $this->settings->logActivity($connectedBy, 'connect_account', 'social_accounts', (string) $accountId, [
            'platform'   => 'linkedin',
            'org_id'     => $cleanOrgId,
            'org_name'   => $orgName,
            'expires_at' => $expiresAt,
        ]);

        return [
            'success'    => true,
            'account_id' => $accountId,
            'org_id'     => $cleanOrgId,
            'org_name'   => $orgName,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Retrieve decrypted access token for a connected account.
     */
    public function getAccountAccessToken(int $accountId): string
    {
        if ($this->mockAccessToken !== null) {
            return $this->mockAccessToken;
        }

        $cred = $this->db->table('social_credentials')
            ->where('account_id', $accountId)
            ->where('platform', 'linkedin')
            ->get()
            ->getRowArray();

        if (!$cred || empty($cred['access_token_encrypted'])) {
            throw new \RuntimeException('No credentials found for LinkedIn account ID: ' . $accountId);
        }

        if (!empty($cred['token_expires_at']) && strtotime($cred['token_expires_at']) < time()) {
            throw new \RuntimeException('LinkedIn access token has expired. Please reconnect the account.');
        }

        return $this->security->decryptSecret($cred['access_token_encrypted']);
    }

    /**
     * Publish a post to LinkedIn Organization Page using Posts API.
     *
     * Supports:
     * - Text-only
     * - Single or multi-image
     * - Video
     * - PDF Documents
     */
    public function publishPost(int $accountId, string $text, array $mediaFiles = [], ?string $destinationUrl = null): array
    {
        $account = $this->db->table('social_accounts')->where('id', $accountId)->get()->getRowArray();
        if (!$account || $account['status'] !== 'active') {
            throw new \RuntimeException('LinkedIn account is not active or disconnected.');
        }

        $orgId = $account['external_id'];
        $authorUrn = "urn:li:organization:{$orgId}";
        $accessToken = $this->getAccountAccessToken($accountId);

        // Character limit assertion
        if (mb_strlen($text, 'UTF-8') > 3000) {
            throw new \InvalidArgumentException('LinkedIn post text exceeds maximum limit of 3,000 characters.');
        }

        // Mock mode for local testing
        if ($this->mockAccessToken !== null || str_starts_with($accessToken, 'mock_')) {
            $mockPostId = 'urn:li:share:mock_' . time() . '_' . bin2hex(random_bytes(4));
            return [
                'success'           => true,
                'external_post_id'  => $mockPostId,
                'external_permalink'=> "https://www.linkedin.com/feed/update/{$mockPostId}",
                'raw_response'      => ['id' => $mockPostId, 'mock' => true],
            ];
        }

        // Build request payload according to LinkedIn /rest/posts API
        $postData = [
            'author'               => $authorUrn,
            'commentary'           => $text,
            'visibility'           => 'PUBLIC',
            'distribution'         => [
                'feedDistribution'               => 'MAIN_FEED',
                'targetEntities'                 => [],
                'thirdPartyDistributionChannels' => [],
            ],
            'lifecycleState'       => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        ];

        // Process media attachments if provided
        if (!empty($mediaFiles)) {
            $mediaPayload = $this->prepareMediaContent($accessToken, $orgId, $mediaFiles);
            if (!empty($mediaPayload)) {
                $postData['content'] = $mediaPayload;
            }
        } elseif ($destinationUrl) {
            $postData['content'] = [
                'article' => [
                    'source' => $destinationUrl,
                ],
            ];
        }

        $url = self::API_BASE_URL . '/rest/posts';
        $headers = [
            'Authorization'             => "Bearer {$accessToken}",
            'LinkedIn-Version'          => '202401',
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
        ];

        $response = $this->httpPost($url, $postData, $headers, true);

        $externalId = $response['id'] ?? $response['x-restli-id'] ?? null;
        if (!$externalId && !empty($response['headers']['x-restli-id'])) {
            $externalId = $response['headers']['x-restli-id'];
        }

        if (!$externalId) {
            $errMsg = $response['message'] ?? 'Failed to retrieve published post ID from LinkedIn API response.';
            throw new \RuntimeException("LinkedIn Publishing Failed: {$errMsg}");
        }

        $permalink = "https://www.linkedin.com/feed/update/{$externalId}";

        return [
            'success'            => true,
            'external_post_id'   => $externalId,
            'external_permalink' => $permalink,
            'raw_response'       => $response,
        ];
    }

    /**
     * Upload and register media assets for LinkedIn Posts API.
     */
    private function prepareMediaContent(string $accessToken, string $orgId, array $mediaFiles): array
    {
        $firstMedia = $mediaFiles[0] ?? null;
        if (!$firstMedia) {
            return [];
        }

        $mediaType = $firstMedia['media_type'] ?? 'image';

        if ($mediaType === 'document') {
            // Document / PDF upload
            $documentUrn = $this->uploadDocument($accessToken, $orgId, $firstMedia['file_path'], $firstMedia['title'] ?? 'Document');
            return [
                'media' => [
                    'id'    => $documentUrn,
                    'title' => $firstMedia['title'] ?? 'TalentHub Portfolio Document',
                ],
            ];
        }

        if ($mediaType === 'video') {
            // Video upload
            $videoUrn = $this->uploadVideo($accessToken, $orgId, $firstMedia['file_path'], $firstMedia['title'] ?? 'Video');
            return [
                'media' => [
                    'id'    => $videoUrn,
                    'title' => $firstMedia['title'] ?? 'TalentHub Video',
                ],
            ];
        }

        // Image / Multi-image upload
        $imageUrns = [];
        foreach (array_slice($mediaFiles, 0, 9) as $img) {
            $imageUrn = $this->uploadImage($accessToken, $orgId, $img['file_path'], $img['alt_text'] ?? null);
            if ($imageUrn) {
                $imageUrns[] = [
                    'id'      => $imageUrn,
                    'altText' => $img['alt_text'] ?? 'TalentHub Image',
                ];
            }
        }

        if (count($imageUrns) === 1) {
            return [
                'media' => $imageUrns[0],
            ];
        }

        if (count($imageUrns) > 1) {
            return [
                'multiImage' => [
                    'images' => $imageUrns,
                ],
            ];
        }

        return [];
    }

    /**
     * Upload an image to LinkedIn Assets API.
     */
    public function uploadImage(string $accessToken, string $orgId, string $filePath, ?string $altText = null): string
    {
        if ($this->mockAccessToken !== null) {
            return 'urn:li:image:mock_img_' . bin2hex(random_bytes(6));
        }

        $initUrl = self::API_BASE_URL . '/rest/images?action=initializeUpload';
        $initPayload = [
            'initializeUploadRequest' => [
                'owner' => "urn:li:organization:{$orgId}",
            ],
        ];

        $headers = [
            'Authorization'             => "Bearer {$accessToken}",
            'LinkedIn-Version'          => '202401',
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
        ];

        $initRes = $this->httpPost($initUrl, $initPayload, $headers, true);
        $uploadUrl = $initRes['value']['uploadUrl'] ?? null;
        $imageUrn  = $initRes['value']['image'] ?? null;

        if (!$uploadUrl || !$imageUrn) {
            throw new \RuntimeException('LinkedIn image upload initialization failed: ' . json_encode($initRes));
        }

        // Upload binary stream
        $fileData = file_get_contents($filePath);
        $this->httpPutBinary($uploadUrl, $fileData, [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => mime_content_type($filePath) ?: 'image/jpeg',
        ]);

        return $imageUrn;
    }

    /**
     * Upload a PDF document to LinkedIn.
     */
    public function uploadDocument(string $accessToken, string $orgId, string $filePath, string $title): string
    {
        if ($this->mockAccessToken !== null) {
            return 'urn:li:document:mock_doc_' . bin2hex(random_bytes(6));
        }

        $initUrl = self::API_BASE_URL . '/rest/documents?action=initializeUpload';
        $initPayload = [
            'initializeUploadRequest' => [
                'owner' => "urn:li:organization:{$orgId}",
            ],
        ];

        $headers = [
            'Authorization'             => "Bearer {$accessToken}",
            'LinkedIn-Version'          => '202401',
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
        ];

        $initRes = $this->httpPost($initUrl, $initPayload, $headers, true);
        $uploadUrl   = $initRes['value']['uploadUrl'] ?? null;
        $documentUrn = $initRes['value']['document'] ?? null;

        if (!$uploadUrl || !$documentUrn) {
            throw new \RuntimeException('LinkedIn document upload initialization failed: ' . json_encode($initRes));
        }

        $fileData = file_get_contents($filePath);
        $this->httpPutBinary($uploadUrl, $fileData, [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/pdf',
        ]);

        return $documentUrn;
    }

    /**
     * Upload a video to LinkedIn.
     */
    public function uploadVideo(string $accessToken, string $orgId, string $filePath, string $title): string
    {
        if ($this->mockAccessToken !== null) {
            return 'urn:li:video:mock_vid_' . bin2hex(random_bytes(6));
        }

        $initUrl = self::API_BASE_URL . '/rest/videos?action=initializeUpload';
        $fileSize = filesize($filePath);
        $initPayload = [
            'initializeUploadRequest' => [
                'owner'     => "urn:li:organization:{$orgId}",
                'fileSizeBytes' => $fileSize,
                'uploadCaptions' => false,
                'uploadThumbnail' => false,
            ],
        ];

        $headers = [
            'Authorization'             => "Bearer {$accessToken}",
            'LinkedIn-Version'          => '202401',
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type'              => 'application/json',
        ];

        $initRes = $this->httpPost($initUrl, $initPayload, $headers, true);
        $uploadInstructions = $initRes['value']['uploadInstructions'] ?? [];
        $videoUrn           = $initRes['value']['video'] ?? null;

        if (empty($uploadInstructions) || !$videoUrn) {
            throw new \RuntimeException('LinkedIn video upload initialization failed: ' . json_encode($initRes));
        }

        $uploadUrl = $uploadInstructions[0]['uploadUrl'] ?? null;
        if (!$uploadUrl) {
            throw new \RuntimeException('Missing uploadUrl in LinkedIn video instructions.');
        }

        $fileData = file_get_contents($filePath);
        $this->httpPutBinary($uploadUrl, $fileData, [
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type'  => 'application/octet-stream',
        ]);

        return $videoUrn;
    }

    /**
     * Fetch Organization Share Statistics (Verified API Metrics).
     */
    public function fetchAnalytics(int $accountId, ?string $externalPostId = null): array
    {
        $account = $this->db->table('social_accounts')->where('id', $accountId)->get()->getRowArray();
        if (!$account) {
            throw new \RuntimeException('Account not found.');
        }

        if ($this->mockAccessToken !== null) {
            return [
                'impressions'     => 1450,
                'reach'           => 1120,
                'likes'           => 64,
                'comments'        => 12,
                'shares'          => 8,
                'clicks'          => 45,
                'engagement_rate' => 5.86,
                'source'          => 'api',
            ];
        }

        $accessToken = $this->getAccountAccessToken($accountId);
        $orgId = $account['external_id'];

        $url = self::API_BASE_URL . "/rest/organizationalEntityShareStatistics?q=organizationalEntity&organizationalEntity=urn:li:organization:{$orgId}";
        if ($externalPostId) {
            $url .= "&shares=List({$externalPostId})";
        }

        $headers = [
            'Authorization'             => "Bearer {$accessToken}",
            'LinkedIn-Version'          => '202401',
            'X-Restli-Protocol-Version' => '2.0.0',
        ];

        $res = $this->httpGet($url, $headers);
        $elements = $res['elements'] ?? [];
        if (empty($elements)) {
            return [
                'impressions'     => 0,
                'reach'           => 0,
                'likes'           => 0,
                'comments'        => 0,
                'shares'          => 0,
                'clicks'          => 0,
                'engagement_rate' => 0.0,
                'source'          => 'api',
            ];
        }

        $first = $elements[0]['totalShareStatistics'] ?? [];
        $impressions = (int) ($first['impressionCount'] ?? 0);
        $reach       = (int) ($first['uniqueImpressionsCount'] ?? $impressions);
        $likes       = (int) ($first['likeCount'] ?? 0);
        $comments    = (int) ($first['commentCount'] ?? 0);
        $shares      = (int) ($first['shareCount'] ?? 0);
        $clicks      = (int) ($first['clickCount'] ?? 0);

        $engRate = $impressions > 0 ? round((($likes + $comments + $shares + $clicks) / $impressions) * 100, 3) : 0.0;

        return [
            'impressions'     => $impressions,
            'reach'           => $reach,
            'likes'           => $likes,
            'comments'        => $comments,
            'shares'          => $shares,
            'clicks'          => $clicks,
            'engagement_rate' => $engRate,
            'source'          => 'api',
            'raw_metrics'     => $first,
        ];
    }

    /**
     * Check if an error code or message is retryable.
     */
    public function isRetryableError(int $httpCode, ?string $errorMessage = null): bool
    {
        // 429: Too Many Requests / Rate Limit
        // 500, 502, 503, 504: Transient server errors
        if (in_array($httpCode, [429, 500, 502, 503, 504], true)) {
            return true;
        }

        if ($errorMessage && (
            str_contains(strtolower($errorMessage), 'timeout') ||
            str_contains(strtolower($errorMessage), 'temporarily unavailable') ||
            str_contains(strtolower($errorMessage), 'rate limit')
        )) {
            return true;
        }

        return false;
    }

    /**
     * Internal HTTP GET helper using cURL.
     */
    private function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = "{$k}: {$v}";
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode((string) $raw, true);
        return is_array($json) ? $json : ['raw' => $raw, 'http_code' => $httpCode];
    }

    /**
     * Internal HTTP POST helper using cURL.
     */
    private function httpPost(string $url, array $data, array $headers = [], bool $isJson = false): array
    {
        $ch = curl_init($url);
        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = "{$k}: {$v}";
        }

        $payload = $isJson ? json_encode($data) : http_build_query($data);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerStr = substr((string) $response, 0, $headerSize);
        $bodyStr   = substr((string) $response, $headerSize);
        curl_close($ch);

        $json = json_decode($bodyStr, true);
        $result = is_array($json) ? $json : ['raw_body' => $bodyStr];
        $result['http_code'] = $httpCode;

        // Parse headers for x-restli-id
        if (preg_match('/x-restli-id:\s*([^\r\n]+)/i', $headerStr, $m)) {
            $result['x-restli-id'] = trim($m[1]);
        }

        return $result;
    }

    /**
     * Internal HTTP PUT Binary helper using cURL.
     */
    private function httpPutBinary(string $url, string $binaryData, array $headers = []): void
    {
        $ch = curl_init($url);
        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = "{$k}: {$v}";
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $binaryData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("Binary upload to {$url} failed with HTTP {$httpCode}: {$res}");
        }
    }
}

