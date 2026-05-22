<?php
// api/admin/membros_api.php
// API JSON para listar a equipe de voluntários, funções, estatísticas e link de contato.

require_once '../../src/helpers/auth.php';
require_once '../../src/config/db.php';

header('Content-Type: application/json');

// Se o usuário não estiver logado, retornamos 401
$loggedUserId = $_SESSION['user_id'] ?? 0;
if (!$loggedUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$sort = $_GET['sort'] ?? 'name';
$orderBy = match($sort) {
    'taxa'    => 'taxa DESC, u.name ASC',
    'escalas' => 'total_escalas DESC, u.name ASC',
    default   => 'u.name ASC',
};

try {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.phone, u.avatar, u.role,
               GROUP_CONCAT(
                   CONCAT(r.id, ':', r.name, ':', r.icon, ':', r.color, ':', IFNULL(ur.is_primary, 0))
                   ORDER BY ur.is_primary DESC, r.name
                   SEPARATOR '||'
               ) as roles_data,
               (
                   SELECT COUNT(*)
                   FROM schedule_users su
                   JOIN schedules sch ON sch.id = su.schedule_id
                   WHERE su.user_id = u.id AND sch.event_date < CURDATE()
               ) as total_escalas,
               (
                   SELECT ROUND(
                       SUM(CASE WHEN su.status IN ('confirmed','pending') THEN 1 ELSE 0 END) * 100.0
                       / NULLIF(COUNT(su.schedule_id), 0)
                   )
                   FROM schedule_users su
                   JOIN schedules sch ON sch.id = su.schedule_id
                   WHERE su.user_id = u.id AND sch.event_date < CURDATE()
               ) as taxa
         FROM users u
         LEFT JOIN user_roles ur ON u.id = ur.user_id
         LEFT JOIN roles r ON ur.role_id = r.id
         GROUP BY u.id
         ORDER BY $orderBy
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as &$user) {
        $user['roles'] = [];
        if (!empty($user['roles_data'])) {
            $rolesArray = explode('||', $user['roles_data']);
            foreach ($rolesArray as $roleStr) {
                // Verificar se a string de role é válida e contém todas as partes necessárias
                $parts = explode(':', $roleStr);
                if (count($parts) >= 5) {
                    list($id, $name, $icon, $color, $isPrimary) = $parts;
                    $user['roles'][] = [
                        'id' => (int)$id,
                        'name' => $name,
                        'icon' => $icon,
                        'color' => $color,
                        'is_primary' => (bool)$isPrimary
                    ];
                }
            }
        }
        unset($user['roles_data']);

        // Ajustar caminhos de avatar/fotos
        if (!empty($user['avatar'])) {
            if (strpos($user['avatar'], 'http') === false && strpos($user['avatar'], 'assets') === false && strpos($user['avatar'], 'uploads') === false) {
                $user['avatar'] = '../uploads/' . $user['avatar'];
            } elseif (strpos($user['avatar'], 'assets/') === 0) {
                $user['avatar'] = '../' . $user['avatar'];
            }
        } else {
            $user['avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=2e7eed&color=fff';
        }
    }
    unset($user);

    echo json_encode([
        'success' => true,
        'data' => $users
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    exit;
}
