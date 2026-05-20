---
phase: 10
slug: harmonizacao-visual
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-05-20
---

# Phase 10 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.
> This is a CSS/design-system harmonization phase — verification is **visual + non-regression**, not unit testing. The feedback signal is: (a) no PHP errors / HTTP 200 on every route, (b) no broken layout, (c) tokens applied consistently at 375px + desktop + dark-mode.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Playwright 1.60.0 (Python) — visual route audit + screenshots |
| **Config file** | `.agent/skills/webapp-testing/scripts/playwright_runner.py` (existing project script) |
| **Quick run command** | `python verify_all_pages.py` (route audit — HTTP 200 + PHP error scan, all admin/ + app/ routes) |
| **Full suite command** | `python verify_all_pages.py --screenshots --viewports 375,1280 --dark` (24 screenshots: 8 critical routes × 375px + 1280px + dark-mode@375px) |
| **Estimated runtime** | ~60–120 seconds (full screenshot pass) |

> Local server: `php -S localhost:8080` via `run_server.bat` must be running before the audit.

---

## Sampling Rate

- **After every task commit:** Run quick route audit on the routes touched by that task (HTTP 200 + no PHP error/`SQLSTATE`/`Fatal error` in output).
- **After every plan wave:** Run the route audit across ALL routes (regression sweep — a `:root` or token change in one file can corrupt later-loaded pages).
- **Before `/gsd-verify-work`:** Full screenshot suite (375px + 1280px + dark-mode) must pass the 5-point regression checklist with zero PHP errors.
- **Max feedback latency:** ~120 seconds.

### 5-Point Regression Checklist (per screenshot)
1. No PHP error / warning / `SQLSTATE` / `Fatal error` text rendered on page.
2. No element overflow / horizontal scroll at 375px.
3. Text legible in both light and dark mode (no white-on-white / dark-on-dark).
4. Buttons ≥ 44px touch height; consistent radius (8–12px).
5. Typography matches canonical scale (no oversized/undersized headings vs siblings).

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Secure Behavior | Test Type | Automated Command | Status |
|---------|------|------|-------------|-----------------|-----------|-------------------|--------|
| 10-01-* | 01 | 1 | UI-03 | N/A (CSS only) | visual/regression | route audit on oracao, devocionais, leitura, evento-detalhe, avisos + any page loaded after them | ⬜ pending |
| 10-02-* | 02 | 2 | UI-01 | N/A | visual/regression | screenshot diff on escalas, lider, repertorio, devocionais, oracao + pib-cards/page-sub-header consumers @375px+dark | ⬜ pending |
| 10-03-* | 03 | 3 | UI-02 | N/A | visual/regression | screenshot of all primary/secondary/ghost buttons + FAB + header @375px (44px height, 8–12px radius) | ⬜ pending |
| 10-04-* | 04 | 4 | UI-04 | N/A | visual/regression | full 24-screenshot suite across all admin/ + app/ routes, 5-point checklist | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] Confirm `php -S localhost:8080` runs locally (run_server.bat) — required for any audit.
- [ ] Confirm `verify_all_pages.py` (or the existing audit script) runs and can log in as admin + músico to reach all routes.
- [ ] Baseline screenshot set captured BEFORE any change (the "before" reference for regression comparison).

*Wave 0 = capture baseline. No test framework install needed — Playwright already present.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Subjective "harmony" (sizes feel balanced, nothing too big/small) | UI-01..04 | Aesthetic judgment not fully automatable | Diego reviews the 375px + desktop screenshot set side-by-side and confirms balance |
| Dark-mode parity feels intentional | UI-03 | Visual taste | Toggle .dark-mode on 3 representative pages, confirm contrast + token usage |

---

## Validation Sign-Off

- [ ] All tasks map to a route audit or screenshot verification
- [ ] Sampling continuity: regression sweep after every wave (cascade safety)
- [ ] Wave 0 baseline captured before first change
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
