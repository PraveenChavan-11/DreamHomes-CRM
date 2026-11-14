<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
$res = $mysqli->query("DESCRIBE jobs");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

