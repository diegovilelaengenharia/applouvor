# APP Louvor Novíssimo

## O que é isto
Aplicativo web móvel (PWA) projetado para a gestão e comunicação interna do Ministério de Louvor da Primeira Igreja Batista em Oliveira/MG. O aplicativo atende a dois públicos: os líderes (painel administrativo de escalas, membros e repertório) e os músicos (acesso a cifras, devocionais, escalas e avisos).

## Valor Principal
Simplificar e centralizar a gestão de escalas e repertórios do ministério, garantindo que os músicos tenham acesso imediato e fácil a todas as informações e recursos de que precisam para o culto.

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
