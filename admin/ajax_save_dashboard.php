<?php
// admin/ajax_save_dashboard.php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

if (isset($input['quick_access'])) {

    // Buscar prefs atuais
    $stmt = $pdo->prepare("SELECT dashboard_prefs FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentPrefsJson = $stmt->fetchColumn();
    $prefs = json_decode($currentPrefsJson ?? '[]', true);

    if (!is_array($prefs)) $prefs = [];

    // Atualizar
    $prefs['quick_access'] = $input['quick_access'];

    // Salvar
    $newPrefsJson = json_encode($prefs);
    $update = $pdo->prepare("UPDATE users SET dashboard_prefs = ? WHERE id = ?");

    if ($update->execute([$newPrefsJson, $userId])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro no banco de dados']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
}
