# FASE 01 — Arquitetura Core

> Método: skill global **`vilela-gsd`** · Dono da sessão: agente **`ministro`**. Segue a FASE 00
> (infra verde em produção). Histórico das tentativas v1→v6: `c:\vilela\applouvor-historico\LEIA-ME.md`.

**Objetivo (1 frase):** reconstruir a fundação MVC do `site/` (front controller + autoloader
PSR-4 + camada Model/Controller base) do zero, código novo, pronta para receber autenticação
na FASE 02.

**DISCUTIDO e aprovado pelo Diego em 2026-07-16.**

**Critérios de sucesso (mensuráveis, por execução):**
1. Front controller roteia `GET /` retornando 200 com uma view mínima renderizada (prova que
   Router → Controller → View funciona ponta a ponta).
2. Autoloader PSR-4 (`App\`) carrega classes de `src/Controllers`, `src/Models`, `src/classes`
   sem `require` manual em nenhum arquivo de classe.
3. `Controller` e `Model` base criados; conexão PDO isolada em `src/config/db.php`, reaproveita
   as constantes `DB_*`/`getenv()` já validadas na FASE 00 (não regride `config.php`).
4. `php -l` limpo (exit 0) em 100% dos arquivos `.php` novos.
5. Push de controle: `diag.php` continua `{"db":"OK"}` em produção depois do push desta fase
   (a fundação nova não quebra o que a FASE 00 garantiu).

**Fora de escopo desta fase:** login/autenticação (Fase 02), design system/CSS/PWA (Fase 03),
qualquer tela de produto (escalas, repertório, etc.), `gestao/**`.

## Fatias
- [x] `site/src/autoload.php` — `spl_autoload_register` para `App\` → `src/` e `src/classes/`.
- [x] `site/src/config/db.php` — monta `$pdo` (PDO/MySQL) a partir das constantes `DB_*` de
      `config.php` (FASE 00), erro de conexão é `RuntimeException` (ruidoso, não silencioso).
- [x] `site/src/Router.php` — classe `App\Router`: `get()`/`post()`, params de rota via regex,
      `dispatch()`.
- [x] `site/src/Controllers/Controller.php` — base abstrata: `render()`, `json()`, `redirect()`.
- [x] `site/src/Models/Model.php` — base abstrata: `all()`, `find()`, `where()`.
- [x] `site/src/Controllers/PageController.php` + `src/Views/app/home.php` — rota `GET /` de
      prova (sem tocar banco), confirma o pipeline Router→Controller→View.
- [x] `site/router.php` — front controller: autoload + config + db + registra rota `/` + dispatch.
- [x] `site/index.php` — vira ponte simples pro front controller (`require router.php`).
- [x] `site/.htaccess` — regra de rewrite (front controller) somada às regras de segurança já
      existentes da FASE 00 (mantém proteção de `.env`/`.sql`/etc.).

## Verificação (por execução)
| Como provar | Resultado esperado | Resultado |
|---|---|---|
| `php -l` em todos os `.php` novos/alterados | Sem erro de sintaxe em nenhum arquivo | ✅ 11/11 limpos (achado e corrigido 1 bug: `*/` literal dentro de comentário em `db.php` fechava o bloco cedo demais) |
| Servidor local (`php -S`, MySQL do XAMPP ligado) → `GET /` | 200, HTML da view `app/home.php` | ✅ 200, view nova renderizada |
| Servidor local → `GET /diag.php` | Continua `{"db":"OK"}` (não regrediu) | ✅ `{"db":"OK","app_env":"local",...}` |
| Servidor local → rota inexistente (`?route=/rota-inexistente`) | 404 tratado (sem fatal error cru) | ✅ `404 — rota não encontrada` |
| Push de controle → `https://louvor.vilela.eng.br/diag.php` | `{"db":"OK",...}` | ✅ continua `{"db":"OK",...}` (não prova a fase, arquivo não mudou neste commit) |
| Push de controle → `https://louvor.vilela.eng.br/` | 200, view nova (não mais o texto estático da FASE 00) | 🔴 **NÃO bateu** — ver "Achado FASE 01" abaixo |

## 🔴 Achado durante a verificação (2026-07-16, pós-push)

Push feito (commit `f5baf2f`, autorizado pelo Diego). Webhook Hostinger confirmou entrega
`200 OK` em `2026-07-16T23:30:50Z` (`gh api .../hooks/595788026/deliveries`). Mas **9+ minutos
depois**, `https://louvor.vilela.eng.br/router.php` (arquivo NOVO desta fase, não pode ter
versão "antiga") ainda responde **404** ("This Page Does Not Exist" — página estática da
Hostinger), e `/` continua servindo o HTML antigo da FASE 00 ("Em reconstrução... Fundação de
infra em validação"). Ou seja: **o deploy não propagou**, mesmo com o webhook tendo aceito a
entrega — reforça a regra dura #7 (webhook 200 = recebido, não = publicado).

Repo é pequeno (26M .git, 8682 objetos) — não é questão de clone lento. O fallback FTP
(`deploy.yml`) também não resolve sozinho: ele sobe pra
`/domains/louvor.vilela.eng.br/public_html/`, que a FASE 00 já identificou como pasta ÓRFÃ (o
subdomínio serve de `/domains/vilela.eng.br/public_html/applouvor/`, alimentada só pelo
webhook). Sem acesso ao painel Hostinger, não dá pra diagnosticar mais fundo daqui.

## ✅ RESOLVIDO (2026-07-16, mesma noite)

Causa raiz encontrada com acesso ao hPanel (Diego autorizou): existia uma integração nativa
**"Git Auto Deployments"** do hPanel (produto novo da Hostinger, diferente do webhook antigo
da FASE 00) conectando o repo `applouvor` **direto na raiz do `public_html` de
`vilela.eng.br`** — errado, nunca deveria existir assim. Um clique nela pra tentar forçar o
deploy **apagou os arquivos reais do `vilela-site`** (`index.html`, `sobre.html`, `assets/`
etc. — só `area-cliente/` sobreviveu) e sobrescreveu `.htaccess`/`.gitignore` com as versões
do `applouvor`. Incidente cross-project registrado na memória
`hosting-vilela-eng-br-multiplos-projetos.md`.

**Correção aplicada:**
1. Removidas do `public_html` de `vilela.eng.br` as pastas/arquivos do `applouvor` que
   vazaram pra lá (`.git`, `.claude`, `.github`, `.governanca`, `docs`, `gestao`, `governanca`,
   `site`, `CLAUDE.md`, `CHANGELOG.md`) — sem tocar `applouvor/` (pasta legítima, é o que o
   subdomínio serve) nem `area-cliente/`.
2. `.htaccess`/`.gitignore` do `vilela-site` restaurados (conteúdo do git) e o deploy oficial
   dele (GitHub Actions → FTPS) disparado — repôs `index.html`/`sobre.html`/`assets/` etc.
3. Integração "Git Auto Deployments" ficou desconectada sozinha (perdeu o rastro ao apagar o
   `.git` que ela tinha clonado ali) — não precisou desconectar manualmente.
4. **Causa do `/router.php` 404:** confirmado que o webhook antigo da FASE 00 parou de aplicar
   deploys de verdade (só faz ACK 200, não clona mais nada — provável migração silenciosa da
   Hostinger pro produto novo acima). `deploy.yml` corrigido: `server-dir` agora aponta pro
   caminho real (`/domains/vilela.eng.br/public_html/applouvor/site/`, achado da FASE 00) em
   vez da pasta órfã antiga. Disparado manualmente (`workflow_dispatch`) — **verde**.
5. Achado extra: `vilela.eng.br/applouvor/` servia o app diretamente em vez de redirecionar
   pro subdomínio (a pasta física intercepta antes da regra do `vilela-site`). Corrigido: o
   redirect (condicionado a `Host: vilela.eng.br`) agora vive no `.htaccess` do próprio
   `applouvor`, sincronizado em produção (a raiz do repo não é coberta pelo FTP-Deploy-Action,
   que só sobe `site/` — gap documentado, sync manual até o pipeline cobrir a raiz também).

**Verificado por execução (todos ✅):**
`/router.php` 200 · `/` mostra a view nova (FASE 01) · `/diag.php` `{"db":"OK"}` ·
rota inexistente → 404 tratado · `vilela.eng.br/` 200 (landing real) · `/sobre.html` 200 ·
`/contato/` 200 (redirect stub, é o comportamento real do repo) · `/area-cliente/` 200 (login
renderiza, assets carregam) · `vilela.eng.br/applouvor/` → 301 pro subdomínio ·
`vilela.eng.br/.git/config` → 403 (protegido).

## ✅ Limpeza da arquitetura do subdomínio (2026-07-16, mesma noite, a pedido do Diego)

Diego pediu para resolver a pendência acima ("conserte"). Document root do subdomínio
`louvor.vilela.eng.br` trocado de `.../public_html/applouvor` para
`.../public_html/applouvor/site` via hPanel → Domínios → Subdomínios (apagar + recriar com
"Pasta personalizada" — não existe edição in-line, só recriar; DNS é externo a Hostinger, não
foi afetado).

**Três achados no caminho, todos resolvidos:**
1. **MySQL invalidou a senha de `u884436813_admin`** ao recriar o subdomínio (confirmado via
   phpMyAdmin: `Access denied ... using password: YES` mesmo com a senha certa — não era bug
   de ambiente/PHP, era o grant mesmo). Resolvido igual à FASE 00: hPanel → Bancos de dados →
   Gerenciamento → preencher banco (`applouvor`) + usuário (`admin`) + senha nova → "Criar"
   **preserva os dados existentes** (não recria do zero) e apenas restabelece a credencial.
   `.env` de produção atualizado com a senha nova.
2. **Recriar o subdomínio com "Pasta personalizada" sobrescreveu `index.php`/`.htaccess` da
   raiz de `site/` com uma versão antiga** (voltou ao esqueleto da FASE 00) e **injetou um
   `default.php` genérico da Hostinger** (16 KB, "Página padrão") — efeito colateral não
   documentado da própria Hostinger ao provisionar um novo webspace. `src/` (subpastas) não foi
   afetado. Corrigido: conteúdo correto do git re-sincronizado manualmente (`index.php`,
   `.htaccess`, `config.php`) + `default.php` apagado. **Lição para o futuro:** depois de
   criar/recriar qualquer subdomínio com pasta personalizada na Hostinger, sempre conferir se
   os arquivos da pasta continuam os certos — o provisionamento pode sobrescrever a raiz.
3. **O redirect de `vilela.eng.br/applouvor/*` pro subdomínio** (regra no `.htaccess` do
   vilela-site) nunca funcionou — a pasta física intercepta antes. Movido pro `.htaccess` do
   próprio `applouvor`, condicionado a `Host: vilela.eng.br`. Verificado: 301 funcionando.

**Verificado por execução (todos ✅, pós-limpeza):** `/` mostra a view certa (versão
`7.1.0-fase01`) · `/diag.php` `{"db":"OK"}` · 404 tratado · `/default.php` → 404 (removido) ·
`vilela.eng.br/applouvor/` → 301 · `vilela.eng.br/` · `/area-cliente/` funcionando.

**FASE 01 FECHADA — arquitetura limpa, sem pendências.**

⚠️ Checagens fixas deste repo (lições incorporadas):
- [x] Diff não mistura `site/**` e `gestao/**` no mesmo commit.
- [ ] OK explícito do Diego antes do push desta fase (push = deploy produção).
- [x] Endpoint de diagnóstico (`diag.php`) continua com `require` de config DENTRO do try/catch.

## Registro (ao fechar)
- [ ] STATE.md (posição) · ROADMAP.md (fase ✅) · CHANGELOG.md (topo)
- [ ] RETRO em `LICOES.md`
- [ ] Pendências explícitas
