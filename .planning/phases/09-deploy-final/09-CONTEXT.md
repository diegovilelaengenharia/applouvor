# Phase 9 Context — Deploy Final

## Goal
App atualizado em produção (`vilela.eng.br/applouvor`) com PWA verificado, Service Worker versionado e processo de deploy documentado.

## Requirements
- PWA-01: Site carrega com todas as features do milestone (entregue via webhook automático ao longo das fases)
- PWA-02: Service Worker versionado e atualizando com cache bust
- PWA-03: Manifest + install prompt iOS/Android (já existia — `assets/manifest.json`)

## Entregue na Phase 9

1. `includes/config.php`: APP_VERSION bumped 4.1 → 5.0 (marco do Milestone 1)
2. `sw.js`: CACHE_NAME alinhado com APP_VERSION (`louvor-pib-v5.0.0`)
3. `sw.js`: cache expandido para páginas críticas:
   - admin/index.php, escalas.php, repertorio.php, metronomo.php, leitura.php, devocionais.php, oracao.php
4. `DEPLOY.md`: documentação do processo manual + verificações pós-deploy + rollback

## Webhook Hostinger

Configurado em fases anteriores — `git push origin main` dispara `git pull` automaticamente em `/public_html/applouvor/`. Não foi necessário ajustar nesta fase.
