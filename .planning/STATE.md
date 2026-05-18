# STATE.md — App Louvor PIB Oliveira

## Project Reference

See: `.planning/PROJECT.md` (updated 2026-05-16)

**Core value:** O músico consegue ver sua próxima escala, confirmar presença, acessar a setlist e roteiro — tudo em segundos, pelo celular.
**Current milestone:** Milestone 1 — Modernização + Features Faltantes

## Current Phase

**Phase 7: Histórico Membro** — Status: ⬜ Próxima fase

**Phase 6: Metrônomo Pro** — Status: ✅ Completed (2026-05-17)
**Phase 5: Música Modernizada** — Status: ✅ Completed (2026-05-17)

## Phase Progress

| Phase | Name | Status | Started | Completed |
|-------|------|--------|---------|-----------|
| 1 | Git Cleanup + Hardening | ✅ Completed | 2026-05-16 | 2026-05-17 |
| 2 | Confirmar Escala | ✅ Completed | 2026-05-17 | 2026-05-17 |
| 3 | Roteiro de Culto | ✅ Completed | 2026-05-17 | 2026-05-17 |
| 3.5 | Limpeza & Organização | ✅ Completed | 2026-05-17 | 2026-05-17 |
| 4 | Registrar Faltas | ✅ Completed | 2026-05-17 | 2026-05-17 |
| 5 | Música Modernizada | ✅ Completed | 2026-05-17 | 2026-05-17 |
| 6 | Metrônomo Pro | ✅ Completed | 2026-05-17 | 2026-05-17 |
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

## Phase 6 — Summary (completada 2026-05-17)

2 commits entregues:
- Plan 06-01 (a35ae5f): `feat(06): create metronomo.php — tap BPM, web audio click, slider` — página completa com 309 linhas
- Plan 06-02 (f042ed1): `feat(06): integrate metronomo — dashboard card, music link, SW cache` — 4 arquivos modificados

**Resultado:** Metrônomo funcional com áudio (Web Audio API) | Tap BPM com média 4+ batidas | Slider 40-220 BPM | Card no dashboard | Link "Abrir no metrônomo" em musica_detalhe.php quando BPM existe | Cache offline via SW v2.3.0

## Phase 5 — Summary (completada 2026-05-17)

4 commits entregues:
- Plan 05-01 (95ded72): `feat(05): platform branding in Tab Referências + styled Tom/BPM/Duração cards` — detectPlatform() helper + branded link cards + colored stat boxes
- Plan 05-02 (354ac9c): `feat(05): fix sugestoes count to use song_suggestions + add pending badge in dashboard` — contagem corrigida + badge laranja para admin
- Plan 05-03 (7fe651a): `feat(05): create escala_setlist.php + add Setlist link in escala_detalhe.php` — página de setlist com impressão/compartilhamento
- Plan 05-04 (696a952): `feat(05): add last_played date to all song card queries in repertorio.php` — "Última: dd/mm/yy" ou "Nunca tocada" em cada card

**Resultado:** musica_detalhe.php com branding visual de plataforma | dashboard admin com notificação de sugestões | setlist imprimível/compartilhável | repertório com data de última vez tocada

## Phase 3 — Summary (completada 2026-05-17)

4 commits entregues em 4 waves sequenciais:
- Wave 1 (03-01): `feat(03): add schedule_roteiro migration + api/roteiro.php CRUD` — commit 14bc968
- Wave 2 (03-02): `feat(03): add roteiro edit UI — admin can add/reorder/delete items via modal` — commit 74f31e7
- Wave 3 (03-03): `feat(03): add roteiro view — músico vê itens ordenados com tom e tipo` — commit e9bc582
- Wave 4 (03-04): `feat(03): integrate custom_tone badge in song cards (ROT-05 complete)` — commit a45d543

**Resultado:** schedule_roteiro table criada | api/roteiro.php CRUD | UI edição líder com modal + setas ▲/▼ | View músico read-only (nota_interna server-side protegida) | badge custom_tone laranja nos cards de repertório

## Phase 2 — Progress (em execução 2026-05-17)

- Plan 02-01 concluído: Footer sticky de confirmação com AJAX em escala_detalhe.php (ESC-01, ESC-02 entregues)
  - CSS: .confirm-footer, .confirm-footer-status, .btn-confirm, .btn-decline, .has-confirm-footer
  - JS: fetch() para api/confirm_scale.php, transição de estado sem reload, safe-area-inset-bottom para iOS
- Plan 02-02 concluído: Badges visuais de status nos cards de participantes (ESC-03 entregue)
  - Badge .member-status-badge verde/amarelo/vermelho em cada card de participante
  - Logica de statusClass corrigida: usa $member['status'] diretamente com in_array() whitelist
- Plan 02-03 concluído: Badge "X/Y confirmados" nos cards de escala (ESC-04 entregue)
- Plan 02-04 concluído: AESGCM real em web_push_helper.php + api/send_reminders.php + widget dashboard + trigger auto no save_changes (ESC-05 entregue)
  - AESGCM: openssl_encrypt(aes-128-gcm) + ECDH P-256 + HKDF em PHP puro
  - Fallback D-02: push sem payload se criptografia falhar (erro logado)
  - Botão "Lembrar" no dashboard admin para escalas com pending nos próximos 2 dias
  - Push automático ao salvar escala (dentro de try/catch separado, nao interrompe o save)

## Decisions Made

- "Footer só renderizado quando $myMemberData !== null && !$isEditable — garante isolamento entre edição e confirmação" (02-01)
- "Transição de estado via outerHTML replacement — simples, sem estado global, vanilla JS" (02-01)
- "in_array() whitelist para statusClass: valor inesperado do banco cai em status-pending — previne classes CSS injetadas" (02-02)
- "Badge com texto legível além do indicador circular — acessibilidade e clareza visual em telas pequenas" (02-02)
- "Reutilizar $participantsMap já carregado para contador de confirmações — zero queries extras no loop" (02-03)
- "Badge invisível quando escala não tem participantes — evita '0/0 confirmados'" (02-03)
- "AESGCM puro PHP: openssl_encrypt(aes-128-gcm) + ECDH P-256 + HKDF sem dependências externas" (02-04)
- "Fallback D-02: if encrypt null — push sem payload + error_log — garante valor mínimo" (02-04)
- "Push trigger isolado em try/catch próprio em escala_detalhe.php — falha não interrompe o redirect" (02-04)
- "VAPID keys via config.php (getenv em prod, DotEnv em local) — nunca hardcoded no código" (02-04)

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
