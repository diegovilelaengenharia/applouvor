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

**✅ RESOLVIDO (2026-07-15 23:19 UTC) — decisão tomada e commitada, sem tocar DNS/subdomínio:**

Investigando mais fundo (não era a integração hPanel → Avançado → GIT, essa está desconectada/
vazia): o mecanismo real é um **webhook do GitHub** (`gh api repos/.../hooks` confirmou),
apontando para `webhooks.hostinger.com/deploy/...`, **criado em 2026-02-11** — antes até da
v1 documentada no histórico. Ele dispara em **todo push, sem filtro de path** (confirmado:
um commit só de docs também disparou). É esse webhook que sempre publicou de verdade; o
GitHub Actions/FTP nunca chegou a servir nada, o tempo todo desde junho.

**Decisão (julgamento técnico, autorizado por "faz o que achar melhor" do Diego):**
manter o webhook como mecanismo **oficial** — já funciona, zero credencial no CI, é mais
simples. Corrigido com 3 arquivos, tudo versionado (commit `5ddcc01`):
1. **`.htaccess` movido da raiz do repo** (não mais só no servidor via File Manager) —
   bloqueia pastas internas + reescreve pra `site/`. Como faz parte do git, o próprio webhook
   o publica em TODO push, então a proteção nunca mais depende de um hotfix manual esquecível.
2. **`deploy.yml`: `on: push` removido**, só `workflow_dispatch` — para de competir com o
   webhook; vira fallback manual documentado.
3. **`CLAUDE.md` + agente `ministro` + skill `vilela-louvor`: regra de ouro corrigida** — "push
   em `main` = deploy" vale pra QUALQUER push agora (não só o que toca `site/**`), porque o
   webhook não filtra por path. A crença antiga só valia pro GitHub Actions.

Verificado por execução após o push: `diag.php` → `{"db":"OK",...}`;
`louvor.vilela.eng.br/.governanca/HANDOFF.md` → 403 (protegido, permanente). Webhook delivery
confirmado 200 (`gh api .../hooks/.../deliveries`).

**Não mexido (fora de escopo, decisão consciente):** o subdomínio continua apontando pra
`applouvor/` (o checkout inteiro) em vez de `applouvor/site/` diretamente — reapontar exigiria
deletar/recriar o subdomínio no hPanel (DNS/SSL), risco desproporcional ao ganho (o `.htaccess`
da raiz já resolve o problema real, que era a exposição). Se o Diego quiser essa limpeza
estética no futuro, é uma tarefa separada, de baixo risco calculado, não uma pendência de
segurança.

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
| `https://louvor.vilela.eng.br/.governanca/HANDOFF.md` | ✅ 403, permanente (versionado no git) |
| Webhook Hostinger (mecanismo oficial) | ✅ delivery 200 confirmado via `gh api` |
| Push de controle pós-correção definitiva | ✅ commit `5ddcc01`, diag continuou verde |

⚠️ Checagens fixas deste repo:
- [x] Diff não misturou `site/**` e `gestao/**` no mesmo commit.
- [x] Push feito com autorização explícita do Diego ("quero que você faça tudo por mim" /
      "faz o que achar melhor").
- [x] Nenhuma ação de DNS/subdomínio tomada sozinha (avaliado e conscientemente adiado).

## Registro (ao fechar)
- [x] STATE/CHANGELOG/CLAUDE.md/agente/skill atualizados; RETRO em `LICOES.md` completa.
- [x] **FASE 00 FECHADA** — todos os 4 critérios de sucesso cumpridos, achado crítico resolvido
      de forma permanente (não é mais hotfix manual). Pronto para DISCUTIR a FASE 01.

## Status final (2026-07-15/16)
FASE 00 fechada com sucesso: infra provada verde em produção, e um problema estrutural de
deploy que vinha **desde fevereiro de 2026** (bem antes do ciclo v6) foi descoberto e corrigido
de forma permanente e versionada. Essa é provavelmente a explicação real de boa parte da dor do
ciclo v6: o time lutou 10 commits pra fazer o GitHub Actions/FTP funcionar, sem saber que um
webhook antigo, esquecido, já publicava (a coisa errada) o tempo todo por baixo.
