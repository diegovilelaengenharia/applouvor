---
phase: 02-confirmar-escala
plan: "01"
subsystem: ui
tags: [php, vanilla-js, ajax, pwa, mobile-first, css, fixed-footer]

requires:
  - phase: 01-git-cleanup
    provides: codebase limpo com admin/escala_detalhe.php e api/confirm_scale.php existentes

provides:
  - Footer sticky de confirmação de escala com botões Confirmar/Recusar
  - Transição de estado via AJAX sem reload de página
  - CSS responsivo para iOS/Android com safe-area-inset-bottom
  - Classes .confirm-footer, .confirm-footer-status, .has-confirm-footer em detail_v3.css

affects:
  - 02-02 (badges de status nos cards de participantes — depende do mesmo escala_detalhe.php)
  - 02-03 (contador de confirmados — depende da escala detalhe)
  - 02-04 (push notifications de lembrete)

tech-stack:
  added: []
  patterns:
    - "AJAX via fetch() com Content-Type application/json para confirmar escala"
    - "Transição de estado via outerHTML replacement no footer"
    - "safe-area-inset-bottom com env() para iOS PWA"

key-files:
  created: []
  modified:
    - admin/escala_detalhe.php
    - assets/css/pages/detail_v3.css

key-decisions:
  - "Footer apenas para $myMemberData !== null && !$isEditable — garante que admin em modo edição não vê footer"
  - "Transição de estado via outerHTML replacement (sem frameworks) — mais simples e sem estado global"
  - "Lucide icons re-renderizados via lucide.createIcons() após troca de innerHTML"

patterns-established:
  - "Pattern: AJAX com fetch sem reload — confirmação via JSON POST, resposta {success, message}"
  - "Pattern: footer fixed com safe-area-inset-bottom para compatibilidade iOS PWA"

requirements-completed:
  - ESC-01
  - ESC-02

duration: 15min
completed: 2026-05-17
---

# Phase 2 Plan 01: Confirmar Escala — Footer Sticky Summary

**Footer sticky com botões Confirmar/Recusar e AJAX para api/confirm_scale.php com transição de estado sem reload**

## Performance

- **Duration:** 15 min
- **Started:** 2026-05-17T00:00:00Z
- **Completed:** 2026-05-17T00:15:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- CSS do footer sticky adicionado ao final de detail_v3.css com classes .confirm-footer, .confirm-footer-status, .btn-confirm, .btn-decline, .has-confirm-footer
- Footer com safe-area-inset-bottom para iOS Safari (home bar não obstrui botões)
- Footer sticky renderizado em escala_detalhe.php com 3 estados: pending (botões), confirmed (label verde + Alterar), declined (label vermelho + Alterar)
- AJAX via fetch() para api/confirm_scale.php com transição de estado sem reload da página
- Botões têm min-height: 44px (padrão de toque móvel conforme CLAUDE.md)
- Footer não exibido para admin em modo edição nem para não-participantes

## Task Commits

Cada task foi commitada atomicamente:

1. **Task 1: CSS do footer sticky em detail_v3.css** - `bb76cbe` (feat)
2. **Task 2: Footer sticky com AJAX em escala_detalhe.php** - `6f288cc` (feat)

## Files Created/Modified
- `assets/css/pages/detail_v3.css` - Classes .confirm-footer, .confirm-footer-status, .btn-confirm, .btn-decline, .has-confirm-footer adicionadas ao final
- `admin/escala_detalhe.php` - Footer sticky com AJAX e JS inline para transição de estado

## Decisions Made
- Footer só renderizado quando `$myMemberData !== null && !$isEditable` — líder em modo edição não interfere com confirmação
- Transição de estado via `outerHTML` replacement — solução vanilla JS simples, sem estado global
- Lucide icons re-renderizados via `lucide.createIcons()` após troca de DOM

## Deviations from Plan

None - plano executado exatamente como especificado.

## Issues Encountered
- PHP CLI não estava no PATH do ambiente de execução — verificação de sintaxe feita por inspeção manual do arquivo. Estrutura PHP verificada linha a linha (if/elseif/endif balanceados, tags PHP corretas).

## Known Stubs
Nenhum stub identificado. O footer consome dados reais de `$myMemberData['status']` vindos do banco.

## Threat Surface Scan
Nenhuma nova superfície de ataque além das contempladas no threat_model do plano:
- T-02A-01 (Spoofing): api/confirm_scale.php já valida $_SESSION['user_id']
- T-02A-02 (Tampering): API já tem in_array() whitelist para status
- T-02A-03 (Elevation): Footer só exibido quando $myMemberData !== null

## Next Phase Readiness
- Plano 02-02 pode prosseguir: adicionar badges de status nos cards de participantes (corrigir linha is_confirmed)
- admin/escala_detalhe.php pronto para receber badges nos cards de membros

---
*Phase: 02-confirmar-escala*
*Completed: 2026-05-17*
