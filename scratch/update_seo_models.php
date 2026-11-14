<?php
// 1. Update JobModel.php
$jobModelPath = 'app/Models/JobModel.php';
$content = file_get_contents($jobModelPath);
$target = "'created_by',\n    ];";
$replacement = "'created_by',\n        'meta_title',\n        'meta_description',\n        'meta_keywords',\n    ];";
$content = str_replace($target, $replacement, $content);
file_put_contents($jobModelPath, $content);
echo "JobModel updated.\n";

// 2. Update BlogPostModel.php
$blogModelPath = 'app/Models/BlogPostModel.php';
$content = file_get_contents($blogModelPath);
$target = "'author_id',\n    ];";
$replacement = "'author_id',\n        'meta_keywords',\n    ];";
$content = str_replace($target, $replacement, $content);
file_put_contents($blogModelPath, $content);
echo "BlogPostModel updated.\n";

// 3. Update WebsitePageModel.php
$pageModelPath = 'app/Models/WebsitePageModel.php';
$content = file_get_contents($pageModelPath);
$target = "'last_updated_by',\n    ];";
$replacement = "'last_updated_by',\n        'meta_keywords',\n    ];";
$content = str_replace($target, $replacement, $content);
file_put_contents($pageModelPath, $content);
echo "WebsitePageModel updated.\n";

