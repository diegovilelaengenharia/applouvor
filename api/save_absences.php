<?php
// api/save_absences.php — Persiste status de presença/falta pós-culto (admin only)
header('Content-Type: application/json');
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';

$userId   = $_SESSION['user_id']   ?? 0;
$userRole = $_SESSION['user_role'] ?? '';

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas administradores podem registrar faltas']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$scheduleId   = (int)($data['schedule_id'] ?? 0);
$participants = $data['participants'] ?? [];

if (!$scheduleId || empty($participants) || !is_array($participants)) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos: schedule_id e participants[] obrigatórios']);
    exit;
}

// Validar que a escala existe e é do passado
$stmtSched = $pdo->prepare("SELECT id, event_date FROM schedules WHERE id = ?");
$stmtSched->execute([$scheduleId]);
$schedule = $stmtSched->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    echo json_encode(['success' => false, 'message' => 'Escala não encontrada']);
    exit;
}

if ($schedule['event_date'] >= date('Y-m-d')) {
    echo json_encode(['success' => false, 'message' => 'Só é possível registrar faltas em escalas passadas']);
    exit;
}

// Buscar user_ids válidos para esta escala (guard de integridade)
$stmtValidUsers = $pdo->prepare("SELECT user_id FROM schedule_users WHERE schedule_id = ?");
$stmtValidUsers->execute([$scheduleId]);
$validUserIds = array_column($stmtValidUsers->fetchAll(PDO::FETCH_ASSOC), 'user_id');

$validStatuses = ['confirmed', 'declined', 'pending', 'absent', 'absent_justified'];

$stmtUpdate = $pdo->prepare("
    UPDATE schedule_users
    SET status = ?, absence_note = ?
    WHERE schedule_id = ? AND user_id = ?
");

$updatedCount = 0;
$errors = [];

foreach ($participants as $p) {
    $pUserId = (int)($p['user_id'] ?? 0);
    $pStatus = $p['status'] ?? 'confirmed';
    $pNote   = isset($p['note']) && $p['note'] !== '' ? trim(substr($p['note'], 0, 500)) : null;

    // Validar que o user_id pertence a esta escala
    if (!in_array($pUserId, $validUserIds)) {
        $errors[] = "user_id $pUserId não pertence a esta escala";
        continue;
    }

    // Validar status contra whitelist
    if (!in_array($pStatus, $validStatuses)) {
        $pStatus = 'confirmed'; // fallback seguro
    }

    // Limpar nota se não for ausência
    if (!in_array($pStatus, ['absent', 'absent_justified'])) {
        $pNote = null;
    }

    try {
        $stmtUpdate->execute([$pStatus, $pNote, $scheduleId, $pUserId]);
        $updatedCount++;
    } catch (Exception $e) {
        $errors[] = "Erro ao atualizar user_id $pUserId";
        error_log("save_absences error for user $pUserId: " . $e->getMessage());
    }
}

if ($updatedCount === 0 && !empty($errors)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum registro atualizado', 'errors' => $errors]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => "$updatedCount participante(s) atualizados",
    'updated' => $updatedCount,
]);
