# CLAUDE.md — App Louvor (escala do ministério, PIB Oliveira)

App web (PHP, MVC front-controller) da escala de louvor — deploy em `louvor.vilela.eng.br`.

## O que é / estrutura
- `index.php` + `router.php` / `src/Router.php` — front controller (toda requisição entra aqui).
- `src/Controllers/` (26) — ex.: `AgendaController`, `AutoScheduleController` (escala automática),
  `AvisoController`, `DashboardController`. Estendem `Controller`.
- `src/Models/` (13) — acesso a dados; `src/Views/` (55) — telas; `src/helpers/`, `src/classes/`.
- `src/config/db_credentials.php` — credenciais (geradas no deploy pelos Secrets; **fora do git**).

## Deploy (produção)
Push em `main` → GitHub Actions → **FTPS Hostinger** (`louvor.vilela.eng.br`). O `deploy.yml`
exclui `CLAUDE.md`, `README.md`, `tests/`, `.claude/` etc. — dev docs **não** vão para produção.
Publicação passa por `vilela-publicar` (avisa que o push dispara deploy).

## 🪙 Economia de tokens (grátis)
Antes de abrir arquivos: leia **`MAPA.md`** (raiz — índice de símbolos PHP) e use **vilela-grafo**
(`graphify explain "<Controller>"`, `query "<símbolo>"`). Grafo/MAPA para NAVEGAR; **Read do trecho
real antes de EDITAR**. Detalhe: skill vilela-grafo. (`MAPA.md`/`graphify-out/` são caches locais.)
