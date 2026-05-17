# Phase 2: Confirmar Escala - Context

**Gathered:** 2026-05-17
**Status:** Ready for planning

<domain>
## Phase Boundary

Músico vê botões "Confirmar" / "Recusar" na sua escala, clica, status muda sem reload (AJAX), e o líder vê o resultado (badges por participante + contador X/Y confirmados na lista). Push notifica os músicos: automático ao salvar a escala (convocação) e botão manual no dashboard (lembrete 2 dias antes para quem não confirmou).

A API `api/confirm_scale.php` já existe e funciona — esta fase entrega APENAS a UI e o push real.

</domain>

<decisions>
## Implementation Decisions

### Push Notifications
- **D-01:** Corrigir `includes/web_push_helper.php` com criptografia AESGCM real em PHP puro (sem Composer). Usar `openssl_encrypt` com AES-128-GCM para o payload, ECDH na curva P-256 para troca de chave, e HKDF para derivação. Não usar OneSignal nem outra dependência externa.
- **D-02:** Fallback documentado no plano: se AESGCM falhar nos testes, o Plan 2D entrega botão "Enviar lembrete" manual funcional como valor mínimo (sem push real), e a crypto é refinada depois.
- **D-03:** Push de convocação dispara **automaticamente** quando o líder salva a escala (após `$pdo->commit()` bem-sucedido no `save_changes` de `escala_detalhe.php`). Chamar o helper para todos os `schedule_users` da escala.
- **D-04:** Lembrete "2 dias antes" (ESC-05) → **botão manual** no dashboard do líder. Dashboard exibe escalas nos próximos 2 dias com botão "Lembrar quem não confirmou" — chama `api/send_reminders.php` que envia push apenas para participantes com `status = 'pending'`.

### UI de Confirmação (View do Músico)
- **D-05:** Botões "Confirmar" / "Recusar" ficam num **footer sticky** na parte inferior da tela em `admin/escala_detalhe.php`. Visível apenas quando `$myMemberData !== null` (usuário logado é participante da escala) e status atual é `pending`.
- **D-06:** Após confirmar/recusar com sucesso (resposta AJAX `success: true`), o footer **não some** — transita para estado de status: "✅ Confirmado" ou "❌ Recusado", com link/botão secundário "Alterar" que reexibe os botões originais.
- **D-07:** Chamada AJAX aponta para `../api/confirm_scale.php` com `schedule_id` e `status` (`confirmed` ou `declined`). Sem reload de página.

### Badges e Contador
- **D-08:** Badges de status nos cards de participantes em `escala_detalhe.php`: 🟢 `confirmed` / 🟡 `pending` / 🔴 `declined` (ou `absent`). Usar classes CSS `.status-confirmed`, `.status-pending`, `.status-declined` — adicionar ao `detail_v3.css`.
- **D-09:** Contador "X/Y confirmados" na listagem `admin/escalas.php` — calculado a partir do `participantsMap` já carregado. Exibir como badge no card da escala.

### Claude's Discretion
- Tamanho e tipografia dos badges de status nos cards de participantes (seguir padrão do design system).
- Animação/transição do footer ao confirmar (fade ou slide).
- Texto exato das notificações push (ex: "📋 Você foi escalado para [tipo] em [data]. Confirme sua presença no app.").
- Posição exata do botão "Lembrar" no dashboard do líder (junto ao card da próxima escala ou seção dedicada).

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### API e Lógica de Negócio
- `api/confirm_scale.php` — API de confirmação pronta. Aceita POST JSON `{schedule_id, status}` onde status é `confirmed` ou `declined`. Retorna `{success, message}`.
- `api/push_subscription.php` — gerencia salvar/recuperar subscriptions de push no banco `push_subscriptions`.

### Arquivos Principais a Modificar
- `admin/escala_detalhe.php` — arquivo principal para o footer sticky e os badges de participantes. Já contém `$myMemberData` (linha ~142) e já busca `su.status` na query de membros (linha ~129).
- `admin/escalas.php` — arquivo para o contador X/Y. Já carrega `participantsMap` com `status` por participante (linha ~53).

### Push
- `includes/web_push_helper.php` — helper de push a ser corrigido. Classe `WebPushHelper` com `sendNotification()`. Método `encrypt()` é atualmente um mock — precisa de implementação AESGCM real.
- `database/setup_push_notifications.sql` — schema da tabela `push_subscriptions` (endpoint, p256dh, auth, user_id).

### CSS e Design System
- `assets/css/pages/detail_v3.css` — CSS da página de detalhe da escala. Adicionar classes de status e footer sticky aqui.
- `assets/css/core/variables.css` — variáveis CSS do design system (cores, radius, etc.).

### Requisitos
- `.planning/REQUIREMENTS.md` §ESCALA — ESC-01 a ESC-05 (todos desta fase).
- `.planning/ROADMAP.md` §Phase 2 — goals, success criteria, e gaps da auditoria profissional.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `$myMemberData` (escala_detalhe.php ~L142): array com dados do usuário logado na escala, incluindo `status` e `is_rehearsed`. Usar para decidir se exibe o footer e qual estado exibir.
- `$participantsMap[$scheduleId]` (escalas.php ~L53): array de participantes por escala, já com `status`. Usar para calcular o contador X/Y sem query adicional.
- Toggle `is_rehearsed` existente: padrão de AJAX inline já implementado na página — seguir o mesmo padrão para a confirmação.

### Established Patterns
- AJAX sem bibliotecas: o projeto usa `fetch()` puro ou `XMLHttpRequest` vanilla. Sem jQuery. Seguir o padrão existente.
- PHP POST para actions: `$_POST['action']` para toggle de rehearsal já existe em escala_detalhe.php (~L28). Para confirmação usar AJAX ao invés de POST form (ESC-02 exige sem reload).
- Design system: `.pib-card` como componente base, `.btn-primary` (azul #3B82F6) e `.btn-danger` (para recusar). Footer fixo: `position: fixed; bottom: 0; left: 0; right: 0`.
- Lucide icons já incluídos via CDN (`data-lucide="check-circle"`, `data-lucide="x-circle"` para os badges).

### Integration Points
- `schedule_users.status`: valores atuais no banco são `confirmed`, `pending`, `absent`, `declined`. A API `confirm_scale.php` usa `confirmed`/`declined` — verificar se `declined` está sendo salvo como-is ou mapeado para `absent` (linha 19-22 do confirm_scale.php: salva o valor diretamente, então `declined` é o valor real do banco para recusa pelo músico, `absent` é para falta registrada pelo admin na Phase 4).
- `push_subscriptions`: a tabela tem `user_id`, `endpoint`, `p256dh`, `auth`. Para disparar push ao salvar escala, buscar todas as subscriptions dos `schedule_users` da escala após o commit.

</code_context>

<specifics>
## Specific Ideas

- Footer sticky deve ter padding-bottom suficiente para não colicar com a barra de navegação do iOS Safari (safe-area-inset-bottom). Usar `padding-bottom: env(safe-area-inset-bottom, 16px)`.
- Ao salvar escala com membros novos vs. edição simples: considerar enviar push apenas para membros novos (evitar notificar quem já estava na escala em edições de observações). Lógica: comparar membros antes/depois do save.

</specifics>

<deferred>
## Deferred Ideas

- Notificação push quando o líder publica um aviso (scope de Phase 8 — Devocional+)
- Deep link no push que abre diretamente a escala específica no app (Phase 9 — melhoria de PWA)
- Confirmação por WhatsApp (backlog v2)

</deferred>

---

*Phase: 02-confirmar-escala*
*Context gathered: 2026-05-17*
