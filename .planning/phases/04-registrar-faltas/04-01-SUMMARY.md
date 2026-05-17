---
phase: 04-registrar-faltas
plan: "01"
status: complete
completed_at: 2026-05-17
commit: 584522a
---

# Summary — Plan 04-01: Registrar Faltas UI

## What Was Done

### Task 1: Migration SQL
Created `database/migrations/004_schedule_users_absences.sql` which:
- Extends the `schedule_users.status` ENUM to include `absent` and `absent_justified`
- Adds `absence_note TEXT NULL` column with a comment explaining admin-only visibility
- Includes a comment explaining the pastoral distinction between the two absence states

### Task 2: "Registrar Faltas" button in escalas.php
Modified `admin/escalas.php` to:
- Wrap each past schedule card in a `.scale-card-wrapper` container `<div>`
- Add an orange "Registrar Faltas" link after the card `<a>` tag
- Link is guarded by `$_SESSION['user_role'] === 'admin'` check — invisible to regular members
- Uses `--orange-500` / `#f97316` as the CTA color per the design system
- Includes a person-add SVG icon inline

### Task 3: admin/registrar_faltas.php
Created full page with:
- `checkAdmin()` guard at the top
- Past-schedule validation: redirects to `escalas.php?erro=escala_futura` if the event hasn't happened yet
- Participant list fetched via JOIN of `schedule_users` + `users`, ordered by name
- Each participant shows: avatar (colored initial), name, instrument
- 3-state toggle buttons: Presente (green active), Faltou (red active), Justificou (amber active)
- Default state: `pending`/`confirmed` → Presente button active
- Optional absence note field — hidden by default, shown when "Faltou" or "Justificou" is toggled
- `saveAbsences()` JS function POSTs `{ schedule_id, participants[] }` to `api/save_absences.php`
- On success: shows green feedback message, redirects to `escalas.php` after 1.2s
- On error: shows red feedback, re-enables save button
- All user data output uses `htmlspecialchars()` — XSS safe
- `SCHEDULE_ID` injected as `(int)` — no string injection risk

### Task 4: assets/css/pages/registrar_faltas.css
Created mobile-first CSS with:
- `.page-container` with `max-width: 600px` and 100px bottom padding for fixed footer
- `.schedule-info-card` with event type badge (blue) and date
- `.feedback-msg` with `.success` (green) and `.error` (red) variants
- `.status-toggle` with 3 button states: default (gray), `.active` (green), `.active.absent` (red), `.active.justified` (amber)
- All touch targets: `min-height: 44px` on toggle buttons, `min-height: 48px` on footer buttons
- `.save-footer` fixed at bottom with `env(safe-area-inset-bottom)` for iOS notch support

## Acceptance Criteria Met

- [x] Migration 004 exists with `absent`/`absent_justified` ENUM values and `absence_note TEXT NULL`
- [x] Link "Registrar Faltas" visible in past schedule cards, admin-only, orange color
- [x] `admin/registrar_faltas.php` passes `php -l` with no syntax errors
- [x] `admin/escalas.php` passes `php -l` with no syntax errors
- [x] Page validates past-date before showing form
- [x] 3-state toggles: Presente / Faltou / Justificou
- [x] Note field appears on Faltou/Justificou, hidden on Presente
- [x] Saves via `fetch()` POST JSON to `api/save_absences.php`
- [x] Redirects to `escalas.php` after 1.2s on success
- [x] CSS mobile-first with `safe-area-inset-bottom` and 44px+ touch targets

## Notes

- The `api/save_absences.php` endpoint is not yet created — it will be built in plan 04-02
- The wrapper `<div class="scale-card-wrapper">` was added in escalas.php because the entire past card was an `<a>` tag, making it impossible to nest another `<a>` (Registrar Faltas link) inside it
- Pre-existing git pack corruption warnings appeared during commit but did not affect the commit itself
