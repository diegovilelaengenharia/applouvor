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

    <!-- Abas Próximas / Histórico (Simples via JS ou reload, aqui vamos mostrar seções) -->

    <!-- Header com Voltar -->
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
        <a href="index.php" class="btn-icon" style="background: var(--bg-secondary); color: var(--text-primary);">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Escalas</h1>
            <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">Próximos Cultos</p>
        </div>
    </div>

    <!-- Toolbar de Ações -->
    <div style="display:flex; justify-content:flex-end; margin-bottom: 20px;">
        <button onclick="openSheet('sheetNewScale')" class="btn btn-primary" style="box-shadow: var(--shadow-md);">
            <i data-lucide="plus" style="width: 18px;"></i> Nova Escala
        </button>
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

<!-- Bottom Sheet Nova Escala -->
<div id="sheetNewScale" class="bottom-sheet-overlay" onclick="closeSheet(this)">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()">
        <div class="sheet-header">Nova Escala</div>

        <!-- TABS MOCK -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid var(--border-subtle);">
            <button onclick="toggleNewScaleTab('evento')" id="ns-btn-evento" class="tab-btn active" style="flex:1; padding-bottom:10px; border:none; border-bottom:2px solid var(--accent-interactive); background:transparent; font-weight:600; color:var(--text-primary);">Evento</button>
            <button onclick="toggleNewScaleTab('equipe')" id="ns-btn-equipe" class="tab-btn" style="flex:1; padding-bottom:10px; border:none; border-bottom:2px solid transparent; background:transparent; font-weight:600; color:var(--text-secondary);">Equipe</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="create_scale">

            <!-- TAB EVENTO -->
            <div id="ns-tab-evento">
                <div class="form-group">
                    <label class="form-label">Data do Evento</label>
                    <input type="date" name="date" class="form-input" required value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Tipo de Culto</label>
                    <select name="type" class="form-select">
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

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary w-full">Criar e Montar Equipe</button>
                </div>
            </div>

            <!-- TAB EQUIPE (Placeholder) -->
            <div id="ns-tab-equipe" style="display: none; text-align: center; padding: 20px 0;">
                <div style="background: var(--bg-tertiary); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i data-lucide="users" style="width: 24px; height: 24px; color: var(--text-secondary);"></i>
                </div>
                <h3 style="font-size: 1rem; margin-bottom: 8px;">Primeiro, crie o evento</h3>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 20px;">
                    Você poderá adicionar músicos e montar a equipe assim que salvar as informações do evento.
                </p>
                <button type="button" onclick="toggleNewScaleTab('evento')" class="btn btn-outline">Voltar para Evento</button>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <button type="button" class="btn-ghost" onclick="closeSheet(document.getElementById('sheetNewScale'))">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleNewScaleTab(tab) {
        // Hide all
        document.getElementById('ns-tab-evento').style.display = 'none';
        document.getElementById('ns-tab-equipe').style.display = 'none';

        // Reset buttons
        document.getElementById('ns-btn-evento').style.color = 'var(--text-secondary)';
        document.getElementById('ns-btn-evento').style.borderBottomColor = 'transparent';

        document.getElementById('ns-btn-equipe').style.color = 'var(--text-secondary)';
        document.getElementById('ns-btn-equipe').style.borderBottomColor = 'transparent';

        // Show selected
        document.getElementById('ns-tab-' + tab).style.display = 'block';
        const btn = document.getElementById('ns-btn-' + tab);
        btn.style.color = 'var(--text-primary)';
        btn.style.borderBottomColor = 'var(--accent-interactive)';
    }

    // Bottom Sheets Logic (Reused)
    function openSheet(id) {
        document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.classList.add('active');
            if (navigator.vibrate) navigator.vibrate(50);
        }
    }

    function closeSheet(element) {
        // Se passar ID string
        if (typeof element === 'string') {
            document.getElementById(element).classList.remove('active');
            return;
        }
        // Se passar o proprio elemento overlay
        if (element.classList.contains('bottom-sheet-overlay')) {
            element.classList.remove('active');
        }
    }
</script>

<?php
renderAppFooter();
?>