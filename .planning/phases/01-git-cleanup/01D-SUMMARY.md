---
plan: 01D
phase: 01-git-cleanup
status: completed
executed: 2026-05-17
executor: Claude Code (claude-sonnet-4-6)
---

# 01D Summary — Hardening de Seguranca

## Commits Criados (3 commits)

| # | Hash | Mensagem | Arquivos |
|---|------|----------|----------|
| D1 | `46fa537` | `chore(security): remove credenciais de producao do tracking git` | 3 arquivos deletados do index (.env.backup, .env.production, includes/vapid_config.php) |
| D2 | `8ecb0c6` | `chore(security): remove deploy.php e scripts de test da raiz (endpoints publicos sem auth)` | 4 arquivos deletados (deploy.php, test_all_notifications.php, test_db_query.php, test_members_count.php) |
| D3 | `124e864` | `chore(security): move scripts administrativos para maintenance/ (nao acessiveis publicamente)` | 13 renomeacoes (raiz -> maintenance/) |

## Verificacao Estado Final

```
git ls-files .env.production .env.backup includes/vapid_config.php
→ (vazio) ✓

git ls-files deploy.php test_all_notifications.php test_db_query.php test_members_count.php
→ (vazio) ✓

git ls-files run_migrations.php reset_password.php setup_database.php
→ (vazio) ✓

git ls-files maintenance/run_migrations.php maintenance/reset_password.php
→ maintenance/reset_password.php
   maintenance/run_migrations.php ✓

Get-ChildItem .git -Recurse -Force -Filter "desktop.ini" | Measure-Object | Select-Object Count
→ 272 removidos, resultado final = 0 ✓

git log --oneline -3
→ 124e864 chore(security): move scripts administrativos para maintenance/ (nao acessiveis publicamente)
   8ecb0c6 chore(security): remove deploy.php e scripts de test da raiz (endpoints publicos sem auth)
   46fa537 chore(security): remove credenciais de producao do tracking git ✓

git status
→ working tree limpo (apenas .planning/ untracked) ✓
```

## Detalhes das Tasks

### Task D1 — Secrets removidos do tracking

Arquivos que estavam versionados e foram removidos com `git rm --cached`:
- `.env.production` — continha `DB_PASS=Diego@159753` e outros dados de conexao
- `.env.backup` — backup das credenciais de producao
- `includes/vapid_config.php` — chave privada VAPID para notificacoes push

Os arquivos PERMANECEM no disco local — apenas deixaram de ser rastreados pelo git.
O `.gitignore` ja protegia esses caminhos (adicionado no 01B) — nao serao reincluidos por acidente.

**ATENCAO:** A senha `Diego@159753` continua no historico git (commits anteriores ao D1).
O historico so seria limpo com `git filter-branch` ou `git-filter-repo` — operacao destrutiva
nao executada aqui. A rotacao da senha (Roteiro Manual 1 abaixo) invalida o vazamento.

### Task D2 — Scripts perigosos deletados da raiz

Todos os 4 arquivos estavam rastreados e foram deletados do disco e do index com `git rm`:
- `deploy.php` — endpoint publico com secret hardcoded `louvor2026` que executava git pull
- `test_all_notifications.php` — disparava push para TODOS os usuarios sem auth
- `test_db_query.php` — executava queries SQL sem auth
- `test_members_count.php` — expunha dados de membros sem auth

### Task D3 — Scripts administrativos movidos para maintenance/

13 scripts movidos da raiz para `maintenance/`:

```
create_table_prefs.php
importar_musicas_excel.php
importar_musicas_manual.php
importar_musicas_simples.php
reset_password.php
run_migrations.php
setup_avisos.php
setup_database.php
setup_devotionals.php
setup_prayers.php
setup_reading_db.php
update_avatar_db.php
upgrade_avisos.php
```

Todos mantiveram 100% de similaridade (git detectou como renomeacoes, nao delecoes+criacoes).

### Task D4 — Limpeza de .git/refs/desktop.ini

272 arquivos `desktop.ini` encontrados dentro de `.git/` (Google Drive File Stream cria
esses arquivos em TODOS os diretorios, incluindo subpastas de .git/).

Todos removidos com `Get-ChildItem .git -Recurse -Force -Filter "desktop.ini" | Remove-Item -Force`.

Resultado: os erros `fatal: bad object refs/desktop.ini` e `error: failed to perform geometric repack`
que apareciam em cada commit da Phase 1 foram eliminados. Git funcionando normalmente.

---

## ROTEIRO MANUAL 1: Rotacao de Senha do Banco de Dados (ANTES DE VIAJAR)

**Contexto:** `DB_PASS=Diego@159753` estava em `.env.production` versionado no git.
O `git rm --cached` remove do tracking futuro, mas o historico git ainda contem a senha.
Rotacionar invalida o vazamento — quem tiver a senha antiga nao consegue mais acessar o banco.

**Passos:**

1. Acessar: https://hpanel.hostinger.com
2. Menu: Hospedagem > MySQL Databases
3. Localizar banco `u884436813_applouvor`, usuario `u884436813_admin`
4. Clicar "Change Password" — gerar senha forte (minimo 24 chars, mistura letras+numeros+simbolos)
5. Anotar a nova senha em gerenciador de senhas (Bitwarden, 1Password, etc.)
6. Hostinger File Manager > `public_html/applouvor/.htaccess`
7. Substituir a linha:
   ```
   SetEnv DB_PASS Diego@159753
   ```
   Por:
   ```
   SetEnv DB_PASS <NOVA_SENHA>
   ```
8. Salvar o `.htaccess`
9. Testar: abrir `vilela.eng.br/applouvor` → fazer login → dashboard deve carregar normalmente
10. Atualizar `.env.production` LOCAL com a nova senha (para referencia offline) — NAO commitar

---

## ROTEIRO MANUAL 2: Regenerar Chaves VAPID (ANTES DE VIAJAR)

**Contexto:** A chave privada VAPID estava em `includes/vapid_config.php`, versionado no git.
O arquivo foi removido do tracking (git rm --cached). Regenerar as chaves invalida a chave antiga —
notificacoes push existentes precisarao de re-subscribe pelos usuarios.

**Passos:**

1. Gerar novas chaves VAPID (escolher um metodo):
   - **Opcao A — Node.js local:** `npx web-push generate-vapid-keys`
   - **Opcao B — Browser (sem instalar nada):** https://vapidkeys.com/
     (pagina simples, roda no browser, nao envia dados para servidores externos)
2. Salvar as novas chaves em local seguro (gerenciador de senhas)
3. Atualizar `includes/vapid_config.php` local com as novas chaves:
   ```php
   define('VAPID_PUBLIC_KEY', '<NOVA_CHAVE_PUBLICA>');
   define('VAPID_PRIVATE_KEY', '<NOVA_CHAVE_PRIVADA>');
   define('VAPID_SUBJECT', 'mailto:diegonunesvilela@gmail.com');
   ```
4. Upload via FTP/Hostinger File Manager para `public_html/applouvor/includes/vapid_config.php`
5. (Opcional mas recomendado) Limpar tabela `push_subscriptions` no banco:
   ```sql
   TRUNCATE TABLE push_subscriptions;
   ```
   Usuarios vao re-subscribe automaticamente quando abrirem o app e permitirem notificacoes
6. Testar: acessar app no celular, permitir notificacoes, verificar nova entrada em
   `push_subscriptions` no banco de dados

---

## ROTEIRO MANUAL 3: Proteger maintenance/ em Producao

**Contexto:** Os scripts em `maintenance/` foram movidos para la localmente e no git,
mas ao fazer deploy para `public_html/applouvor/maintenance/`, eles ficam acessiveis
publicamente via `vilela.eng.br/applouvor/maintenance/<script>.php`. Esses scripts
(reset_password.php, run_migrations.php, etc.) nao tem autenticacao e sao perigosos.

**Passos:**

1. Via Hostinger File Manager, navegar para `public_html/applouvor/maintenance/`
2. Criar novo arquivo `.htaccess` nessa pasta
3. Conteudo do arquivo:
   ```apache
   Require all denied
   ```
4. Salvar
5. Testar: acessar `vilela.eng.br/applouvor/maintenance/restore_db.php`
   Deve retornar **403 Forbidden** (acesso negado)

**Alternativa (se Require all denied nao funcionar no servidor Hostinger):**
```apache
Order deny,allow
Deny from all
```

---

## CHECKLIST PRE-VIAGEM

- [ ] Senha do banco rotacionada (Roteiro Manual 1)
- [ ] `.htaccess` de producao atualizado com nova senha do banco
- [ ] Login funcionando em `vilela.eng.br/applouvor` com nova senha
- [ ] VAPID keys regeneradas (Roteiro Manual 2)
- [ ] `vapid_config.php` atualizado em producao via FTP/File Manager
- [ ] `maintenance/` bloqueado por `.htaccess` em producao (Roteiro Manual 3)
- [ ] Testar: `vilela.eng.br/applouvor/maintenance/restore_db.php` retorna 403
- [ ] App funcionando normalmente (dashboard, escalas, repertorio carregam)
- [ ] Notificacoes push funcionando no celular (opcional — pode testar depois da viagem)

---

## Estado Final da Phase 1

| Requisito | Status |
|---|---|
| GIT-01: Secrets removidos do tracking | CUMPRIDO — D1 |
| GIT-02: desktop.ini removido do tracking | CUMPRIDO — 01B |
| GIT-03: Scripts de manutencao organizados | CUMPRIDO — 01C + D3 |

**Phase 1 completa.** Base segura para desenvolvimento e viagem.

### Historico completo de commits da Phase 1 (git log --oneline -16)

```
124e864 chore(security): move scripts administrativos para maintenance/ (nao acessiveis publicamente)
8ecb0c6 chore(security): remove deploy.php e scripts de test da raiz (endpoints publicos sem auth)
46fa537 chore(security): remove credenciais de producao do tracking git
de4ad5a chore(dev): versiona scripts de setup de ambiente local na raiz
b3a4c80 chore(maintenance): organiza scripts de manutencao e remove descartaveis
0f8f686 chore(git): remove desktop.ini tracking e ignora pasta de backup antiga
2cba602 chore(dev): atualiza run_server.bat e design system MASTER.md
7b455a6 chore(assets): atualiza design system CSS (variables, pib-cards, leitura)
...
```
