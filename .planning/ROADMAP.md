# Roadmap: APP Louvor Novíssimo

## Visão Geral
Este roadmap guiará o desenvolvimento do APP Louvor Novíssimo do zero absoluto até a entrega de um sistema funcional (MVP) de escalas e repertório para a PIB Oliveira. Cada fase foi delimitada de forma que, ao final de sua execução, tenhamos entregas tangíveis e testáveis, focando no design Sacred Minimalist mobile-first e na facilidade de deploy na Hostinger.

---

## Fases

- [ ] **Phase 1: Arquitetura Core** — Configuração da estrutura básica de pastas, roteador Front Controller, autoloader e conexão de banco de dados.
- [ ] **Phase 2: Autenticação** — Sistema de login seguro com bcrypt, controle de sessões, proteção CSRF e rate limiting contra força bruta.
- [ ] **Phase 3: Design System e Base Visual** — Estilos globais mobile-first do Design System "Sacred Minimalist" e integração do PWA instalável.
- [ ] **Phase 4: Gestão de Escalas** — Criação de cultos, designação de membros e aceitação/justificativa de escalas por parte dos músicos.
- [ ] **Phase 5: Repertório e Músicas** — Gestão de músicas (cifras, BPM, links de vídeo/áudio), sugestões e acoplamento de repertório nos roteiros de cultos.

---

## Detalhes das Fases

### Phase 1: Arquitetura Core
**Objetivo**: Estabelecer a infraestrutura básica do projeto que servirá de alicerce para todas as demais fases.
**Depende de**: Nada (fase inicial)
**Requisitos**: [BASE-01, BASE-02, BASE-03]
**Critérios de Sucesso**:
  1. O servidor local (`php -S localhost:8080`) roda e serve páginas HTML/PHP dinâmicas usando roteamento amigável (`router.php`).
  2. Classes PHP criadas sob `src/` são resolvidas e carregadas automaticamente pelo Autoloader PSR-4 sem a necessidade de múltiplos `require_once`.
  3. A classe de banco de dados (`App\DB`) conecta-se com sucesso ao banco MySQL usando as variáveis de ambiente locais do arquivo `.env`.
**Planos**: 2 planos

Planos:
- [ ] 01-01: Configuração do autoloader, roteador e do Front Controller (index.php, router.php, .htaccess).
- [ ] 01-02: Criação da classe de acesso ao banco de dados e importação da base de dados local (`database/schema.sql`).

---

### Phase 2: Autenticação
**Objetivo**: Implementar a segurança de login, persistência de sessão e proteção contra as principais vulnerabilidades da web (ataques CSRF e força bruta).
**Depende de**: Phase 1
**Requisitos**: [AUTH-01, AUTH-02, AUTH-03, AUTH-04, AUTH-05]
**Critérios de Sucesso**:
  1. O usuário comum e o administrador conseguem autenticar-se na tela de login.
  2. Tentativas excessivas de login no mesmo IP são bloqueadas temporariamente por rate limiting.
  3. Requisições POST/PATCH/DELETE falham se o token CSRF não for enviado ou se for inválido.
**Planos**: 2 planos

Planos:
- [ ] 02-01: Criação dos helpers de login, sessão, CSRF, rate limit e controller da tela de login.
- [ ] 02-02: Criação de rotas protegidas (Middlewares de autenticação para restringir acesso a páginas de administração).

---

### Phase 3: Design System e Base Visual
**Objetivo**: Estabelecer a identidade visual Sacred Minimalist mobile-first (para celular de 375px) e tornar o aplicativo instalável nativamente como PWA.
**Depende de**: Phase 2
**Requisitos**: [UI-01, UI-02, UI-03]
**Critérios de Sucesso**:
  1. As páginas renderizam de forma fluida no celular usando variáveis CSS e design system consistente (cores HSL, cantos arredondados, toque mínimo de 48px).
  2. O navegador identifica o app como PWA instalável através do `manifest.json` e Service Worker funcional.
  3. O suporte a tema escuro funciona via classe `.dark` no `<html>` baseado nas preferências do aparelho.
**Planos**: 2 planos

Planos:
- [ ] 03-01: Configuração de variáveis CSS do Design System, componentes (cards, botões, formulários) e base layout.
- [ ] 03-02: Configuração do manifest.json, Service Worker (`sw.js`) e fluxos de instalação Android/iOS.

---

### Phase 4: Gestão de Escalas
**Objetivo**: Implementar o core operacional do ministério de louvor: criação de cultos e escalas trimestrais e designação de músicos.
**Depende de**: Phase 3
**Requisitos**: [SCHED-01, SCHED-02, SCHED-03, SCHED-04]
**Critérios de Sucesso**:
  1. O administrador consegue criar, editar e excluir uma escala de culto com músicos escalados para instrumentos específicos.
  2. O músico consegue ver suas próprias escalas no dashboard e marcar sua presença como "Confirmada" ou "Recusada" (com justificativa por escrito).
**Planos**: 2 planos

Planos:
- [ ] 04-01: Criação da área administrativa de criação de escalas, designação de membros e instrumentos.
- [ ] 04-02: Criação da tela de escala do músico para resposta de presença e visualização de sua agenda.

---

### Phase 5: Repertório e Músicas
**Objetivo**: Lógica de gerenciamento das músicas do ministério, cifras e sua acoplagem aos roteiros dos cultos.
**Depende de**: Phase 4
**Requisitos**: [SONG-01, SONG-02, SONG-03, SONG-04]
**Critérios de Sucesso**:
  1. Líder consegue cadastrar músicas informando tom, BPM, links de vídeo/áudio e anexos de cifras.
  2. Líder consegue vincular músicas e definir sua ordem de apresentação no roteiro do culto.
  3. Músicos conseguem acessar as cifras e links de áudio/vídeo da música escalada na tela de detalhes do culto.
**Planos**: 2 planos

Planos:
- [ ] 05-01: Implementação da área de cadastro de músicas, busca inteligente por tags e sugestões de membros.
- [ ] 05-02: Criação do gerenciador de roteiro do culto (vincular músicas a escalas) e tela de cifras para o músico.

---

## Progresso

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Arquitetura Core | 0/2 | Not started | - |
| 2. Autenticação | 0/2 | Not started | - |
| 3. Design System e Base Visual | 0/2 | Not started | - |
| 4. Gestão de Escalas | 0/2 | Not started | - |
| 5. Repertório e Músicas | 0/2 | Not started | - |
