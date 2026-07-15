# CHANGELOG — applouvor

Formato: `## AAAA-MM-DD` + itens `feat/fix/docs/chore`. Linha nova NO TOPO.

## 2026-07-16 (FASE 01)
- feat(site): FASE 01 (ciclo v7) — fundação MVC reconstruída do zero (código novo, não
  copiado do legado v6): autoloader PSR-4 próprio (`src/autoload.php`, sem composer/vendor —
  o deploy não roda build), `App\Router` com params via regex e 404 tratado, `Controller`/
  `Model` base (`render/json/redirect`, `all/find/where`), `PageController::home()` +
  view `app/home.php` como rota de prova (não toca banco).
- feat(site): `src/config/db.php` isola a conexão PDO (separado de `config.php`, que o
  `diag.php` continua usando sozinho para o smoke test de credenciais).
- chore(site): `index.php` vira ponte simples para `router.php`; `.htaccess` ganha a regra de
  rewrite do front controller (`RewriteCond !-f !-d` → `router.php?route=$1`), somada às regras
  de segurança da FASE 00.
- Verificado localmente (XAMPP): `php -l` limpo em 11/11 arquivos (1 bug achado e corrigido —
  `*/` literal dentro de um comentário fechava o bloco cedo); `GET /` → 200 view nova; `GET
  /diag.php` → `{"db":"OK"}` sem regressão; rota inexistente → 404 tratado. Push de controle em
  produção pendente de OK do Diego.

## 2026-07-16 (FASE 00)
- chore(site)!: FASE 00 (ciclo v7) — `site/` resetado para esqueleto mínimo de infra, código
  NOVO (não copiado do legado). Removidas as 53 telas MVC do ciclo v6 (Controllers/Models/
  Views/assets/database/router.php/composer.json) — recuperáveis via git history
  (`67775ef`) e `applouvor-historico/`. Ficam só `index.php`, `src/config/config.php`,
  `diag.php`, `.htaccess`, `.env.example`.
- fix(site): `config.php` agora lê credenciais de banco só via `getenv()` — falha ruidosa
  (`RuntimeException` nomeando a variável) se `DB_HOST`/`DB_NAME`/`DB_USER` não estiverem
  definidas, em vez do fallback silencioso do ciclo v6. `.env` local segue como conveniência
  de dev (gitignored, nunca vai a produção).
- fix(site): `diag.php` sempre responde JSON (mesmo se `config.php` falhar ao carregar) — nunca
  vaza segredo, nem em sucesso nem em erro; testado localmente (sem env vars, e com env vars
  simuladas contra host inexistente).
- chore(ci): `deploy.yml` simplificado — removidas as duas etapas de contorno de credencial do
  ciclo v6 (injeção em `config.php` via CI + upload Python FTPS paralelo de
  `db_credentials.php`). Deploy volta a ser só `FTP-Deploy-Action` padrão; `continue-on-error`
  removido do passo de FTP (falha real aparece no Actions agora).
- docs(governanca): FASE-00-PLANO.md, STATE.md e HANDOFF.md atualizados com a posição atual e
  as env vars que o Diego precisa cadastrar no painel Hostinger antes do próximo push.

## 2026-07-14
- chore(governanca): harness.ps1 próprio (caminhos + louvor.db + trava de deploy) —
  "doctor" local além do gate.
- docs(governanca): FORMA_DE_TRABALHAR.md e DEFINICAO_DE_PRONTO.md locais (regra de ouro:
  push em main = deploy em produção).
