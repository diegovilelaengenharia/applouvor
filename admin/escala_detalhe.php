<?php
// admin/escala_detalhe.php - Versão Otimizada
require_once '../includes/db.php';
require_once '../includes/layout.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

// --- Processamento do Salvamento em Lote ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // EXCLUSÃO
    if (isset($_POST['delete_schedule']) && $_SESSION['user_role'] === 'admin') {
        try {
            $pdo->beginTransaction();
            
            // Remover dependências (se não houver CASCADE)
            $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
            
            // Remover a escala
            $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$id]);
            
            $pdo->commit();
            header("Location: escalas.php?msg=deleted");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao excluir: " . $e->getMessage());
        }
    }

    if (isset($_POST['save_changes'])) {
    
        try {
            $pdo->beginTransaction();
            
            // 0. Atualizar Dados da Escala (Nome, Data, Hora, Notas)
            if (isset($_POST['event_type']) && isset($_POST['event_date']) && isset($_POST['event_time'])) {
                $notes = $_POST['notes'] ?? ''; // Pegar notas se existir
                $stmtUpdateSchedule = $pdo->prepare("UPDATE schedules SET event_type = ?, event_date = ?, event_time = ?, notes = ? WHERE id = ?");
                $stmtUpdateSchedule->execute([$_POST['event_type'], $_POST['event_date'], $_POST['event_time'], $notes, $id]);
            }
            
            // 1. Atualizar Membros
            // Remover todos os atuais
            $stmtDelMembers = $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?");
            $stmtDelMembers->execute([$id]);
            
            // Inserir os novos
            if (isset($_POST['members']) && is_array($_POST['members'])) {
                $stmtAddMember = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id) VALUES (?, ?)");
                foreach ($_POST['members'] as $userId) {
                    $stmtAddMember->execute([$id, $userId]);
                }
            }
            
            // 2. Atualizar Músicas
            // Remover todas as atuais
            $stmtDelSongs = $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?");
            $stmtDelSongs->execute([$id]);
            
            // Inserir as novas com posição
            if (isset($_POST['songs']) && is_array($_POST['songs'])) {
                $stmtAddSong = $pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, position) VALUES (?, ?, ?)");
                foreach ($_POST['songs'] as $pos => $songId) {
                    $stmtAddSong->execute([$id, $songId, $pos + 1]);
                }
            }
            
            $pdo->commit();
            
            // Redirecionar para limpar o POST e mostrar sucesso
            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Erro ao salvar mudanças: " . $e->getMessage());
        }
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

// Buscar Membros
$stmtUsers = $pdo->prepare("
    SELECT su.*, u.id as user_id, u.name, u.instrument, u.avatar_color
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    WHERE su.schedule_id = ?
    ORDER BY u.name ASC
");
$stmtUsers->execute([$id]);
$team = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
$teamIds = array_column($team, 'user_id');

// Buscar Músicas com TODAS as tags
$stmtSongs = $pdo->prepare("
    SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone, s.bpm, s.category
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    WHERE ss.schedule_id = ?
    ORDER BY ss.position ASC
");
$stmtSongs->execute([$id]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);
$songIds = array_column($songs, 'song_id');

// Buscar TODOS para edição
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone, bpm, category FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Buscar ausências que coincidem com esta escala
$stmtAbsences = $pdo->prepare("
    SELECT 
        u.id as user_id,
        u.name,
        u.avatar_color,
        u.instrument,
        ua.reason,
        ua.audio_path,
        r.id as replacement_id,
        r.name as replacement_name,
        r.avatar_color as replacement_color,
        r.instrument as replacement_instrument
    FROM user_unavailability ua
    JOIN users u ON ua.user_id = u.id
    LEFT JOIN users r ON ua.replacement_id = r.id
    WHERE :event_date BETWEEN ua.start_date AND ua.end_date
    ORDER BY u.name
");
$stmtAbsences->execute(['event_date' => $schedule['event_date']]);
$absences = $stmtAbsences->fetchAll(PDO::FETCH_ASSOC);

// --- Buscar Funções (Roles) de TODOS os usuários ---
$stmtRoles = $pdo->query("
    SELECT ur.user_id, r.name, r.icon, r.color, ur.is_primary 
    FROM user_roles ur 
    JOIN roles r ON ur.role_id = r.id 
    ORDER BY ur.is_primary DESC
");
$userRoles = [];
while ($row = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
    $userRoles[$row['user_id']][] = $row;
}

renderAppHeader('Escala');
renderPageHeader($schedule['event_type'], $diaSemana . ', ' . $date->format('d/m/Y'));
?>

<link rel="stylesheet" href="../assets/css/pages/escala-detalhe.css?v=<?= time() ?>">

<style>
.edit-mode-hidden { display: none; }
.view-mode-hidden { display: none; }

/* === DESIGN ÚNICO - ESCALA DETALHE === */
@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(139, 92, 246, 0.3); }
    50% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.5); }
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-5px); }
}

.hero-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
}

.hero-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    background-size: 200% 100%;
    animation: shimmer 3s infinite;
    pointer-events: none;
}

.hero-date-box {
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.95);
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: float 3s ease-in-out infinite;
}

.hero-date-day {
    font-size: 2.2rem;
    font-weight: 900;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.hero-date-month {
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #764ba2;
    margin-top: 4px;
    letter-spacing: 1px;
}

.hero-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    margin: 0 0 6px 0;
}

.hero-subtitle {
    color: rgba(255,255,255,0.9);
    font-size: 0.9rem;
    font-weight: 500;
}

.hero-stats {
    display: flex;
    gap: 20px;
    margin-top: 12px;
}

.hero-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    padding: 8px 14px;
    border-radius: 30px;
    color: white;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.3);
}

.hero-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

.btn-hero {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.85rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-hero-primary {
    background: white;
    color: #764ba2;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.btn-hero-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.btn-hero-danger {
    background: rgba(255,255,255,0.2);
    color: white;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
}

.btn-hero-danger:hover {
    background: rgba(239, 68, 68, 0.9);
}

/* Section Titles */
.section-title-gradient {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 3px solid;
    border-image: linear-gradient(90deg, #667eea, #764ba2, #f093fb) 1;
}

.section-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.section-icon-green { background: linear-gradient(135deg, #10b981, #34d399); }
.section-icon-purple { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }

.section-text {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-primary);
}

/* Participant Cards */
.participant-card-premium {
    background: linear-gradient(145deg, #ffffff, #f8f9ff);
    border: 1px solid rgba(139, 92, 246, 0.15);
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.participant-card-premium::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
    opacity: 0;
    transition: opacity 0.3s;
}

.participant-card-premium:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 15px 40px rgba(139, 92, 246, 0.2);
    border-color: rgba(139, 92, 246, 0.3);
}

.participant-card-premium:hover::before {
    opacity: 1;
}

.participant-avatar-premium {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 1.3rem;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    position: relative;
}

.participant-avatar-premium::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(255,255,255,0.5), transparent);
    pointer-events: none;
}

/* Song Cards */
.song-card-premium {
    background: linear-gradient(145deg, #ffffff, #faf5ff);
    border: 1px solid rgba(139, 92, 246, 0.1);
    border-left: 5px solid;
    border-image: linear-gradient(to bottom, #667eea, #f093fb) 1;
    border-radius: 0 16px 16px 0;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: inherit;
}

.song-card-premium:hover {
    transform: translateX(8px);
    box-shadow: 0 10px 30px rgba(139, 92, 246, 0.15);
    background: linear-gradient(145deg, #ffffff, #f3e8ff);
}

.song-number {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 1.1rem;
    color: white;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    flex-shrink: 0;
}

.song-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
}

.badge-tone { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.badge-bpm { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #9d174d; }
.badge-category { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; }

@media (max-width: 640px) {
    .hero-card {
        padding: 20px;
        flex-direction: column;
        text-align: center;
    }
    .hero-date-box {
        width: 70px;
        height: 70px;
        margin-bottom: 16px;
    }
    .hero-stats {
        justify-content: center;
        flex-wrap: wrap;
    }
    .hero-actions {
        margin-top: 16px;
        width: 100%;
        justify-content: center;
    }
    .participant-card-premium {
        padding: 12px;
    }
}
</style>

<?php
// Cores do avatar baseadas no tipo
$avatarColors = [
    'A' => '#ef4444', 'B' => '#f97316', 'C' => '#eab308', 'D' => '#22c55e',
    'E' => '#14b8a6', 'F' => '#3b82f6', 'G' => '#8b5cf6', 'H' => '#ec4899',
    'I' => '#f43f5e', 'J' => '#f59e0b', 'K' => '#10b981', 'L' => '#06b6d4',
    'M' => '#6366f1', 'N' => '#d946ef', 'O' => '#f472b6', 'P' => '#fb923c',
    'Q' => '#84cc16', 'R' => '#2dd4bf', 'S' => '#818cf8', 'T' => '#c084fc',
    'U' => '#fb7185', 'V' => '#facc15', 'W' => '#4ade80', 'X' => '#22d3ee',
    'Y' => '#a78bfa', 'Z' => '#e879f9'
];
?>

<!-- Hero Card -->
<div class="schedule-detail-container">
    <div class="hero-card" style="display: flex; align-items: center; gap: 20px;">
        
        <!-- Date Box -->
        <div class="hero-date-box">
            <div class="hero-date-day"><?= $date->format('d') ?></div>
            <div class="hero-date-month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
        </div>

        <!-- Content -->
        <div style="flex: 1; min-width: 0;">
            <h1 id="display-event-name" class="hero-title">
                <?= htmlspecialchars($schedule['event_type']) ?>
            </h1>
            <div id="display-event-date" class="hero-subtitle">
                <?= $diaSemana ?>, <?= $date->format('d/m/Y') ?>
            </div>

            <div class="hero-stats">
                <div class="hero-stat">
                    <i data-lucide="clock" width="14"></i>
                    <span id="display-event-time"><?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?></span>
                </div>
                <div class="hero-stat">
                    <i data-lucide="users" width="14"></i>
                    <?= count($team) ?> pessoas
                </div>
                <div class="hero-stat">
                    <i data-lucide="music" width="14"></i>
                    <?= count($songs) ?> músicas
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="hero-actions">
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta escala?');" style="margin: 0;">
                <input type="hidden" name="delete_schedule" value="1">
                <button type="submit" class="btn-hero btn-hero-danger">
                    <i data-lucide="trash-2" width="16"></i>
                </button>
            </form>
            <?php endif; ?>
            
            <button id="saveBtn" onclick="saveAllChanges()" class="btn-hero btn-hero-primary" style="display: none;">
                <i data-lucide="check" width="16"></i>
                Salvar
            </button>
            
            <button id="editBtn" onclick="toggleEditMode()" class="btn-hero btn-hero-primary">
                <i data-lucide="edit-2" width="16"></i>
                Editar
            </button>
        </div>
    </div>

    <!-- Observações -->
    <?php if ($_SESSION['user_role'] === 'admin' && $schedule['notes']): ?>
        <div id="display-notes-container" style="padding: 16px; background: linear-gradient(145deg, #fefce8, #fef9c3); border-radius: 16px; border: 2px solid #fde047; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(253, 224, 71, 0.3);">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #eab308, #facc15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="lightbulb" style="width: 14px; color: white;"></i>
                </div>
                <span style="font-size: 0.8rem; font-weight: 800; color: #854d0e; text-transform: uppercase; letter-spacing: 0.5px;">Observações do Líder</span>
            </div>
            <div id="display-notes-text" style="font-size: 0.9rem; line-height: 1.5; color: #713f12; font-weight: 500;"><?= nl2br(htmlspecialchars($schedule['notes'])) ?></div>
        </div>
    <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
        <div id="display-notes-container" style="display: none; padding: 16px; background: linear-gradient(145deg, #fefce8, #fef9c3); border-radius: 16px; border: 2px solid #fde047; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <div style="width: 28px; height: 28px; background: linear-gradient(135deg, #eab308, #facc15); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="lightbulb" style="width: 14px; color: white;"></i>
                </div>
                <span style="font-size: 0.8rem; font-weight: 800; color: #854d0e; text-transform: uppercase;">Observações do Líder</span>
            </div>
            <div id="display-notes-text" style="font-size: 0.9rem; line-height: 1.5; color: #713f12;"></div>
        </div>
    <?php endif; ?>

    <!-- Botão Gerenciar -->
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <button id="btn-manage-info" class="edit-mode-item" onclick="openModal('modal-event')" style="
        display: none; width: 100%; margin-bottom: 24px; padding: 14px; 
        background: linear-gradient(145deg, #f3e8ff, #ede9fe); 
        border: 2px dashed #a78bfa; border-radius: 16px; 
        color: #7c3aed; font-weight: 700; cursor: pointer; 
        align-items: center; justify-content: center; gap: 8px; 
        transition: all 0.3s;
    ">
        <i data-lucide="settings-2" style="width: 20px;"></i> Gerenciar Informações
    </button>
    <?php endif; ?>
</div>

<!-- Content -->
<div class="schedule-detail-container">
    
    <!-- MODO VISUALIZAÇÃO -->
    <div id="view-mode" class="view-mode">
        <!-- AUSÊNCIAS CADASTRADAS -->
        <?php if (!empty($absences)): ?>
        <div style="background: var(--rose-50); border: 1px solid var(--rose-200); border-radius: 16px; padding: 20px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                <div style="width: 32px; height: 32px; background: var(--rose-600); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="alert-circle" style="width: 18px; color: white;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: var(--rose-600); font-size: 1.125rem; font-weight: 700;">Ausências Cadastradas</h3>
                    <p style="margin: 0; font-size: 0.875rem; color: var(--rose-700); font-weight: 500;"><?= count($absences) ?> membro<?= count($absences) > 1 ? 's' : '' ?> não poderá<?= count($absences) > 1 ? 'ão' : '' ?> participar</p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($absences as $absence): ?>
                <div style="background: white; border-radius: 12px; padding: 14px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                    <!-- Avatar do Ausente -->
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: <?= $absence['avatar_color'] ?: 'var(--slate-300)' ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.125rem; flex-shrink: 0;">
                        <?= strtoupper(substr($absence['name'], 0, 2)) ?>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 700; color: var(--slate-900); font-size: 0.9375rem;"><?= htmlspecialchars($absence['name']) ?></div>
                        <div style="font-size: 0.8125rem; color: var(--slate-500); margin-top: 2px;"><?= htmlspecialchars($absence['instrument'] ?: 'Membro') ?></div>
                        <?php if ($absence['reason']): ?>
                        <div style="font-size: 0.8125rem; color: var(--slate-400); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                            <i data-lucide="info" style="width: 12px;"></i>
                            <?= htmlspecialchars($absence['reason']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Substituto (se houver) -->
                    <?php if ($absence['replacement_id']): ?>
                    <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: var(--sage-50); border-radius: 10px; border: 1px solid var(--sage-200);">
                        <i data-lucide="arrow-right" style="width: 18px; color: var(--sage-600);"></i>
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $absence['replacement_color'] ?: 'var(--slate-300)' ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1rem; border: 2px solid var(--sage-600);">
                            <?= strtoupper(substr($absence['replacement_name'], 0, 2)) ?>
                        </div>
                        <div style="min-width: 0;">
                            <div style="font-weight: 700; color: var(--sage-600); font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($absence['replacement_name']) ?></div>
                            <div style="font-size: 0.75rem; color: var(--sage-700);"><?= htmlspecialchars($absence['replacement_instrument'] ?: 'Substituto') ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="background: var(--yellow-100); color: #92400e; padding: 8px 14px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; white-space: nowrap;">
                        Sem substituto
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- PARTICIPANTES -->
        <div style="margin-bottom: 40px;">
            <div class="section-title-gradient">
                <div class="section-icon section-icon-green">
                    <i data-lucide="users" style="width: 20px;"></i>
                </div>
                <span class="section-text">Participantes (<?= count($team) ?>)</span>
            </div>
            
            <?php if (empty($team)): ?>
                <div style="text-align: center; padding: 50px 20px; background: linear-gradient(145deg, #f8fafc, #f1f5f9); border-radius: 20px; border: 2px dashed #cbd5e1;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #94a3b8, #64748b); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="user-plus" style="width: 28px; color: white;"></i>
                    </div>
                    <p style="color: #64748b; font-size: 1rem; margin: 0; font-weight: 600;">Nenhum participante escalado</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <?php foreach ($team as $member): 
                        $firstLetter = strtoupper(substr($member['name'], 0, 1));
                        $avatarBg = $avatarColors[$firstLetter] ?? '#8b5cf6';
                    ?>
                        <div class="participant-card-premium">
                            <div class="participant-avatar-premium" style="background: linear-gradient(135deg, <?= $avatarBg ?>, <?= $avatarBg ?>dd);">
                                <?= $firstLetter ?>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($member['name']) ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <?php 
                                    $mRoles = $userRoles[$member['user_id']] ?? [];
                                    if (empty($mRoles) && $member['instrument']) {
                                        echo '<span style="font-size: 0.8rem; color: #64748b; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); padding: 3px 10px; border-radius: 20px; font-weight: 600;">' . htmlspecialchars($member['instrument']) . '</span>';
                                    } else {
                                        foreach ($mRoles as $role): 
                                    ?>
                                        <span title="<?= htmlspecialchars($role['name']) ?>" style="font-size: 1.1rem; cursor: help; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));"><?= $role['icon'] ?></span>
                                    <?php 
                                        endforeach; 
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- REPERTÓRIO -->
        <div style="margin-bottom: 40px;">
            <div class="section-title-gradient">
                <div class="section-icon section-icon-purple">
                    <i data-lucide="music" style="width: 20px;"></i>
                </div>
                <span class="section-text">Repertório (<?= count($songs) ?>)</span>
            </div>
            
            <?php if (empty($songs)): ?>
                <div style="text-align: center; padding: 50px 20px; background: linear-gradient(145deg, #faf5ff, #f3e8ff); border-radius: 20px; border: 2px dashed #c4b5fd;">
                    <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #8b5cf6, #a78bfa); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i data-lucide="music-2" style="width: 28px; color: white;"></i>
                    </div>
                    <p style="color: #7c3aed; font-size: 1rem; margin: 0; font-weight: 600;">Nenhuma música selecionada</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <?php foreach ($songs as $index => $song): ?>
                        <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>" class="song-card-premium">
                            <!-- Número -->
                            <div class="song-number">
                                <?= $index + 1 ?>
                            </div>

                            <!-- Conteúdo -->
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($song['title']) ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;"><?= htmlspecialchars($song['artist']) ?></span>
                                    
                                    <?php if ($song['tone']): ?>
                                        <span class="song-badge badge-tone">
                                            🎵 <?= htmlspecialchars($song['tone']) ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($song['bpm']): ?>
                                        <span class="song-badge badge-bpm">
                                            ⚡ <?= htmlspecialchars($song['bpm']) ?> BPM
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($song['category']): ?>
                                        <span class="song-badge badge-category">
                                            🏷️ <?= htmlspecialchars($song['category']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Arrow -->
                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="chevron-right" style="width: 16px; color: white;"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- MODO EDIÇÃO OTIMIZADO -->
    <div id="edit-mode" class="edit-mode edit-mode-hidden">
        
        <!-- Participantes Card -->
        <div style="background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-color); padding: 20px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Participantes</h3>
                <span style="background: var(--bg-body); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);"><?= count($team) ?> selecionados</span>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
                <?php foreach ($team as $member): ?>
                    <div id="member-chip-<?= $member['user_id'] ?>" style="display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: var(--bg-body); border-radius: 10px; border: 1px solid var(--border-color);">
                         <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= $member['avatar_color'] ?: '#ccc' ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                         </div>
                         <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($member['name']) ?></span>
                         <button onclick="toggleMember(<?= $member['user_id'] ?>, null)" style="border: none; background: none; color: var(--rose-500); cursor: pointer; padding: 0 0 0 4px; display: flex;"><i data-lucide="x" style="width: 14px;"></i></button>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($team)): ?><span style="color: var(--text-muted); font-style: italic;">Nenhum participante.</span><?php endif; ?>
            </div>

            <button onclick="openModal('modal-members')" style="width: 100%; padding: 14px; background: var(--bg-body); border: 2px dashed var(--border-color); border-radius: 12px; color: var(--primary); font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                <i data-lucide="user-plus" style="width: 20px;"></i> Gerenciar Participantes
            </button>
        </div>

        <!-- Músicas Card -->
         <div style="background: var(--bg-surface); border-radius: 16px; border: 1px solid var(--border-color); padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Repertório</h3>
                <span style="background: var(--bg-body); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: var(--text-muted);"><?= count($songs) ?> selecionadas</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <?php foreach ($songs as $idx => $song): ?>
                    <div id="song-chip-<?= $song['song_id'] ?>" style="display: flex; align-items: center; gap: 12px; padding: 10px; background: var(--bg-body); border-radius: 10px; border: 1px solid var(--border-color);">
                         <div style="width: 24px; height: 24px; background: #ddd; color: #555; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;"><?= $idx+1 ?></div>
                         <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--text-main); font-size: 0.95rem;"><?= htmlspecialchars($song['title']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($song['artist']) ?></div>
                         </div>
                         <button onclick="toggleSong(<?= $song['song_id'] ?>, null)" style="border: none; background: none; color: var(--rose-500); cursor: pointer; padding: 4px; display: flex;"><i data-lucide="trash-2" style="width: 16px;"></i></button>
                    </div>
                <?php endforeach; ?>
                 <?php if(empty($songs)): ?><span style="color: var(--text-muted); font-style: italic;">Nenhuma música.</span><?php endif; ?>
            </div>

            <button onclick="openModal('modal-songs')" style="width: 100%; padding: 14px; background: var(--bg-body); border: 2px dashed var(--border-color); border-radius: 12px; color: var(--primary); font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                <i data-lucide="music-4" style="width: 20px;"></i> Gerenciar Repertório
            </button>
        </div>

    </div>
</div>

<!-- MODAL PARTICIPANTES -->
<div id="modal-members" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 500px; height: 80vh; border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Gerenciar Equipe</h3>
            <button onclick="closeModal('modal-members')" style="background:none; border:none; cursor:pointer; padding: 4px;"><i data-lucide="x"></i></button>
        </div>
        
        <div style="padding: 12px; background: var(--bg-body);">
             <input type="text" id="searchMembers" placeholder="Buscar participante..." onkeyup="filterMembers()" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.95rem; background: var(--bg-surface); outline: none;">
        </div>

        <div id="membersList" style="flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
             <?php foreach ($allUsers as $user): 
                $isSelected = in_array($user['id'], $teamIds);
             ?>
                <label class="member-filter-item" data-name="<?= strtolower($user['name']) ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-surface); border-radius: 12px; border: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s;">
                    <input type="checkbox" <?= $isSelected ? 'checked' : '' ?> onchange="toggleMember(<?= $user['id'] ?>, this)" style="width: 20px; height: 20px; accent-color: var(--primary);">
                    <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= $user['avatar_color'] ?: 'var(--slate-200)' ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($user['name']) ?></div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($user['instrument'] ?: 'Vocal') ?></div>
                    </div>
                </label>
             <?php endforeach; ?>
        </div>

        <div style="padding: 16px; border-top: 1px solid var(--border-color); background: var(--bg-surface);">
            <button onclick="closeModal('modal-members')" style="width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer;">Concluir Seleção</button>
        </div>
    </div>
</div>

<!-- MODAL REPERTÓRIO -->
<div id="modal-songs" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 500px; height: 80vh; border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Gerenciar Repertório</h3>
            <button onclick="closeModal('modal-songs')" style="background:none; border:none; cursor:pointer; padding: 4px;"><i data-lucide="x"></i></button>
        </div>
        
        <div style="padding: 12px; background: var(--bg-body);">
             <input type="text" id="searchSongs" placeholder="Buscar música..." onkeyup="filterSongs()" style="width: 100%; padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 0.95rem; background: var(--bg-surface); outline: none;">
        </div>

        <div id="songsList" style="flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
             <?php foreach ($allSongs as $song): 
                $isSelected = in_array($song['id'], $songIds);
             ?>
                <label class="song-filter-item" data-title="<?= strtolower($song['title']) ?>" data-artist="<?= strtolower($song['artist']) ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--bg-surface); border-radius: 12px; border: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s;">
                    <input type="checkbox" <?= $isSelected ? 'checked' : '' ?> onchange="toggleSong(<?= $song['id'] ?>, this)" style="width: 20px; height: 20px; accent-color: var(--primary);">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 0.95rem; color: var(--text-main);"><?= htmlspecialchars($song['title']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($song['artist']) ?></div>
                    </div>
                    <?php if ($song['tone']): ?>
                        <span style="background: #fff7ed; color: #ea580c; padding: 2px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; border: 1px solid #fed7aa; white-space: nowrap;"><?= htmlspecialchars($song['tone']) ?></span>
                    <?php endif; ?>
                </label>
             <?php endforeach; ?>
        </div>

        <div style="padding: 16px; border-top: 1px solid var(--border-color); background: var(--bg-surface);">
            <button onclick="closeModal('modal-songs')" style="width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer;">Concluir Seleção</button>
        </div>
    </div>
</div>

<!-- MODAL EVENT DETAILS (Nome, Data, Hora, Notas) -->
<div id="modal-event" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); width: 90%; max-width: 500px; border-radius: 20px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface);">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">Editar Informações</h3>
            <button onclick="closeModal('modal-event')" style="background:none; border:none; cursor:pointer; padding: 4px;"><i data-lucide="x"></i></button>
        </div>
        
        <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px; background: var(--bg-body);">
            
            <!-- Nome -->
            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Nome do Evento</label>
                <input type="text" id="modal-event-name" value="<?= htmlspecialchars($schedule['event_type']) ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); font-size: 1rem;">
            </div>

            <!-- Data e Hora -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Data</label>
                    <input type="date" id="modal-event-date" value="<?= $schedule['event_date'] ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); font-size: 1rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Horário</label>
                    <input type="time" id="modal-event-time" value="<?= isset($schedule['event_time']) ? substr($schedule['event_time'], 0, 5) : '19:00' ?>" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); font-size: 1rem;">
                </div>
            </div>

            <!-- Notas -->
            <div>
                <label style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-main); margin-bottom: 6px;">Observações (Opcional)</label>
                <textarea id="modal-event-notes" rows="3" style="width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); font-size: 0.95rem; resize: vertical;"><?= htmlspecialchars($schedule['notes'] ?? '') ?></textarea>
            </div>

        </div>

        <div style="padding: 16px; border-top: 1px solid var(--border-color); background: var(--bg-surface);">
            <button onclick="updateEventInfoFromModal()" style="width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 1rem; cursor: pointer;">Concluir Alterações</button>
        </div>
    </div>
</div>

<script>
    window.SCHEDULE_ID = <?= json_encode($id) ?>;
</script>
<script src="js/escala_detalhe.js"></script>

<?php renderAppFooter(); ?>

