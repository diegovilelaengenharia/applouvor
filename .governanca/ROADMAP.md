# Roadmap: APP Louvor

> ## 🔄 RECONSTRUÇÃO 2026-07 (ciclo v7)
> Novo ciclo pela skill **`vilela-gsd`**. FASE 00 (infra) fechada 2026-07-15. Fase atual:
> **FASE 01 — Arquitetura Core** (`fases/FASE-01-PLANO.md`), executada e verificada localmente,
> aguardando OK do Diego pro push (push = deploy produção). Fases futuras existem só como
> títulos até a anterior fechar. A **visão de produto abaixo continua válida** (decisões
> travadas 2026-06-05: superar o LouveApp em cifras/palco/inteligência, sem SaaS pago) — o que
> muda é o RITMO: infra primeiro, uma fase por vez, verificação por execução. Milestones
> v1.0–v5.0 abaixo = referência do ciclo v6 (a numeração de fases do v7 é independente, começa
> na FASE 00 de infra).

---

# [Arquivo — ciclo v6] Roadmap: APP Louvor Novíssimo

## Visão de Produto

**Norte:** o LouveApp (`app.louveapp.com.br`) — mas indo **além** dele onde mais importa: cifras de verdade, modo palco e inteligência de escala/repertório.

De "app de gestão de escalas" para **plataforma do ministério de louvor**, servindo a equipe nos três tempos:

- **Antes** → planejar e ensaiar com inteligência (auto-escala justa, sugestão de repertório, ensaio guiado)
- **Durante** → o culto (modo palco individual, cifras com transposição, roteiro do culto)
- **Depois** → histórico, cuidado pastoral e vida devocional

### Decisões de produto (travadas com o Diego em 2026-06-05)

| Decisão | Escolha |
|---------|---------|
| Inspiração / IA de informação | LouveApp como norte; superar em cifras/palco/inteligência |
| Dependências externas (IA paga, SaaS) | **Não.** Inteligência via algoritmos próprios em PHP. (Web Push nativo/VAPID é permitido — não é SaaS) |
| Modo culto ao vivo | **Stage mode individual** — cada músico controla a própria tela (sem servidor de tempo real) |
| Cifras | **Importar do Cifra Club** (colar/baixar texto → parse para ChordPro) + transposição própria |
| Sequência | **Fechar o MVP e publicar primeiro**, depois os diferenciais |

### Os 5 diferenciais (o "muito melhor")

1. **Cifras de verdade** — ChordPro, transposição de tom com 1 toque, capo, auto-scroll por BPM, modo escuro de palco. (LouveApp só linka pro Cifra Club.)
2. **Modo Palco individual** — tela de apresentação por músico, offline, fonte grande, tom transposto pessoal.
3. **Inteligência sem nuvem** — auto-escalação balanceada e sugestão de setlist por regras (variedade de tom/BPM, rotação justa, não repetir nas últimas N), tudo em PHP.
4. **Cuidado pastoral por dados** — alertas discretos de queda de presença, rotação justa, saúde da equipe.
5. **Offline-first de verdade** — escala e cifra da próxima escala sempre disponíveis, mesmo sem sinal no templo.

---

# Milestone v1.0 — MVP Operacional  *(em andamento)*

**Goal:** MVP funcional de escalas e repertório no ar na Hostinger. 
*Aviso: O design de **todas as 53 telas** já foi gerado no Stitch via Claude Code. As fases abaixo irão focar na absorção imediata desse HTML para o PHP MVC, acelerando drasticamente o backend e entregas.*

| # | Fase | Goal | Status |
|---|------|------|--------|
| 1 | Arquitetura Core | Front Controller, autoloader PSR-4, DB | ✅ Concluída (2026-06-05) |
| 2 | Autenticação | Login bcrypt + CSRF + rate limit + sessão | ✅ Concluída (2026-06-05) |
| 3 | Design System & PWA | Tokens Sacred Minimalist, componentes, manifest, SW | ✅ Concluída (2026-06-05) |
| 3.5 | Shell de Navegação & Layout Global | Casca do app (nav, header, perfil) que todas as telas herdam | ✅ Concluída (2026-06-05) |
| 4 | **Gestão de Escalas** | Criar escala, designar músicos, confirmar/recusar | ⬜ **NEXT UP** |
| 5 | Repertório & Cifras | CRUD músicas, cifras importadas do Cifra Club, vincular ao roteiro | ⬜ |
| 6 | Deploy & UAT | Produção na Hostinger + testes de aceitação | ⬜ |

### Phase 3.5: Shell de Navegação & Layout Global  ⬜ NEXT UP
**Goal:** Criar a casca de navegação que dá ao app a "cara de app" do LouveApp e que todas as telas herdam — eliminando telas soltas desde o início.
**Mode:** ui · **Depende de:** Phase 3

**Estado atual:** `dashboard.php` e `login.php` são telas isoladas, sem layout compartilhado nem navegação.

**Success Criteria:**
1. Layout global reutilizável (`src/layout/`) com `<head>` (fontes, tokens, PWA), header e slot de conteúdo — todas as views passam a usá-lo.
2. **Navegação mobile-first**: bottom-nav fixa (Início · Escalas · Repertório · Perfil) com alvos ≥ 44px e safe-area; menu "mais" para itens secundários (Avisos, Indisponibilidades, Metrônomo, Configurações).
3. Header com nome do ministério + sino de notificações + acesso ao perfil (estilo LouveApp).
4. Dark mode coerente em todo o shell via `.dark`; estados ativos de navegação corretos por rota.
5. Dashboard (Início) reconstruído dentro do shell, com cards reais (próxima escala, aviso) no lugar do placeholder de status atual.

**Plans (est.):**
- 3.5A: `src/layout/layout.php` + `head.php` + header global + integração nas views existentes
- 3.5B: Bottom-nav + menu "mais" + estados ativos por rota + dark mode
- 3.5C: Dashboard/Início dentro do shell (cards próxima escala + aviso + atalhos)

### Phase 4: Gestão de Escalas
**Goal:** Núcleo operacional — líder cria escalas; músico vê e confirma. Utiliza os HTMLs extraídos das telas 03, 04, 05 e 06 do Stitch.
**Mode:** mvp · **Depende de:** Phase 3.5 · **Requisitos:** SCHED-01..04

**Success Criteria:**
1. Líder cria/edita/exclui/**clona** escala (data, hora, tipo de culto) e designa músicos a funções/instrumentos.
2. Lista de escalas com abas **Próximas / Anteriores**, card com data, participantes (avatares) e contadores (X/Y confirmados, nº músicas).
3. Detalhe da escala: bloco de data, participantes com função/instrumento, roteiro, e ações (confirmar/recusar, registrar faltas).
4. Músico confirma/recusa presença sem reload (AJAX + CSRF); badge de status 🟢🟡🔴 por participante.

**Plans (est.):**
- 4A: Área admin — criar/editar/clonar escala + designar membros e funções
- 4B: Lista de escalas (Próximas/Anteriores) + detalhe + contador de confirmados
- 4C: Confirmação/recusa do músico (AJAX) + registrar faltas (toggles) + badges

### Phase 5: Repertório & Cifras
**Goal:** Gestão de músicas com Tom/BPM/links importando as telas prontas (07 a 10) do Stitch.
**Mode:** mvp · **Depende de:** Phase 4 · **Requisitos:** SONG-01..04

**Success Criteria:**
1. CRUD de música: capa, artista, Tom, BPM, duração, classificação e links (Spotify/YouTube/Cifra Club/Letras/Áudio).
2. Repertório com abas **Músicas / Artistas** e busca por nome/artista/tag; detalhe da música com Tom/BPM/Duração em cards + referências.
3. **Importar cifra do Cifra Club**: colar o texto baixado → salvar como cifra da música (base ChordPro vem na v2.0).
4. Líder vincula músicas ao roteiro do culto (ordem + tom customizado por escala); músico acessa cifra/links na tela da escala.

**Plans (est.):**
- 5A: CRUD de música + detalhe (Tom/BPM/Duração + links) + busca/tags
- 5B: Importar/colar cifra (texto) + armazenamento + visualização
- 5C: Roteiro do culto — vincular músicas à escala (ordem + tom) + acesso do músico

### Phase 6: Deploy & UAT
**Goal:** App publicado em produção na Hostinger sob HTTPS, com PWA instalável e todos os fluxos do MVP validados pelo Diego.
**Mode:** mvp · **Depende de:** Phase 5

**Success Criteria:**
1. `vilela.eng.br/applouvor` (ou domínio escolhido) servindo a versão MVP sob HTTPS.
2. Importação do banco em produção + smoke test de login/escala/repertório.
3. PWA instalável testado em iOS e Android; SW com cache versionado.
4. Roteiro de UAT executado e assinado pelo Diego.

**Plans (est.):**
- 6A: Deploy Hostinger + import do banco + verificação pós-deploy + UAT

---

# Milestone v2.0 — Culto ao Vivo & Cifras Pro

**Goal:** Resolver o domingo. Cifras inteligentes e modo palco que fazem o músico tocar com segurança — o maior diferencial sobre o LouveApp.

| # | Fase | Goal |
|---|------|------|
| 7 | Cifras Inteligentes (ChordPro) | Parse de acordes, transposição de tom, capo, fonte ajustável |
| 8 | Importador Cifra Club Pro | Texto baixado do Cifra Club → ChordPro estruturado (acordes sobre a letra) |
| 9 | Modo Palco | Tela de apresentação individual: tom pessoal, auto-scroll por BPM, modo escuro, offline |
| 10 | Roteiro do Culto Pro | Fluxo completo (músicas + momentos: oração/palavra/anúncio), reorder, nota interna do líder |
| 11 | Setlist & Compartilhamento | Versão imprimível/PDF/share do setlist e do roteiro |

---

# Milestone v3.0 — Inteligência do Ministério  *(algoritmos PHP, sem nuvem)*

**Goal:** O líder trabalha menos. Auto-escala justa e sugestão de repertório por regras, mais relatórios que viram cuidado pastoral.

| # | Fase | Goal |
|---|------|------|
| 12 | Auto-escalação Balanceada | Sugerir escala respeitando disponibilidade, rotação justa e cobertura de funções/instrumentos |
| 13 | Sugestão de Repertório | Setlist equilibrada por regras: variedade de tom/BPM/momento, "não repetir nas últimas N", transição harmônica |
| 14 | Modelos de Roteiro & Planejamento de Funções | Templates reutilizáveis de roteiro; escalar por função, não só por pessoa |
| 15 | Relatórios & Cuidado Pastoral | KPIs de presença, ranking (admin), gráficos SVG, alertas discretos de queda < 60% |

---

# Milestone v4.0 — Comunidade & Vida Espiritual

**Goal:** A equipe conectada e cuidada — o módulo "Ministério" do LouveApp, mais a vida devocional.
*(Nota: Telas 24 a 32 já geradas no Stitch. A integração será adiantada devido à facilidade do frontend pronto).*

| # | Fase | Goal |
|---|------|------|
| 16 | Ministério & Membros | Equipes, funções, classificações, permissões, convites, aniversariantes, perfil completo |
| 17 | Indisponibilidades | Músico marca ausências; alimenta a auto-escalação (Phase 12) |
| 18 | Avisos & Push Inteligente | Avisos com prioridade/reações + Web Push nativo (VAPID local) na publicação da escala e lembretes |
| 19 | Mensagens | Mural/chat simples da equipe |
| 20 | Devocional+, Leitura & Oração | Planos de leitura, streak, pedidos de intercessão; Metrônomo Pro (Tap BPM + áudio + offline) |

---

# Milestone v5.0 — Profissionalização & Confiança

**Goal:** App sólido, seguro, rápido e testado para os ~12 membros usarem sem sustos.

| # | Fase | Goal |
|---|------|------|
| 21 | Segurança Avançada | Security headers, session hardening, validação de upload, auditoria |
| 22 | Performance & PWA | Lighthouse ≥ 85, offline essencial (escala + cifra + metrônomo), imagens otimizadas |
| 23 | Acessibilidade WCAG AA | Toque ≥ 44px, contraste, labels, navegação por teclado |
| 24 | Testes E2E (Playwright) | Cobertura dos fluxos críticos: login, escala, confirmação, metrônomo |
| 25 | Observabilidade & Onboarding | error_log + alertas; guia do músico e do líder |

---

## Progresso

| Phase | Plans | Status | Completed |
|-------|-------|--------|-----------|
| 1. Arquitetura Core | 2/2 | ✅ Complete | 2026-06-05 |
| 2. Autenticação | 2/2 | ✅ Complete | 2026-06-05 |
| 3. Design System & PWA | 2/2 | ✅ Complete | 2026-06-05 |
| 3.5. Shell de Navegação | 3/3 | ✅ Complete | 2026-06-05 |
| 4. Gestão de Escalas | 3/3 | ✅ Complete | 2026-06-05 |
| 5. Repertório & Cifras | 0/3 | ⬜ Next up | - |
| 6. Deploy & UAT | 0/1 | ⬜ Not started | - |
| 7–25 (v2.0–v5.0) | — | ⬜ Planejado | - |

---
*Roadmap consolidado em 2026-06-05 — decisões travadas + IA inspirada no LouveApp. Milestones v2.0+ são direção estratégica; o detalhamento (success criteria + plans finais) de cada fase acontece no `/gsd-discuss-phase` + `/gsd-plan-phase` na hora de executá-la.*
