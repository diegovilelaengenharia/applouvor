<?php
// admin/escala_detalhe.php - Redesign V3 (Standard & Clean)
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Helper para ícones de instrumentos
function getInstrumentIcon($instrument) {
    $inst = mb_strtolower($instrument, 'UTF-8');
    if (strpos($inst, 'vocal') !== false || strpos($inst, 'voz') !== false || strpos($inst, 'cantor') !== false) return 'mic';
    if (strpos($inst, 'violão') !== false || strpos($inst, 'guitarra') !== false) return 'guitar';
    if (strpos($inst, 'bateria') !== false || strpos($inst, 'cajon') !== false) return 'drum';
    if (strpos($inst, 'teclado') !== false || strpos($inst, 'piano') !== false) return 'keyboard-music'; 
    if (strpos($inst, 'baixo') !== false) return 'zap'; 
    if (strpos($inst, 'sax') !== false || strpos($inst, 'sopra') !== false) return 'wind';
    return 'music'; // Fallback
}

// --- DB MIGRATION: Ensure instrument column exists in schedule_users
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM schedule_users LIKE 'instrument'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE schedule_users ADD COLUMN instrument VARCHAR(50) DEFAULT NULL");
    }
} catch (Exception $e) {
    // Ignore if already exists or other non-critical error for now
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

// --- LOGICA DE POST/SALVAR MANTIDA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_schedule']) && $_SESSION['user_role'] === 'admin') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM schedules WHERE id = ?")->execute([$id]);
            $pdo->commit();
            header("Location: escalas.php?msg=deleted");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die($e->getMessage());
        }
    }

    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        try {
            $pdo->beginTransaction();
            // Atualizar Agenda
            $notes = $_POST['notes'] ?? '';
            $stmt = $pdo->prepare("UPDATE schedules SET event_type = ?, event_date = ?, event_time = ?, notes = ? WHERE id = ?");
            $stmt->execute([$_POST['event_type'], $_POST['event_date'], $_POST['event_time'], $notes, $id]);
            
            // Atualizar Membros
            $pdo->prepare("DELETE FROM schedule_users WHERE schedule_id = ?")->execute([$id]);
            if (!empty($_POST['members'])) {
                $stmt = $pdo->prepare("INSERT INTO schedule_users (schedule_id, user_id, instrument, status) VALUES (?, ?, ?, 'pending')");
                foreach ($_POST['members'] as $uid => $role) {
                    // Check if role is valid string, if not default to null
                    $roleToSave = (is_string($role) && !empty($role)) ? $role : null;
                    
                    // DEBUG: Log values to see what's failing
                    // file_put_contents('debug_log.txt', "Schedule: $id, User: $uid, Role: $roleToSave\n", FILE_APPEND);
                    
                    // Ensure UID is valid integer
                    if(is_numeric($uid) && $uid > 0) {
                        try {
                            $stmt->execute([$id, $uid, $roleToSave]);
                        } catch (PDOException $e) {
                           // If fails, maybe ignore or log?
                           // For now, let's catch it to avoid breaking the whole save
                        }
                    }
                }
            }

            // Atualizar Músicas
            $pdo->prepare("DELETE FROM schedule_songs WHERE schedule_id = ?")->execute([$id]);
            if (!empty($_POST['songs'])) {
                $stmt = $pdo->prepare("INSERT INTO schedule_songs (schedule_id, song_id, position) VALUES (?, ?, ?)");
                foreach ($_POST['songs'] as $pos => $sid) $stmt->execute([$id, $sid, $pos + 1]);
            }

            $pdo->commit();
            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die($e->getMessage());
        }
    }
}

// --- BUSCAR DADOS ---
$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$id]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) die("Escala não encontrada.");

$date = new DateTime($schedule['event_date']);
$diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Buscar Membros (com Status)
$stmtUsers = $pdo->prepare("
    SELECT su.*, u.id as user_id, u.name, u.instrument, u.avatar, u.avatar_color,
           su.instrument as assigned_instrument
    FROM schedule_users su 
    JOIN users u ON su.user_id = u.id 
    WHERE su.schedule_id = ? 
    ORDER BY u.name
");
$stmtUsers->execute([$id]);
$team = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);
$teamIds = array_column($team, 'user_id');

// Buscar Músicas (com Links e BPM)
$stmtSongs = $pdo->prepare("
    SELECT ss.*, s.id as song_id, s.title, s.artist, s.tone, s.bpm, 
           s.link_letra, s.link_cifra, s.link_audio, s.link_video,
           (SELECT GROUP_CONCAT(t.name SEPARATOR '||') 
            FROM tags t 
            JOIN song_tags st ON st.tag_id = t.id 
            WHERE st.song_id = s.id) as tags_list
    FROM schedule_songs ss 
    JOIN songs s ON ss.song_id = s.id 
    WHERE ss.schedule_id = ? 
    ORDER BY ss.position
");
$stmtSongs->execute([$id]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

// Listas completas para Edit Mode
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$isEditable = isset($_GET['edit']) && $_GET['edit'] == '1' && $_SESSION['user_role'] === 'admin';

renderAppHeader('Detalhes da Escala', 'escalas.php');
?>

<link rel="stylesheet" href="../assets/css/pages/escala-detalhe.css?v=<?= time() ?>">

<?php renderPageHeader('Detalhes da Escala', $schedule['event_type']); ?>

<?php if (isset($_GET['success'])): ?>
    <div class="feedback-message feedback-success">
        <i data-lucide="check-circle" width="20"></i> Alterações salvas com sucesso!
    </div>
<?php endif; ?>

<div class="scale-detail-wrapper">

    <!-- EVENT SUMMARY CARD -->
    <div class="event-info-card">
        <div class="event-main-row">
            <div class="event-date-box">
                <div class="event-day"><?= $date->format('d') ?></div>
                <div class="event-month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
            </div>
            <div class="event-details">
                <div class="event-type"><?= htmlspecialchars($schedule['event_type']) ?></div>
                <div class="event-meta">
                    <i data-lucide="calendar" width="14"></i> <?= $diaSemana ?>, <?= $date->format('Y') ?>
                </div>
                <div class="event-meta mt-1">
                    <i data-lucide="clock" width="14"></i> Hórario: <?= substr($schedule['event_time'], 0, 5) ?>
                </div>
            </div>
            
            <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
            <div>
                <a href="?id=<?= $id ?>&edit=1" class="btn-icon" title="Editar">
                    <i data-lucide="edit-2"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <?php if($schedule['notes']): ?>
        <div class="event-notes">
            <strong><i data-lucide="sticky-note" width="14" class="align-middle"></i> Observações:</strong><br>
            <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($isEditable): ?>
        <!-- EDIT FORM -->
        <form method="POST" id="editForm" class="edit-mode-section">
            <input type="hidden" name="save_changes" value="1">
            <div class="edit-mode-header">
                <i data-lucide="edit" width="20"></i> Editando Escala
            </div>

            <!-- INFO SUMMARY CARD (Edit Mode) -->
            <div class="form-group">
                <label class="form-label">Informações do Evento</label>
                <div class="info-summary-card">
                    <div id="summary-type" class="summary-type"><?= htmlspecialchars($schedule['event_type']) ?></div>
                    <div class="summary-meta-row">
                        <span id="summary-date"><i data-lucide="calendar" width="14" class="align-middle"></i> <?= date('d/m/Y', strtotime($schedule['event_date'])) ?></span>
                        <span id="summary-time"><i data-lucide="clock" width="14" class="align-middle"></i> <?= substr($schedule['event_time'], 0, 5) ?></span>
                    </div>
                    <?php if($schedule['notes']): ?>
                    <div id="summary-notes" class="summary-notes">
                        <i data-lucide="sticky-note" width="12"></i> <?= htmlspecialchars($schedule['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-manage" onclick="openInfoModal()">
                    <i data-lucide="edit-3" width="16"></i> Editar Informações
                </button>
            </div>
            
            <hr class="divider">

            <!-- MEMBERS SELECT -->
             <div class="form-group mt-4">
                <label class="form-label">Participantes</label>
                <div id="members-bag" class="members-bag">
                    <?php foreach($teamIds as $tid): 
                        $uName = ''; foreach($allUsers as $u) if($u['id']==$tid) $uName=$u['name'];
                    ?>
                        <span class="badge" id="m-badge-<?= $tid ?>"><?= $uName ?> <i data-lucide="x" class="cursor-pointer" style="width:12px;" onclick="removeMember(<?= $tid ?>)"></i><input type="hidden" name="members[]" value="<?= $tid ?>"></span>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-manage" onclick="var m = document.getElementById('modalMembers'); m.style.display='flex'; m.style.opacity='1'; m.style.visibility='visible';">
                    <i data-lucide="users" width="16"></i> Gerenciar Participantes
                </button>
            </div>

            <hr class="divider">

             <!-- SONGS SELECT -->
             <div class="form-group mt-4">
                <label class="form-label">Repertório</label>
                <div id="songs-bag" class="songs-bag">
                    <?php foreach($songs as $sg): ?>
                        <div class="song-card-compact" id="s-badge-<?= $sg['song_id'] ?>">
                            <span><?= $sg['title'] ?> - <?= $sg['artist'] ?></span>
                            <div class="song-card-actions">
                                <input type="hidden" name="songs[]" value="<?= $sg['song_id'] ?>">
                                <i data-lucide="x" class="cursor-pointer" style="width:16px;" onclick="removeSong(<?= $sg['song_id'] ?>)"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-manage" onclick="var m = document.getElementById('modalSongs'); m.style.display='flex'; m.style.opacity='1'; m.style.visibility='visible';">
                     <i data-lucide="music" width="16"></i> Gerenciar Músicas
                </button>
            </div>

            <div class="form-actions-grid">
                 <a href="?id=<?= $id ?>" class="btn-warning w-full text-center text-no-decoration">Cancelar</a>
                 <button type="button" onclick="if(confirm('Excluir esta escala?')) document.getElementById('delForm').submit()" class="btn-danger w-full">Excluir</button>
                 <button type="submit" class="btn-success w-full">Salvar Alterações</button>
            </div>
        </form>
        <form id="delForm" method="POST" style="display:none;"><input type="hidden" name="delete_schedule" value="1"></form>
    <?php else: ?>

        <!-- VIEW MODE CONTENT -->
        
        <!-- PARTICIPANTS SECTION -->
        <div class="detail-section section-box">
            <div class="section-header">
                <div class="section-title">
                    Equipe Escala <span class="section-count"><?= count($team) ?></span>
                </div>
            </div>
            
            <?php if(empty($team)): ?>
                <div class="empty-state-text">Nenhum participante definido.</div>
            <?php else: ?>
                <div class="team-list-grid">
<?php
                    // Helper para ícones de instrumentos


                    foreach($team as $member): 
                        $statusClass = 'status-pending';
                        if($member['status'] == 'confirmed') $statusClass = 'status-confirmed';
                        if($member['status'] == 'declined') $statusClass = 'status-declined';
                        
                        $initials = strtoupper(substr($member['name'], 0, 1));
                        
                        // Get assigned instrument (or fallback)
                        $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
                        $iconName = getInstrumentIcon($instr);
                    ?>
                    <div class="member-card">
                        <div class="member-avatar" style="background: <?= $member['avatar_color'] ?: '#ccc' ?>;">
                            <?php if($member['avatar']): 
                                $avatarPath = $member['avatar'];
                                if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                                    $avatarPath = '../assets/uploads/' . $avatarPath;
                                }
                            ?>
                                <img src="<?= htmlspecialchars($avatarPath) ?>" alt="<?= htmlspecialchars($member['name']) ?>">
                            <?php else: ?>
                                <?= $initials ?>
                            <?php endif; ?>
                            <div class="status-indicator <?= $statusClass ?>" title="<?= $member['status'] ?>"></div>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                            <div class="member-role">
                                <i data-lucide="<?= $iconName ?>" width="12" height="12"></i> <?= htmlspecialchars($instr) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- REPERTOIRE SECTION -->
        <div class="detail-section section-box">
            <div class="section-header">
                <div class="section-title">
                    Repertório <span class="section-count"><?= count($songs) ?></span>
                </div>
            </div>

            <?php if(empty($songs)): ?>
                <div class="empty-state-text">Nenhuma música selecionada.</div>
            <?php else: ?>
                <div class="song-list">
                    <?php foreach($songs as $idx => $song): ?>
                    <div class="song-card">
                        <div class="song-main-content">
                            <div class="song-order"><?= $idx + 1 ?></div>
                            <div class="song-info-col">
                                <div class="song-title-row">
                                    <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>" class="song-title hover-underline"><?= htmlspecialchars($song['title']) ?></a>
                                    <?php if($song['tone']): ?>
                                        <span class="meta-badge badge-tone" title="Tom Original"><?= htmlspecialchars($song['tone']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="song-artist-row">
                                    <?= htmlspecialchars($song['artist']) ?>
                                    <?php if($song['bpm']): ?>
                                        <span class="bpm-badge"><i data-lucide="activity" width="10" class="align-middle"></i> <?= $song['bpm'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="song-actions">
                            <!-- Lyrics -->
                            <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="action-btn-icon <?= $song['link_letra'] ? '' : 'disabled' ?>" title="Letra">
                                <i data-lucide="align-left" width="22"></i>
                            </a>
                            
                            <!-- Chords -->
                            <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="action-btn-icon <?= $song['link_cifra'] ? '' : 'disabled' ?>" title="Cifra">
                                <i data-lucide="file-text" width="22"></i>
                            </a>
                            
                            <!-- YouTube -->
                            <a href="<?= $song['link_video'] ?: ($song['link_audio'] ?: 'https://www.youtube.com/results?search_query='.urlencode($song['title'].' '.$song['artist'])) ?>" target="_blank" class="action-btn-icon" title="Ouvir">
                                <i data-lucide="play-circle" width="22"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<!-- MODALS FOR EDIT MODE -->
<?php if($isEditable): ?>
<div id="modalMembers" class="modal-overlay" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center;">
    <div class="modal-card" style="background: var(--bg-surface); width: 90%; max-width: 400px; height: 80vh; display: flex; flex-direction: column; border-radius: 16px;">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between;">
            <h3>Selecionar Participantes</h3>
            <button onclick="document.getElementById('modalMembers').style.display='none'" style="border:none; background:none;"><i data-lucide="x"></i></button>
        </div>
        <div style="padding: 12px; overflow-y: auto; flex: 1;">




                <?php 
                // Group users by instrument
                $groupedUsers = [];
                foreach($allUsers as $u) {
                    $rawRoles = $u['instrument'] ?: 'Outros';
                    // Split by comma or slash
                    $rolesArray = preg_split('/[,\/]/', $rawRoles);
                    
                    foreach($rolesArray as $role) {
                        $role = trim($role);
                        if(empty($role)) continue;
                        
                        // Navigate logic for "Vocal", "Voz", etc.
                        if(strpos(strtolower($role), 'vocal') !== false || strpos(strtolower($role), 'voz') !== false || strpos(strtolower($role), 'cantor') !== false) {
                            $role = 'Vocal';
                        }
                        // Normalize capitalization
                        $role = ucfirst(strtolower($role));
                        
                        if(!isset($groupedUsers[$role])) $groupedUsers[$role] = [];
                        
                        // Prevent duplicates in same group if role mapping causes it
                        $ids = array_column($groupedUsers[$role], 'id');
                        if(!in_array($u['id'], $ids)) {
                            $groupedUsers[$role][] = $u;
                        }
                    }
                }
                // Custom sort order: Vocal, Violão, Bateria, Teclado, Others
                uksort($groupedUsers, function($a, $b) {
                    $order = ['Vocal' => 1, 'Violão' => 2, 'Bateria' => 3, 'Teclado' => 4];
                    $valA = $order[$a] ?? 999;
                    $valB = $order[$b] ?? 999;
                    if ($valA === $valB) return strcasecmp($a, $b);
                    return $valA <=> $valB;
                });
                ?>



            <div id="listMembers" style="padding-bottom: 60px;">
                <?php foreach($groupedUsers as $role => $users): 
                    $icon = getInstrumentIcon($role);
                ?>
                <div class="member-group" data-group-role="<?= strtolower(htmlspecialchars($role)) ?>">
                    <div style="padding: 10px; background: var(--bg-body); font-weight: 700; font-size: 0.9rem; color: var(--text-main); position: sticky; top: 0; z-index: 10; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="<?= $icon ?>" width="16"></i> <?= htmlspecialchars($role) ?>
                    </div>
                    <?php foreach($users as $u): 
                        // Determine if checked: user is in team AND the assigned instrument matches current role
                        // OR if user is in team but has no assigned instrument (legacy), check all roles? No, checking one is safest.
                        // Let's check if user ID is in team. If so, find their assigned role.
                        $isChecked = false;
                        if(in_array($u['id'], $teamIds)) {
                             // Find the specific entry in $team to check instrument
                             foreach($team as $tm) {
                                 if($tm['user_id'] == $u['id']) {
                                     // If assigned_instrument is set, check if it matches $role
                                     // If null, maybe default to their main role? Or check all?
                                     // For now: match strictly if set.
                                     $pRole = $tm['assigned_instrument'];
                                     if($pRole && strcasecmp($pRole, $role) === 0) $isChecked = true;
                                     elseif(!$pRole) {
                                         // Legacy: if no instrument assigned, check if this is their "main" instrument or just check it?
                                         // To support single-role, we should probably just check the first one we find or all?
                                         // Let's check all for legacy compatibility, but UI will enforce single on change.
                                         $isChecked = true; 
                                     }
                                     break;
                                 }
                             }
                        }
                    ?>
                    <label style="display: flex; gap: 10px; padding: 10px; border-bottom: 1px solid var(--border-subtle); cursor:pointer;">
                        <input type="checkbox" name="temp_members[<?= $u['id'] ?>]" value="<?= htmlspecialchars($role) ?>" 
                               data-user-id="<?= $u['id'] ?>" data-role="<?= htmlspecialchars($role) ?>"
                               <?= $isChecked ? 'checked' : '' ?> onchange="toggleMemberSelection(this)">
                        <div style="display: flex; flex-direction: column;">
                            <span><?= htmlspecialchars($u['name']) ?></span>
                            <?php if($role === 'Outros' && $u['instrument']): ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($u['instrument']) ?></span>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Footer with Actions -->
        <div style="padding: 16px; border-top: 1px solid var(--border-subtle); display: flex; gap: 12px; background: var(--bg-surface);">
            <button type="button" class="btn-warning w-full" onclick="document.getElementById('modalMembers').style.display='none'">Cancelar</button>
            <button type="button" class="btn-primary w-full" onclick="confirmMemberSelection()">Confirmar</button>
        </div>
    </div>
</div>

<div id="modalSongs" class="modal-overlay" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center;">
    <div class="modal-card" style="background: var(--bg-surface); width: 90%; max-width: 400px; height: 80vh; display: flex; flex-direction: column; border-radius: 16px;">
        <div style="padding: 16px; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between;">
            <h3>Selecionar Músicas</h3>
            <button onclick="document.getElementById('modalSongs').style.display='none'" style="border:none; background:none;"><i data-lucide="x"></i></button>
        </div>
        <div style="padding: 12px; overflow-y: auto; flex: 1;">
            <input type="text" id="searchSongs" placeholder="Digite para buscar músicas..." onkeyup="filterSongList(this.value)" class="form-input w-full mb-2">
            <div id="emptySongsState" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <i data-lucide="search" width="48" style="opacity: 0.3; margin-bottom: 12px;"></i>
                <p>Digite o nome da música ou artista para buscar</p>
            </div>
            <div id="listSongs">
                <?php 
                // Sort songs: Selected first, then alphabetical
                $selectedSongIds = array_column($songs, 'song_id');
                usort($allSongs, function($a, $b) use ($selectedSongIds) {
                    $aSelected = in_array($a['id'], $selectedSongIds);
                    $bSelected = in_array($b['id'], $selectedSongIds);
                    if ($aSelected && !$bSelected) return -1;
                    if (!$aSelected && $bSelected) return 1;
                    return strcasecmp($a['title'], $b['title']);
                });

                $hasSelectedSongs = !empty($songs);
                ?>
                <script>
                    // Hide empty state if we have selected songs initially
                    <?php if($hasSelectedSongs): ?>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('emptySongsState').style.display = 'none';
                    });
                    <?php endif; ?>
                </script>

                <?php foreach($allSongs as $s): 
                    $isSelected = in_array($s['id'], $selectedSongIds);
                    $displayStyle = $isSelected ? 'flex' : 'none';
                ?>
                <label style="display: <?= $displayStyle ?>; gap: 10px; padding: 10px; border-bottom: 1px solid var(--border-subtle); cursor:pointer;" data-song-search="<?= strtolower(htmlspecialchars($s['title'].' '.$s['artist'])) ?>">
                     <input type="checkbox" value="<?= $s['id'] ?>" data-title="<?= htmlspecialchars($s['title'].' - '.$s['artist']) ?>" 
                        <?= $isSelected ? 'checked' : '' ?> onchange="toggleSong(this)">
                    <div style="font-size: 0.9rem;">
                        <div><?= htmlspecialchars($s['title']) ?></div>
                        <div style="font-size:0.75rem; color:gray;"><?= htmlspecialchars($s['artist']) ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Footer with Actions -->
        <div style="padding: 16px; border-top: 1px solid var(--border-subtle); display: flex; gap: 12px; background: var(--bg-surface);">
            <button type="button" class="btn-warning w-full" onclick="document.getElementById('modalSongs').style.display='none'">Cancelar</button>
            <button type="button" class="btn-primary w-full" onclick="confirmSongSelection()">Confirmar</button>
        </div>
    </div>
</div>

<!-- INFO MODAL MOVED HERE TO PREVENT LAYOUT ISSUES -->
<div id="modalInfo" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div class="modal-card" style="background: white; width: 90%; max-width: 500px; padding: 24px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;">Editar Informações</h3>
        
        <div class="form-group">
            <label class="form-label">Tipo do Evento</label>
            <input type="text" name="event_type" id="input_event_type" form="editForm" value="<?= htmlspecialchars($schedule['event_type']) ?>" class="form-input w-full">
        </div>

        <div class="form-group-row">
            <div>
                <label class="form-label">Data</label>
                <input type="date" name="event_date" id="input_event_date" form="editForm" value="<?= $schedule['event_date'] ?>" class="form-input w-full">
            </div>
            <div>
                <label class="form-label">Horário</label>
                <input type="time" name="event_time" id="input_event_time" form="editForm" value="<?= $schedule['event_time'] ?>" class="form-input w-full">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Observações</label>
            <textarea name="notes" id="input_notes" form="editForm" class="form-input w-full" rows="3"><?= htmlspecialchars($schedule['notes']) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px;">
            <button type="button" class="btn-warning" onclick="closeInfoModal(false)">Cancelar</button>
            <button type="button" class="btn-success" onclick="closeInfoModal(true)">Salvar</button>
        </div>
    </div>
</div>

<script>
function filterList(id, text) {
    const list = document.getElementById(id);
    const labels = list.getElementsByTagName('label');
    text = text.toLowerCase();
    for(let l of labels) l.style.display = l.innerText.toLowerCase().includes(text) ? 'flex' : 'none';
}

function filterSongList(text) {
    const emptyState = document.getElementById('emptySongsState');
    const labels = document.querySelectorAll('#listSongs label');
    text = text.toLowerCase().trim();
    
    if(text.length === 0) {
        // Clear search: Show only selected songs
        let anyVisible = false;
        labels.forEach(l => {
            const cb = l.querySelector('input[type="checkbox"]');
            if(cb.checked) {
                l.style.display = 'flex';
                anyVisible = true;
            } else {
                l.style.display = 'none';
            }
        });
        
        // Show empty state only if no songs are selected
        emptyState.style.display = anyVisible ? 'none' : 'block';
    } else {
        // Search mode: Filter all songs
        let anyVisible = false;
        labels.forEach(l => {
            const searchData = l.getAttribute('data-song-search');
            const match = searchData.includes(text);
            l.style.display = match ? 'flex' : 'none';
            if(match) anyVisible = true;
        });
        
        // Hide empty state if results found (or maybe show "No results" if none? For now just hide if match)
        // If no match, we can show empty state or just nothing. 
        // Let's show empty state if no match, acts as "No results" effectively.
        emptyState.style.display = anyVisible ? 'none' : 'block';
    }
}



// New Logic for Single Role Selection (v3)
function toggleMemberSelection(cb) {
    const userId = cb.getAttribute('data-user-id');
    const isChecked = cb.checked;
    
    if(isChecked) {
        // Uncheck other roles for this user
        const allUserChecks = document.querySelectorAll(`input[data-user-id="${userId}"]`);
        allUserChecks.forEach(box => {
            if(box !== cb) box.checked = false;
        });
    }
}

function confirmMemberSelection() {
    const bag = document.getElementById('members-bag');
    bag.innerHTML = ''; // Clear current
    
    const allChecks = document.querySelectorAll('#listMembers input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const userId = cb.getAttribute('data-user-id');
        const role = cb.getAttribute('data-role');
        // Find name from the DOM (it's in the sibling div)
        const name = cb.nextElementSibling.querySelector('span').textContent;
        
        const sp = document.createElement('span');
        sp.className = 'badge';
        sp.id = 'm-badge-'+userId;
        sp.innerHTML = `${name} <small>(${role})</small> <i data-lucide="x" style="cursor:pointer; width:12px;" onclick="removeMember(${userId})"></i>
                        <input type="hidden" name="members[${userId}]" value="${role}">`;
        bag.appendChild(sp);
    });
    
    lucide.createIcons();
    document.getElementById('modalMembers').style.display = 'none';
}

function removeMember(id) {
    // Remove badge
    const badge = document.getElementById('m-badge-'+id);
    if(badge) badge.remove();
    
    // Uncheck in modal (for consistency if re-opened without refresh)
    const allUserChecks = document.querySelectorAll(`input[data-user-id="${id}"]`);
    allUserChecks.forEach(box => box.checked = false);
}

function toggleSong(cb) {
    // Just toggle visual state, no action needed here
}

function confirmSongSelection() {
    const bag = document.getElementById('songs-bag');
    bag.innerHTML = ''; // Clear current
    
    const allChecks = document.querySelectorAll('#listSongs input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const id = cb.value;
        const title = cb.getAttribute('data-title');
        
        const div = document.createElement('div');
        div.className = 'song-card-compact';
        div.id = 's-badge-'+id;
        div.style.cssText = 'background:var(--bg-body); padding:8px; border-radius:8px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;';
        div.innerHTML = `
            <span>${title}</span>
            <div style="display:flex; gap:8px;">
                <input type="hidden" name="songs[]" value="${id}">
                <i data-lucide="x" style="cursor:pointer; width:16px;" onclick="removeSong(${id})"></i>
            </div>
        `;
        bag.appendChild(div);
    });
    
    lucide.createIcons();
    document.getElementById('modalSongs').style.display = 'none';
}

function removeSong(id) {
    const badge = document.getElementById('s-badge-'+id);
    if(badge) badge.remove();
    const cb = document.querySelector(`#listSongs input[value="${id}"]`);
    if(cb) cb.checked = false;
}

// Info Modal Logic
let originalInfo = {};
function openInfoModal() {
    originalInfo = {
        type: document.getElementById('input_event_type').value,
        date: document.getElementById('input_event_date').value,
        time: document.getElementById('input_event_time').value,
        notes: document.getElementById('input_notes').value
    };
    const modal = document.getElementById('modalInfo');
    modal.style.display = 'flex';
    modal.style.opacity = '1';
    modal.style.visibility = 'visible';
}

function closeInfoModal(save) {
    const modal = document.getElementById('modalInfo');
    
    if (save) {
        // Update Summary
        document.getElementById('summary-type').innerText = document.getElementById('input_event_type').value;
        
        // Date formatting (simple)
        const d = document.getElementById('input_event_date').value;  
        if(d) {
             const parts = d.split('-');
             document.getElementById('summary-date').innerHTML = `<i data-lucide="calendar" width="14" style="vertical-align: middle;"></i> ${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        const t = document.getElementById('input_event_time').value;
        if(t) {
            document.getElementById('summary-time').innerHTML = `<i data-lucide="clock" width="14" style="vertical-align: middle;"></i> ${t.substring(0,5)}`;
        }

        const n = document.getElementById('input_notes').value;
        const noteDiv = document.getElementById('summary-notes');
        if(n) {
            if(!noteDiv) {
                // Create if note didn't exist
                const newDiv = document.createElement('div');
                newDiv.id = 'summary-notes';
                newDiv.style = "font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; border-top: 1px dashed var(--border-color); padding-top: 8px;";
                newDiv.innerHTML = `<i data-lucide="sticky-note" width="12"></i> ${n}`;
                document.querySelector('.info-summary-card').appendChild(newDiv);
            } else {
                noteDiv.innerHTML = `<i data-lucide="sticky-note" width="12"></i> ${n}`;
            }
        } else if (noteDiv) {
            noteDiv.remove();
        }
        
        lucide.createIcons();
    } else {
        // Revert values
        document.getElementById('input_event_type').value = originalInfo.type;
        document.getElementById('input_event_date').value = originalInfo.date;
        document.getElementById('input_event_time').value = originalInfo.time;
        document.getElementById('input_notes').value = originalInfo.notes;
    }
    
    document.getElementById('modalInfo').style.display = 'none';
}

</script>
<?php endif; ?>

<?php renderAppFooter(); ?>
