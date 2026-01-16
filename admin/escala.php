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

    <!-- Header com Voltar -->
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 32px;">
        <a href="index.php" class="btn-icon" style="background: var(--bg-secondary); color: var(--text-primary); text-decoration: none;">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 style="margin: 0; font-size: 1.5rem; font-weight: 700;">Escalas</h1>
            <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">Próximos Cultos</p>
        </div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap: 16px; margin-bottom: 32px;">
        <button onclick="openSheet('sheetReport')" class="btn" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: white; border: none; box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4); padding: 0 16px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
            <i data-lucide="printer" style="width: 18px;"></i>
            <span>Relatório</span>
        </button>
        <button onclick="openWizard()" class="btn btn-primary" style="box-shadow: var(--shadow-md); padding: 0 20px;">
            <i data-lucide="plus" style="width: 18px; margin-right: 6px;"></i> Nova Escala
        </button>
    </div>

    <!-- Lista Próximas (Grouped by Month) -->
    <div class="list-group">
        <?php if (empty($upcoming)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary); background: var(--bg-secondary); border-radius: 12px;">
                <i data-lucide="calendar-off" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                <p>Nenhuma escala futura agendada.</p>
            </div>
        <?php else: ?>
            <?php
            $currentMonth = '';
            foreach ($upcoming as $scale):
                $dateObj = new DateTime($scale['event_date']);
                $isToday = $scale['event_date'] === $today;

                // Month Group Header
                $monthLabel = ucfirst(strftime('%B %Y', $dateObj->getTimestamp())); // Requires setlocale, using format for safety
                // Fallback since strftime is deprecated in PHP 8.1+ but simple implementation for now:
                $months = [
                    '01' => 'Janeiro',
                    '02' => 'Fevereiro',
                    '03' => 'Março',
                    '04' => 'Abril',
                    '05' => 'Maio',
                    '06' => 'Junho',
                    '07' => 'Julho',
                    '08' => 'Agosto',
                    '09' => 'Setembro',
                    '10' => 'Outubro',
                    '11' => 'Novembro',
                    '12' => 'Dezembro'
                ];
                $mNum = $dateObj->format('m');
                $yNum = $dateObj->format('Y');
                $monthLabel = $months[$mNum] . ' ' . $yNum;

                if ($monthLabel !== $currentMonth) {
                    $currentMonth = $monthLabel;
                    echo "<div style='margin-top: 20px; margin-bottom: 10px; font-weight: 700; color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; padding-left: 5px;'>$currentMonth</div>";
                }
            ?>
                <div class="list-item" onclick="window.location.href='gestao_escala.php?id=<?= $scale['id'] ?>'" style="cursor: pointer;">
                    <div style="display:flex; align-items:center; gap: 16px; width:100%;">
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

    <!-- Histórico (Collapsable) -->
    <?php if (!empty($history)): ?>
        <div style="margin-top: 40px;">
            <button onclick="toggleHistory()" style="background: none; border: none; font-size:1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 0;">
                Histórico Recente <i data-lucide="chevron-down" id="hist-arrow" style="width: 16px; transition: transform 0.2s;"></i>
            </button>

            <div id="history-list" style="display: none; opacity: 0.8; margin-top: 15px;">
                <?php foreach (array_slice($history, 0, 10) as $scale):
                    $dateObj = new DateTime($scale['event_date']);
                ?>
                    <div class="list-item" onclick="window.location.href='gestao_escala.php?id=<?= $scale['id'] ?>'" style="cursor: pointer; border-color: var(--bg-tertiary); margin-bottom: 8px;">
                        <div style="display:flex; align-items:center; gap: 16px; width:100%;">
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
        </div>
        <script>
            function toggleHistory() {
                const list = document.getElementById('history-list');
                const arrow = document.getElementById('hist-arrow');
                if (list.style.display === 'none') {
                    list.style.display = 'block';
                    arrow.style.transform = 'rotate(180deg)';
                } else {
                    list.style.display = 'none';
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        </script>
    <?php endif; ?>

</div>

<!-- Bottom Sheet Report -->
<div id="sheetReport" class="bottom-sheet-overlay" onclick="closeSheet(this)">
    <div class="bottom-sheet-content" onclick="event.stopPropagation()">
        <div class="sheet-header">Gerar Relatório</div>
        <form action="relatorio_print.php" method="GET" target="_blank">
            <div class="form-group">
                <label class="form-label">Data Início</label>
                <input type="date" name="start" class="form-input" value="<?= date('Y-m-01') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Data Fim</label>
                <input type="date" name="end" class="form-input" value="<?= date('Y-m-t') ?>" required>
            </div>
            <button type="submit" class="btn btn-primary w-full" style="height: 50px;">
                <i data-lucide="printer" style="width: 18px; margin-right: 8px;"></i> Gerar PDF / Imprimir
            </button>
        </form>
    </div>
</div>

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
                    <button type="button" onclick="goToStep(2)" class="btn-primary w-full" style="height: 50px; font-size: 1rem;">Escalar Equipe &rarr;</button>
                </div>
            </div>

            <!-- STEP 2: EQUIPE TÉCNICA -->
            <div id="step-2" style="display: none;">
                <h3 style="margin-bottom: 5px; font-size: 1.1rem;">Passo 2: Montar Equipe</h3>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 20px;">Adicione os integrantes por função.</p>

                <!-- AREA 1: ADD MEMBER FORM -->
                <div style="background: var(--bg-tertiary); padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Adicionar Novo</div>

                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <!-- Role Select -->
                        <div style="flex: 1;">
                            <select id="roleFilter" class="form-select modern-input" onchange="filterUsersByRole()" style="font-size: 0.9rem; padding: 12px;">
                                <option value="">Função...</option>
                                <option value="Voz Feminina">Voz Feminina</option>
                                <option value="Voz Masculina">Voz Masculina</option>
                                <option value="Violão">Violão</option>
                                <option value="Teclado">Teclado</option>
                                <option value="Bateria">Bateria</option>
                            </select>
                        </div>

                        <!-- User Select (Filtered) -->
                        <div style="flex: 1.5;">
                            <select id="userAdder" class="form-select modern-input" style="font-size: 0.9rem; padding: 12px;">
                                <option value="">Selecione...</option>
                                <?php foreach ($usersList as $u): ?>
                                    <?php
                                    // Normalize logic mirrored from gestao_escala.php
                                    $rawCat = mb_strtolower($u['category'], 'UTF-8');
                                    $normalizedCat = $rawCat;
                                    if (strpos($rawCat, 'voz') !== false) $normalizedCat = 'voz';
                                    if (strpos($rawCat, 'violao') !== false) $normalizedCat = 'violão';
                                    if (strpos($rawCat, 'teclado') !== false) $normalizedCat = 'teclado';
                                    if (strpos($rawCat, 'bateria') !== false) $normalizedCat = 'bateria';
                                    if (strpos($rawCat, 'baixo') !== false) $normalizedCat = 'baixo';
                                    if (strpos($rawCat, 'guitarra') !== false) $normalizedCat = 'guitarra';
                                    if (strpos($rawCat, 'midia') !== false) $normalizedCat = 'mídia';
                                    if (strpos($rawCat, 'som') !== false) $normalizedCat = 'som';
                                    ?>
                                    <option value="<?= $u['id'] ?>"
                                        data-role="<?= $normalizedCat ?>"
                                        data-avatar="<?= strtoupper(substr($u['name'], 0, 1)) ?>"
                                        data-name="<?= htmlspecialchars($u['name']) ?>">
                                        <?= htmlspecialchars($u['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="button" onclick="addMemberToScale()" class="btn btn-primary w-full" style="height: 50px; font-size: 0.95rem; background: var(--accent-interactive); font-weight: 700; box-shadow: var(--shadow-md);">
                        <i data-lucide="plus" style="margin-right: 6px;"></i> ADICIONAR À LISTA
                    </button>
                </div>

                <!-- AREA 2: SELECTED MEMBERS LIST -->
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; display: flex; justify-content: space-between;">
                        <span>Equipe Selecionada</span>
                        <span id="count-display" style="color: var(--text-secondary);">0</span>
                    </div>

                    <div id="selected-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 250px; overflow-y: auto; padding-right: 5px;">
                        <!-- JS renders items here -->
                        <div id="empty-state" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.9rem; border: 1px dashed var(--border-subtle); border-radius: 8px;">
                            Ninguém adicionado ainda.
                        </div>
                    </div>
                </div>

                <!-- INFO: CONFIRMATION -->
                <div style="display: flex; align-items: center; gap: 10px; background: rgba(59, 130, 246, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                    <i data-lucide="info" style="width: 18px; color: var(--accent-interactive);"></i>
                    <p style="font-size: 0.8rem; color: var(--text-primary); margin: 0;">Os membros serão notificados automaticamente via App.</p>
                </div>

                <!-- Hidden inputs container -->
                <div id="hidden-inputs"></div>

                <div style="margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <button type="button" onclick="goToStep(1)" class="btn btn-outline" style="height: 50px; border-color: #F59E0B; background-color: #FEF3C7; color: #D97706; font-weight: 600;">
                        &larr; Voltar
                    </button>
                    <button type="submit" class="btn-primary" style="height: 50px; background: var(--status-success);">
                        Concluir e Salvar
                    </button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <button type="button" class="btn-ghost" onclick="closeSheet('sheetNewScale')" style="background: #EF4444; color: white; padding: 0 16px; height: 50px; border-radius: 8px; font-weight: 600; font-size: 1rem; width: 100%;">Cancelar Criação</button>
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
        <div class="card" style="background: var(--bg-secondary); width: 90%; max-width: 400px; text-align: center; animation: slideUp 0.3s ease; padding: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

            <!-- Header Success -->
            <div style="background: var(--status-success); padding: 30px 20px; color: white;">
                <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                    <i data-lucide="check" style="width: 32px; height: 32px; color: white;"></i>
                </div>
                <h2 style="font-size: 1.6rem; font-weight: 800; margin: 0;">Escala Criada!</h2>
                <p style="opacity: 0.9; margin-top: 5px; font-size: 0.95rem;">O evento foi agendado com sucesso.</p>
            </div>

            <!-- Body Details -->
            <div style="padding: 25px 20px;">
                <div style="margin-bottom: 20px;">
                    <h3 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 4px;"><?= $info['type'] ?></h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;"><?= $info['date'] ?></p>
                </div>

                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 25px; line-height: 1.5;">
                    Sua equipe já está visível no painel e os membros podem visualizar suas escalas pelo aplicativo. <br>
                    <b>Bom trabalho!</b>
                </p>

                <!-- Team Summary -->
                <?php if (!empty($info['team'])): ?>
                    <div style="background: var(--bg-tertiary); border-radius: 12px; padding: 15px; text-align: left; margin-bottom: 20px; max-height: 150px; overflow-y: auto;">
                        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 10px; font-weight: 700;">
                            Integrantes (<?= count($info['team']) ?>)
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

                <button onclick="closeSuccessModal()" class="btn-primary w-full" style="height: 50px; font-size: 1rem;">Perfeito, Fechar</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function openWizard() {
        goToStep(1);
        openSheet('sheetNewScale');
        // Clear logic if needed
        document.getElementById('selected-list').innerHTML = '<div id="empty-state" style="text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.9rem; border: 1px dashed var(--border-subtle); border-radius: 8px;">Ninguém adicionado ainda.</div>';
        document.getElementById('hidden-inputs').innerHTML = '';
        document.getElementById('count-display').innerText = '0';
    }

    function goToStep(step) {
        document.getElementById('step-1').style.display = 'none';
        document.getElementById('step-2').style.display = 'none';

        document.getElementById('step-' + step).style.display = 'block';

        if (step === 1) {
            document.getElementById('step-indicator-1').style.background = 'var(--accent-interactive)';
            document.getElementById('step-indicator-2').style.background = 'var(--border-subtle)';
        } else {
            document.getElementById('step-indicator-1').style.background = 'var(--accent-interactive)';
            document.getElementById('step-indicator-2').style.background = 'var(--accent-interactive)';
        }
    }

    // Role Filtering Logic
    function filterUsersByRole() {
        const role = document.getElementById('roleFilter').value.toLowerCase().trim();
        const select = document.getElementById('userAdder');
        const options = select.getElementsByTagName('option');

        select.value = ""; // Reset

        for (let i = 0; i < options.length; i++) {
            const opt = options[i];
            if (opt.value === "") continue;

            const optRole = (opt.getAttribute('data-role') || '').toLowerCase().trim();
            const optName = (opt.getAttribute('data-name') || '').toLowerCase(); // Placeholder if we need name filtering

            // Special Logic for Gender if needed (Currently filtering by raw voice cat)
            // If DB doesn't have gender, we just show all voices. 
            // If user has specific gender logic, add here.

            if (!role) {
                opt.style.display = 'block';
            } else {
                // Mapping display roles to data-roles
                let searchRole = role;
                if (role.includes('voz')) searchRole = 'voz';

                if (optRole.includes(searchRole)) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            }
        }
    }

    // Dynamic Add Logic
    function addMemberToScale() {
        const select = document.getElementById('userAdder');
        const id = select.value;
        const name = select.selectedOptions[0]?.getAttribute('data-name');
        const role = select.selectedOptions[0]?.getAttribute('data-role');
        const avatar = select.selectedOptions[0]?.getAttribute('data-avatar');

        if (!id) return;

        // Check duplicate
        if (document.querySelector(`input[name="members[]"][value="${id}"]`)) {
            alert('Membro já adicionado!');
            return;
        }

        const list = document.getElementById('selected-list');
        const emptyState = document.getElementById('empty-state');
        if (emptyState) emptyState.remove();

        const item = document.createElement('div');
        item.className = 'list-item';
        item.style.padding = '10px';
        item.style.background = 'var(--bg-primary)';
        item.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem; border: 1px solid var(--border-subtle);">
                        ${avatar}
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem;">${name}</div>
                        <div style="font-size: 0.75rem; opacity: 0.7;">${role}</div>
                    </div>
                </div>
                <button type="button" onclick="removeMember(this, '${id}')" style="background: none; border: none; color: var(--status-error); cursor: pointer;">
                    <i data-lucide="trash-2" style="width: 18px;"></i>
                </button>
            </div>
        `;

        list.appendChild(item);
        lucide.createIcons();

        // Add Hidden Input
        const inputDiv = document.getElementById('hidden-inputs');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'members[]';
        input.value = id;
        inputDiv.appendChild(input);

        // Update Counter
        updateCount();
    }

    function removeMember(btn, id) {
        btn.closest('.list-item').remove();
        const input = document.querySelector(`input[name="members[]"][value="${id}"]`);
        if (input) input.remove();
        updateCount();
    }

    function updateCount() {
        const count = document.querySelectorAll('#hidden-inputs input').length;
        document.getElementById('count-display').innerText = count;
    }

    function closeSuccessModal() {
        const modal = document.getElementById('modalSuccess');
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none'; // Force hide
        }
    }

    // Bottom Sheets Logic (Reused)
    function openSheet(id) {
        document.querySelectorAll('.bottom-sheet-overlay').forEach(el => el.classList.remove('active'));
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.classList.add('active');
            sheet.style.display = 'flex'; // Ensure flex
            if (navigator.vibrate) navigator.vibrate(50);
        }
    }

    function closeSheet(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (el) {
            el.classList.remove('active');
            setTimeout(() => {
                if (!el.classList.contains('active')) el.style.display = 'none'; // Clean up
            }, 300);
        }
    }
</script>

<?php
renderAppFooter();
?>

<style>
    /* Shared Modern Styles */
    .modern-input {
        background: var(--bg-primary);
        border: 1px solid var(--border-subtle);
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 0.95rem;
        transition: all 0.2s;
        width: 100%;
        color: var(--text-primary);
    }

    .modern-input:focus {
        border-color: var(--accent-interactive);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
</style>