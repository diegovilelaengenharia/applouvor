---
status: partial
phase: 02-confirmar-escala
source: [02-VERIFICATION.md]
started: 2026-05-17
updated: 2026-05-17
---

## Current Test

[aguardando validação manual do Diego]

## Tests

### 1. Footer sticky no celular — exibição e interação com sessão real
expected: Músico participante com status 'pending' vê footer fixo na parte inferior com botões "Confirmar" e "Recusar". Clicar muda o status via AJAX sem reload. Footer exibe "Confirmado" ou "Recusado" com botão "Alterar".
result: [pending]

### 2. Safe-area-inset-bottom em iPhone com home indicator
expected: Em iPhone X+ com home indicator, o footer não fica escondido atrás da home bar. padding-bottom usa `calc(12px + env(safe-area-inset-bottom, 16px))`.
result: [pending]

### 3. Push end-to-end — notificação entregue ao browser
expected: Com VAPID_PUBLIC_KEY e VAPID_PRIVATE_KEY configurados no Hostinger e subscription ativa no banco, clicar "Lembrar" no dashboard envia push que aparece no browser do músico pendente.
prereq: Configurar VAPID keys no .htaccess do Hostinger (SetEnv VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY)
result: [pending]

### 4. Fallback D-02 — erro de criptografia não quebra o fluxo
expected: Se openssl falhar (ex: PHP sem suporte a prime256v1), o endpoint retorna success mas loga o erro — não lança exceção nem retorna 500.
result: [pending]

### 5. Contador X/Y após ciclo completo de confirmação
expected: Após músico confirmar via footer, atualizar a página da listagem `admin/escalas.php` e ver o contador incrementado (ex: "1/3 confirmados" → "2/3 confirmados").
result: [pending]

## Summary

total: 5
passed: 0
issues: 0
pending: 5
skipped: 0
blocked: 0

## Gaps
