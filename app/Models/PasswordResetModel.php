<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table            = 'password_resets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'token_hash',
        'ip_address',
        'user_agent',
        'expires_at',
        'used_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function createToken(string $email): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $rawToken);
        $request = service('request');

        $this->insert([
            'email'      => $email,
            'token_hash' => $hash,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => substr($request->getUserAgent()->getAgentString() ?? '', 0, 255),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600), // 60 mins
        ]);

        return $rawToken;
    }

    public function verifyAndUse(string $rawToken): ?string
    {
        $hash = hash('sha256', $rawToken);
        $record = $this->where('token_hash', $hash)
                       ->where('used_at IS NULL')
                       ->where('expires_at >=', date('Y-m-d H:i:s'))
                       ->first();

        if (!$record) {
            return null;
        }

        $this->update($record['id'], ['used_at' => date('Y-m-d H:i:s')]);
        return $record['email'];
    }
}

