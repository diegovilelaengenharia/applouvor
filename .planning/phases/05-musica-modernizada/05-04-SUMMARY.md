---
phase: 05-musica-modernizada
plan: "04"
subsystem: repertorio
tags: [repertorio, last-played, sql, php]
completed: 2026-05-17
commit: 696a952
requirements: [MUS-05]
---

# Phase 5 Plan 04: Última Data Tocada nos Cards de Músicas

**One-liner:** All three query branches in repertorio.php now LEFT JOIN schedule_songs + schedules for MAX(event_date) as last_played, with GROUP BY s.id, and cards show "Última: dd/mm/yy" or "Nunca tocada" (faded) below the artist name.

## What Was Done

**Query by Tag (tagId branch):**
- Added `MAX(sch.event_date) as last_played` to SELECT
- Added `LEFT JOIN schedule_songs ss ON ss.song_id = s.id`
- Added `LEFT JOIN schedules sch ON sch.id = ss.schedule_id`
- Added `GROUP BY s.id` before ORDER BY

**Query by Tone (tone branch):**
- Changed `SELECT * FROM songs` to `SELECT s.*, MAX(sch.event_date) as last_played FROM songs s`
- Added same LEFT JOINs
- Changed WHERE clause to use `s.tone`, `s.title`, `s.artist` prefixes
- Added `GROUP BY s.id`

**Normal search (default branch):**
- Changed `SELECT * FROM songs` to `SELECT s.*, MAX(sch.event_date) as last_played FROM songs s`
- Added same LEFT JOINs
- Changed WHERE to use `s.title`, `s.artist` prefixes
- Added `GROUP BY s.id`

**Card display:**
- After the artist `<p>` tag, added conditional block
- If `$song['last_played']` is not empty: shows "Última: dd/mm/yy" with `(new DateTime())->format('d/m/y')`
- If empty (never played): shows "Nunca tocada" with `opacity: 0.4` (more faded than last_played)

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED

- `admin/repertorio.php` contains `last_played`, `MAX(sch.event_date)`, `LEFT JOIN schedule_songs`, `GROUP BY s.id`, `Nunca tocada`, `format('d/m/y')`
- All three query paths updated
- Commit 696a952 verified in git log
