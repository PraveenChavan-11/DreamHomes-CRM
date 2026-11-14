<?php

namespace Crm\Controllers;

use Crm\Services\AuthException;
use Crm\Services\AuthService;
use Crm\Services\Crm;
use Crm\Services\EmailService;
use Crm\Services\Settings;

class AuthController extends CrmController
{
    public function login()
    {
        if (session()->get('crm_user')) {
            return redirect()->to(site_url('crm'));
        }
        if ($this->request->is('post')) {
            $id = trim((string) $this->request->getPost('identity'));
            try {
                $u = AuthService::attempt($id, (string) $this->request->getPost('password'), $this->request->getIPAddress(), (string) $this->request->getUserAgent());
                session()->regenerate(true);
                session()->set('crm_user', $u);
                $to = session()->get('crm_intended');
                session()->remove('crm_intended');
                $to = is_string($to) && str_starts_with($to, 'crm') && ! str_contains($to, '//') ? $to : 'crm';

                return redirect()->to(site_url($to));
            } catch (AuthException $e) {
                return redirect()->to(site_url('crm/login'))->withInput()->with('error', $e->getMessage());
            }
        }

        return view('Crm\Views\auth\login');
    }

    public function logout()
    {
        $u = session()->get('crm_user');
        if ($u) {
            AuthService::logout((int) $u['id'], $this->request->getIPAddress(), (string) $this->request->getUserAgent());
        }
        session()->destroy();

        return redirect()->to(site_url('crm/login'))->with('success', 'You have been signed out.');
    }

    public function forgot()
    {
        if ($this->request->is('post')) {
            $email = strtolower(trim((string) $this->request->getPost('email')));
            $raw   = filter_var($email, FILTER_VALIDATE_EMAIL) ? AuthService::createResetToken($email, $this->request->getIPAddress()) : null;
            if ($raw) {
                $u = Crm::db()->table('admin_users')->where('email', $email)->get()->getRowArray();
                try {
                    EmailService::queue($email, [
                        'subject' => 'Reset your DHIS CRM password', 'user_id' => (int) $u['id'], 'automation' => 'password_reset',
                        'body' => "Hello {$u['first_name']},\n\nUse this link within 60 minutes to set a new password:\n" . site_url('crm/reset-password?token=' . $raw) . "\n\nIf you did not request this, ignore this email.",
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', 'reset email failed: ' . $e->getMessage());
                }
            }

            // identical response whether or not the account exists (no user enumeration)
            return redirect()->to(site_url('crm/login'))->with('success', 'If that email belongs to an active account, a reset link has been sent.');
        }

        return view('Crm\Views\auth\forgot');
    }

    public function reset()
    {
        $token = (string) ($this->request->getGet('token') ?? $this->request->getPost('token'));
        if ($this->request->is('post')) {
            if ($this->request->getPost('password') !== $this->request->getPost('password_confirm')) {
                return redirect()->back()->with('error', 'Passwords do not match.');
            }
            try {
                AuthService::resetPassword($token, (string) $this->request->getPost('password'), $this->request->getIPAddress());

                return redirect()->to(site_url('crm/login'))->with('success', 'Password updated. You can sign in now.');
            } catch (AuthException $e) {
                return redirect()->to(site_url('crm/reset-password?token=' . urlencode($token)))->with('error', $e->getMessage());
            }
        }

        return view('Crm\Views\auth\reset', ['token' => $token]);
    }

    public function profile(): string
    {
        $u = Crm::db()->table('admin_users')->where('id', $this->uid())->get()->getRowArray();
        $hist = Crm::db()->table('crm_login_history')->where('user_id', $this->uid())->orderBy('id', 'DESC')->limit(15)->get()->getResultArray();

        return $this->render('auth/profile', ['u' => $u, 'history' => $hist, 'timeout' => Settings::get('session.timeout_minutes')], 'My profile');
    }

    public function changePassword()
    {
        if ($this->request->getPost('new_password') !== $this->request->getPost('confirm_password')) {
            return redirect()->back()->with('error', 'New passwords do not match.');
        }

        return $this->act(static function () {
            AuthService::changePassword((int) session()->get('crm_user')['id'], (string) service('request')->getPost('current_password'), (string) service('request')->getPost('new_password'), service('request')->getIPAddress());
        }, 'Password changed.');
    }
}
