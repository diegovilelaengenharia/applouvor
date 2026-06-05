# PLANO — Conversão das Telas Stitch → PHP MVC

> **Pronto para executar.** Plano de conversão das 53 telas geradas no Stitch (design 100% pronto) em views/controllers/models PHP do APP Louvor Novíssimo.
> Criado em 2026-06-05 (sessão 2). Para retomar: comece pela **Wave 0** e depois siga as waves na ordem.
>
> **Fontes:** telas + IDs Stitch em [NAV-MAP.md](NAV-MAP.md) §2 · projeto Stitch `7244459960065792477` · design system `assets/18053454826462421656`.

## Status de execução (2026-06-05, sessão 3)
- ✅ **Wave 0 — Fundação:** layout compartilhado feito (`src/Views/layouts/{head,top-app-bar,bottom-nav,flash}.php`), `src/Models/Model.php`, views reorganizadas em subpastas.
- ✅ **Wave 1 — Escalas núcleo (03–06):** `ScheduleController`, models `Schedule`/`ScheduleUser`, rotas e views `escalas/{index,show,form,faltas}.php`. _Falta:_ 40 auto, 41 setlist, 42 stats(rep.), 43 ao-vivo, 44 ensaio, 45 compartilhar.
- ✅ **Wave 2 — Repertório núcleo (07–10):** `SongController`, model `Song`, rotas e views `repertorio/{index,show,form,cifra}.php`. _Falta:_ 31 sugestões, 42 stats.
- 🟡 **Wave 3 — em andamento:**
  - ✅ Perfil (11), Editar Perfil (12), Alterar Senha (34) → `User` model, `ProfileController`, views `perfil/*`, rotas `/perfil*`.
  - ✅ Configurações (13), Preferências de Notificação (35) → `UserSetting` model, `SettingsController`, views `app/configuracoes.php` + `app/notif-prefs.php`, rotas `/configuracoes*`. Tema escuro via `toggleTheme()` (client-side).
  - ✅ Indisponibilidades (17) → `Unavailability` model, `UnavailabilityController`, view `perfil/indisponibilidades.php`, rotas `/indisponibilidades*`. Atalhos ligados na tela de Perfil.
  - ✅ Ajuda/FAQ (38), Onboarding (37), Offline (39) → `PageController`, views `app/{ajuda,onboarding,offline}.php`, rotas `/ajuda` `/onboarding` `/offline`.
  - ✅ Recuperar Senha (33) → `LoginController::recover`, view `auth/recuperar-senha.php`, rota `/recuperar-senha`, link adicionado no login. ⚠️ _Versão informativa_ (orienta falar com liderança) — trocar por self-service quando houver e-mail + tabela de tokens.
  - ✅ Página **404** → `app/404.php` (o Router já a inclui automaticamente).
  - ✅ **Wave 3 COMPLETA.**
- ⬜ **Wave 4 — Comunidade/Vida Espiritual/Admin:** não iniciada. _Começar por:_ Dashboard enriquecido (02), Avisos (14/15), Notificações (16); depois Vida Espiritual (Oração 27/28/49, Devocionais 29/30/50) e Admin (Membros 20/21/36, Relatórios 22, Ministério 24/32/46/47/48).

> ⚙️ Ambiente: teste funcional exige **MySQL ligado no XAMPP**. `php -l` limpo em todos os arquivos; boot do front controller OK.

---

## 0. Como o código funciona hoje (convenções a seguir)

- **Front controller:** [router.php](../router.php) — registra rotas com `$router->get('/rota', [Controller::class, 'metodo'])` e `->post(...)`. Suporta parâmetros `{id}` (vira `(?P<id>...)`).
- **Controllers:** estendem `App\Controllers\Controller` ([src/Controllers/Controller.php](../src/Controllers/Controller.php)). Construtor recebe `PDO $pdo` (injetado pelo Router via `global $pdo`). Helpers disponíveis: `render($viewPath, $data)`, `json($data, $status)`, `redirect($url)`.
- **Views:** `require src/Views/{viewPath}.php`. Hoje cada view é um HTML completo (repete `<head>`). **Ver Wave 0** — vamos extrair um layout compartilhado.
- **Auth:** `App\AuthMiddleware::requireLogin()` no início de cada método protegido. Papel em `$_SESSION['user_role']` (`admin`/`user`), nome em `$_SESSION['user_name']`.
- **Segurança:** helpers globais já carregados — `csrf.php` (token CSRF em forms POST), `rate_limit.php`, `auth.php`.
- **Front-end:** Tailwind CDN (`?plugins=forms`) + `assets/css/stitch-theme.css` + Material Symbols + `assets/js/theme.js` (dark/light) + `assets/js/app.js` (registro PWA). Fontes: Hanken Grotesk (display) + Open Sans/Public Sans (body). Cor primária `#2E7EED`.
- **Banco:** MySQL/PDO, 22 tabelas em [database/schema.sql](../database/schema.sql) (criadas via `schema.php`).
- **404:** o Router já procura `src/Views/app/404.php` (ainda não existe — criar na Wave 0/Fechamento).

### Tabelas existentes (para os models)
`users` · `user_settings` · `songs` · `tags` · `song_tags` · `schedules` · `schedule_users` · `schedule_songs` · `schedule_roteiro` · `schedule_comments` · `user_unavailability` · `avisos` · `push_subscriptions` · `notifications` · `devotionals` · `devotional_comments` · `devotional_tags` · `devotional_reads` · `prayer_requests` · `prayer_interactions` · `reading_progress` · `song_suggestions`

---

## Wave 0 — Fundação (FAZER PRIMEIRO, ~1 sessão)

Objetivo: parar de repetir HTML e ter como "vestir" qualquer tela Stitch rapidamente.

1. **Layout compartilhado** em `src/Views/layouts/`:
   - `head.php` — `<head>` completo (meta PWA, fontes, Tailwind, tema, CSS). Recebe `$title`.
   - `top-app-bar.php` — header fixo (ícone igreja → `/dashboard`, título "PIB Oliveira", sino → `/notificacoes`).
   - `bottom-nav.php` — nav fixa 4 itens (Início · Escalas · Repertório · Perfil), item ativo destacado via `$activeNav`.
   - `flash.php` — mensagens de sucesso/erro via `$_SESSION['flash']`.
   - Padrão de view: `<?php $title=...; $activeNav=...; require __DIR__.'/layouts/head.php'; ?> ... conteúdo ... <?php require __DIR__.'/layouts/bottom-nav.php'; ?>`.
2. **Base de Model** `src/Models/Model.php` (PDO compartilhado, helpers `find/all/where`), e um `BaseController` já existe.
3. **Helper de extração do Stitch:** baixar o HTML de cada tela e salvar em `.stitch/{##-slug}.html` como referência de conversão.
   - Via MCP: `get_screen` (ou `list_screens`) → `htmlCode.downloadUrl` → **baixar IMEDIATAMENTE** (o link expira a cada chamada). Ver [[stitch-mcp-workflow]].
4. **Organizar `src/Views/` em subpastas** por domínio: `auth/`, `app/` (dashboard, busca, 404, offline), `escalas/`, `repertorio/`, `perfil/`, `ministerio/`, `vida-espiritual/`.
5. **Mover login.php e dashboard.php** para o novo layout (prova de conceito do pipeline).

**DoD da Wave 0:** login + dashboard renderizando pelo layout compartilhado, sem `<head>` duplicado; pipeline "Stitch HTML → view PHP" validado localmente no Apache.

---

## Wave 1 — Núcleo Escalas (GSD Phase 4)

| # | Tela | Rota | Controller::método | View | Tabelas |
|---|------|------|--------------------|------|---------|
| 03 | Escalas (lista) | `GET /escalas` | `ScheduleController::index` | `escalas/index` | schedules, schedule_users |
| 04 | Escala — Detalhe | `GET /escalas/{id}` | `ScheduleController::show` | `escalas/show` | schedules, schedule_users, schedule_songs, schedule_roteiro, schedule_comments |
| 05 | Escala — Criar/Editar | `GET/POST /escalas/nova`, `/escalas/{id}/editar` | `ScheduleController::create/edit/store/update` | `escalas/form` | schedules, schedule_users, schedule_songs |
| 06 | Registrar Faltas | `GET/POST /escalas/{id}/faltas` | `ScheduleController::attendance` | `escalas/faltas` | schedule_users |
| 40 | Auto-escalação Balanceada | `GET/POST /escalas/auto` | `ScheduleController::autoAssign` | `escalas/auto` | schedules, schedule_users, user_unavailability |
| 41 | Sugerir Setlist | `GET/POST /escalas/{id}/setlist-sugerida` | `ScheduleController::suggestSetlist` | `escalas/setlist-sugerida` | songs, schedule_songs |
| 43 | Modo Culto ao Vivo | `GET /escalas/{id}/ao-vivo` | `ScheduleController::live` | `escalas/ao-vivo` | schedules, schedule_songs |
| 44 | Modo Ensaio | `GET /escalas/{id}/ensaio` | `ScheduleController::rehearsal` | `escalas/ensaio` | schedule_songs, songs |
| 45 | Compartilhar Setlist | `GET /escalas/{id}/setlist` | `ScheduleController::share` | `escalas/setlist` | schedules, schedule_songs, schedule_users |

> Algoritmos de 40/41 são **PHP puro** (sem IA/nuvem) — balancear rotação, respeitar `user_unavailability`, variar tom/BPM.

---

## Wave 2 — Repertório & Cifras (GSD Phase 5)

| # | Tela | Rota | Controller::método | View | Tabelas |
|---|------|------|--------------------|------|---------|
| 07 | Repertório (lista) | `GET /repertorio` | `SongController::index` | `repertorio/index` | songs, tags, song_tags |
| 08 | Música — Detalhe | `GET /musicas/{id}` | `SongController::show` | `repertorio/show` | songs, song_tags |
| 09 | Música — Criar/Editar | `GET/POST /musicas/nova`, `/musicas/{id}/editar` | `SongController::create/store/edit/update` | `repertorio/form` | songs, tags, song_tags |
| 10 | Cifra (palco) | `GET /musicas/{id}/cifra` | `SongController::chord` | `repertorio/cifra` | songs |
| 42 | Estatísticas de Repertório | `GET /repertorio/stats` | `SongController::stats` | `repertorio/stats` | songs, schedule_songs |
| 31 | Sugestões de Música (fila) | `GET /sugestoes` | `SongSuggestionController::index` | `repertorio/sugestoes` | song_suggestions |
| 31b | Sugerir Música (form) | `GET/POST /sugestoes/nova` | `SongSuggestionController::create/store` | `repertorio/sugerir` | song_suggestions |

> ⚠️ Tela 42 saiu num clone de design system (`cad3119c`) — reaplicar `assets/18053454826462421656` no Stitch antes de extrair, ou só ajustar as classes na conversão.

---

## Wave 3 — Perfil, Config & Fechamento

| # | Tela | Rota | Controller::método | View | Tabelas |
|---|------|------|--------------------|------|---------|
| 11 | Perfil | `GET /perfil` | `ProfileController::index` | `perfil/index` | users, user_settings |
| 12 | Editar Perfil | `GET/POST /perfil/editar` | `ProfileController::edit/update` | `perfil/editar` | users |
| 34 | Alterar Senha | `GET/POST /perfil/senha` | `ProfileController::password` | `perfil/senha` | users |
| 13 | Configurações | `GET /configuracoes` | `SettingsController::index` | `app/configuracoes` | user_settings |
| 35 | Preferências de Notificação | `GET/POST /configuracoes/notificacoes` | `SettingsController::notifications` | `app/notif-prefs` | user_settings, push_subscriptions |
| 17 | Indisponibilidades | `GET/POST /indisponibilidades` | `UnavailabilityController::index/store` | `perfil/indisponibilidades` | user_unavailability |
| 33 | Recuperar Senha | `GET/POST /recuperar-senha` | `LoginController::recover` | `auth/recuperar-senha` | users |
| 37 | Onboarding | `GET /onboarding` | `OnboardingController::index` | `app/onboarding` | — |
| 38 | Ajuda / FAQ | `GET /ajuda` | `PageController::ajuda` | `app/ajuda` | — |
| 39 | Offline (PWA) | estático | — | `app/offline` | — |
| — | 404 | fallback Router | — | `app/404` | — |

---

## Wave 4 — Comunidade, Vida Espiritual & Admin

| # | Tela | Rota | Controller::método | View | Tabelas |
|---|------|------|--------------------|------|---------|
| 02 | Dashboard | `GET /dashboard` | `DashboardController::index` (existe — enriquecer) | `app/dashboard` | schedules, avisos, notifications |
| 14 | Avisos (lista) | `GET /avisos` | `AvisoController::index` | `app/avisos` | avisos |
| 15 | Aviso — Detalhe/Criar | `GET /avisos/{id}`, `GET/POST /avisos/novo` | `AvisoController::show/create/store` | `app/aviso-form` | avisos |
| 16 | Notificações | `GET /notificacoes` | `NotificationController::index` | `app/notificacoes` | notifications |
| 19 | Mensagens (mural) | `GET/POST /mensagens` | `MessageController::index` | `app/mensagens` | (definir) |
| 23 | Aniversariantes | `GET /aniversariantes` | `MemberController::birthdays` | `app/aniversariantes` | users |
| 18 | Metrônomo | `GET /metronomo` | `PageController::metronomo` | `app/metronomo` | — (JS) |
| 53 | Busca Global | `GET /busca` | `SearchController::index` | `app/busca` | songs, users, schedules, avisos, devotionals |
| 51 | Agenda / Eventos | `GET /agenda` | `AgendaController::index` | `app/agenda` | schedules |
| 25 | Leitura Bíblica | `GET /leitura` | `ReadingController::index` | `vida-espiritual/leitura` | reading_progress |
| 26 | Leitura — Escolher Plano | `GET /leitura/planos` | `ReadingController::plans` | `vida-espiritual/planos` | reading_progress |
| 27 | Mural de Oração | `GET /oracao` | `PrayerController::index` | `vida-espiritual/oracao` | prayer_requests, prayer_interactions |
| 28 | Novo Pedido de Oração | `GET/POST /oracao/novo` | `PrayerController::create/store` | `vida-espiritual/oracao-form` | prayer_requests |
| 49 | Oração — Detalhe | `GET /oracao/{id}` | `PrayerController::show` | `vida-espiritual/oracao-show` | prayer_requests, prayer_interactions |
| 29 | Devocionais (lista) | `GET /devocionais` | `DevotionalController::index` | `vida-espiritual/devocionais` | devotionals, devotional_reads |
| 30 | Devocional — Detalhe | `GET /devocionais/{id}` | `DevotionalController::show` | `vida-espiritual/devocional-show` | devotionals, devotional_reads |
| 50 | Devocional — Comentários | `GET/POST /devocionais/{id}/comentarios` | `DevotionalController::comments` | `vida-espiritual/devocional-comentarios` | devotional_comments |
| 32 | Ministério / Quem Somos | `GET /ministerio` | `MinistryController::index` | `ministerio/index` | users |
| 24 | Painel do Líder | `GET /lider` | `MinistryController::leaderPanel` | `ministerio/lider` | — |
| 20 | Membros (lista, admin) | `GET /membros` | `MemberController::index` | `ministerio/membros` | users |
| 21 | Membro — Detalhe (admin) | `GET /membros/{id}` | `MemberController::show` | `ministerio/membro-show` | users, schedule_users |
| 36 | Convidar Membro (admin) | `GET/POST /membros/convidar` | `MemberController::invite/store` | `ministerio/convidar` | users |
| 22 | Relatórios (admin) | `GET /relatorios` | `ReportController::index` | `ministerio/relatorios` | schedule_users, songs |
| 46 | Equipes (admin) | `GET /ministerio/equipes` | `MinistryController::teams` | `ministerio/equipes` | (definir) |
| 47 | Funções/Classificações (admin) | `GET /ministerio/funcoes` | `MinistryController::roles` | `ministerio/funcoes` | (definir) |
| 48 | Modelos de Roteiro (admin) | `GET /ministerio/modelos-roteiro` | `MinistryController::roteiroTemplates` | `ministerio/modelos-roteiro` | (definir) + sub-tela "Editar Bloco" Stitch `69d53448` |
| 52 | Limpeza & Equipamentos | `GET /escala-limpeza` | `FacilityController::index` | `ministerio/limpeza` | (definir) |

> Algumas telas (Mensagens, Equipes, Funções, Modelos de Roteiro, Limpeza) **não têm tabela ainda** — adicionar ao `schema.sql` quando chegar nelas.

---

## Definition of Done — por tela

- [ ] Rota registrada no `router.php` (GET e POST quando houver form).
- [ ] Controller/método com `AuthMiddleware::requireLogin()` (e checagem de `admin` quando for tela de gestão).
- [ ] View no layout compartilhado (top-app-bar / bottom-nav / sem `<head>` duplicado).
- [ ] HTML adaptado do Stitch (`.stitch/##-slug.html`), classes Tailwind do tema, dark/light OK.
- [ ] Dados reais via Model/PDO (sem dados chumbados) + `htmlspecialchars()` na saída.
- [ ] Forms com token CSRF e validação (`Validator`).
- [ ] Testado localmente no Apache (navegação ←/→, botões, FAB admin).

---

## ▶️ Comece por aqui amanhã
1. **Wave 0** (fundação do layout) — destrava todas as outras.
2. **Wave 1, telas 03→04→05→06** (núcleo Escalas = GSD Phase 4) — maior valor de MVP.
3. Seguir Wave 2, 3, 4.

> Alternativa GSD: rodar `/gsd-plan-phase 4` para gerar o PLAN.md formal da Fase 4 a partir deste mapa.

## Pendências paralelas (rápidas, não bloqueiam)
- Limpar duplicatas no Stitch (interface web): "Nova Escala" `cc24c61a`,`5bc13a69` (manter `dadea47b`); Metrônomo `30531c34`,`203abfa2` (manter `efcc2c36`).
- Reaplicar DS `18053454…` na tela 42.
