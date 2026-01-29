<?php
/**
 * API para gerenciamento de avisos e tags
 * Endpoints:
 * - GET /avisos_api.php?action=list_tags
 * - POST /avisos_api.php?action=create_tag
 * - POST /avisos_api.php?action=update_tag
 * - POST /avisos_api.php?action=delete_tag
 * - POST /avisos_api.php?action=mark_read
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

checkLogin();

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ===== TAGS =====
        
        case 'list_tags':
            $stmt = $pdo->query("SELECT * FROM aviso_tags ORDER BY is_default DESC, name ASC");
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'tags' => $tags]);
            break;

        case 'create_tag':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem criar tags');
            }
            
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '#64748b';
            $icon = $_POST['icon'] ?? 'tag';
            
            if (empty($name)) {
                throw new Exception('Nome da tag é obrigatório');
            }
            
            $stmt = $pdo->prepare("INSERT INTO aviso_tags (name, color, icon, is_default) VALUES (?, ?, ?, FALSE)");
            $stmt->execute([$name, $color, $icon]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tag criada com sucesso',
                'tag_id' => $pdo->lastInsertId()
            ]);
            break;

        case 'update_tag':
            if (!$isAdmin) {
                throw new Exception('Apenas administradores podem editar tags');
            }
            
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '';
            $icon = $_POST['icon'] ?? '';
            
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
            
            $id = $_POST['id'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM aviso_tags WHERE id = ? AND is_default = FALSE");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Tag não encontrada ou é uma tag padrão');
            }
            
            echo json_encode(['success' => true, 'message' => 'Tag deletada com sucesso']);
            break;

        // ===== AVISOS =====
        
        case 'mark_read':
            $avisoId = $_POST['aviso_id'] ?? 0;
            
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
            
            $avisoId = $_POST['aviso_id'] ?? 0;
            $isPinned = $_POST['is_pinned'] ?? false;
            
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
