# Phase 2: Confirmar Escala - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-05-17
**Phase:** 02-confirmar-escala
**Areas discussed:** Push notifications, UI de confirmação, Trigger do push, Pós-confirmação, Lembrete 2 dias antes

---

## Push Notifications

| Option | Description | Selected |
|--------|-------------|----------|
| Botão manual | Botão "Enviar lembrete" no dashboard do líder, sem push real | |
| Consertar web_push_helper.php | AESGCM real em PHP puro sem Composer | ✓ |
| OneSignal free tier | SDK externo gratuito | |
| Sem push por enquanto | Pular ESC-05, implementar em fase posterior | |

**User's choice:** Consertar `web_push_helper.php` com AESGCM real em PHP puro.
**Notes:** Codebase já tem estrutura VAPID + subscriptions, só falta a criptografia do payload. Fallback documentado: se AESGCM travar nos testes, entrega botão manual como valor mínimo.

---

## UI de Confirmação (Placement)

| Option | Description | Selected |
|--------|-------------|----------|
| Footer sticky | Barra fixa na parte de baixo, sempre visível enquanto rola | ✓ |
| Card no topo | Card abaixo do cabeçalho do evento | |
| Inline no card do músico | Botões junto com o check de "ensaiou" | |

**User's choice:** Footer sticky.
**Notes:** Padrão de apps mobile — botão de ação principal fixo na parte inferior.

---

## Pós-Confirmação

| Option | Description | Selected |
|--------|-------------|----------|
| Substitui por status | Footer muda para "✅ Você confirmou" com opção de alterar | ✓ |
| Footer some | Desaparece após confirmar | |
| Redirect para lista | Volta para lista de escalas | |

**User's choice:** Footer substitui por status com opção "Alterar".
**Notes:** Confirmação visual clara, sem perder o contexto da escala.

---

## Trigger do Push de Convocação

| Option | Description | Selected |
|--------|-------------|----------|
| Botão dedicado "Enviar convocação" | Separado do salvar | |
| Automático ao salvar | Todo save dispara push | ✓ |
| Sem push de publicação | Só lembrete 2 dias antes | |

**User's choice:** Automático ao salvar.
**Notes:** Simplifica o fluxo do líder. Considerar enviar apenas para membros novos na escala (não re-notificar em edições de observações).

---

## Lembrete 2 Dias Antes (ESC-05)

| Option | Description | Selected |
|--------|-------------|----------|
| Botão manual no dashboard | Diego clica quando quiser | ✓ |
| Verificar cron Hostinger | Se suportado, criar script diário | |
| GitHub Actions como cron | POST diário externo para o app | |

**User's choice:** Botão manual no dashboard do líder.
**Notes:** Hostinger shared hosting sem garantia de cron. Botão manual é confiável e suficiente para ~12 músicos.

---

## Claude's Discretion

- Tamanho e tipografia dos badges de status nos cards de participantes
- Animação/transição do footer ao confirmar (fade ou slide)
- Texto exato das notificações push
- Posição do botão "Lembrar" no dashboard do líder

## Deferred Ideas

- Notificação push ao publicar aviso → Phase 8 (Devocional+)
- Deep link no push para abrir a escala específica → Phase 9 (Deploy/PWA)
- Confirmação por WhatsApp (deep link) → Backlog v2
