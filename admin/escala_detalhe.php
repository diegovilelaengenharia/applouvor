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

        <div onclick="openSheet('sheet-perfil')" class="ripple" style="
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: rgba(255,255,255,0.2); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            overflow: hidden; 
            cursor: pointer;
            border: 2px solid rgba(255,255,255,0.3);
        ">
            <?php if (!empty($_SESSION['user_avatar'])): ?>
                <img src="../assets/uploads/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <span style="font-weight: 700; font-size: 0.9rem; color: white;">
                    <?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 style="color: white; margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px;">Detalhes da Escala</h1>
            <p style="color: rgba(255,255,255,0.9); margin-top: 4px; font-weight: 500; font-size: 0.95rem;">Louvor PIB Oliveira</p>
        </div>
    </div>
</div>

<!-- Navegação por Abas -->
<div class="tabs-nav" style="margin-top: -30px; position: relative; z-index: 10; padding: 6px; background: var(--bg-tertiary); box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    <button class="tab-btn <?= $activeTab === 'detalhes' ? 'active' : '' ?>" onclick="openTab('detalhes')">Detalhes</button>
    <button class="tab-btn <?= $activeTab === 'equipe' ? 'active' : '' ?>" onclick="openTab('equipe')">Equipe</button>
    <button class="tab-btn <?= $activeTab === 'repertorio' ? 'active' : '' ?>" onclick="openTab('repertorio')">Músicas</button>
</div>

<!-- CONTEÚDO: DETALHES -->
<div id="detalhes" class="tab-content <?= $activeTab === 'detalhes' ? 'active' : '' ?>">
    <!-- Lógica de Agrupamento e Data -->
    <?php
    $d = new DateTime($schedule['event_date']);
    $dayNum = $d->format('d');
    $monthStr = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'][(int)$d->format('m') - 1];
    $timeStr = '19:00'; // Poderia vir do banco se tivesse campo hora

    // Agrupar equipe por instrumento
    $teamGrouped = [];
    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz';
        $teamGrouped[$inst][] = $m;
    }
    ksort($teamGrouped);
    ?>

    <div class="card-clean" style="padding: 0; overflow: hidden; border-radius: 24px; box-shadow: var(--shadow-lg); border: none; background: var(--bg-secondary);">

        <!-- Modern Header Region -->
        <div style="background: linear-gradient(to bottom, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding: 32px 24px 28px; border-bottom: 1px solid var(--border-subtle);">
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <!-- Modern Date Badge -->
                <div style="
                    background: var(--bg-secondary); 
                    border: 1px solid var(--border-subtle);
                    border-radius: 18px; 
                    padding: 8px 0; 
                    width: 76px;
                    text-align: center; 
                    box-shadow: var(--shadow-md); 
                    flex-shrink: 0;
                    display: flex; flex-direction: column; justify-content: center;
                    position: relative;
                    overflow: hidden;
                ">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--primary-green);"></div>
                    <span style="font-size: 0.8rem; font-weight: 800; color: var(--primary-green); text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;"><?= $monthStr ?></span>
                    <span style="font-size: 2rem; font-weight: 900; color: var(--text-primary); line-height: 1; letter-spacing: -1px; margin-bottom: 4px;"><?= $dayNum ?></span>
                </div>

                <div style="flex: 1; padding-top: 2px;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="
                            font-size: 0.7rem; 
                            font-weight: 700; 
                            color: var(--primary-green); 
                            background: rgba(16, 185, 129, 0.1); 
                            padding: 4px 10px; 
                            border-radius: 50px;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            display: flex; align-items: center; gap: 4px;
                        ">
                            <i data-lucide="calendar" style="width: 12px;"></i> <?= $dayName ?>
                        </span>
                        <span style="
                            font-size: 0.7rem; 
                            font-weight: 700; 
                            color: var(--text-secondary); 
                            background: var(--bg-tertiary); 
                            padding: 4px 10px; 
                            border-radius: 50px;
                            display: flex; align-items: center; gap: 4px;
                        ">
                            <i data-lucide="clock" style="width: 12px;"></i> <?= $timeStr ?>
                        </span>
                    </div>
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin: 0; letter-spacing: -0.5px;">
                        <?= htmlspecialchars($schedule['event_type']) ?>
                    </h2>
                </div>
            </div>

            <!-- Observations Stylized -->
            <?php if (!empty($schedule['notes'])): ?>
                <div style="margin-top: 24px; position: relative;">
                    <div style="
                        background: rgba(250, 204, 21, 0.1); 
                        border-left: 3px solid #FACC15; 
                        padding: 16px; 
                        border-radius: 0 12px 12px 0; 
                        color: var(--text-primary);
                        font-size: 0.95rem; 
                        line-height: 1.6;
                        display: flex; gap: 12px;
                    ">
                        <i data-lucide="info" style="color: #EAB308; width: 20px; flex-shrink: 0; margin-top: 2px;"></i>
                        <div style="color: var(--text-secondary);">
                            <span style="font-weight: 700; color: #EAB308; display: block; margin-bottom: 4px; font-size: 0.8rem; text-transform: uppercase;">Observações</span>
                            <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>


        <!-- Botões de Ação Modernos -->
        <div style="
            padding: 24px; 
            background: var(--bg-secondary); 
            border-top: 1px solid var(--border-subtle); 
            display: flex; 
            gap: 16px;
        ">
            <button onclick="openEditModal()" class="ripple" style="
                flex: 0 0 auto;
                width: 60px;
                background: #FFC107; 
                color: white; 
                border: none; 
                border-radius: 16px; 
                padding: 16px; 
                font-weight: 700; 
                cursor: pointer; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
                transition: transform 0.2s;
             ">
                <i data-lucide="edit-3" style="width: 24px;"></i>
            </button>

            <form method="POST" onsubmit="return confirm('Excluir esta escala?')" style="margin: 0; flex: 1;">
                <input type="hidden" name="action" value="delete_schedule">
                <button type="submit" class="ripple" style="
                    width: 100%;
                    background: #DC3545;
                    color: white;
                    border: none; 
                    padding: 18px; 
                    border-radius: 16px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    gap: 10px;
                    cursor: pointer;
                    font-weight: 700;
                    font-size: 1.05rem;
                    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
                    transition: all 0.2s;
                ">
                    <i data-lucide="trash-2" style="width: 22px;"></i> Excluir Escala
                </button>
            </form>
        </div>

    </div>
</div>

<!-- CONTEÚDO: EQUIPE -->
<div id="equipe" class="tab-content <?= $activeTab === 'equipe' ? 'active' : '' ?>">
    <?php if (empty($currentMembers)): ?>
        <div class="empty-state">
            <div style="background: var(--bg-tertiary); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="users" style="color: var(--text-muted); width: 32px; height: 32px;"></i>
            </div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Equipe Vazia</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px;">Adicione instrumentistas.</p>
            <button onclick="openModal('modalMembers')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membro
            </button>
        </div>
    <?php else: ?>
        <button onclick="openModal('modalMembers')" class="btn-action-add ripple w-full" style="margin-bottom: 16px;">
            <i data-lucide="plus"></i> Gerenciar Equipe
        </button>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($currentMembers as $member): ?>
                <div style="
                    background: var(--bg-secondary);
                    border: 1px solid var(--border-subtle);
                    border-left: 4px solid #10B981;
                    border-radius: 12px;
                    padding: 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: var(--shadow-sm);
                    transition: all 0.2s;
                ">
                    <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                        <div style="
                            width: 48px;
                            height: 48px;
                            border-radius: 50%;
                            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: 800;
                            font-size: 1.1rem;
                            color: #047857;
                            flex-shrink: 0;
                        "><?= strtoupper(substr($member['name'], 0, 1)) ?></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 2px;"><?= htmlspecialchars($member['name']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;"><?= htmlspecialchars($member['instrument'] ?: 'Voz') ?></div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Remover membro?');" style="margin: 0;">
                        <input type="hidden" name="action" value="remove_member">
                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                        <input type="hidden" name="current_tab" value="equipe">
                        <button type="submit" class="ripple" style="
                            background: transparent;
                            border: none;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: var(--status-error);
                            cursor: pointer;
                            transition: all 0.2s;
                        "><i data-lucide="trash-2" style="width: 20px;"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- CONTEÚDO: REPERTÓRIO -->
<div id="repertorio" class="tab-content <?= $activeTab === 'repertorio' ? 'active' : '' ?>">
    <?php if (empty($currentSongs)): ?>
        <div class="empty-state">
            <div style="background: var(--bg-tertiary); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i data-lucide="music" style="color: var(--text-muted); width: 32px; height: 32px;"></i>
            </div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Repertório Vazio</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px;">Selecione as músicas.</p>
            <button onclick="openModal('modalSongs')" class="btn-action-add ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Música
            </button>
        </div>
    <?php else: ?>
        <button onclick="openModal('modalSongs')" class="btn-action-add ripple w-full" style="margin-bottom: 16px;">
            <i data-lucide="plus"></i> Adicionar Música
        </button>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($currentSongs as $index => $song): ?>
                <div style="
                    background: var(--bg-secondary);
                    border: 1px solid var(--border-subtle);
                    border-left: 4px solid #3B82F6;
                    border-radius: 12px;
                    padding: 16px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    box-shadow: var(--shadow-sm);
                    transition: all 0.2s;
                ">
                    <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
                        <div style="
                            width: 48px;
                            height: 48px;
                            border-radius: 50%;
                            background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: 800;
                            font-size: 1.1rem;
                            color: #1D4ED8;
                            flex-shrink: 0;
                        "><?= $index + 1 ?></div>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 2px;"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500;">
                                <?= htmlspecialchars($song['artist']) ?> • <span style="font-weight: 700; color: #3B82F6;"><?= htmlspecialchars($song['tone']) ?></span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Remover música?');" style="margin: 0;">
                        <input type="hidden" name="action" value="remove_song">
                        <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                        <input type="hidden" name="current_tab" value="repertorio">
                        <button type="submit" class="ripple" style="
                            background: transparent;
                            border: none;
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: var(--status-error);
                            cursor: pointer;
                            transition: all 0.2s;
                        "><i data-lucide="trash-2" style="width: 20px;"></i></button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DE MEMBROS -->
<div id="modalMembers" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Adicionar à Equipe</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_members">
            <input type="hidden" name="current_tab" value="equipe">
            <div class="checkbox-list">
                <?php foreach ($allUsers as $user):
                    // Pular se já estiver na escala
                    $isAlreadyIn = false;
                    foreach ($currentMembers as $cm) {
                        if ($cm['id'] == $user['id']) $isAlreadyIn = true;
                    }
                    if ($isAlreadyIn) continue;
                ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="users[]" value="<?= $user['id'] ?>">
                        <div class="checkbox-info">
                            <div class="checkbox-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="checkbox-sub"><?= htmlspecialchars($user['instrument'] ?: 'Sem inst.') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeSheet('modalMembers')" class="btn-outline ripple" style="flex: 1; justify-content: center;">Cancelar</button>
                <button type="submit" class="btn-action-save ripple" style="flex: 1; justify-content: center;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE MÚSICAS -->
<div id="modalSongs" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Adicionar Músicas</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_songs">
            <input type="hidden" name="current_tab" value="repertorio">
            <div class="checkbox-list">
                <?php foreach ($allSongs as $song):
                    // Pular se já estiver na escala (opcional, mas comum para não repetir)
                    $isAlreadyIn = false;
                    foreach ($currentSongs as $cs) {
                        if ($cs['id'] == $song['id']) $isAlreadyIn = true;
                    }
                    if ($isAlreadyIn) continue;
                ?>
                    <label class="checkbox-item">
                        <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>">
                        <div class="checkbox-info">
                            <div class="checkbox-name"><?= htmlspecialchars($song['title']) ?></div>
                            <div class="checkbox-sub"><?= htmlspecialchars($song['artist']) ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeSheet('modalSongs')" class="btn-outline ripple" style="flex: 1; justify-content: center;">Cancelar</button>
                <button type="submit" class="btn-action-save ripple" style="flex: 1; justify-content: center;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Edição -->
<div id="modalEditSchedule" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Editar Escala</div>
        <form method="POST">
            <input type="hidden" name="action" value="edit_schedule">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Data do Evento</label>
                <input type="date" name="event_date" class="form-input" value="<?= $schedule['event_date'] ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label">Tipo de Evento</label>
                <select name="event_type" class="form-input" required>
                    <option value="Culto Domingo a Noite" <?= $schedule['event_type'] === 'Culto Domingo a Noite' ? 'selected' : '' ?>>Culto Domingo a Noite</option>
                    <option value="Culto Tema Especial" <?= $schedule['event_type'] === 'Culto Tema Especial' ? 'selected' : '' ?>>Culto Tema Especial</option>
                    <option value="Ensaio" <?= $schedule['event_type'] === 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                    <option value="Outro" <?= $schedule['event_type'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Observações</label>
                <textarea name="notes" class="form-input" rows="4" style="resize: none;"><?= htmlspecialchars($schedule['notes']) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeSheet('modalEditSchedule')" class="btn-outline ripple" style="flex: 1; justify-content: center;">Cancelar</button>
                <button type="submit" class="btn-action-save ripple" style="flex: 1; justify-content: center;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleActionsMenu() {
        document.getElementById('actionsMenu').classList.toggle('active');
    }

    function openEditModal() {
        document.getElementById('actionsMenu').classList.remove('active');
        document.getElementById('modalEditSchedule').classList.add('active');
    }

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('actionsMenu');
        const btn = document.getElementById('actionsMenuBtn');
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    function openTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        // Simple logic to highlight button, works because onclick passes ID
        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(btn => {
            if (btn.getAttribute('onclick').includes(tabId)) btn.classList.add('active');
        });
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeSheet(id) {
        document.getElementById(id).classList.remove('active');
    }
</script>

<?php renderAppFooter(); ?>