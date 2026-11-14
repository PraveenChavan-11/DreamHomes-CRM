<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'ip_address',
        'user_agent',
        'success',
        'attempted_at',
    ];

    protected $useTimestamps = false;

    public function record(string $email, bool $success): void
    {
        $request = service('request');
        $this->insert([
            'email'        => $email,
            'ip_address'   => $request->getIPAddress(),
            'user_agent'   => substr($request->getUserAgent()->getAgentString() ?? '', 0, 255),
            'success'      => $success ? 1 : 0,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function countRecentFailures(string $ip, int $minutes = 15): int
    {
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        return $this->where('ip_address', $ip)
                    ->where('success', 0)
                    ->where('attempted_at >=', $since)
                    ->countAllResults();
    }
}

