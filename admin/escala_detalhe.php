<?php
// admin/escala_detalhe.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

// --- LÓGICA DE POST (Adicionar/Remover/Editar/Excluir) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Excluir Escala
        if ($_POST['action'] === 'delete_schedule') {
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: escalas.php");
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
        header("Location: escala_detalhe.php?id=$id");
        exit;
    }
}

// Buscar Detalhes da Escala
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    echo "Escala não encontrada.";
    exit;
}

$date = new DateTime($schedule['event_date']);
$diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Buscar Membros e Músicas
// Buscar MEMBROS add
$stmtUsers = $pdo->prepare("
    SELECT su.*, u.name, u.instrument 
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    WHERE su.schedule_id = ?
    ORDER BY u.name ASC
");
$stmtUsers->execute([$id]);
$team = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Buscar MÚSICAS add
$stmtSongs = $pdo->prepare("
    SELECT ss.*, s.title, s.artist, s.tone, s.category
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    WHERE ss.schedule_id = ?
    ORDER BY ss.position ASC
");
$stmtSongs->execute([$id]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

// Buscar DADOS PARA OS MODAIS (TODOS)
$allUsers = $pdo->query("SELECT id, name, instrument FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

renderAppHeader('Detalhes');
?>

<!-- Compact Header -->
<div style="background: var(--bg-surface); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
        <div style="flex: 1;">
            <a href="escalas.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 8px;">
                <i data-lucide="arrow-left" style="width: 14px;"></i> Voltar
            </a>
            <h1 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($schedule['event_type']) ?></h1>
        </div>
        
        <!-- Actions Menu -->
        <div style="position: relative;">
            <button onclick="toggleOptionsMenu()" id="menuBtn" class="ripple" style="background: var(--bg-body); border: 1px solid var(--border-color); width: 36px; height: 36px; border-radius: 8px; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="more-vertical" style="width: 18px;"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="optionsMenu" style="display: none; position: absolute; top: 42px; right: 0; background: var(--bg-surface); border-radius: 12px; box-shadow: var(--shadow-md); min-width: 180px; z-index: 50; overflow: hidden; border: 1px solid var(--border-color);">
                <button onclick="openModal('modalEditSchedule'); toggleOptionsMenu()" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; font-size: 0.9rem; color: var(--text-main); cursor: pointer; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="edit-2" style="width: 16px;"></i> Editar Detalhes
                </button>
                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');" style="margin: 0;">
                    <input type="hidden" name="action" value="delete_schedule">
                    <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; font-size: 0.9rem; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 10px; border-top: 1px solid var(--border-color);">
                        <i data-lucide="trash-2" style="width: 16px;"></i> Excluir Escala
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Info Row -->
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: var(--text-muted);">
            <i data-lucide="calendar" style="width: 16px;"></i>
            <span><?= $diaSemana ?>, <?= $date->format('d/m/Y') ?></span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; color: var(--text-muted);">
            <i data-lucide="clock" style="width: 16px;"></i>
            <span>19:00</span>
        </div>
        <?php if ($schedule['notes']): ?>
            <div style="flex: 1; min-width: 0;">
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <i data-lucide="info" style="width: 14px; display: inline; vertical-align: text-bottom;"></i>
                    <?= htmlspecialchars(substr($schedule['notes'], 0, 50)) ?><?= strlen($schedule['notes']) > 50 ? '...' : '' ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MAIN GRID LAYOUT -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; align-items: start;">

    <!-- LEFT COLUMN: Observations & Participants -->
    <div style="display: flex; flex-direction: column; gap: 12px;">

        <!-- Observations Card -->
        <?php if (!empty($schedule['notes'])): ?>
            <div style="background: var(--bg-surface); border-radius: var(--radius-md); padding: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 0.8rem; font-weight: 700; color: var(--text-main); margin: 0 0 8px 0;">Observações</h3>
                <p style="margin: 0; font-size: 0.85rem; line-height: 1.5; color: var(--text-muted);"><?= nl2br(htmlspecialchars($schedule['notes'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- Participants Card -->
        <div style="background: var(--bg-surface); border-radius: var(--radius-md); padding: 14px; position: relative; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);">Participantes <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= count($team) ?>)</span></h3>
                <button onclick="openModal('modalMembers')" style="
                        background: var(--bg-body); color: var(--text-main); border: 1px solid var(--border-color); 
                        padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.75rem; cursor: pointer;
                        transition: all 0.2s;
                    ">
                    + Editar
                </button>
            </div>

            <?php if (empty($team)): ?>
                <div style="text-align: center; padding: 12px; background: var(--bg-body); border-radius: 8px; border: 1px dashed var(--border-color);">
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">Ninguém escalado ainda.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($team as $member): ?>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 4px 0;">
                            <div style="
                                    width: 36px; height: 36px; 
                                    background: #eff6ff; color: #2563eb; border-radius: 50%; 
                                    display: flex; align-items: center; justify-content: center; 
                                    font-weight: 700; font-size: 0.85rem;
                                    flex-shrink: 0; border: 1px solid #dbeafe;
                                ">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-main); font-size: 0.85rem;"><?= htmlspecialchars($member['name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($member['instrument'] ?? 'Vocal') ?></div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Remover membro?');" style="margin: 0;">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 6px; border-radius: 6px; transition: background 0.2s;">
                                    <i data-lucide="x" style="width: 18px;"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT COLUMN: Roteiro (Songs) -->
    <div style="background: var(--bg-surface); border-radius: var(--radius-md); padding: 14px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h3 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);">Roteiro <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">(<?= count($songs) ?>)</span></h3>
            <button onclick="openModal('modalSongs')" style="
                    background: #0f172a; color: white; border: none; 
                    padding: 6px 12px; border-radius: 12px; font-weight: 600; font-size: 0.75rem; cursor: pointer;
                    display: flex; align-items: center; gap: 4px; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1);
                ">
                <i data-lucide="music" style="width: 12px;"></i> + Música
            </button>
        </div>

        <?php if (empty($songs)): ?>
            <div style="text-align: center; padding: 20px 12px; border: 1px dashed var(--border-color); border-radius: 8px; background: var(--bg-body);">
                <i data-lucide="music-2" style="width: 24px; height: 24px; color: var(--text-muted); margin-bottom: 6px;"></i>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">Nenhuma música selecionada.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($songs as $index => $song): ?>
                    <div style="display: flex; align-items: flex-start; gap: 10px; position: relative;">
                        <div style="
                                font-size: 1.1rem; font-weight: 800; color: #e2e8f0; 
                                width: 24px; text-align: center; line-height: 1; margin-top: 2px;
                            ">
                            <?= $index + 1 ?>ª
                        </div>
                        <div style="flex: 1; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h4 style="margin: 0 0 4px 0; color: #0f172a; font-size: 1rem; font-weight: 700;"><?= htmlspecialchars($song['title']) ?></h4>
                                <!-- Actions for Song -->
                                <form method="POST" onsubmit="return confirm('Remover música?');" style="margin: 0;">
                                    <input type="hidden" name="action" value="remove_song">
                                    <input type="hidden" name="song_id" value="<?= $song['song_id'] ?>">
                                    <button type="submit" style="background: none; border: none; color: #94a3b8; cursor: pointer; opacity: 0.6; padding: 4px;">
                                        <i data-lucide="trash-2" style="width: 16px;"></i>
                                    </button>
                                </form>
                            </div>

                            <p style="margin: 0; color: #64748b; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                                <?= htmlspecialchars($song['artist']) ?>
                            </p>

                            <div style="margin-top: 10px; display: flex; gap: 8px;">
                                <?php if ($song['tone']): ?>
                                    <span style="
                                            background: #fff7ed; color: #ea580c; 
                                            padding: 4px 10px; border-radius: 6px; 
                                            font-size: 0.75rem; font-weight: 700; border: 1px solid #ffedd5;
                                        ">TOM: <?= $song['key_semitone'] ?? $song['tone'] ?></span>
                                <?php endif; ?>

                                <a href="https://www.youtube.com/results?search_query=<?= urlencode($song['title'] . ' ' . $song['artist']) ?>" target="_blank" style="
                                        background: #fef2f2; color: #ef4444; text-decoration: none;
                                        padding: 4px 10px; border-radius: 6px; 
                                        font-size: 0.75rem; font-weight: 700; border: 1px solid #fee2e2;
                                        display: flex; align-items: center; gap: 4px;
                                        transition: background 0.2s;
                                     ">
                                    <i data-lucide="youtube" style="width: 12px;"></i> YouTube
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- AREA DE MODAIS -->

<!-- MODAL DE MEMBROS -->
<div id="modalMembers" class="bottom-sheet-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: flex-end; justify-content: center;">
    <div class="bottom-sheet-content" style="background: white; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0; padding: 24px; max-height: 80vh; overflow-y: auto; animation: slideUp 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Adicionar à Equipe</h3>
            <button onclick="closeSheet('modalMembers')" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_members">
            <div style="display: flex; flex-direction: column; gap: 8px; max-height: 50vh; overflow-y: auto;">
                <?php foreach ($allUsers as $user):
                    // Pular se já estiver na escala
                    $isAlreadyIn = false;
                    foreach ($team as $tm) {
                        if ($tm['user_id'] == $user['id']) $isAlreadyIn = true;
                    } // Ajuste na comparação array
                    if ($isAlreadyIn) continue;
                ?>
                    <label style="display: flex; align-items: center; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer;">
                        <input type="checkbox" name="users[]" value="<?= $user['id'] ?>" style="transform: scale(1.3); margin-right: 16px; accent-color: #166534;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($user['name']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($user['instrument'] ?: 'Sem inst.') ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeSheet('modalMembers')" style="flex: 1; padding: 12px; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 12px; font-weight: 600;">Cancelar</button>
                <button type="submit" style="flex: 1; padding: 12px; background: #166534; border: none; color: white; border-radius: 12px; font-weight: 600;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE MÚSICAS -->
<div id="modalSongs" class="bottom-sheet-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: flex-end; justify-content: center;">
    <div class="bottom-sheet-content" style="background: white; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0; padding: 24px; max-height: 80vh; overflow-y: auto; animation: slideUp 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Adicionar Músicas</h3>
            <button onclick="closeSheet('modalSongs')" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_songs">
            <div style="display: flex; flex-direction: column; gap: 8px; max-height: 50vh; overflow-y: auto;">
                <?php foreach ($allSongs as $song):
                    // Pular se já estiver na escala
                    $isAlreadyIn = false;
                    foreach ($songs as $s) {
                        if ($s['song_id'] == $song['id']) $isAlreadyIn = true;
                    }
                    if ($isAlreadyIn) continue;
                ?>
                    <label style="display: flex; align-items: center; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer;">
                        <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>" style="transform: scale(1.3); margin-right: 16px; accent-color: #166534;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="button" onclick="closeSheet('modalSongs')" style="flex: 1; padding: 12px; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 12px; font-weight: 600;">Cancelar</button>
                <button type="submit" style="flex: 1; padding: 12px; background: #166534; border: none; color: white; border-radius: 12px; font-weight: 600;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Edição -->
<div id="modalEditSchedule" class="bottom-sheet-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: flex-end; justify-content: center;">
    <div class="bottom-sheet-content" style="background: white; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0; padding: 24px; max-height: 80vh; overflow-y: auto; animation: slideUp 0.3s ease;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 1.2rem; color: #1e293b;">Editar Escala</h3>
            <button onclick="closeSheet('modalEditSchedule')" style="background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="edit_schedule">

            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Data do Evento</label>
                <input type="date" name="event_date" value="<?= $schedule['event_date'] ?>" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Tipo de Evento</label>
                <select name="event_type" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px;">
                    <option value="Culto Domingo a Noite" <?= $schedule['event_type'] === 'Culto Domingo a Noite' ? 'selected' : '' ?>>Culto Domingo a Noite</option>
                    <option value="Culto Tema Especial" <?= $schedule['event_type'] === 'Culto Tema Especial' ? 'selected' : '' ?>>Culto Tema Especial</option>
                    <option value="Ensaio" <?= $schedule['event_type'] === 'Ensaio' ? 'selected' : '' ?>>Ensaio</option>
                    <option value="Outro" <?= $schedule['event_type'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
                </select>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Observações</label>
                <textarea name="notes" rows="4" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; resize: none;"><?= htmlspecialchars($schedule['notes']) ?></textarea>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeSheet('modalEditSchedule')" style="flex: 1; padding: 12px; background: white; border: 1px solid #d1d5db; color: #374151; border-radius: 12px; font-weight: 600;">Cancelar</button>
                <button type="submit" style="flex: 1; padding: 12px; background: #166534; border: none; color: white; border-radius: 12px; font-weight: 600;">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes slideUp {
        from {
            transform: translateY(100%);
        }

        to {
            transform: translateY(0);
        }
    }
</style>

<script>
    function toggleOptionsMenu() {
        var menu = document.getElementById('optionsMenu');
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    }

    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        var menu = document.getElementById('optionsMenu');
        var btn = document.getElementById('menuBtn');
        if (menu.style.display === 'block' && !menu.contains(e.target) && !btn.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    function openModal(id) {
        var modal = document.getElementById(id);
        modal.style.display = 'flex';
    }

    function closeSheet(id) {
        var modal = document.getElementById(id);
        modal.style.display = 'none';
    }
</script>

<?php renderAppFooter(); ?>