<?php
// 1. Update SettingsController.php
$path = 'app/Controllers/Admin/SettingsController.php';
$content = file_get_contents($path);

$targetKeys = "'contact_form_enabled',\n        ];";
$replaceKeys = "'contact_form_enabled',\n            'admin_email_otp_enabled',\n        ];";

$targetCheckboxes = "'contact_form_enabled',\n        ];";
// Since $targetKeys and $targetCheckboxes are the same string, let's replace both occurrences
$content = str_replace($targetKeys, $replaceKeys, $content);
file_put_contents($path, $content);
echo "SettingsController updated.\n";

// 2. Update AuthController.php
$authPath = 'app/Controllers/Admin/AuthController.php';
$authContent = file_get_contents($authPath);

$targetOtp = "\$otpEnabled = (!empty(\$user['email_otp_enabled']) || (\$this->siteSettings['admin_email_otp_enabled'] ?? '0') === '1');";
$replaceOtp = "\$settingModel = new \App\Models\WebsiteSettingModel();\n            \$globalOtp = \$settingModel->getSetting('admin_email_otp_enabled', '0');\n            \$otpEnabled = (\$globalOtp === '1' || \$globalOtp === 1);";

$authContent_norm = str_replace("\r\n", "\n", $authContent);
$targetOtp_norm = str_replace("\r\n", "\n", $targetOtp);
$replaceOtp_norm = str_replace("\r\n", "\n", $replaceOtp);

if (strpos($authContent_norm, $targetOtp_norm) !== false) {
    file_put_contents($authPath, str_replace($targetOtp_norm, $replaceOtp_norm, $authContent_norm));
    echo "AuthController updated.\n";
} else {
    echo "FAILED to find target in AuthController\n";
}

