---
phase: 02-confirmar-escala
plan: "02"
subsystem: ui
tags: [php, css, mobile-first, badges, status-visual]

requires:
  - phase: 02-confirmar-escala
    plan: "01"
    provides: footer sticky de confirmacao com AJAX em escala_detalhe.php

provides:
  - Badge colorido .member-status-badge por participante em escala_detalhe.php
  - Indicador circular .status-indicator corrigido para usar $member['status'] diretamente
  - CSS .member-status-badge com variantes confirmed/pending/declined/absent

affects:
  - 02-03 (contador de confirmados — mesmo arquivo escala_detalhe.php, ja concluido)
  - 02-04 (push notifications — independente)

tech-stack:
  added: []
  patterns:
    - "in_array() whitelist para sanitizacao de status do banco antes de montar classe CSS"
    - "Entidades HTML (&amp;#10003;/&amp;#10007;/&amp;middot;) para simbolos de status sem dependencia de fontes de icones"

key-files:
  created: []
  modified:
    - assets/css/pages/detail_v3.css
    - admin/escala_detalhe.php

key-decisions:
  - "Usar in_array() whitelist (T-02B-02): valor inesperado do banco cai em status-pending — previne classes CSS injetadas"
  - "Badge de texto legivel alem do indicador circular — acessibilidade e clareza visual"
  - "Entidades HTML ao inves de emojis: compatibilidade universal em todos os browsers/OS"
  - "statusClass unificado: mesmo valor usado para .status-indicator e .member-status-badge — coerencia visual"

requirements-completed:
  - ESC-03

duration: 10min
completed: 2026-05-17
---

# Phase 2 Plan 02: Confirmar Escala — Badges de Status nos Cards Summary

**Badges visuais coloridos (verde/amarelo/vermelho) com texto legivel em cada card de participante da escala, corrigindo logica de status que usava coluna is_confirmed inexistente**

## Performance

- **Duration:** 10 min
- **Started:** 2026-05-17T00:00:00Z
- **Completed:** 2026-05-17T00:10:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- CSS de badge `.member-status-badge` adicionado ao final de detail_v3.css com variantes confirmed/pending/declined/absent
- Verde (#dcfce7/#16a34a) para confirmado, amarelo (#fef3c7/#d97706) para pendente, vermelho (#fee2e2/#dc2626) para recusado/ausente
- Logica de $statusClass corrigida: substituida linha `is_confirmed` por `$member['status'] ?? 'pending'` com whitelist in_array()
- Badge de texto inline (`member-status-badge`) adicionado em `.member-info` apos `.member-role`
- Indicador circular no avatar (`.status-indicator`) mantido e agora usa classe correta derivada do status real
- Entidades HTML para simbolos: &#10003; (check confirmado), &#10007; (x recusado/ausente), &middot; (ponto pendente)

## Task Commits

1. **Task 1: CSS de badges de status em detail_v3.css** - `27ae537` (feat)
2. **Task 2: Badges de status nos cards em escala_detalhe.php** - `eb18aa2` (feat)

## Files Created/Modified

- `assets/css/pages/detail_v3.css` - Bloco `/* STATUS BADGES nos cards de participantes (Phase 2B) */` adicionado ao final com .member-status-badge e variantes
- `admin/escala_detalhe.php` - Loop de participantes corrigido: $statusClass via in_array whitelist, badge de texto adicionado em .member-info

## Decisions Made

- `in_array()` whitelist para status: valores inesperados do banco (NULL, string estranha) caem em 'status-pending' sem expor a string na classe CSS
- Badge com texto alem do ponto colorido: o indicador circular sozinho e pouco legivel em telas pequenas
- Entidades HTML ao inves de caracteres UTF-8 ou emojis: renderizam consistentemente em todos os contextos

## Deviations from Plan

**1. [Rule 2 - Seguranca] Entidades HTML ao inves de caracteres especiais diretamente**
- **Found during:** Task 2
- **Issue:** O plano sugeria usar '&#10003;' '&#10007;' como strings PHP — decisao ja alinhada com seguranca
- **Fix:** Mantido conforme plano, sem alteracao necessaria
- **Files modified:** nenhum extra

**2. [Rule 1 - Observacao] Classes .status-confirmed/.status-pending/.status-declined ja existiam no CSS**
- **Found during:** Task 1
- **Issue:** O CSS ja tinha essas classes (linhas 210-221) como seletores simples sem contexto de badge
- **Fix:** Adicionadas as classes `.member-status-badge.status-*` como modificadores especificos — as classes originais do status-indicator foram preservadas
- **Impacto:** Zero — as classes originais continuam funcionando para o indicador circular

## Issues Encountered

- PHP CLI nao disponivel no PATH do ambiente de execucao — verificacao de sintaxe feita por inspecao manual linha a linha (foreach/endforeach, if/else/endif balanceados, tags PHP corretas)
- Avisos de geometric repack no git (objetos corrompidos preexistentes) — commits criados com sucesso, nao afeta o codigo

## Known Stubs

Nenhum stub identificado. Os badges consomem `$member['status']` real do banco via query existente.

## Threat Surface Scan

Nenhuma nova superficie de ataque alem das contempladas no threat_model:
- T-02B-01: Status visivel a todos os participantes autenticados — intencional para coordenacao
- T-02B-02: in_array() whitelist implementado — valor inesperado cai em 'status-pending'

## Next Phase Readiness

- Plan 02-03 ja concluido (badge X/Y confirmados nos cards de escala)
- Plan 02-04 pendente: push notifications de lembrete (independente de escala_detalhe.php)
- ESC-03 entregue: status de confirmacao visivel por participante

## Self-Check

- [x] `assets/css/pages/detail_v3.css` contem `.member-status-badge.status-confirmed`
- [x] `admin/escala_detalhe.php` contem `member-status-badge` no loop de membros
- [x] `is_confirmed` removido do arquivo PHP (grep retorna zero ocorrencias)
- [x] `$memberStatus = $member['status'] ?? 'pending'` presente
- [x] `$statusLabels` array presente com 4 entradas
- [x] Commit `27ae537` criado para CSS
- [x] Commit `eb18aa2` criado para PHP

---
*Phase: 02-confirmar-escala*
*Completed: 2026-05-17*
