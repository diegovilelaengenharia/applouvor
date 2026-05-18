# Phase 8 Context — Devocional+

## Goal
Devocional tem streak de leitura visível, versículo/hino da semana, e orações de intercessão da equipe.

## Requirements
- DEV-01: Home do músico exibe versículo/hino da semana + streak de leitura
- DEV-02: Líder posta pedidos de oração semanais; equipe visualiza na home

## Estado Atual

**Já existia (antes da Phase 8):**
- `admin/oracao.php` já está implementado com CRUD completo (create/pray/comment/answered)
- `admin/leitura.php` tem UI de streak — mas variável `$currentStreak = 0` era hardcoded
- `admin/avisos.php` tem campo `type` (geral/espiritual/eventos/musica/importante/urgente)
- `admin/index.php` é a home (admin & músico)

**O que faltava (entregue na Phase 8):**
1. Cálculo real do streak em `leitura.php` (walking back from current plan day)
2. Tipo `versiculo` no enum de avisos
3. Widget "Versículo da Semana" no dashboard (lê aviso mais recente type='versiculo')
4. Widget "Orando juntos" no dashboard (3 pedidos de oração mais recentes, não respondidos)

## Schema relevante

`avisos(id, title, message, priority, type, target_audience, expires_at, created_by, created_at)`
- priority: normal/important/urgent
- type: geral/versiculo/espiritual/eventos/musica/importante/urgente

`prayer_requests(id, user_id, title, description, category, is_urgent, is_anonymous, prayer_count, is_answered, answered_at, created_at)`

`reading_progress(id, user_id, month_num, day_num, verses_read, comment, note_title)`
- verses_read: JSON array com índices das passagens lidas
- Streak: walk back do plan_day atual contando consecutivos com count(verses_read) > 0
