<?php
$path = 'app/Controllers/Admin/EngagementController.php';
$content = file_get_contents($path);

$target = "        return \$this->renderAdminView('admin/engagement/analytics/index', \$data, 'engagement_analytics');
    }";

$addition = "        return \$this->renderAdminView('admin/engagement/analytics/index', \$data, 'engagement_analytics');
    }

    /**
     * Executive Placement & Marketing Performance Report (GAP-09)
     * Provides printable B2B Executive Summary with KPI charts, candidate funnel, and telemetry.
     */
    public function executiveReport()
    {
        if (\$redirect = \$this->requireAnyPermission(['engagement.view', 'engagement.manage', 'reports.view'])) {
            return \$redirect;
        }

        \$appModel = new \App\Models\ApplicationModel();
        \$jobModel = new \App\Models\JobModel();
        \$candModel = new \App\Models\CandidateModel();

        \$data = \$this->getCommonData('engagement_analytics', 'TalentHub — Executive Placement & Operations Report');
        \$data['totalApplications'] = \$appModel->countAllResults();
        \$data['hiredCount'] = \$appModel->where('status', 'HIRED')->countAllResults();
        \$data['shortlistedCount'] = \$appModel->whereIn('status', ['SHORTLISTED', 'INTERVIEW', 'OFFER'])->countAllResults();
        \$data['activeJobsCount'] = \$jobModel->where('status', 'PUBLISHED')->countAllResults();
        \$data['totalCandidates'] = \$candModel->countAllResults();
        \$data['analytics'] = \$this->eventModel->getAnalyticsSummary();

        return view('admin/engagement/analytics/executive_report', \$data);
    }";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$addition_norm = str_replace("\r\n", "\n", $addition);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $addition_norm, $content_norm));
    echo "EngagementController executiveReport added successfully.\n";
} else {
    echo "FAILED: target not found in EngagementController.\n";
}

