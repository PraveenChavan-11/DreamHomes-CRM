# TalentHub — Autonomous Engineering Guidelines

## Project Identity & Architecture
- **Stack:** CodeIgniter 4.7 / PHP 8.2 / MariaDB (MySQLi) / Vanilla JS & CSS / Hostinger Shared Hosting.
- **Brand Palette:**
  - Primary Green: `#1D6A34`
  - Secondary Green: `#37CE97`
  - Dark Green: `#1A532A`
  - Background: `#F9FBFA`
  - Deep Teal: `#1B5F52`
- **Product Ecosystem:**
  - WorkSphere Product URL: `https://www.worksphere.talenthubconsultant.com`
  - Technical Enquiries: `projects@talenthubconsultant.com`
  - General / B2B Enquiries: `hr@talenthubconsultant.com`
  - Candidate Applications: `hiring@talenthubconsultant.com`

## Standard Commands
- **Lint:** `php -l <file>`
- **Run Unit Tests:** `php vendor/bin/phpunit --no-coverage`
- **Serve Locally:** `php spark serve --port 8080` (or `8091`)

## Mandatory Engineering Rules
1. **Never Invent Business Information:**
   - Never fabricate customer names, logos, testimonials, awards, or arbitrary statistical counters.
   - Animated counters are permitted ONLY for verified real data (e.g. real database counts or hard facts).
2. **Preserve Integrations & Workflows:**
   - Product catalogue, CRM lead lifecycle, and HMAC signed WorkSphere webhook receiver must remain untouched and operational.
   - Form submissions must never fake success; all data must persist to MariaDB and trigger proper notifications.
3. **Design & Motion Principles:**
   - Built on `public/assets/css/tokens.css`, `theme.css`, `motion.css`.
   - Progressive enhancement: sites remain 100% readable and usable with JavaScript disabled.
   - Respect `prefers-reduced-motion` at all times.
4. **Git Safety:**
   - Do not commit secrets, do not discard uncommitted work, do not force-push.
5. **Continuous Quality:**
   - Before ending a milestone, verify all 141+ PHPUnit tests pass and update `docs/REDESIGN_PROGRESS.md`.

