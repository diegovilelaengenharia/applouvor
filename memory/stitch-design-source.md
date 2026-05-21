---
name: stitch-design-source
description: Stitch project that is the design source of truth for App Louvor, and how to pull it
metadata:
  type: project
---

The visual design target for App Louvor is the Stitch project **"Modern Worship Hub"**
(`projects/17720733107156785907`), design system **"Sacred Minimalist"** — created
2026-05-21. Mobile-first, font Hanken Grotesk (display) + Open Sans (body), primary
worship-blue `#2E7EED`, deep-navy `#1A1B1F`, altar-gold `#FFC107`.

Designed screens: Início (`61dc2f2bfa584080939f2484519346df`), WorshipFlow App,
Escalas (`bf1873096fe84961a87f12245e66f167`), Repertório (`e79d5906fa5143f18328984697c6bc86`),
Mensagens (`4436d4d08ae64c9899b2f639bfa679c3`).

The Stitch MCP is registered (`claude mcp add stitch --transport http https://stitch.googleapis.com/mcp`
with header `X-Goog-Api-Key`). Native MCP tools only load after a Claude Code session
restart, but the server is **stateless HTTP JSON-RPC** and can be driven directly with
curl: `initialize` → `tools/list` → `tools/call` (e.g. `list_projects`, `list_screens`
with `projectId`, `get_screen` with name+projectId+screenId → `htmlCode`/`screenshot`
downloadUrls). The API key is a secret — never commit it; it lives in `~/.claude.json`.

Key gap found 2026-05-21: the app's `tailwind.config` in `src/layout/head.php` had only
`colors` + `fontFamily` and was **missing** the Stitch `fontSize`/`spacing`/`borderRadius`
scales, so Stitch classes (`text-display-lg-mobile`, `text-headline-md`, `px-margin-mobile`…)
rendered as no-ops. See [[dark-mode-architecture]].
