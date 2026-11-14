<?php
$path = 'd:\\Personal\\TalentHub\\app\\Controllers\\Admin\\ApplicationsController.php';
$content = file_get_contents($path);

$target = '        if ($result[\'success\']) {
            session()->setFlashdata(\'success\', $result[\'message\']);
        } else {
            session()->setFlashdata(\'error\', $result[\'message\']);
        }

        return redirect()->to(base_url(\'admin/applications\'));
    }

    public function downloadResume($id)';

$replacement = '        if ($result[\'success\']) {
            session()->setFlashdata(\'success\', $result[\'message\']);
        } else {
            session()->setFlashdata(\'error\', $result[\'message\']);
        }

        return redirect()->to(base_url(\'admin/applications\'));
    }

    /**
     * Bulk Application Actions (Pipeline Stage Transition & Export)
     */
    public function bulkAction()
    {
        if ($redirect = $this->requirePermission(\'applications.transition\')) {
            return $redirect;
        }

        $action       = $this->request->getPost(\'bulk_action\');
        $selectedIds  = (array) ($this->request->getPost(\'selected_ids\') ?? []);
        $targetStatus = strtoupper(trim((string) ($this->request->getPost(\'target_status\') ?? \'\')));
        $notes        = trim((string) ($this->request->getPost(\'notes\') ?? \'Bulk updated by administrator.\'));
        $followUpDate = $this->request->getPost(\'follow_up_date\') ?: null;
        $adminUser    = session()->get(\'admin_user\');
        $adminUserId  = $adminUser[\'id\'] ?? null;

        $validIds = array_values(array_filter(array_map(\'intval\', $selectedIds)));

        if (empty($validIds)) {
            session()->setFlashdata(\'error\', \'No candidate applications were selected. Please select at least one record.\');
            return redirect()->to(base_url(\'admin/applications\'));
        }

        $appModel = new ApplicationModel();

        // 1. Bulk Export as CSV
        if ($action === \'export\') {
            $records = $appModel->whereIn(\'id\', $validIds)->findAll();
            $csvData = "\xEF\xBB\xBF";
            $csvData .= "Application ID,Candidate Name,Email,Phone,Applied Job ID,Status,Created At\n";
            foreach ($records as $row) {
                $name = str_replace(\'"\', \'""\', $row[\'candidate_name\'] ?? \'\');
                $email = str_replace(\'"\', \'""\', $row[\'candidate_email\'] ?? \'\');
                $phone = str_replace(\'"\', \'""\', $row[\'candidate_phone\'] ?? \'\');
                $jobId = str_replace(\'"\', \'""\', (string) ($row[\'job_id\'] ?? \'\'));
                $st = str_replace(\'"\', \'""\', $row[\'status\'] ?? \'\');
                $date = str_replace(\'"\', \'""\', $row[\'created_at\'] ?? \'\');
                $csvData .= "\"{$row[\'id\']}\",\"{$name}\",\"{$email}\",\"{$phone}\",\"{$jobId}\",\"{$st}\",\"{$date}\"\n";
            }
            return $this->response
                ->setHeader(\'Content-Type\', \'text/csv; charset=utf-8\')
                ->setHeader(\'Content-Disposition\', \'attachment; filename="talenthub_selected_apps_\' . date(\'Y-m-d_His\') . \'.csv"\')
                ->setBody($csvData);
        }

        // 2. Bulk Transition Pipeline Stage
        if ($action === \'transition\') {
            $statusMap = [
                \'NEW\'         => \'APPLIED\',
                \'APPLIED\'     => \'APPLIED\',
                \'REVIEWING\'   => \'SCREENING\',
                \'SCREENING\'   => \'SCREENING\',
                \'SHORTLISTED\' => \'SHORTLISTED\',
                \'INTERVIEW\'   => \'INTERVIEW\',
                \'OFFERED\'     => \'OFFER\',
                \'OFFER\'       => \'OFFER\',
                \'HIRED\'       => \'HIRED\',
                \'REJECTED\'    => \'REJECTED\',
                \'WITHDRAWN\'   => \'WITHDRAWN\',
            ];

            $canonicalStatus = $statusMap[$targetStatus] ?? null;
            if (!$canonicalStatus) {
                session()->setFlashdata(\'error\', \'Invalid target pipeline stage selected for bulk transition.\');
                return redirect()->to(base_url(\'admin/applications\'));
            }

            $successCount = 0;
            foreach ($validIds as $id) {
                $res = $this->automationService->updateApplicationStatus(
                    $id,
                    $canonicalStatus,
                    $notes,
                    $followUpDate,
                    $adminUserId
                );
                if ($res[\'success\']) {
                    $successCount++;
                }
            }

            try {
                $audit = new AuditLogModel();
                $audit->log(\'BULK_APPLICATIONS_TRANSITION\', \'candidate_applications\', null, null, [
                    \'count\'         => $successCount,
                    \'target_status\' => $canonicalStatus,
                    \'ids\'           => $validIds,
                ], $adminUserId);
            } catch (\Throwable $e) {}

            session()->setFlashdata(\'success\', "Successfully transitioned {$successCount} candidate application(s) to {$canonicalStatus}.");
            return redirect()->to(base_url(\'admin/applications\'));
        }

        session()->setFlashdata(\'error\', \'Unrecognized bulk action requested.\');
        return redirect()->to(base_url(\'admin/applications\'));
    }

    public function downloadResume($id)';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    $updated = str_replace($target_norm, $replacement_norm, $content_norm);
    file_put_contents($path, $updated);
    echo "SUCCESS: ApplicationsController.php updated!\n";
} else {
    echo "FAILED: target not found!\n";
}

