<?php
// api/admin/escalas_api.php
// API JSON robusta para listagem e detalhamento de Escalas (próximas e anteriores).

require_once '../../src/helpers/auth.php';
require_once '../../src/config/db.php';
require_once '../../src/classes/ScheduleRepository.php';

header('Content-Type: application/json');

// Se o usuário não estiver logado, retornamos 401
$loggedUserId = $_SESSION['user_id'] ?? 0;
if (!$loggedUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$scheduleRepo = new \App\Repositories\ScheduleRepository($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($id > 0) {
        // DETALHE DE UMA ESCALA
        $schedule = $scheduleRepo->getById($id);
        if (!$schedule) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Escala não encontrada']);
            exit;
        }

        $participants = $scheduleRepo->getParticipants($id);
        $songs = $scheduleRepo->getSongs($id);
        $roteiro = $scheduleRepo->getRoteiro($id);
        $comments = $scheduleRepo->getComments($id);

        // Limpar caminhos das fotos para o frontend
        foreach ($participants as &$p) {
            if (!empty($p['photo'])) {
                if (strpos($p['photo'], 'http') === false && strpos($p['photo'], 'assets') === false && strpos($p['photo'], 'uploads') === false) {
                    $p['photo'] = '../uploads/' . $p['photo'];
                } elseif (strpos($p['photo'], 'assets/') === 0) {
                    $p['photo'] = '../' . $p['photo'];
                }
            } else {
                $p['photo'] = 'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=2e7eed&color=fff';
            }
        }
        unset($p);

        echo json_encode([
            'success' => true,
            'data' => [
                'schedule' => $schedule,
                'participants' => $participants,
                'songs' => $songs,
                'roteiro' => $roteiro,
                'comments' => $comments
            ]
        ]);
        exit;
    } else {
        // LISTAGEM DE ESCALAS
        $filterType = $_GET['type'] ?? '';
        $filterMine = isset($_GET['mine']) && $_GET['mine'] === '1';

        $futureResults = $scheduleRepo->getFutureSchedules($filterType);
        $pastResults = $scheduleRepo->getPastSchedules($filterType, 15);

        $allSchedules = array_merge($futureResults, $pastResults);
        $scheduleIds = array_column($allSchedules, 'id');

        $participantsMap = $scheduleRepo->getParticipantsByScheduleIds($scheduleIds);
        $songCountsMap = $scheduleRepo->getSongCountsByScheduleIds($scheduleIds);

        // Processar escalas
        $processSchedules = function($schedules) use ($participantsMap, $songCountsMap, $loggedUserId, $filterMine) {
            $processed = [];
            foreach ($schedules as $s) {
                $schId = $s['id'];
                $participants = $participantsMap[$schId] ?? [];
                $songsCount = $songCountsMap[$schId] ?? 0;

                // Verificar se o usuário logado está escalado e qual seu status
                $isMine = false;
                $myStatus = 'pending';
                $myRole = '';
                foreach ($participants as $p) {
                    if ($p['user_id'] == $loggedUserId) {
                        $isMine = true;
                        $myStatus = $p['status'];
                        $myRole = $p['assigned_instrument'] ?? $p['instrument'] ?? '';
                        break;
                    }
                }

                if ($filterMine && !$isMine) {
                    continue;
                }

                // Ajustar caminhos das fotos
                foreach ($participants as &$p) {
                    if (!empty($p['photo'])) {
                        if (strpos($p['photo'], 'http') === false && strpos($p['photo'], 'assets') === false && strpos($p['photo'], 'uploads') === false) {
                            $p['photo'] = '../uploads/' . $p['photo'];
                        } elseif (strpos($p['photo'], 'assets/') === 0) {
                            $p['photo'] = '../' . $p['photo'];
                        }
                    } else {
                        $p['photo'] = 'https://ui-avatars.com/api/?name=' . urlencode($p['name']) . '&background=2e7eed&color=fff';
                    }
                }
                unset($p);

                $s['participants'] = $participants;
                $s['songs_count'] = $songsCount;
                $s['is_mine'] = $isMine;
                $s['my_status'] = $myStatus;
                $s['my_role'] = $myRole;

                $processed[] = $s;
            }
            return $processed;
        };

        echo json_encode([
            'success' => true,
            'data' => [
                'future' => $processSchedules($futureResults),
                'past' => $processSchedules($pastResults)
            ]
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    exit;
}
