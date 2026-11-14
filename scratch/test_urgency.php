<?php
require 'vendor/autoload.php';
define('FCPATH', __DIR__ . '/../public/');
$app = require 'app/Config/Paths.php';
$paths = new Config\Paths();
require SYSTEMPATH . 'bootstrap.php';

$db = \Config\Database::connect();
$rows = $db->query('SELECT urgency, COUNT(*) as c FROM employer_enquiries GROUP BY urgency')->getResultArray();
echo "EMPLOYER ENQUIRIES URGENCY:\n";
print_r($rows);

$total = $db->table('employer_enquiries')->countAllResults();
echo "TOTAL EMPLOYER ENQUIRIES: $total\n";

