<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminUserModel;
use App\Models\AuditLogModel;
use App\Models\LoginAttemptModel;
use App\Models\PasswordResetModel;
use App\Services\OutboxService;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function login()
    {
        $session = session();
        if ($session->get('admin_user')) {
            return redirect()->to(base_url('admin'));
        }

        if ($this->request->getMethod() === 'post' || $this->request->getMethod() === 'POST' || $this->request->is('post')) {
            $identifier = '';
            $password   = '';

            // Safe parsing: only parse JSON if Content-Type indicates JSON
            $contentType = $this->request->header('Content-Type') ? $this->request->header('Content-Type')->getValue() : '';
            if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
                try {
                    $json = $this->request->getJSON(true) ?? [];
                    if (is_array($json)) {
                        $identifier = trim((string) ($json['email'] ?? $json['username'] ?? ''));
                        $password   = (string) ($json['password'] ?? '');
                    }
                } catch (\Throwable $e) {
                    $identifier = '';
                    $password   = '';
                }
            }

            // Fallback to standard form input
            if ($identifier === '') {
                $identifier = trim((string) ($this->request->getPost('email') ?? $this->request->getPost('username') ?? ''));
            }
            if ($password === '') {
                $password = (string) ($this->request->getPost('password') ?? '');
            }

            $attemptModel = new LoginAttemptModel();
            $ip = $this->request->getIPAddress();

            try {
                if ($attemptModel->countRecentFailures($ip) >= 5) {
                    $errorMsg = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
                    if ($this->request->isAJAX() || $this->isApiRequest()) {
                        return $this->respondError($errorMsg, 429, 'LOGIN_THROTTLED');
                    }
                    return redirect()->back()->withInput()->with('error', $errorMsg);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Login attempt throttling check failed: ' . $e->getMessage());
            }

            $userModel = new AdminUserModel();
            $user = null;
            try {
                $user = $userModel->authenticate($identifier, $password);
            } catch (\Throwable $e) {
                log_message('error', 'User authentication failed with exception: ' . $e->getMessage());
            }

            if (!$user) {
                try {
                    $attemptModel->record($identifier, false);
                } catch (\Throwable $e) {
                    log_message('error', 'Record login failure failed: ' . $e->getMessage());
                }
                $errorMsg = 'Invalid username/email or password.';
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondError($errorMsg, 401, 'INVALID_CREDENTIALS');
                }
                return redirect()->back()->withInput()->with('error', $errorMsg);
            }

            // Success: record attempt & check MFA requirements
            try {
                $attemptModel->record($identifier, true);
            } catch (\Throwable $e) {
                log_message('error', 'Record login success failed: ' . $e->getMessage());
            }

            // Check if Email OTP verification is enabled for this user or globally in website_settings
            $settingModel = new \App\Models\WebsiteSettingModel();
            $globalOtp = $settingModel->getSetting('admin_email_otp_enabled', '0');
            $otpEnabled = ($globalOtp === '1' || $globalOtp === 1);

            if ($otpEnabled) {
                $otp = sprintf('%06d', random_int(100000, 999999));
                $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);

                try {
                    $userModel->update($user['id'], [
                        'email_otp_code'       => $hashedOtp,
                        'email_otp_expires_at' => $expiresAt,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', 'Update email_otp_code failed: ' . $e->getMessage());
                }

                $session->set([
                    '2fa_pending_user_id' => $user['id'],
                    '2fa_pending_email'   => $user['email'],
                    '2fa_resend_allowed'  => time() + 60,
                ]);

                // Dispatch Email OTP via OutboxService
                try {
                    $outbox = new OutboxService();
                    $outbox->enqueue(
                        $user['email'],
                        "TalentHub Admin Verification Code: {$otp}",
                        view('emails/admin_login_otp', [
                            'user' => $user,
                            'otp'  => $otp,
                            'ip'   => $this->request->getIPAddress(),
                            'time' => date('M j, Y g:i A'),
                        ]),
                        'admin_auth',
                        (int) $user['id']
                    );
                    $outbox->processPending(1);
                } catch (\Throwable $e) {
                    log_message('error', 'Admin Login OTP dispatch failed: ' . $e->getMessage());
                }

                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondJson([
                        'mfa_required' => true,
                        'redirect'     => base_url('admin/verify-otp'),
                    ], 'Verification code dispatched to your registered email address.');
                }

                return redirect()->to(base_url('admin/verify-otp'))->with('success', 'A 6-digit verification code has been dispatched to your email.');
            }

            $session->regenerate();
            $session->set('admin_user', $user);

            try {
                $audit = new AuditLogModel();
                $audit->log('ADMIN_LOGIN', 'admin_users', (int) $user['id'], null, ['username' => $user['username']], (int) $user['id']);
            } catch (\Throwable $e) {
                log_message('error', 'Audit log failed: ' . $e->getMessage());
            }

            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondJson([
                    'user' => $user,
                ], 'Logged in successfully.');
            }

            return redirect()->to(base_url('admin'));
        }

        return view('admin/login', [
            'settings' => $this->siteSettings,
        ]);
    }

    /**
     * Verify Email OTP Code
     */
    public function verifyOtp()
    {
        $session = session();
        $pendingUserId = $session->get('2fa_pending_user_id');

        if (!$pendingUserId) {
            return redirect()->to(base_url('admin/login'));
        }

        $userModel = new AdminUserModel();
        $user = $userModel->find((int) $pendingUserId);

        if (!$user || (int) $user['is_active'] !== 1) {
            $session->remove(['2fa_pending_user_id', '2fa_pending_email', '2fa_resend_allowed']);
            return redirect()->to(base_url('admin/login'))->with('error', 'Session expired. Please sign in again.');
        }

        if ($this->request->getMethod() === 'post' || $this->request->getMethod() === 'POST' || $this->request->is('post')) {
            $otp = trim((string) ($this->request->getPost('otp') ?? ''));

            if (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
                return redirect()->back()->with('error', 'Please enter a valid 6-digit verification code.');
            }

            if (empty($user['email_otp_code']) || empty($user['email_otp_expires_at'])) {
                return redirect()->back()->with('error', 'Verification code expired. Please click Resend Passcode.');
            }

            if (strtotime($user['email_otp_expires_at']) < time()) {
                return redirect()->back()->with('error', 'Verification code has expired. Please click Resend Passcode.');
            }

            if (!password_verify($otp, $user['email_otp_code'])) {
                return redirect()->back()->with('error', 'Invalid verification code. Please check your email and try again.');
            }

            // OTP Verified Successfully!
            $userModel->update($user['id'], [
                'email_otp_code'       => null,
                'email_otp_expires_at' => null,
                'last_login_at'        => date('Y-m-d H:i:s'),
                'failed_login_count'   => 0,
            ]);

            $session->remove(['2fa_pending_user_id', '2fa_pending_email', '2fa_resend_allowed']);
            $session->regenerate();
            $session->set('admin_user', $user);

            try {
                $audit = new AuditLogModel();
                $audit->log('ADMIN_LOGIN_MFA_SUCCESS', 'admin_users', (int) $user['id'], null, ['email_otp' => true], (int) $user['id']);
            } catch (\Throwable $e) {}

            return redirect()->to(base_url('admin'))->with('success', 'Workstation verified successfully. Welcome back, ' . esc($user['first_name'] ?? $user['username']) . '!');
        }

        return view('admin/verify_otp', [
            'settings'    => $this->siteSettings,
            'maskedEmail' => $this->maskEmail($user['email'] ?? ''),
        ]);
    }

    /**
     * Resend Email OTP Code
     */
    public function resendOtp()
    {
        $session = session();
        $pendingUserId = $session->get('2fa_pending_user_id');

        if (!$pendingUserId) {
            return redirect()->to(base_url('admin/login'));
        }

        $resendAllowed = (int) ($session->get('2fa_resend_allowed') ?? 0);
        if (time() < $resendAllowed) {
            $waitSec = $resendAllowed - time();
            return redirect()->to(base_url('admin/verify-otp'))->with('error', "Please wait {$waitSec} seconds before requesting a new code.");
        }

        $userModel = new AdminUserModel();
        $user = $userModel->find((int) $pendingUserId);

        if (!$user) {
            return redirect()->to(base_url('admin/login'));
        }

        $otp = sprintf('%06d', random_int(100000, 999999));
        $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 600);

        $userModel->update($user['id'], [
            'email_otp_code'       => $hashedOtp,
            'email_otp_expires_at' => $expiresAt,
        ]);

        $session->set('2fa_resend_allowed', time() + 60);

        try {
            $outbox = new OutboxService();
            $outbox->enqueue(
                $user['email'],
                "TalentHub Admin Verification Code: {$otp}",
                view('emails/admin_login_otp', [
                    'user' => $user,
                    'otp'  => $otp,
                    'ip'   => $this->request->getIPAddress(),
                    'time' => date('M j, Y g:i A'),
                ]),
                'admin_auth',
                (int) $user['id']
            );
            $outbox->processPending(1);
        } catch (\Throwable $e) {
            log_message('error', 'Resend OTP dispatch failed: ' . $e->getMessage());
        }

        return redirect()->to(base_url('admin/verify-otp'))->with('success', 'A fresh verification code has been dispatched to your email.');
    }

    /**
     * Cancel OTP & Return to Login
     */
    public function cancelOtp()
    {
        session()->remove(['2fa_pending_user_id', '2fa_pending_email', '2fa_resend_allowed']);
        return redirect()->to(base_url('admin/login'))->with('success', 'Sign-in cancelled.');
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);
        if ($len <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, $len - 2)) . substr($name, -1);
        }
        return $maskedName . '@' . $domain;
    }

    public function logout()
    {
        $session = session();
        $user = $session->get('admin_user');
        if ($user) {
            $audit = new AuditLogModel();
            $audit->log('ADMIN_LOGOUT', 'admin_users', (int) $user['id'], null, null, (int) $user['id']);
        }

        $session->destroy();

        if ($this->request->isAJAX() || $this->isApiRequest()) {
            return $this->respondJson([], 'Logged out successfully.');
        }

        return redirect()->to(base_url('admin/login'))->with('success', 'You have been logged out.');
    }

    public function session(): ResponseInterface
    {
        $session = session();
        $user = $session->get('admin_user');
        if (!$user) {
            return $this->respondError('No active session', 401, 'UNAUTHENTICATED');
        }

        return $this->respondJson([
            'user' => $user,
        ]);
    }

    public function forgotPassword()
    {
        if ($this->request->getMethod() === 'post' || $this->request->getMethod() === 'POST' || $this->request->is('post')) {
            $email = '';
            $contentType = $this->request->header('Content-Type') ? $this->request->header('Content-Type')->getValue() : '';
            if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
                try {
                    $json = $this->request->getJSON(true) ?? [];
                    $email = strtolower(trim((string) ($json['email'] ?? '')));
                } catch (\Throwable $e) {
                    $email = '';
                }
            }
            if ($email === '') {
                $email = strtolower(trim((string) ($this->request->getPost('email') ?? '')));
            }

            $userModel = new AdminUserModel();
            $user = $userModel->where('email', $email)->first();

            if ($user) {
                $pwdModel = new PasswordResetModel();
                $rawToken = $pwdModel->createToken($email);
                $resetLink = base_url('admin/reset-password?token=' . $rawToken);

                $outbox = new OutboxService();
                $outbox->queueTemplate('password_reset', $email, $user['first_name'] . ' ' . $user['last_name'], [
                    'username'   => $user['username'],
                    'reset_link' => $resetLink,
                ]);
            }

            // Always respond success to prevent email enumeration
            $msg = 'If the email address exists in our system, password reset instructions have been dispatched.';
            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondJson([], $msg);
            }
            return redirect()->to(base_url('admin/login'))->with('success', $msg);
        }

        return view('admin/forgot_password', [
            'settings' => $this->siteSettings,
        ]);
    }

    public function resetPassword()
    {
        $token = $this->request->getGet('token') ?: $this->request->getPost('token');

        if ($this->request->getMethod() === 'post' || $this->request->getMethod() === 'POST' || $this->request->is('post')) {
            $newPassword = '';
            $confirmPassword = '';
            $contentType = $this->request->header('Content-Type') ? $this->request->header('Content-Type')->getValue() : '';
            if (is_string($contentType) && str_contains(strtolower($contentType), 'application/json')) {
                try {
                    $json = $this->request->getJSON(true) ?? [];
                    $token = $token ?: ($json['token'] ?? null);
                    $newPassword = (string) ($json['password'] ?? '');
                    $confirmPassword = (string) ($json['password_confirm'] ?? '');
                } catch (\Throwable $e) {}
            }
            if ($newPassword === '') {
                $newPassword = (string) ($this->request->getPost('password') ?? '');
            }
            if ($confirmPassword === '') {
                $confirmPassword = (string) ($this->request->getPost('password_confirm') ?? '');
            }

            if (strlen($newPassword) < 8) {
                $err = 'Password must be at least 8 characters long.';
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondError($err, 422, 'VALIDATION_FAILED');
                }
                return redirect()->back()->with('error', $err);
            }

            if ($newPassword !== $confirmPassword) {
                $err = 'Passwords do not match.';
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondError($err, 422, 'VALIDATION_FAILED');
                }
                return redirect()->back()->with('error', $err);
            }

            $pwdModel = new PasswordResetModel();
            $email = $pwdModel->verifyAndUse((string) $token);

            if (!$email) {
                $err = 'Invalid or expired password reset token. Please request a new link.';
                if ($this->request->isAJAX() || $this->isApiRequest()) {
                    return $this->respondError($err, 400, 'INVALID_TOKEN');
                }
                return redirect()->to(base_url('admin/forgot-password'))->with('error', $err);
            }

            $userModel = new AdminUserModel();
            $user = $userModel->where('email', $email)->first();
            if ($user) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $userModel->update($user['id'], [
                    'password_hash'      => $hash,
                    'failed_login_count' => 0,
                    'locked_until'       => null,
                ]);

                $audit = new AuditLogModel();
                $audit->log('PASSWORD_RESET', 'admin_users', (int) $user['id'], null, ['email' => $email], (int) $user['id']);
            }

            $msg = 'Your password has been reset successfully. Please log in with your new credentials.';
            if ($this->request->isAJAX() || $this->isApiRequest()) {
                return $this->respondJson([], $msg);
            }
            return redirect()->to(base_url('admin/login'))->with('success', $msg);
        }

        return view('admin/reset_password', [
            'token'    => $token,
            'settings' => $this->siteSettings,
        ]);
    }
}
