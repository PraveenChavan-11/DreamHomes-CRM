<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'talenthub_db');
if ($mysqli->connect_error) {
    die("Connect error: " . $mysqli->connect_error);
}
$res = $mysqli->query("SHOW TABLES");
while ($row = $res->fetch_array()) {
    $table = $row[0];
    $q = $mysqli->query("SELECT * FROM `$table`");
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            foreach ($r as $k => $v) {
                if (is_string($v) && (stripos($v, 'Talent Hub insights & job updates') !== false || stripos($v, 'recruitment trends') !== false || stripos($v, 'Stay Ahead in the Pune') !== false)) {
                    echo "FOUND in table $table, col $k, id " . ($r['id'] ?? '') . ":\n";
                    echo substr($v, 0, 300) . "\n-------------------\n";
                }
            }
        }
    }
}
