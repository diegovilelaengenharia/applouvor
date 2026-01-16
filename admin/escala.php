<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Processar Nova Escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_scale') {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];

    if ($date && $type) {
        $stmt = $pdo->prepare("INSERT INTO scales (event_date, event_type, description) VALUES (?, ?, ?)");
        $stmt->execute([$date, $type, $description]);
        // Redireciona para gerenciar a escala recém criada
        $newId = $pdo->lastInsertId();
        header("Location: gestao_escala.php?id=$newId");
        exit;
    }
}

// Buscar Escalas
$stmt = $pdo->query("SELECT * FROM scales ORDER BY event_date ASC");
$allScales = $stmt->fetchAll();

// Separar Próximas e Passadas
$today = date('Y-m-d');
$upcoming = [];
$history = [];

foreach ($allScales as $s) {
    if ($s['event_date'] >= $today) {
        $upcoming[] = $s;
    } else {
        $history[] = $s; // Opcional: inverter ordem para histórico
    }
}
// Histórico mais recente primeiro
usort($history, fn($a, $b) => $b['event_date'] <=> $a['event_date']);

renderAppHeader('Escalas');
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">

    <!-- Abas Próximas / Histórico (Simples via JS ou reload, aqui vamos mostrar seções) -->

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h2 style="font-size:1.2rem;">Próximos Cultos</h2>
        <button onclick="document.getElementById('modalNewScale').classList.add('visible')" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">+ Nova</button>
    </div>

    <!-- Lista Próximas -->
    <div class="list-group">
        <?php if (empty($upcoming)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary); background: var(--bg-secondary); border-radius: 12px;">
                <i data-lucide="calendar-off" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                <p>Nenhuma escala agendada.</p>
            </div>
        <?php else: ?>
            <?php foreach ($upcoming as $scale):
                $dateObj = new DateTime($scale['event_date']);
                $isToday = $scale['event_date'] === $today;
            ?>
                <div class="list-item" onclick="window.location.href='gestao_escala.php?id=<?= $scale['id'] ?>'" style="cursor: pointer;">
                    <div style="display:flex; align-items:center; gap: 15px; width:100%;">
                        <!-- Data Box -->
                        <div style="background: <?= $isToday ? 'var(--status-success)' : 'var(--bg-tertiary)' ?>; 
                                    color: <?= $isToday ? '#fff' : 'var(--text-primary)' ?>;
                                    width: 60px; height: 60px; border-radius: 12px; display:flex; flex-direction:column; align-items:center; justify-content:center; flex-shrink:0;">
                            <span style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.9;"><?= $dateObj->format('M') ?></span>
                            <span style="font-size: 1.4rem; font-weight: 700; line-height: 1;"><?= $dateObj->format('d') ?></span>
                        </div>

                        <!-- Info -->
                        <div style="flex:1;">
                            <h3 style="font-size: 1.05rem; margin-bottom: 4px; color: var(--text-primary);"><?= htmlspecialchars($scale['event_type']) ?></h3>
                            <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?= $scale['description'] ? htmlspecialchars($scale['description']) : 'Domingo' ?>
                            </div>
                        </div>

                        <!-- Seta -->
                        <i data-lucide="chevron-right" style="color: var(--text-muted); width: 20px;"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Histórico (Collapsable ou Separado) -->
    <?php if (!empty($history)): ?>
        <h3 style="font-size:1rem; color: var(--text-secondary); margin: 30px 0 15px; text-transform: uppercase; letter-spacing: 1px;">Histórico Recente</h3>
        <div class="list-group" style="opacity: 0.8;">
            <?php foreach (array_slice($history, 0, 5) as $scale):
                $dateObj = new DateTime($scale['event_date']);
            ?>
                <div class="list-item" onclick="window.location.href='gestao_escala.php?id=<?= $scale['id'] ?>'" style="cursor: pointer; border-color: var(--bg-tertiary);">
                    <div style="display:flex; align-items:center; gap: 15px; width:100%;">
                        <div style="width: 60px; text-align: center; color: var(--text-secondary); font-weight: 600;">
                            <?= $dateObj->format('d/m') ?>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size: 1rem; color: var(--text-secondary);"><?= htmlspecialchars($scale['event_type']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal Nova Escala -->
<div id="modalNewScale" class="sidebar-overlay" style="z-index: 300; display: none; align-items: flex-end; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 0; border-radius: 24px 24px 0 0; animation: slideUp 0.3s ease-out;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h3>Nova Escala</h3>
            <button onclick="document.getElementById('modalNewScale').classList.remove('visible')" style="background:none; border:none; color:var(--text-secondary);"><i data-lucide="x"></i></button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="create_scale">

            <div class="form-group">
                <label class="form-label">Data do Evento</label>
                <input type="date" name="date" class="form-input" required value="<?= date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Tipo de Culto</label>
                <select name="type" class="form-input">
                    <option value="Culto de Domingo">Culto de Domingo</option>
                    <option value="Ensaio">Ensaio</option>
                    <option value="Evento Jovens">Evento Jovens</option>
                    <option value="Evento Especial">Evento Especial</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Descrição (Opcional)</label>
                <input type="text" name="description" class="form-input" placeholder="Ex: Ceia, Visitante Especial...">
            </div>

            <button type="submit" class="btn btn-primary w-full" style="margin-top: 15px;">Criar Escala</button>
        </form>
    </div>
</div>

<style>
    /* Reutiliza animação */
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }

    #modalNewScale.visible {
        display: flex !important;
        opacity: 1;
    }
</style>

<?php
renderAppFooter();
?>