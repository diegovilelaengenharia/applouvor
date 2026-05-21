---
name: dark-mode-architecture
description: How dark mode works in App Louvor and the root-cause bug that was fixed
metadata:
  type: project
---

App Louvor dark mode = **two mechanisms that must stay in sync**:
1. Tailwind `dark:` variants in templates (e.g. `bg-surface dark:bg-deep-navy`). Tailwind
   CDN config is `darkMode: "class"` → variants fire only when `<html>` has class `dark`.
2. Hand-written `body.dark-mode` CSS in `assets/css/dark-mode.css` + `design-system.css`
   (slate scale) and the Stitch tokens in `assets/css/stitch-theme.css` (`:root` light +
   `body.dark-mode` deep-navy).

Root-cause bug (fixed 2026-05-21): the toggle (`assets/js/theme-toggle.js`) added only
`body.dark-mode`, never `dark` on `<html>`, so **no `dark:` variant ever fired**. Also the
Tailwind `colors` were hardcoded light hex (didn't follow the CSS variables). Fixes:
`theme-toggle.js` now toggles `dark` on `documentElement` in sync (+ anti-flash inline
script in `head.php`); Tailwind color tokens now point to `var(--token, <hex-fallback>)`.

Canonical elevated card (from Stitch): `bg-surface-container-lowest dark:bg-surface-container
border border-surface-container-highest rounded-xl p-6 shadow-sm hover:shadow-md`.
In dark, `--surface` (#1A1B1F) == page background, so cards must use a *container* level
for elevation, not `bg-surface`. See [[stitch-design-source]].
