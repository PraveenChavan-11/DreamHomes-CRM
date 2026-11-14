<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Validates/allow-lists a WorkSphere event and upserts the matching CRM lead (contract section 5).
 * Must be called inside the caller's DB transaction.
 */
class WorksphereLeadMapper
{
    public const EVENT_TYPES = [
        'registration_initiated', 'email_verified', 'provisioning_succeeded', 'provisioning_failed', 'demo_activated',
        'demo_expiry_approaching', 'demo_expired', 'converted_to_paid', 'consultation_requested',
    ];

    private const LABELS = [
        'registration_initiated' => 'Registration initiated', 'email_verified' => 'Email verified',
        'provisioning_succeeded' => 'Provisioning succeeded', 'provisioning_failed' => 'Provisioning failed',
        'demo_activated' => 'Demo activated', 'demo_expiry_approaching' => 'Demo expiry approaching',
        'demo_expired' => 'Demo expired', 'converted_to_paid' => 'Converted to paid', 'consultation_requested' => 'Consultation requested',
    ];

    /** Pipeline order used so automated events only ever move a lead forward. */
    private const RANK = ['new' => 0, 'contacted' => 1, 'requirement_gathering' => 2, 'qualified' => 2, 'demo_scheduled' => 3,
        'demo_active' => 4, 'proposal_sent' => 5, 'negotiation' => 6];

    private const NOTIFY = [
        'registration_initiated' => 'ws_registration_initiated', 'email_verified' => 'ws_email_verified',
        // provisioning_succeeded deliberately has no e-mail: WorkSphere emits demo_activated right after it, one alert per demo.
        'demo_activated' => 'ws_demo_activated',
        'provisioning_failed' => 'ws_provisioning_failed', 'demo_expiry_approaching' => 'ws_demo_expiring',
        'converted_to_paid' => 'ws_converted', 'consultation_requested' => 'product_enquiry',
    ];

    private BaseConnection $db;
    private ItLeadService $leads;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db    = $db ?? \Config\Database::connect();
        $this->leads = new ItLeadService($this->db);
    }

    /**
     * Allow-list + normalise. Unknown keys are dropped; anything invalid returns null (-> 422 INVALID_EVENT).
     * Never copies password/token/secret-like fields because only listed keys are read.
     */
    public static function sanitize(array $b): ?array
    {
        $str = static function ($v, int $max): ?string {
            if ($v === null) {
                return '';
            }
            if (!is_string($v) && !is_int($v)) {
                return null;
            }
            $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', (string) $v) ?? '');
            return mb_substr($v, 0, $max);
        };
        $date = static function ($v): ?string {
            if ($v === null || $v === '') {
                return '';
            }
            if (!is_string($v) || strlen($v) > 40 || ($t = strtotime($v)) === false) {
                return null;
            }
            return date('Y-m-d H:i:s', $t);
        };

        if (($b['schema_version'] ?? null) !== 1 || !in_array($b['event_type'] ?? null, self::EVENT_TYPES, true)
            || !is_string($b['event_id'] ?? null) || !IntegrationSignatureService::validEventId($b['event_id'])
            || ($b['source'] ?? 'worksphere') !== 'worksphere'
            || !is_string($b['correlation_id'] ?? null) || !preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $b['correlation_id'])) {
            return null;
        }
        $occurred = $date($b['occurred_at'] ?? null);
        $lead = is_array($b['lead'] ?? null) ? $b['lead'] : [];
        $demo = is_array($b['demo'] ?? null) ? $b['demo'] : [];
        $ctx  = is_array($b['context'] ?? null) ? $b['context'] : [];
        if ($occurred === null || $occurred === '' ) {
            return null;
        }

        $email = strtolower(trim((string) ($lead['email'] ?? '')));
        if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $out = ['schema_version' => 1, 'event_id' => strtolower($b['event_id']), 'event_type' => $b['event_type'], 'occurred_at' => $occurred,
            'source' => 'worksphere', 'correlation_id' => $b['correlation_id'], 'lead' => ['email' => $email], 'demo' => [], 'context' => []];

        foreach (['organization_name' => 150, 'contact_name' => 150, 'employee_size' => 40, 'industry' => 80, 'organization_type' => 60,
                  'country' => 60, 'state' => 60, 'city' => 60] as $k => $max) {
            $v = $str($lead[$k] ?? null, $max);
            if ($v === null) {
                return null;
            }
            $out['lead'][$k] = $v;
        }
        $phone = $str($lead['phone'] ?? null, 30);
        if ($phone === null || ($phone !== '' && !preg_match('/^[0-9+()\-. ]{5,30}$/', $phone))) {
            return null;
        }
        $out['lead']['phone'] = $phone;

        foreach (['status' => 30, 'plan' => 40] as $k => $max) {
            $v = $str($demo[$k] ?? null, $max);
            if ($v === null) {
                return null;
            }
            $out['demo'][$k] = $v;
        }
        foreach (['started_at', 'expires_at'] as $k) {
            $v = $date($demo[$k] ?? null);
            if ($v === null) {
                return null;
            }
            $out['demo'][$k] = $v;
        }

        foreach (['message' => 1000, 'interest' => 100, 'failed_stage' => 60] as $k => $max) {
            $v = $str($ctx[$k] ?? null, $max);
            if ($v === null) {
                return null;
            }
            $out['context'][$k] = $v;
        }
        $dr = $ctx['days_remaining'] ?? null;
        if ($dr !== null && (!is_int($dr) || $dr < 0 || $dr > 3650)) {
            return null;
        }
        $out['context']['days_remaining'] = $dr;
        $utm = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $k) {
            $v = is_array($ctx['utm'] ?? null) ? $str($ctx['utm'][$k] ?? null, 120) : '';
            if ($v === null) {
                return null;
            }
            if ($v !== '') {
                $utm[$k] = $v;
            }
        }
        $out['context']['utm'] = $utm;
        return $out;
    }

    /** @return array{lead_id:int,created:bool,notify:?string} */
    public function apply(array $e): array
    {
        $type  = $e['event_type'];
        $lead  = $e['lead'];
        $demo  = $e['demo'];
        $ctx   = $e['context'];
        $now   = date('Y-m-d H:i:s');
        $corr  = $e['correlation_id'];
        $email = $lead['email'];

        $row = $this->db->table('it_leads')->where('external_ref', $corr)->where('deleted_at IS NULL')->get()->getRowArray();
        $newCorrelation = false;
        if (!$row) {
            $row = $this->db->table('it_leads')->where('LOWER(email)', $email)->where('product_slug', 'worksphere')
                ->where('deleted_at IS NULL')->whereNotIn('status', ItLeadService::CLOSED)->orderBy('id', 'ASC')->get(1)->getRowArray();
            if ($row) {
                $seen = $this->db->table('crm_integration_events')->where('correlation_id', $corr)->where('event_id !=', $e['event_id'])
                    ->where('lead_id', $row['id'])->countAllResults();
                $newCorrelation = $seen === 0 && ($row['external_ref'] ?? null) !== $corr;
            }
        }

        $source = $type === 'consultation_requested' ? 'worksphere_enquiry' : 'worksphere_demo';
        $label  = self::LABELS[$type];
        $demoBits = array_filter([
            $demo['status'] !== '' ? 'demo ' . $demo['status'] : '', $demo['plan'] !== '' ? 'plan ' . $demo['plan'] : '',
            $demo['expires_at'] !== '' ? 'expires ' . $demo['expires_at'] : '',
            $ctx['days_remaining'] !== null ? $ctx['days_remaining'] . ' day(s) left' : '',
            $ctx['failed_stage'] !== '' ? 'failed stage: ' . $ctx['failed_stage'] : '',
        ]);
        $note = 'WorkSphere event: ' . $label . ($demoBits ? ' (' . implode(', ', $demoBits) . ')' : '') . ' [ref ' . substr($corr, 0, 8) . ']';

        if (!$row) {
            $desc = 'WorkSphere ' . ($type === 'consultation_requested' ? 'consultation request' : 'demo registration')
                . array_reduce([['Interest', $ctx['interest']], ['Employees', $lead['employee_size']], ['Industry', $lead['industry']], ['Organisation type', $lead['organization_type']], ['Message', $ctx['message']]],
                    static fn ($c, $p) => $p[1] !== '' ? $c . "\n" . $p[0] . ': ' . $p[1] : $c, '');
            $loc = implode(', ', array_filter([$lead['city'], $lead['state'], $lead['country']]));
            $utm = $ctx['utm'];
            $status = in_array($type, ['provisioning_succeeded', 'demo_activated'], true) ? 'demo_active' : ($type === 'converted_to_paid' ? 'won' : 'new');
            $this->db->table('it_leads')->insert([
                'client_name' => $lead['contact_name'] !== '' ? $lead['contact_name'] : ($lead['organization_name'] !== '' ? $lead['organization_name'] : strstr($email, '@', true)),
                'company' => $lead['organization_name'] ?: null, 'email' => $email, 'phone' => $lead['phone'], 'location' => $loc ?: null,
                'service_category' => 'erp-hrms', 'project_description' => $desc, 'consent_at' => $now, 'consent_privacy_at' => $now,
                'status' => $status, 'source_system' => 'worksphere', 'source' => $source, 'product_slug' => 'worksphere',
                'external_ref' => $corr, 'priority' => $type === 'provisioning_failed' ? 'high' : 'normal',
                'last_activity_at' => $now, 'verified_email_at' => $type === 'email_verified' ? $now : null,
                'demo_status' => $demo['status'] ?: null, 'demo_started_at' => $demo['started_at'] ?: null, 'demo_expires_at' => $demo['expires_at'] ?: null,
                'utm_source' => $utm['utm_source'] ?? null, 'utm_medium' => $utm['utm_medium'] ?? null, 'utm_campaign' => $utm['utm_campaign'] ?? null,
                'utm_term' => $utm['utm_term'] ?? null, 'utm_content' => $utm['utm_content'] ?? null,
                'idempotency_key' => 'ws_' . md5($email . '|' . $corr), 'created_at' => $now, 'updated_at' => $now,
            ]);
            $id = (int) $this->db->insertID();
            $this->leads->activity($id, 'created', 'Lead created from WorkSphere (' . $label . ')');
            $this->leads->activity($id, 'integration', $note);
            return ['lead_id' => $id, 'created' => true, 'notify' => self::NOTIFY[$type] ?? null];
        }

        // ── existing lead: timeline + selective field updates; never regress staff progress ──
        $id  = (int) $row['id'];
        $set = ['updated_at' => $now, 'last_activity_at' => $now];
        if ($newCorrelation) {
            $set['enquiry_count'] = (int) $row['enquiry_count'] + 1;
            $note .= ' - additional registration for an existing open lead';
        }
        if ($type === 'email_verified' && empty($row['verified_email_at'])) {
            $set['verified_email_at'] = $now;
        }
        if ($demo['status'] !== '') {
            $set['demo_status'] = $demo['status'];
        }
        if ($type === 'demo_expired') {
            $set['demo_status'] = 'expired';
        }
        if ($demo['started_at'] !== '') {
            $set['demo_started_at'] = $demo['started_at'];
        }
        if ($demo['expires_at'] !== '') {
            $set['demo_expires_at'] = $demo['expires_at'];
        }
        if (empty($row['phone']) && $lead['phone'] !== '') {
            $set['phone'] = $lead['phone'];
        }
        if (empty($row['company']) && $lead['organization_name'] !== '') {
            $set['company'] = $lead['organization_name'];
        }
        if ($type === 'provisioning_failed' && !in_array($row['priority'] ?? 'normal', ['high', 'urgent'], true)) {
            $set['priority'] = 'high';
        }
        if ($type === 'demo_expiry_approaching' && (empty($row['next_follow_up_at']) || $row['next_follow_up_at'] > date('Y-m-d'))) {
            $set['next_follow_up_at'] = date('Y-m-d');
        }
        if (($row['source'] ?? '') === 'project_enquiry' || empty($row['source'])) {
            $set['source'] = $source;
        }
        if (empty($row['external_ref'])) {
            $set['external_ref'] = $corr;
        }

        $target = match ($type) {
            'provisioning_succeeded', 'demo_activated' => 'demo_active',
            'converted_to_paid' => 'won',
            default => null,
        };
        $cur = $row['status'];
        if ($target === 'won' && $cur !== 'spam' && $cur !== 'won') {
            $set['status'] = 'won';
        } elseif ($target && isset(self::RANK[$cur]) && self::RANK[$target] > self::RANK[$cur]) {
            $set['status'] = $target; // forward only; on_hold/lost/spam/staff-advanced leads are left alone
        }
        if (isset($set['status'])) {
            $note .= ' - status ' . (ItLeadService::STATUSES[$cur] ?? $cur) . ' -> ' . ItLeadService::STATUSES[$set['status']];
            if (empty($row['first_contacted_at'])) {
                $set['first_contacted_at'] = null; // automated moves are not human contact
                unset($set['first_contacted_at']);
            }
        }

        $this->db->table('it_leads')->where('id', $id)->update($set);
        $this->leads->activity($id, 'integration', $note);
        return ['lead_id' => $id, 'created' => false, 'notify' => self::NOTIFY[$type] ?? null];
    }
}
