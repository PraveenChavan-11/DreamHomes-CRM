<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
$res = $mysqli->query("DESCRIBE website_pages");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

