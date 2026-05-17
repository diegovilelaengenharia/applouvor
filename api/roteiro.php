<?php
// api/roteiro.php — CRUD para itens do roteiro de culto
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/db.php';

$userId   = $_SESSION['user_id']   ?? 0;
$userRole = $_SESSION['user_role'] ?? '';

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: listar itens do roteiro ──────────────────────────────────────────────
if ($method === 'GET') {
    $scheduleId = (int)($_GET['schedule_id'] ?? 0);
    if (!$scheduleId) {
        echo json_encode(['success' => false, 'message' => 'schedule_id obrigatório']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT r.id, r.order_position, r.item_type, r.title,
                   r.song_id, r.custom_tone, r.nota_interna,
                   s.title as song_title, s.artist as song_artist, s.tone as song_tone
            FROM schedule_roteiro r
            LEFT JOIN songs s ON s.id = r.song_id
            WHERE r.schedule_id = ?
            ORDER BY r.order_position ASC, r.id ASC
        ");
        $stmt->execute([$scheduleId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // nota_interna só retorna para admin
        if ($userRole !== 'admin') {
            foreach ($items as &$item) {
                $item['nota_interna'] = null;
            }
            unset($item);
        }

        echo json_encode(['success' => true, 'data' => $items]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar roteiro']);
    }
    exit;
}

// ── POST: add / delete / reorder ──────────────────────────────────────────────
if ($method === 'POST') {
    // Somente admin pode modificar o roteiro
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Apenas administradores podem editar o roteiro']);
        exit;
    }

    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';

    // Validar que schedule existe (guard comum a todas as ações de escrita)
    $scheduleId = (int)($data['schedule_id'] ?? 0);
    if ($scheduleId) {
        $chk = $pdo->prepare("SELECT id FROM schedules WHERE id = ?");
        $chk->execute([$scheduleId]);
        if (!$chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Escala não encontrada']);
            exit;
        }
    }

    // ── action: add ──────────────────────────────────────────────────────────
    if ($action === 'add') {
        if (!$scheduleId) {
            echo json_encode(['success' => false, 'message' => 'schedule_id obrigatório']);
            exit;
        }

        $validTypes = ['musica', 'oracao', 'palavra', 'anuncio', 'intervalo', 'livre'];
        $itemType   = in_array($data['item_type'] ?? '', $validTypes) ? $data['item_type'] : 'livre';
        $title      = isset($data['title'])        ? trim(substr($data['title'], 0, 255))       : null;
        $songId     = ($itemType === 'musica' && !empty($data['song_id'])) ? (int)$data['song_id'] : null;
        $customTone = ($itemType === 'musica' && !empty($data['custom_tone'])) ? trim(substr($data['custom_tone'], 0, 10)) : null;
        $notaInterna = isset($data['nota_interna']) ? trim($data['nota_interna']) : null;
        $notaInterna = $notaInterna === '' ? null : $notaInterna;

        // Validar song_id existe (se fornecido)
        if ($songId) {
            $sc = $pdo->prepare("SELECT id FROM songs WHERE id = ?");
            $sc->execute([$songId]);
            if (!$sc->fetch()) $songId = null;
        }

        // Próxima posição = max atual + 1
        $posStmt = $pdo->prepare("SELECT COALESCE(MAX(order_position), -1) + 1 FROM schedule_roteiro WHERE schedule_id = ?");
        $posStmt->execute([$scheduleId]);
        $nextPos = (int)$posStmt->fetchColumn();

        try {
            $ins = $pdo->prepare("
                INSERT INTO schedule_roteiro
                    (schedule_id, order_position, item_type, title, song_id, custom_tone, nota_interna)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$scheduleId, $nextPos, $itemType, $title ?: null, $songId, $customTone, $notaInterna]);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'order_position' => $nextPos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar item']);
        }
        exit;
    }

    // ── action: delete ────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $itemId = (int)($data['id'] ?? 0);
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'id do item obrigatório']);
            exit;
        }

        try {
            $del = $pdo->prepare("DELETE FROM schedule_roteiro WHERE id = ?");
            $del->execute([$itemId]);
            if ($del->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
                exit;
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao deletar item']);
        }
        exit;
    }

    // ── action: reorder ───────────────────────────────────────────────────────
    if ($action === 'reorder') {
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            echo json_encode(['success' => false, 'message' => 'items[] obrigatório']);
            exit;
        }

        try {
            $upd = $pdo->prepare("UPDATE schedule_roteiro SET order_position = ? WHERE id = ?");
            foreach ($items as $item) {
                $iId  = (int)($item['id']  ?? 0);
                $iPos = (int)($item['pos'] ?? 0);
                if ($iId > 0) $upd->execute([$iPos, $iId]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao reordenar']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'action inválida']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método não permitido']);
