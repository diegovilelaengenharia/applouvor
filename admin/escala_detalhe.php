<?php
// admin/escala_detalhe.php - Redesign V3 (Standard & Clean) with Engagement Features
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

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: escalas.php');
    exit;
}

require_once '../includes/classes/ScheduleRepository.php';
require_once '../includes/classes/NotificationService.php';
$scheduleRepo = new \App\Repositories\ScheduleRepository($pdo);
$notificationService = new \App\Services\NotificationService($pdo);

// --- ENGAGEMENT ACTIONS (Check-in & Comments) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validação CSRF (todas as ações POST precisam de token válido)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }
    
    // Toggle Rehearsal
    if ($_POST['action'] === 'toggle_rehearsal') {
        $newState = $_POST['state'] === '1' ? 1 : 0;
        $scheduleRepo->toggleRehearsal($id, $_SESSION['user_id'], $newState);
        header("Location: escala_detalhe.php?id=$id");
        exit;
    }

    // Add Comment
    if ($_POST['action'] === 'add_comment') {
        $comment = trim($_POST['comment']);
        if (!empty($comment)) {
            $scheduleRepo->addComment($id, $_SESSION['user_id'], $comment);
        }
        header("Location: escala_detalhe.php?id=$id#comments");
        exit;
    }

    // Delete Comment
    if ($_POST['action'] === 'delete_comment') {
        $cmtId = $_POST['comment_id'];
        $scheduleRepo->deleteComment($cmtId, $_SESSION['user_id'], $_SESSION['user_role'] === 'admin');
        header("Location: escala_detalhe.php?id=$id#comments");
        exit;
    }
}

// --- LOGICA DE POST/SALVAR MANTIDA (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

    if (isset($_POST['delete_schedule']) && $_SESSION['user_role'] === 'admin') {
        try {
            $scheduleRepo->deleteSchedule($id);
            header("Location: escalas.php?msg=deleted");
            exit;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    if (isset($_POST['save_changes']) && $_SESSION['user_role'] === 'admin') {
        try {
            $data = [
                'event_type' => $_POST['event_type'],
                'event_date' => $_POST['event_date'],
                'event_time' => $_POST['event_time'],
                'notes'      => $_POST['notes'] ?? ''
            ];
            $members = $_POST['members'] ?? [];
            $songs = $_POST['songs'] ?? [];
            
            $scheduleRepo->updateSchedule($id, $data, $members, $songs);

            // Push de convocação (D-03) — dispara após commit bem-sucedido
            try {
                $pushTargets = $scheduleRepo->getPendingParticipantsPushSubscriptions($id);
                if (!empty($pushTargets)) {
                    $notificationService->sendConvocNotification(
                        $id,
                        $_POST['event_type'],
                        $_POST['event_date'],
                        $_POST['event_time'],
                        $pushTargets
                    );
                }
            } catch (Exception $pushEx) {
                error_log('Push convocação falhou: ' . $pushEx->getMessage());
            }

            header("Location: escala_detalhe.php?id=$id&success=1");
            exit;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

// --- BUSCAR DADOS ---
$schedule = $scheduleRepo->getById($id);
if (!$schedule) die("Escala não encontrada.");

$date = new DateTime($schedule['event_date']);
$diaSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][$date->format('w')];

// Buscar Membros
$team = $scheduleRepo->getParticipants($id);
$teamIds = array_column($team, 'user_id');

// Verificar se sou membro desta escala
$myMemberData = null;
foreach($team as $m) {
    if ($m['user_id'] == $_SESSION['user_id']) {
        $myMemberData = $m;
        break;
    }
}

// Buscar Músicas
$songs = $scheduleRepo->getSongs($id);

// Buscar Comentários
$comments = $scheduleRepo->getComments($id);

// Buscar Roteiro de Culto
$roteiro = $scheduleRepo->getRoteiro($id);

// Mapa de custom_tone por song_id (para override nos cards de repertório — ROT-05)
$customToneMap = [];
foreach ($roteiro as $rItem) {
    if ($rItem['item_type'] === 'musica' && $rItem['song_id'] && !empty($rItem['custom_tone'])) {
        $customToneMap[(int)$rItem['song_id']] = $rItem['custom_tone'];
    }
}

// Listas completas para Edit Mode
$allUsers = $pdo->query("SELECT id, name, instrument, avatar_color FROM users ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$allSongs = $pdo->query("SELECT id, title, artist, tone FROM songs ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

$isEditable = isset($_GET['edit']) && $_GET['edit'] == '1' && $_SESSION['user_role'] === 'admin';

renderAppHeader('Detalhes da Escala', 'escalas.php');
?>

<link rel="stylesheet" href="../assets/css/pages/detail_v3.css?v=<?= time() ?>">

<?php renderPageHeader('Detalhes da Escala', $schedule['event_type']); ?>

<?php if (isset($_GET['success'])): ?>
    <div class="feedback-message feedback-success">
        <i data-lucide="check-circle" width="20"></i> Alterações salvas com sucesso!
    </div>
<?php endif; ?>

<div class="scale-detail-wrapper<?= ($myMemberData && $myMemberData['status'] === 'pending') ? ' has-confirm-footer' : '' ?>">

    <!-- UNIFIED COMPACT HEADER -->
    <div class="event-info-card">
        <div class="event-main-row">
            <!-- Compact Date -->
            <div class="event-date-box">
                <div class="event-day"><?= $date->format('d') ?></div>
                <div class="event-month"><?= strtoupper(strftime('%b', $date->getTimestamp())) ?></div>
            </div>
            
            <!-- Event Details -->
            <div class="event-details">
                <div class="event-type"><?= htmlspecialchars($schedule['event_type']) ?></div>
                <div class="event-meta">
                    <span><i data-lucide="calendar" class="align-middle"></i> <?= $diaSemana ?></span>
                    <span><i data-lucide="clock" class="align-middle"></i> <?= substr($schedule['event_time'], 0, 5) ?></span>
                </div>
            </div>

            <!-- Edit Action (Desktop/Admin) -->
            <?php if ($_SESSION['user_role'] === 'admin' && !$isEditable): ?>
            <div class="desktop-only">
                <a href="?id=<?= $id ?>&edit=1" class="btn-icon" title="Editar">
                    <i data-lucide="edit-2" width="18"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:10px;">
            <a href="escala_setlist.php?id=<?= (int)$id ?>"
               style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;font-weight:600;
                      color:var(--color-primary,#3b82f6);text-decoration:none;padding:6px 12px;
                      border:1px solid var(--color-primary,#3b82f6);border-radius:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                    <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                </svg>
                Setlist
            </a>
        </div>

        <?php if($schedule['notes']): ?>
        <div class="event-notes">
            <strong>Observações:</strong> <?= nl2br(htmlspecialchars($schedule['notes'])) ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($isEditable): ?>
        <!-- EDIT FORM -->
        <form method="POST" id="editForm" class="edit-mode-section">
            <?= App\AuthMiddleware::csrfField() ?>
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

            <hr class="divider">

            <!-- ROTEIRO DE CULTO (Edit Mode) -->
            <div class="form-group mt-4" id="roteiro-edit-section">
                <label class="form-label">Roteiro de Culto</label>
                <div id="roteiro-list" class="roteiro-edit-list">
                    <div class="roteiro-empty" id="roteiro-empty-state">Nenhum item no roteiro. Clique em "Adicionar Item".</div>
                </div>
                <button type="button" class="btn-manage" onclick="openRoteiroModal()">
                    <i data-lucide="plus" width="16"></i> Adicionar Item
                </button>
            </div>

            <div class="form-actions-grid">
                 <a href="?id=<?= $id ?>" class="btn-warning w-full text-center text-no-decoration">Cancelar</a>
                 <button type="button" onclick="if(confirm('Excluir esta escala?')) document.getElementById('delForm').submit()" class="btn-danger w-full">Excluir</button>
                 <button type="submit" class="btn-success w-full">Salvar Alterações</button>
            </div>
        </form>
        <form id="delForm" method="POST" style="display:none;"><input type="hidden" name="delete_schedule" value="1"></form>

        <!-- MODAL: Adicionar Item ao Roteiro -->
        <div id="modalRoteiro" class="roteiro-modal-overlay">
            <div class="roteiro-modal-card">
                <h3><i data-lucide="list-ordered" width="18" style="vertical-align:middle;margin-right:6px;"></i>Adicionar Item ao Roteiro</h3>

                <div class="roteiro-field-group">
                    <label>Tipo do Item</label>
                    <select id="roteiro-type" class="form-input w-full" onchange="handleRoteiroTypeChange(this.value)">
                        <option value="musica">🎵 Música</option>
                        <option value="oracao">🙏 Oração</option>
                        <option value="palavra">📖 Palavra</option>
                        <option value="anuncio">📢 Anúncio</option>
                        <option value="intervalo">☕ Intervalo</option>
                        <option value="livre">➕ Livre</option>
                    </select>
                </div>

                <!-- Só aparece quando tipo = musica -->
                <div class="roteiro-field-group" id="roteiro-song-group">
                    <label>Música da Escala</label>
                    <select id="roteiro-song" class="form-input w-full" onchange="onRoteiroSongChange(this)">
                        <option value="">— selecione uma música —</option>
                        <?php foreach ($songs as $sg): ?>
                        <option value="<?= $sg['song_id'] ?>"
                                data-title="<?= htmlspecialchars($sg['title']) ?>"
                                data-tone="<?= htmlspecialchars($sg['tone'] ?? '') ?>">
                            <?= htmlspecialchars($sg['title']) ?> — <?= htmlspecialchars($sg['artist']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tom customizado — só aparece quando tipo = musica -->
                <div class="roteiro-field-group" id="roteiro-tone-group">
                    <label>Tom customizado <small style="font-weight:400;text-transform:none;">(deixe vazio para usar o tom padrão)</small></label>
                    <input type="text" id="roteiro-custom-tone" class="form-input w-full" placeholder="Ex: D, Em, F#m..." maxlength="10">
                </div>

                <!-- Título — obrigatório para não-músicas, opcional para músicas -->
                <div class="roteiro-field-group" id="roteiro-title-group">
                    <label id="roteiro-title-label">Título / Descrição</label>
                    <input type="text" id="roteiro-title" class="form-input w-full" placeholder="Ex: Momento de intercessão" maxlength="255">
                </div>

                <div class="roteiro-field-group">
                    <label>Nota interna <small style="font-weight:400;text-transform:none;">(só você vê — não aparece para músicos)</small></label>
                    <textarea id="roteiro-nota" class="form-input w-full" rows="2" placeholder="Ex: Aqui Diego prega os pedidos de oração"></textarea>
                </div>

                <div class="roteiro-modal-actions">
                    <button type="button" class="btn-warning" onclick="closeRoteiroModal()">Cancelar</button>
                    <button type="button" class="btn-primary" onclick="submitRoteiroItem()">Adicionar</button>
                </div>
            </div>
        </div>

    <?php else: ?>

        <!-- VIEW MODE CONTENT -->
        
        <!-- REHEARSAL CHECK-IN BAR (If User is Member) -->
        <?php if ($myMemberData): ?>
        <div class="check-in-card">
            <div class="check-in-info">
                <?php if ($myMemberData['is_rehearsed']): ?>
                    <div style="background: var(--green-100); color: var(--green-600); padding: 8px; border-radius: 50%;">
                        <i data-lucide="check" width="24"></i>
                    </div>
                    <div>
                        <div class="check-in-title text-green-600">Repertório estudado!</div>
                        <div class="check-in-subtitle">Você está pronto para o ensaio.</div>
                    </div>
                <?php else: ?>
                    <div style="background: var(--bg-body); color: var(--text-muted); padding: 8px; border-radius: 50%;">
                        <i data-lucide="music" width="24"></i>
                    </div>
                    <div>
                        <div class="check-in-title">Prepare-se para ouvir</div>
                        <div class="check-in-subtitle">Marque quando tiver estudado as músicas.</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <?= App\AuthMiddleware::csrfField() ?>
                <input type="hidden" name="action" value="toggle_rehearsal">
                <input type="hidden" name="state" value="<?= $myMemberData['is_rehearsed'] ? '0' : '1' ?>">
                <button type="submit" class="btn-check-in <?= $myMemberData['is_rehearsed'] ? 'checked' : '' ?>">
                    <?php if ($myMemberData['is_rehearsed']): ?>
                        <i data-lucide="check-circle" width="16"></i> Confirmado
                    <?php else: ?>
                        <i data-lucide="circle" width="16"></i> Já estudei
                    <?php endif; ?>
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- PARTICIPANTS SECTION (Compact) -->
        <div class="detail-section">
            <div class="section-header">
                <span class="section-title">Participantes</span>
                <span class="section-count"><?= count($team) ?></span>
            </div>
            
            <?php if(empty($team)): ?>
                <div class="text-muted" style="font-size: 0.85rem; padding: 10px;">Nenhum participante definido.</div>
            <?php else: ?>
                <div class="team-list-grid">
                    <?php foreach($team as $member):
                        $memberStatus = $member['status'] ?? 'pending';
                        // Normalizar: 'absent' exibe como 'declined' visualmente (whitelist — T-02B-02)
                        $statusClass = in_array($memberStatus, ['confirmed', 'declined', 'absent']) ? 'status-' . $memberStatus : 'status-pending';
                        $initials = strtoupper(substr($member['name'], 0, 1));
                        $instr = $member['assigned_instrument'] ?: $member['instrument'] ?: 'Vocal';
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
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:white; font-size:10px;">
                                    <?= $initials ?>
                                </div>
                            <?php endif; ?>
                            <div class="status-indicator <?= $statusClass ?>"></div>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                            <div class="member-role"><?= htmlspecialchars($instr) ?></div>
                            <div class="member-status-badge <?= $statusClass ?>">
                                <?php
                                $statusLabels = [
                                    'status-confirmed' => '&#10003; Confirmado',
                                    'status-pending'   => '&middot; Pendente',
                                    'status-declined'  => '&#10007; Recusado',
                                    'status-absent'    => '&#10007; Ausente',
                                ];
                                echo $statusLabels[$statusClass] ?? '&middot; Pendente';
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- REPERTOIRE SECTION (Compact) -->
        <div class="detail-section">
            <div class="section-header">
                <span class="section-title">Repertório</span>
                <span class="section-count"><?= count($songs) ?></span>
            </div>

            <div class="song-list">
                <?php if (empty($songs)): ?>
                    <div class="text-muted" style="font-size: 0.85rem; padding: 10px;">Nenhuma música selecionada.</div>
                <?php else: ?>
                    <?php foreach ($songs as $index => $song):
                        $resolvedTone = $customToneMap[(int)$song['song_id']] ?? null;
                        $isCustomTone = !empty($resolvedTone);
                        $toneDisplay  = $isCustomTone ? $resolvedTone : ($song['tone'] ?? '');
                    ?>
                    <div class="song-card">
                        <div class="song-order"><?= $index + 1 ?></div>
                        <div class="song-info">
                            <a href="musica_detalhe.php?id=<?= $song['song_id'] ?>" class="song-title"><?= htmlspecialchars($song['title']) ?></a>
                            <div class="song-artist">
                                <?= htmlspecialchars($song['artist']) ?>
                                <?php if ($toneDisplay): ?>
                                    • <span class="song-tone-badge <?= $isCustomTone ? 'song-tone-custom' : 'song-tone-default' ?>"><?= htmlspecialchars($toneDisplay) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="song-actions">
                            <a href="<?= $song['link_letra'] ?: '#' ?>" target="_blank" class="action-icon <?= empty($song['link_letra']) ? 'disabled' : '' ?>" title="Letra">
                                <i data-lucide="align-left" width="18"></i>
                            </a>
                            <a href="<?= $song['link_cifra'] ?: '#' ?>" target="_blank" class="action-icon <?= empty($song['link_cifra']) ? 'disabled' : '' ?>" title="Cifra">
                                <i data-lucide="file-text" width="18"></i>
                            </a>
                            <a href="<?= $song['link_video'] ?: ($song['link_audio'] ?: 'https://www.youtube.com/results?search_query='.urlencode($song['title'].' '.$song['artist'])) ?>" target="_blank" class="action-icon" title="Ouvir">
                                <i data-lucide="play-circle" width="18"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ROTEIRO DE CULTO (View Mode — Read-Only) -->
        <?php if (!empty($roteiro)): ?>
        <div class="detail-section roteiro-view-section">
            <div class="section-header">
                <span class="section-title">Roteiro de Culto</span>
                <span class="section-count"><?= count($roteiro) ?></span>
            </div>

            <?php
            $roteiroIcons = [
                'musica'    => 'music',
                'oracao'    => 'hands',
                'palavra'   => 'book-open',
                'anuncio'   => 'megaphone',
                'intervalo' => 'coffee',
                'livre'     => 'more-horizontal',
            ];
            $roteiroLabels = [
                'musica'    => 'Música',
                'oracao'    => 'Oração',
                'palavra'   => 'Palavra',
                'anuncio'   => 'Anúncio',
                'intervalo' => 'Intervalo',
                'livre'     => 'Livre',
            ];
            foreach ($roteiro as $idx => $item):
                $icon  = $roteiroIcons[$item['item_type']]  ?? 'more-horizontal';
                $label = $roteiroLabels[$item['item_type']] ?? $item['item_type'];

                $displayTitle = ($item['item_type'] === 'musica' && $item['song_title'])
                    ? $item['song_title']
                    : ($item['title'] ?: $label);

                $displayTone = null;
                if ($item['item_type'] === 'musica') {
                    $displayTone = (!empty($item['custom_tone'])) ? $item['custom_tone'] : ($item['song_tone'] ?? null);
                }
            ?>
            <div class="roteiro-view-item">
                <div class="roteiro-view-num"><?= $idx + 1 ?></div>
                <div class="roteiro-view-icon">
                    <i data-lucide="<?= $icon ?>" width="18"></i>
                </div>
                <div class="roteiro-view-info">
                    <div class="roteiro-view-title"><?= htmlspecialchars($displayTitle) ?></div>
                    <div class="roteiro-view-meta">
                        <span><?= htmlspecialchars($label) ?></span>
                        <?php if ($item['item_type'] === 'musica' && $item['song_artist']): ?>
                            <span><?= htmlspecialchars($item['song_artist']) ?></span>
                        <?php endif; ?>
                        <?php if ($displayTone): ?>
                            <span class="roteiro-view-tone"><?= htmlspecialchars($displayTone) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php
                    // nota_interna visível APENAS para admin (ROT-04 — músico não vê)
                    if (!empty($item['nota_interna']) && $_SESSION['user_role'] === 'admin'):
                    ?>
                    <div class="roteiro-view-nota">
                        <i data-lucide="eye-off" width="12" style="flex-shrink:0;margin-top:2px;"></i>
                        <span><?= htmlspecialchars($item['nota_interna']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- COMMENTS SECTION -->
        <div class="detail-section section-box comments-section" id="comments">
            <div class="section-header">
                <div class="section-title">
                    <i data-lucide="message-square" width="16"></i> Comentários da Escala
                </div>
            </div>
            
            <div class="comments-list">
                <?php if(empty($comments)): ?>
                    <div class="empty-state-text" style="flex:1; text-align:center; padding: 20px;">Seja o primeiro a comentar!</div>
                <?php else: ?>
                    <?php foreach($comments as $cmt): 
                        $isMe = $cmt['user_id'] == $_SESSION['user_id'];
                        $avatar = $cmt['avatar'];
                        if ($avatar && strpos($avatar, 'http') === false && strpos($avatar, 'assets') === false && strpos($avatar, 'uploads') === false) {
                            $avatar = '../assets/uploads/' . $avatar;
                        }
                    ?>
                    <div class="comment-item">
                        <div class="comment-avatar-wrapper">
                            <div class="member-avatar" style="background: <?= $cmt['avatar_color'] ?: '#ccc' ?>; width: 40px; height: 40px; font-size: 0.9rem;">
                                <?php if($avatar): ?>
                                    <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($cmt['name']) ?>">
                                <?php else: ?>
                                    <?= strtoupper(substr($cmt['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="comment-body">
                            <div class="comment-header-row">
                                <span class="comment-author-name"><?= htmlspecialchars($cmt['name']) ?></span>
                                <span class="comment-timestamp"><?= date('d/m H:i', strtotime($cmt['created_at'])) ?></span>
                                <?php if($isMe || $_SESSION['user_role'] === 'admin'): ?>
                                    <form method="POST" style="margin-left: auto;" onsubmit="return confirm('Apagar comentário?');">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= $cmt['id'] ?>">
                                        <button type="submit" class="btn-delete-comment" title="Excluir">
                                            <i data-lucide="trash-2" width="14"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="comment-message"><?= nl2br(htmlspecialchars($cmt['comment'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="comment-form-container">
                <form method="POST">
                    <?= App\AuthMiddleware::csrfField() ?>
                    <input type="hidden" name="action" value="add_comment">
                    <div class="comment-input-row">
                        <input type="text" name="comment" class="form-input" placeholder="Escreva uma mensagem..." required autocomplete="off">
                        <button type="submit" class="btn-send-comment">
                            <i data-lucide="send" width="18"></i>
                        </button>
                    </div>
                </form>
            </div>
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
            <div id="listMembers" style="padding-bottom: 60px;">
                <?php 
                $groupedUsers = [];
                foreach($allUsers as $u) {
                    $rawRoles = $u['instrument'] ?: 'Outros';
                    $rolesArray = preg_split('/[,\/]/', $rawRoles);
                    foreach($rolesArray as $role) {
                        $role = trim($role);
                        if(empty($role)) continue;
                        if(strpos(strtolower($role), 'vocal') !== false || strpos(strtolower($role), 'voz') !== false || strpos(strtolower($role), 'cantor') !== false) {
                            $role = 'Vocal';
                        }
                        $role = ucfirst(strtolower($role));
                        if(!isset($groupedUsers[$role])) $groupedUsers[$role] = [];
                        $ids = array_column($groupedUsers[$role], 'id');
                        if(!in_array($u['id'], $ids)) {
                            $groupedUsers[$role][] = $u;
                        }
                    }
                }
                uksort($groupedUsers, function($a, $b) {
                    $order = ['Vocal' => 1, 'Violão' => 2, 'Bateria' => 3, 'Teclado' => 4];
                    $valA = $order[$a] ?? 999;
                    $valB = $order[$b] ?? 999;
                    if ($valA === $valB) return strcasecmp($a, $b);
                    return $valA <=> $valB;
                });
                
                foreach($groupedUsers as $role => $users): 
                    $icon = getInstrumentIcon($role);
                ?>
                <div class="member-group" data-group-role="<?= strtolower(htmlspecialchars($role)) ?>">
                    <div style="padding: 10px; background: var(--bg-body); font-weight: 700; font-size: 0.9rem; color: var(--text-main); position: sticky; top: 0; z-index: 10; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="<?= $icon ?>" width="16"></i> <?= htmlspecialchars($role) ?>
                    </div>
                    <?php foreach($users as $u): 
                        $isChecked = false;
                        if(in_array($u['id'], $teamIds)) {
                             foreach($team as $tm) {
                                 if($tm['user_id'] == $u['id']) {
                                     $pRole = $tm['assigned_instrument'];
                                     if($pRole && strcasecmp($pRole, $role) === 0) $isChecked = true;
                                     elseif(!$pRole) { $isChecked = true; }
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
            <div id="emptySongsState" style="text-align: center; padding: 40px 20px; color: var(--text-muted); display:none;">
                <i data-lucide="search" width="48" style="opacity: 0.3; margin-bottom: 12px;"></i>
                <p>Digite o nome da música ou artista para buscar</p>
            </div>
            <div id="listSongs">
                <?php 
                $selectedSongIds = array_column($songs, 'song_id');
                // Sort songs: Selected first
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
                    <?php if($hasSelectedSongs): ?>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('emptySongsState').style.display = 'none';
                    });
                    <?php else: ?>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('emptySongsState').style.display = 'block';
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
        <div style="padding: 16px; border-top: 1px solid var(--border-subtle); display: flex; gap: 12px; background: var(--bg-surface);">
            <button type="button" class="btn-warning w-full" onclick="document.getElementById('modalSongs').style.display='none'">Cancelar</button>
            <button type="button" class="btn-primary w-full" onclick="confirmSongSelection()">Confirmar</button>
        </div>
    </div>
</div>

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
function filterSongList(text) {
    const emptyState = document.getElementById('emptySongsState');
    const labels = document.querySelectorAll('#listSongs label');
    text = text.toLowerCase().trim();
    
    if(text.length === 0) {
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
        emptyState.style.display = anyVisible ? 'none' : 'block';
    } else {
        let anyVisible = false;
        labels.forEach(l => {
            const searchData = l.getAttribute('data-song-search');
            const match = searchData.includes(text);
            l.style.display = match ? 'flex' : 'none';
            if(match) anyVisible = true;
        });
        emptyState.style.display = anyVisible ? 'none' : 'block';
    }
}

function toggleMemberSelection(cb) {
    const userId = cb.getAttribute('data-user-id');
    const isChecked = cb.checked;
    if(isChecked) {
        const allUserChecks = document.querySelectorAll(`input[data-user-id="${userId}"]`);
        allUserChecks.forEach(box => {
            if(box !== cb) box.checked = false;
        });
    }
}

function confirmMemberSelection() {
    const bag = document.getElementById('members-bag');
    bag.innerHTML = '';
    const allChecks = document.querySelectorAll('#listMembers input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const userId = cb.getAttribute('data-user-id');
        const role = cb.getAttribute('data-role');
        const name = cb.nextElementSibling.querySelector('span').textContent;
        const sp = document.createElement('span');
        sp.className = 'badge';
        sp.id = 'm-badge-'+userId;
        sp.innerHTML = `${name} <small>(${role})</small> <i data-lucide="x" style="cursor:pointer; width:12px;" onclick="removeMember(${userId})"></i><input type="hidden" name="members[${userId}]" value="${role}">`;
        bag.appendChild(sp);
    });
    lucide.createIcons();
    document.getElementById('modalMembers').style.display = 'none';
}

function removeMember(id) {
    const badge = document.getElementById('m-badge-'+id);
    if(badge) badge.remove();
    const allUserChecks = document.querySelectorAll(`input[data-user-id="${id}"]`);
    allUserChecks.forEach(box => box.checked = false);
}

function toggleSong(cb) {}

function confirmSongSelection() {
    const bag = document.getElementById('songs-bag');
    bag.innerHTML = '';
    const allChecks = document.querySelectorAll('#listSongs input[type="checkbox"]:checked');
    allChecks.forEach(cb => {
        const id = cb.value;
        const title = cb.getAttribute('data-title');
        const div = document.createElement('div');
        div.className = 'song-card-compact';
        div.id = 's-badge-'+id;
        div.style.cssText = 'background:var(--bg-body); padding:8px; border-radius:8px; border:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;';
        div.innerHTML = `<span>${title}</span><div style="display:flex; gap:8px;"><input type="hidden" name="songs[]" value="${id}"><i data-lucide="x" style="cursor:pointer; width:16px;" onclick="removeSong(${id})"></i></div>`;
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
        document.getElementById('summary-type').innerText = document.getElementById('input_event_type').value;
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
        document.getElementById('input_event_type').value = originalInfo.type;
        document.getElementById('input_event_date').value = originalInfo.date;
        document.getElementById('input_event_time').value = originalInfo.time;
        document.getElementById('input_notes').value = originalInfo.notes;
    }
    document.getElementById('modalInfo').style.display = 'none';
}

</script>
<?php endif; ?>

<?php if ($myMemberData !== null && !$isEditable):
    $currentStatus = $myMemberData['status'];
    $scheduleIdForJs = (int)$id;
?>

<?php if ($currentStatus === 'pending'): ?>
<div class="confirm-footer" id="confirm-footer">
    <button class="btn-confirm" onclick="confirmPresence('confirmed')">
        <i data-lucide="check-circle" width="20"></i> Confirmar
    </button>
    <button class="btn-decline" onclick="confirmPresence('declined')">
        <i data-lucide="x-circle" width="20"></i> Recusar
    </button>
</div>
<?php elseif ($currentStatus === 'confirmed'): ?>
<div class="confirm-footer-status" id="confirm-footer">
    <span class="status-label is-confirmed">
        <i data-lucide="check-circle" width="20"></i> Confirmado
    </span>
    <button class="btn-change" onclick="showConfirmButtons()">Alterar</button>
</div>
<?php elseif ($currentStatus === 'declined'): ?>
<div class="confirm-footer-status" id="confirm-footer">
    <span class="status-label is-declined">
        <i data-lucide="x-circle" width="20"></i> Recusado
    </span>
    <button class="btn-change" onclick="showConfirmButtons()">Alterar</button>
</div>
<?php endif; ?>

<script>
(function() {
    var scheduleId = <?= $scheduleIdForJs ?>;

    function confirmPresence(status) {
        var footer = document.getElementById('confirm-footer');
        var btnConfirm = footer.querySelector('.btn-confirm');
        var btnDecline = footer.querySelector('.btn-decline');
        if (btnConfirm) btnConfirm.disabled = true;
        if (btnDecline) btnDecline.disabled = true;

        fetch('../api/confirm_scale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ schedule_id: scheduleId, status: status })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var isConfirmed = (status === 'confirmed');
                var labelText = isConfirmed ? 'Confirmado' : 'Recusado';
                var labelClass = isConfirmed ? 'is-confirmed' : 'is-declined';
                var iconName = isConfirmed ? 'check-circle' : 'x-circle';

                footer.outerHTML = '<div class="confirm-footer-status" id="confirm-footer">'
                    + '<span class="status-label ' + labelClass + '">'
                    + '<i data-lucide="' + iconName + '" width="20"></i> ' + labelText
                    + '</span>'
                    + '<button class="btn-change" onclick="showConfirmButtons()">Alterar</button>'
                    + '</div>';

                // Re-render Lucide icons no novo elemento
                if (typeof lucide !== 'undefined') lucide.createIcons();

                // Atualizar padding do wrapper
                var wrapper = document.querySelector('.scale-detail-wrapper');
                if (wrapper) wrapper.classList.remove('has-confirm-footer');
            } else {
                alert('Erro ao salvar: ' + (data.message || 'Tente novamente.'));
                if (btnConfirm) btnConfirm.disabled = false;
                if (btnDecline) btnDecline.disabled = false;
            }
        })
        .catch(function(err) {
            alert('Erro de conexão. Tente novamente.');
            if (btnConfirm) btnConfirm.disabled = false;
            if (btnDecline) btnDecline.disabled = false;
        });
    }

    function showConfirmButtons() {
        var footer = document.getElementById('confirm-footer');
        footer.outerHTML = '<div class="confirm-footer" id="confirm-footer">'
            + '<button class="btn-confirm" onclick="confirmPresence(\'confirmed\')">'
            + '<i data-lucide="check-circle" width="20"></i> Confirmar'
            + '</button>'
            + '<button class="btn-decline" onclick="confirmPresence(\'declined\')">'
            + '<i data-lucide="x-circle" width="20"></i> Recusar'
            + '</button>'
            + '</div>';

        if (typeof lucide !== 'undefined') lucide.createIcons();

        var wrapper = document.querySelector('.scale-detail-wrapper');
        if (wrapper) wrapper.classList.add('has-confirm-footer');
    }

    // Expor para onclick inline
    window.confirmPresence = confirmPresence;
    window.showConfirmButtons = showConfirmButtons;
})();
</script>

<?php endif; ?>

<?php if ($isEditable): ?>
<script>
(function() {
    var scheduleId = <?= (int)$id ?>;
    var roteiroItems = [];

    var ROTEIRO_ICONS = {
        musica: 'music', oracao: 'hands', palavra: 'book-open',
        anuncio: 'megaphone', intervalo: 'coffee', livre: 'more-horizontal'
    };
    var ROTEIRO_LABELS = {
        musica: 'Música', oracao: 'Oração', palavra: 'Palavra',
        anuncio: 'Anúncio', intervalo: 'Intervalo', livre: 'Livre'
    };

    function loadRoteiro() {
        fetch('../api/roteiro.php?schedule_id=' + scheduleId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    roteiroItems = data.data || [];
                    renderRoteiroList();
                }
            });
    }

    function renderRoteiroList() {
        var list = document.getElementById('roteiro-list');
        var emptyState = document.getElementById('roteiro-empty-state');
        if (!list) return;

        var existingItems = list.querySelectorAll('.roteiro-item');
        existingItems.forEach(function(el) { el.remove(); });

        if (roteiroItems.length === 0) {
            if (emptyState) emptyState.style.display = 'block';
            return;
        }
        if (emptyState) emptyState.style.display = 'none';

        roteiroItems.forEach(function(item, idx) {
            var isFirst = idx === 0;
            var isLast  = idx === roteiroItems.length - 1;
            var icon    = ROTEIRO_ICONS[item.item_type] || 'more-horizontal';
            var label   = ROTEIRO_LABELS[item.item_type] || item.item_type;

            var displayTitle = item.item_type === 'musica' && item.song_title
                ? item.song_title
                : (item.title || label);

            var subParts = [label];
            if (item.item_type === 'musica' && item.song_artist) subParts.push(item.song_artist);
            if (item.custom_tone) subParts.push('<strong>' + escHtml(item.custom_tone) + '</strong>');
            else if (item.song_tone) subParts.push(escHtml(item.song_tone));

            var notaHtml = item.nota_interna
                ? '<div class="roteiro-item-nota"><i data-lucide="eye-off" style="width:11px;vertical-align:middle;margin-right:3px;"></i>' + escHtml(item.nota_interna) + '</div>'
                : '';

            var el = document.createElement('div');
            el.className = 'roteiro-item';
            el.dataset.id  = item.id;
            el.dataset.pos = item.order_position;
            el.innerHTML = ''
                + '<div class="roteiro-item-icon"><i data-lucide="' + icon + '" width="18"></i></div>'
                + '<div class="roteiro-item-info">'
                +   '<div class="roteiro-item-title">' + escHtml(displayTitle) + '</div>'
                +   '<div class="roteiro-item-sub">' + subParts.join(' · ') + '</div>'
                +   notaHtml
                + '</div>'
                + '<div class="roteiro-item-actions">'
                +   '<button type="button" class="roteiro-btn-arrow" onclick="moveRoteiroItem(' + idx + ', -1)" ' + (isFirst ? 'disabled' : '') + ' title="Mover para cima">▲</button>'
                +   '<button type="button" class="roteiro-btn-arrow" onclick="moveRoteiroItem(' + idx + ', 1)"  ' + (isLast  ? 'disabled' : '') + ' title="Mover para baixo">▼</button>'
                + '</div>'
                + '<button type="button" class="roteiro-btn-delete" onclick="deleteRoteiroItem(' + item.id + ')" title="Excluir item">'
                +   '<i data-lucide="trash-2" width="16"></i>'
                + '</button>';
            list.appendChild(el);
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function moveRoteiroItem(idx, direction) {
        var newIdx = idx + direction;
        if (newIdx < 0 || newIdx >= roteiroItems.length) return;

        var tmp = roteiroItems[idx];
        roteiroItems[idx] = roteiroItems[newIdx];
        roteiroItems[newIdx] = tmp;

        roteiroItems.forEach(function(item, i) { item.order_position = i; });
        renderRoteiroList();

        var payload = roteiroItems.map(function(item) { return {id: item.id, pos: item.order_position}; });
        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'reorder', schedule_id: scheduleId, items: payload})
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.success) alert('Erro ao reordenar: ' + (data.message || ''));
        });
    }

    function deleteRoteiroItem(itemId) {
        if (!confirm('Excluir este item do roteiro?')) return;
        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: itemId, schedule_id: scheduleId})
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                roteiroItems = roteiroItems.filter(function(i) { return i.id !== itemId; });
                roteiroItems.forEach(function(item, i) { item.order_position = i; });
                renderRoteiroList();
            } else {
                alert('Erro ao excluir: ' + (data.message || ''));
            }
        });
    }

    window.openRoteiroModal = function() {
        document.getElementById('roteiro-type').value = 'musica';
        document.getElementById('roteiro-song').value = '';
        document.getElementById('roteiro-custom-tone').value = '';
        document.getElementById('roteiro-title').value = '';
        document.getElementById('roteiro-nota').value = '';
        handleRoteiroTypeChange('musica');
        var m = document.getElementById('modalRoteiro');
        m.style.display = 'flex';
    };

    window.closeRoteiroModal = function() {
        document.getElementById('modalRoteiro').style.display = 'none';
    };

    window.handleRoteiroTypeChange = function(type) {
        var isSong = type === 'musica';
        document.getElementById('roteiro-song-group').style.display  = isSong ? 'block' : 'none';
        document.getElementById('roteiro-tone-group').style.display  = isSong ? 'block' : 'none';
        var titleLabel = document.getElementById('roteiro-title-label');
        if (titleLabel) titleLabel.textContent = isSong ? 'Título alternativo (opcional)' : 'Título / Descrição *';
    };

    window.onRoteiroSongChange = function(sel) {
        var tone = sel.options[sel.selectedIndex].getAttribute('data-tone') || '';
        var toneInput = document.getElementById('roteiro-custom-tone');
        if (toneInput) toneInput.placeholder = tone ? 'Ex: ' + tone + ' (padrão — deixe vazio para manter)' : 'Ex: D, Em, F#m...';
    };

    window.submitRoteiroItem = function() {
        var type = document.getElementById('roteiro-type').value;
        var songId = type === 'musica' ? (parseInt(document.getElementById('roteiro-song').value) || null) : null;
        var customTone = type === 'musica' ? (document.getElementById('roteiro-custom-tone').value.trim() || null) : null;
        var title = document.getElementById('roteiro-title').value.trim() || null;
        var nota = document.getElementById('roteiro-nota').value.trim() || null;

        if (type !== 'musica' && !title) {
            alert('Preencha o título do item.');
            document.getElementById('roteiro-title').focus();
            return;
        }

        var payload = {
            action: 'add',
            schedule_id: scheduleId,
            item_type: type,
            song_id: songId,
            custom_tone: customTone,
            title: title,
            nota_interna: nota
        };

        fetch('../api/roteiro.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) {
                closeRoteiroModal();
                loadRoteiro();
            } else {
                alert('Erro ao adicionar: ' + (data.message || ''));
            }
        });
    };

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    window.moveRoteiroItem   = moveRoteiroItem;
    window.deleteRoteiroItem = deleteRoteiroItem;

    loadRoteiro();
})();
</script>
<?php endif; ?>

<?php renderAppFooter(); ?>
