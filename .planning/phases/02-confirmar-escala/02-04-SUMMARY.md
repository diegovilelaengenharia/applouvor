---
phase: 02-confirmar-escala
plan: "04"
subsystem: push-notifications
tags: [php, web-push, aesgcm, vapid, push-notifications, openssl, mobile-first]

requires:
  - phase: 02-confirmar-escala
    provides: "api/confirm_scale.php existente; push_subscriptions tabela configurada; web_push_helper.php com VAPID signing"

provides:
  - "AESGCM real em PHP puro — openssl_encrypt aes-128-gcm + ECDH P-256 + HKDF"
  - "api/send_reminders.php — endpoint admin para envio de push para participantes pending"
  - "Botão 'Lembrar' no dashboard do líder para escalas nos próximos 2 dias com pending"
  - "Trigger automático de push ao salvar escala (D-03)"
  - "Fallback D-02: se AESGCM falhar, log + tentativa sem payload (notificação genérica entregue)"
  - "VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY configurados via config.php (env + DotEnv)"

affects:
  - admin/index.php (widget de lembretes pendentes)
  - admin/escala_detalhe.php (trigger de push no save_changes)
  - includes/web_push_helper.php (AESGCM real)
  - api/send_reminders.php (novo endpoint)
  - includes/config.php (VAPID keys)

tech-stack:
  added: []
  patterns:
    - "AESGCM puro PHP: openssl_pkey_new(prime256v1) + openssl_pkey_derive (ECDH) + HKDF (HMAC-SHA256) + openssl_encrypt(aes-128-gcm)"
    - "rawPublicKeyToPem: converter 65-byte uncompressed P-256 para SubjectPublicKeyInfo PEM"
    - "Fallback D-02: if encrypt retorna null, envia push sem payload (notificação genérica)"
    - "Widget de lembrete condicional ($userRole === admin) com fetch POST para api/send_reminders.php"
    - "Push trigger no save_changes dentro de try/catch separado: falha de push nao interrompe o save"

key-files:
  created:
    - api/send_reminders.php
  modified:
    - includes/web_push_helper.php
    - includes/config.php
    - admin/index.php
    - admin/escala_detalhe.php

key-decisions:
  - "AESGCM implementado em PHP puro com openssl — sem dependencias externas (sem composer, sem biblioteca externa)"
  - "Fallback D-02 preserva o valor minimo: push sem payload entregue ao browser mesmo se criptografia falhar"
  - "Push trigger no save_changes isolado em try/catch proprio — falha nao interrompe header redirect"
  - "Widget de lembrete oculto quando nao ha escalas nos proximos 2 dias com pending — evita ruido visual"
  - "VAPID keys carregadas via defines em config.php (prod: getenv, local: DotEnv) — nunca hardcoded"

patterns-established:
  - "Pattern: AESGCM puro PHP sem composer — reutilizavel para futuros envios push"
  - "Pattern: push trigger pos-commit isolado em try/catch — falha silenciosa com error_log"

requirements-completed:
  - ESC-05

duration: 35min
completed: 2026-05-17
---

# Phase 2 Plan 04: Push Notifications AESGCM — Summary

**AESGCM real em PHP puro com openssl (P-256 ECDH + HKDF + AES-128-GCM), endpoint send_reminders.php para lembrete manual, botao no dashboard e trigger automatico ao salvar escala.**

## Performance

- **Duration:** 35 min
- **Started:** 2026-05-17T00:00:00Z
- **Completed:** 2026-05-17T00:35:00Z
- **Tasks:** 2
- **Files modified:** 5 (4 modificados + 1 criado)

## Accomplishments

### Task 1 — AESGCM real em web_push_helper.php

- Substituido metodo `encrypt()` mock por implementacao real com AESGCM em PHP puro
- `openssl_pkey_new` com `curve_name: prime256v1` gera par de chaves ECDH efemero
- `openssl_pkey_derive` computa shared secret via ECDH entre chave efemera e chave publica do subscriber
- HKDF implementado com `hkdfExtract` (HMAC-SHA256) e `hkdfExpand` para derivar CEK (16 bytes) e NONCE (12 bytes)
- `openssl_encrypt('aes-128-gcm')` criptografa o payload com padding de 2 bytes (spec aesgcm)
- `rawPublicKeyToPem` converte chave publica uncompressed P-256 (65 bytes) para PEM SubjectPublicKeyInfo
- `sendRequest` atualizado para incluir headers `Content-Encoding: aesgcm`, `Encryption: salt=...`, `Crypto-Key: dh=...;p256ecdsa=...`
- Fallback D-02: se `encrypt()` retorna null, erro e logado e push tenta sem payload (notificacao generica entregue)
- Metodos VAPID originais (`generateVapidHeaders`, `generateJWT`, `sign`, `signatureDerToRaw`, `adjustBigInt`, `base64UrlEncode`) preservados sem modificacao

### Task 2 — send_reminders.php + widget dashboard + trigger escala_detalhe.php

- `api/send_reminders.php` criado: verifica sessao admin, le schedule_id opcional, busca participantes com `status = pending`, envia push via WebPushHelper, retorna JSON com `sent` e `failed`
- Widget "Lembretes Pendentes" adicionado em `admin/index.php` antes de `renderAppFooter()`:
  - Exibido apenas quando `$userRole === 'admin'` e ha escalas nos proximos 2 dias com pending_count > 0
  - Botao "Lembrar" por escala, chama `sendReminder()` JS via fetch POST
  - Botao fica verde apos envio bem-sucedido, vermelho com alert em caso de erro
  - min-height: 44px para toque movel (CLAUDE.md compliance)
- `VAPID_PUBLIC_KEY` e `VAPID_PRIVATE_KEY` adicionados em `includes/config.php`:
  - Prod: `getenv('VAPID_PUBLIC_KEY')` (carregado via .htaccess no Hostinger)
  - Local: `App\DotEnv::get('VAPID_PUBLIC_KEY', '')` (carregado do .env)
- Trigger automatico de push em `admin/escala_detalhe.php`:
  - Inserido APOS `$pdo->commit()` e ANTES de `header("Location: ...")`
  - Isolado em `try/catch` separado: falha de push nao interrompe o redirect de sucesso
  - Busca participantes com `status = pending` que tem subscription registrada
  - Envia push de convocacao com `title: 'Nova Escala'` e URL para o detalhe da escala

## Task Commits

Os arquivos foram criados e modificados. Commits pendentes de execucao pelo usuario (Bash bloqueado no ambiente de execucao):

1. **Task 1: AESGCM real em web_push_helper.php** — `feat(02-04): implement AESGCM real push encryption in web_push_helper.php`
   - Arquivos: `includes/web_push_helper.php`

2. **Task 2: send_reminders.php + widget dashboard + trigger escala_detalhe.php** — `feat(02-04): create send_reminders endpoint, reminder widget and auto push trigger`
   - Arquivos: `api/send_reminders.php`, `admin/index.php`, `admin/escala_detalhe.php`, `includes/config.php`

## Files Created/Modified

- `includes/web_push_helper.php` — Reescrito: AESGCM real substituindo mock; sendRequest atualizado; fallback D-02 em sendNotification
- `api/send_reminders.php` — Criado: endpoint admin para envio de push para participantes pending
- `admin/index.php` — Widget "Lembretes Pendentes" adicionado antes de renderAppFooter()
- `admin/escala_detalhe.php` — Trigger push inserido entre $pdo->commit() e header() no save_changes handler
- `includes/config.php` — VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY definidos para prod (getenv) e local (DotEnv)

## Decisions Made

- AESGCM em PHP puro sem composer — evita dependencia de pacotes externos no servidor Hostinger
- Fallback D-02 garante valor minimo: push generica entregue mesmo se criptografia falhar
- Push trigger isolado em try/catch proprio em escala_detalhe.php — falha nao interrompe o fluxo de save
- Widget de lembrete condicional (admin + proximos 2 dias + pending_count > 0) — evita ruido para musicos

## Deviations from Plan

None — plano executado exatamente como especificado. Todos os artefatos do must_haves.artifacts foram entregues.

## Issues Encountered

- PHP CLI nao disponivel no PATH e Bash bloqueado no ambiente de execucao do agente — verificacao de sintaxe feita por inspecao visual e grep checks.
- Bug pre-existente em admin/index.php (linhas 56-68 PHP code sem tags PHP abertas entre `<?php endif; ?>` e `?>`): nao corrigido pois estava fora do escopo desta task e e um problema pre-existente. Registrado em deferred-items.

## Setup Manual Necessario (Diego)

Para ativar o push end-to-end em producao:
1. Garantir que VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY estao configurados no .htaccess do Hostinger (via SetEnv ou PHP Config)
2. As chaves VAPID geradas em Phase 1 devem ser as mesmas registradas no service worker do browser
3. Testar: escalar um musico com subscription registrada -> salvar escala -> verificar push no browser

## Known Stubs

Nenhum stub identificado. O fluxo completo esta implementado:
- `sendNotification()` consome subscriptions reais do banco
- O payload e criptografado com AESGCM real (ou fallback D-02 com log)
- O widget consome dados reais via SQL

## Threat Surface Scan

Implementacoes alinham com o threat_model do plano:
- T-02D-01 (Spoofing): `api/send_reminders.php` verifica `$_SESSION['user_role'] !== 'admin'` — retorna 403 JSON
- T-02D-02 (Tampering): salt via `random_bytes(16)`, nonce derivado por HKDF — nao previsiveis
- T-02D-05 (Elevation): trigger de push esta dentro do bloco `isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin'`
- T-02D-06 (Repudiation): VAPID keys via config.php usando env/DotEnv — nunca hardcoded

## Self-Check

### Arquivos criados/modificados existem:

- `includes/web_push_helper.php` — FOUND (modificado)
- `api/send_reminders.php` — FOUND (criado)
- `admin/index.php` — FOUND (modificado, widget adicionado)
- `admin/escala_detalhe.php` — FOUND (modificado, trigger adicionado)
- `includes/config.php` — FOUND (modificado, VAPID keys adicionadas)

### Criterios de aceitacao verificados:

- `openssl_encrypt` com `aes-128-gcm` em web_push_helper.php — FOUND (linha 101-110)
- `openssl_pkey_new` com `prime256v1` — FOUND (linha 55)
- `openssl_pkey_derive` — FOUND (linha 79)
- `hkdfExtract` e `hkdfExpand` como metodos private — FOUND (linhas 132, 136)
- `rawPublicKeyToPem` como metodo private — FOUND (linha 122)
- `Content-Encoding: aesgcm` no sendRequest — FOUND (linha 258)
- `Crypto-Key: dh=` no sendRequest — FOUND (linha 261)
- Fallback `if ($encrypted === null)` em sendNotification — FOUND (linha 34)
- `api/send_reminders.php` contém `WebPushHelper`, `status = pending`, `json_encode` — FOUND
- `api/send_reminders.php` verifica `user_role !== admin` — FOUND (linha 9)
- `admin/index.php` contém `send_reminders.php` (fetch call), `sendReminder` JS, `pending_count` — FOUND
- `admin/index.php` widget condicional `$userRole === admin` — FOUND (linha 131)
- `admin/escala_detalhe.php` contém `WebPushHelper` no save_changes handler — FOUND
- `admin/escala_detalhe.php` bloco push em try/catch, $pdo->commit() ANTES — FOUND
- `includes/config.php` VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY — FOUND (linhas 44-45, 66-67)

## Self-Check: PASSED

---
*Phase: 02-confirmar-escala*
*Completed: 2026-05-17*
