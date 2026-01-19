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

<!-- Header já removido a favor do Global, mas o Título do Evento deve aparecer no Card de Detalhes -->

<!-- Navegação por Abas -->
<div class="tabs-nav" style="margin-top: 20px;">
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
    $timeStr = '19:00';

    // Agrupar equipe por instrumento
    $teamGrouped = [];
    foreach ($currentMembers as $m) {
        $inst = $m['instrument'] ?: 'Voz'; // Default
        $teamGrouped[$inst][] = $m;
    }
    ksort($teamGrouped);
    ?>

    <div class="card-clean" style="padding: 0; overflow: hidden; border-radius: 20px; box-shadow: var(--shadow-md);">

        <!-- Top Banner / Header -->
        <div style="background: var(--bg-secondary); padding: 24px; border-bottom: 1px solid var(--border-subtle);">
            <div style="display: flex; gap: 16px; align-items: flex-start;">
                <!-- Date Badge -->
                <div style="background: white; border: 2px solid var(--primary-green); border-radius: 16px; padding: 10px; min-width: 70px; text-align: center; box-shadow: var(--shadow-sm); flex-shrink: 0;">
                    <span style="display: block; font-size: 0.8rem; font-weight: 800; color: var(--primary-green); text-transform: uppercase;"><?= $monthStr ?></span>
                    <span style="display: block; font-size: 1.8rem; font-weight: 800; color: var(--text-primary); line-height: 1; margin-top: 2px;"><?= $dayNum ?></span>
                </div>

                <div>
                    <span style="font-size: 0.8rem; font-weight: 700; color: var(--accent-interactive); letter-spacing: 0.5px; text-transform: uppercase; display: block; margin-bottom: 4px;"><?= $dayName ?> • <?= $timeStr ?></span>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin: 0;"><?= htmlspecialchars($schedule['event_type']) ?></h2>
                </div>
            </div>

            <!-- Observations -->
            <?php if (!empty($schedule['notes'])): ?>
                <div style="margin-top: 20px; background: #FEF9C3; border: 1px solid #FEF08A; padding: 12px 16px; border-radius: 12px; color: #854D0E; font-size: 0.95rem; line-height: 1.5; display: flex; gap: 10px;">
                    <i data-lucide="info" style="width: 18px; flex-shrink: 0; margin-top: 2px;"></i>
                    <div><?= nl2br(htmlspecialchars($schedule['notes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div style="padding: 24px;">

            <!-- Equipe Grouped -->
            <div style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="users" style="width: 20px; color: var(--primary-green);"></i> Equipe
                    </h3>
                    <button onclick="openTab('equipe')" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-interactive); background: none; border: none;">Gerenciar</button>
                </div>

                <?php if (empty($teamGrouped)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Ninguém escalado.</p>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <?php foreach ($teamGrouped as $instrument => $members): ?>
                            <div>
                                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;"><?= htmlspecialchars($instrument) ?></label>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <?php foreach ($members as $m): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 24px; height: 24px; background: var(--bg-tertiary); border-radius: 50%; color: var(--text-primary); font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                                                <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                            </div>
                                            <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars(explode(' ', $m['name'])[0]) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Músicas -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="music" style="width: 20px; color: var(--primary-green);"></i> Músicas
                    </h3>
                    <button onclick="openTab('repertorio')" style="font-size: 0.85rem; font-weight: 600; color: var(--accent-interactive); background: none; border: none;">Ver todas</button>
                </div>

                <?php if (empty($currentSongs)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhuma música.</p>
                <?php else: ?>
                    <div style="background: var(--bg-tertiary); border-radius: 16px; overflow: hidden; border: 1px solid var(--border-subtle);">
                        <?php foreach ($currentSongs as $index => $s): ?>
                            <div style="padding: 12px 16px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid var(--border-subtle);">
                                <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); width: 20px;"><?= $index + 1 ?></span>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-primary);"><?= htmlspecialchars($s['title']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($s['artist']) ?></div>
                                </div>
                                <?php if ($s['tone']): ?>
                                    <span style="background: var(--bg-primary); padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; color: var(--text-primary); border: 1px solid var(--border-subtle); box-shadow: var(--shadow-sm);"><?= htmlspecialchars($s['tone']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Botões de Ação -->
        <div style="padding: 20px 24px; background: var(--bg-secondary); border-top: 1px solid var(--border-subtle); display: flex; gap: 12px;">
            <!-- Botão Amarelo Solicitado -->
            <button onclick="openEditModal()" class="ripple" style="
                flex: 1;
                background: #F59E0B; 
                color: white; 
                border: none; 
                border-radius: 12px; 
                padding: 14px; 
                font-weight: 600; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                gap: 8px; 
                box-shadow: 0 4px 6px rgba(245, 158, 11, 0.2);
             ">
                <i data-lucide="edit-3" style="width: 18px;"></i> Editar Informações
            </button>

            <form method="POST" onsubmit="return confirm('Excluir esta escala?')" style="margin: 0;">
                <input type="hidden" name="action" value="delete_schedule">
                <button type="submit" class="ripple" style="
                    background: #FEE2E2; 
                    color: #DC2626; 
                    border: none; 
                    width: 50px; 
                    height: 50px; 
                    border-radius: 12px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    cursor: pointer;
                ">
                    <i data-lucide="trash-2" style="width: 20px;"></i>
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
            <button onclick="openModal('modalMembers')" class="btn-primary ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membro
            </button>
        </div>
    <?php else: ?>
        <button onclick="openModal('modalMembers')" class="btn-primary ripple w-full" style="margin-bottom: 16px;">
            <i data-lucide="plus"></i> Gerenciar Equipe
        </button>
        <div class="card-clean" style="padding: 0; overflow: hidden;">
            <?php foreach ($currentMembers as $member): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="table-avatar"><?= strtoupper(substr($member['name'], 0, 1)) ?></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($member['name']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($member['instrument'] ?: 'Voz') ?></div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Remover membro?');" style="margin: 0;">
                        <input type="hidden" name="action" value="remove_member">
                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                        <input type="hidden" name="current_tab" value="equipe">
                        <button type="submit" class="btn-icon" style="color: var(--status-error);"><i data-lucide="trash-2" style="width: 18px;"></i></button>
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
            <button onclick="openModal('modalSongs')" class="btn-primary ripple">
                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Música
            </button>
        </div>
    <?php else: ?>
        <button onclick="openModal('modalSongs')" class="btn-primary ripple w-full" style="margin-bottom: 16px;">
            <i data-lucide="plus"></i> Adicionar Música
        </button>
        <div class="card-clean" style="padding: 0; overflow: hidden;">
            <?php foreach ($currentSongs as $index => $song): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid var(--border-subtle);">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span style="font-size: 1.2rem; font-weight: 700; color: var(--text-muted); width: 24px; text-align: center;"><?= $index + 1 ?></span>
                        <div>
                            <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                <?= htmlspecialchars($song['artist']) ?> • <span style="font-weight: 700; color: var(--accent-interactive);"><?= htmlspecialchars($song['tone']) ?></span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Remover música?');" style="margin: 0;">
                        <input type="hidden" name="action" value="remove_song">
                        <input type="hidden" name="song_id" value="<?= $song['id'] ?>">
                        <input type="hidden" name="current_tab" value="repertorio">
                        <button type="submit" class="btn-icon" style="color: var(--status-error);"><i data-lucide="trash-2" style="width: 18px;"></i></button>
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
                <button type="submit" class="btn-primary ripple" style="flex: 1; justify-content: center;">Salvar</button>
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
                <button type="submit" class="btn-primary ripple" style="flex: 1; justify-content: center;">Salvar</button>
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
                <button type="submit" class="btn-primary ripple" style="flex: 1; justify-content: center;">Salvar</button>
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