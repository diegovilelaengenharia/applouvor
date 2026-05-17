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
- Plan 4A: Tela `admin/registrar_faltas.php` com lista + toggles (presente/faltou/justificado)
- Plan 4B: API de salvamento de faltas (`api/save_absences.php`) + suporte a `absent_justified`
- Plan 4C: Integração com perfil do membro (estatísticas de presença)

---

### Phase 5: Música Modernizada
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
- Plan 5A: Redesign de `admin/musica_detalhe.php` — streaming cards + stats cards
- Plan 5B: Formulário de sugestão + fila de aprovação para líder + badge no dashboard
- Plan 5C: Página de setlist para impressão/compartilhamento (`admin/escala_setlist.php`)
- Plan 5D: Stats de repertório — última vez usada + ranking de uso (query em schedule_songs JOIN songs)

---

### Phase 6: Metrônomo Pro
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
- Plan 6A: Refatorar `admin/metrônomo.php` — Tap BPM com média + slider vertical + clique audível (Web Audio API)
- Plan 6B: Integração BPM da música: parâmetro `?bpm=127` pré-carrega o valor + garantir cache no SW

---

### Phase 7: Histórico e Estatísticas do Membro
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
- Plan 7A: Query de histórico de escalas por membro + UI em `admin/membro_detalhe.php`
- Plan 7B: Cálculo e exibição de taxa de presença + alerta pastoral (queda < 60% em 4 escalas)
- Plan 7C: Ranking de presença em `admin/membros.php` (admin only)

---

### Phase 8: Devocional+
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

### Phase 9: Deploy Final
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
```

---

## Visão Geral (Atualizada)

| # | Fase | Goal | Plans | Status |
|---|------|------|-------|--------|
| 1 | Git Cleanup + Hardening | Base limpa + segurança pré-viagem | 4 | ✅ Concluída |
| 2 | Confirmar Escala | Músico confirma/recusa + push real | 4 | ✅ Concluída |
| 3 | Roteiro de Culto | Líder monta fluxo do culto + nota interna | 4 | ⬜ |
| 4 | Registrar Faltas | Ausente vs justificado + pastoral | 3 | ⬜ |
| 5 | Música Modernizada | Cards streaming + aprovação sugestões + stats | 4 | ⬜ |
| 6 | Metrônomo Pro | Tap BPM + áudio + slider + offline | 2 | ⬜ |
| 7 | Histórico Membro | Presença + alerta pastoral | 3 | ⬜ |
| 8 | Devocional+ | Streak + versículo + orações da equipe | 3 | ⬜ |
| 9 | Deploy Final | Deploy documentado + SW offline + PWA | 3 | ⬜ |

---
*Criado: 2026-05-16 | v1.1 — Atualizado 2026-05-17 com gaps da auditoria profissional*
