# Roadmap — App Louvor PIB Oliveira

*Milestone 1: Modernização + Features Faltantes*
*Estrutura: MVP Vertical — cada fase entrega valor usável*

---

## Visão Geral

| # | Fase | Goal | Requirements | Critérios |
|---|------|------|--------------|-----------|
| 1 | Git Cleanup | Base limpa para desenvolvimento | GIT-01..03 | 3 |
| 2 | Confirmar Escala | Músico confirma/recusa presença | ESC-01..05 | 4 |
| 3 | Roteiro de Culto | Líder cria roteiro, músico visualiza | ROT-01..05 | 4 |
| 4 | Registrar Faltas | Líder registra ausências pós-culto | FAL-01..04 | 3 |
| 5 | Música Modernizada | Detalhe visual + sugestões | MUS-01..04 | 4 |
| 6 | Metrônomo Pro | Tap BPM + slider + BPM da escala | MET-01..03 | 3 |
| 7 | Histórico Membro | Presença e estatísticas | MEM-01..03 | 3 |
| 8 | Devocional+ | Streak + versículo da semana | DEV-01..02 | 2 |
| 9 | Deploy Final | PWA atualizado + produção | PWA-01..03 | 3 |

**9 fases | 29 requisitos | 100% cobertos ✓**

---

## Fases Detalhadas

### Phase 1: Git Cleanup + Hardening ✅ CONCLUÍDA (2026-05-17)
**Goal:** Commitar o estado atual do projeto de forma organizada, remover desktop.ini do tracking, organizar maintenance/, e hardenizar segurança antes da viagem do Diego.
**Mode:** mvp

**Requirements:** GIT-01 ✅, GIT-02 ✅, GIT-03 ✅

**Resultado:** 12 commits semânticos | working tree clean | secrets fora do tracking | deploy.php removido | maintenance/ organizado

**Ações manuais pendentes (Diego — antes de viajar):**
- Rotacionar DB_PASS no Hostinger + atualizar .htaccess de produção
- Regenerar VAPID keys + upload para produção
- Criar `maintenance/.htaccess` com `Require all denied` em produção
- Ver roteiro em `.planning/phases/01-git-cleanup/01D-SUMMARY.md`

Plans:
- [x] 01A-PLAN.md — Commits semânticos por área
- [x] 01B-PLAN.md — desktop.ini removido + .gitignore expandido (credenciais, VAPID, backup)
- [x] 01C-PLAN.md — maintenance/ organizado + scripts de setup versionados
- [x] 01D-PLAN.md — Hardening pré-viagem (secrets, deploy.php, scripts admin)

---

### Phase 2: Confirmar Escala (Músico)
**Goal:** Músico consegue confirmar ou recusar presença na escala diretamente pelo app, com feedback visual imediato. Líder vê o status de cada participante.
**Mode:** mvp

**Requirements:** ESC-01, ESC-02, ESC-03, ESC-04, ESC-05

**Estado atual encontrado:** `api/confirm_scale.php` já pronta e funcionando. Coluna `schedule_users.status` suporta confirmed/pending/absent. Infraestrutura de push subscriptions existe — falta script de envio server-side.

**Success Criteria:**
1. Músico vê botões "Confirmar" e "Recusar" na sua escala e ao clicar o status muda sem reload
2. Badge de status (confirmado 🟢 / pendente 🟡 / recusado 🔴) visível em cada participante
3. Contador "X/Y confirmados" aparece na listagem de escalas para o líder
4. Push notification disparada na **publicação da escala** (convocação) E 2 dias antes (lembrete)

**Gaps adicionados pela auditoria (2026-05-17):**
- Push de publicação (convocação inicial) — não estava no plano original
- `api/send_reminders.php` com envio server-side real (subscriptions existem, envio não)
- Verificar se Hostinger suporta cron job; se não, criar botão "Enviar lembrete" manual

**Plans:**
- [x] Plan 2A (02-01): UI de confirmação na view do músico (`admin/escala_detalhe.php`) — footer sticky com AJAX *(ESC-01, ESC-02 entregues — 2026-05-17)*
- [x] Plan 2B (02-02): Badges de status nos cards de participantes (admin + músico) *(ESC-03 entregue — 2026-05-17)*
- [x] Plan 2C (02-03): Contador de confirmações na listagem de escalas (`admin/escalas.php`) *(ESC-04 entregue — 2026-05-17)*
- [x] Plan 2D (02-04): Push server-side — AESGCM real + send_reminders endpoint + widget dashboard + auto trigger *(ESC-05 entregue — 2026-05-17)*

---

### Phase 3: Roteiro de Culto
**Goal:** Líder cria um roteiro ordenado dentro de cada escala (músicas + outros itens). Músico visualiza o roteiro completo antes do culto.
**Mode:** mvp

**Requirements:** ROT-01, ROT-02, ROT-03, ROT-04, ROT-05

**Success Criteria:**
1. Líder adiciona itens ao roteiro (Música, Oração, Palavra, Anúncio, Intervalo) e reordena
2. Roteiro é salvo e persistido no banco com ordem definida
3. Músico visualiza roteiro completo em read-only na sua view da escala
4. Tom customizado por música na escala pode ser definido e difere do tom padrão

**Gaps adicionados pela auditoria (2026-05-17):**
- Tabela `schedule_roteiro` deve ter `nota_interna` por item (visível só para líder — ex: "aqui Diego prega os pedidos de oração"). É o que separa app de lista de app ministerial.
- Usar setas ▲/▼ para reordenação mobile — drag-and-drop é frágil no toque; preservar como enhancement desktop opcional

**Plans:**
- Plan 3A: Tabela `schedule_roteiro` no banco + migrations (id, schedule_id, order, type, title, custom_tone, nota_interna)
- Plan 3B: UI de edição do roteiro para líder (add/reorder com setas ▲/▼/delete items)
- Plan 3C: View do roteiro para músico (read-only, clean layout — nota_interna oculta)
- Plan 3D: Campo "tom customizado" por música na escala

---

### Phase 3.5: Limpeza & Organização
**Goal:** Remover arquivos obsoletos, arquivar scripts one-time de manutenção, e mover utilitários de dev/deploy para `tools/` — deixando o projeto limpo para as próximas fases.
**Mode:** mvp

**Success Criteria:**
1. Todos os arquivos backup/test/debug removidos do tracking
2. Scripts one-time de maintenance/ movidos para maintenance/ARCHIVED/
3. Scripts de deploy e dev organizados em tools/
4. `desktop.ini` removido de todas as pastas (maintenance/, database/)
5. Working tree limpa com commits semânticos por área

**Plans:**
- Plan 3.5-A: Deletar arquivos obsoletos (backups, test, .bak, desktop.ini residuais)
- Plan 3.5-B: Arquivar scripts de manutenção one-time em maintenance/ARCHIVED/
- Plan 3.5-C: Criar tools/ e mover scripts de deploy/dev

---

### Phase 4: Registrar Faltas
**Goal:** Após um culto/ensaio, líder registra quem compareceu e quem faltou. Isso alimenta o histórico de presença.
**Mode:** mvp

**Requirements:** FAL-01, FAL-02, FAL-03, FAL-04

**Success Criteria:**
1. Botão "Registrar Faltas" aparece em escalas passadas no dashboard do líder
2. Tela exibe lista de participantes com toggle "faltou / justificou / presente" por pessoa
3. Salvamento persiste status no banco (`schedule_users.status`)
4. Histórico do membro já reflete as faltas registradas

**Gaps adicionados pela auditoria (2026-05-17):**
- Dois estados de ausência: `absent` (faltou sem aviso) vs `absent_justified` (justificou com antecedência). Peso pastoral diferente no histórico.
- Campo opcional de motivo (texto livre) para o líder anotar internamente

**Plans:**
- [x] Plan 4A (04-01): Migration 004 + botão em escalas.php + tela `admin/registrar_faltas.php` com toggles *(FAL-01, FAL-02 — 2026-05-17)*
- [x] Plan 4B (04-02): API `api/save_absences.php` + suporte a `absent_justified` *(FAL-03 — 2026-05-17)*
- [x] Plan 4C (04-03): Integração com `admin/membro_detalhe.php` — badges + stats de presença *(FAL-04 — 2026-05-17)*

---

### Phase 5: Música Modernizada ✅ CONCLUÍDA (2026-05-17)
**Goal:** Página de detalhe da música tem visual moderno com links de streaming como cards, tom/BPM em destaque, e músico pode sugerir músicas.
**Mode:** mvp

**Requirements:** MUS-01, MUS-02, MUS-03, MUS-04

**Success Criteria:**
1. Detalhe da música exibe Spotify, YouTube, Cifra Club, Letras como cards visuais com ícone
2. Tom, BPM e Duração aparecem em cards destacados lado a lado (não texto inline)
3. Músico pode abrir formulário de sugestão — líder aprova/rejeita na fila de sugestões
4. Setlist de uma escala tem versão para impressão/compartilhamento

**Gaps adicionados pela auditoria (2026-05-17):**
- Sugestão de música precisa de fila de aprovação (status: suggested/approved/rejected) + badge no dashboard do líder "X sugestões pendentes"
- Stats básicas de repertório: última vez que cada música foi tocada + frequência. Simples, alto valor.

**Plans:**
- [x] Plan 5A (05-01): Redesign de `admin/musica_detalhe.php` — streaming cards com branding de plataforma + stats cards destacados *(MUS-01, MUS-02)*
- [x] Plan 5B (05-02): Badge de sugestões pendentes no dashboard (admin only) + corrigir contagem em dashboard_data.php *(MUS-03)*
- [x] Plan 5C (05-03): Página de setlist para impressão/compartilhamento (`admin/escala_setlist.php`) + link em escala_detalhe.php *(MUS-04)*
- [x] Plan 5D (05-04): Stats de repertório — "última vez tocada" em cada card de música no repertorio.php *(MUS-05)*

---

### Phase 6: Metrônomo Pro ✅ CONCLUÍDA (2026-05-17)
**Goal:** Metrônomo com Tap BPM funcional, clique audível, slider vertical, e integração com BPM da música selecionada na escala.
**Mode:** mvp

**Requirements:** MET-01, MET-02, MET-03

**Success Criteria:**
1. Tap BPM calcula média dos últimos 4+ toques e exibe BPM em tempo real
2. Clique audível (Web Audio API) — metrônomo funciona no ensaio sem fones
3. Slider vertical permite ajuste fino do BPM (40-220) com arraste
4. Se músico acessa metrônomo a partir de uma música, BPM é pré-carregado

**Gaps adicionados pela auditoria (2026-05-17):**
- **Clique audível é a feature mais importante de um metrônomo** — sem áudio é só visual, inútil no ensaio. Usar Web Audio API (`AudioContext`, `OscillatorNode`) — sem dependências.
- Página deve estar no cache do SW (uso offline durante ensaio com sinal fraco)

**Plans:**
- [x] Plan 6A (06-01): Criar `admin/metronomo.php` — Tap BPM com média + slider + clique audível (Web Audio API) *(MET-01, MET-02)*
- [x] Plan 6B (06-02): Card no dashboard + link de musica_detalhe.php + cache no SW *(MET-03)*

---

### Phase 7: Histórico e Estatísticas do Membro ✅ CONCLUÍDA (2026-05-17)
**Goal:** Cada músico e o líder conseguem ver o histórico de participação em escalas e a taxa de presença.
**Mode:** mvp

**Requirements:** MEM-01, MEM-02, MEM-03

**Success Criteria:**
1. Perfil do músico exibe lista das últimas 10 escalas com status (confirmou/faltou/justificou)
2. Porcentagem de presença (ex: "8/10 escalas — 80%") visível no card do membro
3. Líder vê ranking de presença na página de membros (ordenável) — visível SOMENTE para admin
4. Alerta pastoral discreto quando presença cai abaixo de 60% nas últimas 4 escalas

**Gaps adicionados pela auditoria (2026-05-17):**
- Alerta pastoral — "pode precisar de atenção" transforma auditoria em cuidado cristão
- Ranking de presença visível só para admin (evitar competição e constrangimento entre músicos)

**Plans:**
- [x] Plan 7A: Histórico de escalas em membro_detalhe.php *(entregue na Phase 4 — MEM-01)*
- [x] Plan 7B: Taxa de presença em membro_detalhe.php *(entregue na Phase 4 — MEM-02)* + alerta pastoral (queda < 60% em 4 escalas) *(Phase 7 — admin only)*
- [x] Plan 7C: Ranking de presença em `admin/membros.php` (admin only) + ordenação por nome/presença/escalas *(MEM-03)*

---

### Phase 8: Devocional+ ✅ CONCLUÍDA (2026-05-18)
**Goal:** Devocional tem streak de leitura visível, versículo/hino da semana, e orações de intercessão da equipe.
**Mode:** mvp

**Requirements:** DEV-01, DEV-02

**Success Criteria:**
1. Home do músico exibe versículo/hino da semana (definido pelo líder como aviso especial)
2. Devocional exibe streak atual de dias consecutivos lidos com ícone de chama
3. Líder posta pedidos de oração semanais; equipe visualiza na home

**Gaps adicionados pela auditoria (2026-05-17):**
- Orações de intercessão da equipe — `admin/oracao.php` existia na versão antiga (23.01.2026) e foi perdido. Resgate ou recriação simples com lista de pedidos + quem está orando.
- Streak deve ser calculado do banco (`reading_progress`), não de localStorage (já está no banco, só calcular os dias consecutivos)

**Plans:**
- Plan 8A: Streak de leitura — calcular dias consecutivos a partir de `reading_progress` + exibir na `admin/leitura.php`
- Plan 8B: Versículo da semana na home — aviso com tag especial ou campo dedicado
- Plan 8C: Orações de intercessão — resgate de `admin/oracao.php` com pedidos da semana + visualização na home do músico

---

### Phase 9: Deploy Final ✅ CONCLUÍDA (2026-05-18)
**Goal:** App atualizado em produção no Hostinger com PWA e HTTPS verificados, processo de deploy documentado.
**Mode:** mvp

**Requirements:** PWA-01, PWA-02, PWA-03

**Success Criteria:**
1. `vilela.eng.br/applouvor` carrega a versão com todas as features do milestone
2. Service worker atualizado (cache bust + nova versão) com versionamento ligado ao APP_VERSION
3. HTTPS ativo, manifest e install prompt funcionando em iOS e Android
4. Processo de deploy documentado em `DEPLOY.md` (sem deploy.php — foi removido na Phase 1)

**Gaps adicionados pela auditoria (2026-05-17):**
- `deploy.php` removido em Phase 1 — criar `DEPLOY.md` com processo manual (FTP + FileZilla ou SSH)
- Auditar quais rotas estão no cache do SW (músico precisa ver escala + metrônomo offline)
- Convenção de versionamento: `CACHE_NAME = 'louvor-pib-v' . APP_VERSION` — constante em `includes/config.php`

**Plans:**
- Plan 9A: Criar `DEPLOY.md` com processo manual + atualizar versionamento do SW em `config.php`
- Plan 9B: Auditar cache do SW — garantir que escala, roteiro e metrônomo estão cacheados offline
- Plan 9C: Upload via FTP/Hostinger + verificação pós-deploy + testes de PWA install em iOS e Android

---

### Phase 10: Harmonização Visual
**Goal:** Padronizar tipografia, botões, espaçamentos e densidade em todas as páginas (admin + músico), criando um sistema visual harmônico e consistente no desktop e no mobile (mobile-first 375px), sem quebrar funcionalidades existentes.
**Mode:** ui

**Requirements:** UI-01, UI-02, UI-03, UI-04

**Estado atual encontrado:** `assets/css/design-system.css` já define fontes (Inter / Inter Tight), cor primária `#3B82F6` e componente base `.pib-card`. Porém vários CSS de páginas declaram fontes/tamanhos/raios redundantes, gerando inconsistência (elementos grandes demais, outros espremidos), especialmente no mobile.

**Success Criteria:**
1. Escala tipográfica única (tamanho base 16px, secundário 14px, títulos proporcionais) aplicada via tokens do design-system — sem font-size/family rígidos espalhados nos CSS de página
2. Botões com tamanhos e estados padronizados (altura mínima de toque 44px, raio 8–12px, hierarquia primário/secundário/ghost consistente)
3. Espaçamentos e densidade padronizados por tokens (paddings de página, gaps de cards, .pib-card uniforme) — visual harmônico em desktop e mobile
4. Auditoria visual de todas as páginas (admin + músico) confirmando consistência em viewport 375px e desktop, sem regressões funcionais

**Plans:** 4 plans (4 waves)
- [x] 10-01-PLAN.md — Kill 5 :root override blocks (cascade safety) [UI-03]
- [x] 10-02-PLAN.md — Typography token swap across 8 high-traffic files [UI-01]
- [x] 10-03-PLAN.md — Button matrix states + radius tokens + dashboard header hide + FAB dedup [UI-02, UI-03]
- [x] 10-04-PLAN.md — Full visual + non-regression audit (24 screenshots) + Diego sign-off [UI-04]

---

### Phase 11: Migração do Painel Administrativo para React
**Goal:** Configurar o ambiente do projeto React com Vite + TypeScript + Tailwind CSS do zero e migrar a tela principal (Dashboard) do Painel Administrativo, integrando-a de forma assíncrona com as APIs JSON PHP existentes de forma otimizada para a Hostinger.
**Mode:** mvp

**Requirements:** REA-01, REA-02, REA-03, REA-04

**Success Criteria:**
1. Projeto React configurado com Vite, TypeScript e Tailwind CSS v4 na raiz ou em pasta dedicada, gerando build estático otimizado.
2. Layout autoral premium criado em React (Sidebar, TopBar unificado, Dark Mode reativo e Auth Context para cookies de sessão PHP).
3. Dashboard Administrativo principal migrado para React (`admin/index.php` -> React) exibindo os dados em tempo real da API `api/admin/dashboard_data.php`.
4. Transições de página instantâneas e animações Spring de alta qualidade em todos os cliques e hovers.

**Plans:**
- [ ] 11A-PLAN.md — Setup do Ambiente React (Vite + TypeScript + Tailwind v4 + Estrutura de Pastas)
- [ ] 11B-PLAN.md — Layout Autoral & Sistema de Temas (Sidebar, TopBar, Dark Mode, Cookies/Auth)
- [ ] 11C-PLAN.md — Dashboard em React (Migração de admin/index.php, Cards Bento Autoriais, Fetch de API PHP)

---

## Dependencies

```
Phase 1 (Git) → todas as outras (base limpa) ✅ CONCLUÍDA
Phase 2 (Confirmar) → Phase 4 (Faltas) — status 'absent'/'absent_justified' usa mesma coluna
Phase 3 (Roteiro) → independente
Phase 4 (Faltas) → Phase 7 (Histórico) — alimenta estatísticas
Phase 5 (Música) → independente
Phase 6 (Metrônomo) → independente
Phase 7 (Histórico) → depende de Phase 4
Phase 8 (Devocional) → independente
Phase 9 (Deploy) → todas as fases anteriores
Phase 10 (Harmonização) → todas as fases visuais PHP
Phase 11 (React Migration) → depende do setup das APIs PHP existentes (Phase 2-9)
```

---

## Visão Geral (Atualizada)

| # | Fase | Goal | Plans | Status |
|---|------|------|-------|--------|
| 1 | Git Cleanup + Hardening | Base limpa + segurança pré-viagem | 4 | ✅ Concluída |
| 2 | Confirmar Escala | Músico confirma/recusa + push real | 4 | ✅ Concluída |
| 3 | Roteiro de Culto | Líder monta fluxo do culto + nota interna | 4 | ✅ Concluída |
| 3.5 | Limpeza & Organização | Deletar obsoletos, arquivar scripts, organizar tools/ | 3 | ⬜ |
| 4 | Registrar Faltas | Ausente vs justificado + pastoral | 3 | ✅ Concluída |
| 5 | Música Modernizada | Cards streaming + aprovação sugestões + stats | 4 | ✅ Concluída |
| 6 | Metrônomo Pro | Tap BPM + áudio + slider + offline | 2 | ✅ Concluída |
| 7 | Histórico Membro | Presença + alerta pastoral | 3 | ✅ Concluída |
| 8 | Devocional+ | Streak + versículo + orações da equipe | 3 | ✅ Concluída |
| 9 | Deploy Final | Deploy documentado + SW offline + PWA | 3 | ✅ Concluída |
| 10 | Harmonização Visual | Tipografia + botões + espaçamentos consistentes (desktop + mobile) | 4 | ✅ Concluída |
| 11 | Migração React | Painel Principal em Vite + React + Tailwind v4 Autoral | 3 | ⬜ Planejado |

---
*Criado: 2026-05-16 | v1.3 — Atualizado 2026-05-22 para migração do painel para React*
