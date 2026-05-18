# Plan 06-02 — SUMMARY

**Phase:** 06-metronomo-pro
**Requirements:** MET-03
**Commit:** f042ed1
**Date:** 2026-05-17

## Entregue

4 arquivos modificados para integração do metrônomo no ecossistema do app:

1. **`includes/dashboard_cards.php`** — novo card 'metronomo'
   - Categoria: gestao
   - Icon: `timer` (lucide)
   - Cor: azul #2563EB / bg #DBEAFE
   - URL: `metronomo.php`
   - `admin_only: false` (todos podem usar)

2. **`admin/index.php`** — 'metronomo' adicionado ao array `$groupedCards['gestao']` (último da lista)

3. **`admin/musica_detalhe.php`** — link "Abrir no metrônomo — X BPM" após o info-grid-row (Tom/BPM/Duração):
   - Renderizado apenas se `!empty($song['bpm'])`
   - SVG inline + texto azul, padding 10×16, border azul
   - BPM injetado como `(int)$song['bpm']` (sanitizado)

4. **`sw.js`** — cache offline:
   - `/admin/metronomo.php` adicionado em `urlsToCache`
   - `CACHE_NAME` bumped: `louvor-pib-v2.2.0` → `louvor-pib-v2.3.0` (força refresh)

## Verificação
- `php -l` em todos os 4 arquivos → No syntax errors detected

## Resultado
- Card "Metrônomo" aparece no dashboard para todos os usuários (categoria Gestão)
- Páginas de música com BPM cadastrado têm botão direto para abrir no metrônomo já com o BPM pré-carregado
- Página `metronomo.php` será cacheada pelo Service Worker — disponível offline durante ensaio com sinal fraco
