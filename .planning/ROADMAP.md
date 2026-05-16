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

### Phase 1: Git Cleanup
**Goal:** Commitar o estado atual do projeto de forma organizada, remover desktop.ini do tracking e preparar base limpa para desenvolvimento.
**Mode:** mvp

**Requirements:** GIT-01, GIT-02, GIT-03

**Success Criteria:**
1. `git status` mostra working tree clean após commit
2. `desktop.ini` não aparece mais no `git status` (está em `.gitignore`)
3. Todos os scripts de manutenção organizados em `maintenance/` e commitados

**Plans:**
- Plan 1A: Analisar mudanças do Gemini, separar em commits semânticos, commitar
- Plan 1B: Adicionar `desktop.ini` ao `.gitignore`, remover do tracking com `git rm --cached`
- Plan 1C: Commitar scripts de manutenção organizados em `maintenance/`

---

### Phase 2: Confirmar Escala (Músico)
**Goal:** Músico consegue confirmar ou recusar presença na escala diretamente pelo app, com feedback visual imediato. Líder vê o status de cada participante.
**Mode:** mvp

**Requirements:** ESC-01, ESC-02, ESC-03, ESC-04, ESC-05

**Success Criteria:**
1. Músico vê botões "Confirmar" e "Recusar" na sua escala e ao clicar o status muda sem reload
2. Badge de status (confirmado 🟢 / pendente 🟡 / recusado 🔴) visível em cada participante
3. Contador "X/Y confirmados" aparece na listagem de escalas para o líder
4. Notificação push é disparada 2 dias antes para músicos com status pendente

**Plans:**
- Plan 2A: UI de confirmação na view do músico (`admin/escala_detalhe.php`) — botões com AJAX
- Plan 2B: Badges de status nos cards de participantes (admin + músico)
- Plan 2C: Contador de confirmações na listagem de escalas (`admin/escalas.php`)
- Plan 2D: Cron job + push notification 2 dias antes (usar infraestrutura de push existente)

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

**Plans:**
- Plan 3A: Tabela `schedule_roteiro` no banco + migrations (id, schedule_id, order, type, title, custom_tone, notes)
- Plan 3B: UI de edição do roteiro para líder (add/reorder/delete items)
- Plan 3C: View do roteiro para músico (read-only, clean layout)
- Plan 3D: Campo "tom customizado" por música na escala

---

### Phase 4: Registrar Faltas
**Goal:** Após um culto/ensaio, líder registra quem compareceu e quem faltou. Isso alimenta o histórico de presença.
**Mode:** mvp

**Requirements:** FAL-01, FAL-02, FAL-03, FAL-04

**Success Criteria:**
1. Botão "Registrar Faltas" aparece em escalas passadas no dashboard do líder
2. Tela exibe lista de participantes com toggle "faltou" por pessoa
3. Salvamento atualiza `schedule_users.status = 'absent'` no banco
4. Histórico do membro já reflete as faltas registradas

**Plans:**
- Plan 4A: Tela `admin/registrar_faltas.php` com lista + toggles de ausência
- Plan 4B: API de salvamento de faltas (`api/save_absences.php`)
- Plan 4C: Integração com perfil do membro (estatísticas de presença)

---

### Phase 5: Música Modernizada
**Goal:** Página de detalhe da música tem visual moderno com links de streaming como cards, tom/BPM em destaque, e músico pode sugerir músicas.
**Mode:** mvp

**Requirements:** MUS-01, MUS-02, MUS-03, MUS-04

**Success Criteria:**
1. Detalhe da música exibe Spotify, YouTube, Cifra Club, Letras como cards visuais com ícone
2. Tom, BPM e Duração aparecem em cards destacados lado a lado (não texto inline)
3. Músico pode abrir formulário de sugestão e enviar nome + artista + link
4. Setlist de uma escala tem versão para impressão/compartilhamento

**Plans:**
- Plan 5A: Redesign de `admin/musica_detalhe.php` — streaming cards + stats cards
- Plan 5B: Formulário de sugestão de música para o músico (view + API)
- Plan 5C: Página de setlist para impressão/compartilhamento (`admin/escala_setlist.php`)

---

### Phase 6: Metrônomo Pro
**Goal:** Metrônomo com Tap BPM funcional, slider vertical, e integração com BPM da música selecionada na escala.
**Mode:** mvp

**Requirements:** MET-01, MET-02, MET-03

**Success Criteria:**
1. Tap BPM calcula média dos últimos 4+ toques e exibe BPM em tempo real
2. Slider vertical permite ajuste fino do BPM (40-220) com arraste
3. Se músico acessa metrônomo a partir de uma música, BPM é pré-carregado

**Plans:**
- Plan 6A: Refatorar `admin/metrônomo.php` — Tap BPM com média + slider vertical
- Plan 6B: Integração BPM da música: parâmetro `?bpm=127` pré-carrega o valor

---

### Phase 7: Histórico e Estatísticas do Membro
**Goal:** Cada músico e o líder conseguem ver o histórico de participação em escalas e a taxa de presença.
**Mode:** mvp

**Requirements:** MEM-01, MEM-02, MEM-03

**Success Criteria:**
1. Perfil do músico exibe lista das últimas 10 escalas com status (confirmou/faltou)
2. Porcentagem de presença (ex: "8/10 escalas — 80%") visível no card do membro
3. Líder vê ranking de presença na página de membros (ordenável)

**Plans:**
- Plan 7A: Query de histórico de escalas por membro + UI em `admin/membro_detalhe.php`
- Plan 7B: Cálculo e exibição de taxa de presença no card do membro
- Plan 7C: Ranking de presença em `admin/membros.php`

---

### Phase 8: Devocional+
**Goal:** Devocional tem streak de leitura visível e um versículo/hino da semana na home do músico.
**Mode:** mvp

**Requirements:** DEV-01, DEV-02

**Success Criteria:**
1. Home do músico exibe versículo/hino da semana (definido pelo líder como aviso especial)
2. Devocional exibe streak atual de dias consecutivos lidos com ícone de chama

**Plans:**
- Plan 8A: Streak de leitura — calcular dias consecutivos e exibir na `admin/leitura.php`
- Plan 8B: Versículo da semana na home — aviso com tag especial ou campo dedicado

---

### Phase 9: Deploy Final
**Goal:** App atualizado em produção no Hostinger com PWA e HTTPS verificados.
**Mode:** mvp

**Requirements:** PWA-01, PWA-02, PWA-03

**Success Criteria:**
1. `vilela.eng.br/applouvor` carrega a versão com todas as features do milestone
2. Service worker atualizado (cache bust + nova versão)
3. HTTPS ativo, manifest e install prompt funcionando em iOS e Android

**Plans:**
- Plan 9A: Preparar deploy (atualizar versão, cache bust do SW, checar .htaccess)
- Plan 9B: Upload via FTP/Hostinger e verificação pós-deploy
- Plan 9C: Testes de PWA install em iOS e Android + verificar push notifications

---

## Dependencies

```
Phase 1 (Git) → todas as outras (base limpa)
Phase 2 (Confirmar) → Phase 4 (Faltas) — status 'absent' usa mesma coluna
Phase 3 (Roteiro) → independente
Phase 4 (Faltas) → Phase 7 (Histórico) — alimenta estatísticas
Phase 5 (Música) → independente
Phase 6 (Metrônomo) → independente
Phase 7 (Histórico) → depende de Phase 4
Phase 8 (Devocional) → independente
Phase 9 (Deploy) → todas as fases anteriores
```

---
*Criado: 2026-05-16 | v1.0*
