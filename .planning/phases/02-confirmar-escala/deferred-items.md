# Deferred Items — Phase 02-confirmar-escala

## Bug Pre-existente: PHP inline sem tags em admin/index.php

**Encontrado durante:** Task 2 do Plano 02-04

**Descricao:** Entre `<?php endif; ?>` (linha 54) e `?>` (linha 68) em admin/index.php, existe codigo PHP sem tag `<?php` de abertura:

```php
// 2. Organizar Cards por Categoria (Sistema Original)
$groupedCards = [
    'gestao' => ['escalas', 'repertorio', 'membros', 'agenda', 'historico'],
    ...
];
$categoryNames = [...];
```

Esse codigo provavelmente funciona porque existe um `<?php` escondido na linha anterior que ainda esta aberto — ou o codigo e renderizado como HTML literal e o PHP e executado pelo bloco `<?php ... ?>` que nao fechou antes. Requer inspecao para entender o fluxo exato.

**Status:** Fora do escopo do Plano 02-04 (pre-existente, nao causado pelas modificacoes deste plano)

**Acao recomendada:** Na proxima sessao de desenvolvimento, inspecionar e corrigir as tags PHP em admin/index.php para garantir que o fluxo PHP/HTML esta correto.

---

## Commits Git Pendentes (Bash bloqueado no ambiente de execucao)

Os seguintes arquivos foram criados/modificados mas precisam ser commitados:

### Task 1 — AESGCM (incluir no mesmo commit ou separado):
- `includes/web_push_helper.php`

**Mensagem sugerida:**
```
feat(02-04): implement real AESGCM push encryption in web_push_helper.php

- Replace mock encrypt() with real ECDH P-256 + HKDF + AES-128-GCM implementation
- Add rawPublicKeyToPem, hkdfExtract, hkdfExpand helper methods
- Update sendRequest with Content-Encoding: aesgcm and Crypto-Key: dh= headers
- Add D-02 fallback: if encrypt fails, log error and send push without payload
```

### Task 2 — send_reminders + dashboard + escala_detalhe (incluir num commit ou separar):
- `api/send_reminders.php` (novo)
- `admin/index.php` (widget de lembretes pendentes)
- `admin/escala_detalhe.php` (trigger push no save_changes)
- `includes/config.php` (VAPID keys)

**Mensagem sugerida:**
```
feat(02-04): add send_reminders endpoint, reminder widget and auto push trigger

- Create api/send_reminders.php: admin-only endpoint to push pending participants
- Add reminder widget in admin/index.php: shows escalas with pending_count > 0
- Add auto push trigger in escala_detalhe.php save_changes after $pdo->commit()
- Add VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to includes/config.php (prod + local)
```

### Metadata (documentacao):
- `.planning/phases/02-confirmar-escala/02-04-SUMMARY.md` (novo)
- `.planning/STATE.md` (atualizado)
- `.planning/ROADMAP.md` (atualizado)

**Mensagem sugerida:**
```
docs(02-04): complete push notifications plan — ESC-05 delivered
```

**Comandos para executar no terminal:**
```bash
cd "c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor"

# Task 1
git add includes/web_push_helper.php
git commit -m "feat(02-04): implement real AESGCM push encryption in web_push_helper.php"

# Task 2
git add api/send_reminders.php admin/index.php admin/escala_detalhe.php includes/config.php
git commit -m "feat(02-04): add send_reminders endpoint, reminder widget and auto push trigger"

# Metadata
git add .planning/phases/02-confirmar-escala/02-04-SUMMARY.md .planning/STATE.md .planning/ROADMAP.md .planning/phases/02-confirmar-escala/deferred-items.md
git commit -m "docs(02-04): complete push notifications plan -- ESC-05 delivered"
```
