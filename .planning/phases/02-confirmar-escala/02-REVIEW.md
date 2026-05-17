---
phase: 02-confirmar-escala
reviewed: 2026-05-17T00:00:00Z
depth: standard
files_reviewed: 7
files_reviewed_list:
  - admin/escala_detalhe.php
  - assets/css/pages/detail_v3.css
  - admin/escalas.php
  - includes/web_push_helper.php
  - api/send_reminders.php
  - admin/index.php
  - includes/config.php
findings:
  critical: 6
  warning: 7
  info: 4
  total: 17
status: issues_found
---

# Phase 02: Code Review Report

**Reviewed:** 2026-05-17
**Depth:** standard
**Files Reviewed:** 7
**Status:** issues_found

## Summary

Os arquivos implementam a UI de detalhes de escala (V3), o rodapé fixo de confirmação de presença, notificações push (AESGCM/VAPID) e o painel de lembretes do líder. A camada de segurança HTTP básica (PDO, htmlspecialchars) está presente, mas foram encontrados seis problemas críticos: ausência de verificação de autenticação em páginas de admin, SSRF via avatar URL externa não validada, ausência de autorização no endpoint de confirmação, HKDF com info-string incorreta (incompatível com RFC 8291/aesgcm), externalização fraca de segredo VAPID privado, e revelação de mensagem de erro de banco em produção no endpoint de lembrete. Há também sete advertências relevantes de lógica e qualidade.

---

## Critical Issues

### CR-01: Ausência de checkLogin()/checkAdmin() em admin/escala_detalhe.php

**File:** `admin/escala_detalhe.php:1-22`
**Issue:** O arquivo não chama `checkLogin()` nem `checkAdmin()`. Qualquer visitante não autenticado acessa dados de escalas, membros, repertório e comentários diretamente via `?id=N`. Ações protegidas apenas por `$_SESSION['user_role'] === 'admin'` no corpo do POST podem ser ignoradas ao reproduzir manualmente um POST sem sessão se a variável não estiver definida — `$_SESSION['user_role']` seria `null`, e a comparação com `'admin'` falharia silenciosamente, mas o bloco de leitura de dados exibe tudo sem proteção.

**Fix:**
```php
// Adicionar imediatamente após require_once '../includes/db.php':
require_once '../includes/auth.php';
checkLogin(); // exige sessão válida para qualquer usuário
// Remover apenas a verificação inline de admin nos POSTs, manter checkLogin() no topo
```

---

### CR-02: Ausência de checkLogin() em admin/escalas.php

**File:** `admin/escalas.php:1-11`
**Issue:** Mesmo problema que CR-01. O arquivo não inclui `auth.php` nem chama `checkLogin()`. A linha 11 faz fallback para `$_SESSION['user_id'] ?? 1`, o que significa que em ausência de sessão o sistema usa o user_id `1` (presumivelmente o admin) para filtrar "Minhas Escalas". Usuários não autenticados veem a lista de escalas e os avatares dos participantes.

**Fix:**
```php
// Após os requires existentes:
require_once '../includes/auth.php';
checkLogin();
// Remover o fallback hardcoded:
// ANTES: $loggedUserId = $_SESSION['user_id'] ?? 1;
// DEPOIS:
$loggedUserId = $_SESSION['user_id'];
```

---

### CR-03: SSRF — URL de avatar de terceiros refletida sem validação em escalas.php

**File:** `admin/escalas.php:237-239`
**Issue:** O campo `photo` de cada participante é lido do banco e, se não contiver `http`, recebe o prefixo `../`. Se contiver `http`, é inserido diretamente em `src=` de uma tag `<img>`. Não há validação de domínio. Um usuário que consiga editar seu próprio campo `photo` (ou que um admin insira) pode apontar para um endpoint interno (ex.: `http://127.0.0.1/admin/...`), causando SSRF via browser do visitante. Além disso, o valor não é passado por `htmlspecialchars()`, abrindo XSS se o campo contiver `"` ou `javascript:`.

```php
// Linha 238 — SEM htmlspecialchars:
$pAvatar = !empty($p['photo']) ? $p['photo'] : 'https://ui-avatars.com/api/?...';
// ...
<img src="<?= $pAvatar ?>" ...>
```

**Fix:**
```php
// Sanitizar sempre com htmlspecialchars e rejeitar schemas não-http(s):
$pAvatar = !empty($p['photo']) ? $p['photo'] : null;
if ($pAvatar) {
    // Aceitar apenas URLs relativas ou https://
    if (strpos($pAvatar, 'http') !== false && !preg_match('#^https?://#', $pAvatar)) {
        $pAvatar = null; // schema inválido
    }
    if (strpos($pAvatar, 'http') === false) {
        $pAvatar = '../' . ltrim($pAvatar, '/');
    }
}
$pAvatar = $pAvatar ? htmlspecialchars($pAvatar, ENT_QUOTES, 'UTF-8')
         : 'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=random';
```

---

### CR-04: HKDF info-string incorreta — notificações push incompatíveis com RFC 8291 / Chrome

**File:** `includes/web_push_helper.php:89-94`
**Issue:** A derivação HKDF para `aesgcm` (RFC 7516 / draft-ietf-httpbis-encryption-encoding) usa o PRK derivado de `HMAC-SHA256(authToken, sharedSecret)` como salt, e a info como `salt || localPublicKey || userPublicKey || infoLabel`. Esse formato **não** segue a especificação RFC 8030 / draft-ietf-webpush-encryption que exige:

```
context = "P-256\x00" + len(ua_public) + ua_public + len(as_public) + as_public
info    = "Content-Encoding: " + type + "\x00" + "P-256\x00" + ...
PRK     = HKDF-Extract(auth, IKM)
key     = HKDF-Expand(PRK, "Content-Encoding: aesgcm\x00" + context, 16)
nonce   = HKDF-Expand(PRK, "Content-Encoding: nonce\x00" + context, 12)
```

A implementação atual omite o prefixo de contexto `"P-256\x00"` e os comprimentos de chave (2 bytes each), passando apenas `salt || localPub || userPub` diretamente como info do HKDF. Isso gera CEK e nonce diferentes dos esperados pelo browser. O resultado é que as notificações chegam mas o browser **não consegue descriptografar o payload** — a notificação aparece vazia ou não aparece (depende do browser). Chrome/Firefox descartam silenciosamente payloads que falham na descriptografia AESGCM.

**Fix:** Implementar o contexto correto conforme a especificação:
```php
// Contexto conforme draft-ietf-webpush-encryption:
$contextLabel = "P-256\x00";
$uaLen = pack('n', strlen($userPublicKey));   // 2 bytes big-endian
$asLen = pack('n', strlen($localPublicKeyRaw)); // 2 bytes big-endian
$context = $contextLabel . $uaLen . $userPublicKey . $asLen . $localPublicKeyRaw;

$cekInfo   = "Content-Encoding: aesgcm\x00" . $context;
$nonceInfo = "Content-Encoding: nonce\x00"  . $context;

// PRK usa authToken como salt, resultado do ECDH como IKM
$prk = $this->hkdfExtract($userAuthToken, $sharedSecret);

// Expand com salt separado (segundo nível de HKDF para content encryption key)
// Conforme spec, o "salt" entra em hkdfExtract separado do context info
$prk2 = $this->hkdfExtract($salt, $prk); // salted PRK
$cek   = $this->hkdfExpand($prk2, $cekInfo, 16);
$nonce = $this->hkdfExpand($prk2, $nonceInfo, 12);
```

---

### CR-05: api/confirm_scale.php — ausência de verificação de autenticação explícita

**File:** `api/confirm_scale.php:7`
**Issue:** O endpoint usa `$_SESSION['user_id'] ?? 0` e depois verifica `if (!$userId ...)`. Se a sessão não existir, `$userId` será `0` e a requisição é rejeitada — até aqui funciona. O problema é que `auth.php` é incluído mas `checkLogin()` não é chamado, e mais importante: **não há verificação de que o usuário da sessão está de fato escalado para `$scheduleId`**. Qualquer usuário autenticado pode chamar o endpoint com o `schedule_id` de outro músico e o `status` de sua escolha, atualizando o status de qualquer participante qualquer de qualquer escala.

```php
// Linha 19-25 — sem WHERE user_id = $userId efetivo:
$stmt = $pdo->prepare("
    UPDATE schedule_users 
    SET status = ? 
    WHERE schedule_id = ? AND user_id = ?
");
$stmt->execute([$status, $scheduleId, $userId]);
```

A cláusula `AND user_id = ?` usa o `$userId` da sessão, então na prática um usuário só altera a sua própria linha. Porém: **o `$stmt->rowCount()` não é verificado**. Se a linha não existir (usuário não está na escala), o UPDATE não faz nada mas retorna `success: true`. Isso mascara tentativas inválidas sem indicação de erro. Adicionalmente, o retorno bem-sucedido não distingue "linha atualizada" de "nenhuma linha afetada".

**Fix:**
```php
$stmt->execute([$status, $scheduleId, $userId]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Você não está escalado para este evento.']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
```

---

### CR-06: Revelação de mensagem de exceção em produção — api/send_reminders.php

**File:** `api/send_reminders.php:91-93`
**Issue:** O bloco catch retorna `$e->getMessage()` diretamente na resposta JSON. Em produção, mensagens de exceção PDO podem conter nome do banco, nome de tabela, query SQL ou dados internos.

```php
catch (Exception $e) {
    error_log('send_reminders.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
```

**Fix:**
```php
catch (Exception $e) {
    error_log('send_reminders.php error: ' . $e->getMessage());
    $msg = defined('APP_DEBUG') && APP_DEBUG
        ? 'Erro interno: ' . $e->getMessage()
        : 'Erro interno. Verifique os logs do servidor.';
    echo json_encode(['success' => false, 'message' => $msg]);
}
```

---

## Warnings

### WR-01: Fallback de push com payload vazio pode violar spec — web_push_helper.php

**File:** `includes/web_push_helper.php:34-37`
**Issue:** Quando `encrypt()` retorna `null`, o código continua e envia a requisição com `ciphertext: ''`, `salt: ''`, `localPublicKey: ''`. O `sendRequest()` na linha 257 verifica `!empty($encrypted['ciphertext'])` e, se vazio, omite os headers de criptografia mas ainda envia um POST ao push service. Isso resulta em uma notificação sem `Content-Encoding: aesgcm` mas com `Content-Type: application/octet-stream` ausente — formato inválido para push services que exigem payload sempre criptografado (FCM, por exemplo). O push service pode retornar 400, ou pior, 410 (subscription expirada) e a subscription seria removida erroneamente.

**Fix:** Se `encrypt()` falhar, lançar exceção ao invés de degradar silenciosamente, ou enviar requisição com body vazio e sem headers de criptografia (notificação "sem título/corpo" é válida segundo spec se não houver Content-Encoding).

---

### WR-02: `strftime()` depreciada no PHP 8.1+ — escala_detalhe.php e escalas.php

**File:** `admin/escala_detalhe.php:237` | `admin/escalas.php:308`
**Issue:** `strftime('%b', ...)` está depreciada desde PHP 8.1 e removida no PHP 9. O resultado também depende do locale do sistema operacional do servidor. Em ambiente Hostinger com locale padrão, pode retornar o mês em inglês ao invés de português.

**Fix:**
```php
// Substituir por:
$monthAbbr = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
$abbr = $monthAbbr[(int)$date->format('n') - 1];
```

---

### WR-03: Ausência de CSRF token em formulários POST — escala_detalhe.php

**File:** `admin/escala_detalhe.php:365-376`, `504-510`, `521-529`
**Issue:** Os formulários de toggle_rehearsal, delete_comment e add_comment não possuem CSRF token. Um atacante pode hospedar uma página que submete silenciosamente um formulário para `escala_detalhe.php` com `action=toggle_rehearsal` ou `action=delete_comment` enquanto a vítima está autenticada. O cookie de sessão com `SameSite=Lax` mitiga CSRF via links, mas **não** protege contra `<form method="POST">` em submissões de formulários cross-site (SameSite=Lax permite POST cross-site vindo de navegação top-level, mas não de submissões via fetch/xhr — porém formulários HTML comuns são cobertos).

**Fix:** Gerar e validar um token CSRF por sessão:
```php
// Em auth.php ou no início da página:
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Em cada form:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
// Na validação POST:
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('CSRF token inválido');
}
```

---

### WR-04: XSS em innerHTML de notas do evento no modal de edição — escala_detalhe.php

**File:** `admin/escala_detalhe.php:831-834`
**Issue:** Na função `closeInfoModal(true)`, o valor de `input_notes` é inserido via `innerHTML` sem sanitização:

```js
noteDiv.innerHTML = `<i data-lucide="sticky-note" width="12"></i> ${n}`;
```

Se um admin digitar `<img src=x onerror=alert(1)>` no campo de observações, isso é executado no contexto do modal (antes de salvar). Após salvar, o valor é protegido por `htmlspecialchars` no PHP, mas no cliente a execução ocorre imediatamente durante a edição.

**Fix:**
```js
// Usar textContent para o texto, mantendo o ícone separado:
const icon = document.createElement('i');
icon.setAttribute('data-lucide', 'sticky-note');
noteDiv.innerHTML = ''; // limpar
noteDiv.appendChild(icon);
noteDiv.appendChild(document.createTextNode(' ' + n));
```

---

### WR-05: PDOException exposta em produção via die() — escala_detalhe.php

**File:** `admin/escala_detalhe.php:75`, `149`
**Issue:** `die($e->getMessage())` expõe detalhes internos de exceção PDO (queries, nomes de tabela) para o usuário em produção nos blocos de delete_schedule e save_changes.

**Fix:**
```php
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('escala_detalhe error: ' . $e->getMessage());
    if (defined('APP_DEBUG') && APP_DEBUG) die($e->getMessage());
    die('Ocorreu um erro ao salvar. Tente novamente.');
}
```

---

### WR-06: Ausência de validação de comprimento no comentário — escala_detalhe.php

**File:** `admin/escala_detalhe.php:37-44`
**Issue:** O servidor só valida `!empty($comment)`. Não há limite de tamanho. Um usuário autenticado pode submeter um comentário de megabytes, saturando o campo `TEXT` ou `VARCHAR` do banco, dependendo do schema, e consumindo armazenamento desnecessário.

**Fix:**
```php
$comment = trim($_POST['comment']);
if (!empty($comment) && mb_strlen($comment, 'UTF-8') <= 2000) {
    // inserir
} else {
    // ignorar silenciosamente ou redirecionar com erro
}
```

---

### WR-07: Safe-area-inset insuficiente no footer fixo — detail_v3.css

**File:** `assets/css/pages/detail_v3.css:370-375`, `428-433`
**Issue:** O padding-bottom dos footers fixos usa `calc(12px + env(safe-area-inset-bottom, 16px))`. O fallback `16px` é para dispositivos que não suportam `env()` (navegadores antigos). Em iPhones com notch/Dynamic Island, `safe-area-inset-bottom` pode chegar a 34px. O problema está no wrapper `.has-confirm-footer` que adiciona apenas `padding-bottom: 90px` (linha 468). Se o footer tiver `safe-area-inset-bottom` de 34px, a altura real do footer será `12 + 34 + altura_botoes ≈ 46 + ~52px = ~98px`, mas o wrapper assume 90px — o último elemento do conteúdo fica escondido atrás do footer em dispositivos com notch.

**Fix:**
```css
.has-confirm-footer {
    padding-bottom: calc(90px + env(safe-area-inset-bottom, 0px));
}
```

---

## Info

### IN-01: Fallback hardcoded `$_SESSION['user_id'] ?? 1` deve ser removido — escalas.php

**File:** `admin/escalas.php:11`
**Issue:** O comentário diz "ou hardcoded 1 para dev se não tiver sessão ainda". Esse fallback nunca deve existir em um branch que vai para produção. Após CR-02 ser corrigido (checkLogin()), este fallback se torna morto, mas deveria ser removido explicitamente para evitar confusão futura.

---

### IN-02: `time()` no href do CSS gera cache miss em cada page load — escala_detalhe.php

**File:** `admin/escala_detalhe.php:218`
**Issue:** `href="../assets/css/pages/detail_v3.css?v=<?= time() ?>"` força o browser a recarregar o CSS em toda requisição, anulando o cache do browser.

**Fix:** Usar a constante `APP_VERSION` ou um hash do arquivo:
```php
href="../assets/css/pages/detail_v3.css?v=<?= APP_VERSION ?>"
```

---

### IN-03: admin/index.php contém PHP raw fora de tags PHP — index.php

**File:** `admin/index.php:56-68`
**Issue:** As linhas 56-68 contêm código PHP (`$groupedCards = [...]`, `$categoryNames = [...]`) fora de qualquer bloco `<?php ... ?>`. Esse trecho é renderizado como HTML literal no browser, exibindo o código fonte PHP visível para o usuário. É provável que seja um erro de edição (falta o `<?php` após o `?>` da linha 54).

**Fix:**
```php
<?php
// 2. Organizar Cards por Categoria (Sistema Original)
$groupedCards = [ ... ];
$categoryNames = [ ... ];
?>
```

---

### IN-04: Ausência de feedback visual de erro no botão "Lembrar" — admin/index.php

**File:** `admin/index.php:197-200`
**Issue:** No bloco `.catch`, o botão exibe "Erro" mas permanece desabilitado se `data.success` for falso sem tratar o estado visual adequadamente. O código no bloco `.then` já trata isso (linha 191: `btn.disabled = false`), mas o `.catch` apenas muda o texto e habilita — sem restaurar a cor original do botão (#f97316), deixando-o vermelho permanentemente após erro de rede.

**Fix:**
```js
.catch(function() {
    btn.textContent = 'Erro de rede';
    btn.style.background = '#dc2626';
    btn.disabled = false;
    setTimeout(() => {
        btn.textContent = 'Lembrar';
        btn.style.background = '#f97316';
    }, 3000);
});
```

---

_Reviewed: 2026-05-17_
_Reviewer: Claude (gsd-code-reviewer)_
_Depth: standard_
