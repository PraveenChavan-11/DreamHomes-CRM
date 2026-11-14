<?php
$path = 'app/Controllers/Web/JobsController.php';
$content = file_get_contents($path);

$target = "            // 7. Securely validate & vault resume file
            \$vaultResult = \$vault->storeResume(\$file, \$candId);";

$replacement = "            // 7. Securely validate & vault resume file
            \$vaultResult = \$vault->storeResume(\$file, \$candId);

            // 7.1 Automated Skills Extraction (GAP-03: Resume Keyword Parsing)
            try {
                \$ext = strtolower(pathinfo(\$file->getClientName(), PATHINFO_EXTENSION));
                if (!empty(\$vaultResult['file_path'])) {
                    \$extractedSkills = \$vault->extractResumeKeywords(\$vaultResult['file_path'], \$ext);
                    if (!empty(\$extractedSkills)) {
                        \$candRec = \$candModel->find(\$candId);
                        \$existingSkills = !empty(\$candRec['primary_skills']) ? array_map('trim', explode(',', \$candRec['primary_skills'])) : [];
                        \$allSkills = array_unique(array_merge(\$existingSkills, \$extractedSkills));
                        \$candModel->update(\$candId, ['primary_skills' => implode(', ', array_slice(\$allSkills, 0, 15))]);
                    }
                }
            } catch (\\Throwable \$skillErr) {
                log_message('warning', 'JobsController: Resume skill extraction skipped: ' . \$skillErr->getMessage());
            }";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "JobsController updated with automated skill parsing.\n";
} else {
    echo "FAILED: target not found in JobsController.\n";
}

