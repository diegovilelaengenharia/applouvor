---
phase: 05-musica-modernizada
plan: "02"
subsystem: dashboard
tags: [dashboard, suggestions, badge, php]
completed: 2026-05-17
commit: 354ac9c
requirements: [MUS-03]
---

# Phase 5 Plan 02: Badge de Sugestões Pendentes no Dashboard

**One-liner:** Fixed sugestoes_count query to use song_suggestions table instead of avisos, and added an orange admin-only badge in the dashboard linking to sugestoes_musicas.php when pending suggestions exist.

## What Was Done

- `admin/dashboard_data.php`: changed `avisos WHERE category = 'Sugestão'` to `song_suggestions WHERE status = 'pending'`
- Added `$pendingSuggestions = ($userRole === 'admin') ? (int)$historicoData['sugestoes_count'] : 0` variable
- Exposed `pendingSuggestions` in the return array so index.php can access it via `extract()`
- `admin/index.php`: added orange badge block before the category rendering loop
- Badge only renders when `$pendingSuggestions > 0 && $_SESSION['user_role'] === 'admin'`
- Badge shows pill count + text "X sugestão(ões) de música pendente(s)" + chevron arrow
- Links directly to `sugestoes_musicas.php`
- Non-admin users get `$pendingSuggestions = 0` — badge never renders for musicians

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED

- `admin/dashboard_data.php` contains `song_suggestions WHERE status = 'pending'` and `$pendingSuggestions`
- `admin/index.php` contains `$pendingSuggestions` and link to `sugestoes_musicas.php`
- Commit 354ac9c verified in git log
