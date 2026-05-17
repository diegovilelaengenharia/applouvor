---
plan: 03-04
phase: 03-roteiro
status: completed
completed_at: 2026-05-17
---

# Plan 03-04 Summary — Custom Tone Integration

## Deliverables

- `assets/css/pages/detail_v3.css` — .song-tone-badge base + .song-tone-default (gray) + .song-tone-custom (orange, ::before ✦)
- `admin/escala_detalhe.php` — song cards in view mode use $customToneMap for tone resolution; .song-tone-custom badge when custom_tone set, .song-tone-default for default tone; onRoteiroSongChange auto-fills custom_tone placeholder

## Decisions

- $customToneMap lookup uses $song['song_id'] (already int from query) — no extra cast needed at lookup
- $toneDisplay falls back to $song['tone'] when no custom_tone in map — preserves existing UX
- Badge hidden when no tone available ($toneDisplay empty) — clean for non-music items
- Orange badge (#c2410c / rgba(249,115,22)) chosen to visually differ from primary blue of roteiro-view-tone
- ::before "✦" marker distinguishes custom badge from default without extra text
