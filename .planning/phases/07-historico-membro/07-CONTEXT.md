# Phase 7 Context — Histórico e Estatísticas do Membro

## Goal
Cada músico e o líder veem o histórico de participação em escalas, taxa de presença, ranking de presença (admin only) e alerta pastoral discreto quando a presença cai.

## Requirements
- MEM-01: Histórico das escalas com status (✅ ENTREGUE NA PHASE 4)
- MEM-02: Taxa de presença visível no card do membro (✅ ENTREGUE NA PHASE 4 — em membro_detalhe.php)
- MEM-03: Ranking de presença em `admin/membros.php` (admin only) + alerta pastoral

## Estado Atual

**Já entregue (Phase 4):**
- `admin/membro_detalhe.php` tem breakdown de presença (Presente/Faltou/Justificou/Taxa)
- `presence_status` e `absence_note` exibidos por escala
- Variáveis `$totalPresente`, `$totalFaltou`, `$totalJustificou`, `$taxaPresenca` calculadas

**O que falta:**
1. Taxa de presença visível em cada card de membro em `admin/membros.php` (admin only)
2. Ordenação por taxa de presença (admin only)
3. Alerta pastoral em `membro_detalhe.php` quando últimas 4 escalas têm < 60% presença — visível só para admin

## Regras Pastorais

- Alerta deve ser **cuidadoso, não punitivo** — "pode precisar de uma conversa" e não "este membro está faltando muito"
- Visível só para admin (não para o próprio músico — evita constrangimento)
- Threshold: ≥ 2 ausências nas últimas 4 escalas E taxa < 60%
- Ícone gentil (alert-circle âmbar, não red alarm)

## Query para Ranking

```sql
SELECT u.id, u.name,
       COUNT(su.schedule_id) as total_escalas,
       SUM(CASE WHEN su.status IN ('confirmed','pending') THEN 1 ELSE 0 END) as presentes,
       ROUND(SUM(CASE WHEN su.status IN ('confirmed','pending') THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(su.schedule_id), 0)) as taxa
FROM users u
LEFT JOIN schedule_users su ON u.id = su.user_id
LEFT JOIN schedules s ON s.id = su.schedule_id AND s.event_date < CURDATE()
GROUP BY u.id
```

## Threshold de Ranking (cores)

- ≥ 80% → verde (#10b981)
- 60–79% → azul (#3b82f6)
- 40–59% → âmbar (#f59e0b)
- < 40% → vermelho suave (#ef4444)
- Sem escalas (NULL) → cinza (sem badge)
