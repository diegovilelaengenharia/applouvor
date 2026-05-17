---
phase: 02-confirmar-escala
plan: "03"
subsystem: ui
tags: [php, escalas, confirmação, badge, pib-badge]

requires:
  - phase: 02-confirmar-escala
    provides: "$participantsMap carregado com status de cada participante em admin/escalas.php"

provides:
  - "Badge 'X/Y confirmados' visível em cada card de escala (futuras e passadas)"
  - "Coloração dinâmica: verde quando todos confirmaram, amarelo quando parcial"
  - "ESC-04 entregue: líder vê situação de confirmação na listagem sem abrir o detalhe"

affects: [02-confirmar-escala, 03-roteiro-culto]

tech-stack:
  added: []
  patterns:
    - "Eager-loaded participantsMap reutilizado para cálculo de contador — zero queries extras no loop"
    - "pib-badge-success / pib-badge-warning para coloração semântica de status de confirmação"

key-files:
  created: []
  modified:
    - admin/escalas.php

key-decisions:
  - "Usar apenas $participantsMap já carregado — sem query SQL adicional no loop de renderização"
  - "Badge não aparece quando escala não tem participantes ($totalParticipants === 0)"
  - "Replicated in past schedules loop for visual consistency"

patterns-established:
  - "Loop PHP sobre participantsMap para contar confirmações — padrão reutilizável em outras listagens"

requirements-completed: [ESC-04]

duration: 12min
completed: 2026-05-17
---

# Phase 2 Plan 03: Contador X/Y Confirmados nos Cards de Escala — Summary

**Badge 'X/Y confirmados' adicionado em todos os cards de escala usando $participantsMap já carregado, com cor verde (todos) ou amarela (parcial), sem nenhuma query SQL extra.**

## Performance

- **Duration:** 12 min
- **Started:** 2026-05-17T00:00:00Z
- **Completed:** 2026-05-17T00:12:00Z
- **Tasks:** 1
- **Files modified:** 1

## Accomplishments
- Cálculo de `$confirmedCount` e `$totalParticipants` adicionado no loop de escalas futuras, reutilizando `$participantsMap` já disponível (zero queries extras)
- Badge `pib-badge` com texto "X/Y confirmados" exibido próximo aos avatares em cada card de escala futura
- Mesma lógica replicada no loop de escalas passadas para consistência visual
- Coloração semântica: verde (`pib-badge-success`) quando todos confirmaram, amarelo (`pib-badge-warning`) quando parcial, sem badge quando nenhum confirmou

## Task Commits

Cada task foi commitada atomicamente:

1. **Task 1: Calcular e exibir contador X/Y confirmados nos cards de escala** - `3697765` (feat)

**Plan metadata:** (a ser adicionado no commit final de docs)

## Files Created/Modified
- `admin/escalas.php` — Adicionados cálculo de $confirmedCount/$totalParticipants e badge HTML nos loops de escalas futuras e passadas

## Decisions Made
- Reutilizar `$participantsMap` já carregado na query eager-loading — mantém zero queries extras no loop de renderização
- Badge invisível quando a escala não tem participantes (`$totalParticipants === 0`) — evita "0/0 confirmados" sem sentido
- Replicado nas escalas passadas para consistência visual, mesmo que confirmação seja menos relevante no histórico

## Deviations from Plan

None — plano executado exatamente como especificado.

## Issues Encountered

- PHP não disponível no PATH do shell de execução, impossibilitando `php -l` para lint de sintaxe. Verificação de sintaxe feita por inspeção visual das edições e confirmação das grep checks nos critérios de aceitação.

## User Setup Required

None — nenhuma configuração externa necessária.

## Next Phase Readiness

- ESC-04 entregue: líder consegue ver o status de confirmação direto na listagem de escalas
- Plano 02-04 (push notifications) pode executar independentemente
- Plano 02-02 (UI de confirmação em escala_detalhe.php) completa o fluxo end-to-end do músico

---
*Phase: 02-confirmar-escala*
*Completed: 2026-05-17*
