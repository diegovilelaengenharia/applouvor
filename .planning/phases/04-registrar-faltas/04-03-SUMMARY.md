---
phase: 04-registrar-faltas
plan: "03"
status: done
completed_at: 2026-05-17
commit: aab757d
---

# 04-03 Summary — Integrate presence stats in membro_detalhe

## What was done

Made 4 targeted edits to `admin/membro_detalhe.php` to surface presence data registered by Plans 04-01 and 04-02.

### Edit 1 — Query $stmtHistory
Added `su.status as presence_status` and `su.absence_note` to the SELECT so each schedule row carries its attendance status and optional note.

### Edit 2 — PHP breakdown variables (lines ~72–89)
After the existing frequency calculation, added:
- `$totalEscalas`, `$totalPresente`, `$totalFaltou`, `$totalJustificou`
- `$taxaPresenca` = round(present / total * 100)
- confirmed + pending both count as "presente" (matches FAL-04 intent)

### Edit 3 — HTML stats section (presence breakdown card)
Inserted a `.presence-stats-card` block above the tabs with 4 mini-cards:
- Presente (green #d1fae5)
- Faltou (red #fee2e2)
- Justificou (amber #fef3c7)
- Taxa% (blue, highlighted border)

### Edit 4 — Schedule history loop badges
Inside the `foreach ($schedules as $schedule)` loop, added:
- PHP `match()` expression mapping presence_status to [icon, label, bg, color]
- Inline badge rendered below the date/song count
- `absence_note` shown (with `htmlspecialchars`) when status is absent or absent_justified

## Verification

- `php -l admin/membro_detalhe.php` → No syntax errors detected
- `grep presence_status|absence_note` → present in query (lines 41-42), PHP logic (80, 273), and HTML display (295, 297)
- `grep taxaPresenca|totalFaltou|totalJustificou` → calculated (76-87) and rendered (239, 243, 247)
- `grep htmlspecialchars.*absence_note` → XSS protection present (line 297)

## Acceptance criteria met

- [x] Query includes `su.status as presence_status` and `su.absence_note`
- [x] `$totalPresente`, `$totalFaltou`, `$totalJustificou`, `$taxaPresenca` calculated
- [x] 4-card presence breakdown rendered in HTML
- [x] Badge per schedule (Presente/Faltou/Justificou/Pendente/Recusou) using match()
- [x] `absence_note` displayed with `htmlspecialchars` when status is absent/absent_justified
- [x] Colors consistent with registrar_faltas.php (green/red/amber)
- [x] No PHP syntax errors

## Threats mitigated

- T-04C-01 (XSS on absence_note): `htmlspecialchars()` applied at display point
- T-04C-02 (info disclosure): page is admin-only via existing `checkAdmin()`
