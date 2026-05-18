# Phase 5 Context — Música Modernizada

## Goal
Página de detalhe da música tem visual moderno com links de streaming como cards com identidade de plataforma, tom/BPM em destaque, músico pode sugerir músicas com fila de aprovação, e setlist da escala tem versão para impressão/compartilhamento.

## Requirements
- MUS-01: Links de streaming como cards visuais com ícone de plataforma (Spotify, YouTube, Cifra Club, Letras)
- MUS-02: Tom, BPM e Duração em cards destacados (não texto inline)
- MUS-03: Músico pode sugerir música — líder aprova/rejeita
- MUS-04: Setlist de uma escala exportável/compartilhável

## Estado Atual — O que JÁ EXISTE

**Quase completo:**
- `admin/musica_detalhe.php` — tem tabs (Visão Geral, Tons, Referências), cards de stats, link cards genéricos
- `admin/sugerir_musica.php` — formulário de sugestão para músico ✅
- `admin/sugestoes_musicas.php` — fila de aprovação para admin ✅
- `admin/sugestoes_api.php` — API completa (create/approve/reject/count_pending) ✅
- `admin/init_db_suggestions.php` — auto-cria tabela `song_suggestions` ✅

**O que falta (trabalho real):**
1. Link cards em `musica_detalhe.php` usam nomes genéricos — precisam de branding de plataforma
2. Dashboard badge "X sugestões pendentes" usa tabela errada (avisos, não song_suggestions)
3. `admin/escala_setlist.php` não existe
4. `admin/repertorio.php` não mostra "última vez tocada" na listagem

## Banco de Dados

`song_suggestions(id, user_id, title, artist, tone, youtube_link, spotify_link, reason, status, reviewed_by, reviewed_at, created_at)`

`schedule_songs(id, schedule_id, song_id, position)` — JOIN com `schedules(event_date)` para stats

## Detecção de Plataforma

Mapear URLs para plataformas em `musica_detalhe.php`:
- `link_letra` → Letras.mus.br (azul índigo)
- `link_cifra` → Cifra Club (laranja #f97316)
- `link_audio` → detectar: se URL contém "spotify" → Spotify (verde #1db954); senão "Áudio"
- `link_video` → detectar: se URL contém "youtube" ou "youtu.be" → YouTube (vermelho #ff0000); senão "Vídeo"

## Setlist para Impressão

`admin/escala_setlist.php?id={schedule_id}` — página limpa com:
- Título da escala (tipo + data)
- Lista numerada de músicas (título, artista, tom, BPM)
- CSS de impressão (`@media print`)
- Link de compartilhamento via Web Share API (mobile) ou copiar URL
