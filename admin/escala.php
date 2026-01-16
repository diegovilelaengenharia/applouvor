<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Buscar Usuários para o Wizard
$usersList = $pdo->query("SELECT * FROM users ORDER BY name")->fetchAll();

// Processar Nova Escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_scale') {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $selected_members = $_POST['members'] ?? [];
    $notify_members = isset($_POST['notify_members']);

    if ($date && $type) {
        // 1. Criar Escala
        $stmt = $pdo->prepare("INSERT INTO scales (event_date, event_type, description) VALUES (?, ?, ?)");
        $stmt->execute([$date, $type, $description]);
        $newId = $pdo->lastInsertId();

        // 2. Adicionar Membros e Capturar Nomes
        $escaladosNames = [];
        if (!empty($selected_members)) {
            $stmtMember = $pdo->prepare("INSERT INTO scale_members (scale_id, user_id, instrument, confirmed) VALUES (?, ?, ?, 0)");
            foreach ($selected_members as $uid) {
                // Definir instrumento padrão
                $userCat = 'Voz';
                $userName = 'Membro';
                foreach ($usersList as $u) {
                    if ($u['id'] == $uid) {
                        $cat = $u['category'];
                        $userName = $u['name'];
                        if ($cat == 'violao') $userCat = 'Violão';
                        if ($cat == 'teclado') $userCat = 'Teclado';
                        if ($cat == 'bateria') $userCat = 'Bateria';
                        if ($cat == 'baixo') $userCat = 'Baixo';
                        break;
                    }
                }
                $stmtMember->execute([$newId, $uid, $userCat]);
                $escaladosNames[] = $userName;
            }
        }

        // Definir dados para o Popup de Sucesso na Sessão
        $_SESSION['scale_created'] = [
            'type' => $type,
            'date' => date('d/m/Y', strtotime($date)),
            'team' => $escaladosNames, // Array de nomes
            'notify' => $notify_members ? 1 : 0,
            'scale_id' => $newId
        ];

        header("Location: escala.php");
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
        <button onclick="openWizard()" class="btn btn-primary" style="box-shadow: var(--shadow-md);">
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

<!-- Bottom Sheet Nova Escala (WIZARD) -->
<div id="sheetNewScale" class="bottom-sheet-overlay" onclick="closeSheet(this)">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()" style="max-height: 90vh; display: flex; flex-direction: column;">
        <div class="sheet-header">Nova Escala</div>

        <!-- Progress Steps -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <div id="step-indicator-1" style="flex: 1; height: 4px; background: var(--accent-interactive); border-radius: 2px;"></div>
            <div id="step-indicator-2" style="flex: 1; height: 4px; background: var(--border-subtle); border-radius: 2px;"></div>
        </div>

        <form method="POST" id="formNewScale" style="overflow-y: auto;">
            <input type="hidden" name="action" value="create_scale">

            <!-- STEP 1: EVENTO -->
            <div id="step-1">
                <h3 style="margin-bottom: 15px; font-size: 1.1rem;">Passo 1: Detalhes do Evento</h3>

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
                    <button type="button" onclick="goToStep(2)" class="btn-primary w-full">Escalar Equipe &rarr;</button>
                </div>
            </div>

            <!-- STEP 2: EQUIPE -->
            <div id="step-2" style="display: none;">
                <h3 style="margin-bottom: 5px; font-size: 1.1rem;">Passo 2: Selecionar Equipe</h3>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 15px;">Selecione quem participará deste evento.</p>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                    <?php foreach ($usersList as $u): ?>
                        <label class="member-select-item" style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: var(--bg-tertiary); border-radius: 10px; cursor: pointer; border: 1px solid transparent; transition: all 0.2s;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem; border: 1px solid transparent;">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($u['name']) ?></div>
                                    <div style="font-size: 0.75rem; opacity: 0.7;"><?= ucfirst(str_replace('_', ' ', $u['category'])) ?></div>
                                </div>
                            </div>
                            <input type="checkbox" name="members[]" value="<?= $u['id'] ?>" style="width: 20px; height: 20px; accent-color: var(--accent-interactive);" onchange="toggleMemberSelection(this)">
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- REQUEST CONFIRMATION CHECKBOX -->
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2);">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="notify_members" value="1" style="width: 20px; height: 20px; accent-color: var(--accent-interactive);">
                        <div>
                            <span style="display: block; font-weight: 600; color: var(--text-primary);">Solicitar confirmação?</span>
                            <span style="display: block; font-size: 0.8rem; color: var(--text-secondary);">Os membros serão notificados via App.</span>
                        </div>
                    </label>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary w-full" style="background: var(--status-success);">Concluir Criação</button>
                    <button type="button" onclick="goToStep(1)" class="btn-ghost w-full" style="margin-top: 10px;">&larr; Voltar</button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <button type="button" class="btn-ghost" onclick="closeSheet(document.getElementById('sheetNewScale'))">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- SUCCESS MODAL (Modern) -->
<?php if (isset($_SESSION['scale_created'])):
    $info = $_SESSION['scale_created'];
    unset($_SESSION['scale_created']);
?>
    <div id="modalSuccess" class="bottom-sheet-overlay active" style="align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px);">
        <div class="card" style="width: 90%; max-width: 400px; text-align: center; animation: slideUp 0.3s ease; padding: 0; overflow: hidden; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

            <!-- Header Success -->
            <div style="background: var(--status-success); padding: 30px 20px; color: white;">
                <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i data-lucide="check" style="width: 32px; height: 32px; color: white;"></i>
                </div>
                <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0;">Sucesso!</h2>
                <p style="opacity: 0.9; margin-top: 5px; font-size: 0.95rem;">Escala cadastrada.</p>
            </div>

            <!-- Body Details -->
            <div style="padding: 25px 20px;">
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 4px;"><?= $info['type'] ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;"><?= $info['date'] ?></p>
                </div>

                <!-- Team Summary -->
                <?php if (!empty($info['team'])): ?>
                    <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 15px; text-align: left; margin-bottom: 20px; max-height: 150px; overflow-y: auto;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; font-weight: 700;">
                            Equipe Escalada (<?= count($info['team']) ?>)
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php foreach ($info['team'] as $name): ?>
                                <span style="font-size: 0.85rem; background: var(--bg-primary); padding: 4px 10px; border-radius: 20px; border: 1px solid var(--border-subtle); color: var(--text-primary);">
                                    <?= htmlspecialchars($name) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($info['notify']): ?>
                    <div style="display: flex; align-items: center; gap: 10px; background: rgba(59, 130, 246, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: left;">
                        <div style="background: var(--accent-interactive); width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;"></div>
                        <p style="font-size: 0.85rem; color: var(--text-primary); margin: 0;">Notificação de confirmação enviada para o App dos membros.</p>
                    </div>
                <?php endif; ?>

                <button onclick="closeSuccessModal()" class="btn-primary w-full" style="height: 50px; font-size: 1rem;">OK, Fechar</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function openWizard() {
        // Reset to step 1
        goToStep(1);
        // Clear inputs? Optional.
        openSheet('sheetNewScale');
    }

    function goToStep(step) {
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'none';

        document.getElementById('step-' + step).style.display = 'block';

        // Update indicators
        const ind1 = document.getElementById('step-indicator-1');
        const ind2 = document.getElementById('step-indicator-2');

        if (step === 1) {
            document.getElementById('step-indicator-1').style.background = 'var(--accent-interactive)';
            document.getElementById('step-indicator-2').style.background = 'var(--border-subtle)';
        } else {
            document.getElementById('step-indicator-1').style.background = 'var(--accent-interactive)';
            document.getElementById('step-indicator-2').style.background = 'var(--accent-interactive)';
        }
    }

    function toggleMemberSelection(checkbox) {
        const label = checkbox.closest('.member-select-item');
        if (checkbox.checked) {
            label.style.background = 'rgba(76, 175, 80, 0.15)'; // Green light
            label.style.borderColor = 'var(--status-success)';
            label.querySelector('.user-avatar').style.borderColor = 'var(--status-success)';
        } else {
            label.style.background = 'var(--bg-tertiary)';
            label.style.borderColor = 'transparent';
            label.querySelector('.user-avatar').style.borderColor = 'transparent';
        }
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