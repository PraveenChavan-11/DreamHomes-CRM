<?php
$path = 'app/Controllers/Admin/JobsController.php';
$content = file_get_contents($path);

// In create():
$targetCreate = "            'created_by'           => \$this->adminUser['id'] ?? null,
        ];";
$replaceCreate = "            'created_by'           => \$this->adminUser['id'] ?? null,
            'meta_title'           => trim((string) \$this->request->getPost('meta_title')) ?: null,
            'meta_description'     => trim((string) \$this->request->getPost('meta_description')) ?: null,
            'meta_keywords'        => trim((string) \$this->request->getPost('meta_keywords')) ?: null,
        ];";

// In update():
$targetUpdate = "            'is_featured'          => \$this->request->getPost('is_featured') ? 1 : 0,
        ];";
$replaceUpdate = "            'is_featured'          => \$this->request->getPost('is_featured') ? 1 : 0,
            'meta_title'           => trim((string) \$this->request->getPost('meta_title')) ?: null,
            'meta_description'     => trim((string) \$this->request->getPost('meta_description')) ?: null,
            'meta_keywords'        => trim((string) \$this->request->getPost('meta_keywords')) ?: null,
        ];";

$content_norm = str_replace("\r\n", "\n", $content);
$targetCreate_norm = str_replace("\r\n", "\n", $targetCreate);
$replaceCreate_norm = str_replace("\r\n", "\n", $replaceCreate);
$targetUpdate_norm = str_replace("\r\n", "\n", $targetUpdate);
$replaceUpdate_norm = str_replace("\r\n", "\n", $replaceUpdate);

if (strpos($content_norm, $targetCreate_norm) !== false && strpos($content_norm, $targetUpdate_norm) !== false) {
    $content_norm = str_replace($targetCreate_norm, $replaceCreate_norm, $content_norm);
    $content_norm = str_replace($targetUpdate_norm, $replaceUpdate_norm, $content_norm);
    file_put_contents($path, $content_norm);
    echo "JobsController updated with SEO fields.\n";
} else {
    echo "FAILED: markers not found in JobsController\n";
}

