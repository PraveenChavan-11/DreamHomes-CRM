<?php
$path = 'app/Controllers/Admin/UsersController.php';
$content = file_get_contents($path);

// In create():
$target1 = "'is_active'     => \$this->request->getPost('is_active') ? 1 : 0,";
$replace1 = "'is_active'         => \$this->request->getPost('is_active') ? 1 : 0,\n            'email_otp_enabled' => \$this->request->getPost('email_otp_enabled') ? 1 : 0,";

// In update():
$target2 = "'is_active'  => \$this->request->getPost('is_active') ? 1 : 0,";
$replace2 = "'is_active'         => \$this->request->getPost('is_active') ? 1 : 0,\n            'email_otp_enabled' => \$this->request->getPost('email_otp_enabled') ? 1 : 0,";

$content = str_replace($target1, $replace1, $content);
$content = str_replace($target2, $replace2, $content);
file_put_contents($path, $content);
echo "UsersController updated.\n";

