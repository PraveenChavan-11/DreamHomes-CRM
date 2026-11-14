<?php
$path = 'app/Config/Routes.php';
$content = file_get_contents($path);

$target = "\$routes->get('engagement/analytics', 'Admin\EngagementController::analytics');";
$replacement = "\$routes->get('engagement/analytics', 'Admin\EngagementController::analytics');\n    \$routes->get('engagement/analytics/report', 'Admin\EngagementController::executiveReport');";

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "Routes.php updated with executiveReport.\n";
} else {
    echo "FAILED: target not found in Routes.php\n";
}

