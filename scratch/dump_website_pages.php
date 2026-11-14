<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error . "\n";
    exit(1);
}
$res = $mysqli->query("SELECT id, slug, title, meta_title, meta_description FROM website_pages ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Slug: {$row['slug']} | Title: {$row['title']} | MetaTitle: " . ($row['meta_title'] ?: '[BLANK]') . " | MetaDesc: " . ($row['meta_description'] ?: '[BLANK]') . "\n";
}

