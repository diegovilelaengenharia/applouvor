# Requirements — App Louvor PIB Oliveira

*Milestone 1: Modernização + Features Faltantes*

---

## v1 Requirements

### GIT — Limpeza e Organização

- [ ] **GIT-01**: Estado atual (33 arquivos modificados) commitado de forma organizada com mensagens semânticas
- [ ] **GIT-02**: `desktop.ini` removido do tracking git e adicionado ao `.gitignore`
- [ ] **GIT-03**: Scripts de manutenção organizados em `maintenance/` e commitados

### ESCALA — Confirmação pelo Músico

- [ ] **ESC-01**: Músico visualiza botão "Confirmar" / "Recusar" na tela de detalhe da sua escala
- [ ] **ESC-02**: Músico confirma ou recusa presença via toggle/botão com feedback visual imediato (AJAX)
- [ ] **ESC-03**: Status de confirmação (confirmado/pendente/recusado) visível no card de cada participante
- [x] **ESC-04**: Líder vê quantos confirmaram vs pendente vs recusaram na lista de escalas *(entregue em 02-03, 2026-05-17)*
- [ ] **ESC-05**: Notificação push automática 2 dias antes da escala para músicos que não confirmaram

### ESCALA — Roteiro de Culto

- [ ] **ROT-01**: Roteiro de culto como seção dentro da escala (itens ordenáveis)
- [ ] **ROT-02**: Tipos de item no roteiro: Música, Oração, Palavra, Anúncio, Intervalo, Livre
- [ ] **ROT-03**: Líder cria e edita itens do roteiro (drag-and-drop ou setas para reordenar)
- [ ] **ROT-04**: Músico visualiza roteiro completo na sua view (read-only)
- [ ] **ROT-05**: Tom customizado por músicana escala (pode diferir do tom padrão do repertório)

### ESCALA — Registro de Faltas

- [ ] **FAL-01**: Líder acessa tela "Registrar faltas" dentro de uma escala passada
- [ ] **FAL-02**: Lista de participantes com toggle "faltou" por pessoa
- [ ] **FAL-03**: Salvar registro de faltas persiste no banco (`schedule_users.status = 'absent'`)
- [ ] **FAL-04**: Histórico de faltas refletido nas estatísticas do membro

### MÚSICA — Detalhe Moderno

- [ ] **MUS-01**: Página de detalhe da música exibe links de streaming como cards visuais (ícone + nome do serviço)
- [ ] **MUS-02**: Tom, BPM e duração exibidos em cards destacados (não só texto)
- [ ] **MUS-03**: Músico pode sugerir uma música diretamente da tela de repertório (formulário simples)
- [ ] **MUS-04**: Setlist de uma escala exportável/compartilhável (página de impressão ou compartilhamento)

### METRÔNOMO — Melhorias

- [ ] **MET-01**: Tap BPM funcional (múltiplos toques calculam a média do BPM)
- [ ] **MET-02**: Slider vertical para ajustar BPM com precisão (como no LouveApp)
- [ ] **MET-03**: BPM da escala atual disponível no metrônomo (pré-carrega BPM da música selecionada)

### MEMBRO — Histórico e Estatísticas

- [ ] **MEM-01**: Página de perfil do músico exibe histórico de escalas participadas
- [ ] **MEM-02**: Porcentagem de presença (confirmadas / escalas totais) visível no perfil
- [ ] **MEM-03**: Líder vê ranking de presença dos membros (quem mais participa)

### DEVOCIONAL — Melhorias

- [ ] **DEV-01**: Versículo/hino da semana na home do músico (curado pelo líder nos avisos)
- [ ] **DEV-02**: Streak de dias lidos no devocional (motivação de leitura contínua)

### PWA — Infraestrutura e Deploy

- [ ] **PWA-01**: HTTPS verificado em produção (vilela.eng.br/applouvor)
- [ ] **PWA-02**: Service worker atualizado para versão atual do app
- [ ] **PWA-03**: Deploy pós-milestone: arquivos atualizados no Hostinger

### UI — Harmonização Visual (Phase 10)

- [ ] **UI-01**: Escala tipográfica única via tokens do design-system (base 16px, secundário 14px, títulos proporcionais) — sem font-size/family rígidos nos CSS de página
- [ ] **UI-02**: Botões padronizados em tamanho e estados (altura mínima de toque 44px, raio 8–12px, hierarquia primário/secundário/ghost consistente)
- [ ] **UI-03**: Espaçamentos e densidade padronizados por tokens (paddings de página, gaps, `.pib-card` uniforme) — visual harmônico em desktop e mobile
- [ ] **UI-04**: Auditoria visual de todas as páginas (admin + músico) confirmando consistência em 375px e desktop, sem regressões funcionais

### REACT — Migração do Painel (Phase 11)

- [ ] **REA-01**: Projeto React configurado com Vite, TypeScript e Tailwind CSS v4 na subpasta `/dashboard` gerando build estático otimizado
- [ ] **REA-02**: Layout autoral premium criado em React (Sidebar, TopBar unificado, Dark Mode reativo e Auth Context para cookies de sessão PHP)
- [ ] **REA-03**: Dashboard Administrativo principal migrado para React (`admin/index.php` -> React) exibindo os dados em tempo real da API `api/admin/dashboard_data.php`
- [ ] **REA-04**: Transições de página instantâneas e animações Spring de alta qualidade em todos os cliques e hovers

---

## v2 Requirements (próximo milestone)

- Lembrete por WhatsApp (deep link) para escala
- Estatísticas avançadas de repertório (músicas mais tocadas por período)
- Tela de perfil público do músico para a congregação
- Integração plena com Google Calendar (já existe OAuth, falta sincronização automática)
- Orações da equipe — feed de pedidos de oração com reações

---

## Out of Scope

- Chat em tempo real — WhatsApp já cobre, custo de infra alto
- App nativo iOS/Android — PWA é suficiente
- Integração com API do Spotify/Deezer — links diretos são suficientes
- Multi-ministério (multi-tenant) — fora do escopo
- Letras de músicas inline — direitos autorais
- Gestão financeira — fora do ministério de louvor

---

## Traceability

| REQ-ID | Phase | Status |
|--------|-------|--------|
| GIT-01..03 | Phase 1 | — |
| ESC-01..05 | Phase 2 | — |
| ROT-01..05 | Phase 3 | — |
| FAL-01..04 | Phase 4 | — |
| MUS-01..04 | Phase 5 | — |
| MET-01..03 | Phase 6 | — |
| MEM-01..03 | Phase 7 | — |
| DEV-01..02 | Phase 8 | — |
| PWA-01..03 | Phase 9 | — |
| UI-01..04 | Phase 10 | — |
| REA-01..04 | Phase 11 | — |

---
*Gerado: 2026-05-16 | Milestone 1 — Modernização & Features Faltantes*
