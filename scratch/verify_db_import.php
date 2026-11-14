<?php
$m = new mysqli('localhost', 'root', '', 'talenthub_db');
if ($m->connect_error) {
    die("Connection error: " . $m->connect_error);
}

echo "=== WEBSITE PAGES (Count: " . $m->query("SELECT COUNT(*) FROM website_pages")->fetch_row()[0] . ") ===\n";
$res = $m->query("SELECT slug, CHAR_LENGTH(meta_title) as t_len, CHAR_LENGTH(meta_description) as d_len, meta_title FROM website_pages ORDER BY id");
while ($r = $res->fetch_assoc()) {
    echo sprintf("Page: %-22s | Title: %2d chars | Desc: %3d chars | %s\n", $r['slug'], $r['t_len'], $r['d_len'], substr($r['meta_title'], 0, 45));
}

echo "\n=== CANDIDATES (Count: " . $m->query("SELECT COUNT(*) FROM candidates")->fetch_row()[0] . ") ===\n";
$res = $m->query("SELECT id, full_name, email FROM candidates");
while ($r = $res->fetch_assoc()) {
    echo "Candidate [{$r['id']}]: {$r['full_name']} ({$r['email']})\n";
}

echo "\n=== JOBS SEO (Count: " . $m->query("SELECT COUNT(*) FROM jobs")->fetch_row()[0] . ") ===\n";
$res = $m->query("SELECT id, title, CHAR_LENGTH(meta_title) as t_len, CHAR_LENGTH(meta_description) as d_len FROM jobs");
while ($r = $res->fetch_assoc()) {
    echo "Job [{$r['id']}] {$r['title']}: Title {$r['t_len']} chars, Desc {$r['d_len']} chars\n";
}

echo "\n=== SETTINGS OTP ===\n";
$res = $m->query("SELECT setting_key, setting_value FROM website_settings WHERE setting_key = 'admin_email_otp_enabled'");
while ($r = $res->fetch_assoc()) {
    echo "Setting: {$r['setting_key']} = {$r['setting_value']}\n";
}
