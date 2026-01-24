<?php
// admin/api/roles.php
// API para gerenciar funções dos membros

header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['user_id'])) {
                // Buscar funções de um usuário específico
                getUserRoles($_GET['user_id']);
            } else {
                // Listar todas as funções disponíveis
                getAllRoles();
            }
            break;
            
        case 'POST':
            // Adicionar função a um usuário
            addUserRole();
            break;
            
        case 'DELETE':
            // Remover função de um usuário
            removeUserRole();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getAllRoles() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT id, name, icon, category, color 
        FROM roles 
        ORDER BY 
            FIELD(category, 'voz', 'cordas', 'teclas', 'percussao', 'sopro', 'outros'),
            name
    ");
    
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agrupar por categoria
    $grouped = [];
    foreach ($roles as $role) {
        $category = $role['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $role;
    }
    
    echo json_encode([
        'success' => true,
        'roles' => $roles,
        'grouped' => $grouped
    ]);
}

function getUserRoles($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT r.id, r.name, r.icon, r.category, r.color, ur.is_primary
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = ?
        ORDER BY ur.is_primary DESC, r.name
    ");
    
    $stmt->execute([$userId]);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'roles' => $roles
    ]);
}

function addUserRole() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['role_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id e role_id são obrigatórios']);
        return;
    }
    
    $userId = $data['user_id'];
    $roleId = $data['role_id'];
    $isPrimary = $data['is_primary'] ?? false;
    
    // Se for marcada como principal, desmarcar outras
    if ($isPrimary) {
        $stmt = $pdo->prepare("UPDATE user_roles SET is_primary = FALSE WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    // Inserir ou atualizar
    $stmt = $pdo->prepare("
        INSERT INTO user_roles (user_id, role_id, is_primary)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)
    ");
    
    $stmt->execute([$userId, $roleId, $isPrimary]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Função adicionada com sucesso'
    ]);
}

function removeUserRole() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id']) || !isset($data['role_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id e role_id são obrigatórios']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
    $stmt->execute([$data['user_id'], $data['role_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Função removida com sucesso'
    ]);
}
?>
