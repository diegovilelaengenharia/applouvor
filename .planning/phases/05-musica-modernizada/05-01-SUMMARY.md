---
phase: 05-musica-modernizada
plan: "01"
subsystem: musica-detalhe
tags: [ui, platform-branding, php]
completed: 2026-05-17
commit: 95ded72
requirements: [MUS-01, MUS-02]
---

# Phase 5 Plan 01: Branding de Plataforma em musica_detalhe.php

**One-liner:** Platform-branded link cards with SVG icons and colored borders (Spotify green, YouTube red, Cifra Club orange, Letras indigo) plus highlighted Tom/BPM/Duração stat boxes in musica_detalhe.php.

## What Was Done

- Added `detectPlatform(string $url, string $type): array` helper function at top of `admin/musica_detalhe.php`
- Function detects: Spotify (#1db954), Deezer (#a238ff), YouTube (#ff0000), Cifra Club (#f97316), Letras (#6366f1) from URL content
- Replaced static Tab 3 `.links-grid` with dynamic PHP loop rendering branded cards
- Each card has: colored border, background tint, platform SVG icon, platform name, "Acessar →" or "Não cadastrado"
- Missing URLs: opacity 0.45, `onclick="return false"` (not clickable), neutral grey border
- Present URLs: `target="_blank" rel="noopener"` for security, hover shadow effect
- `htmlspecialchars()` on all URL outputs (XSS mitigation)
- Replaced info-grid-row Tom/BPM/Duração `<label>`/`<value>` HTML tags with styled `<div>` blocks
- Tom: `background:#eff6ff; border:1.5px solid #3b82f6`
- BPM: `background:#fff7ed; border:1.5px solid #f97316`
- Duração: `background:#f0fdf4; border:1.5px solid #10b981`

## Deviations from Plan

None — plan executed exactly as written.

## Self-Check: PASSED

- `admin/musica_detalhe.php` exists and contains `detectPlatform`, `spotify`, `youtube`, `opacity:0.45`, `onclick="return false"`, `border:1.5px solid`
- Commit 95ded72 verified in git log
