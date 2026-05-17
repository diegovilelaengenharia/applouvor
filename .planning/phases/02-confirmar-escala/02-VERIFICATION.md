---
phase: 02-confirmar-escala
verified: 2026-05-17T00:00:00Z
status: human_needed
score: 10/10 must-haves verified
overrides_applied: 0
human_verification:
  - test: "Músico com status pending acessa detalhe da escala e vê footer sticky com botões Confirmar e Recusar"
    expected: "Footer aparece fixado na parte inferior da tela; botões têm ao menos 44px de altura"
    why_human: "Renderização condicional PHP baseada em $myMemberData — requer sessão de usuário ativa em navegador real"
  - test: "Clicar em Confirmar envia request para api/confirm_scale.php e o footer transita para estado 'Confirmado' sem reload"
    expected: "Feedback visual imediato (footer muda de estado), sem reload de página"
    why_human: "Fluxo AJAX com transição de DOM — não verificável programaticamente"
  - test: "Botão 'Alterar' no footer pós-confirmação exibe novamente os botões Confirmar/Recusar"
    expected: "Footer reverte para estado de ação corretamente"
    why_human: "Comportamento dinâmico via outerHTML replacement — requer inspeção no browser"
  - test: "Em iPhone/Android PWA o footer não se sobrepõe à home bar do sistema"
    expected: "safe-area-inset-bottom respeita a área segura do iOS"
    why_human: "Comportamento específico de dispositivo móvel com home indicator"
  - test: "Admin clica em 'Lembrar' no widget do dashboard e a API api/send_reminders.php responde com sucesso JSON"
    expected: "Botão fica verde e mostra 'Enviado!'; em caso de VAPID keys não configuradas, mensagem de erro clara"
    why_human: "Push end-to-end requer subscriptions ativas no banco e VAPID keys configuradas no ambiente"
---

# Phase 2: Confirmar Escala — Verification Report

**Phase Goal:** Músico pode confirmar ou recusar presença na escala pelo celular, com feedback imediato. Líder vê quem confirmou. Sistema envia push para quem não confirmou.
**Verified:** 2026-05-17
**Status:** human_needed
**Re-verification:** No — initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Músico participante com status pending vê footer sticky com botões Confirmar/Recusar | VERIFIED | `admin/escala_detalhe.php` L852-880: bloco `if ($myMemberData !== null && !$isEditable)` renderiza `<div class="confirm-footer" id="confirm-footer">` com os dois botões quando `$currentStatus === 'pending'` |
| 2 | Clicar Confirmar/Recusar chama api/confirm_scale.php via fetch sem reload | VERIFIED | `escala_detalhe.php` L893-930: `fetch('../api/confirm_scale.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({schedule_id, status}) })` com handlers `.then`/`.catch` |
| 3 | Após AJAX success, footer transita para estado de status com botão Alterar | VERIFIED | `escala_detalhe.php` L906-914: em caso de `data.success`, `footer.outerHTML` é substituído por `.confirm-footer-status` com label e botão Alterar; `lucide.createIcons()` re-renderizado |
| 4 | Clicar Alterar reexibe botões originais de confirmação | VERIFIED | `escala_detalhe.php` L932-946: `showConfirmButtons()` substitui `outerHTML` de volta para `.confirm-footer` com os dois botões |
| 5 | Footer fixo na parte inferior com safe-area-inset-bottom para iOS | VERIFIED | `assets/css/pages/detail_v3.css` L370+L430: `padding-bottom: calc(12px + env(safe-area-inset-bottom, 16px))` em `.confirm-footer` e `.confirm-footer-status`; `position: fixed; bottom: 0` |
| 6 | Cada card de participante exibe badge visual de status (confirmado/pendente/recusado) | VERIFIED | `escala_detalhe.php` L391-425: `$memberStatus = $member['status'] ?? 'pending'`, whitelist `in_array()`, `.member-status-badge $statusClass` com `$statusLabels` array de texto legível |
| 7 | Badge usa cores verde/amarelo/vermelho por status | VERIFIED | `detail_v3.css` L485-497: `.member-status-badge.status-confirmed { background: #dcfce7; color: #16a34a }`, `.status-pending { background: #fef3c7; color: #d97706 }`, `.status-declined { background: #fee2e2; color: #dc2626 }` |
| 8 | Cada card de escala na listagem exibe X/Y confirmados | VERIFIED | `escalas.php` L196-204: cálculo `$confirmedCount/$totalParticipants` via loop sobre `$participantsMap` (zero queries extras); L247-251: badge `pib-badge` exibido; replicado L294-302 no loop de escalas passadas |
| 9 | Botão Lembrar aparece no dashboard do líder para escalas nos próximos 2 dias com pending | VERIFIED | `admin/index.php` L132-205: bloco `if ($userRole === 'admin')` com query SQL `BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 2 DAY) AND su.status = 'pending'`; widget condicional `if (!empty($upcomingWithPending))`; botão com `onclick="sendReminder()"` |
| 10 | Clicar Lembrar chama api/send_reminders.php que envia push para participantes pending | VERIFIED | `admin/index.php` L178-202: `fetch('../api/send_reminders.php', { method: 'POST', ... })` com feedback visual; `api/send_reminders.php` verifica auth admin, busca pending users, instancia `WebPushHelper` com AESGCM real |

**Score:** 10/10 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `admin/escala_detalhe.php` | Footer sticky de confirmação com JS AJAX | VERIFIED | Contém `id="confirm-footer"`, `fetch('../api/confirm_scale.php'`, `window.confirmPresence`, `window.showConfirmButtons`; trigger push no `save_changes` com `WebPushHelper` |
| `assets/css/pages/detail_v3.css` | CSS do footer sticky | VERIFIED | Contém `.confirm-footer` (L362), `.confirm-footer-status` (L422), `.has-confirm-footer` (L467), `.member-status-badge` (L474) com variantes de cor |
| `admin/escalas.php` | Contador de confirmações nos cards | VERIFIED | Contém `$confirmedCount`, `$totalParticipants`, texto `confirmados`, lógica `pib-badge-success`/`pib-badge-warning`; cálculo sem queries extras |
| `api/send_reminders.php` | Endpoint para push de lembretes | VERIFIED | Criado; verifica `$_SESSION['user_role'] !== 'admin'`; busca pending por schedule_id ou próximos 2 dias; instancia `WebPushHelper`; retorna JSON `{success, sent, failed}` |
| `includes/web_push_helper.php` | AESGCM real para payload de push | VERIFIED | Contém `openssl_pkey_new(prime256v1)`, `openssl_pkey_derive` (ECDH), `hkdfExtract`/`hkdfExpand` (HKDF), `openssl_encrypt('aes-128-gcm')`, fallback `if ($encrypted === null)` |
| `admin/index.php` | Botão manual de lembrete no dashboard | VERIFIED | Widget condicional `$userRole === 'admin'`; query com `pending_count`; botão com `sendReminder()` JS; `min-height: 44px` |
| `includes/config.php` | VAPID keys via environment | VERIFIED | L44-45: `define('VAPID_PUBLIC_KEY', getenv(...))`; L66-67: `define('VAPID_PUBLIC_KEY', App\DotEnv::get(...))` para prod e local |

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `escala_detalhe.php` (footer buttons) | `api/confirm_scale.php` | `fetch('../api/confirm_scale.php', { method: 'POST' })` | WIRED | L893: chamada + L898-918: response handling com transição de estado |
| `admin/index.php` (botão Lembrar) | `api/send_reminders.php` | `fetch('../api/send_reminders.php', { method: 'POST' })` | WIRED | L181: chamada + L184-196: response handling com feedback visual |
| `api/send_reminders.php` | `includes/web_push_helper.php` | `require_once '../includes/web_push_helper.php'` + `new WebPushHelper()` | WIRED | L6: require; L57: instancia; L78: `sendNotification()` chamado em loop |
| `admin/escala_detalhe.php` (save_changes) | `includes/web_push_helper.php` | `require_once '../includes/web_push_helper.php'` + `new WebPushHelper()` | WIRED | L112: require; L126: instancia; L136: `sendNotification()` em loop; em `try/catch` isolado após `$pdo->commit()` |
| `escalas.php` (loop) | `$participantsMap[$schedule['id']]` | PHP loop contando `$p['status'] === 'confirmed'` | WIRED | L196-204 (futuras) e L294-302 (passadas): cálculo eager-loaded, sem query extra |
| `escala_detalhe.php` (member loop) | CSS `.status-confirmed/.status-pending/.status-declined` | `$statusClass = in_array($memberStatus, [...]) ? 'status-' . $memberStatus : 'status-pending'` | WIRED | L392-393: cálculo whitelist; L411: `.status-indicator $statusClass`; L416: `.member-status-badge $statusClass` |

### Data-Flow Trace (Level 4)

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|--------------|--------|-------------------|--------|
| `escala_detalhe.php` footer | `$myMemberData['status']` | Query L165-174: `SELECT su.*, u.id as user_id ... FROM schedule_users su JOIN users u ... WHERE su.schedule_id = ?` | Sim — DB real via PDO | FLOWING |
| `escala_detalhe.php` member badges | `$member['status']` | Mesmo query acima — `$team` array com `su.status` | Sim | FLOWING |
| `escalas.php` contador confirmados | `$confirmedCount/$totalParticipants` | `$participantsMap` populado via L52-63: query `SELECT su.schedule_id, su.user_id, ..., su.status FROM schedule_users su JOIN users u` | Sim — DB real via PDO | FLOWING |
| `admin/index.php` widget lembrete | `$upcomingWithPending` | L137-148: query SQL `SELECT s.id, ..., COUNT(su.user_id) as pending_count FROM schedules s JOIN schedule_users su ... BETWEEN CURDATE() AND DATE_ADD(...)` | Sim | FLOWING |
| `api/send_reminders.php` | `$pendingUsers` + `$subscriptions` | L31-49: query por schedule_id ou período; L63-65: query `push_subscriptions WHERE user_id = ?` | Sim — dados reais do banco | FLOWING |

### Behavioral Spot-Checks

Step 7b: PHP CLI não disponível no ambiente de execução — verificação de sintaxe por inspeção manual linha a linha. Estrutura verificada:

| Behavior | Method | Result | Status |
|----------|--------|--------|--------|
| `escala_detalhe.php` PHP válido | Inspeção manual: if/elseif/endif balanceados, tags PHP corretas, blocos try/catch fechados | Estrutura PHP coerente — L852 abre bloco `if ($myMemberData...`, L955 fecha `endif;`; L79 abre `if (isset(save_changes)...)`, L151 fecha | PASS |
| `api/send_reminders.php` PHP válido | Inspeção manual | try/catch fechado em L91; todos os `if/else` balanceados | PASS |
| `includes/web_push_helper.php` PHP válido | Inspeção manual | Classe com `{` fechado em L292 `}`; todos os métodos `private function` com `{}`; `?>` em L293 | PASS |
| `api/confirm_scale.php` funcional | Leitura direta — L13: whitelist `in_array($status, ['confirmed', 'declined'])`; L19-25: UPDATE PDO com execute | API valida entrada e persiste no banco | PASS |

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|---------|
| ESC-01 | 02-01 | Músico visualiza botão Confirmar/Recusar na tela de detalhe da sua escala | SATISFIED | `escala_detalhe.php` L857-865: footer com botões para status=pending |
| ESC-02 | 02-01 | Músico confirma ou recusa presença via toggle/botão com feedback visual imediato (AJAX) | SATISFIED | `escala_detalhe.php` L882-953: fetch + transição DOM via outerHTML + lucide.createIcons() |
| ESC-03 | 02-02 | Status de confirmação (confirmado/pendente/recusado) visível no card de cada participante | SATISFIED | `escala_detalhe.php` L391-428: `.member-status-badge` com `$statusLabels` e indicador circular `.status-indicator`; CSS em `detail_v3.css` L474-497 |
| ESC-04 | 02-03 | Líder vê quantos confirmaram vs pendente vs recusaram na lista de escalas | SATISFIED | `escalas.php` L247-251 (futuras) e L314-318 (passadas): badge `X/Y confirmados` com cor dinâmica |
| ESC-05 | 02-04 | Notificação push automática para músicos que não confirmaram | SATISFIED (com ressalva) | `api/send_reminders.php` + botão manual em `index.php`; AESGCM real em `web_push_helper.php`; trigger automático em `escala_detalhe.php` save_changes; **ressalva: entrega end-to-end depende de VAPID keys configuradas e push_subscriptions no banco — requer verificação humana** |

### Anti-Patterns Found

| File | Pattern | Severity | Impact |
|------|---------|----------|--------|
| `admin/index.php` deferred-items.md | "Bug pre-existente: PHP inline sem tags" — mencionado no deferred-items.md pelo implementador | Info | Falso alarme: inspeção manual confirma que L56 abre `<?php` válido após `endif; ?>` de L54 — estrutura PHP correta, sem regressão introduzida pela phase 2 |
| `api/send_reminders.php` L21-25 | Se VAPID keys não configuradas, retorna `{success: false}` sem enviar push | Warning | Comportamento intencional e documentado — admin verá mensagem de erro; não é bug mas requer setup manual de VAPID keys em produção |
| `admin/escala_detalhe.php` L543 `event_date` via `$_POST` | Push de convocação usa `$_POST['event_date']` para formatar data | Info | Sanitizado via `date()` — nenhum output direto de `$_POST` sem processamento; risco mínimo |

### Human Verification Required

#### 1. Footer sticky — exibição e interação mobile

**Test:** Logar como músico participante de uma escala com status `pending`, acessar `admin/escala_detalhe.php?id={id}` no celular
**Expected:** Footer fixo na parte inferior com botões "Confirmar" e "Recusar"; ao clicar "Confirmar", footer muda para "Confirmado" + botão "Alterar" sem reload; "Alterar" reexibe os botões originais
**Why human:** Renderização condicional baseada em sessão PHP + comportamento AJAX com transição de DOM — não verificável sem browser ativo

#### 2. safe-area-inset-bottom em iOS Safari / PWA

**Test:** Acessar a página em iPhone com home indicator ativo (iPhone X ou posterior) no PWA ou Safari
**Expected:** O footer não se sobrepõe à home bar do sistema — espaçamento correto da área segura
**Why human:** Comportamento específico do ambiente de hardware iOS — não simulável por inspeção de código

#### 3. Push notification end-to-end

**Test:** Com VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY configurados no ambiente, escalar um músico que tem subscription ativa em `push_subscriptions`, salvar a escala, e/ou clicar "Lembrar" no dashboard
**Expected:** Notificação push recebida no browser do músico com título "Nova Escala" / "Lembrete de Escala" e corpo com data/hora do evento
**Why human:** Requer VAPID keys configuradas, subscription ativa no banco, e browser receptor disponível — stack completa de push não testável por código

#### 4. Fallback AESGCM (D-02)

**Test:** Forçar falha de `openssl_pkey_new` (ex.: desabilitar temporariamente a extensão ou injetar chave inválida) e verificar que push é enviado sem payload
**Expected:** `error_log` registra "AESGCM falhou", notificação genérica ainda entregue ao browser
**Why human:** Requer manipulação de ambiente PHP ou mock de openssl — não verificável por inspeção

#### 5. Contador X/Y confirmados — atualização após confirmação

**Test:** Com escala de 3 músicos (2 pending, 1 confirmed), confirmar como um músico, voltar à listagem `admin/escalas.php`
**Expected:** Badge muda de "1/3 confirmados" para "2/3 confirmados" (amarelo) após confirmação
**Why human:** Requer ciclo completo de sessão PHP para confirmar e reload da listagem

### Gaps Summary

Nenhum gap bloqueador identificado. Todos os 10 must-haves foram verificados como VERIFIED com evidências diretas no código. Todos os 5 requisitos (ESC-01 a ESC-05) estão cobertos por implementações substantivas e fiadas.

Os 5 itens de verificação humana identificados são de natureza comportamental/visual/de ambiente — não indicam implementação faltante, mas confirmam que a entrega end-to-end de push notifications (ESC-05) depende de setup de ambiente (VAPID keys + subscriptions) que requer validação manual por Diego.

---

_Verified: 2026-05-17_
_Verifier: Claude (gsd-verifier)_
