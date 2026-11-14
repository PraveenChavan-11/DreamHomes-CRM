<?php

namespace App\Services\Social;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Enterprise Social Analytics & Reporting Service
 *
 * Aggregates verified performance telemetry from official APIs and local tracking,
 * generates KPI summaries, publishing velocity trends, campaign attribution,
 * and handles CSV / executive report generation.
 *
 * STRICT COMPLIANCE: Never fabricates metrics; clearly labels API vs local metrics.
 */
class SocialAnalyticsService
{
    private BaseConnection $db;
    private LinkedInApiService $linkedIn;
    private InstagramApiService $instagram;

    public function __construct(
        ?BaseConnection $db = null,
        ?LinkedInApiService $linkedIn = null,
        ?InstagramApiService $instagram = null
    ) {
        $this->db        = $db ?? Database::connect();
        $this->linkedIn  = $linkedIn ?? new LinkedInApiService($this->db);
        $this->instagram = $instagram ?? new InstagramApiService($this->db);
    }

    /**
     * Get Executive Overview KPIs for a selected date filter and platform filter.
     */
    public function getOverviewKpis(string $dateRange = '30_days', string $platformFilter = 'all'): array
    {
        $startDate = $this->calculateStartDate($dateRange);

        // 1. Post counts
        $postBuilder = $this->db->table('social_posts');
        if ($startDate) {
            $postBuilder->where('created_at >=', $startDate);
        }
        if ($platformFilter !== 'all') {
            $postBuilder->groupStart()
                ->where('primary_platform', $platformFilter)
                ->orWhere('primary_platform', 'both')
            ->groupEnd();
        }

        $allPosts = $postBuilder->get()->getResultArray();

        $totalPublished = 0;
        $totalScheduled = 0;
        $totalDrafts    = 0;
        $totalFailed    = 0;

        foreach ($allPosts as $p) {
            if ($p['status'] === 'published') $totalPublished++;
            elseif ($p['status'] === 'scheduled') $totalScheduled++;
            elseif ($p['status'] === 'draft') $totalDrafts++;
            elseif ($p['status'] === 'failed') $totalFailed++;
        }

        // Platform breakdown
        $liCount = $this->db->table('social_post_platforms')
            ->where('platform', 'linkedin')
            ->where('status', 'published')
            ->countAllResults();

        $igCount = $this->db->table('social_post_platforms')
            ->where('platform', 'instagram')
            ->where('status', 'published')
            ->countAllResults();

        // 2. Aggregated Analytics Snapshots
        $snapBuilder = $this->db->table('social_analytics_snapshots');
        if ($startDate) {
            $snapBuilder->where('snapshot_date >=', substr($startDate, 0, 10));
        }
        if ($platformFilter !== 'all') {
            $snapBuilder->where('platform', $platformFilter);
        }

        $snapRow = $snapBuilder->select('
            SUM(impressions) as total_impressions,
            SUM(reach) as total_reach,
            SUM(reactions + likes) as total_likes,
            SUM(comments) as total_comments,
            SUM(shares) as total_shares,
            SUM(clicks) as total_clicks
        ')->get()->getRowArray();

        $totalImpressions = (int) ($snapRow['total_impressions'] ?? 0);
        $totalReach       = (int) ($snapRow['total_reach'] ?? 0);
        $totalLikes       = (int) ($snapRow['total_likes'] ?? 0);
        $totalComments    = (int) ($snapRow['total_comments'] ?? 0);
        $totalShares      = (int) ($snapRow['total_shares'] ?? 0);
        $totalClicks      = (int) ($snapRow['total_clicks'] ?? 0);

        // Standard engagement rate formula: (Likes + Comments + Shares + Clicks) / Impressions * 100
        $engagementRate = 0.0;
        if ($totalImpressions > 0) {
            $engagementRate = round((($totalLikes + $totalComments + $totalShares + $totalClicks) / $totalImpressions) * 100, 2);
        }

        // 3. Social Enquiries
        $enqBuilder = $this->db->table('social_enquiries');
        if ($startDate) {
            $enqBuilder->where('created_at >=', $startDate);
        }
        if ($platformFilter !== 'all') {
            $enqBuilder->where('source_platform', $platformFilter);
        }

        $totalEnquiries = $enqBuilder->countAllResults(false);
        $newEnquiries   = (clone $enqBuilder)->where('lead_status', 'new')->countAllResults();
        $convertedLeads = (clone $enqBuilder)->where('lead_status', 'converted')->countAllResults();

        return [
            'total_published'    => $totalPublished,
            'linkedin_published' => $liCount,
            'instagram_published'=> $igCount,
            'scheduled_posts'    => $totalScheduled,
            'pending_drafts'     => $totalDrafts,
            'failed_posts'       => $totalFailed,
            'total_impressions'  => $totalImpressions,
            'total_reach'        => $totalReach,
            'total_clicks'       => $totalClicks,
            'engagement_rate'    => $engagementRate,
            'total_enquiries'    => $totalEnquiries,
            'new_enquiries'      => $newEnquiries,
            'converted_leads'    => $convertedLeads,
            'date_range'         => $dateRange,
            'platform_filter'    => $platformFilter,
            'last_synced_at'     => $this->getLastSyncTime(),
        ];
    }

    /**
     * Get publishing trend chart data (grouped by date).
     */
    public function getPublishingTrends(int $days = 30): array
    {
        $dates = [];
        $linkedinCounts = [];
        $instagramCounts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = date('M d', strtotime($date));

            $li = $this->db->table('social_post_platforms')
                ->where('platform', 'linkedin')
                ->where('status', 'published')
                ->where('DATE(published_at)', $date)
                ->countAllResults();

            $ig = $this->db->table('social_post_platforms')
                ->where('platform', 'instagram')
                ->where('status', 'published')
                ->where('DATE(published_at)', $date)
                ->countAllResults();

            $linkedinCounts[]  = $li;
            $instagramCounts[] = $ig;
        }

        return [
            'labels'    => $dates,
            'linkedin'  => $linkedinCounts,
            'instagram' => $instagramCounts,
        ];
    }

    /**
     * Synchronize analytics snapshot for published posts from official APIs.
     */
    public function syncPostAnalytics(int $postId): array
    {
        $platforms = $this->db->table('social_post_platforms')
            ->where('post_id', $postId)
            ->where('status', 'published')
            ->where('external_post_id IS NOT NULL')
            ->get()
            ->getResultArray();

        $synced = [];
        $today  = date('Y-m-d');

        foreach ($platforms as $pp) {
            $platform   = $pp['platform'];
            $externalId = $pp['external_post_id'];
            $accountId  = (int) $pp['account_id'];

            try {
                $metrics = [];
                if ($platform === 'linkedin') {
                    $metrics = $this->linkedIn->fetchAnalytics($accountId, $externalId);
                } elseif ($platform === 'instagram') {
                    $metrics = $this->instagram->fetchAnalytics($accountId, $externalId);
                }

                if (!empty($metrics)) {
                    $record = [
                        'platform'         => $platform,
                        'account_id'       => $accountId,
                        'post_id'          => $postId,
                        'external_post_id' => $externalId,
                        'snapshot_date'    => $today,
                        'impressions'      => $metrics['impressions'] ?? 0,
                        'reach'            => $metrics['reach'] ?? 0,
                        'reactions'        => $metrics['reactions'] ?? 0,
                        'likes'            => $metrics['likes'] ?? 0,
                        'comments'         => $metrics['comments'] ?? 0,
                        'shares'           => $metrics['shares'] ?? 0,
                        'video_views'      => $metrics['video_views'] ?? 0,
                        'clicks'           => $metrics['clicks'] ?? 0,
                        'engagement_rate'  => $metrics['engagement_rate'] ?? 0.000,
                        'source'           => $metrics['source'] ?? 'api',
                        'raw_metrics_json' => json_encode($metrics['raw_metrics'] ?? []),
                        'created_at'       => date('Y-m-d H:i:s'),
                    ];

                    $existing = $this->db->table('social_analytics_snapshots')
                        ->where('platform', $platform)
                        ->where('external_post_id', $externalId)
                        ->where('snapshot_date', $today)
                        ->get()
                        ->getRowArray();

                    if ($existing) {
                        $this->db->table('social_analytics_snapshots')
                            ->where('id', $existing['id'])
                            ->update($record);
                    } else {
                        $this->db->table('social_analytics_snapshots')->insert($record);
                    }

                    $synced[$platform] = $record;
                }
            } catch (\Throwable $e) {
                log_message('error', "Analytics sync failed for post {$postId} on {$platform}: " . $e->getMessage());
            }
        }

        return $synced;
    }

    /**
     * Export social enquiries to CSV string.
     */
    public function exportEnquiriesCsv(array $filters = []): string
    {
        $builder = $this->db->table('social_enquiries')->orderBy('created_at', 'DESC');
        if (!empty($filters['source_platform'])) {
            $builder->where('source_platform', $filters['source_platform']);
        }
        if (!empty($filters['lead_status'])) {
            $builder->where('lead_status', $filters['lead_status']);
        }

        $enquiries = $builder->get()->getResultArray();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Contact Name', 'Email', 'Phone', 'Company', 'Enquiry Type', 'Platform', 'Status', 'Priority', 'Follow-up Date', 'Created Date']);

        foreach ($enquiries as $row) {
            fputcsv($output, [
                $row['id'],
                $row['contact_name'],
                $row['email'],
                $row['phone'],
                $row['company'],
                $row['enquiry_type'],
                $row['source_platform'],
                $row['lead_status'],
                $row['priority'],
                $row['follow_up_date'],
                $row['created_at'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return (string) $csv;
    }

    private function calculateStartDate(string $range): ?string
    {
        switch ($range) {
            case 'today':
                return date('Y-m-d 00:00:00');
            case '7_days':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case '30_days':
                return date('Y-m-d 00:00:00', strtotime('-30 days'));
            case '90_days':
                return date('Y-m-d 00:00:00', strtotime('-90 days'));
            case 'all':
            default:
                return null;
        }
    }

    private function getLastSyncTime(): ?string
    {
        $row = $this->db->table('social_accounts')
            ->select('MAX(last_synced_at) as last_sync')
            ->get()
            ->getRowArray();

        return $row['last_sync'] ?? null;
    }
}

