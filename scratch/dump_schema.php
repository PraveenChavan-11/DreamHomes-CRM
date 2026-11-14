<?php

define('HOMEPATH', __DIR__ . '/../');
define('CONFIGPATH', __DIR__ . '/../app/Config/');
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$db = \Config\Database::connect();
$tables = $db->listTables();
$schema = [];
foreach ($tables as $table) {
    $fields = $db->getFieldData($table);
    $schema[$table] = array_map(function($f) {
        return [
            'name'        => $f->name,
            'type'        => $f->type,
            'max_length'  => $f->max_length,
            'nullable'    => $f->nullable,
            'default'     => $f->default,
            'primary_key' => $f->primary_key,
        ];
    }, $fields);
}

file_put_contents(__DIR__ . '/database_schema_dump.json', json_encode($schema, JSON_PRETTY_PRINT));
echo "SUCCESS: Dumped " . count($schema) . " tables to database_schema_dump.json\n";
