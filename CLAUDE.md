# CLAUDE.md — `applouvor` (ecossistema ⛪ IGREJA)

Orientação de raiz. O repo tem **dois lados** e confundi-los custa um deploy acidental em produção.

| Lado | O que é | Deploy? |
|---|---|---|
| `site/` | **PWA público** (PHP, MVC front-controller) — a escala vista pela equipe, em `louvor.vilela.eng.br` | **SIM** — push que toca `site/**` vai para produção |
| `gestao/` | **App do líder** (FastAPI, **porta 8020**) — escala, jejum, repertório, imagem semanal. É onde o Diego trabalha | **NÃO** |

> ⚠️ **Nunca misture os dois num commit.** O `deploy.yml` filtra por `paths: ['site/**', ...]` — foi
> assim, no mesmo commit, que a F1 da cirurgia blindou o deploy (teste negativo provado: commit só em
> `gestao/` **não** dispara Actions).

## `site/` — o PWA público
- `index.php` + `router.php` / `src/Router.php` — front controller (toda requisição entra aqui).
- `src/Controllers/` (26) — ex.: `AgendaController`, `AutoScheduleController` (escala automática),
  `AvisoController`, `DashboardController`. Estendem `Controller`.
- `src/Models/` (13) — acesso a dados; `src/Views/` (55) — telas; `src/helpers/`, `src/classes/`.
- `src/config/db_credentials.php` — credenciais (geradas no deploy pelos Secrets; **fora do git**).
- Deploy: push em `main` → GitHub Actions → **FTPS Hostinger**. O `deploy.yml` exclui `CLAUDE.md`,
  `README.md`, `tests/`, `.claude/` — dev docs não vão para produção.

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
