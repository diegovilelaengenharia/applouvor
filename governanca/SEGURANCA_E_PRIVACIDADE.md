# Segurança e Privacidade — ecossistema ⛪ Igreja (applouvor)

> Este repo tem dois riscos, e nenhum deles é PII de cidadão: **dados de membros da igreja**
> e **deploy direto em produção**.

## O que é sensível aqui

**Dados de membros** (nome, contato, disponibilidade, escala, indisponibilidades) vivem no
`louvor.db` — SSOT do ministério, em `C:\vilela\Vilela Igreja\0. Máquina\louvor.db`.
**Fora do git**, sempre. Não é PII de cidadão (isso é do `vilela-prefeitura`), mas é dado de
pessoas reais da igreja: trate com o mesmo cuidado pastoral que o Diego trata a equipe.

**Credenciais de produção** (`site/src/config/db_credentials.php`) são geradas no deploy
pelos GitHub Secrets — **nunca** commitadas.

## Os dois lados do repo (a regra que mais dói se esquecida)

| Lado | O que é | Push em `main` |
|---|---|---|
| `site/` | PWA público (PHP) | **PUBLICA EM PRODUÇÃO** — `louvor.vilela.eng.br`, via Actions → FTPS. **Não existe staging.** |
| `gestao/` | App do líder (FastAPI, 8020) | Não deploya |

**Nunca misture os dois num commit.** O gate (`governanca/pronto.ps1 -Hook`) **bloqueia** o
commit misto: ou você publica sem querer, ou segura uma publicação que devia sair. O filtro
`paths: ['site/**']` do `deploy.yml` é o que garante isso — foi provado por teste negativo na
F1 da cirurgia (commit só em `gestao/` não dispara Actions).

## As regras duras

1. **`louvor.db` nunca entra no git.** Nem cópia, nem backup, nem fixture com nome real de
   membro. O gate bloqueia `*.db` staged.
2. **Se o commit toca `site/`, o push publica.** O gate avisa em amarelo; a skill
   `vilela-publicar` confirma com o Diego antes. Publicação é decisão dele, nunca da IA.
3. **Este ecossistema não conhece a Prefeitura.** Dado de cidadão não tem nada que fazer
   aqui, em nenhuma hipótese.
4. **EBD e Leituras não moram aqui** — ficaram na Central (Teologia). Não traga.

## O gate

```powershell
.\governanca\pronto.ps1          # rápido: git + lados não misturados
.\governanca\pronto.ps1 -Full    # completo: + a suíte do gestao/
.\governanca\instalar-hooks.ps1  # planta o gate no .git/hooks
```

Até a F8 os hooks daqui chamavam o `pronto.ps1` **da engenharia** — que testava as suítes da
engenharia e não sabia nada do louvor (e ainda barrava commit aqui quando a engenharia estava
vermelha). Agora cada casa tem a sua.
