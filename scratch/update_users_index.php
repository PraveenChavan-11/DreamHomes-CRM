<?php
$path = 'app/Views/admin/users/index.php';
$content = file_get_contents($path);

$target = '                    <span><?= esc($u[\'email\']) ?></span>
                  </div>';

$replacement = '                    <span><?= esc($u[\'email\']) ?></span>
                    <?php if (!empty($u[\'email_otp_enabled\'])): ?>
                      <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Email OTP 2FA
                      </span>
                    <?php endif; ?>
                  </div>';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "users/index.php updated successfully.\n";
} else {
    echo "target not found in users/index.php\n";
}

