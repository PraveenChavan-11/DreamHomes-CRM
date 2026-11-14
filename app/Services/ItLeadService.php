<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * IT Services CRM domain logic: create, dedupe, status/assignment/notes/follow-ups, all with an activity trail.
 */
class ItLeadService
{
    public const STATUSES = [
        'new'                   => 'New',
        'contacted'             => 'Contacted',
        'requirement_gathering' => 'Requirement Gathering', // legacy: kept so older rows stay valid
        'qualified'             => 'Qualified',
        'demo_scheduled'        => 'Demo Scheduled',
        'demo_active'           => 'Demo Active',
        'proposal_sent'         => 'Proposal Sent',
        'negotiation'           => 'Negotiation',
        'on_hold'               => 'On Hold',
        'won'                   => 'Closed Won',
        'lost'                  => 'Closed Lost',
        'spam'                  => 'Spam',
    ];

    /** Statuses that end a lead's life (excluded from open pipeline, follow-ups, duplicate matching). */
    public const CLOSED = ['won', 'lost', 'spam'];

    public const PRIORITIES = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];

    public const CATEGORIES = [
        'website-development'    => 'Website Development',
        'mobile-application'     => 'Mobile Application',
        'software-development'   => 'Software Development',
        'erp-hrms'               => 'ERP / HRMS',
        'api-development'        => 'API Development',
        'qa-testing'             => 'QA / Testing',
        'digital-transformation' => 'Digital Transformation',
        'seo-digital-marketing'  => 'SEO / Digital Marketing',
        'it-consulting'          => 'IT Consulting',
        'other'                  => 'Other',
    ];

    public const BUDGETS   = ['Under ₹1 lakh', '₹1–5 lakh', '₹5–15 lakh', '₹15 lakh+', 'Not sure yet'];
    public const TIMELINES = ['Immediately', '1–3 months', '3–6 months', 'Flexible'];
    public const CONTACTS  = ['email', 'phone', 'whatsapp'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        static $ensured = false;
        if (!$ensured) {
            try {
                if (!$this->db->tableExists('it_leads') || !$this->db->fieldExists('source_system', 'it_leads')
                    || !$this->db->tableExists('crm_integration_events')) {
                    require_once APPPATH . 'Database/Migrations/20261108000002_crm_integration.php';
                    \App\Database\Migrations\CrmIntegration::apply($this->db);
                }
            } catch (\Throwable $e) {
                log_message('error', 'CRM schema ensure failed: ' . $e->getMessage());
            }
            $ensured = true;
        }
    }

    /** Insert a lead (idempotent). Returns ['id'=>int,'duplicate_of'=>?int,'idempotent'=>bool]. */
    public function create(array $d): array
    {
        $idem = 'itl_' . md5(strtolower($d['email']) . '|' . $d['service_category'] . '|' . substr($d['project_description'], 0, 40) . '|' . date('YmdH'));
        $existing = $this->db->table('it_leads')->select('id')->where('idempotency_key', $idem)->get()->getRowArray();
        if ($existing) {
            return ['id' => (int) $existing['id'], 'duplicate_of' => null, 'idempotent' => true];
        }

        // Duplicate detection: same email or phone, still open, in the last 60 days.
        $dup = $this->db->table('it_leads')->select('id')
            ->where('deleted_at IS NULL')
            ->whereNotIn('status', self::CLOSED)
            ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-60 days')))
            ->groupStart()->where('email', $d['email'])->orWhere('phone', $d['phone'])->groupEnd()
            ->orderBy('id', 'ASC')->get(1)->getRowArray();

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->table('it_leads')->insert([
            'client_name'         => $d['client_name'],
            'company'             => $d['company'] ?: null,
            'email'               => $d['email'],
            'phone'               => $d['phone'],
            'location'            => $d['location'] ?: null,
            'service_category'    => $d['service_category'],
            'service_slug'        => $d['service_slug'] ?: null,
            'project_description' => $d['project_description'],
            'budget_range'        => $d['budget_range'] ?: null,
            'timeline'            => $d['timeline'] ?: null,
            'preferred_contact'   => $d['preferred_contact'] ?: null,
            'source_page'         => $d['source_page'] ?: null,
            'utm_source'          => $d['utm_source'] ?? null,
            'utm_medium'          => $d['utm_medium'] ?? null,
            'utm_campaign'        => $d['utm_campaign'] ?? null,
            'utm_term'            => $d['utm_term'] ?? null,
            'utm_content'         => $d['utm_content'] ?? null,
            'consent_at'          => $now,
            'ip_address'          => $d['ip_address'] ?? null,
            'status'              => 'new',
            'source_system'       => $d['source_system'] ?? 'website',
            'source'              => $d['source'] ?? 'project_enquiry',
            'product_slug'        => $d['product_slug'] ?? null,
            'last_activity_at'    => $now,
            'consent_privacy_at'  => $now,
            'duplicate_of'        => $dup['id'] ?? null,
            'idempotency_key'     => $idem,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);
        $id = (int) $this->db->insertID();
        $this->activity($id, 'created', 'Lead received via website' . (!empty($dup['id']) ? " (possible duplicate of #{$dup['id']})" : ''));
        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Could not save lead');
        }
        return ['id' => $id, 'duplicate_of' => isset($dup['id']) ? (int) $dup['id'] : null, 'idempotent' => false];
    }

    public function activity(int $leadId, string $type, ?string $body, ?int $adminId = null): void
    {
        $this->db->table('it_lead_activities')->insert([
            'lead_id' => $leadId, 'admin_user_id' => $adminId, 'type' => $type,
            'body' => $body, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function touch(int $id, array $set): void
    {
        $set['updated_at'] = date('Y-m-d H:i:s');
        $set['last_activity_at'] = $set['updated_at'];
        $this->db->table('it_leads')->where('id', $id)->where('deleted_at IS NULL')->update($set);
    }

    public function find(int $id): ?array
    {
        return $this->db->table('it_leads')->where('id', $id)->where('deleted_at IS NULL')->get()->getRowArray() ?: null;
    }

    /** Closed Lost requires a reason; the first move away from "new" stamps first_contacted_at (response-time KPI). */
    public function changeStatus(int $id, string $status, int $adminId, ?string $lostReason = null): bool
    {
        $lead = $this->find($id);
        if (!$lead || !isset(self::STATUSES[$status])) {
            return false;
        }
        $lostReason = $lostReason !== null ? trim(mb_substr($lostReason, 0, 255)) : null;
        if ($status === 'lost' && ($lostReason === null || $lostReason === '')) {
            return false;
        }
        if ($lead['status'] === $status) {
            return true;
        }
        $set = ['status' => $status];
        if ($status === 'lost') {
            $set['lost_reason'] = $lostReason;
        }
        if (empty($lead['first_contacted_at']) && $status !== 'new') {
            $set['first_contacted_at'] = date('Y-m-d H:i:s');
        }
        $this->touch($id, $set);
        $this->activity($id, 'status_change', self::STATUSES[$lead['status']] . ' → ' . self::STATUSES[$status]
            . ($status === 'lost' ? " (reason: {$lostReason})" : ''), $adminId);
        return true;
    }

    public function setPriority(int $id, string $priority, int $adminId): bool
    {
        if (!isset(self::PRIORITIES[$priority]) || !($lead = $this->find($id))) {
            return false;
        }
        if (($lead['priority'] ?? 'normal') !== $priority) {
            $this->touch($id, ['priority' => $priority]);
            $this->activity($id, 'update', 'Priority: ' . ($lead['priority'] ?? 'normal') . " → {$priority}", $adminId);
        }
        return true;
    }

    public function setEstimatedValue(int $id, ?string $value, int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $value = trim(str_replace([',', ' '], '', (string) $value));
        if ($value === '') {
            $dec = null;
        } elseif (preg_match('/^\d{1,10}(\.\d{1,2})?$/', $value)) {
            $dec = $value;
        } else {
            return false;
        }
        $this->touch($id, ['estimated_value' => $dec]);
        $this->activity($id, 'update', $dec === null ? 'Estimated value cleared' : "Estimated value set to {$dec}", $adminId);
        return true;
    }

    /** Free labels: lowercase letters/digits/hyphen/space, max 10 per lead, 30 chars each. */
    public function setTags(int $id, string $csv, int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $tags = [];
        foreach (explode(',', $csv) as $t) {
            $t = trim(preg_replace('/[^a-z0-9\- ]/', '', strtolower($t)));
            if ($t !== '') {
                $tags[mb_substr($t, 0, 30)] = true;
            }
        }
        $tags = array_slice(array_keys($tags), 0, 10);
        $this->touch($id, ['tags' => $tags ? implode(',', $tags) : null]);
        $this->activity($id, 'update', $tags ? 'Tags: ' . implode(', ', $tags) : 'Tags cleared', $adminId);
        return true;
    }

    public function assign(int $id, ?int $userId, int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $name = 'Unassigned';
        if ($userId) {
            $u = $this->db->table('admin_users')->select('first_name,last_name,username')->where('id', $userId)->where('is_active', 1)->get()->getRowArray();
            if (!$u) {
                return false;
            }
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['username'];
        }
        $this->touch($id, ['assigned_to' => $userId ?: null]);
        $this->activity($id, 'assignment', "Assigned to: {$name}", $adminId);
        return true;
    }

    public function addNote(int $id, string $note, int $adminId): bool
    {
        $note = trim($note);
        if ($note === '' || !$this->find($id)) {
            return false;
        }
        $this->activity($id, 'note', mb_substr($note, 0, 4000), $adminId);
        return true;
    }

    public function setFollowUp(int $id, ?string $date, int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        if ($date !== null && $date !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dt || $dt->format('Y-m-d') !== $date) {
                return false;
            }
        } else {
            $date = null;
        }
        $this->touch($id, ['next_follow_up_at' => $date]);
        $this->activity($id, 'follow_up', $date ? "Follow-up set for {$date}" : 'Follow-up cleared', $adminId);
        return true;
    }

    public function softDelete(int $id, int $adminId): bool
    {
        if (!$this->find($id)) {
            return false;
        }
        $this->db->table('it_leads')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        $this->activity($id, 'system', 'Lead archived (soft-deleted)', $adminId);
        return true;
    }

    /** Dashboard counters from real rows. */
    public function counts(): array
    {
        $b = fn () => $this->db->table('it_leads')->where('deleted_at IS NULL');
        return [
            'total'         => $b()->countAllResults(),
            'new'           => $b()->where('status', 'new')->countAllResults(),
            'open'          => $b()->whereNotIn('status', self::CLOSED)->countAllResults(),
            'won'           => $b()->where('status', 'won')->countAllResults(),
            'followups_due' => $b()->whereNotIn('status', self::CLOSED)->where('next_follow_up_at <=', date('Y-m-d'))->countAllResults(),
        ];
    }
}
