<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailOutboxModel extends Model
{
    protected $table            = 'email_outbox';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'template_code',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_html',
        'body_text',
        'status',
        'attempts',
        'last_error',
        'idempotency_key',
        'scheduled_at',
        'sent_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Enqueue an email with idempotency protection
     */
    public function enqueue(
        string $recipientEmail,
        string $recipientName,
        string $subject,
        string $bodyHtml,
        string $bodyText = '',
        ?string $templateCode = null,
        ?string $idempotencyKey = null
    ): ?int {
        if ($idempotencyKey) {
            $existing = $this->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return (int) $existing['id'];
            }
        }

        return (int) $this->insert([
            'template_code'   => $templateCode,
            'recipient_email' => $recipientEmail,
            'recipient_name'  => $recipientName,
            'subject'         => $subject,
            'body_html'       => $bodyHtml,
            'body_text'       => $bodyText,
            'status'          => 'PENDING',
            'attempts'        => 0,
            'idempotency_key' => $idempotencyKey,
            'scheduled_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Atomically claim a batch of pending emails to prevent duplicate sends across concurrent workers
     */
    public function claimBatch(int $limit = 15): array
    {
        $db = \Config\Database::connect();

        // 1. Reset stale PROCESSING rows older than 15 minutes
        $db->query("
            UPDATE email_outbox 
            SET status = 'PENDING' 
            WHERE status = 'PROCESSING' 
              AND created_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");

        // 2. Select candidates
        $candidates = $db->table($this->table)
            ->select('id')
            ->where('status', 'PENDING')
            ->where('attempts <', 4)
            ->where('scheduled_at <=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $claimed = [];
        foreach ($candidates as $row) {
            $id = (int) $row['id'];
            // Atomic conditional update
            $db->query("
                UPDATE email_outbox 
                SET status = 'PROCESSING' 
                WHERE id = ? AND status = 'PENDING'
            ", [$id]);

            if ($db->affectedRows() > 0) {
                $item = $this->find($id);
                if ($item) {
                    $claimed[] = $item;
                }
            }
        }

        return $claimed;
    }

    /**
     * Fetch pending items ready for sending
     */
    public function getPendingBatch(int $limit = 25): array
    {
        return $this->where('status', 'PENDING')
                    ->where('attempts <', 4)
                    ->where('scheduled_at <=', date('Y-m-d H:i:s'))
                    ->orderBy('id', 'ASC')
                    ->findAll($limit);
    }
}

