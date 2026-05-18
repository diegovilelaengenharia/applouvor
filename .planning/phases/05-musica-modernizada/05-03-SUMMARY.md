---
phase: 05-musica-modernizada
plan: "03"
subsystem: escalas
tags: [setlist, print, share, php, new-file]
completed: 2026-05-17
commit: 7fe651a
requirements: [MUS-04]
---

# Phase 5 Plan 03: Página de Setlist para Impressão/Compartilhamento

**One-liner:** Created admin/escala_setlist.php — clean printable setlist page with Web Share API, clipboard fallback, @media print CSS, and custom_tone override from schedule_roteiro; added Setlist link button in escala_detalhe.php.

## What Was Done

**New file `admin/escala_setlist.php`:**
- `checkLogin()` guard at top
- Validates `?id=` param as integer; redirects to escalas.php if missing or not found
- Queries `schedule_songs JOIN songs LEFT JOIN schedule_roteiro` for `COALESCE(r.custom_tone, s.tone) as display_tone`
- Header shows: church name, event type, day name + date + time
- Song list shows: number, title, artist, tone badge (blue), BPM in small text
- Empty state message when no songs
- Fixed `.action-bar` with "Imprimir" (`window.print()`) and "Compartilhar" buttons
- `shareSetlist()`: uses `navigator.share` on mobile; falls back to `navigator.clipboard.writeText(url)` on desktop with button feedback
- `@media print` CSS hides .action-bar, header, app-header; removes box-shadows; `break-inside: avoid` per song
- All user data protected with `htmlspecialchars()` including JS string (`addslashes()` for event_type in JS context)

**Modified `admin/escala_detalhe.php`:**
- Added Setlist link button inside `.event-info-card`, below the event details, above the notes
- Button uses `(int)$id` for safe ID injection
- Inline SVG document icon; primary blue border and text color

## Deviations from Plan

None — plan executed exactly as written. Removed emoji characters (printer/share icons) from button text per CLAUDE.md "no emojis" convention.

## Self-Check: PASSED

- `admin/escala_setlist.php` created, contains `window.print()`, `navigator.share`, `@media print`, `checkLogin()`
- `admin/escala_detalhe.php` contains `escala_setlist.php?id=` and `(int)$id`
- Commit 7fe651a verified in git log
