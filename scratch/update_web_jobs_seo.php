<?php
$path = 'app/Controllers/Web/JobsController.php';
$content = file_get_contents($path);

$target = "        \$title = \$job['title'] . ' Job in ' . (\$job['location'] ?? 'Pune') . ' | TalentHub';
        \$desc = substr(strip_tags(\$job['description']), 0, 160);";

$replacement = "        \$title = !empty(\$job['meta_title']) ? \$job['meta_title'] : (\$job['title'] . ' in ' . (\$job['location'] ?? 'Pune') . ' | TalentHub');
        \$desc = !empty(\$job['meta_description']) ? \$job['meta_description'] : substr(strip_tags(\$job['description']), 0, 158);
        \$keywords = !empty(\$job['meta_keywords']) ? \$job['meta_keywords'] : (\$job['title'] . ', ' . (\$job['location'] ?? 'Pune') . ' jobs, ' . (\$job['skills'] ?? '') . ', recruitment Pune');";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

$targetRender = "        return \$this->renderView('public/jobs/show', [
            'meta_title'       => \$title,
            'meta_description' => \$desc,
            'job'              => \$job,
            'relatedJobs'      => \$relatedJobs,
            'formProtection'   => (new \\App\\Services\\FormSecurityService())->renderProtectionFields('job_apply'),
        ]);";

$replaceRender = "        return \$this->renderView('public/jobs/show', [
            'meta_title'       => \$title,
            'meta_description' => \$desc,
            'meta_keywords'    => \$keywords,
            'og_type'          => 'article',
            'job'              => \$job,
            'relatedJobs'      => \$relatedJobs,
            'formProtection'   => (new \\App\\Services\\FormSecurityService())->renderProtectionFields('job_apply'),
        ]);";

$targetRender_norm = str_replace("\r\n", "\n", $targetRender);
$replaceRender_norm = str_replace("\r\n", "\n", $replaceRender);

if (strpos($content_norm, $target_norm) !== false && strpos($content_norm, $targetRender_norm) !== false) {
    $content_norm = str_replace($target_norm, $replacement_norm, $content_norm);
    $content_norm = str_replace($targetRender_norm, $replaceRender_norm, $content_norm);
    file_put_contents($path, $content_norm);
    echo "Web JobsController updated with dynamic SEO.\n";
} else {
    echo "FAILED: markers not found in Web JobsController\n";
}

