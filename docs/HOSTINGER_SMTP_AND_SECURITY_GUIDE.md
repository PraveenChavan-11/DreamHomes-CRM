# TalentHub Consultant — Hostinger SMTP & Production Security Guide

## 1. P0 Required Action: Rotate Compromised Mailbox Password

> [!WARNING]
> **IMMEDIATE ACTION REQUIRED BY HOSTINGER ACCOUNT HOLDER:**
> The previous mailbox password for `noreply@talenthubconsultant.com` was exposed in prior repository commits. 
> Removing the credential from Git alone **does not invalidate an exposed password**. 
> You must immediately log in to your Hostinger hPanel and rotate the password for `noreply@talenthubconsultant.com`.

### Steps to Rotate Password in Hostinger hPanel:
1. Log into your **Hostinger Control Panel** ([hpanel.hostinger.com](https://hpanel.hostinger.com)).
2. Navigate to **Emails** &rarr; select **talenthubconsultant.com**.
3. Locate the mailbox: `noreply@talenthubconsultant.com`.
4. Click the three dots (&vellip;) &rarr; **Change Password**.
5. Generate a strong, unique 16+ character password (e.g., using Hostinger's password generator).
6. Save the new password.

---

## 2. Secure Server-Side Configuration (Ready & Deployed)

Everything has been configured and deployed for you. The configuration file already exists on Hostinger at:
- **Path:** `public_html/api/smtp_config.php`

### Single Step to Activate:
1. Open **Hostinger File Manager** &rarr; navigate to `public_html/api/smtp_config.php`.
2. Find line 18:
   ```php
   'smtp_pass' => 'PASTE_NEW_PASSWORD_HERE',
   ```
3. Replace `'PASTE_NEW_PASSWORD_HERE'` with your rotated password.
4. Click **Save**.

That's it! As soon as you save the file, `/api/contact.php` immediately recognizes the password and starts dispatching emails through Hostinger SMTP.

### Security Guarantee:
This file is blocked by Apache `.htaccess` (`FilesMatch "smtp_config\.php"`), so web visitors attempting to access `https://talenthubconsultant.com/api/smtp_config.php` receive an immediate `HTTP 403 Forbidden` response. Your password is never accessible over the web.

---

## 3. Contact Form Architecture & Fallback Resilience

The contact form is built with dual-mode resilience:
1. **Direct SMTP Delivery:** Submissions are validated server-side, checked for email header injection, rate-limited (5 requests / 15 min per IP), and passed to Hostinger SMTP.
2. **Honest Delivery Response:** If SMTP credentials are not yet configured or if an SMTP authentication failure occurs, the server returns a clear HTTP 503/500 code and **never displays a false success message**.
3. **Instant Email Draft Fallback:** If server delivery is unavailable or in maintenance mode, the form automatically offers a 1-click **Launch Email Client** fallback with pre-formatted subject and body addressed to `hr@talenthubconsultant.com`.

---

## 4. Security Protections Enforced in `.htaccess`

The production `.htaccess` file enforces:
- **HTTPS Enforcement:** Instant 301 redirection to `https://`.
- **Sensitive File Access Denial:** Apache blocks any request to `.env`, `smtp_config.php`, `*.log`, `*.sh`, `*.key`, `*.pem`, `.git`.
- **Content Security Policy (CSP):** Permits necessary scripts for Google Analytics 4 (`G-K6J0RWMLBS`) and Tawk.to Live Chat (`embed.tawk.to`, `wss://*.tawk.to`) while prohibiting untrusted remote execution.
- **Cache-Control:** Dynamic HTML pages have `no-cache, must-revalidate` so visitors always receive the latest static build without stale chunk errors.
