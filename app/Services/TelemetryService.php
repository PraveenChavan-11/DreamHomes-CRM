<?php

namespace App\Services;

use App\Models\ApplicationModel;
use App\Models\EmployerEnquiryModel;
use App\Models\ContactEnquiryModel;
use App\Models\JobModel;
use App\Models\BlogPostModel;

class TelemetryService
{
    /**
     * Get real-time dashboard KPIs and telemetry metrics
     */
    public function getDashboardMetrics(): array
    {
        $db = \Config\Database::connect();
        $jobModel = new JobModel();
        $appModel = new ApplicationModel();
        $empEnqModel = new EmployerEnquiryModel();
        $contactEnqModel = new ContactEnquiryModel();
        $blogModel = new BlogPostModel();

        // 1. Job Statistics
        $totalJobs = $jobModel->countAllResults(false);
        $activeJobs = $jobModel->where('status', 'PUBLISHED')->where('deleted_at IS NULL')->countAllResults(false);
        $expiredJobs = $jobModel->where('status', 'CLOSED')->orWhere('status', 'EXPIRED')->countAllResults(false);

        // 2. Application Statistics
        $totalApps = (new ApplicationModel())->countAllResults();
        $todayApps = (new ApplicationModel())->where('applications.created_at >=', date('Y-m-d 00:00:00'))->countAllResults();
        $pendingApps = (new ApplicationModel())->whereIn('applications.status', ['APPLIED', 'NEW', 'SUBMITTED', 'applied', 'new'])->countAllResults();

        // 3. Enquiry Statistics
        $totalEmpEnq = (new EmployerEnquiryModel())->countAllResults();
        $totalContactEnq = (new ContactEnquiryModel())->countAllResults();
        $pendingEnquiries = (new EmployerEnquiryModel())->whereIn('status', ['NEW', 'new'])->countAllResults() +
                            (new ContactEnquiryModel())->whereIn('status', ['UNREAD', 'unread', 'NEW', 'new'])->countAllResults();

        // 4. Content Statistics
        $publishedBlogs = (new BlogPostModel())->where('is_published', 1)->countAllResults();

        // 5. Outbox Email Status
        $outboxStats = $db->table('email_outbox')
                          ->select('status, COUNT(*) as count')
                          ->groupBy('status')
                          ->get()
                          ->getResultArray();
        $emailCounts = ['SENT' => 0, 'PENDING' => 0, 'FAILED' => 0];
        foreach ($outboxStats as $row) {
            $emailCounts[$row['status']] = (int) $row['count'];
        }

        // 6. Security Metrics
        $todayLogins = $db->table('login_attempts')
                          ->where('attempted_at >=', date('Y-m-d 00:00:00'))
                          ->countAllResults();

        // 7. Recent Applications (Last 5)
        $recentApps = $appModel->getApplicationsWithDetails([], 5, 0);

        // 8. Recent Inquiries (Last 5)
        $recentEnquiries = $empEnqModel->orderBy('created_at', 'DESC')->findAll(5);

        return [
            'kpis' => [
                'total_jobs'          => $totalJobs,
                'active_jobs'         => $activeJobs,
                'expired_jobs'        => $expiredJobs,
                'total_applications'  => $totalApps,
                'today_applications'  => $todayApps,
                'pending_applications'=> $pendingApps,
                'total_enquiries'     => $totalEmpEnq + $totalContactEnq,
                'pending_enquiries'   => $pendingEnquiries,
                'published_blogs'     => $publishedBlogs,
                'today_logins'        => $todayLogins,
                'emails_sent'         => $emailCounts['SENT'],
                'emails_pending'      => $emailCounts['PENDING'],
                'emails_failed'       => $emailCounts['FAILED'],
            ],
            'recent_applications' => $recentApps,
            'recent_enquiries'    => $recentEnquiries,
        ];
    }
}
