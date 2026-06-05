# SPEC: Phase 4 — Gestão de Escalas

## 1. Visão Geral (Overview)
O núcleo operacional do aplicativo. Permite que o líder ministerial crie cultos (escalas), designe músicos e instrumentos, enquanto permite que o músico visualize quando deve tocar e confirme ou recuse sua presença. O objetivo é remover as dúvidas de "quem toca domingo?" e organizar a disponibilidade.

## 2. Requisitos Base (Discussão & Decisões)
- **SCHED-01**: Líderes criam/editam escalas. *Decisão técnica: A tabela `schedules` armazena data, hora, tipo e notas.*
- **SCHED-02**: Líderes designam músicos. *Decisão técnica: A tabela `schedule_users` liga usuário à escala com o `assigned_instrument`.*
- **SCHED-03**: Músicos visualizam a lista. *Decisão técnica: A Rota `/escalas` filtrará as próximas e passadas.*
- **SCHED-04**: Músicos confirmam/recusam. *Decisão técnica: O campo `status` em `schedule_users` alterna entre `pending`, `confirmed`, `declined`. A mudança será via formulário POST (ou AJAX) para evitar CSRF em requisições GET.*

## 3. Escopo de Telas (Baseado no Stitch)
- **03: Escalas (lista)** — Abas "Próximas" e "Anteriores". Exibição de cards com os músicos (avatares) e quantidade de músicas.
- **04: Escala — Detalhe** — Visualização completa de uma escala (Roteiro, Participantes, Comentários). O botão principal do usuário será confirmar ou recusar.
- **05: Escala — Criar/Editar (Apenas Admin)** — Formulário para data, hora, tipo e seleção da equipe.
- **06: Registrar Faltas (Apenas Admin)** — Controle do painel de administração da escala para gerenciar quem efetivamente tocou ou faltou (muda o `status` para `absent` ou `absent_justified`).

## 4. Acceptance Criteria (DoD)
- [ ] O Controller `ScheduleController` deve estar coberto pelo `AuthMiddleware`. Telas de edição devem checar se o usuário logado é `admin`.
- [ ] O Banco de Dados deve usar queries `JOIN` para trazer a escala e seus participantes de uma vez.
- [ ] Formulários (`/escalas/nova`, `/escalas/{id}/editar`, `/escalas/{id}/status`) devem utilizar o helper de CSRF criado na Phase 2.
- [ ] A view deve estender a nova arquitetura de Layout (`head.php`, `top-app-bar.php`, `bottom-nav.php`).
