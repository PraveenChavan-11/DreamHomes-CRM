<?php
require 'vendor/autoload.php';
define('SYSTEMPATH', __DIR__ . '/../vendor/codeigniter4/framework/system/');
define('APPPATH', __DIR__ . '/../app/');
define('WRITEPATH', __DIR__ . '/../writable/');
define('ROOTPATH', __DIR__ . '/../');
define('FCPATH', __DIR__ . '/../public/');

require 'app/Config/Paths.php';
$paths = new Config\Paths();
require SYSTEMPATH . 'bootstrap.php';

$db = \Config\Database::connect();
$rows = $db->table('pages')->get()->getResultArray();
echo "TOTAL PAGES IN DB: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | Slug: " . $r['slug'] . " | Title: " . $r['title'] . " | Meta Title: " . ($r['meta_title'] ?? 'NULL') . "\n";
}

