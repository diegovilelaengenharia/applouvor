---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: MVP Operacional
status: executing
last_updated: "2026-06-05T21:00:00.000Z"
last_activity: 2026-06-05 (Wave 4 completa — Membros/Relatórios/Aniversariantes/Ministério/Sugestões/Líder/Mensagens)
progress:
  total_phases: 7
  completed_phases: 5
  total_plans: 12
  completed_plans: 11
  percent: 90
---

# Estado do Projeto — APP Louvor Novíssimo

## Referência do Projeto

Ver: [.planning/PROJECT.md](PROJECT.md) | [.planning/PLAN-STITCH-TO-PHP.md](PLAN-STITCH-TO-PHP.md) | [.planning/NAV-MAP.md](NAV-MAP.md)

**Valor Principal:** Centralizar gestão de escalas e repertório do ministério PIB Oliveira.
**Stack:** PHP 8 MVC manual, MySQL/PDO, Tailwind CDN, Vanilla JS, Hostinger.

---

## ▶️ POSIÇÃO ATUAL

**Próxima tarefa:** Wave 5 — Funcionalidades Avançadas (Auto-escalação, Ao Vivo, Ensaio, Setlist, Metrônomo, Estatísticas, Leitura Bíblica, Busca Global).

Progresso: [██████████] ~90%

---

## ✅ O QUE ESTÁ FEITO

### Wave 0 — Layout Compartilhado
- `src/Views/layouts/head.php` — `<head>` + fonts + Tailwind + theme.js
- `src/Views/layouts/top-app-bar.php` — header PIB Oliveira + sino (badge de não-lidos)
- `src/Views/layouts/bottom-nav.php` — nav inferior 4 itens + fecha HTML
- `src/Views/layouts/flash.php` — mensagens de flash session
- `assets/css/stitch-theme.css` — design system Sacred Minimalist
- `assets/js/theme.js` — dark mode localStorage
- `assets/js/app.js` — PWA service worker + reveal animations

### Wave 1 — Escalas (núcleo)
- Rotas: GET/POST `/escalas`, `/escalas/nova`, `/escalas/{id}`, `/escalas/{id}/editar`, `/escalas/{id}/faltas`, `/escalas/{id}/status`
- `ScheduleController`, `Schedule`, `ScheduleUser` models
- Views: `escalas/{index,show,form,faltas}.php`

### Wave 2 — Repertório (núcleo)
- Rotas: GET/POST `/repertorio`, `/musicas/nova`, `/musicas/{id}`, `/musicas/{id}/editar`, `/musicas/{id}/cifra`
- `SongController`, `Song` model
- Views: `repertorio/{index,show,form,cifra}.php`

### Wave 3 — Perfil / Config / Utilitárias
- **Perfil:** GET/POST `/perfil`, `/perfil/editar`, `/perfil/senha`
  - `ProfileController`, `User` model
  - Views: `perfil/{index,editar,senha}.php`
- **Configurações:** GET/POST `/configuracoes`, `/configuracoes/notificacoes`
  - `SettingsController`, `UserSetting` model
  - Views: `app/configuracoes.php`, `app/notif-prefs.php`
- **Indisponibilidades:** GET/POST `/indisponibilidades`, `/indisponibilidades/{id}/remover`
  - `UnavailabilityController`, `Unavailability` model
  - View: `perfil/indisponibilidades.php`
- **Utilitárias:** `/ajuda`, `/onboarding`, `/offline`, `/recuperar-senha`
  - `PageController`, `LoginController::recover`
  - Views: `app/{ajuda,onboarding,offline,404}.php`, `auth/recuperar-senha.php`

### Wave 4 — Dashboard / Avisos / Notificações / Vida Espiritual / Admin (COMPLETA)
- **Dashboard enriquecido** — card próximo culto + aviso liderança + Confirmar/Recusar
- **Avisos** — lista + detalhe + form criação (admin FAB +)
  - `AvisoController`, `Aviso` model
  - Views: `app/{avisos,aviso-detalhe,aviso-form}.php`
- **Notificações** — lista com filtros (Todas/Escalas/Avisos/Lembretes), marcar lida
  - `NotificationController`, `Notification` model
  - View: `app/notificacoes.php`
- **Mural de Oração** — lista com filtros, FAB +, "Estou orando 🙏"
  - `PrayerController`, `PrayerRequest` model
  - Views: `vida-espiritual/{oracao,oracao-novo,oracao-detalhe}.php`
- **Devocionais** — lista com streak, destaque "NÃO LIDO", marcar como lido, comentários
  - `DevotionalController`, `Devotional` model
  - Views: `vida-espiritual/{devocionais,devocional}.php`
- **Membros** — lista com busca + ranking presença, detalhe, convidar
  - `MemberController`, `Member` model
  - Views: `membros/{index,show,convidar}.php`
  - Rotas: GET `/membros`, `/membros/convidar`, `/membros/{id}`; POST `/membros/convidar`
- **Relatórios** — KPIs (6 cards), top 5 músicas mais tocadas, filtro 7d/1m/3m
  - `ReportController` (usa Member model + PDO direto)
  - View: `app/relatorios.php`
- **Aniversariantes** — lista agrupada por mês, destaque mês atual, Parabenizar via WhatsApp
  - `ReportController::birthdays()`
  - View: `app/aniversariantes.php`
- **Ministério / Quem Somos** — banner, sobre, menu de gerenciamento (admin)
  - `MinisterioController`
  - View: `ministerio/index.php`
- **Sugestões de Música** — tabs Pendentes/Aprovadas/Recusadas, aprovar/recusar (admin), FAB +
  - `SuggestionController`, `Suggestion` model
  - Views: `sugestoes/{index,nova}.php`
  - Rotas: GET `/sugestoes`, `/sugestoes/nova`; POST `/sugestoes/nova`, `/sugestoes/{id}/aprovar`, `/sugestoes/{id}/recusar`
- **Painel do Líder** — KPIs de pendências + 8 atalhos rápidos (admin)
  - `LiderController`
  - View: `app/lider.php`
- **Mensagens** — placeholder "em breve" com link WhatsApp
  - `MessageController`
  - View: `app/mensagens.php`

---

## ⬜ O QUE FALTA (Wave 5)

### Wave 5 — Funcionalidades Avançadas
| # | Tela | Rota | Stitch | Status |
|---|------|------|--------|--------|
| 40 | Auto-escalação | `/escalas/auto` | `1b6b9230` | ⬜ pendente |
| 41 | Sugerir Setlist | `/escalas/{id}/setlist-sugerida` | `b365c356` | ⬜ pendente |
| 43 | Ao Vivo | `/escalas/{id}/ao-vivo` | `25bae697` | ⬜ pendente |
| 44 | Modo Ensaio | `/escalas/{id}/ensaio` | `33203941` | ⬜ pendente |
| 45 | Compartilhar Setlist | `/escalas/{id}/setlist` | `0647c160` | ⬜ pendente |
| 42 | Estatísticas Repertório | `/repertorio/stats` | `57dc9502` | ⬜ pendente |
| 18 | Metrônomo | `/metronomo` | `efcc2c36` | ⬜ pendente |
| 25 | Leitura Bíblica | `/leitura` | `b2875c65` | ⬜ pendente |
| 51 | Agenda/Eventos | `/agenda` | — | ⬜ pendente |
| 53 | Busca Global | `/busca` | — | ⬜ pendente |

---

## Pendências Técnicas
- **Limpeza no Stitch (UI web):** duplicatas a apagar: `cc24c61a`, `5bc13a69` (manter `dadea47b`); `30531c34`, `203abfa2` (manter `efcc2c36`).
- **MySQL test:** todas as telas precisam de MySQL ligado no XAMPP para teste funcional.
- **Reações em Avisos/Devocionais:** campo de reações decorativo — implementar `aviso_reactions` / `devotional_reactions` quando necessário.
- **Recuperar senha:** versão informativa (redireciona para WhatsApp). Trocar por self-service quando houver infra de e-mail.
- **`user_instrument` na sessão:** adicionado ao `auth.php` login (linha 57); sessões antigas não têm — relogar para ver o instrumento no dashboard.
- **Mensagens:** sem backend real (sem tabela `messages` no schema). Tela atual é placeholder "em breve". Implementar na Wave 6.

---

## Continuidade da Sessão
Última sessão: 2026-06-05 (sessão 5 — Wave 4 completa)

**Começar por Wave 5:**
1. Metrônomo `/metronomo` — buscar tela `efcc2c36` (já gerada no Stitch)
2. Auto-escalação `/escalas/auto` — buscar tela `1b6b9230`
3. Ao Vivo `/escalas/{id}/ao-vivo` — buscar tela `25bae697`
4. Ensaio `/escalas/{id}/ensaio` — buscar tela `33203941`

Padrão: `get_screen` → `WebFetch HTML URL` → escrever Controller + Model + Views → adicionar rota → lint.

Ver: [HANDOFF.md](HANDOFF.md) para guia completo de onboarding (Claude Code + Gemini).
