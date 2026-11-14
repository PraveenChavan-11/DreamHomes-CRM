<?php
$path = 'app/Controllers/Web/InsightsController.php';
$content = file_get_contents($path);

$target = "        return \$this->renderView('public/insights/show', [
            'meta_title'       => \$title,
            'meta_description' => \$desc,
            'post'             => \$post,
            'relatedPosts'     => \$relatedPosts,
        ]);";

$replacement = "        \$keywords = !empty(\$post['meta_keywords']) ? \$post['meta_keywords'] : (\$post['title'] . ', Pune recruitment intelligence, IT hiring benchmarks, career insights Pune');
        \$featuredImg = !empty(\$post['featured_image']) ? base_url(\$post['featured_image']) : null;

        return \$this->renderView('public/insights/show', [
            'meta_title'       => \$title,
            'meta_description' => \$desc,
            'meta_keywords'    => \$keywords,
            'og_type'          => 'article',
            'og_image'         => \$featuredImg,
            'post'             => \$post,
            'relatedPosts'     => \$relatedPosts,
        ]);";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "Web InsightsController updated with dynamic article SEO.\n";
} else {
    echo "FAILED: target not found in Web InsightsController\n";
}

