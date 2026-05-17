# STATE.md — App Louvor PIB Oliveira

## Project Reference

See: `.planning/PROJECT.md` (updated 2026-05-16)

**Core value:** O músico consegue ver sua próxima escala, confirmar presença, acessar a setlist e roteiro — tudo em segundos, pelo celular.
**Current milestone:** Milestone 1 — Modernização + Features Faltantes

## Current Phase

**Phase 2: Confirmar Escala** — Status: 🔄 Em Execução (plano 03/04 concluído)

## Phase Progress

| Phase | Name | Status | Started | Completed |
|-------|------|--------|---------|-----------|
| 1 | Git Cleanup + Hardening | ✅ Completed | 2026-05-16 | 2026-05-17 |
| 2 | Confirmar Escala | 📋 Planned | 2026-05-17 | — |
| 3 | Roteiro de Culto | ⬜ Not Started | — | — |
| 4 | Registrar Faltas | ⬜ Not Started | — | — |
| 5 | Música Modernizada | ⬜ Not Started | — | — |
| 6 | Metrônomo Pro | ⬜ Not Started | — | — |
| 7 | Histórico Membro | ⬜ Not Started | — | — |
| 8 | Devocional+ | ⬜ Not Started | — | — |
| 9 | Deploy Final | ⬜ Not Started | — | — |

## Phase 1 — Summary (completada 2026-05-17)

12 commits semânticos criados em 4 waves:
- Wave 1 (01A): 5 commits — admin/, includes/, api/, assets/css/, utilitários
- Wave 2 (01B): 1 commit — desktop.ini removidos + .gitignore expandido (credenciais, VAPID, backup)
- Wave 2 (01C): 2 commits — maintenance/ organizado + scripts de setup versionados
- Wave 3 (01D): 4 commits — secrets removidos do tracking, deploy.php deletado, scripts admin movidos

**Estado final:** working tree clean | git ls-files *.ini = vazio | .env.production/vapid_config.php fora do tracking

**Ações manuais pendentes para o Diego (ANTES DE VIAJAR):**
- Rotacionar DB_PASS no painel Hostinger + atualizar .htaccess de produção
- Regenerar chaves VAPID + upload para produção
- Criar maintenance/.htaccess com `Require all denied` em produção
- Ver roteiro completo em `.planning/phases/01-git-cleanup/01D-SUMMARY.md`

## Phase 2 — Progress (em execução 2026-05-17)

- Plan 02-03 concluído: Badge "X/Y confirmados" nos cards de escala (ESC-04 entregue)
- Plan 02-01 concluído: CSS do footer sticky de confirmação em detail_v3.css
- Plans 02-02 e 02-04 ainda pendentes

## Decisions Made

- "Reutilizar $participantsMap já carregado para contador de confirmações — zero queries extras no loop" (02-03)
- "Badge invisível quando escala não tem participantes — evita '0/0 confirmados'" (02-03)

## Context Notes

- App está ~65% pronto para uso ministerial pleno
- App está rodando localmente e em produção (vilela.eng.br/applouvor)
- Usuário prefere YOLO mode + fases finas + commits no git
- `api/confirm_scale.php` já pronta — Phase 2 só precisa de UI
- Versão antiga (23.01.2026) está em `App louvor 23.01.2026/` (gitignored) — usar como referência se precisar resgatar features (ex: admin/oracao.php)
- **Gaps identificados pela auditoria profissional** — ver `.claude/plans/ok-incremente-mas-leia-elegant-lamport.md` para lista completa de ajustes por fase

## Key Files

- `.planning/PROJECT.md` — Contexto completo do projeto
- `.planning/REQUIREMENTS.md` — 29 requisitos com REQ-IDs
- `.planning/ROADMAP.md` — 9 fases, MVP Vertical
- `includes/config.php` — Configuração do ambiente
- `includes/auth.php` — Autenticação
- `admin/escala_detalhe.php` — Detalhe da escala (modificar em Phase 2+3)
- `api/confirm_scale.php` — API de confirmação (já existe, sem UI)

---
*Inicializado: 2026-05-16*
