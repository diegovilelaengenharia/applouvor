-- Migration 004: Ampliar schedule_users para suportar registro de faltas
-- Adiciona 'absent' (faltou sem aviso) e 'absent_justified' (justificou com antecedência)
-- ao ENUM de status. Peso pastoral distinto: absent_justified não conta como abandono
-- pois o membro avisou com antecedência e demonstrou comprometimento com a equipe.
-- absence_note: nota interna opcional do líder sobre o motivo da ausência.

ALTER TABLE schedule_users
  MODIFY COLUMN status ENUM('pending','confirmed','declined','absent','absent_justified')
    NOT NULL DEFAULT 'pending';

ALTER TABLE schedule_users
  ADD COLUMN absence_note TEXT NULL
    COMMENT 'Nota interna do líder sobre a ausência. Visível apenas para admin.';
