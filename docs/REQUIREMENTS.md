# Requisitos — APP Louvor Novíssimo

**Definido:** 2026-06-05  
**Valor Principal:** Simplificar e centralizar a gestão de escalas e repertórios do ministério, garantindo que os músicos tenham acesso imediato e fácil a todas as informações e recursos de que precisam para o culto.

---

## Requisitos v1 (Escopo Principal)

Estes requisitos representam a base funcional necessária para o aplicativo ser utilizável para a gestão de escalas e músicas.

### Fundação e Rotas (BASE)
- [ ] **BASE-01**: O sistema deve possuir um Front Controller para capturar e rotear todas as requisições usando URLs amigáveis.
- [ ] **BASE-02**: O sistema deve possuir um autoloader automático padrão PSR-4 para carregamento de classes de controle e de modelo.
- [ ] **BASE-03**: O sistema deve ser executável localmente e na hospedagem compartilhada Hostinger sem necessidade de processos de build complexos.

### Autenticação (AUTH)
- [ ] **AUTH-01**: Músicos e administradores podem fazer login usando Usuário e Senha.
- [ ] **AUTH-02**: A senha deve ser criptografada de forma segura usando bcrypt.
- [ ] **AUTH-03**: O sistema deve possuir proteção de rate limiting no login contra ataques de força bruta.
- [ ] **AUTH-04**: O sistema deve possuir proteção contra ataques CSRF em todos os envios de formulário POST/PATCH/DELETE.
- [ ] **AUTH-05**: Usuários autenticados devem ter sessões persistentes no navegador.

### Gestão de Escalas (SCHED)
- [ ] **SCHED-01**: Líderes (admin) podem criar, editar e excluir escalas de cultos contendo data, horário, tipo de evento e observações.
- [ ] **SCHED-02**: Líderes podem designar músicos para instrumentos específicos em uma escala.
- [ ] **SCHED-03**: Músicos podem visualizar a lista de escalas em que estão escalados.
- [ ] **SCHED-04**: Músicos podem confirmar ou recusar presença em uma escala com justificativa.

### Repertório de Músicas (SONG)
- [ ] **SONG-01**: Líderes podem cadastrar músicas informando título, artista, tom, BPM, links (letra, cifra, áudio, vídeo) e observações.
- [ ] **SONG-02**: Líderes podem vincular uma lista de músicas a uma escala de culto específica.
- [ ] **SONG-03**: Músicos podem visualizar os detalhes da música, incluindo cifras e links externos da escala vinculada.
- [ ] **SONG-04**: Músicos podem sugerir novas músicas para o repertório.

### Interface e PWA (UI)
- [ ] **UI-01**: A interface deve ser projetada mobile-first (totalmente otimizada para viewport de 375px) com design clean "Sacred Minimalist".
- [ ] **UI-02**: O aplicativo deve ser instalável em dispositivos móveis Android e iOS através de suporte a PWA (manifest.json e Service Worker).
- [ ] **UI-03**: O aplicativo deve possuir suporte a tema escuro (Dark Mode) respeitando a preferência do dispositivo ou seleção do usuário.

---

## Requisitos v2 (Melhorias Futuras)

Estes requisitos serão adiados para lançamentos futuros para mantermos o escopo inicial focado.

### Painel de Avisos (AVIS)
- **AVIS-01**: Líderes podem criar avisos fixados ou com data de expiração para a equipe de louvor.
- **AVIS-02**: Músicos podem reagir ou comentar nos avisos.

### Devocionais (DEVO)
- **DEVO-01**: Líderes ou administradores podem postar devocionais diários de texto ou vídeo.
- **DEVO-02**: Músicos podem marcar devocionais como lidos e deixar comentários.

### Oração e Espiritual (PRAY)
- **PRAY-01**: Membros da equipe podem postar pedidos de oração (anônimos ou identificados).
- **PRAY-02**: Músicos podem interagir clicando em "Vou Orar" ou deixando comentários de apoio.

### Leitura Bíblica (READ)
- **READ-01**: Usuários podem acompanhar o progresso de leitura bíblica diária através de um plano de leitura integrado.

### Notificações (NOTF)
- **NOTF-01**: Músicos recebem notificações push no celular quando são adicionados a uma escala ou uma escala é alterada.

---

## Fora de Escopo

| Funcionalidade | Motivo da Exclusão |
|----------------|-------------------|
| Chat em tempo real integrado | Aumenta drasticamente a complexidade; o contato via link de WhatsApp é suficiente. |
| Upload direto de vídeos pesados no servidor | A Hostinger possui limite de armazenamento; links para YouTube/Vimeo/Drive devem ser usados. |
| Cadastro e login via redes sociais (OAuth) | A autenticação padrão por usuário/senha é suficiente e simplifica o deploy. |

---

## Rastreabilidade

Relação entre os requisitos v1 e as fases de desenvolvimento do Roadmap.

| Requisito | Fase | Status |
|-----------|------|--------|
| BASE-01 | Fase 1: Arquitetura Core | Pendente |
| BASE-02 | Fase 1: Arquitetura Core | Pendente |
| BASE-03 | Fase 1: Arquitetura Core | Pendente |
| AUTH-01 | Fase 2: Autenticação | Pendente |
| AUTH-02 | Fase 2: Autenticação | Pendente |
| AUTH-03 | Fase 2: Autenticação | Pendente |
| AUTH-04 | Fase 2: Autenticação | Pendente |
| AUTH-05 | Fase 2: Autenticação | Pendente |
| UI-01 | Fase 3: Design System e Base Visual | Pendente |
| UI-02 | Fase 3: Design System e Base Visual | Pendente |
| UI-03 | Fase 3: Design System e Base Visual | Pendente |
| SCHED-01 | Fase 4: Gestão de Escalas | Pendente |
| SCHED-02 | Fase 4: Gestão de Escalas | Pendente |
| SCHED-03 | Fase 4: Gestão de Escalas | Pendente |
| SCHED-04 | Fase 4: Gestão de Escalas | Pendente |
| SONG-01 | Fase 5: Repertório e Músicas | Pendente |
| SONG-02 | Fase 5: Repertório e Músicas | Pendente |
| SONG-03 | Fase 5: Repertório e Músicas | Pendente |
| SONG-04 | Fase 5: Repertório e Músicas | Pendente |

**Cobertura:**
- Requisitos v1: 19 total
- Mapeados para fases: 19
- Não mapeados: 0 ✓

---
*Requisitos definidos: 2026-06-05*  
*Última atualização: 2026-06-05 após definição inicial*
