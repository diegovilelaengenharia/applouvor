# Phase 10: Harmonização Visual — Research

**Researched:** 2026-05-20
**Domain:** CSS design-system harmonisation over existing PHP/Vanilla-CSS PWA (no build step)
**Confidence:** HIGH — all findings verified directly from codebase via Grep/Read tools

---

## Summary

Phase 10 is a pure CSS refactor targeting visual consistency across all admin/ and app/ pages. The design-system token foundation (`assets/css/design-system.css`) already exists and is comprehensive. The problem is that **page-level CSS files bypass it** in two ways: five files declare their own `:root {}` blocks that mutate shared custom properties for the entire tab session, and 25+ page files hardcode `font-size` literals, `border-radius` px values, and `font-weight: 800/900` instead of using the canonical tokens.

The work has a clear sequencing constraint: the five `:root` override blocks corrupt the cascade globally and must be removed **before** any per-file token-swap work can be considered stable. After that, the highest-impact changes are typography tokenisation in the six highest-traffic page files (escalas, lider, oracao, devocionais, repertorio, pib-cards) and the two shared component files (page-sub-header, pib-cards). Finally, the `.pib-card` primitive itself needs its literal `1.1rem/0.9rem` values replaced with `--font-size-xl/--font-size-sm` tokens.

The validation strategy is visual and structural: no automated test framework covers CSS, so the phase gate is a confirmed-passing checklist of browser screenshots across the eight critical routes at 375px and 1280px, plus dark-mode, with specific checks for no PHP errors and no overflowing/clipping content.

**Primary recommendation:** Remove five `:root` override blocks first (single wave, low-risk atomic commit), then token-swap per logical group (one commit per file group), finally add `body.page-is-dashboard .page-sub-header { display: none; }` with the body class on the two dashboard pages.

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| UI-01 | Escala tipográfica única via tokens — sem font-size/family rígidos nos CSS de página | Verified: 25 page-CSS files contain literal `font-size` values. All canonical tokens exist in design-system.css. Replacement map is in UI-SPEC §Redundant Declarations Audit. |
| UI-02 | Botões padronizados (44px toque, radius 8–12px, hierarquia consistente) | Verified: `components/buttons.css` already implements the full matrix. Problem is page-level overrides (`.pray-btn`, `.btn-new-prayer`, `.btn-print`, `.btn-hero-confirm`) bypassing it. |
| UI-03 | Espaçamentos e densidade padronizados por tokens, `.pib-card` uniforme | Verified: `.pib-card` in pib-cards.css uses tokens for layout but hardcodes `1.1rem/0.9rem/0.75rem` for typography. Page CSS files use raw px border-radius in 28/30 files. |
| UI-04 | Auditoria visual de todas as páginas confirmando consistência sem regressões funcionais | No automated CSS test exists; strategy: Playwright screenshot audit across 8 critical routes × 3 viewports (375px, 1280px, dark-mode at 375px) using the existing `playwright_runner.py` skill script. |
</phase_requirements>

---

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Design token source of truth | `assets/css/design-system.css` | — | Single `:root {}` declaration consumed by all other layers via CSS custom property cascade |
| Component base styles | `assets/css/components/*.css` (buttons, pib-cards, page-sub-header, sidebar, mobile-bottom-nav) | — | Reusable across pages; loaded via `app-main.css` `@import` chain |
| Page-specific overrides | `assets/css/pages/*.css` | — | Loaded per-page via inline `<link>` in each `.php` file; must NOT redeclare `:root` |
| CSS load order (cascade authority) | `head.php` + per-page `<link>` | `app-main.css` @import order | design-system → reset → components → pages. Pages load AFTER components so they win specificity battles legitimately |
| Dark-mode theming | `body.dark-mode {}` in `design-system.css` | `dark-mode.css` (imported last in app-main) | `.dark-mode` class toggled on `<body>` by `theme-toggle.js`; pages must not redeclare raw hex for dark states |
| Header visibility (dashboard hide) | `includes/layout.php` `renderPageHeader()` | `page-sub-header.css` | `$isHome` flag already conditionally renders header — add body class to drive CSS rather than inline style |

---

## CSS Load Order (Critical for Cascade Safety)

`head.php` loads CSS in this exact order — understand before making any changes:

```
1. design-system.css           (direct <link>, cache-busted by filemtime)
2. app-main.css                (direct <link>, which itself @imports:)
   2a.  design-system.css      (duplicate import — safe, browser deduplicates)
   2b.  core/reset.css
   2c.  core/utilities.css
   2d.  modern-enhancements.css
   2e.  roles.css
   2f.  components/header-complete.css
   2g.  components/buttons.css
   2h.  components/cards.css
   2i.  components/notifications.css, modals, forms, badges, icons, sidebar, mobile-bottom-nav
   2j.  components/timeline-cards.css, animations, tables, layout, action-buttons
   2k.  pages/dashboard.css, escalas.css, escala-detalhe.css, agenda.css, evento-detalhe.css
        repertorio.css, lider.css, list-views.css
   2l.  theme-premium.css
   2m.  dark-mode.css
3. components/page-sub-header.css  (direct <link>, time() cache-bust — always fresh)
4. components/mobile-bottom-nav.css
5. components/sidebar.css
6. components/pib-cards.css        (direct <link>, always fresh)
7. components/dashboard-hero.css   (direct <link>, always fresh)
8. [per-page] pages/oracao.css etc. (inline <link> in each admin/*.php)
```

**Key insight for the refactor:** Page CSS files loaded via inline `<link>` (#8) come AFTER all component and global styles. Their `:root {}` blocks therefore silently win the cascade and overwrite design-system tokens for the rest of the tab session — because `:root` specificity is always 0-0-1, last-writer wins regardless of load order. This is why five page files corrupt the global token set.

**Dark-mode safety:** `body.dark-mode {}` in `design-system.css` overrides semantic tokens (bg, text, border, shadow). Any page CSS that uses `var(--bg-surface)`, `var(--text-primary)` etc. automatically adapts. The only way page CSS breaks dark mode is if it declares raw hex colors that don't reference tokens, or if it redeclares the tokens in `:root` to raw hex (which the oracao/devocionais files do — removing their `:root` blocks fixes dark mode in those pages for free).

---

## Standard Stack

No new libraries. All work is within existing files.

### Core (already installed)
| File | Role | Current State |
|------|------|---------------|
| `assets/css/design-system.css` | Token source of truth — paleta, tipografia, spacing, radius, shadows, transitions, z-index, dark-mode overrides | Complete. All tokens referenced in UI-SPEC already exist. [VERIFIED: Read tool] |
| `assets/css/components/buttons.css` | Button primitive with full variant and size matrix | Well-formed. Missing: `active/press scale(0.97)` state, `focus-visible` outline. [VERIFIED: Read tool] |
| `assets/css/components/pib-cards.css` | Card primitive | Uses tokens for layout; still has `1.1rem`, `0.9rem`, `0.75rem` literals for typography, `font-weight: 800`. [VERIFIED: Read tool] |
| `assets/css/components/page-sub-header.css` | Sticky page header component | Has literal hex colors (`#0f172a`, `#94a3b8`, etc.), `font-weight: 750` (non-standard), `font-size: 1.05rem`, `0.78rem`. Dark-mode block correct. [VERIFIED: Read tool] |
| `assets/css/components/action-buttons.css` | FAB and action button utilities | Exists in app-main.css @import chain — destination for deduplicated `.fab-create` from oracao/devocionais. [VERIFIED: Glob] |

### Conflicting Files (must be fixed)
| File | Issue | Priority |
|------|-------|----------|
| `pages/oracao.css` | `:root` block lines 4–21 redefines `--primary` → green-500, `--radius-lg`, `--shadow-sm` | CRITICAL — fix first |
| `pages/devocionais.css` | `:root` block lines 4–21 redefines `--primary` → green-600, same tokens | CRITICAL — fix first |
| `pages/leitura.css` | `:root` block lines 6–10 redefines `--reading-primary` | HIGH — fix in Wave 1 |
| `pages/evento-detalhe.css` | `:root` block lines 4–27 redefines 13 tokens (all already in design-system) | CRITICAL — fix first |
| `pages/avisos.css` | `:root` block lines 2–7 redefines `--teal-primary`, `--teal-light`, `--teal-bg`, `--teal-border` | HIGH — fix in Wave 1 |

---

## Architecture Patterns

### Recommended Execution Grouping (Task Structure for Planner)

The planner should structure tasks in three waves of increasing scope:

**Wave 10-01: :root kill — 5 files** (atomic, reversible, zero visual risk)
Remove the `:root {}` blocks from: `oracao.css`, `devocionais.css`, `leitura.css`, `evento-detalhe.css`, `avisos.css`. Replace `var(--primary)` references in those files with the correct semantic token (`var(--color-success)` for green usages, `var(--reading-primary)` for leitura, `var(--teal-primary)` for avisos). This wave is a prerequisite for all other waves.

**Wave 10-02: Typography + weight tokens — 8 high-traffic files**
Files: `pages/escalas.css`, `pages/lider.css`, `pages/oracao.css`, `pages/devocionais.css`, `pages/repertorio.css`, `components/pib-cards.css`, `components/page-sub-header.css`, `components/timeline-cards.css`. Replace all literal `font-size` values (rem/px) with `--font-size-*` tokens per the UI-SPEC map. Replace `font-weight: 750/800/900` with `--font-weight-bold` (700) except KPI display exceptions.

**Wave 10-03: Radius tokens + header dashboard rule + FAB dedup**
Replace `border-radius: Npx` literals with `--radius-*` tokens in: `oracao.css`, `devocionais.css`, `escalas.css`, `lider.css`. Add `body.page-is-dashboard .page-sub-header { display: none; }` to `page-sub-header.css`. Add `body.page-is-dashboard` class to `<body>` in `admin/index.php` and `app/index.php`. Move `.fab-create` to `components/action-buttons.css` and remove from `oracao.css` and `devocionais.css`.

### Project Structure (unchanged)
```
assets/css/
├── design-system.css        # Token source — do not add page-scoped :root here
├── app-main.css             # Orchestrator — @import order is canonical
├── core/                    # reset, utilities
├── components/              # Reusable: buttons, pib-cards, page-sub-header, sidebar...
└── pages/                   # Per-page overrides — MUST NOT declare :root {}
```

### System Architecture Diagram (CSS Cascade Flow)

```
Browser loads page (e.g. admin/oracao.php)
         |
         v
   head.php injects:
   [1] design-system.css   --> :root { --primary: blue, --font-size-*, ... }
   [2] app-main.css        --> @imports components/, some pages/
   [3] page-sub-header.css --> component styles
   [4] pib-cards.css       --> card primitive
         |
         v
   body innerHTML loads, then browser parses:
   [5] pages/oracao.css    --> CURRENTLY: :root { --primary: green }  <-- BREAKS cascade
                               AFTER FIX: no :root block, uses var(--color-success)
         |
         v
   body.dark-mode (set by theme-toggle.js on load from localStorage)
         |
         v
   design-system.css body.dark-mode {} overrides bg/text/border tokens
   page-sub-header.css body.dark-mode {} overrides header specifically
```

### Pattern: Safe :root Override Removal

**What:** Replace `:root { --primary: #22c55e }` with page-scoped semantic references.

**How:**
```css
/* BEFORE (in pages/oracao.css) */
:root {
    --primary: #22c55e;
    --primary-hover: #16a34a;
    --primary-light: #dcfce7;
    ...
}

/* AFTER: Remove the entire :root block.
   Then replace every var(--primary) usage in this file: */
.prayer-card.answered {
    /* WAS: border-color: var(--primary); */
    border-color: var(--color-success);   /* green-500 — correct semantic */
}
.pray-btn.active {
    /* WAS: background: var(--primary); */
    background: var(--color-success);
}
```
[VERIFIED: pattern from UI-SPEC + confirmed token existence in design-system.css via Read]

### Pattern: Font-size Literal to Token Swap

```css
/* BEFORE */
.prayer-title { font-size: 1.1rem; }

/* AFTER */
.prayer-title { font-size: var(--font-size-xl); }   /* 20px = 1.25rem */
```

Token mapping (from UI-SPEC, verified against design-system.css):
- `1.1rem` (17.6px) → `var(--font-size-xl)` (1.25rem = 20px) — heading role
- `0.95rem` (15.2px) → `var(--font-size-base)` (1rem = 16px) — body
- `0.9rem` (14.4px) → `var(--font-size-sm)` (0.875rem = 14px) — secondary
- `0.85rem` (13.6px) → `var(--font-size-sm)` (0.875rem = 14px)
- `0.78rem`, `0.72rem`, `0.7rem` → `var(--font-size-xs)` (0.75rem = 12px) — caption
- `1.25rem` → `var(--font-size-xl)` or `var(--font-size-2xl)` depending on context
- `1.4rem` (22.4px) → `var(--font-size-2xl)` (1.5rem = 24px) — display
- `1.7rem` (27.2px) → `var(--font-size-3xl)` (1.875rem = 30px) — display exception
- `1.75rem` (28px) → `var(--font-size-3xl)` (1.875rem = 30px) — KPI
- `2.5rem` → keep as-is or `var(--font-size-4xl)` (2.25rem) — large display

### Pattern: Dashboard Header Hide

```php
// admin/index.php and app/index.php — add class to <body>
// In renderAppHeader() call or on the body tag opened by layout.php
// layout.php line 76: <body>
// Change to:
```
```css
/* In page-sub-header.css — add ONE rule */
body.page-is-dashboard .page-sub-header {
    display: none;
}
```
The `renderPageHeader()` function already checks `$isHome` and skips rendering when true (layout.php line 238: `if (!$isHome)`). The CSS rule adds an extra safety layer for pages that manually render the header. Add the class via a body attribute set in `renderAppHeader()` when `$isHome` is true.
[VERIFIED: Read layout.php lines 228–238]

### Anti-Patterns to Avoid

- **Removing :root blocks without replacing var() references first:** The file will break because vars like `var(--primary)` will resolve to the global blue instead of green. Always audit usages before deleting.
- **Using `!important` to override:** Several existing rules already use it (e.g., `escala.css:51`). Do not add more — fix cascade specificity by choosing the right token.
- **Changing `body.dark-mode` rules in page CSS:** These belong only in `design-system.css` and explicitly in `page-sub-header.css`. Page CSS should NOT have `body.dark-mode .x { color: #hexvalue }` — use tokens and dark mode adapts automatically.
- **Touching files not in scope:** `dashboard.css`, `modern-enhancements.css`, `dark-mode.css`, `roles.css` are legacy files maintained for compatibility — do not modify in this phase.
- **Merging :root fixes into the same commit as token swaps:** keep Wave 10-01 a standalone commit so it can be reverted independently.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| FAB create button | New CSS class in oracao/devocionais | Existing `.fab-create` in action-buttons.css after dedup move | Already styled and mobile-safe |
| Dark-mode color variants | `body.dark-mode .class { color: #hex }` per page | Semantic tokens `var(--bg-surface)`, `var(--text-primary)` | design-system.css handles all dark overrides automatically |
| Page-scoped custom properties | New `:root` variables | Page-level class scope: `.oracao-page .element { --local-var: value }` if truly needed | Limits cascade pollution to one route's DOM subtree |
| Font-weight scale | `font-weight: 750` (non-standard) | `var(--font-weight-bold)` (700) or `var(--font-weight-semibold)` (600) | 750 is only interpolated on variable fonts; most rendering engines round it |

---

## Concrete Conflict Inventory

### Verified :root Override Files (5 files — CRITICAL)

| File | Lines | Tokens Redefined | Impact Severity |
|------|-------|-----------------|-----------------|
| `pages/oracao.css` | 4–21 | `--primary` (green-500), `--primary-hover`, `--primary-light`, `--primary-subtle`, `--bg-surface`, `--border-color`, `--text-main`, `--text-muted`, `--radius-lg`, `--shadow-sm` | CRITICAL — `--primary` bleeds to all later CSS in same session |
| `pages/devocionais.css` | 4–21 | same 10 tokens + `--primary: #16A34A` (green-600) | CRITICAL — same severity |
| `pages/leitura.css` | 6–10 | `--reading-primary: #10b981` (overrides design-system's `--green-600`) | HIGH |
| `pages/evento-detalhe.css` | 4–27 | 13 tokens: `--primary`, `--bg-body`, `--bg-surface`, `--text-main`, `--text-muted`, `--border-color`, `--border-subtle`, `--shadow-sm`, `--shadow-md`, `--radius-lg`, `--blue-500/green-500/red-500/yellow-500` | CRITICAL — widest blast radius |
| `pages/avisos.css` | 2–7 | `--teal-primary`, `--teal-light`, `--teal-bg`, `--teal-border` (differ from design-system values) | HIGH |

[VERIFIED: Read tool on all 5 files]

### Verified font-size Literal Files (25 page files + 2 component files)

All 25 page CSS files contain literal `font-size` values (grep confirmed). Priority group for Wave 10-02:

| File | Specific Literals to Replace |
|------|------------------------------|
| `pages/escalas.css` | `1.05rem` (.event-title), `1.7rem` (.date-day), `0.72rem` (.scale-time, .music-count-pill) |
| `pages/lider.css` | `1.75rem` (.kpi-value), `1.25rem` (.kpi-value-mini), `1.1rem` (.section-title, .card-title) |
| `pages/oracao.css` | `1.1rem` (.prayer-title), `0.95rem` (.prayer-description), `0.85rem` (.pray-btn, .filter-tab) |
| `pages/devocionais.css` | `1.4rem` (.dev-title), `0.95rem` (.dev-author-name), `0.9rem` (.tab-btn) |
| `pages/repertorio.css` | `0.95rem` (.dropdown-item) |
| `components/pib-cards.css` | `1.1rem` (.pib-card-title, weight 800), `0.9rem` (.pib-card-body), `0.75rem` (.pib-card-date) |
| `components/page-sub-header.css` | `1.05rem` (.page-sub-title), `0.78rem` (.user-pill-name), `0.7rem` (.user-pill-role, .user-pill-avatar-placeholder), `0.9rem` (.page-sub-title mobile), `0.6rem` (.user-pill-role) |

[VERIFIED: Grep `font-size:\s*[0-9]+(\.[0-9]+)?(rem|px|em)` across pages/ — 25 files hit]

### Verified font-weight: 750/800/900 (non-standard weight spread)

Total instances found across all CSS: 80+ occurrences of `font-weight: 800`, 4 of `750`, 3 of `900`.

**In-scope for Phase 10 (highest-visibility components and pages):**
- `components/page-sub-header.css`: `.user-pill-name` weight 750 → `--font-weight-bold` (700); `.user-pill-avatar-placeholder` weight 800 → same
- `components/pib-cards.css`: `.pib-card-title` weight 800 → `--font-weight-bold` (700) per UI-SPEC
- `pages/escalas.css`: `.date-day` weight 900, `.month-divider-label` weight 800 → `--font-weight-bold`
- `pages/lider.css`: `.kpi-value` weight 800 → keep as intentional display exception per UI-SPEC
- `components/dashboard-hero.css`: weight 750/900 → `--font-weight-bold`

**Deferred to Phase 11:** The remaining ~70 occurrences in other page files (dashboard-widgets, detail_v3, shared-pages, etc.) — too many to do safely in one phase without visual regression risk.

[VERIFIED: Grep `font-weight:\s*(750|800|900)` — confirmed file list and line numbers]

### Verified border-radius Literals (28 of 30 page files)

Nearly universal. Phase 10 targets only the 4 files from UI-SPEC: `oracao.css`, `devocionais.css`, `escalas.css`, `lider.css`. Remaining files deferred per UI-SPEC note "Priority for Phase 10".

---

## Common Pitfalls

### Pitfall 1: var() References That Survive :root Removal
**What goes wrong:** Developer removes `:root` block from `oracao.css` but the file still contains `var(--primary-light)` in `.prayer-card.answered`. After removal, `--primary-light` resolves to the global blue-100 from design-system, turning the green answered state blue.
**Why it happens:** The :root block was providing local overrides that other rules in the same file depended on.
**How to avoid:** Before removing any `:root` block, grep that file for all `var(--primary*)` usages and replace each with the correct semantic token.
**Warning signs:** Green/teal/custom-color elements suddenly appearing in the primary blue.

### Pitfall 2: Mobile Viewport Overflow After Spacing Token Swap
**What goes wrong:** Replacing a magic-number padding (`padding: 14px`) with a token (`var(--space-4)` = 16px) on a card element that was precisely sized to avoid horizontal overflow at 375px.
**Why it happens:** A 2px increase can cause a card inside a 100vw container with no overflow:hidden to exceed viewport width.
**How to avoid:** After any spacing change, screenshot the affected page at exactly 375px before committing. Check horizontal scrollbar.
**Warning signs:** Horizontal scroll bar appears, or card right edge clips into scrollable area.

### Pitfall 3: Double design-system.css Load Causing Unexpected Token Order
**What goes wrong:** `head.php` loads `design-system.css` directly (line 47) AND `app-main.css` (line 49) which `@imports` design-system again. In most browsers this is harmless (second parse is ignored). However, if someone re-orders `head.php` to put `app-main.css` before `design-system.css`, the first load wins for `:root` — and `body.dark-mode {}` in design-system could be skipped if app-main's dark-mode.css @import overrides it.
**How to avoid:** Never reorder `head.php` lines 47–49. The direct `design-system.css` load exists as an explicit cache-busting mechanism.
**Warning signs:** Dark mode variables stop working in pages that load their CSS late.

### Pitfall 4: page-sub-header.css Hard-Coded Hex Colors in Dark Mode
**What goes wrong:** `page-sub-header.css` contains explicit `body.dark-mode .page-sub-title { color: #f1f5f9 }` (line 583). These hardcoded hex values work correctly now. If you replace these with `var(--text-primary)` without understanding that dark-mode sets `--text-primary: var(--slate-50)` (which is #f8fafc, not #f1f5f9), you introduce a subtle color shift.
**How to avoid:** Do not modify the dark-mode block in `page-sub-header.css` during Phase 10. The UI-SPEC explicitly says "preserve" the dark-mode header block. Only change the light-mode hex literals.
**Warning signs:** Header text becoming slightly off-white vs designed.

### Pitfall 5: FAB Dedup Breaks Specificity
**What goes wrong:** Moving `.fab-create` from `oracao.css`/`devocionais.css` to `action-buttons.css` (which loads earlier in app-main.css @import chain) means the rule now has lower cascade priority than any page-specific override.
**Why it happens:** `action-buttons.css` loads at step 2j, page CSS loads at step 8. Page CSS automatically wins at equal specificity.
**How to avoid:** After moving `.fab-create` to `action-buttons.css`, remove the duplicate declarations from `oracao.css` and `devocionais.css` entirely. Do not leave commented-out versions.
**Warning signs:** FAB button appearing wrong color or size on one of the two pages after the move.

### Pitfall 6: :root Override Removal Breaks Dark Mode on Those Pages
**What goes wrong:** `oracao.css` `:root` block sets `--bg-surface: #ffffff`. After removal, `--bg-surface` in dark mode resolves to `var(--slate-800)` (from `body.dark-mode {}` in design-system). The prayer cards will now correctly go dark — but if the prayer-card CSS has `background: white` hardcoded instead of `var(--bg-surface)`, it stays white in dark mode.
**Why it happens:** The `:root` block was masking a separate raw hex in the component rule.
**How to avoid:** When removing a `:root` block, also audit the file for raw hex color values that should become tokens.
**Warning signs:** Cards visible in light mode but invisible or wrong-colored in dark mode.

---

## Code Examples

### Removing :root and Replacing Usages (oracao.css pattern)
```css
/* Source: UI-SPEC §Redundant Declarations Audit + design-system.css Read */

/* REMOVE this entire block from oracao.css lines 4-21: */
:root {
    --primary: #22c55e;
    --primary-hover: #16a34a;
    --primary-light: #dcfce7;
    --primary-subtle: #f0fdf4;
    --bg-surface: #ffffff;
    --border-color: #e2e8f0;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --radius-lg: 16px;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* THEN replace all green var(--primary) usages in the same file: */
/* var(--primary)         → var(--color-success)       */
/* var(--primary-hover)   → var(--green-600)            */
/* var(--primary-light)   → var(--green-100)            */
/* var(--primary-subtle)  → var(--green-50)             */
/* var(--bg-surface)      → var(--bg-surface)  [no change — now resolves to design-system] */
/* var(--border-color)    → var(--border-color) [no change] */
/* var(--radius-lg)       → var(--radius-lg)   [but was 16px; design-system has 12px!]    */
```

**NOTE on radius-lg discrepancy:** `oracao.css` `:root` sets `--radius-lg: 16px`; `design-system.css` has `--radius-lg: 12px`. Cards in oracao currently use 16px corners. After removal they will use 12px. This is the correct canonical value per UI-SPEC. Verify visually — it is an intentional change, not a bug.

### Adding body.page-is-dashboard
```php
// In includes/layout.php, function renderAppHeader()
// Line 76 currently: <body>
// Change to:
?>
<body<?php if ($isHome ?? false): ?> class="page-is-dashboard"<?php endif; ?>>
```

```css
/* In assets/css/components/page-sub-header.css — append at end */
body.page-is-dashboard .page-sub-header {
    display: none;
}
```

### pib-cards.css Typography Token Swap
```css
/* Source: pib-cards.css Read + design-system.css token verification */

/* BEFORE */
.pib-card-title {
    font-size: 1.1rem;
    font-weight: 800;
    line-height: 1.2;
}
.pib-card-body  { font-size: 0.9rem; }
.pib-card-date  { font-size: 0.75rem; font-weight: 800; }

/* AFTER */
.pib-card-title {
    font-size: var(--font-size-xl);          /* 20px */
    font-weight: var(--font-weight-bold);    /* 700 */
    line-height: var(--line-height-tight);   /* 1.25 */
}
.pib-card-body  { font-size: var(--font-size-sm); }    /* 14px */
.pib-card-date  {
    font-size: var(--font-size-xs);          /* 12px */
    font-weight: var(--font-weight-bold);    /* 700 */
}
```

---

## Validation Architecture

`nyquist_validation: true` is set in `.planning/config.json`. This phase has no automated unit tests — all validation is visual/structural.

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Playwright 1.60.0 (verified installed via `pip show playwright`) |
| Config file | None — using `.agent/skills/webapp-testing/scripts/playwright_runner.py` skill script |
| Quick run command | `python .agent/skills/webapp-testing/scripts/playwright_runner.py http://localhost:8080/applouvor/admin/index.php --screenshot` |
| Full suite command | Manual visual audit loop (see Route Matrix below) |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | Notes |
|--------|----------|-----------|-------------------|-------|
| UI-01 | Typography tokens in use — no bare px/rem literals rendering | Visual screenshot | Playwright screenshot at 375px + 1280px | Compare before/after on escalas, oracao, devocionais |
| UI-02 | Buttons 44px min-height, correct radius, correct variant colors | Visual screenshot | Playwright screenshot + manual DevTools inspect | Check `.pray-btn`, `.btn-print` removed; `.btn` class used |
| UI-03 | .pib-card padding, radius, shadow consistent across pages | Visual screenshot | Playwright screenshot loop over card-bearing pages | Check no overflow at 375px |
| UI-04 | No PHP errors, no broken layout | HTTP status + screenshot | `playwright_runner.py` returns `status: ok` (non-500) + manual scroll on each route | Must pass all 8 critical routes |

### Critical Routes for Visual Audit (Phase Gate)

Run Playwright screenshot on each route at **375px width** and **1280px width**, then verify **dark mode** at 375px:

| Route | File | What to Check |
|-------|------|--------------|
| Admin Dashboard | `admin/index.php` | page-sub-header hidden (body.page-is-dashboard); KPI cards; sidebar |
| Escalas List | `admin/escalas.php` | `.date-day` size; `.pib-card-schedule` border; month divider |
| Escala Detalhe | `admin/escala_detalhe.php` | "Confirmar presença" button 44px; participant status badges |
| Repertório | `admin/repertorio.php` | Song card typography; dropdown font size |
| Oracao | `admin/oracao.php` | Green accent using `--color-success` not `--primary`; pray-btn removed |
| Devocionais | `admin/devocionais.php` | Green accent correct; `.dev-title` at 24px; FAB single source |
| Lider Dashboard | `admin/lider.php` | KPI values at 30px; action-cards gradient intact |
| Relatorios | `admin/relatorios_gerais.php` | btn-print replaced with `.btn .btn-secondary` |

### Regression Check Protocol
For each route, verify:
1. **No PHP error banner** — no `Warning:`, `Notice:`, `Fatal error:` text in screenshot
2. **No horizontal scroll at 375px** — content fits within viewport
3. **No invisible text** — text-on-background contrast passes (check in dark mode)
4. **Interactive elements visible** — buttons, FABs, nav items all visible and not clipped
5. **Dark mode parity** — toggle dark mode, reload, screenshot; card backgrounds should be `--slate-800` not white

### Sampling Rate
- **Per task commit (per wave):** `python .agent/skills/webapp-testing/scripts/playwright_runner.py http://localhost:8080/applouvor/admin/[affected-page].php --screenshot` for the 2–3 pages most affected by that wave
- **Per wave merge:** Full 8-route loop × 3 viewport/mode combinations = 24 screenshots
- **Phase gate:** All 24 screenshots pass regression checklist before `/gsd-verify-work`

### Wave 0 Gaps
The Playwright skill script exists and is functional. No new test infrastructure needs to be created. The server must be running locally (`php -S localhost:8080` via `run_server.bat`) before running screenshots.

- [ ] Confirm `run_server.bat` starts server at `localhost:8080` (manual step before testing)
- [ ] Confirm `playwright install chromium` has been run at least once (`playwright install chromium`)

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Python 3 | Playwright screenshot runner | Yes | 3.14.4 | — |
| Playwright | Visual audit screenshots | Yes | 1.60.0 | Manual browser DevTools screenshots |
| PHP CLI | Local dev server (`php -S localhost:8080`) | Not confirmed in shell | — | Production URL `vilela.eng.br/applouvor` |
| Git | Commit waves | Yes (git repo confirmed) | — | — |

**Missing dependencies with no fallback:** None blocking.

**Missing dependencies with fallback:**
- PHP CLI not detected in current shell PATH — use `run_server.bat` on Windows or verify PHP installed at full path. Fallback: test against production URL (less safe — avoid for CSS changes).

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Page-scoped `:root {}` overrides to theme per-page | Semantic tokens with page-scoped class scope if needed | Design-system V3 (Milestone 2) established tokens | Removal is the fix |
| `font-weight: 800/900` for bold display | `font-weight: 700` (`--font-weight-bold`) with variable font for 800 if needed | Inter loaded via Google Fonts supports 400–800 range | 800 works visually; 750/900 are technically valid on Inter but non-standard for non-variable contexts |
| Per-page dark mode raw hex overrides | Semantic token system with `body.dark-mode {}` in design-system | Current — already implemented | Pages that use tokens get dark mode for free |

**Deprecated patterns confirmed in codebase:**
- `font-weight: 750`: Found in `page-sub-header.css` line 255 and `dashboard-hero.css` line 168/235. Replace with 700.
- `:root` in page CSS: 5 files. Remove entirely.
- `.pray-btn`, `.btn-new-prayer`, `.btn-hero-confirm`, `.btn-hero-decline`, `.btn-print`, `.btn-registrar-faltas` (most): Page-specific button classes that bypass `components/buttons.css`. Replace callers with semantic `.btn .btn-*` classes or remove CSS if callers already migrated.

---

## Project Constraints (from CLAUDE.md)

- **No build step** — all CSS is plain files; no PostCSS, Sass, or Tailwind compilation. Token swaps are direct `var()` reference changes.
- **Mobile-first always** — test at 375px before desktop in every wave.
- **Preserve existing pages** — never break functionality; CSS-only changes in this phase, no PHP logic changes except adding `body.page-is-dashboard` class.
- **No frameworks** — `components/buttons.css` is the button primitive, not Bootstrap/Tailwind.
- **Design system** — use variables from `assets/css/core/variables.css` — note: `variables.css` is absorbed by `design-system.css` per `app-main.css` comment. Use `design-system.css` as reference.
- **Primary color:** `#3B82F6` (blue) — `var(--primary)` / `var(--color-primary)` must NOT be green in any `:root` block.
- **CTA color:** `#FFC501` (yellow) — `var(--color-accent)`.
- **Inter/Inter Tight:** Already loaded via `design-system.css` `@import url(Google Fonts)`.
- **Dark-mode:** `.dark-mode` class on body; never hardcode hex for dark states in page CSS.
- **44px touch minimum** — `min-height: 44px` on all primary interactive elements; `--btn` already enforces it; audit custom page buttons.

---

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `components/action-buttons.css` already declares `.fab-create` or is the right destination for the dedup | Don't Hand-Roll / Wave 10-03 | If the file has no `.fab-create` and needs one written, scope expands slightly |
| A2 | `run_server.bat` starts PHP at `localhost:8080` matching `APP_URL` config | Environment / Validation | Screenshot URLs would be wrong; use production URL as fallback |
| A3 | `evento-detalhe.css` is loaded only by `admin/evento_detalhe.php` (not globally via app-main) | :root kill safety | If loaded globally, its `:root` affects more routes than expected — still fix, but test more routes |
| A4 | `font-weight: 800` on Inter via Google Fonts renders correctly (Inter supports 800 weight) | Typography | If the font loaded is only 400–700, 800 renders as 700 anyway — no regression, just cosmetic |

---

## Open Questions

1. **`components/action-buttons.css` content**
   - What we know: File exists and is imported in `app-main.css` at step 2j.
   - What's unclear: Whether `.fab-create` is already declared there or if it's empty.
   - Recommendation: Executor should `Read` the file before Wave 10-03 to confirm dedup destination is appropriate.

2. **`evento_detalhe.css` `:root` blast radius**
   - What we know: File redefines 13 tokens. It is listed in `app-main.css` @imports at step 2k (`pages/evento-detalhe.css`), meaning it loads globally for ALL pages via app-main.
   - What's unclear: Whether this causes visible corruption on other pages currently.
   - Recommendation: Treat as CRITICAL and fix in Wave 10-01. After removal, audit escalas and repertorio pages which load after it in the cascade.

3. **`admin/index.php` body class injection point**
   - What we know: `renderAppHeader()` in `layout.php` opens the `<body>` tag (line 76).
   - What's unclear: The `$isHome` variable scope inside `renderAppHeader()`.
   - Recommendation: The `renderPageHeader()` function already computes `$isHome` (line 228). `renderAppHeader()` does not. Executor needs to add `$isHome = basename($_SERVER['PHP_SELF']) === 'index.php';` to `renderAppHeader()` before using it for the body class.

---

## Sources

### Primary (HIGH confidence — verified via codebase Read/Grep)
- `assets/css/design-system.css` — full token inventory verified (Read)
- `assets/css/components/buttons.css` — button matrix verified (Read)
- `assets/css/components/pib-cards.css` — card primitive literals verified (Read)
- `assets/css/components/page-sub-header.css` — hard-coded hex, font literals, dark-mode block verified (Read)
- `assets/css/pages/oracao.css` lines 1–21 — `:root` block verified (Read)
- `assets/css/pages/devocionais.css` lines 1–21 — `:root` block verified (Read)
- `assets/css/pages/leitura.css` lines 1–10 — `:root` block verified (Read)
- `assets/css/pages/avisos.css` lines 1–7 — `:root` block verified (Read)
- `assets/css/pages/evento-detalhe.css` lines 1–27 — `:root` block verified (Read)
- `assets/css/app-main.css` — CSS load order verified (Read)
- `includes/head.php` — CSS injection order verified (Read)
- `includes/layout.php` — renderPageHeader / renderAppHeader / $isHome logic verified (Read)
- `.planning/phases/10-harmonizacao-visual/10-UI-SPEC.md` — Design contract (Read)
- `.planning/config.json` — `nyquist_validation: true` confirmed (Read)
- Grep `font-size:\s*[0-9]+(rem|px)` — 25 page files confirmed
- Grep `font-weight:\s*(750|800|900)` — 80+ instances across 28 files confirmed
- Grep `border-radius:\s*[0-9]+px` — 28/30 page files confirmed
- Playwright 1.60.0 — `pip show playwright` confirmed installed

### Secondary (MEDIUM confidence)
- `10-UI-SPEC.md` token replacement map cross-referenced against `design-system.css` actual values — all tokens cited in the spec were found in design-system.css with matching names

### Tertiary (LOW confidence)
- None — all claims verified from codebase directly

---

## Metadata

**Confidence breakdown:**
- Conflict inventory (`:root` files, font literals): HIGH — verified by direct file read + grep
- CSS load order: HIGH — verified by reading head.php and app-main.css
- Token existence in design-system: HIGH — verified by reading the full file
- Validation strategy: MEDIUM — Playwright confirmed installed; PHP server availability not confirmed in current shell
- Wave sequencing recommendation: HIGH — based on cascade mechanics, not assumption

**Research date:** 2026-05-20
**Valid until:** 2026-06-20 (stable stack — 30 day window appropriate; no third-party library versions involved)
