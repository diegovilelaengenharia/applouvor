---
phase: 02-confirmar-escala
plan: 02B
type: execute
wave: 2
depends_on: [02A]
files_modified:
  - admin/escala_detalhe.php
  - assets/css/pages/detail_v3.css
autonomous: true
requirements:
  - ESC-03

must_haves:
  truths:
    - "escala_detalhe.php usa $member['status'] para calcular $statusClass (nao $member['is_confirmed'])"
    - "Cada card de participante exibe badge de status: Confirmado / Pendente / Recusado"
    - "detail_v3.css contem .status-confirmed, .status-pending, .status-declined"
    - "Badge de status e visivel ao lado do nome ou abaixo do avatar em todos os participantes"
  artifacts:
    - path: "admin/escala_detalhe.php"
      provides: "Badges de status nos cards de participantes"
  key_links:
    - from: "escala_detalhe.php team-list-grid"
      to: "status badge HTML"
      via: "$member['status']"
      pattern: "status-confirmed|status-pending|status-declined"
---

<objective>
Corrigir e aprimorar a exibicao de status dos participantes em `admin/escala_detalhe.php`.

Problema atual: linha ~355 usa `$member['is_confirmed']` que nao existe na query — o calculo de $statusClass esta bugado. Corrigir para usar `$member['status']` diretamente.

Adicionar badge de texto visivel (nao so o indicador circular) para cada participante: "Confirmado", "Pendente" ou "Recusado". Adicionar CSS completo para as tres classes de status.

Output: Cada card de participante mostra claramente o status de confirmacao (ESC-03).
</objective>

<context>
@.planning/phases/02-confirmar-escala/02-CONTEXT.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Adicionar CSS das classes de status em detail_v3.css</name>
  <files>
    assets/css/pages/detail_v3.css
  </files>
  <read_first>
    Ler `assets/css/pages/detail_v3.css` e buscar por `.status-indicator`.
    Verificar se ja existem estilos para `.status-confirmed`, `.status-pending`, `.status-declined`.
    Verificar estilos existentes do `.member-card` e `.status-indicator` para entender o contexto visual.
  </read_first>
  <action>
    Adicionar/substituir no `detail_v3.css` as seguintes regras (adicionar apos o bloco do CONFIRM FOOTER
    adicionado pelo plano 02A, ou apos `.status-indicator` se ja existir esse seletor):

    ```css
    /* ===== STATUS INDICATOR (Phase 2) ===== */
    .status-indicator {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid var(--bg-surface, #fff);
    }

    .status-confirmed  { background-color: #22C55E; } /* green-500 */
    .status-pending    { background-color: #F59E0B; } /* amber-500 */
    .status-declined   { background-color: #EF4444; } /* red-500 */

    /* Badge de texto de status no card do participante */
    .member-status-badge {
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 999px;
        display: inline-block;
        margin-top: 2px;
        white-space: nowrap;
    }

    .member-status-badge.badge-confirmed {
        background: #DCFCE7;
        color: #16A34A;
    }

    .member-status-badge.badge-pending {
        background: #FEF3C7;
        color: #D97706;
    }

    .member-status-badge.badge-declined {
        background: #FEE2E2;
        color: #DC2626;
    }
    ```
  </action>
  <acceptance_criteria>
    - `detail_v3.css` contem `.status-confirmed` com `background-color: #22C55E`
    - `detail_v3.css` contem `.status-pending` com `background-color: #F59E0B`
    - `detail_v3.css` contem `.status-declined` com `background-color: #EF4444`
    - `detail_v3.css` contem `.member-status-badge`
    - `detail_v3.css` contem `.badge-confirmed`
    - `detail_v3.css` contem `.badge-pending`
    - `detail_v3.css` contem `.badge-declined`
  </acceptance_criteria>
  <done>CSS das classes de status adicionado em detail_v3.css.</done>
</task>

<task type="auto">
  <name>Task 2: Corrigir $statusClass e adicionar badge visivel em escala_detalhe.php</name>
  <files>
    admin/escala_detalhe.php
  </files>
  <read_first>
    Ler `admin/escala_detalhe.php` e localizar exatamente:

    1. O loop `foreach($team as $member):` na secao PARTICIPANTS SECTION (~linha 354)
    2. A linha com `$statusClass = 'status-' . ($member['is_confirmed']...` (~linha 355) — ISSO E O BUG
    3. A div `.member-card` e seu conteudo (.member-avatar, .status-indicator, .member-info, .member-name, .member-role)
    4. Verificar que `$member['status']` esta disponivel na query (SELECT su.* inclui status ✓)

    NAO alterar o loop do edit mode (modais).
  </read_first>
  <action>
    CORRECAO 1 — Substituir o calculo bugado de $statusClass (~linha 355):

    ANTES:
    ```php
    $statusClass = 'status-' . ($member['is_confirmed'] ? 'confirmed' : ($member['status'] == 'declined' ? 'declined' : 'pending'));
    ```

    DEPOIS:
    ```php
    $memberStatus = $member['status'] ?? 'pending';
    if (!in_array($memberStatus, ['confirmed', 'declined', 'absent'])) $memberStatus = 'pending';
    $displayStatus = ($memberStatus === 'absent') ? 'declined' : $memberStatus;
    $statusClass = 'status-' . $displayStatus;
    $statusLabels = ['confirmed' => 'Confirmado', 'pending' => 'Pendente', 'declined' => 'Recusado'];
    $statusLabel = $statusLabels[$displayStatus] ?? 'Pendente';
    ```

    CORRECAO 2 — Adicionar badge de texto no `.member-info` div.
    Localizar o bloco:
    ```php
    <div class="member-info">
        <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
        <div class="member-role"><?= htmlspecialchars($instr) ?></div>
    </div>
    ```

    Substituir por:
    ```php
    <div class="member-info">
        <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
        <div class="member-role"><?= htmlspecialchars($instr) ?></div>
        <span class="member-status-badge badge-<?= $displayStatus ?>">
            <?= $statusLabel ?>
        </span>
    </div>
    ```
  </action>
  <acceptance_criteria>
    - `admin/escala_detalhe.php` NAO contem `$member['is_confirmed']` (o bug foi removido)
    - `admin/escala_detalhe.php` contem `$memberStatus = $member['status'] ?? 'pending'`
    - `admin/escala_detalhe.php` contem `$displayStatus`
    - `admin/escala_detalhe.php` contem `member-status-badge badge-`
    - `admin/escala_detalhe.php` contem `$statusLabel`
    - O bloco `.member-info` contem o `<span class="member-status-badge`
  </acceptance_criteria>
  <done>$statusClass corrigido, badge de texto de status adicionado em cada card de participante.</done>
</task>

</tasks>

<verification>
Testar manualmente com php -S localhost:8080:

1. Abrir admin/escala_detalhe.php?id=X (qualquer escala com participantes)
2. Cada participante deve mostrar um badge colorido: "Confirmado" (verde), "Pendente" (amarelo) ou "Recusado" (vermelho)
3. O indicador circular (.status-indicator) deve ter a cor correta
4. Confirmar a propria presenca via footer (plano 02A) e recarregar — o badge deve atualizar para "Confirmado"
5. Nao deve haver erros PHP no log (especialmente sobre $member['is_confirmed'])
</verification>

<success_criteria>
- ESC-03: Status de confirmacao visivel em cada card de participante ✓
- Bug de $member['is_confirmed'] corrigido ✓
- 'absent' mapeado para 'declined' visualmente (correto — absent e para faltas registradas pelo admin, nao pelo musico) ✓
</success_criteria>
