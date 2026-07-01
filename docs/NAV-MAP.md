# NAV-MAP — Mapa de Navegação & Estrutura do App

> Blueprint da arquitetura de informação do APP Louvor Novíssimo. Define **todas as páginas, botões, ações (voltar/avançar/editar/excluir) e conexões entre telas**. Serve como contrato de UI para a geração no Stitch (via MCP) e para a conversão em PHP (Phase 3.5+).
>
> **Design System (Stitch):** Sacred Minimalist Theme · `assets/18053454826462421656` · DARK · `#2E7EED` · Hanken Grotesk + Public Sans · raio 8px.
> **Projeto Stitch:** `projects/7244459960065792477`.

---

## 1. Princípios de Navegação

- **Mobile-first (390px)**, container `max-w-md`, dark-first.
- **Bottom-nav fixa (4 itens)** presente nas telas-raiz do músico: **Início · Escalas · Repertório · Perfil**. Item ativo em `primary-container`.
- **TopAppBar fixa**: ícone igreja (→ Início) · título "PIB Oliveira" · sino de notificações (→ Notificações).
- **Voltar (←)**: telas de detalhe/formulário têm seta voltar no canto superior esquerdo → retorna à tela-pai (lista de origem).
- **FAB "+"**: telas-lista de criação têm botão flutuante inferior-direito (só admin) → abre formulário de criação.
- **Menu de contexto (⋮)**: detalhes editáveis têm menu superior-direito com **Editar / Clonar / Excluir** (admin).
- **Excluir**: sempre via modal de confirmação ("Tem certeza? Esta ação não pode ser desfeita.") → Confirmar (destrutivo, vermelho) / Cancelar.
- **Salvar/Cancelar**: formulários têm botão Salvar fixo inferior → volta ao detalhe; Cancelar/← descarta → volta sem salvar.
- **Papéis**: `músico` (padrão) e `admin/líder` (vê ações de gestão: criar/editar/excluir, registrar faltas, relatórios, membros).
- **Menu "Mais"**: itens secundários (Avisos, Indisponibilidades, Metrônomo, Mensagens, Configurações) acessíveis via Perfil e/ou um menu "mais" — não poluem a bottom-nav.

---

## 2. Mapa de Telas

Legenda de status Stitch: ✅ gerada · 🔧 em geração · ⬜ pendente

| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 01 | Login | `/login` | público | ✅ `5d559d30` |
| 02 | Dashboard / Início | `/dashboard` | ambos | ✅ `b5702812` |
| 03 | Escalas (lista) | `/escalas` | ambos | ✅ `f4c779a7` |
| 04 | Escala — Detalhe | `/escalas/{id}` | ambos | ✅ `75139f5d` |
| 05 | Escala — Criar/Editar | `/escalas/nova`, `/escalas/{id}/editar` | admin | ✅ `dadea47b` ⚠️dupes `cc24c61a`,`5bc13a69` p/ apagar |
| 06 | Registrar Faltas | `/escalas/{id}/faltas` | admin | ✅ `75912b13` |
| 07 | Repertório (lista) | `/repertorio` | ambos | ✅ `6981dc5f` |
| 08 | Música — Detalhe | `/musicas/{id}` | ambos | ✅ `f3a611e8` |
| 09 | Música — Criar/Editar | `/musicas/nova`, `/musicas/{id}/editar` | admin | ✅ `97711b76` |
| 10 | Cifra (visualização/palco) | `/musicas/{id}/cifra` | ambos | ✅ `bc68ead9` |
| 11 | Perfil | `/perfil` | ambos | ✅ `114f2b0c` |
| 12 | Editar Perfil | `/perfil/editar` | ambos | ✅ `b66db06b` |
| 13 | Configurações | `/configuracoes` | ambos | ✅ `0db3664c` |
| 14 | Avisos (lista) | `/avisos` | ambos | ✅ `c81c0a5a` |
| 15 | Aviso — Detalhe/Criar | `/avisos/{id}`, `/avisos/novo` | ambos/admin | ✅ `d206d171` |
| 16 | Notificações | `/notificacoes` | ambos | ✅ `dc7636d1` (com filtro, editado pelo Diego) |
| 17 | Indisponibilidades | `/indisponibilidades` | ambos | ✅ `26c60ed6` |
| 18 | Metrônomo (com Setlist) | `/metronomo` | ambos | ✅ `efcc2c36` ⚠️dupes `30531c34`,`203abfa2` p/ apagar |
| 19 | Mensagens (mural) | `/mensagens` | ambos | ✅ `fe5a2984` |
| 20 | Membros (lista) | `/membros` | admin | ✅ `c5df171a` |
| 21 | Membro — Detalhe | `/membros/{id}` | admin | ✅ manter 1: `10fc4e0a`(Diego) ou `e77dff54` |
| 22 | Relatórios / Visão Geral | `/relatorios` | admin | ✅ `ebea1382` |
| 23 | Aniversariantes | `/aniversariantes` | ambos | ✅ `3630e54b` |

### Telas adicionais — Vida Espiritual & Liderança (das versões antigas + Milestone v4.0) — A GERAR
| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 24 | Painel do Líder (atalhos admin) | `/lider` | admin | ✅ `7aa58b3d` |
| 25 | Leitura Bíblica (plano + progresso/streak) | `/leitura` | ambos | ✅ `b2875c65` |
| 26 | Leitura — Escolher Plano | `/leitura/planos` | ambos | ✅ `f2f7e523` |
| 27 | Mural de Oração / Intercessão | `/oracao` | ambos | ✅ `d92d2660` |
| 28 | Novo Pedido de Oração | `/oracao/novo` | ambos | ✅ `ede8a323` |
| 29 | Devocionais (lista + streak) | `/devocionais` | ambos | ✅ `b62b17b5` |
| 30 | Devocional — Detalhe | `/devocionais/{id}` | ambos | ✅ `480102e9` |
| 31 | Sugestões de Música (fila + sugerir) | `/sugestoes` | ambos/admin | ✅ `51e4b03f` (+form `925d0e7f`) |
| 32 | Ministério / Quem Somos (equipes, funções) | `/ministerio` | ambos | ✅ `53f090ee` |

### Grupo "Fechamento do app"
| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 33 | Recuperar Senha | `/recuperar-senha` | público | ✅ `0d6d9a50` |

> **Handoff (2026-06-05, sessão 2):** ✅ **53/53 telas geradas no Stitch.** As 20 restantes (34–53) foram geradas com `GEMINI_3_FLASH`, sem retry em timeout, validando com `list_screens`. **Nenhuma duplicata nova** criada nesta sessão (única dupe é a pré-existente "Nova Escala" ×3). Próximo passo sugerido: converter as telas em PHP MVC (Phase 3.5+) e limpar duplicatas/clones na interface web do Stitch. Projeto Stitch `7244459960065792477`, design system `assets/18053454826462421656`.
| 34 | Alterar Senha | `/perfil/senha` | ambos | ✅ `f2d69193` |
| 35 | Preferências de Notificação (por tipo) | `/configuracoes/notificacoes` | ambos | ✅ `03bf3909` |
| 36 | Convidar Membro | `/membros/convidar` | admin | ✅ `5ac6a421` (título "Invite Member") |
| 37 | Onboarding / Guia do Músico | `/onboarding` | ambos | ✅ `a30f5bca` |
| 38 | Ajuda / FAQ | `/ajuda` | ambos | ✅ `1c9ddf5d` |
| 39 | 404 / Offline (PWA) | `/404`, `/offline` | ambos | ✅ `2daa29bc` (Estado Offline) |

### Grupo "Inteligência (algoritmo PHP, sem nuvem)"
| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 40 | Auto-escalação Balanceada (gerar escala) | `/escalas/auto` | admin | ✅ `1b6b9230` |
| 41 | Sugerir Setlist (equilíbrio tom/BPM/rotação) | `/escalas/{id}/setlist-sugerida` | admin | ✅ `b365c356` |
| 42 | Estatísticas de Repertório (drill-down) | `/repertorio/stats` | ambos | ✅ `57dc9502` ⚠️DS clone `cad3119c` (reaplicar `18053454…` se quiser) |

### Grupo "Culto ao Vivo & Ensaio"
| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 43 | Modo Culto ao Vivo (líder conduz, músicos acompanham) | `/escalas/{id}/ao-vivo` | ambos | ✅ `25bae697` |
| 44 | Modo Ensaio (setlist + metrônomo + cifra) | `/escalas/{id}/ensaio` | ambos | ✅ `33203941` |
| 45 | Setlist Imprimir / Compartilhar | `/escalas/{id}/setlist` | ambos | ✅ `0647c160` (Compartilhar Setlist) |

### Grupo "Ministério & Comunidade"
| # | Tela | Rota | Papel | Stitch |
|---|------|------|-------|--------|
| 46 | Equipes | `/ministerio/equipes` | admin | ✅ `c5d5ae0d` |
| 47 | Funções / Classificações | `/ministerio/funcoes` | admin | ✅ `9973e59f` |
| 48 | Modelos de Roteiro | `/ministerio/modelos-roteiro` | admin | ✅ `58a213b1` (+ bônus "Editar Bloco" `69d53448`) |
| 49 | Oração — Detalhe do Pedido | `/oracao/{id}` | ambos | ✅ `992033fd` |
| 50 | Devocional — Comentários | `/devocionais/{id}/comentarios` | ambos | ✅ `2cc2066c` |
| 51 | Agenda / Eventos | `/agenda` | ambos | ✅ `7b2247ae` |
| 52 | Escala de Limpeza / Equipamentos | `/escala-limpeza` | ambos | ✅ `8973177a` (Limpeza & Equipamentos) |
| 53 | Busca Global | `/busca` | ambos | ✅ `ab8be4d2` |

---

## 3. Telas × Botões × Conexões

### 01 · Login `/login`
- **Entrar** → valida → `/dashboard`
- **Esqueceu a senha?** → `/recuperar-senha`
- Suporte (WhatsApp) → link externo

### 02 · Dashboard / Início `/dashboard`
- Card **Próximo Culto**: toque → `/escalas/{id}`; **Confirmar** / **Recusar** (AJAX, atualiza status)
- Card **Aviso da Liderança**: toque → `/avisos/{id}`
- Sino (header) → `/notificacoes`
- Bottom-nav → Escalas / Repertório / Perfil

### 03 · Escalas (lista) `/escalas`
- Abas **Próximas / Anteriores**
- Card de escala → `/escalas/{id}`
- **FAB +** (admin) → `/escalas/nova`
- Filtro/busca (ícone topo)
- ← volta → `/dashboard`

### 04 · Escala — Detalhe `/escalas/{id}`
- Blocos: **Músicas** · **Participantes** (Membros/Funções) · **Roteiro** · **Comentários**
- **Confirmar / Recusar** presença (músico)
- **Registrar faltas** (admin) → `/escalas/{id}/faltas`
- Música no roteiro → `/musicas/{id}`
- **Alterar músicas** (admin) → seletor de repertório
- ⋮ → **Editar** (`/escalas/{id}/editar`) / **Clonar** / **Excluir** (modal confirm)
- ← volta → `/escalas`

### 05 · Escala — Criar/Editar `/escalas/nova`
- Campos: data, hora, tipo de culto, designar membros+funções
- **Salvar** → `/escalas/{id}` · **Cancelar/←** → volta

### 06 · Registrar Faltas `/escalas/{id}/faltas`
- Lista de participantes com toggle presente/faltou/justificou
- **Salvar** → `/escalas/{id}` · **✕/←** → volta

### 07 · Repertório (lista) `/repertorio`
- Abas **Músicas / Artistas / Pastas** · busca
- Música → `/musicas/{id}` · Artista → lista filtrada
- **FAB +** (admin) → `/musicas/nova`
- ← volta → `/dashboard`

### 08 · Música — Detalhe `/musicas/{id}`
- Tom · BPM · Duração (cards) · links (Spotify/YouTube/Cifra Club/Letras)
- **Cifra** → `/musicas/{id}/cifra`
- ⋮ → **Editar** (`/musicas/{id}/editar`) / **Excluir** (admin)
- ← volta → `/repertorio`

### 09 · Música — Criar/Editar `/musicas/nova`
- Campos: título, artista, tom, BPM, duração, links, cifra (colar do Cifra Club)
- **Salvar** → `/musicas/{id}` · **Cancelar/←** → volta

### 10 · Cifra (palco) `/musicas/{id}/cifra`
- Transposição de tom (+/−) · auto-scroll (BPM) · fonte +/− · modo escuro de palco
- ← volta → `/musicas/{id}`

### 11 · Perfil `/perfil`
- Dados, funções/instrumentos, permissões
- **Editar** → `/perfil/editar`
- Atalhos: Indisponibilidades · Metrônomo · Mensagens · **Configurações** (`/configuracoes`)
- **Sair** → `/logout`

### 12 · Editar Perfil `/perfil/editar`
- Nome, e-mail, nascimento, alterar senha, foto
- **Salvar** → `/perfil` · **Cancelar/←** → volta

### 13 · Configurações `/configuracoes`
- Notificações · Tema · Idioma · Suporte · Termos · Privacidade · **Sair**
- ← volta → `/perfil`

### 14–23 · Secundárias
- **Avisos**: lista → detalhe; FAB + (admin) → criar; ← `/dashboard`
- **Notificações**: lista; toque → tela relacionada; ← volta
- **Indisponibilidades**: calendário, marcar/desmarcar datas; **Salvar**; ← `/perfil`
- **Metrônomo**: Tap BPM, play/stop, slider; ← `/perfil`
- **Mensagens**: mural/chat, enviar; ← `/dashboard`
- **Membros** (admin): lista → **Membro Detalhe** (histórico/stats); FAB + convidar; ← `/dashboard`
- **Relatórios** (admin): KPIs + filtros (período/dias/membros); cada card → drill-down; ← `/dashboard`
- **Aniversariantes**: calendário mensal; ← `/dashboard`

---

## 4. Diagrama de Fluxo (núcleo)

```
Login → Dashboard ──┬─→ Escalas ──→ Escala Detalhe ──┬─→ Música Detalhe → Cifra (palco)
                    │      ↑ FAB+        │ ⋮ Editar/Clonar/Excluir
                    │   Criar Escala     └─→ Registrar Faltas
                    │
                    ├─→ Repertório ──→ Música Detalhe ──→ Cifra (palco)
                    │       ↑ FAB+ Criar Música
                    │
                    ├─→ Perfil ──┬─→ Editar Perfil
                    │            ├─→ Configurações → (Sair)
                    │            └─→ Indisponibilidades · Metrônomo · Mensagens
                    │
                    └─→ Notificações (sino) · Avisos (card)
```

---

## 5. Ordem de Geração no Stitch ("aos poucos")

Geração incremental, fluxo a fluxo, sempre com o design system `assets/18053454826462421656`:

1. **Fluxo Escalas** (núcleo MVP): 03 Escalas → 04 Escala Detalhe → 05 Criar Escala → 06 Registrar Faltas
2. **Fluxo Repertório/Cifras**: 07 Repertório → 08 Música Detalhe → 09 Criar Música → 10 Cifra
3. **Fluxo Perfil/Config**: 11 Perfil → 12 Editar Perfil → 13 Configurações
4. **Secundárias**: 14 Avisos · 16 Notificações · 17 Indisponibilidades · 18 Metrônomo · 19 Mensagens
5. **Admin/Analytics**: 20 Membros · 21 Membro Detalhe · 22 Relatórios · 23 Aniversariantes

> Após cada tela gerada: atualizar o status na tabela §2 e baixar o HTML para `.stitch/` (referência de conversão PHP).

### Regras de geração (anti-duplicata — aprendidas em 2026-06-05)
1. **Timeout NÃO é falha.** A geração continua no servidor e quase sempre cria a tela. **Nunca repetir** `generate_screen_from_text` após timeout.
2. Após timeout: aguardar ~60-120s e validar com `list_screens` (a tela aparece com novo ID).
3. **Sempre `list_screens` antes de gerar** — se já existe tela com o mesmo título/função, não gerar de novo.
4. Preferir o modelo **`GEMINI_3_FLASH`** (retorna inline, raramente dá timeout). O PRO costuma estourar o tempo.
5. Stitch MCP **não tem delete** — duplicatas só na interface do Stitch. Logo: evitar criá-las é a única defesa.

---
*Criado em 2026-06-05 — blueprint de navegação (GSD). Evolui conforme as telas são geradas no Stitch e convertidas em PHP.*
