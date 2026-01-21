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

renderAppHeader('Detalhes da Escala');
?>

<!-- Header Clean (Estilo LouveApp) -->
<header style="
    background: white; 
    padding: 20px 24px; 
    border-bottom: 1px solid #e2e8f0; 
    margin: -16px -16px 24px -16px; 
    display: flex; 
    align-items: center; 
    justify-content: space-between;
    position: sticky; top: 0; z-index: 20;
">
    <a href="escalas.php" class="ripple" style="
        width: 40px; height: 40px; 
        display: flex; align-items: center; justify-content: center; 
        text-decoration: none; color: #64748b; 
        border-radius: 50%;
        transition: background 0.2s;
    " onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
        <i data-lucide="arrow-left"></i>
    </a>

    <div style="text-align: center;">
        <h1 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b;"><?= htmlspecialchars($schedule['event_type']) ?></h1>
        <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">
            <?= $diaSemana ?>, <?= $date->format('d/m') ?>
        </p>
    </div>

    <!-- Botão de Opções (Editar/Excluir) -->
    <div style="position: relative;">
        <button onclick="toggleOptionsMenu()" id="menuBtn" class="ripple" style="
            width: 40px; height: 40px; 
            background: transparent; border: none; 
            color: #64748b; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
        ">
            <i data-lucide="more-vertical"></i>
        </button>
        <!-- Dropdown Menu -->
        <div id="optionsMenu" style="
            display: none; position: absolute; top: 48px; right: 0; 
            background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
            min-width: 160px; z-index: 50; overflow: hidden; border: 1px solid #e2e8f0;
        ">
            <button onclick="openModal('modalEditSchedule'); toggleOptionsMenu()" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; font-size: 0.9rem; color: #334155; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="edit-2" style="width: 16px;"></i> Editar
            </button>
            <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');" style="margin: 0;">
                <input type="hidden" name="action" value="delete_schedule">
                <button type="submit" style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; font-size: 0.9rem; color: #ef4444; cursor: pointer; display: flex; align-items: center; gap: 8px; border-top: 1px solid #f1f5f9;">
                    <i data-lucide="trash-2" style="width: 16px;"></i> Excluir
                </button>
            </form>
        </div>
    </div>
</header>

<div style="max-width: 900px; margin: 0 auto; padding: 0 16px;">

    <!-- Resumo (Cards Coloridos Compactos) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px;">
        <div style="background: #eff6ff; padding: 16px; border-radius: 12px; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #3b82f6;"><?= count($team) ?></div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #1e40af; opacity: 0.8;">Membros</div>
        </div>
        <div style="background: #fdf2f8; padding: 16px; border-radius: 12px; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 800; color: #ec4899;"><?= count($songs) ?></div>
            <div style="font-size: 0.8rem; font-weight: 600; color: #9d174d; opacity: 0.8;">Músicas</div>
        </div>
    </div>

    <!-- Seção: Equipe -->
    <div style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1rem; font-weight: 700; color: #334155; margin: 0;">Equipe Escalada</h2>
            <button onclick="openModal('modalMembers')" style="color: #166534; font-weight: 600; background: none; border: none; font-size: 0.9rem; cursor: pointer;">
                + Adicionar
            </button>
        </div>

        <?php if (empty($team)): ?>
            <div style="text-align: center; padding: 32px; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <p style="color: #94a3b8; font-size: 0.9rem;">Nenhum membro escalado.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($team as $member): ?>
                    <div style="display: flex; align-items: center; gap: 12px; background: white; padding: 12px; border-radius: 12px; border: 1px solid #f1f5f9; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; background: #dcfce7; color: #166534; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($member['name']) ?></div>
                                <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($member['instrument'] ?? 'Vocal') ?></div>
                            </div>
                        </div>
                        <form method="POST" onsubmit="return confirm('Remover membro?');" style="margin: 0;">
                            <input type="hidden" name="action" value="remove_member">
                            <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>"> <!-- Corrigido para user_id se tabela pivot -->
                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 8px;">
                                <i data-lucide="minus-circle" style="width: 18px;"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seção: Repertório -->
    <div style="margin-bottom: 100px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1rem; font-weight: 700; color: #334155; margin: 0;">Repertório</h2>
            <button onclick="openModal('modalSongs')" style="color: #166534; font-weight: 600; background: none; border: none; font-size: 0.9rem; cursor: pointer;">
                + Músicas
            </button>
        </div>

        <?php if (empty($songs)): ?>
            <div style="text-align: center; padding: 32px; background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <p style="color: #94a3b8; font-size: 0.9rem;">Nenhuma música selecionada.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($songs as $song): ?>
                    <div style="display: flex; align-items: center; gap: 12px; background: white; padding: 12px; border-radius: 12px; border: 1px solid #f1f5f9;">
                        <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #64748b; font-weight: 700; font-size: 0.8rem;">
                            <?= $song['order_index'] ?? ($song['position'] + 1) ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($song['artist']) ?> • Tom: <span style="color: #d97706; font-weight: 600;"><?= $song['key_semitone'] ?? $song['tone'] ?></span></div>
                        </div>

                        <form method="POST" onsubmit="return confirm('Remover música?');" style="margin: 0;">
                            <input type="hidden" name="action" value="remove_song">
                            <input type="hidden" name="song_id" value="<?= $song['song_id'] ?>"> <!-- Ajustado para song_id da pivot table -->
                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 8px;">
                                <i data-lucide="trash-2" style="width: 18px;"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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