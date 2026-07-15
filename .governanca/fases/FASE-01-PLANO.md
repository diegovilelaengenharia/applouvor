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

**Pendência explícita:** Diego precisa checar no hPanel (Avançado → GIT) o status/log do
último deploy do webhook — se falhou, travou, ou está numa fila. FASE 01 fica **executada e
commitada, mas NÃO fechada** até `/router.php` responder (não mais 404) e `/` mostrar a view
nova em produção.

⚠️ Checagens fixas deste repo (lições incorporadas):
- [x] Diff não mistura `site/**` e `gestao/**` no mesmo commit.
- [ ] OK explícito do Diego antes do push desta fase (push = deploy produção).
- [x] Endpoint de diagnóstico (`diag.php`) continua com `require` de config DENTRO do try/catch.

## Registro (ao fechar)
- [ ] STATE.md (posição) · ROADMAP.md (fase ✅) · CHANGELOG.md (topo)
- [ ] RETRO em `LICOES.md`
- [ ] Pendências explícitas
