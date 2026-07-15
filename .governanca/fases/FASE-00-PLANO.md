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
3. `diag.php` em produção respondendo **verde** (conexão DB ok) após um deploy normal via
   GitHub Actions — sem passo manual, sem upload paralelo de credencial.
4. Rollback provado: um segundo push qualquer não quebra o verde (o caminho é estável, não
   sorte de um deploy).

**Fora de escopo desta fase:** telas, features, design, cifras, escalas — NADA de produto.

## Fatias (rascunho — refinar no PLANEJAR da sessão do ministro)
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
- [ ] Configurar env vars no painel Hostinger — **DEPENDE DO DIEGO** (agente não tem acesso ao
      painel). Variáveis: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (ver HANDOFF.md para a
      lista exata e onde cadastrar).
- [ ] Push + conferir `diag.php` NO AR (`https://louvor.vilela.eng.br/diag.php`) —
      **DEPENDE DO DIEGO**: autorização explícita do push (regra dura do método).
- [ ] Segundo push de controle (criterio 4) — depende dos dois itens acima primeiro.

## Verificação (por execução)
| Como provar | Resultado esperado |
|---|---|
| `https://louvor.vilela.eng.br/diag.php` após deploy | "DB: OK" (verde), sem credencial exposta |
| GitHub Actions do push | verde, sem etapas de contorno |
| segundo push trivial em `site/` | diag continua verde |

⚠️ Checagens fixas deste repo:
- [ ] Diff **não mistura** `site/**` e `gestao/**` no mesmo commit.
- [ ] Todo push desta fase toca produção → **cada push com OK explícito do Diego** (vilela-publicar).
- [ ] `vilela-backup` antes de mexer grosso no `deploy.yml`/`site/`.

## Registro (ao fechar)
- [x] STATE/CHANGELOG atualizados nesta rodada (2026-07-15/16); RETRO em `LICOES.md`.
- [ ] Só depois do verde estável (env vars cadastradas + push autorizado + `diag.php` verde no
      ar + segundo push de controle): fase fecha de fato e DISCUTIR a Fase 01 (esqueleto MVC
      mínimo + login, reaproveitando as decisões de produto travadas em 05/06 e as 53 telas
      Stitch).

## Status desta rodada (2026-07-15/16)
Trabalho de código local concluído e testado (lint PHP 5/5 OK, `diag.php` exercitado de verdade
via `php -S` local em 2 cenários — sem env vars e com env vars simuladas — ver HANDOFF.md).
**Nada foi enviado a produção.** Falta só o que só o Diego pode fazer: cadastrar as env vars no
painel Hostinger e autorizar o push. Ver `.governanca/HANDOFF.md` para o passo a passo exato.
