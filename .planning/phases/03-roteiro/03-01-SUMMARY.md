---
plan: 03-01
phase: 03-roteiro
status: completed
completed_at: 2026-05-17
---

# Plan 03-01 Summary — Migration + API

## Deliverables

- `database/migrations/003_schedule_roteiro.sql` — tabela schedule_roteiro com ENUM(6 tipos), FKs para schedules e songs, índice composto em (schedule_id, order_position)
- `api/roteiro.php` — CRUD completo: GET lista com JOIN songs (nota_interna filtrada para músicos), POST add/delete/reorder (admin-only 403)

## Decisions

- `nota_interna` filtrada no GET para non-admin (null no array antes de json_encode) — server-side, não depende do cliente
- `item_type` validado contra whitelist de 6 valores em action=add — valor inválido cai em 'livre'
- `song_id` validado contra tabela songs antes de inserir — ID inválido resulta em song_id=null
- `nextPos = MAX(order_position) + 1` — appends ao final, sem gaps iniciais
- `rowCount()` em action=delete — detecta item inexistente sem query extra
