<?php

namespace App\Services;

class FormSecurityService
{
    private string $signingKey;
    private CaptchaService $captchaService;

    public function __construct()
    {
        $this->signingKey = env('app.appKey', env('encryption.key', 'th_secure_signing_salt_2026_pune'));
        $this->captchaService = new CaptchaService();
    }

    /**
     * Generate HTML snippet for form protection (Honeypot, Signed Timestamp, and Turnstile Widget)
     */
    public function renderProtectionFields(string $action = 'submit'): string
    {
        $ts = time();
        $signature = hash_hmac('sha256', (string) $ts, $this->signingKey);

        $html = "<!-- Anti-Bot & Spam Protection -->\n";

        // 1. Accessible Honeypot (Excluded from keyboard tabbing, screen readers, and autofill)
        $html .= '<div style="opacity:0;position:absolute;top:0;left:-9999px;height:0;width:0;z-index:-1;overflow:hidden;pointer-events:none;" aria-hidden="true">' . "\n";
        $html .= '  <label for="th_hp_check" tabindex="-1">Leave this field blank</label>' . "\n";
        $html .= '  <input type="text" name="th_hp_check" id="th_hp_check" value="" tabindex="-1" autocomplete="new-password" aria-hidden="true">' . "\n";
        $html .= '  <input type="text" name="th_hp_website" id="th_hp_website" value="" tabindex="-1" autocomplete="off" aria-hidden="true">' . "\n";
        $html .= "</div>\n";

        // 2. Signed Form Timestamp
        $html .= '<input type="hidden" name="th_form_ts" value="' . $ts . '">' . "\n";
        $html .= '<input type="hidden" name="th_form_sig" value="' . $signature . '">' . "\n";

        // 3. CAPTCHA Widget Container (Cloudflare Turnstile)
        if ($this->captchaService->isEnabled()) {
            $siteKey = esc($this->captchaService->getSiteKey());
            $escapedAction = esc($action);
            $html .= '<div class="pt-2">' . "\n";
            $html .= '  <div class="cf-turnstile" data-sitekey="' . $siteKey . '" data-action="' . $escapedAction . '" data-theme="light"></div>' . "\n";
            $html .= "</div>\n";
        }

        return $html;
    }

    /**
     * Generate signed timestamp array
     */
    public function generateSignedTimestamp(): array
    {
        $ts = time();
        $sig = hash_hmac('sha256', (string) $ts, $this->signingKey);
        return [
            'th_form_ts'  => $ts,
            'th_form_sig' => $sig,
        ];
    }

    /**
     * Verify signed timestamp
     */
    public function verifySignedTimestamp(int $ts, string $sig, int $minSec = 0, int $maxSec = 7200): array
    {
        $expectedSig = hash_hmac('sha256', (string) $ts, $this->signingKey);
        if (!hash_equals($expectedSig, $sig)) {
            return [
                'valid'  => false,
                'reason' => 'TAMPERED_TIMESTAMP',
            ];
        }

        $elapsed = time() - $ts;
        if ($elapsed < $minSec) {
            return [
                'valid'  => false,
                'reason' => 'SUBMISSION_TOO_FAST',
            ];
        }

        if ($elapsed > $maxSec) {
            return [
                'valid'  => false,
                'reason' => 'FORM_EXPIRED',
            ];
        }

        return ['valid' => true, 'elapsed' => $elapsed];
    }

    /**
     * Check if honeypot was triggered in post data
     */
    public function isHoneypotTriggered(array $postData): bool
    {
        return !empty($postData['th_hp_check']) 
            || !empty($postData['th_hp_website'])
            || !empty($postData['th_hp_email'])
            || !empty($postData['website_hp']);
    }

    /**
     * Validate incoming form submission signals
     *
     * @param array $postData Form POST parameters
     * @param string $action Expected action name
     * @param string|null $clientIp Client IP address
     * @return array [ 'valid' => bool, 'code' => string, 'message' => string ]
     */
    public function validateSubmission(array $postData, string $action, ?string $clientIp = null): array
    {
        // 1. Honeypot check (Strict rejection)
        if (!empty($postData['th_hp_check']) || !empty($postData['th_hp_website'])) {
            log_message('warning', "FormSecurityService: Honeypot triggered from IP {$clientIp} on action '{$action}'");
            return [
                'valid'   => false,
                'code'    => 'SPAM_DETECTED',
                'message' => 'Your submission was flagged by automated security defenses. Please refresh and try again.',
            ];
        }

        // 2. Signed form timestamp check (Supplementary signal)
        $ts = $postData['th_form_ts'] ?? null;
        $sig = $postData['th_form_sig'] ?? null;

        if ($ts !== null && $sig !== null) {
            $expectedSig = hash_hmac('sha256', (string) $ts, $this->signingKey);
            if (!hash_equals($expectedSig, (string) $sig)) {
                log_message('warning', "FormSecurityService: Timestamp signature tampering from IP {$clientIp}");
                return [
                    'valid'   => false,
                    'code'    => 'SIGNATURE_TAMPERED',
                    'message' => 'Security verification signature was tampered. Please reload the form.',
                ];
            }

            $elapsed = time() - (int) $ts;
            if ($elapsed < 0) {
                return [
                    'valid'   => false,
                    'code'    => 'INVALID_TIMESTAMP',
                    'message' => 'System clock anomaly detected. Please verify your device time and reload.',
                ];
            }
            if ($elapsed > 86400) { // 24 hours form age limit
                return [
                    'valid'   => false,
                    'code'    => 'FORM_EXPIRED',
                    'message' => 'The submission form has expired. Please reload the page to refresh security tokens.',
                ];
            }
        }

        // 3. CAPTCHA verification
        $token = $postData['cf-turnstile-response'] ?? $postData['g-recaptcha-response'] ?? $postData['captcha_token'] ?? null;
        $captchaResult = $this->captchaService->verify($token, $action, $clientIp);

        if (!$captchaResult['success']) {
            return [
                'valid'   => false,
                'code'    => $captchaResult['code'],
                'message' => $captchaResult['message'],
            ];
        }

        return [
            'valid'   => true,
            'code'    => 'VALID',
            'message' => 'Security signals validated successfully.',
        ];
    }
}
