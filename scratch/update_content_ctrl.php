<?php
$path = 'app/Controllers/Admin/ContentController.php';
$content = file_get_contents($path);

$target = "        \$title = trim(\$this->request->getPost('title') ?? '');
        \$data = [
            'title'            => \$title,
            'meta_title'       => trim(\$this->request->getPost('meta_title') ?? ''),
            'meta_description' => trim(\$this->request->getPost('meta_description') ?? ''),
        ];

        // Process sections / content if present
        \$heroTitle = \$this->request->getPost('hero_title');
        \$heroSubtitle = \$this->request->getPost('hero_subtitle');
        if (\$heroTitle !== null) {
            \$sections = json_decode(\$page['sections_json'] ?? '[]', true) ?: [];
            \$sections['hero']['title'] = \$heroTitle;
            \$sections['hero']['subtitle'] = \$heroSubtitle;
            \$data['sections_json'] = json_encode(\$sections);
        }";

$replacement = "        \$title = trim(\$this->request->getPost('title') ?? '');
        \$data = [
            'title'            => \$title,
            'meta_title'       => trim(\$this->request->getPost('meta_title') ?? ''),
            'meta_description' => trim(\$this->request->getPost('meta_description') ?? ''),
            'meta_keywords'    => trim(\$this->request->getPost('meta_keywords') ?? ''),
            'og_image'         => trim(\$this->request->getPost('og_image') ?? ''),
        ];";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "ContentController updated.\n";
} else {
    echo "FAILED: target not found in ContentController\n";
}

