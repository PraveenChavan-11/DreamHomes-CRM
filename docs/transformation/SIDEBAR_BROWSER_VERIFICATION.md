# Sidebar Browser Verification — 2026-11-08

Method: Microsoft Edge 157 (headless) driven by `playwright-core`, against `php spark serve` on `localhost:8080`
with the local dev database. Sessions were forged on the local dev machine only (not production/staging).
Results: 32/32 automated checks passed after two fixes (below). Screenshots were reviewed manually for the expanded layout only.

| Check | Result |
|---|---|
| 7 groups render; only the active group is open on load | Pass |
| Active item highlighted; `aria-current` set | Pass |
| Each of 7 accordions opens and closes the others | Pass |
| Persistence after refresh (collapsed state; only active group open) | Pass |
| `/` focuses search; filtering; empty-result message | Pass |
| ArrowDown moves focus; Enter toggles group | Pass |
| Sidebar fits viewport; no page scroll; no horizontal scroll | Pass (1366x768) |
| Short viewport (520px): nav scrolls internally, header/footer stay visible | Pass |
| Collapsed icon-only mode (72px), labels hidden, `title` tooltips, persists | Pass |
| Clicking a group icon while collapsed re-expands the sidebar | Pass |
| All 46 nav links return < 400 and do not redirect to login (super admin) | Pass |
| Mobile 390px: drawer hidden, opens, no horizontal scroll, backdrop closes | Pass |
| JavaScript disabled: all 45 sub-links visible | Pass |
| No JS page errors | Pass |
| Limited-permission user (`applications.view`, `candidates.view`) sees only Recruitment + System, with live badge counts | Pass |

## Defects found by browser testing and fixed
1. Collapse toggle did nothing: the script ran before the header button existed. Now binds on DOMContentLoaded.
2. After refresh two groups could be open (saved state + active). Now only the active group opens.
3. These were not caught by PHPUnit — browser verification was necessary.

## Not verified
- Real-device touch, Safari/Firefox, screen reader. Collapsed-mode screenshot not visually reviewed.
- Escape key has no dedicated behaviour (Escape in the search box clears it only via native `type=search` handling).
- Other pages still show the page-content "Cron Worker: Active" badge on `/admin/social/queue`; see System Health work.
