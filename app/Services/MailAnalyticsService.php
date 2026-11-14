<?php

namespace App\Services;

use App\Models\MailboxConnectionModel;
use App\Models\MailMessageIndexModel;
use App\Models\EmailOutboxModel;

class MailAnalyticsService
{
    protected MailboxConnectionModel $boxModel;
    protected MailMessageIndexModel $msgIndex;

    public function __construct()
    {
        $this->boxModel = new MailboxConnectionModel();
        $this->msgIndex = new MailMessageIndexModel();
    }

    /**
     * Compute comprehensive communication metrics for dashboard KPI cards
     */
    public function getDashboardMetrics(): array
    {
        $todayStart = date('Y-m-d 00:00:00');

        $totalMailboxes = $this->boxModel->countAllResults();
        $totalInbox     = $this->msgIndex->where('folder', 'INBOX')->countAllResults();
        $unreadInbox    = $this->msgIndex->where('folder', 'INBOX')->where('is_read', 0)->countAllResults();

        $receivedToday  = $this->msgIndex->where('folder', 'INBOX')
                                        ->where('message_date >=', $todayStart)
                                        ->countAllResults();

        $sentToday      = $this->msgIndex->where('folder', 'SENT')
                                        ->where('message_date >=', $todayStart)
                                        ->countAllResults();

        $candidateInquiries = $this->msgIndex->where('category', 'candidate_app')->countAllResults();
        $employerInquiries  = $this->msgIndex->where('category', 'employer_req')->countAllResults();
        $pendingFollowups   = $this->msgIndex->where('is_flagged', 1)->where('folder', 'INBOX')->countAllResults();

        // Compute aggregate storage usage
        $mailboxes = $this->boxModel->findAll();
        $usedStorage = 0;
        $totalStorage = 0;
        foreach ($mailboxes as $mb) {
            $usedStorage += (int) ($mb['quota_used_bytes'] ?? 0);
            $totalStorage += (int) ($mb['quota_total_bytes'] ?? 10737418240);
        }

        // Daily activity chart (Last 7 days)
        $dailyActivity = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = $day . ' 00:00:00';
            $dayEnd = $day . ' 23:59:59';

            $in = $this->msgIndex->where('folder', 'INBOX')
                                 ->where('message_date >=', $dayStart)
                                 ->where('message_date <=', $dayEnd)
                                 ->countAllResults();

            $out = $this->msgIndex->where('folder', 'SENT')
                                  ->where('message_date >=', $dayStart)
                                  ->where('message_date <=', $dayEnd)
                                  ->countAllResults();

            $dailyActivity[] = [
                'date'     => date('M d', strtotime($day)),
                'incoming' => $in,
                'outgoing' => $out,
            ];
        }

        return [
            'total_mailboxes'      => $totalMailboxes,
            'total_inbox'          => $totalInbox,
            'unread_inbox'         => $unreadInbox,
            'received_today'       => $receivedToday,
            'sent_today'           => $sentToday,
            'candidate_inquiries'  => $candidateInquiries,
            'employer_inquiries'   => $employerInquiries,
            'pending_followups'    => $pendingFollowups,
            'storage'              => [
                'used_bytes'      => $usedStorage,
                'total_bytes'     => $totalStorage > 0 ? $totalStorage : 10737418240,
                'used_mb'         => round($usedStorage / (1024 * 1024), 1),
                'total_gb'        => round($totalStorage / (1024 * 1024 * 1024), 1),
                'percent'         => $totalStorage > 0 ? round(($usedStorage / $totalStorage) * 100, 1) : 0,
            ],
            'daily_activity'       => $dailyActivity,
        ];
    }
}

