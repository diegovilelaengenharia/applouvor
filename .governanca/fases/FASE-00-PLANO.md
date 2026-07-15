# FASE 00 — Fundação de infra (a lição do ciclo v6: infra ANTES de produto)

> Método: skill global **`vilela-gsd`** · Dono da sessão: agente **`ministro`** (abrir a sessão
> em `c:\vilela\applouvor`). Histórico completo das tentativas v1→v6:
> `c:\vilela\applouvor-historico\LEIA-ME.md`.

**Objetivo (1 frase):** provar conexão de banco VERDE em produção (`louvor.vilela.eng.br`)
por um caminho de credenciais estável — antes de escrever qualquer tela.

**Por que esta fase existe:** o ciclo v6 (jun/2026) chegou a 99% do MVP e morreu em 10 commits
`fix:/temp:/diag:` lutando para levar credenciais de DB (gitignored) à Hostinger via CI. A
reconstrução não repete isso: primeiro a fundação, depois o produto.

**Critérios de sucesso (mensuráveis, por execução):**
1. ✅ **DECIDIDO (DISCUTIR, 2026-07-15):** escopo = **só `site/` reinicia do zero**. `gestao/`
   (FastAPI 8020, app do líder) fica como está — funciona, é pós-pausa, não é o que quebrou.
2. ✅ **DECIDIDO (DISCUTIR, 2026-07-15):** arquitetura = **MySQL próprio da Hostinger continua**,
   mas credenciais viram **variáveis de ambiente no painel Hostinger**, lidas com `getenv()` —
   nunca mais arquivo de credencial subido por FTP/CI. Menor mudança possível que mata a causa
   raiz dos 10 commits `fix:/temp:/diag:` de junho.
3. ✅ **VERDE (2026-07-15 23:11 UTC):** `https://louvor.vilela.eng.br/diag.php` →
   `{"db":"OK","checked_at":"2026-07-15T23:11:30+00:00","app_env":"production","host":"localhost"}`.
   Raiz (`/`) também no ar ("App Louvor — PIB Oliveira").
4. ⚠️ **Parcial** — ver "Achado crítico" abaixo. O caminho até ficar verde não foi só credencial;
   havia um bug de infra mais profundo. Precisa de decisão do Diego antes de considerar
   "estável" (não é sorte de um deploy, mas também não é o caminho definitivo ainda).

**Fora de escopo desta fase:** telas, features, design, cifras, escalas — NADA de produto.

## 🔴 Achado crítico durante o smoke test (2026-07-15 noite)

O primeiro deploy (GitHub Actions → FTP → `/domains/louvor.vilela.eng.br/public_html/`) foi
verde no Actions, mas `https://louvor.vilela.eng.br/` respondia **403** e `/diag.php` **404**
("This Page Does Not Exist", página estática da Hostinger). Investigando pelo painel:

- O **subdomínio `louvor.vilela.eng.br` está configurado (hPanel → Subdomínios) para servir de
  `/domains/vilela.eng.br/public_html/applouvor` — NÃO da pasta que o FTP-Deploy-Action sobe.**
- Essa pasta `applouvor/` é alimentada por um **segundo mecanismo de deploy**: a integração
  **GIT nativa da Hostinger** (Avançado → GIT), que clona o **repositório inteiro** (`.git/`,
  `.governanca/`, `.claude/`, `gestao/`, `docs/`, `governanca/`, `vendor/` — tudo) a cada push.
  Isso é **diferente e paralelo** ao deploy.yml do GitHub Actions, e ninguém desligou.
- Consequência real: por alguns minutos, `.governanca/HANDOFF.md` (documentação interna do
  projeto) ficou **publicamente acessível** em `louvor.vilela.eng.br/.governanca/HANDOFF.md`
  (confirmado, conteúdo completo servido). `.git/` já vinha bloqueado (403) por alguma regra
  padrão da Hostinger, mas `.governanca/`, `gestao/`, `docs/` etc. não tinham proteção nenhuma.

**Mitigação aplicada (hotfix, só arquivo, reversível, feita direto no servidor via File
Manager — NÃO está no git):** `domains/vilela.eng.br/public_html/applouvor/.htaccess` —
bloqueia `.git/.governanca/.claude/.github/gestao/governanca/docs/vendor` (403) e reescreve
todo o resto para `site/`. Testado: `.governanca/HANDOFF.md` → 403; `/` e `/diag.php` → OK.
O `.env` de produção foi criado em `applouvor/site/.env` (o caminho que este hotfix realmente
serve), não em `domains/louvor.vilela.eng.br/public_html/.env` (que ficou órfão, sem efeito).

**⚠️ Isto é um remendo, não a correção definitiva — decisão do Diego, não tomada sozinha:**
1. Esse `.htaccess` de emergência pode ser apagado no próximo `git pull`/reset do deploy Git
   nativo da Hostinger (não temos certeza do comportamento dele). Não é confiável a longo prazo.
2. **Duas opções para a correção real** (ambas exigem acesso ao painel, ambas fora do escopo de
   "agente sozinho"):
   - **(a) Desligar o deploy Git nativo da Hostinger** (Avançado → GIT) e manter só o
     GitHub Actions → FTP para `/domains/louvor.vilela.eng.br/public_html/` — aí sim editar o
     subdomínio para apontar pra essa pasta (isso EXIGE deletar+recriar o subdomínio no hPanel,
     que a Hostinger avisa que apaga registros DNS/e-mail associados — **não fiz isso sozinho**,
     quase cliquei em "Deletar Subdomínio" por engano e cancelei a tempo).
   - **(b) Manter o deploy Git nativo** como o mecanismo real, e o GitHub Actions vira redundante
     (ou é desligado). Mais simples de não mexer em DNS, mas mistura infra de deploy (2 fontes
     de verdade é exatamente o tipo de coisa que já causou dor no ciclo v6).
3. Enquanto o Diego não decidir, o hotfix `.htaccess` fica valendo, mas **confira que ainda está
   lá** antes de cada deploy futuro (`applouvor/.htaccess` via File Manager).

## Fatias
- [x] DISCUTIR com Diego: escopo do reset + escolha da arquitetura de dados (criterio 1 e 2).
- [x] `site/` resetado para esqueleto mínimo NOVO (código escrito do zero, não copiado do
      legado): `index.php`, `src/config/config.php` (getenv + falha ruidosa se faltar),
      `diag.php` (JSON, nunca expõe segredo), `.htaccess` (só segurança), `.env.example`.
      53 telas MVC do ciclo v6 removidas do working tree (preservadas em `git log` — HEAD
      pré-reset `67775ef` — e em `applouvor-historico/`).
- [x] Simplificar `deploy.yml`: removidas as duas etapas de contorno do ciclo v6 (injeção de
      credenciais em `config.php` via CI + upload Python FTPS paralelo de
      `db_credentials.php`/`config.php`/`diag.php`). Deploy volta a ser só
      `FTP-Deploy-Action` padrão subindo `./site/`; `continue-on-error` removido (falha real
      agora aparece no Actions, não é mascarada).
- [x] `.htaccess` corrigido para sintaxe Apache 2.4 (`Require`) com fallback 2.2 — commit
      `575629f` (era hipótese inicial do 403; não era a causa real, mas é melhoria válida).
- [x] Env vars resolvidas — **não há variável de ambiente no painel neste plano de hospedagem**
      (só "Versão/Extensões/Opções do PHP", sem aba de env vars). Credenciais reais obtidas
      resetando a senha do usuário MySQL `u884436813_admin` (banco `u884436813_applouvor`, já
      existia desde 16/01/2026) e colocadas em `applouvor/site/.env` no servidor (fora do git,
      não sobe por FTP, sobrevive a redeploys do FTP-Deploy-Action).
- [x] Descoberto e mitigado: subdomínio apontava para pasta errada + exposição pública de
      `.governanca/` (ver "Achado crítico" acima). Hotfix aplicado, verde confirmado.
- [x] Push + `diag.php` NO AR: **verde**, ver critério 3.
- [~] Segundo push de controle: dois pushes reais já aconteceram (reset + fix .htaccess), ambos
      via Actions verde. Falta um push trivial **depois** do estado atual (com o achado crítico
      já resolvido) pra fechar o critério 4 com 100% de confiança.

## Verificação (por execução)
| Como provar | Resultado |
|---|---|
| `https://louvor.vilela.eng.br/diag.php` após deploy | ✅ `{"db":"OK",...}` |
| `https://louvor.vilela.eng.br/` | ✅ "App Louvor — PIB Oliveira" |
| `https://louvor.vilela.eng.br/.governanca/HANDOFF.md` | ✅ 403 (bloqueado, era exposto antes) |
| GitHub Actions do push | ✅ verde, sem etapas de contorno |
| segundo push trivial em `site/` pós-hotfix | ⬜ pendente |

⚠️ Checagens fixas deste repo:
- [x] Diff não misturou `site/**` e `gestao/**` no mesmo commit.
- [x] Push feito com autorização explícita do Diego ("quero que você faça tudo por mim").
- [ ] `vilela-backup` — não rodado nesta rodada (mudança foi só em `site/` + infra Hostinger,
      não em dado sensível do repo).

## Registro (ao fechar)
- [x] STATE/CHANGELOG atualizados; RETRO em `LICOES.md` (inclui o achado do document root).
- [ ] Fase fecha de vez quando o Diego decidir (a) ou (b) do "Achado crítico" — até lá, tratar
      como "verde mas com dívida técnica de infra conhecida e documentada", não como 100% fechada.

## Status desta rodada (2026-07-15/16)
FASE 00 chegou ao verde em produção (`diag.php` OK), mas o caminho revelou um problema de infra
que **não estava mapeado**: dois mecanismos de deploy concorrentes (GitHub Actions FTP e Git
nativo da Hostinger) apontando para pastas diferentes, com a pasta realmente servida expondo
publicamente `.governanca/`, `gestao/`, `docs/` por não ter proteção. Mitigado com um hotfix de
`.htaccess` direto no servidor (fora do git, não confiável a longo prazo). **Decisão pendente do
Diego:** qual dos dois mecanismos de deploy vira o oficial (ver opções (a)/(b) acima) — essa
decisão não foi tomada sozinha porque envolve deletar/recriar o subdomínio (DNS/SSL).
