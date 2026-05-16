# STATE.md — App Louvor PIB Oliveira

## Project Reference

See: `.planning/PROJECT.md` (updated 2026-05-16)

**Core value:** O músico consegue ver sua próxima escala, confirmar presença, acessar a setlist e roteiro — tudo em segundos, pelo celular.
**Current milestone:** Milestone 1 — Modernização + Features Faltantes

## Current Phase

**Phase 1: Git Cleanup** — Status: NOT STARTED

## Phase Progress

| Phase | Name | Status | Started | Completed |
|-------|------|--------|---------|-----------|
| 1 | Git Cleanup | ⬜ Not Started | — | — |
| 2 | Confirmar Escala | ⬜ Not Started | — | — |
| 3 | Roteiro de Culto | ⬜ Not Started | — | — |
| 4 | Registrar Faltas | ⬜ Not Started | — | — |
| 5 | Música Modernizada | ⬜ Not Started | — | — |
| 6 | Metrônomo Pro | ⬜ Not Started | — | — |
| 7 | Histórico Membro | ⬜ Not Started | — | — |
| 8 | Devocional+ | ⬜ Not Started | — | — |
| 9 | Deploy Final | ⬜ Not Started | — | — |

## Context Notes

- Git tem 33 arquivos modificados não commitados (mudanças do Gemini CLI)
- `desktop.ini` rastreado incorretamente — remover com `git rm --cached`
- App está rodando localmente e em produção (vilela.eng.br/applouvor)
- Usuário prefere YOLO mode + fases finas + commits no git

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
