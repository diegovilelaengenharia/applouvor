<?php
// admin/registrar_faltas.php — Registro de presenças e faltas pós-culto (admin only)
require_once '../includes/db.php';
require_once '../includes/layout.php';
checkAdmin();

$scheduleId = (int)($_GET['id'] ?? 0);
if (!$scheduleId) {
    header('Location: escalas.php');
    exit;
}

// Buscar escala — deve ser do passado
$stmtSched = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmtSched->execute([$scheduleId]);
$schedule = $stmtSched->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: escalas.php');
    exit;
}

// Garantir que é uma escala passada
if ($schedule['event_date'] >= date('Y-m-d')) {
    header('Location: escalas.php?erro=escala_futura');
    exit;
}

// Buscar participantes da escala
$stmtUsers = $pdo->prepare("
    SELECT su.user_id, su.status, su.absence_note, su.instrument as assigned_instrument,
           u.name, u.instrument as default_instrument, u.avatar_color
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    WHERE su.schedule_id = ?
    ORDER BY u.name ASC
");
$stmtUsers->execute([$scheduleId]);
$participants = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

if (empty($participants)) {
    header('Location: escala_detalhe.php?id=' . $scheduleId);
    exit;
}

$eventDate = new DateTime($schedule['event_date']);
$dateFormatted = $eventDate->format('d/m/Y');
$dayName = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][$eventDate->format('w')];

renderAppHeader('Registrar Faltas', 'escalas.php');
?>
<link rel="stylesheet" href="../assets/css/pages/registrar_faltas.css">

<div class="page-container">

  <div class="schedule-info-card pib-card">
    <div class="schedule-info-header">
      <span class="event-type-badge"><?= htmlspecialchars($schedule['event_type']) ?></span>
      <span class="event-date"><?= $dayName ?>, <?= $dateFormatted ?></span>
    </div>
    <p class="schedule-info-hint">Marque o status de presença de cada participante. Isso alimenta o histórico pastoral da equipe.</p>
  </div>

  <div id="feedback-msg" class="feedback-msg" style="display:none;"></div>

  <div class="participants-list" id="participants-list">
    <?php foreach ($participants as $p): ?>
    <?php
      $statusClass = in_array($p['status'], ['confirmed','absent','absent_justified']) ? $p['status'] : 'pending';
      $instrument = $p['assigned_instrument'] ?: $p['default_instrument'];
    ?>
    <div class="participant-card pib-card" data-user-id="<?= (int)$p['user_id'] ?>">
      <div class="participant-info">
        <div class="participant-avatar" style="background:<?= htmlspecialchars($p['avatar_color'] ?: '#3B82F6') ?>">
          <?= mb_substr(htmlspecialchars($p['name']), 0, 1) ?>
        </div>
        <div class="participant-details">
          <span class="participant-name"><?= htmlspecialchars($p['name']) ?></span>
          <?php if ($instrument): ?>
          <span class="participant-instrument"><?= htmlspecialchars($instrument) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="status-toggle" role="group" aria-label="Status de <?= htmlspecialchars($p['name']) ?>">
        <button type="button"
                class="toggle-btn <?= ($statusClass === 'confirmed' || $statusClass === 'pending') ? 'active' : '' ?>"
                data-status="confirmed"
                aria-pressed="<?= ($statusClass === 'confirmed' || $statusClass === 'pending') ? 'true' : 'false' ?>">
          ✅ Presente
        </button>
        <button type="button"
                class="toggle-btn <?= $statusClass === 'absent' ? 'active absent' : '' ?>"
                data-status="absent"
                aria-pressed="<?= $statusClass === 'absent' ? 'true' : 'false' ?>">
          ❌ Faltou
        </button>
        <button type="button"
                class="toggle-btn <?= $statusClass === 'absent_justified' ? 'active justified' : '' ?>"
                data-status="absent_justified"
                aria-pressed="<?= $statusClass === 'absent_justified' ? 'true' : 'false' ?>">
          ⚠️ Justificou
        </button>
      </div>

      <div class="absence-note-field" style="<?= in_array($statusClass, ['absent','absent_justified']) ? '' : 'display:none' ?>">
        <input type="text"
               class="absence-note-input"
               placeholder="Motivo (opcional, só você vê)"
               maxlength="255"
               value="<?= htmlspecialchars($p['absence_note'] ?? '') ?>">
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="save-footer">
    <button id="btn-save" class="btn-save-primary" onclick="saveAbsences()">
      Salvar Registro
    </button>
    <a href="escalas.php" class="btn-cancel">Cancelar</a>
  </div>

</div>

<script>
const SCHEDULE_ID = <?= (int)$scheduleId ?>;

// Toggle de status por card
document.querySelectorAll('.status-toggle').forEach(group => {
  group.addEventListener('click', e => {
    const btn = e.target.closest('.toggle-btn');
    if (!btn) return;

    const card = btn.closest('.participant-card');
    const newStatus = btn.dataset.status;

    // Atualizar botões ativos
    group.querySelectorAll('.toggle-btn').forEach(b => {
      b.classList.remove('active', 'absent', 'justified');
      b.setAttribute('aria-pressed', 'false');
    });
    btn.classList.add('active');
    if (newStatus === 'absent') btn.classList.add('absent');
    if (newStatus === 'absent_justified') btn.classList.add('justified');
    btn.setAttribute('aria-pressed', 'true');

    // Mostrar/ocultar campo de nota
    const noteField = card.querySelector('.absence-note-field');
    if (newStatus === 'absent' || newStatus === 'absent_justified') {
      noteField.style.display = '';
      noteField.querySelector('input').focus();
    } else {
      noteField.style.display = 'none';
    }
  });
});

async function saveAbsences() {
  const btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  const participants = [];
  document.querySelectorAll('.participant-card').forEach(card => {
    const userId = parseInt(card.dataset.userId);
    const activeBtn = card.querySelector('.toggle-btn.active');
    const status = activeBtn ? activeBtn.dataset.status : 'confirmed';
    const note = card.querySelector('.absence-note-input')?.value?.trim() || null;
    participants.push({ user_id: userId, status, note });
  });

  try {
    const res = await fetch('../api/save_absences.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ schedule_id: SCHEDULE_ID, participants })
    });
    const data = await res.json();

    const msg = document.getElementById('feedback-msg');
    if (data.success) {
      msg.className = 'feedback-msg success';
      msg.textContent = 'Registro salvo com sucesso!';
      msg.style.display = '';
      btn.textContent = 'Salvo ✓';
      setTimeout(() => { window.location.href = 'escalas.php'; }, 1200);
    } else {
      msg.className = 'feedback-msg error';
      msg.textContent = data.message || 'Erro ao salvar.';
      msg.style.display = '';
      btn.disabled = false;
      btn.textContent = 'Salvar Registro';
    }
  } catch (err) {
    const msg = document.getElementById('feedback-msg');
    msg.className = 'feedback-msg error';
    msg.textContent = 'Erro de conexão. Tente novamente.';
    msg.style.display = '';
    btn.disabled = false;
    btn.textContent = 'Salvar Registro';
  }
}
</script>

<?php renderAppFooter(); ?>
