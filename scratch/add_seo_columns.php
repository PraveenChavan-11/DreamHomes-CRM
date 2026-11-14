<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
if ($mysqli->connect_errno) {
    echo "Connection error: " . $mysqli->connect_error . "\n";
    exit(1);
}

// 1. website_pages.meta_keywords
$res = $mysqli->query("SHOW COLUMNS FROM website_pages LIKE 'meta_keywords'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE website_pages ADD COLUMN meta_keywords VARCHAR(255) NULL AFTER meta_description");
    echo "Added meta_keywords to website_pages.\n";
} else {
    echo "meta_keywords already exists on website_pages.\n";
}

// 2. jobs.meta_title, meta_description, meta_keywords
$res = $mysqli->query("SHOW COLUMNS FROM jobs LIKE 'meta_title'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE jobs ADD COLUMN meta_title VARCHAR(255) NULL AFTER title");
    $mysqli->query("ALTER TABLE jobs ADD COLUMN meta_description TEXT NULL AFTER meta_title");
    $mysqli->query("ALTER TABLE jobs ADD COLUMN meta_keywords VARCHAR(255) NULL AFTER meta_description");
    echo "Added meta_title, meta_description, meta_keywords to jobs.\n";
} else {
    echo "SEO columns already exist on jobs.\n";
}

// 3. blog_posts.meta_keywords
$res = $mysqli->query("SHOW COLUMNS FROM blog_posts LIKE 'meta_keywords'");
if ($res->num_rows === 0) {
    $mysqli->query("ALTER TABLE blog_posts ADD COLUMN meta_keywords VARCHAR(255) NULL AFTER meta_description");
    echo "Added meta_keywords to blog_posts.\n";
} else {
    echo "meta_keywords already exists on blog_posts.\n";
}

echo "Schema update completed successfully.\n";

