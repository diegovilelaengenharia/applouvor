# APP Louvor

> ## 🔄 RECONSTRUÇÃO 2026-07 (ciclo v7)
> Histórico v1→v6 centralizado em `c:\vilela\applouvor-historico\`. Novo ciclo: skill
> **`vilela-gsd`**, fase atual `fases/FASE-00-PLANO.md`. As **Decisões Chave abaixo seguem
> travadas** (norte LouveApp, ChordPro próprio, stage mode, sem SaaS pago, PHP puro na
> Hostinger). Decisões NOVAS do ciclo v7 (escopo do reset, arquitetura de dados) serão
> registradas aqui na FASE 00.

## O que é isto
Aplicativo web móvel (PWA) projetado para a gestão e comunicação interna do Ministério de Louvor da Primeira Igreja Batista em Oliveira/MG. O aplicativo atende a dois públicos: os líderes (painel administrativo de escalas, membros e repertório) e os músicos (acesso a cifras, devocionais, escalas e avisos).

## Valor Principal
Simplificar e centralizar a gestão de escalas e repertórios do ministério, garantindo que os músicos tenham acesso imediato e fácil a todas as informações e recursos de que precisam para o culto.

## Current Milestone: v1.0 MVP Operacional

**Goal:** MVP funcional de escalas e repertório para a PIB Oliveira com layout Sacred Minimalist e deploy na Hostinger.

**Target features:**
- Estrutura base MVC com Front Controller e Autoloader PSR-4.
- Login seguro com senhas criptografadas, rate limiting e CSRF.
- Visual Sacred Minimalist dark-first móvel integrado ao PWA instalável.
- Painel líder para criação de escalas e designação de músicos.
- Agenda do músico com visualização de repertório e respostas de presença.
- Cadastro e acesso de cifras no aplicativo móvel.

## Requisitos

### Validados

(Nenhum ainda — entregue para validar)

### Ativos

- [ ] Configuração do ambiente local de banco de dados e servidor web PHP.
- [ ] Implementação de um Front Controller em PHP Puro para gerenciar as rotas.
- [ ] Implementação de um autoloader PSR-4 para carregamento automático de classes.
- [ ] Tela de login e autenticação segura com senhas criptografadas e controle de acessos (admin vs músico).
- [ ] Layout mobile-first baseado no Design System "Sacred Minimalist".
- [ ] PWA instalável com Service Worker funcional.

### Fora de Escopo

- [ ] Uso de frameworks PHP pesados (Laravel, Symfony) — para garantir compatibilidade total e leveza na Hostinger.
- [ ] Processo de compilação/build complexo (npm, webpack) no deploy de produção — o código deve rodar diretamente como PHP/JS/CSS puros na Hostinger.

## Contexto
O projeto é uma reconstrução completa do zero, motivada pela necessidade de simplificar e limpar o código da versão 2, que havia se tornado excessivamente complexo e instável. A Hostinger será a plataforma de hospedagem de produção e o GitHub será usado para versionamento.

## Restrições

- **Tecnologia**: PHP 8.0+ / MySQL (PDO) / Vanilla JS e CSS.
- **Hospedagem**: Hostinger (limitações de hospedagem compartilhada, exigindo que o deploy seja feito sem builds complexos).
- **Mobile-First**: A interface deve ser 100% otimizada para telefones celulares (viewport de 375px), rodando como PWA instalável.
- **Segurança**: Senhas criptografadas (bcrypt), proteção contra ataques CSRF e rate limiting no login.

## Decisões Chave

| Decisão | Justificativa | Resultado |
|---------|---------------|-----------|
| PHP Puro com MVC manual | Facilidade de deploy na Hostinger e controle total da stack | — Pendente |
| Uso do GSD (Get Shit Done) | Metodologia estruturada para garantir a qualidade de cada entrega e evitar bagunça no código | — Pendente |
| Norte de produto = LouveApp, mas superá-lo | Mesmo público; ir além em cifras, modo palco e inteligência de escala/repertório | ✓ Travada (2026-06-05) |
| Sem dependências externas (IA paga/SaaS) | Inteligência via algoritmos próprios em PHP; custo zero e 100% offline-capaz (Web Push nativo/VAPID é permitido) | ✓ Travada (2026-06-05) |
| Modo culto = stage mode individual | Cada músico controla a própria tela; sem servidor de tempo real na Hostinger | ✓ Travada (2026-06-05) |
| Cifras importadas do Cifra Club | Diego já baixa cifras de lá; colar/importar texto → ChordPro próprio + transposição | ✓ Travada (2026-06-05) |
| Fechar o MVP e publicar antes dos diferenciais | Validar cedo com a equipe; diferenciais (v2.0+) vêm depois do app no ar | ✓ Travada (2026-06-05) |

> **Visão de produto completa, milestones (v1.0→v5.0) e os 5 diferenciais:** ver `.planning/ROADMAP.md`.

## Evolução

Este documento evolui nas transições de fase e marcos.

**Após cada transição de fase** (via `/gsd-transition`):
1. Requisitos invalidados? → Mover para Fora de Escopo com justificativa.
2. Requisitos validados? → Mover para Validados com referência da fase.
3. Novos requisitos surgiram? → Adicionar aos Ativos.
4. Decisões para registrar? → Adicionar às Decisões Chave.
5. "O que é isto" ainda é preciso? → Atualizar se houver desvio.

**Após cada marco** (via `/gsd-complete-milestone`):
1. Revisão completa de todas as seções.
2. Verificação do Valor Principal — ainda é a prioridade certa?
3. Auditoria do Fora de Escopo — os motivos ainda são válidos?
4. Atualizar Contexto com o estado atual.

---
*Última atualização: 2026-06-05 após inicialização*
