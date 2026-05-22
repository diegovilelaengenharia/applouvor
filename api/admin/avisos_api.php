<?php
/**
 * API para gerenciamento de avisos, tags e reações da SPA React
 * Endpoints:
 * - GET /avisos_api.php?action=list
 * - GET /avisos_api.php?action=list_tags
 * - POST /avisos_api.php?action=create
 * - POST /avisos_api.php?action=update
 * - POST /avisos_api.php?action=delete
 * - POST /avisos_api.php?action=archive
 * - POST /avisos_api.php?action=create_tag
 * - POST /avisos_api.php?action=update_tag
 * - POST /avisos_api.php?action=delete_tag
 * - POST /avisos_api.php?action=mark_read
 * - POST /avisos_api.php?action=toggle_reaction
 * - GET /avisos_api.php?action=get_unread_count
 * - POST /avisos_api.php?action=pin_aviso
 * - GET /avisos_api.php?action=get_aviso_stats
 */

require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';

checkLogin();

// Suporte para CORS ou JSON Request Body (Vite dev server roda em porta diferente)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Em requisições do React usando fetch (JSON body), a action pode vir dentro do body
if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawBody = file_get_contents('php://input');
    $bodyData = json_decode($rawBody, true);
    if (isset($bodyData['action'])) {
        $action = $bodyData['action'];
    }
}

try {
    switch ($action) {
        // ===== LISTAR AVISOS (COM REAÇÕES E TAGS) =====
        case 'list':
            $showArchived = isset($_GET['archived']) && ($_GET['archived'] === '1' || $_GET['archived'] === 'true');
            $showHistory = isset($_GET['history']) && ($_GET['history'] === '1' || $_GET['history'] === 'true');
            $filterPriority = $_GET['priority'] ?? 'all';
            $filterType = $_GET['type'] ?? 'all';
            $search = $_GET['search'] ?? '';

            $sql = "SELECT a.*, u.name as author_name, u.photo as author_avatar 
                    FROM avisos a 
                    LEFT JOIN users u ON a.created_by = u.id 
                    WHERE 1=1";
            $params = [];

            if ($showArchived) {
                $sql .= " AND a.archived_at IS NOT NULL";
            } else {
                $sql .= " AND a.archived_at IS NULL";
            }

            if (!$showHistory) {
                $sql .= " AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())";
            }

            if ($filterPriority !== 'all') {
                $sql .= " AND a.priority = ?";
                $params[] = $filterPriority;
            }

            if ($filterType !== 'all') {
                $sql .= " AND a.type = ?";
                $params[] = $filterType;
            }

            if (!empty($search)) {
                $sql .= " AND (a.title LIKE ? OR a.message LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            $sql .= " ORDER BY a.is_pinned DESC, 
                     CASE WHEN a.priority = 'urgent' THEN 1 WHEN a.priority = 'important' THEN 2 ELSE 3 END ASC,
                     a.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $avisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Buscar reações e tags para cada aviso
            if (!empty($avisos)) {
                $avisoIds = array_column($avisos, 'id');
                $placeholders = str_repeat('?,', count($avisoIds) - 1) . '?';
                
                // Reações
                $allReactions = [];
                try {
                    $stmtReactions = $pdo->prepare("SELECT * FROM aviso_reactions WHERE aviso_id IN ($placeholders)");
                    $stmtReactions->execute($avisoIds);
                    $allReactions = $stmtReactions->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}

                // Relação de Tags
                $allTagsRelations = [];
                try {
                    $stmtTags = $pdo->prepare("
                        SELECT atr.aviso_id, t.* 
                        FROM aviso_tags t
                        INNER JOIN aviso_tag_relations atr ON t.id = atr.tag_id
                        WHERE atr.aviso_id IN ($placeholders)
                    ");
                    $stmtTags->execute($avisoIds);
                    $allTagsRelations = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}

                // Mapear leituras
                $allReads = [];
                try {
                    $stmtReads = $pdo->prepare("SELECT aviso_id FROM aviso_reads WHERE user_id = ? AND aviso_id IN ($placeholders)");
                    $stmtReads->execute(array_merge([$userId], $avisoIds));
                    $allReads = $stmtReads->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $e) {}

                foreach ($avisos as &$av) {
                    $av['reactions'] = ['like' => 0, 'confirm' => 0];
                    $av['user_reacted'] = ['like' => false, 'confirm' => false];
                    $av['tags'] = [];
                    $av['is_read'] = in_array($av['id'], $allReads);

                    foreach ($allReactions as $r) {
                        if ($r['aviso_id'] == $av['id']) {
                            if (isset($av['reactions'][$r['reaction_type']])) {
                                $av['reactions'][$r['reaction_type']]++;
                            }
                            if ($r['user_id'] == $userId) {
                                $av['user_reacted'][$r['reaction_type']] = true;
                            }
                        }
                    }

                    foreach ($allTagsRelations as $t) {
                        if ($t['aviso_id'] == $av['id']) {
                            $av['tags'][] = [
                                'id' => (int)$t['id'],
                                'name' => $t['name'],
                                'color' => $t['color'],
                                'icon' => $t['icon']
                            ];
                        }
                    }
                    
                    // Normalizar fotos de avatar
                    if (!empty($av['author_avatar']) && strpos($av['author_avatar'], 'http') === false) {
                        if (strpos($av['author_avatar'], 'assets') === false && strpos($av['author_avatar'], 'uploads') === false) {
                            $av['author_avatar'] = '../uploads/' . $av['author_avatar'];
                        } else {
                            $av['author_avatar'] = '../' . $av['author_avatar'];
                        }
                    }
                }
                unset($av);
            }

            echo json_encode(['success' => true, 'avisos' => $avisos]);
            break;

        // ===== CRUD AVISOS =====
        case 'create':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem criar avisos');
            }
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;

            $title = $data['title'] ?? '';
            $message = $data['message'] ?? '';
            $priority = $data['priority'] ?? 'normal';
            $type = $data['type'] ?? 'geral';
            $targetAudience = $data['target_audience'] ?? 'all';
            $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : NULL;
            $tagsSelected = isset($data['tags']) ? (is_array($data['tags']) ? $data['tags'] : json_decode($data['tags'], true)) : [];

            if (empty($title) || empty($message)) {
                throw new Exception('Título e mensagem são obrigatórios');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO avisos (title, message, priority, type, target_audience, expires_at, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$title, $message, $priority, $type, $targetAudience, $expiresAt, $userId]);
            $avisoId = $pdo->lastInsertId();

            // Relações de Tags
            if (!empty($tagsSelected)) {
                $stmtTagRel = $pdo->prepare("INSERT INTO aviso_tag_relations (aviso_id, tag_id) VALUES (?, ?)");
                foreach ($tagsSelected as $tagId) {
                    $stmtTagRel->execute([$avisoId, $tagId]);
                }
            }

            // Notificações
            try {
                if (file_exists('../src/helpers/notification_system.php')) {
                    require_once '../src/helpers/notification_system.php';
                    $notificationSystem = new NotificationSystem($pdo);
                    $notifType = $priority === 'urgent' ? 'aviso_urgent' : 'new_aviso';
                    
                    $usersQuery = "SELECT id FROM users WHERE status = 'active'";
                    if ($targetAudience === 'admins') {
                        $usersQuery .= " AND role = 'admin'";
                    } elseif ($targetAudience === 'team') {
                        $usersQuery .= " AND role IN ('admin', 'member')";
                    }
                    
                    $stmtUsers = $pdo->query($usersQuery);
                    $users = $stmtUsers->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($users as $uid) {
                        if ($uid == $userId) continue;
                        
                        $notificationSystem->createNotification(
                            $uid,
                            $notifType,
                            "Novo Aviso: $title",
                            strip_tags($message),
                            "avisos.php"
                        );
                    }
                }
            } catch (Exception $e) {
                error_log("Erro ao enviar notificações na API: " . $e->getMessage());
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Aviso publicado com sucesso', 'aviso_id' => $avisoId]);
            break;

        case 'update':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem atualizar avisos');
            }

            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;

            $id = $data['id'] ?? 0;
            $title = $data['title'] ?? '';
            $message = $data['message'] ?? '';
            $priority = $data['priority'] ?? 'normal';
            $type = $data['type'] ?? 'geral';
            $targetAudience = $data['target_audience'] ?? 'all';
            $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : NULL;
            $tagsSelected = isset($data['tags']) ? (is_array($data['tags']) ? $data['tags'] : json_decode($data['tags'], true)) : [];

            if (!$id || empty($title) || empty($message)) {
                throw new Exception('ID, título e mensagem são obrigatórios');
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE avisos SET title = ?, message = ?, priority = ?, type = ?, target_audience = ?, expires_at = ? WHERE id = ?");
            $stmt->execute([$title, $message, $priority, $type, $targetAudience, $expiresAt, $id]);

            // Atualizar Tags
            $stmtDel = $pdo->prepare("DELETE FROM aviso_tag_relations WHERE aviso_id = ?");
            $stmtDel->execute([$id]);

            if (!empty($tagsSelected)) {
                $stmtTagRel = $pdo->prepare("INSERT INTO aviso_tag_relations (aviso_id, tag_id) VALUES (?, ?)");
                foreach ($tagsSelected as $tagId) {
                    $stmtTagRel->execute([$id, $tagId]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Aviso atualizado com sucesso']);
            break;

        case 'delete':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem deletar avisos');
            }

            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            $id = $data['id'] ?? 0;

            if (!$id) {
                throw new Exception('ID do aviso é obrigatório');
            }

            $pdo->beginTransaction();

            // Deletar relações de tags
            $stmtDelTags = $pdo->prepare("DELETE FROM aviso_tag_relations WHERE aviso_id = ?");
            $stmtDelTags->execute([$id]);

            // Deletar reações e leituras
            $stmtDelReac = $pdo->prepare("DELETE FROM aviso_reactions WHERE aviso_id = ?");
            $stmtDelReac->execute([$id]);
            $stmtDelReads = $pdo->prepare("DELETE FROM aviso_reads WHERE aviso_id = ?");
            $stmtDelReads->execute([$id]);

            // Deletar aviso
            $stmt = $pdo->prepare("DELETE FROM avisos WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Aviso excluído com sucesso']);
            break;

        case 'archive':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem arquivar avisos');
            }

            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            $id = $data['id'] ?? 0;
            $archive = isset($data['archive']) ? (bool)$data['archive'] : true;

            if (!$id) {
                throw new Exception('ID do aviso é obrigatório');
            }

            if ($archive) {
                $stmt = $pdo->prepare("UPDATE avisos SET archived_at = NOW() WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE avisos SET archived_at = NULL WHERE id = ?");
            }
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => $archive ? 'Aviso arquivado' : 'Aviso desarquivado']);
            break;

        // ===== REAÇÕES =====
        case 'toggle_reaction':
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            
            $avisoId = $data['aviso_id'] ?? 0;
            $type = $data['reaction_type'] ?? 'like'; // 'like' ou 'confirm'

            if (!$avisoId) {
                throw new Exception('ID do aviso é obrigatório');
            }

            // Verificar se já existe a reação
            $stmt = $pdo->prepare("SELECT id FROM aviso_reactions WHERE aviso_id = ? AND user_id = ? AND reaction_type = ?");
            $stmt->execute([$avisoId, $userId, $type]);
            $reaction = $stmt->fetch();

            $pdo->beginTransaction();
            if ($reaction) {
                // Remover
                $stmtDel = $pdo->prepare("DELETE FROM aviso_reactions WHERE aviso_id = ? AND user_id = ? AND reaction_type = ?");
                $stmtDel->execute([$avisoId, $userId, $type]);
                $reacted = false;
            } else {
                // Adicionar
                $stmtIns = $pdo->prepare("INSERT INTO aviso_reactions (aviso_id, user_id, reaction_type) VALUES (?, ?, ?)");
                $stmtIns->execute([$avisoId, $userId, $type]);
                $reacted = true;
                
                // Se for confirmação de leitura, marcar como lido em aviso_reads
                if ($type === 'confirm') {
                    $stmtRead = $pdo->prepare("
                        INSERT INTO aviso_reads (user_id, aviso_id) 
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
                    ");
                    $stmtRead->execute([$userId, $avisoId]);
                }
            }
            $pdo->commit();

            // Buscar contagem atualizada
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM aviso_reactions WHERE aviso_id = ? AND reaction_type = ?");
            $stmtCount->execute([$avisoId, $type]);
            $count = (int)$stmtCount->fetchColumn();

            echo json_encode([
                'success' => true,
                'reacted' => $reacted,
                'count' => $count,
                'message' => 'Reação atualizada com sucesso'
            ]);
            break;

        // ===== TAGS =====
        case 'list_tags':
            $stmt = $pdo->query("SELECT * FROM aviso_tags ORDER BY is_default DESC, name ASC");
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Normalizar IDs
            foreach ($tags as &$t) {
                $t['id'] = (int)$t['id'];
                $t['is_default'] = (bool)$t['is_default'];
            }
            echo json_encode(['success' => true, 'tags' => $tags]);
            break;

        case 'create_tag':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem criar tags');
            }
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            
            $name = $data['name'] ?? '';
            $color = $data['color'] ?? 'var(--slate-500)';
            $icon = $data['icon'] ?? 'tag';
            
            if (empty($name)) {
                throw new Exception('Nome da tag é obrigatório');
            }
            
            $stmt = $pdo->prepare("INSERT INTO aviso_tags (name, color, icon, is_default) VALUES (?, ?, ?, FALSE)");
            $stmt->execute([$name, $color, $icon]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tag criada com sucesso',
                'tag_id' => (int)$pdo->lastInsertId()
            ]);
            break;

        case 'update_tag':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem editar tags');
            }
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            
            $id = $data['id'] ?? 0;
            $name = $data['name'] ?? '';
            $color = $data['color'] ?? '';
            $icon = $data['icon'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE aviso_tags SET name = ?, color = ?, icon = ? WHERE id = ? AND is_default = FALSE");
            $stmt->execute([$name, $color, $icon, $id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Tag não encontrada ou é uma tag padrão');
            }
            
            echo json_encode(['success' => true, 'message' => 'Tag atualizada com sucesso']);
            break;

        case 'delete_tag':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem deletar tags');
            }
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            $id = $data['id'] ?? 0;
            
            $pdo->beginTransaction();
            // Remover relações primeiro
            $stmtRel = $pdo->prepare("DELETE FROM aviso_tag_relations WHERE tag_id = ?");
            $stmtRel->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM aviso_tags WHERE id = ? AND is_default = FALSE");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                throw new Exception('Tag não encontrada ou é uma tag padrão');
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tag deletada com sucesso']);
            break;

        case 'mark_read':
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            $avisoId = $data['aviso_id'] ?? 0;
            
            if (!$avisoId) {
                throw new Exception('ID do aviso é obrigatório');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO aviso_reads (user_id, aviso_id) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId, $avisoId]);
            
            echo json_encode(['success' => true, 'message' => 'Aviso marcado como lido']);
            break;

        case 'get_unread_count':
            // Contar avisos não lidos pelo usuário
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM avisos a
                LEFT JOIN aviso_reads ar ON a.id = ar.aviso_id AND ar.user_id = ?
                WHERE a.archived_at IS NULL 
                AND (a.expires_at IS NULL OR a.expires_at >= CURDATE())
                AND ar.aviso_id IS NULL
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'unread_count' => (int)$result['count']]);
            break;

        case 'pin_aviso':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem fixar avisos');
            }
            
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody, true) ?: $_POST;
            
            $avisoId = $data['aviso_id'] ?? 0;
            $isPinned = $data['is_pinned'] ?? false;
            
            $stmt = $pdo->prepare("UPDATE avisos SET is_pinned = ? WHERE id = ?");
            $stmt->execute([$isPinned ? 1 : 0, $avisoId]);
            
            echo json_encode(['success' => true, 'message' => 'Aviso ' . ($isPinned ? 'fixado' : 'desfixado')]);
            break;

        case 'get_aviso_stats':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem ver estatísticas');
            }
            
            $avisoId = $_GET['aviso_id'] ?? 0;
            
            // Total de leituras
            $stmt = $pdo->prepare("SELECT COUNT(*) as read_count FROM aviso_reads WHERE aviso_id = ?");
            $stmt->execute([$avisoId]);
            $readCount = $stmt->fetchColumn();
            
            // Total de usuários
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            
            // Usuários que leram
            $stmt = $pdo->prepare("
                SELECT u.name, ar.read_at 
                FROM aviso_reads ar
                JOIN users u ON ar.user_id = u.id
                WHERE ar.aviso_id = ?
                ORDER BY ar.read_at DESC
            ");
            $stmt->execute([$avisoId]);
            $readers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'read_count' => (int)$readCount,
                'total_users' => (int)$totalUsers,
                'read_percentage' => $totalUsers > 0 ? round(($readCount / $totalUsers) * 100, 1) : 0,
                'readers' => $readers
            ]);
            break;

        default:
            throw new Exception('Ação inválida');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
