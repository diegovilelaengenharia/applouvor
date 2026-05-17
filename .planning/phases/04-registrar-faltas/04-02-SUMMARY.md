---
phase: 04-registrar-faltas
plan: "02"
status: done
completed_at: 2026-05-17
---

# Summary — Plan 04-02: API save_absences

## O que foi feito

Criado `api/save_absences.php` — endpoint POST admin-only que persiste status de presença/falta para participantes de uma escala passada.

## Arquivo criado

- `api/save_absences.php` (107 linhas)

## Validações implementadas

| Validação | Implementação |
|-----------|---------------|
| Auth (401) | `$_SESSION['user_id'] ?? 0` — retorna 401 se ausente |
| Admin-only (403) | `$userRole !== 'admin'` — retorna 403 para usuários comuns |
| Método POST (405) | `$_SERVER['REQUEST_METHOD'] !== 'POST'` |
| Escala existe | SELECT no `schedules` por id |
| Escala no passado | `event_date >= date('Y-m-d')` retorna erro |
| user_id pertence à escala | `in_array($pUserId, $validUserIds)` — skip com erro se inválido |
| Status whitelist | 5 valores válidos: `confirmed, declined, pending, absent, absent_justified` |
| absence_note limpa | `null` para status não-ausência |
| absence_note truncada | `substr($note, 0, 500)` |
| PDO prepared statements | Sem concatenação de strings SQL em nenhum ponto |

## Acceptance criteria — verificação

- [x] `php -l api/save_absences.php` → "No syntax errors detected"
- [x] 401 para sessão ausente
- [x] 403 para usuário não-admin
- [x] Valida `event_date < date('Y-m-d')` — rejeita escalas futuras
- [x] Valida cada user_id contra `$validUserIds` antes de UPDATE
- [x] Whitelist de 5 status — fallback 'confirmed' para status inválido
- [x] absence_note é `null` para status != absent/absent_justified
- [x] absence_note truncada a 500 chars
- [x] Retorna `{success: true, updated: N}` com contagem
- [x] Erros PDO não são expostos (apenas "Erro ao atualizar user_id X")

## Commit

`feat(04): add save_absences API — admin-only, validates past schedule + user whitelist`

## Próximo passo

Plan 04-03 — integrar o formulário `admin/registrar_faltas.php` (04-01) com este endpoint via fetch POST.
