<?php
$path = 'app/Views/admin/settings/index.php';
$content = file_get_contents($path);

$target = '    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-4 pt-4">';

$addition = '    <!-- Section 6: Administrative Security & Two-Step Verification -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 md:p-8 space-y-5">
      <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
        <div>
          <h2 class="text-sm font-extrabold text-slate-900">Administrative Security &amp; Two-Step Verification</h2>
          <p class="text-xs text-slate-500">Enhanced login protection for administrators and recruitment consultants</p>
        </div>
      </div>

      <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs">
              <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
              <h3 class="text-xs font-bold text-slate-900">Two-Step Email OTP Login Verification</h3>
              <p class="text-[11px] text-slate-500">Require a 6-digit one-time passcode dispatched to the administrator\'s work email upon login</p>
            </div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="admin_email_otp_enabled" value="1" <?= (!empty($settings[\'admin_email_otp_enabled\']) && $settings[\'admin_email_otp_enabled\'] == \'1\') ? \'checked\' : \'\' ?> class="sr-only peer">
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-primary"></div>
          </label>
        </div>
        <div class="text-[11px] text-slate-600 bg-white p-3.5 rounded-xl border border-slate-200/80 leading-relaxed flex items-start gap-2.5">
          <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div>
            <strong>Default: OFF (Disabled).</strong> When switched ON, any staff login attempt (after logout or session timeout) will dispatch a secure 6-digit numeric verification token with a 10-minute expiry to their registered email before granting dashboard access. No external authenticator app needed.
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex items-center justify-end gap-4 pt-4">';

$content_norm = str_replace("\r\n", "\n", $content);
$target_norm = str_replace("\r\n", "\n", $target);
$addition_norm = str_replace("\r\n", "\n", $addition);

if (strpos($content_norm, $target_norm) !== false) {
    file_put_contents($path, str_replace($target_norm, $addition_norm, $content_norm));
    echo "settings/index.php updated with Two-Step OTP section.\n";
} else {
    echo "FAILED: target not found in settings/index.php\n";
}

