# Phase 4 Context — Registrar Faltas

## Goal
Após um culto/ensaio, o líder registra quem compareceu e quem faltou. Isso alimenta o histórico de presença. Dois tipos de ausência: `absent` (faltou sem aviso) e `absent_justified` (justificou com antecedência) — peso pastoral diferente.

## Requirements
- FAL-01: Botão "Registrar Faltas" em escalas passadas no dashboard do líder
- FAL-02: Lista de participantes com toggle "faltou / justificou / presente" por pessoa
- FAL-03: Salvamento persiste status no banco (`schedule_users.status`)
- FAL-04: Histórico do membro já reflete as faltas registradas

## Gaps da Auditoria
- Dois estados de ausência: `absent` vs `absent_justified` (peso pastoral diferente no histórico)
- Campo opcional de motivo (texto livre) para o líder anotar internamente

## Dependências do Banco

`schedule_users` já tem coluna `status` com ENUM atual:
- `pending` — ainda não confirmou
- `confirmed` — confirmou presença (Phase 2)
- `declined` — recusou (Phase 2)

**Migration necessária (Plan 04-01):** Adicionar `absent` e `absent_justified` ao ENUM + coluna `absence_note TEXT NULL`.

## Fluxo de Usuário

1. Líder abre `admin/escalas.php` — lista de escalas passadas
2. Cada escala passada tem botão "Registrar Faltas" (admin only)
3. Líder abre `admin/registrar_faltas.php?id={schedule_id}`
4. Vê lista dos participantes escalados com toggle de 3 estados por pessoa
5. Opcionalmente adiciona nota de ausência
6. Salva via `api/save_absences.php`
7. Histórico de presença em `admin/membro_detalhe.php` reflete automaticamente

## Interfaces Críticas

**admin/escalas.php:**
- `$pastSchedules` — array de escalas passadas (já existe)
- Adicionar link "Registrar Faltas" nos cards de escalas passadas, visível só para admin

**schedule_users:**
- `schedule_id`, `user_id`, `status`, `instrument`, `is_rehearsed`
- Após migration: + `absence_note TEXT NULL`

**api/confirm_scale.php:**
- Padrão de API para referência: `header('Content-Type: application/json')`, PDO, sessão

## Design Pattern
Seguir padrão visual estabelecido nas Phases 2 e 3:
- `.pib-card` com shadow
- Botões mínimo 44×44px (mobile-first)
- Cores: verde confirmado, amarelo pendente, vermelho faltou, roxo/cinza justificado
- Toggles de 3 estados: presente ✅ / faltou ❌ / justificou ⚠️
