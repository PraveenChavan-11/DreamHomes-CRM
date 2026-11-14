<?php
$path = 'app/Views/admin/users/form.php';
$content = file_get_contents($path);

$target = '      <!-- Active Status -->
      <div class="pt-2 border-t border-slate-100">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="is_active" value="1" <?= (!empty($user[\'is_active\']) || empty($user)) ? \'checked\' : \'\' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Active User (Permitted to log in to Admin Portal)</span>
        </label>
      </div>';

$replacement = '      <!-- Active Status -->
      <div class="pt-2 border-t border-slate-100">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="is_active" value="1" <?= (!empty($user[\'is_active\']) || empty($user)) ? \'checked\' : \'\' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Active User (Permitted to log in to Admin Portal)</span>
        </label>
      </div>

      <!-- Two-Factor Authentication via Email OTP -->
      <div class="pt-2 border-t border-slate-100">
        <label class="flex items-center gap-2.5 text-xs font-bold text-slate-800 cursor-pointer">
          <input type="checkbox" name="email_otp_enabled" value="1" <?= !empty($user[\'email_otp_enabled\']) ? \'checked\' : \'\' ?> class="w-4 h-4 rounded text-brand-primary focus:ring-brand-primary border-slate-300">
          <span>Require Email OTP Verification on Login (Two-Factor Authentication)</span>
        </label>
        <p class="text-[11px] text-slate-500 ml-6 mt-1">
          When enabled, logging in with this user\'s password sends a 6-digit one-time passcode to their registered work email address with 10-minute expiry.
        </p>
      </div>';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$replacement_norm = str_replace("\r\n", "\n", $replacement);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $replacement_norm, $content_norm));
    echo "form.php updated successfully.\n";
} else {
    echo "target not found in form.php\n";
}

