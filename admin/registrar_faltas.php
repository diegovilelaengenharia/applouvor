<?php
// admin/registrar_faltas.php — Registro de presenças e faltas pós-culto (admin only)
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
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

<style>
    .bento-card-stat {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-card-stat {
        background: var(--bg-surface, #1A1B1F);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .toggle-btn.active.btn-confirmed {
        background-color: #10B981 !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
    }
    .toggle-btn.active.btn-absent {
        background-color: #EF4444 !important;
        color: white !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    }
    .toggle-btn.active.btn-justified {
        background-color: #FFC107 !important;
        color: #1E293B !important;
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.15);
    }
</style>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-4 mb-24 font-hanken">
    
    <!-- Hero Header de Culto com gradiente premium -->
    <div class="bg-gradient-to-br from-worship-blue/10 to-altar-gold/5 dark:from-worship-blue/5 dark:to-altar-gold/5 border border-worship-blue/20 rounded-2xl p-6 mb-8">
        <div class="flex flex-wrap items-center gap-2.5 mb-3">
            <span class="bg-worship-blue text-white text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                <?= htmlspecialchars($schedule['event_type']) ?>
            </span>
            <span class="bg-altar-gold/10 text-altar-gold border border-altar-gold/20 text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                <?= $dayName ?>, <?= $dateFormatted ?>
            </span>
        </div>
        <h2 class="text-xl md:text-2xl font-bold text-on-background tracking-tight">Registro de Presenças</h2>
        <p class="text-xs md:text-sm text-secondary mt-1">
            Marque a presença de cada participante para atualizar o histórico pastoral e relatórios estatísticos da equipe.
        </p>
    </div>

    <!-- Feedback Message Box -->
    <div id="feedback-msg" class="hidden p-4 rounded-xl mb-6 text-sm font-semibold border" style="display:none;"></div>

    <!-- Lista de Participantes em Bento Grid Tátil -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="participants-list">
        <?php foreach ($participants as $p): ?>
        <?php
            $statusClass = in_array($p['status'], ['confirmed','absent','absent_justified']) ? $p['status'] : 'pending';
            $instrument = $p['assigned_instrument'] ?: $p['default_instrument'];
        ?>
        <div class="participant-card bento-card-stat rounded-2xl p-5 flex flex-col justify-between gap-4 bg-white dark:bg-deep-navy relative" data-user-id="<?= (int)$p['user_id'] ?>">
            
            <div class="flex items-center gap-3">
                <!-- User Avatar Custom -->
                <div class="w-11 h-11 rounded-full text-white font-extrabold text-sm flex items-center justify-center shrink-0 shadow-sm" style="background-color: <?= htmlspecialchars($p['avatar_color'] ?: '#3B82F6') ?>;">
                    <?= mb_substr(htmlspecialchars($p['name']), 0, 1) ?>
                </div>
                
                <div class="min-w-0">
                    <h3 class="font-bold text-sm text-on-background truncate"><?= htmlspecialchars($p['name']) ?></h3>
                    <?php if ($instrument): ?>
                        <span class="text-[10px] text-secondary mt-0.5 block truncate"><?= htmlspecialchars($instrument) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Toggles táteis de presença -->
            <div class="status-toggle flex rounded-xl border border-outline-variant/30 bg-ghost-gray/40 dark:bg-surface-variant/10 p-1 w-full gap-1" role="group" aria-label="Status de <?= htmlspecialchars($p['name']) ?>">
                <button type="button"
                        class="toggle-btn btn-confirmed px-2.5 py-2 rounded-lg text-[10px] font-extrabold tracking-wide uppercase transition-all duration-150 text-center flex-1 active:scale-[0.97] <?= ($statusClass === 'confirmed' || $statusClass === 'pending') ? 'active bg-emerald-500 text-white' : 'bg-transparent text-secondary hover:text-emerald-500' ?>"
                        data-status="confirmed"
                        aria-pressed="<?= ($statusClass === 'confirmed' || $statusClass === 'pending') ? 'true' : 'false' ?>">
                    Presente
                </button>
                <button type="button"
                        class="toggle-btn btn-absent px-2.5 py-2 rounded-lg text-[10px] font-extrabold tracking-wide uppercase transition-all duration-150 text-center flex-1 active:scale-[0.97] <?= $statusClass === 'absent' ? 'active bg-rose-500 text-white' : 'bg-transparent text-secondary hover:text-rose-500' ?>"
                        data-status="absent"
                        aria-pressed="<?= $statusClass === 'absent' ? 'true' : 'false' ?>">
                    Faltou
                </button>
                <button type="button"
                        class="toggle-btn btn-justified px-2.5 py-2 rounded-lg text-[10px] font-extrabold tracking-wide uppercase transition-all duration-150 text-center flex-1 active:scale-[0.97] <?= $statusClass === 'absent_justified' ? 'active bg-altar-gold text-slate-900' : 'bg-transparent text-secondary hover:text-altar-gold' ?>"
                        data-status="absent_justified"
                        aria-pressed="<?= $statusClass === 'absent_justified' ? 'true' : 'false' ?>">
                    Justificou
                </button>
            </div>

            <!-- Campo dinâmico de observação de falta -->
            <div class="absence-note-field w-full mt-1 transition-all duration-300" style="<?= in_array($statusClass, ['absent','absent_justified']) ? '' : 'display:none' ?>">
                <input type="text"
                       class="absence-note-input w-full bg-ghost-gray/20 dark:bg-surface-variant/5 border border-outline-variant/30 text-on-background px-3 py-2.5 rounded-xl text-[11px] focus:border-worship-blue focus:ring-1 focus:ring-worship-blue/15 outline-none transition-all placeholder:text-secondary/50"
                       placeholder="Motivo (opcional)"
                       maxlength="255"
                       value="<?= htmlspecialchars($p['absence_note'] ?? '') ?>">
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Botões de Ação Inferiores -->
    <div class="flex items-center justify-end gap-3 border-t border-outline-variant/20 pt-6">
        <a href="escalas.php" class="bg-ghost-gray hover:bg-outline-variant/20 dark:bg-surface-variant/10 text-on-background px-6 py-3 rounded-full text-xs font-bold uppercase tracking-wider transition-all">
            Cancelar
        </a>
        <button id="btn-save" class="bg-worship-blue hover:brightness-110 active:scale-[0.98] transition-all text-white px-7 py-3 rounded-full text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-1.5 shadow-md" onclick="saveAbsences()">
            Salvar Registro
        </button>
    </div>

</main>

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
      b.classList.remove('active', 'bg-emerald-500', 'bg-rose-500', 'bg-altar-gold', 'text-white', 'text-slate-900');
      b.classList.add('bg-transparent', 'text-secondary');
      b.setAttribute('aria-pressed', 'false');
    });
    
    btn.classList.add('active');
    btn.classList.remove('bg-transparent', 'text-secondary');
    
    if (newStatus === 'confirmed') {
        btn.classList.add('bg-emerald-500', 'text-white');
    } else if (newStatus === 'absent') {
        btn.classList.add('bg-rose-500', 'text-white');
    } else if (newStatus === 'absent_justified') {
        btn.classList.add('bg-altar-gold', 'text-slate-900');
    }
    
    btn.setAttribute('aria-pressed', 'true');

    // Mostrar/ocultar campo de nota com animação simples
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

  const msg = document.getElementById('feedback-msg');
  msg.style.display = 'none';

  try {
    const res = await fetch('../api/save_absences.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ schedule_id: SCHEDULE_ID, participants })
    });
    const data = await res.json();

    if (data.success) {
      msg.className = 'p-4 rounded-xl mb-6 text-sm font-semibold border bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
      msg.textContent = 'Registro de presenças atualizado com sucesso!';
      msg.style.display = '';
      btn.textContent = 'Salvo ✓';
      setTimeout(() => { window.location.href = 'escalas.php'; }, 1200);
    } else {
      msg.className = 'p-4 rounded-xl mb-6 text-sm font-semibold border bg-rose-500/10 text-rose-500 border-rose-500/20';
      msg.textContent = data.message || 'Erro ao registrar presenças.';
      msg.style.display = '';
      btn.disabled = false;
      btn.textContent = 'Salvar Registro';
    }
  } catch (err) {
    msg.className = 'p-4 rounded-xl mb-6 text-sm font-semibold border bg-rose-500/10 text-rose-500 border-rose-500/20';
    msg.textContent = 'Falha de comunicação. Por favor, verifique a rede e tente novamente.';
    msg.style.display = '';
    btn.disabled = false;
    btn.textContent = 'Salvar Registro';
  }
}
</script>

<?php renderAppFooter(); ?>
