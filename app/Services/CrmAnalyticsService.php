<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Real KPIs for the admin CRM dashboard, computed from it_leads / crm_integration_events / email_outbox.
 * Every query is a bounded aggregate over indexed columns (created_at, status, source, product_slug, next_follow_up_at).
 */
class CrmAnalyticsService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        new ItLeadService($this->db); // ensures CRM schema
    }

    /** @return array{from:string,to:string,range:string} normalised, bounded to 366 days */
    public static function resolveRange(string $range, string $from = '', string $to = ''): array
    {
        $today = date('Y-m-d');
        if ($range === 'custom') {
            $f = \DateTime::createFromFormat('Y-m-d', $from);
            $t = \DateTime::createFromFormat('Y-m-d', $to);
            if ($f && $t && $f->format('Y-m-d') === $from && $t->format('Y-m-d') === $to && $from <= $to) {
                if ((strtotime($to) - strtotime($from)) / 86400 > 365) {
                    $from = date('Y-m-d', strtotime($to . ' -365 days'));
                }
                return ['from' => $from, 'to' => $to, 'range' => 'custom'];
            }
            $range = '30';
        }
        $days = in_array($range, ['7', '30', '90'], true) ? (int) $range : 30;
        return ['from' => date('Y-m-d', strtotime("-" . ($days - 1) . " days")), 'to' => $today, 'range' => (string) $days];
    }

    private function base(array $r, array $f): \CodeIgniter\Database\BaseBuilder
    {
        $b = $this->db->table('it_leads')->where('deleted_at IS NULL')
            ->where('created_at >=', $r['from'] . ' 00:00:00')->where('created_at <=', $r['to'] . ' 23:59:59');
        if (!empty($f['product']) && preg_match('/^[a-z0-9\-]{1,120}$/', $f['product'])) {
            $b->where('product_slug', $f['product']);
        }
        if (!empty($f['source']) && preg_match('/^[a-z_]{1,40}$/', $f['source'])) {
            $b->where('source', $f['source']);
        }
        return $b;
    }

    private function groupCount(string $col, array $r, array $f, int $limit = 12): array
    {
        $rows = $this->base($r, $f)->select("COALESCE(NULLIF({$col},''),'(none)') AS k, COUNT(*) AS n", false)->groupBy('k')->orderBy('n', 'DESC')->limit($limit)->get()->getResultArray();
        $out = [];
        foreach ($rows as $x) {
            $out[$x['k']] = (int) $x['n'];
        }
        return $out;
    }

    public function compute(array $r, array $f = []): array
    {
        $db = $this->db;
        $today = date('Y-m-d');
        $open = fn () => $this->base($r, $f)->whereNotIn('status', ItLeadService::CLOSED);
        // "Right now" figures ignore the date range but still honour product/source filters.
        $now = function () use ($f, $db) {
            $b = $db->table('it_leads')->where('deleted_at IS NULL')->whereNotIn('status', ItLeadService::CLOSED);
            if (!empty($f['product']) && preg_match('/^[a-z0-9\-]{1,120}$/', $f['product'])) {
                $b->where('product_slug', $f['product']);
            }
            if (!empty($f['source']) && preg_match('/^[a-z_]{1,40}$/', $f['source'])) {
                $b->where('source', $f['source']);
            }
            return $b;
        };

        $total = $this->base($r, $f)->countAllResults();
        $won   = $this->base($r, $f)->where('status', 'won')->countAllResults();
        $lost  = $this->base($r, $f)->where('status', 'lost')->countAllResults();

        $recent = fn (int $days) => $db->table('it_leads')->where('deleted_at IS NULL')->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days')))->countAllResults();

        // WorkSphere funnel (leads created in range, source_system = worksphere)
        $ws = fn () => $this->base($r, $f)->where('source_system', 'worksphere');
        $failedProv = $db->table('crm_integration_events')->where('event_type', 'provisioning_failed')->where('result', 'accepted')
            ->where('received_at >=', $r['from'] . ' 00:00:00')->where('received_at <=', $r['to'] . ' 23:59:59')->countAllResults();

        $avg = $this->base($r, $f)->select('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_contacted_at)) AS m', false)->where('first_contacted_at IS NOT NULL')->get()->getRowArray();
        $pipe = $now()->select('COALESCE(SUM(estimated_value),0) AS v, COUNT(estimated_value) AS n', false)->get()->getRowArray();

        $trendRows = $this->base($r, $f)->select('DATE(created_at) AS d, COUNT(*) AS n', false)->groupBy('d')->orderBy('d')->get()->getResultArray();
        $byDay = array_column($trendRows, 'n', 'd');
        $trend = ['labels' => [], 'values' => []];
        for ($t = strtotime($r['from']); $t <= strtotime($r['to']); $t += 86400) {
            $d = date('Y-m-d', $t);
            $trend['labels'][] = date('d M', $t);
            $trend['values'][] = (int) ($byDay[$d] ?? 0);
        }

        $since24 = date('Y-m-d H:i:s', time() - 86400);
        $lastEvt = $db->query("SELECT MAX(received_at) AS t FROM crm_integration_events WHERE result IN ('accepted','duplicate')")->getRow();
        $health = [
            'last_event'      => $lastEvt->t ?? null,
            'failures_24h'    => $db->table('crm_integration_events')->where('result', 'failed')->where('received_at >=', $since24)->countAllResults(),
            'clients_active'  => $db->table('crm_integration_clients')->where('is_active', 1)->countAllResults(),
            'email_failed'    => $db->table('email_outbox')->like('template_code', 'crm_', 'after')->where('status', 'FAILED')->countAllResults(),
            'email_pending'   => $db->table('email_outbox')->like('template_code', 'crm_', 'after')->whereIn('status', ['PENDING', 'PROCESSING'])->countAllResults(),
        ];

        return [
            'range'        => $r,
            'filters'      => $f,
            'total'        => $total,
            'new_today'    => $recent(1), 'new_7d' => $recent(7), 'new_30d' => $recent(30),
            'by_status'    => $this->groupCount('status', $r, $f),
            'by_source'    => $this->groupCount('source', $r, $f),
            'by_product'   => $this->groupCount('product_slug', $r, $f),
            'by_category'  => $this->groupCount('service_category', $r, $f),
            'funnel'       => [
                'initiated' => $ws()->countAllResults(),
                'verified'  => $ws()->where('verified_email_at IS NOT NULL')->countAllResults(),
                'activated' => $ws()->where('demo_started_at IS NOT NULL')->countAllResults(),
                'converted' => $ws()->where('status', 'won')->countAllResults(),
                'failed_provisioning' => $failedProv,
            ],
            'won' => $won, 'lost' => $lost,
            'conversion_rate' => ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : null,
            'overdue_followups' => $now()->where('next_follow_up_at <', $today)->countAllResults(),
            'unassigned'   => $now()->where('assigned_to IS NULL')->countAllResults(),
            'avg_first_contact_hours' => ($avg['m'] ?? null) !== null ? round(((float) $avg['m']) / 60, 1) : null,
            'pipeline_value' => (float) $pipe['v'], 'pipeline_valued_leads' => (int) $pipe['n'],
            'trend'        => $trend,
            'health'       => $health,
        ];
    }
}
