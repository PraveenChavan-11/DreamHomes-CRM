<?php

define('HOMEPATH', __DIR__ . '/../');
define('CONFIGPATH', __DIR__ . '/../app/Config/');
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$db = \Config\Database::connect();
$tables = $db->listTables();
$tableData = [];
foreach ($tables as $table) {
    try {
        $count = $db->table($table)->countAllResults();
    } catch (\Throwable $e) {
        $count = 'ERROR: ' . $e->getMessage();
    }
    $tableData[$table] = $count;
}

file_put_contents(__DIR__ . '/table_counts.json', json_encode($tableData, JSON_PRETTY_PRINT));
echo "SUCCESS: Recorded counts for " . count($tables) . " tables\n";

