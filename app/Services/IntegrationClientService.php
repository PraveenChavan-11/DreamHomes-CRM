<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Encryption\EncrypterInterface;

/**
 * Machine identities (key id + secret) for server-to-server integrations such as WorkSphere.
 * Secrets are encrypted at rest with the CI4 encrypter (requires `encryption.key`) and returned in clear
 * exactly once, from create()/rotate(). Without an encryption key every operation fails closed.
 */
class IntegrationClientService
{
    /** Previous secret stays valid this long after a rotation so the sender can roll over without downtime. */
    public const ROTATION_GRACE_SECONDS = 86400;

    private static ?EncrypterInterface $override = null;
    private static bool $disabled = false;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        if (!$this->db->tableExists('crm_integration_clients')) {
            require_once APPPATH . 'Database/Migrations/20261108000002_crm_integration.php';
            \App\Database\Migrations\CrmIntegration::apply($this->db);
        }
    }

    /** Test hook: use an explicit raw key ('' = behave as if no encryption key were configured). */
    public static function useKeyForTesting(?string $rawKey): void
    {
        self::$override = null;
        self::$disabled = false;
        if ($rawKey === null) {
            return;
        }
        if ($rawKey === '') {
            self::$disabled = true;
            return;
        }
        $cfg = new \Config\Encryption();
        $cfg->key = $rawKey;
        self::$override = \Config\Services::encrypter($cfg, false);
    }

    public static function encrypter(): ?EncrypterInterface
    {
        if (self::$disabled) {
            return null;
        }
        if (self::$override) {
            return self::$override;
        }
        try {
            $cfg = config(\Config\Encryption::class);
            if (trim((string) $cfg->key) === '') {
                return null;
            }
            return \Config\Services::encrypter($cfg, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function isConfigured(): bool
    {
        return self::encrypter() !== null;
    }

    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function validKeyId(string $keyId): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_\-]{2,62}$/', $keyId);
    }

    /** @return array{key_id:string,secret:string,created:bool} secret is shown once. */
    public function createOrRotate(string $keyId, string $label = ''): array
    {
        $enc = self::encrypter();
        if (!$enc) {
            throw new \RuntimeException('encryption.key is not configured; refusing to store integration secrets.');
        }
        if (!self::validKeyId($keyId)) {
            throw new \InvalidArgumentException('Key id must be 3-63 chars: lowercase letters, digits, - or _.');
        }
        $secret = self::generateSecret();
        $now    = date('Y-m-d H:i:s');
        $row    = $this->db->table('crm_integration_clients')->where('key_id', $keyId)->get()->getRowArray();
        if ($row) {
            $this->db->table('crm_integration_clients')->where('id', $row['id'])->update([
                'secret_prev_encrypted' => $row['secret_encrypted'],
                'prev_expires_at'       => date('Y-m-d H:i:s', time() + self::ROTATION_GRACE_SECONDS),
                'secret_encrypted'      => base64_encode($enc->encrypt($secret)),
                'rotated_at'            => $now,
                'is_active'             => 1,
            ]);
            return ['key_id' => $keyId, 'secret' => $secret, 'created' => false];
        }
        $this->db->table('crm_integration_clients')->insert([
            'key_id' => $keyId, 'label' => mb_substr($label !== '' ? $label : $keyId, 0, 120),
            'secret_encrypted' => base64_encode($enc->encrypt($secret)), 'is_active' => 1, 'created_at' => $now,
        ]);
        return ['key_id' => $keyId, 'secret' => $secret, 'created' => true];
    }

    public function find(string $keyId): ?array
    {
        return $this->db->table('crm_integration_clients')->where('key_id', $keyId)->get()->getRowArray() ?: null;
    }

    /** Decrypts the currently valid secrets (current first, then the previous one while in its grace window). */
    public function secretsFor(array $client): array
    {
        $enc = self::encrypter();
        if (!$enc) {
            return [];
        }
        $out = [];
        try {
            $out[] = $enc->decrypt(base64_decode((string) $client['secret_encrypted'], true) ?: '');
            if (!empty($client['secret_prev_encrypted']) && !empty($client['prev_expires_at']) && strtotime($client['prev_expires_at']) > time()) {
                $out[] = $enc->decrypt(base64_decode((string) $client['secret_prev_encrypted'], true) ?: '');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Integration secret decrypt failed for a client (wrong encryption.key?).');
            return [];
        }
        return $out;
    }

    public function setActive(string $keyId, bool $active): bool
    {
        $this->db->table('crm_integration_clients')->where('key_id', $keyId)->update(['is_active' => $active ? 1 : 0]);
        return $this->db->affectedRows() > 0;
    }

    public function touch(string $keyId): void
    {
        $this->db->table('crm_integration_clients')->where('key_id', $keyId)->update(['last_used_at' => date('Y-m-d H:i:s')]);
    }

    /** Safe listing for the admin UI: never includes secret columns. */
    public function listClients(): array
    {
        return $this->db->table('crm_integration_clients')
            ->select('id,key_id,label,is_active,last_used_at,created_at,rotated_at,prev_expires_at')->orderBy('id')->get()->getResultArray();
    }
}
