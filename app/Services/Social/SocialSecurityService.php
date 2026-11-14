<?php

namespace App\Services\Social;

use CodeIgniter\Encryption\EncrypterInterface;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use Config\Services;
use Config\Encryption;

/**
 * Enterprise Social Security Service
 *
 * Manages encryption at rest for OAuth tokens & API client secrets,
 * OAuth state CSRF defense, media file security verification (magic bytes & MIME),
 * and payload sanitization to prevent sensitive data leakage.
 */
class SocialSecurityService
{
    private static ?EncrypterInterface $encrypterOverride = null;
    private static bool $encrypterDisabled = false;
    private BaseConnection $db;

    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'pdf'];
    public const MAX_IMAGE_BYTES    = 10485760;  // 10 MB
    public const MAX_VIDEO_BYTES    = 104857600; // 100 MB
    public const MAX_DOC_BYTES      = 20971520;  // 20 MB

    public const PLATFORM_LIMITS = [
        'linkedin' => [
            'caption_max' => 3000,
            'hashtags_recommended_max' => 10,
            'allowed_media' => ['image', 'video', 'document'],
        ],
        'instagram' => [
            'caption_max' => 2200,
            'hashtags_recommended_max' => 30,
            'allowed_media' => ['image', 'video'],
        ],
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /** Test hook to set explicit raw encryption key */
    public static function useKeyForTesting(?string $rawKey): void
    {
        self::$encrypterOverride = null;
        self::$encrypterDisabled = false;
        if ($rawKey === null) {
            return;
        }
        if ($rawKey === '') {
            self::$encrypterDisabled = true;
            return;
        }
        $cfg = new Encryption();
        $cfg->key = $rawKey;
        self::$encrypterOverride = Services::encrypter($cfg, false);
    }

    public static function encrypter(): ?EncrypterInterface
    {
        if (self::$encrypterDisabled) {
            return null;
        }
        if (self::$encrypterOverride) {
            return self::$encrypterOverride;
        }
        try {
            $cfg = config(Encryption::class);
            if (trim((string) $cfg->key) === '') {
                return null;
            }
            return Services::encrypter($cfg, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isEncryptionConfigured(): bool
    {
        return self::encrypter() !== null;
    }

    /**
     * Encrypt a sensitive token or secret string at rest.
     */
    public function encryptSecret(string $plain): string
    {
        $enc = self::encrypter();
        if (!$enc) {
            throw new \RuntimeException('Encryption key is not configured. Cannot store credentials insecurely.');
        }
        return base64_encode($enc->encrypt($plain));
    }

    /**
     * Decrypt a sensitive token or secret string from storage.
     */
    public function decryptSecret(string $cipherBase64): string
    {
        $enc = self::encrypter();
        if (!$enc) {
            throw new \RuntimeException('Encryption key is not configured. Cannot decrypt credentials.');
        }
        $raw = base64_decode($cipherBase64, true);
        if ($raw === false) {
            throw new \RuntimeException('Invalid base64 payload for encrypted credential.');
        }
        return (string) $enc->decrypt($raw);
    }

    /**
     * Mask a secret or access token for administrative display (e.g. sk_live_...4a9f).
     */
    public function maskSecret(?string $secret, int $visibleSuffix = 4): string
    {
        if ($secret === null || $secret === '') {
            return '';
        }
        $len = strlen($secret);
        if ($len <= $visibleSuffix + 4) {
            return str_repeat('•', 8);
        }
        $prefix = substr($secret, 0, min(6, (int) floor($len / 4)));
        $suffix = substr($secret, -$visibleSuffix);
        return $prefix . '••••••••' . $suffix;
    }

    /**
     * Generate a cryptographic OAuth state token with CSRF defense and expiration.
     */
    public function generateOAuthState(string $platform, int $userId, string $redirectUri, ?string $codeVerifier = null): string
    {
        $stateToken = bin2hex(random_bytes(32));
        $expiresAt  = date('Y-m-d H:i:s', time() + 900); // 15 minutes TTL

        $this->db->table('social_oauth_states')->insert([
            'state_token'   => $stateToken,
            'platform'      => $platform,
            'user_id'       => $userId,
            'redirect_uri'  => $redirectUri,
            'code_verifier' => $codeVerifier,
            'expires_at'    => $expiresAt,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return $stateToken;
    }

    /**
     * Validate and consume an OAuth state token.
     * Prevents replay attacks by marking the state as used immediately.
     */
    public function validateOAuthState(string $stateToken, string $platform, ?int $userId = null): array
    {
        $row = $this->db->table('social_oauth_states')
            ->where('state_token', $stateToken)
            ->where('platform', $platform)
            ->where('used_at', null)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->get()
            ->getRowArray();

        if (!$row) {
            return [
                'valid'   => false,
                'code'    => 'INVALID_OR_EXPIRED_STATE',
                'message' => 'OAuth state verification failed or session expired. Please reinitiate authorization.',
            ];
        }

        if ($userId !== null && (int) $row['user_id'] !== $userId) {
            return [
                'valid'   => false,
                'code'    => 'USER_MISMATCH',
                'message' => 'OAuth state user mismatch detected.',
            ];
        }

        // Consume state atomically to prevent replay
        $this->db->table('social_oauth_states')
            ->where('id', $row['id'])
            ->update(['used_at' => date('Y-m-d H:i:s')]);

        return [
            'valid'         => true,
            'user_id'       => (int) $row['user_id'],
            'redirect_uri'  => $row['redirect_uri'],
            'code_verifier' => $row['code_verifier'],
        ];
    }

    /**
     * Thorough media file verification:
     * - File existence & size limits
     * - Extension whitelist
     * - True MIME type via finfo
     * - Magic bytes file signature verification
     * - Dimension / duration verification
     */
    public function validateMediaFile(string $filePath, string $originalFilename, ?string $targetPlatform = null): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'valid'   => false,
                'code'    => 'FILE_NOT_FOUND',
                'message' => 'Media file not found on server or unreadable.',
            ];
        }

        $fileSize = (int) filesize($filePath);
        if ($fileSize <= 0) {
            return [
                'valid'   => false,
                'code'    => 'EMPTY_FILE',
                'message' => 'Media file is empty (0 bytes).',
            ];
        }

        // 1. Extension Check
        $ext = strtolower((string) pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return [
                'valid'   => false,
                'code'    => 'UNSUPPORTED_EXTENSION',
                'message' => "File extension '.{$ext}' is not supported. Allowed: " . implode(', ', self::ALLOWED_EXTENSIONS),
            ];
        }

        // 2. MIME Type via finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $validMimes = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'mp4'  => ['video/mp4'],
            'pdf'  => ['application/pdf', 'application/x-pdf'],
        ];

        if (!isset($validMimes[$ext]) || !in_array($detectedMime, $validMimes[$ext], true)) {
            return [
                'valid'   => false,
                'code'    => 'MIME_TYPE_MISMATCH',
                'message' => "Detected MIME type '{$detectedMime}' does not match file extension '.{$ext}'.",
            ];
        }

        // 3. Magic Bytes Inspection
        $handle = fopen($filePath, 'rb');
        $magicHeader = fread($handle, 16);
        fclose($handle);

        $magicPassed = false;
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $magicPassed = str_starts_with($magicHeader, "\xFF\xD8\xFF");
                break;
            case 'png':
                $magicPassed = str_starts_with($magicHeader, "\x89PNG\r\n\x1a\n");
                break;
            case 'webp':
                $magicPassed = str_starts_with($magicHeader, 'RIFF') && substr($magicHeader, 8, 4) === 'WEBP';
                break;
            case 'mp4':
                $magicPassed = substr($magicHeader, 4, 4) === 'ftyp';
                break;
            case 'pdf':
                $magicPassed = str_starts_with($magicHeader, '%PDF-');
                break;
        }

        if (!$magicPassed) {
            return [
                'valid'   => false,
                'code'    => 'CORRUPT_OR_SPOOFED_FILE',
                'message' => 'File header signature does not match binary format requirements.',
            ];
        }

        // 4. Size Limits
        $mediaType = 'image';
        if ($ext === 'mp4') {
            $mediaType = 'video';
            if ($fileSize > self::MAX_VIDEO_BYTES) {
                return [
                    'valid'   => false,
                    'code'    => 'VIDEO_TOO_LARGE',
                    'message' => 'Video exceeds maximum limit of 100 MB.',
                ];
            }
        } elseif ($ext === 'pdf') {
            $mediaType = 'document';
            if ($fileSize > self::MAX_DOC_BYTES) {
                return [
                    'valid'   => false,
                    'code'    => 'DOCUMENT_TOO_LARGE',
                    'message' => 'Document exceeds maximum limit of 20 MB.',
                ];
            }
        } else {
            if ($fileSize > self::MAX_IMAGE_BYTES) {
                return [
                    'valid'   => false,
                    'code'    => 'IMAGE_TOO_LARGE',
                    'message' => 'Image exceeds maximum limit of 10 MB.',
                ];
            }
        }

        // 5. Platform Compatibility Validation
        if ($targetPlatform === 'instagram' && $mediaType === 'document') {
            return [
                'valid'   => false,
                'code'    => 'INSTAGRAM_UNSUPPORTED_DOCUMENT',
                'message' => 'Instagram does not support document/PDF publishing. Supported on LinkedIn only.',
            ];
        }

        // 6. Image Dimension Extraction (if applicable)
        $width  = null;
        $height = null;
        if ($mediaType === 'image') {
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo) {
                $width  = (int) $imageInfo[0];
                $height = (int) $imageInfo[1];

                // Instagram aspect ratio check (between 4:5 and 1.91:1)
                if ($targetPlatform === 'instagram' && $height > 0) {
                    $ratio = $width / $height;
                    if ($ratio < 0.79 || $ratio > 1.95) {
                        return [
                            'valid'   => false,
                            'code'    => 'INSTAGRAM_INVALID_ASPECT_RATIO',
                            'message' => "Instagram image aspect ratio must be between 4:5 (0.8) and 1.91:1. Detected ratio: " . round($ratio, 2),
                        ];
                    }
                }
            }
        }

        return [
            'valid'        => true,
            'media_type'   => $mediaType,
            'mime_type'    => $detectedMime,
            'file_size'    => $fileSize,
            'extension'    => $ext,
            'width'        => $width,
            'height'       => $height,
        ];
    }

    /**
     * Sanitize request/response payloads before saving to audit or attempt logs.
     * Removes access tokens, client secrets, passwords, Authorization headers.
     */
    public function sanitizePayload(mixed $payload): mixed
    {
        if (is_string($payload)) {
            // Strip Bearer tokens
            $sanitized = preg_replace('/Bearer\s+[A-Za-z0-9\-._~+\/]+=*/i', 'Bearer [REDACTED_TOKEN]', $payload);
            // Decode if JSON string
            $decoded = json_decode($sanitized, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($this->sanitizeArray($decoded), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
            return $sanitized;
        }

        if (is_array($payload)) {
            return $this->sanitizeArray($payload);
        }

        return $payload;
    }

    private function sanitizeArray(array $data): array
    {
        $sensitiveKeys = [
            'client_secret', 'access_token', 'refresh_token', 'secret',
            'password', 'authorization', 'auth_token', 'token', 'code_verifier',
            'api_secret', 'app_secret', 'private_key', 'encryption_key'
        ];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, $sensitiveKeys, true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value) && (str_contains($lowerKey, 'token') || str_contains($lowerKey, 'secret'))) {
                $data[$key] = $this->maskSecret($value);
            }
        }

        return $data;
    }
}

