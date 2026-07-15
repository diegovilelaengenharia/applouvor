# CLAUDE.md — `applouvor` (ecossistema ⛪ IGREJA)

Orientação de raiz. O repo tem **dois lados** e confundi-los custa um deploy acidental em produção.

> ## 🔴 REGRA DE OURO CORRIGIDA (FASE 00, 2026-07-16) — leia antes de qualquer push
> **TODO push em `main` publica em produção — não só o que toca `site/**`.** Descoberto na
> FASE 00: existe um **webhook nativo da Hostinger** (GitHub → `webhooks.hostinger.com`,
> configurado em 2026-02-11, **anterior** ao `deploy.yml`/GitHub Actions) que clona o **repo
> inteiro** a cada push — sem filtro de path — para a pasta que o domínio
> `louvor.vilela.eng.br` **realmente** serve. A crença antiga ("commit só em `gestao/` não
> dispara deploy") valia só para o GitHub Actions; o webhook sempre rodou por baixo, o tempo
> todo, sem que ninguém soubesse. Proteção contra exposição de `.governanca/`/`gestao/`/etc.
> está no `.htaccess` da RAIZ do repo (não em `site/.htaccess`) — **nunca remover sem
> equivalente no lugar**. Detalhes: `.governanca/fases/FASE-00-PLANO.md` §"Achado crítico".

| Lado | O que é | Deploy? |
|---|---|---|
| `site/` | **PWA público** (PHP, MVC front-controller) — a escala vista pela equipe, em `louvor.vilela.eng.br` | Servido daqui (via `.htaccess` da raiz) |
| `gestao/` | **App do líder** (FastAPI, **porta 8020**) — escala, jejum, repertório, imagem semanal. É onde o Diego trabalha | Não é servido na web, mas **vai junto** no clone do webhook a cada push (mitigado pelo `.htaccess` da raiz, que bloqueia acesso direto) |

> ⚠️ **Ainda assim, não misture os dois num commit** — mantém o histórico legível e o
> `deploy.yml` (agora só `workflow_dispatch`, fallback manual via FTP) continua filtrando por
> `site/**` para esse caso de uso manual.

## `site/` — o PWA público
- `index.php` + `router.php` / `src/Router.php` — front controller (toda requisição entra aqui).
- `src/Controllers/` (26) — ex.: `AgendaController`, `AutoScheduleController` (escala automática),
  `AvisoController`, `DashboardController`. Estendem `Controller`.
- `src/Models/` (13) — acesso a dados; `src/Views/` (55) — telas; `src/helpers/`, `src/classes/`.
- `src/.env` — credenciais de produção (fora do git, colocadas manualmente no servidor via File
  Manager — este plano de hospedagem não tem variável de ambiente no painel).
- Deploy: push em `main` → webhook nativo Hostinger (automático, oficial) → repo inteiro clonado,
  `.htaccess` da raiz roteia para `site/`. `deploy.yml`/GitHub Actions é só fallback manual.

## `gestao/` — o app do líder (onde o trabalho acontece)
```powershell
cd gestao ; .\iniciar.ps1     # http://127.0.0.1:8020
cd gestao ; py -m pytest tests
```
- **SSOT:** `C:\vilela\Vilela Igreja\0. Máquina\louvor.db` (resolvido por `gestao\caminhos.py::louvor_db_path()`,
  env `LOUVOR_DB` tem precedência). Equipe, escala de cultos, jejum, repertório, setlist, histórico.
- `gestao\ferramentas\`: `louvor.py` (próximos cultos + lacunas), `louvor_db.py`,
  `gerar_imagem_louvor.py` (PNG semanal do WhatsApp), `sincronizar_igreja_agenda.py`,
  `resumo_diario.py` (→ `Vilela Sistema\_central\resumos\igreja.json`).
- A **Central Vilela não gerencia mais o louvor** (F5 fechou o split-brain) — ela só exibe o resumo.

**Não moram aqui:** EBD e Leituras ficaram na Central como módulo *Teologia* (ferramentas em
`Vilela Sistema\ferramentas\`, dados em `Vilela Igreja\00. _Gestão\ebd.json`). Nunca leram o `louvor.db`.

## Skills locais (`.claude/skills/`)
`vilela-louvor` (ministério: escala, jejum, repertório, imagem semanal). As transversais são globais.

## 🪙 Economia de tokens (grátis)
Antes de abrir arquivos: leia **`MAPA.md`** (raiz — índice de símbolos PHP) e use **vilela-grafo**
(`graphify explain "<Controller>"`, `query "<símbolo>"`). Grafo/MAPA para NAVEGAR; **Read do trecho
real antes de EDITAR**. (`MAPA.md`/`graphify-out/` são caches locais regeneráveis.)

## Verificação
Por execução: escala/imagem geradas → **abra e confira** (nome certo na posição certa). Antes de
pushar, confirme que o diff **não toca `site/`** se você não quer deployar. Publicação passa pela
skill `vilela-publicar` (ela avisa quando o push dispara produção).
