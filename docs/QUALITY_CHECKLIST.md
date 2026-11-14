# TalentHub — Quality & Verification Checklist

## 1. Automated Verification
- [x] PHP syntax linting (`php -l`) passes on all controllers, models, and views.
- [x] Full PHPUnit test suite passes (141 tests, 997 assertions).
- [x] No PHP notices, warnings, or deprecations during web requests.
- [x] No broken internal links, routes, or assets.

## 2. Brand & Visual Consistency
- [x] Strict palette adherence: `#1D6A34` (Primary), `#37CE97` (Mint), `#1A532A` (Dark), `#F9FBFA` (Bg), `#1B5F52` (Teal).
- [x] Typography uses self-hosted variable Inter + Plus Jakarta Sans.
- [x] Consistent card styling and button hierarchy across public pages.
- [x] Responsive layout across 360px, 390px, 768px, 1366px, 1440px, 1920px.

## 3. SEO & Accessibility
- [x] Semantic HTML5 headings (`h1` - `h4`).
- [x] Alt attributes on all images.
- [x] Dynamic Schema.org structured data (`Organization`, `WebSite`, `ContactPoint`).
- [x] Touch targets ≥ 44px on interactive elements.
- [x] `prefers-reduced-motion` respected in CSS and JS.

## 4. Integrity & Data Protection
- [x] DPDP Act compliant CV deletion requests.
- [x] Cloudflare Turnstile token validation active on contact/enquiry endpoints.
- [x] Rate limiting active on all public POST routes.
- [x] WorkSphere webhook HMAC signature verification enforced.

