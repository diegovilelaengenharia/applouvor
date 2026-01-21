<?php
// admin/escala_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

if (!isset($_GET['id'])) {
    header('Location: escala.php');
    exit;
}

$id = $_GET['id'];

// --- LÓGICA DE POST (Adicionar/Remover/Editar/Excluir) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Excluir Escala
        if ($_POST['action'] === 'delete_schedule') {
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: escala.php");
            exit;
        }
        // Editar Detalhes da Escala
        elseif ($_POST['action'] === 'edit_schedule') {
            $stmt = $pdo->prepare("UPDATE schedules SET event_date = ?, event_type = ?, notes = ? WHERE id = ?");
            $stmt->execute([$_POST['event_date'], $_POST['event_type'], $_POST['notes'], $id]);
            header("Location: escala_detalhe.php?id=$id");
            exit;
        }
        // Adicionar Membros
        if ($_POST['action'] === 'add_members' && !empty($_POST['users'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
            foreach ($_POST['users'] as $userId) {
                $stmt->execute([$id, $userId]);
            }
        }
        // Remover Membro
        elseif ($_POST['action'] === 'remove_member' && !empty($_POST['user_id'])) {
            $stmt = $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ? AND user_id = ?");
            $stmt->execute([$id, $_POST['user_id']]);
        }
        // Adicionar Músicas
        elseif ($_POST['action'] === 'add_songs' && !empty($_POST['songs'])) {
            // Pega a última ordem
            $stmtOrder = $pdo->prepare("SELECT MAX(order_index) FROM schedule_songs WHERE schedule_id = ?");
            $stmtOrder->execute([$id]);
            $lastOrder = $stmtOrder->fetchColumn() ?: 0;

            $stmt = $pdo->prepare("INSERT IGNORE INTO schedule_songs (schedule_id, song_id, order_index) VALUES (?, ?, ?)");
            foreach ($_POST['songs'] as $songId) {
                $lastOrder++;
                $stmt->execute([$id, $songId, $lastOrder]);
            }
        }
        // Remover Música
        elseif ($_POST['action'] === 'remove_song' && !empty($_POST['song_id'])) {
            $stmt = $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ? AND song_id = ?");
            $stmt->execute([$id, $_POST['song_id']]);
        }

        // Refresh para evitar reenvio
        header("Location: escala_detalhe.php?id=$id&tab=" . ($_POST['current_tab'] ?? 'detalhes'));
        exit;
    }
}

// --- BUSCA DADOS DA ESCALA ---
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    echo "Escala não encontrada.";
    exit;
}

// --- BUSCA MEMBROS ATUAIS ---
$stmtMembers = $pdo->prepare("
    SELECT u.id, u.name, u.instrument, su.status 
    FROM schedule_users su 
    JOIN users u ON su.user_id = u.id 
    WHERE su.schedule_id = ?
    ORDER BY u.name
");
$stmtMembers->execute([$id]);
$currentMembers = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

// --- BUSCA MÚSICAS ATUAIS ---
$stmtScheduleSongs = $pdo->prepare("
    SELECT s.id, s.title, s.artist, s.tone, ss.order_index
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    WHERE ss.schedule_id = ?
    ORDER BY ss.order_index ASC
");
$stmtScheduleSongs->execute([$id]);
$currentSongs = $stmtScheduleSongs->fetchAll(PDO::FETCH_ASSOC);

// --- BUSCA DADOS PARA OS MODAIS (TODOS) ---
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$date = new DateTime($schedule['event_date']);
$formattedDate = $date->format('d/m/Y');
$dayName = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Controle de Aba Ativa via GET
$activeTab = $_GET['tab'] ?? 'detalhes';

renderAppHeader('Detalhes da Escala');
?>

<style>
    /* Tabs Styles */
    .tabs-nav {
        display: flex;
        background: var(--bg-tertiary);
        padding: 4px;
        border-radius: 16px;
        margin-bottom: 24px;
    }

    .tab-btn {
        flex: 1;
        text-align: center;
        padding: 10px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: transparent;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab-btn.active {
        color: var(--text-primary);
        background: var(--bg-secondary);
        box-shadow: var(--shadow-sm);
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background: var(--bg-secondary);
        border-radius: 24px;
        border: 1px dashed var(--border-subtle);
        margin-top: 10px;
    }

    /* Checkbox List Styling */
    .checkbox-list {
        max-height: 300px;
        overflow-y: auto;
        text-align: left;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        padding: 12px;
        border-bottom: 1px solid var(--border-subtle);
        gap: 12px;
    }

    .checkbox-item:last-child {
        border-bottom: none;
    }

    .checkbox-item input[type="checkbox"] {
        transform: scale(1.3);
        accent-color: var(--accent-interactive);
    }

    .checkbox-info {
        flex: 1;
    }

    .checkbox-name {
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-primary);
    }

    .checkbox-sub {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }
</style>

<!-- Hero Header -->
<div style="
    background: linear-gradient(135deg, #047857 0%, #065f46 100%); 
    margin: -24px -16px 32px -16px; 
    padding: 32px 24px 64px 24px; 
    border-radius: 0 0 32px 32px; 
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: visible;
">
    <!-- Navigation Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <a href="escala.php" class="ripple" style="
            padding: 10px 20px;
            border-radius: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            color: #047857; 
            background: white; 
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        ">
            <i data-lucide="arrow-left" style="width: 16px;"></i> Voltar
        </a>

        <div style="display: flex; align-items: center;">
            <?php renderGlobalNavButtons(); ?>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Detalhes da Escala</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<!-- CONTEÚDO: DETALHES -->
<div id="detalhes">
    <?php
    $d = new DateTime($schedule['event_date']);
    $dayNum = $d->format('d');
    $monthStr = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][(int)$d->format('m') - 1];
    $timeStr = '19:00';

    // Calcular estatísticas
    $totalMembros = count($currentMembers);
    $totalMusicas = count($currentSongs);
    $duracaoEstimada = $totalMusicas * 5; // 5 min por música

    // Agrupar instrumentos
    $instrumentos = [];
    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz';
        $instrumentos[$inst] = ($instrumentos[$inst] ?? 0) + 1;
    }
    ksort($instrumentos);
    ?>

    <!-- Card Principal -->
    <div style="background: var(--bg-secondary); border-radius: 20px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.08); border: 1px solid var(--border-subtle);">

        <!-- Header com Data -->
        <div style="background: linear-gradient(135deg, #047857 0%, #065f46 100%); padding: 24px; color: white; position: relative;">
            <!-- Menu de Três Pontinhos -->
            <div style="position: absolute; top: 20px; right: 20px;">
                <button onclick="toggleMenu()" class="ripple" style="background: rgba(255,255,255,0.2); border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; backdrop-filter: blur(10px);" onmouseover="this.style.background='rgba(255,255,255,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                    <i data-lucide="more-vertical" style="width: 20px; color: white;"></i>
                </button>

                <!-- Dropdown Menu -->
                <div id="actionMenu" style="display: none; position: absolute; top: 45px; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 180px; overflow: hidden; z-index: 1000; border: 1px solid var(--border-subtle);">
                    <button onclick="openEditModal(); toggleMenu();" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 18px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: var(--text-primary); font-weight: 600; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='var(--bg-tertiary)';" onmouseout="this.style.background='transparent';">
                        <i data-lucide="edit-3" style="width: 18px; color: #FFC107;"></i>
                        Editar Escala
                    </button>
                    <div style="height: 1px; background: var(--border-subtle); margin: 0 12px;"></div>
                    <form method="POST" onsubmit="return confirm('Excluir esta escala?')" style="margin: 0;">
                        <input type="hidden" name="action" value="delete_schedule">
                        <button type="submit" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 18px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: #DC3545; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                            <i data-lucide="trash-2" style="width: 18px;"></i>
                            Excluir Escala
                        </button>
                    </form>
                </div>
            </div>

            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <!-- Badge de Data -->
                <div style="background: white; border-radius: 16px; padding: 12px 16px; text-align: center; min-width: 80px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <div style="font-size: 0.75rem; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 1px;"><?= $monthStr ?></div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #1f2937; line-height: 1; margin: 4px 0;"><?= $dayNum ?></div>
                    <div style="font-size: 0.7rem; font-weight: 600; color: #6b7280; text-transform: uppercase;"><?= $dayName ?></div>
                </div>

                <!-- Informações -->
                <div style="flex: 1;">
                    <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                        <span style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="clock" style="width: 14px;"></i> <?= $timeStr ?>
                        </span>
                    </div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; margin: 0; line-height: 1.2;">
                        <?= htmlspecialchars($schedule['event_type']) ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Resumo Estatístico -->
        <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
            <h3 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="bar-chart-2" style="width: 16px; color: #047857;"></i>
                Resumo da Escala
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px;">
                <!-- Card: Equipe -->
                <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #93c5fd;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 6px;">Equipe</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #1e3a8a; line-height: 1;">
                        <i data-lucide="users" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #1e3a8a;"><?= $totalMembros ?></div>
                </div>

                <!-- Card: Músicas -->
                <div style="background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #f9a8d4;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #9f1239; text-transform: uppercase; margin-bottom: 6px;">Músicas</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #881337; line-height: 1;">
                        <i data-lucide="music" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #881337;"><?= $totalMusicas ?></div>
                </div>

                <!-- Card: Duração -->
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #fcd34d;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #92400e; text-transform: uppercase; margin-bottom: 6px;">Duração</div>
                    <div style="font-size: 2rem; font-weight: 900; color: #78350f; line-height: 1;">
                        <i data-lucide="timer" style="width: 24px; height: 24px; margin-bottom: 4px;"></i>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #78350f;">~<?= $duracaoEstimada ?>min</div>
                </div>
            </div>
        </div>

        <!-- Instrumentos Escalados -->
        <?php if (!empty($instrumentos)): ?>
            <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
                <h3 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="guitar" style="width: 16px; color: #047857;"></i>
                    Instrumentos Escalados
                </h3>

                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach ($instrumentos as $inst => $count):
                        $cores = [
                            'Voz' => ['bg' => '#ddd6fe', 'text' => '#5b21b6', 'border' => '#a78bfa'],
                            'Violão' => ['bg' => '#fed7aa', 'text' => '#9a3412', 'border' => '#fb923c'],
                            'Guitarra' => ['bg' => '#bfdbfe', 'text' => '#1e40af', 'border' => '#60a5fa'],
                            'Bateria' => ['bg' => '#fecaca', 'text' => '#991b1b', 'border' => '#f87171'],
                            'Teclado' => ['bg' => '#a7f3d0', 'text' => '#065f46', 'border' => '#34d399'],
                            'Baixo' => ['bg' => '#e9d5ff', 'text' => '#6b21a8', 'border' => '#c084fc'],
                        ];
                        $cor = $cores[$inst] ?? ['bg' => '#e5e7eb', 'text' => '#374151', 'border' => '#9ca3af'];
                    ?>
                        <div style="background: <?= $cor['bg'] ?>; color: <?= $cor['text'] ?>; border: 1.5px solid <?= $cor['border'] ?>; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <span><?= htmlspecialchars($inst) ?></span>
                            <span style="background: <?= $cor['text'] ?>; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Observações -->
        <?php if (!empty($schedule['notes'])): ?>
            <div style="padding: 24px; border-bottom: 1px solid var(--border-subtle);">
                <div style="background: rgba(250, 204, 21, 0.1); border-left: 4px solid #FACC15; padding: 16px; border-radius: 0 12px 12px 0; display: flex; gap: 12px;">
                    <i data-lucide="info" style="color: #EAB308; width: 20px; flex-shrink: 0; margin-top: 2px;"></i>
                    <div>
                        <div style="font-weight: 700; color: #EAB308; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 6px;">Observações</div>
                        <div style="color: var(--text-secondary); line-height: 1.6; font-size: 0.95rem;">
                            <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- NOVO DESIGN: ABA EQUIPE -->
<div id="equipe" style="margin-top: 24px;">
    <?php
    // Agrupar por tipo
    $vozMembers = [];
    $instrumentoMembers = [];

    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz';
        if (stripos($inst, 'voz') !== false && strlen($inst) <= 10) {
            $vozMembers[] = $m;
        } else {
            $instrumentoMembers[] = $m;
        }
    }

    $totalMembros = count($currentMembers);
    ?>

    <?php if (empty($currentMembers)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i data-lucide="users" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Equipe Vazia</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">Adicione instrumentistas e vocalistas para esta escala.</p>
            <button onclick="openModal('modalMembers')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membros
            </button>
        </div>
    <?php else: ?>

        <!-- Header com Contador -->
        <div style="background: var(--bg-secondary); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="users" style="width: 22px; color: #047857;"></i>
                        Equipe Escalada
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 32px;"><?= $totalMembros ?> <?= $totalMembros == 1 ? 'membro' : 'membros' ?></p>
                </div>
                <button onclick="openModal('modalMembers')" class="ripple" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                    <i data-lucide="plus" style="width: 16px;"></i> Adicionar
                </button>
            </div>
        </div>

        <!-- Vocalistas -->
        <?php if (!empty($vozMembers)): ?>
            <div style="margin-bottom: 24px;">
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="mic-2" style="width: 16px; color: #8b5cf6;"></i>
                    Voz (<?= count($vozMembers) ?>)
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($vozMembers as $member):
                        $statusColors = [
                            'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'Confirmado', 'icon' => 'check-circle'],
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Pendente', 'icon' => 'clock'],
                            'declined' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'Recusado', 'icon' => 'x-circle'],
                        ];
                        $status = $statusColors[$member['status']] ?? $statusColors['pending'];
                    ?>
                        <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 14px; padding: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#8b5cf6';" onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 4px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">
                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                <!-- Avatar -->
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #ddd6fe 0%, #c4b5fd 100%); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: #5b21b6; flex-shrink: 0; border: 2px solid #a78bfa;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">
                                        <?= htmlspecialchars($member['instrument'] ?: 'Voz') ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div style="background: <?= $status['bg'] ?>; color: <?= $status['text'] ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i data-lucide="<?= $status['icon'] ?>" style="width: 14px;"></i>
                                    <span><?= $status['label'] ?></span>
                                </div>
                            </div>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($member['name']) ?>?');" style="margin: 0 0 0 12px;">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <input type="hidden" name="current_tab" value="equipe">
                                <button type="submit" class="ripple" style="background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Instrumentistas -->
        <?php if (!empty($instrumentoMembers)): ?>
            <div>
                <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="guitar" style="width: 16px; color: #f59e0b;"></i>
                    Instrumentos (<?= count($instrumentoMembers) ?>)
                </h4>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($instrumentoMembers as $member):
                        $statusColors = [
                            'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => 'Confirmado', 'icon' => 'check-circle'],
                            'pending' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'Pendente', 'icon' => 'clock'],
                            'declined' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'Recusado', 'icon' => 'x-circle'],
                        ];
                        $status = $statusColors[$member['status']] ?? $statusColors['pending'];

                        // Cor do avatar baseada no instrumento
                        $instColors = [
                            'Violão' => ['bg' => '#fed7aa', 'border' => '#fb923c', 'text' => '#9a3412'],
                            'Guitarra' => ['bg' => '#bfdbfe', 'border' => '#60a5fa', 'text' => '#1e40af'],
                            'Bateria' => ['bg' => '#fecaca', 'border' => '#f87171', 'text' => '#991b1b'],
                            'Teclado' => ['bg' => '#a7f3d0', 'border' => '#34d399', 'text' => '#065f46'],
                            'Baixo' => ['bg' => '#e9d5ff', 'border' => '#c084fc', 'text' => '#6b21a8'],
                        ];
                        $instColor = $instColors[$member['instrument']] ?? ['bg' => '#fed7aa', 'border' => '#fb923c', 'text' => '#9a3412'];
                    ?>
                        <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 14px; padding: 14px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#f59e0b';" onmouseout="this.style.transform=''; this.style.boxShadow='0 1px 4px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">
                            <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                <!-- Avatar -->
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, <?= $instColor['bg'] ?> 0%, <?= $instColor['bg'] ?> 100%); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; color: <?= $instColor['text'] ?>; flex-shrink: 0; border: 2px solid <?= $instColor['border'] ?>;">
                                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                </div>

                                <!-- Info -->
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($member['name']) ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 500;">
                                        <?= htmlspecialchars($member['instrument']) ?>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div style="background: <?= $status['bg'] ?>; color: <?= $status['text'] ?>; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 4px; white-space: nowrap;">
                                    <i data-lucide="<?= $status['icon'] ?>" style="width: 14px;"></i>
                                    <span><?= $status['label'] ?></span>
                                </div>
                            </div>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($member['name']) ?>?');" style="margin: 0 0 0 12px;">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                <input type="hidden" name="current_tab" value="equipe">
                                <button type="submit" class="ripple" style="background: transparent; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- NOVO DESIGN: ABA MÚSICAS -->
<div id="repertorio" style="margin-top: 24px;">
    <?php
    $totalMusicas = count($currentSongs);
    $duracaoTotal = $totalMusicas * 5; // 5 min por música (estimativa)
    ?>

    <?php if (empty($currentSongs)): ?>
        <!-- Empty State -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: var(--bg-tertiary); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i data-lucide="music" style="color: var(--text-muted); width: 40px; height: 40px;"></i>
            </div>
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Repertório Vazio</h3>
            <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">Selecione as músicas para esta escala.</p>
            <button onclick="openModal('modalSongs')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Músicas
            </button>
        </div>
    <?php else: ?>

        <!-- Header com Contador e Duração -->
        <div style="background: var(--bg-secondary); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-subtle); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="music-2" style="width: 22px; color: #3b82f6;"></i>
                        Repertório
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 32px;">
                        <?= $totalMusicas ?> <?= $totalMusicas == 1 ? 'música' : 'músicas' ?> • ~<?= $duracaoTotal ?>min
                    </p>
                </div>
                <button onclick="openModal('modalSongs')" class="ripple" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);">
                    <i data-lucide="plus" style="width: 16px;"></i> Adicionar
                </button>
            </div>
        </div>

        <!-- Lista de Músicas -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($currentSongs as $index => $song):
                $ordem = $index + 1;
                $duracaoEstimada = 5; // minutos
            ?>
                <div style="background: var(--bg-secondary); border: 1.5px solid var(--border-subtle); border-radius: 16px; overflow: hidden; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.1)'; this.style.borderColor='#3b82f6';" onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)'; this.style.borderColor='var(--border-subtle)';">

                    <!-- Header da Música -->
                    <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); padding: 14px 18px; border-bottom: 1px solid #93c5fd;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <!-- Número da Ordem -->
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1rem; color: white; flex-shrink: 0; box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);">
                                <?= $ordem ?>
                            </div>

                            <!-- Título -->
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="font-size: 1.05rem; font-weight: 800; color: #1e3a8a; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($song['title']) ?>
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Corpo da Música -->
                    <div style="padding: 16px 18px;">
                        <!-- Artista -->
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <i data-lucide="user" style="width: 16px; color: #6b7280;"></i>
                            <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">
                                <?= htmlspecialchars($song['artist'] ?: 'Artista não informado') ?>
                            </span>
                        </div>

                        <!-- Informações -->
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                            <!-- Tom -->
                            <?php if (!empty($song['tone'])): ?>
                                <div style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #fcd34d;">
                                    <i data-lucide="music" style="width: 14px;"></i>
                                    Tom: <?= htmlspecialchars($song['tone']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Duração -->
                            <div style="background: #e0e7ff; color: #3730a3; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #c7d2fe;">
                                <i data-lucide="clock" style="width: 14px;"></i>
                                ~<?= $duracaoEstimada ?>min
                            </div>
                        </div>

                        <!-- Ações -->
                        <div style="display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid var(--border-subtle);">
                            <!-- Link Cifra -->
                            <?php if (!empty($song['link'])): ?>
                                <a href="<?= htmlspecialchars($song['link']) ?>" target="_blank" class="ripple" style="flex: 1; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 10px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='#e5e7eb';" onmouseout="this.style.background='#f3f4f6';">
                                    <i data-lucide="link" style="width: 16px;"></i>
                                    Cifra
                                </a>
                            <?php endif; ?>

                            <!-- Botão Ver Detalhes -->
                            <a href="musica_detalhe.php?id=<?= $song['id'] ?>" class="ripple" style="flex: 1; background: #3b82f6; color: white; border: none; padding: 10px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.3);" onmouseover="this.style.background='#2563eb';" onmouseout="this.style.background='#3b82f6';">
                                <i data-lucide="eye" style="width: 16px;"></i>
                                Ver Detalhes
                            </a>

                            <!-- Botão Remover -->
                            <form method="POST" onsubmit="return confirm('Remover <?= htmlspecialchars($song['title']) ?>?');" style="margin: 0;">
                                <input type="hidden" name="action" value="remove_song">
                                <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                                <input type="hidden" name="current_tab" value="repertorio">
                                <button type="submit" class="ripple" style="background: transparent; border: 1px solid #fca5a5; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #dc2626; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                                    <i data-lucide="trash-2" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Dica de Reordenação -->
        <div style="margin-top: 20px; padding: 14px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 0 10px 10px 0; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="info" style="width: 18px; color: #3b82f6; flex-shrink: 0;"></i>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0; line-height: 1.5;">
                <strong style="color: #1e40af;">Dica:</strong> As músicas são exibidas na ordem em que foram adicionadas.
            </p>
        </div>

    <?php endif; ?>
</div>

<!-- MODAIS -->

<script>
// Função para toggle do menu de ações
function toggleMenu() {
    const menu = document.getElementById('actionMenu');
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(event) {
    const menu = document.getElementById('actionMenu');
    const button = event.target.closest('button[onclick*="toggleMenu"]');
    
    if (!button && menu && !menu.contains(event.target)) {
        menu.style.display = 'none';
    }
});



lucide.createIcons();
</script>

<?php renderAppFooter(); ?>