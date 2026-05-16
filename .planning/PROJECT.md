# App Louvor PIB Oliveira

## What This Is

PWA (Progressive Web App) em PHP/MySQL para o Ministério de Louvor da PIB Oliveira (MG). O app centraliza a gestão de escalas, repertório de músicas, avisos e vida devocional da equipe — acessível pelo celular como app instalado, com suporte a modo escuro, notificações push e uso offline. É usado por líderes (admin) e músicos, com dashboards e funcionalidades distintas por papel.

## Core Value

O músico consegue ver sua próxima escala, confirmar presença, acessar a setlist e o roteiro de culto — tudo em segundos, pelo celular, sem precisar de WhatsApp.

## Requirements

### Validated (já existente no codebase)

- ✓ Autenticação com bcrypt, sessões seguras, roles admin/user — existente
- ✓ Dashboard do líder com alertas urgentes, próxima escala, aniversariantes — existente
- ✓ Dashboard do músico com próxima escala e avisos — existente
- ✓ CRUD de escalas com data, horário, tipo de evento — existente
- ✓ Detalhe de escala com participantes, músicas e comentários — existente
- ✓ Repertório com 140+ músicas (tom, BPM, duração, links cifra/letra/áudio/vídeo) — existente
- ✓ Gestão de membros com instrumentos, foto, avatar, indisponibilidades — existente
- ✓ Sistema de avisos com prioridade (urgente/importante/info), reações emoji — existente
- ✓ Devocionais diários com leitura bíblica, progresso e orações — existente
- ✓ Aniversariantes com calendário mensal — existente
- ✓ PWA instalável (manifest, service worker, install prompt iOS/Android) — existente
- ✓ Dark mode via `.dark-mode` no body, persistido em localStorage — existente
- ✓ Push notifications (Web Push API) — existente
- ✓ API `confirm_scale.php` para confirmação de escala (backend pronto) — existente, sem UI

### Active (milestone atual — modernização e features faltantes)

- [ ] UI para músico confirmar/recusar escala (consumir API existente)
- [ ] Roteiro de culto dentro da escala (tabela + CRUD)
- [ ] Registrar faltas por escala com toggles (líder)
- [ ] Detalhamento moderno da música (links de streaming como cards visuais)
- [ ] Metrônomo: Tap BPM + slider vertical + configurações salvas
- [ ] Commitar estado atual (33 arquivos modificados não commitados)
- [ ] Git cleanup — remover desktop.ini do tracking

### Out of Scope

- Chat/mensagens em tempo real — WhatsApp já resolve, custo de infraestrutura alto
- Integração com plataformas de streaming (Spotify API) — links diretos são suficientes
- App nativo (iOS/Android) — PWA cobre o caso de uso
- Multi-ministério (multi-tenant) — é para um único ministério
- Letras de músicas inline — direitos autorais e escopo
- Financeiro / dízimos — fora do ministério de louvor

## Context

**Stack atual:** PHP 8+ / MySQL / PDO / Vanilla JS / CSS (60+ arquivos, design system próprio) / Hostinger

**Ambiente:** Projeto hospedado em `vilela.eng.br/applouvor`. Desenvolvimento local via `run_server.bat` (PHP built-in server). Banco: `pibo_louvor` local / `u884436813_applouvor` produção.

**Histórico:** Projeto desenvolvido ao longo de 2024-2026. Parado por meses. Tentativa com Gemini CLI deixou ~33 arquivos modificados sem commit. Código está funcional mas git sujo.

**Referência visual:** LouveApp (`app.louveapp.com.br`) — usado como referência de UX/features desejadas.

**Design System:** `assets/css/` + `design-system/app-louvor/MASTER.md`. Core colors: `#3B82F6` (azul), `#F97316` (laranja). Componente central: `pib-card` (12px radius, shadow-sm → shadow-md hover).

**Usuários:** Diego (admin/líder) + ~12 músicos/membros da equipe de louvor da PIB Oliveira.

## Constraints

- **Stack:** PHP/MySQL — sem refatoração para outro framework. Arquitetura atual mantida.
- **Hosting:** Hostinger shared hosting — sem Docker, sem Node.js no servidor.
- **Mobile-first:** 100% das telas devem funcionar bem em celular (PWA).
- **Sem build step:** CSS e JS puros, sem webpack/vite/npm no deploy.
- **Compatibilidade:** Deve manter todas as páginas existentes funcionando.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| PHP + MySQL ao invés de framework moderno | Já está em produção, hospedagem compartilhada | ✓ Correto — mantém custo zero extra |
| PWA ao invés de app nativo | Sem loja de apps, músicos instalam direto do browser | ✓ Correto — funciona bem |
| Design system próprio (sem Bootstrap/Tailwind) | Controle total do visual, sem dependências | ✓ Correto — já bem estruturado |
| Confirmar escala via API REST | Permite chamadas AJAX sem reload de página | — Pending (UI ainda falta) |
| Roteiro de culto como tabela separada | Flexibilidade para adicionar itens além de músicas | — Pending (a implementar) |

## Evolution

Este documento evolui a cada transição de fase e marco de milestone.

**Após cada fase:**
1. Requisitos invalidados? → Mover para Out of Scope com motivo
2. Requisitos validados? → Mover para Validated com referência da fase
3. Novos requisitos emergiram? → Adicionar em Active
4. Decisões a registrar? → Adicionar em Key Decisions
5. "What This Is" ainda preciso? → Atualizar se drifted

**Após cada milestone:**
1. Revisão completa de todas as seções
2. Core Value — ainda é a prioridade certa?
3. Auditar Out of Scope — motivos ainda válidos?

---
*Last updated: 2026-05-16 após inicialização brownfield*
