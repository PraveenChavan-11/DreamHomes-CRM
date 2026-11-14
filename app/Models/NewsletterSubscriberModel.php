<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsletterSubscriberModel extends Model
{
    protected $table            = 'newsletter_subscribers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'email',
        'first_name',
        'status',
        'verification_token_hash',
        'verification_expires_at',
        'unsubscribe_token_hash',
        'consent_given',
        'consent_timestamp',
        'consent_source',
        'verified_at',
        'unsubscribed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Find active subscriber by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', strtolower(trim($email)))->first();
    }

    /**
     * Find subscriber by verification token hash
     */
    public function findByVerificationTokenHash(string $hash): ?array
    {
        return $this->where('verification_token_hash', $hash)->first();
    }

    /**
     * Find subscriber by unsubscribe token hash
     */
    public function findByUnsubscribeTokenHash(string $hash): ?array
    {
        return $this->where('unsubscribe_token_hash', $hash)->first();
    }

    /**
     * Check if an email is suppressed (unsubscribed or bounced)
     */
    public function isSuppressed(string $email): bool
    {
        $subscriber = $this->findByEmail($email);
        if (!$subscriber) {
            return false;
        }
        return in_array($subscriber['status'], ['unsubscribed', 'bounced'], true);
    }

    /**
     * Return counts for dashboard telemetry
     */
    public function getTelemetryCounts(): array
    {
        $db = \Config\Database::connect();
        $tbl = $this->table;

        $total        = $db->table($tbl)->countAllResults();
        $verified     = $db->table($tbl)->where('status', 'subscribed')->countAllResults();
        $unverified   = $db->table($tbl)->where('status', 'unverified')->countAllResults();
        $unsubscribed = $db->table($tbl)->where('status', 'unsubscribed')->countAllResults();
        $newLast7Days = $db->table($tbl)->where('created_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();

        return [
            'total'          => $total,
            'verified'       => $verified,
            'unverified'     => $unverified,
            'unsubscribed'   => $unsubscribed,
            'new_last_7_days'=> $newLast7Days,
        ];
    }
}
