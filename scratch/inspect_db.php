<?php
require __DIR__ . '/../vendor/autoload.php';
define('FCPATH', __DIR__ . '/../public/');
$app = Config\Services::codeigniter();
$app->initialize();
$db = Config\Database::connect();
$rows = $db->table('announcements')->get()->getResultArray();
echo "Found " . count($rows) . " announcements:\n";
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | Type: " . $r['announcement_type'] . " | Format: " . $r['display_format'] . " | Status: " . $r['status'] . "\n";
    echo "Heading: " . $r['public_heading'] . "\n";
    echo "Audience: " . $r['target_audience'] . " | Pages: " . $r['target_pages'] . "\n";
    echo "----------------------------------------\n";
}

