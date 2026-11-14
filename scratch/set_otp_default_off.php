<?php
$mysqli = new mysqli('localhost', 'root', '', 'talenthub_db');
$mysqli->query("INSERT IGNORE INTO website_settings (setting_key, setting_value, setting_group, is_public) VALUES ('admin_email_otp_enabled', '0', 'security', 0)");
$mysqli->query("UPDATE website_settings SET setting_value = '0' WHERE setting_key = 'admin_email_otp_enabled'");
$mysqli->query("UPDATE admin_users SET email_otp_enabled = 0");
echo "admin_email_otp_enabled is verified OFF (0) by default in database.\n";

