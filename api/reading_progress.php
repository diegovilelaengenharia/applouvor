<?php
/**
 * API Endpoint for Reading Progress
 * Manages user reading progress, settings, and notes
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../includes/auth.php';
require_once '../includes/db.php';

checkLogin();

$userId = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_settings':
            // Get user's reading plan settings
            $stmt = $pdo->prepare("
                SELECT plan_id, start_date, created_at 
                FROM user_reading_settings 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'settings' => $settings ?: null
            ]);
            break;

        case 'save_settings':
            $planId = $_POST['plan_id'] ?? '';
            $startDate = $_POST['start_date'] ?? date('Y-m-d');
            
            if (empty($planId)) {
                throw new Exception('Plan ID é obrigatório');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO user_reading_settings (user_id, plan_id, start_date)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    start_date = VALUES(start_date),
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId, $planId, $startDate]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Configurações salvas com sucesso'
            ]);
            break;

        case 'get_progress':
            $planId = $_GET['plan_id'] ?? '';
            
            if (empty($planId)) {
                throw new Exception('Plan ID é obrigatório');
            }
            
            $stmt = $pdo->prepare("
                SELECT day_number, passage_index, completed_at
                FROM reading_progress
                WHERE user_id = ? AND plan_id = ?
                ORDER BY day_number, passage_index
            ");
            $stmt->execute([$userId, $planId]);
            $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'progress' => $progress
            ]);
            break;

        case 'mark_complete':
            $planId = $_POST['plan_id'] ?? '';
            $dayNumber = $_POST['day_number'] ?? 0;
            $passageIndex = $_POST['passage_index'] ?? 0;
            
            if (empty($planId) || !$dayNumber) {
                throw new Exception('Dados incompletos');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO reading_progress (user_id, plan_id, day_number, passage_index)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$userId, $planId, $dayNumber, $passageIndex]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Passagem marcada como lida'
            ]);
            break;

        case 'mark_incomplete':
            $planId = $_POST['plan_id'] ?? '';
            $dayNumber = $_POST['day_number'] ?? 0;
            $passageIndex = $_POST['passage_index'] ?? 0;
            
            $stmt = $pdo->prepare("
                DELETE FROM reading_progress 
                WHERE user_id = ? AND plan_id = ? AND day_number = ? AND passage_index = ?
            ");
            $stmt->execute([$userId, $planId, $dayNumber, $passageIndex]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Marcação removida'
            ]);
            break;

        case 'save_note':
            $planId = $_POST['plan_id'] ?? '';
            $dayNumber = $_POST['day_number'] ?? 0;
            $passageRef = $_POST['passage_reference'] ?? '';
            $noteContent = $_POST['note_content'] ?? '';
            
            if (empty($planId) || !$dayNumber || empty($noteContent)) {
                throw new Exception('Dados incompletos');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO reading_notes (user_id, plan_id, day_number, passage_reference, note_content)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $planId, $dayNumber, $passageRef, $noteContent]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Anotação salva com sucesso',
                'note_id' => $pdo->lastInsertId()
            ]);
            break;

        case 'get_notes':
            $planId = $_GET['plan_id'] ?? '';
            $dayNumber = $_GET['day_number'] ?? null;
            
            $sql = "SELECT * FROM reading_notes WHERE user_id = ? AND plan_id = ?";
            $params = [$userId, $planId];
            
            if ($dayNumber !== null) {
                $sql .= " AND day_number = ?";
                $params[] = $dayNumber;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'notes' => $notes
            ]);
            break;

        case 'get_stats':
            $planId = $_GET['plan_id'] ?? '';
            
            if (empty($planId)) {
                throw new Exception('Plan ID é obrigatório');
            }
            
            // Total de passagens lidas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_read
                FROM reading_progress
                WHERE user_id = ? AND plan_id = ?
            ");
            $stmt->execute([$userId, $planId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Streak (dias consecutivos)
            $stmt = $pdo->prepare("
                SELECT DISTINCT DATE(completed_at) as read_date
                FROM reading_progress
                WHERE user_id = ? AND plan_id = ?
                ORDER BY read_date DESC
            ");
            $stmt->execute([$userId, $planId]);
            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $streak = 0;
            $today = new DateTime();
            foreach ($dates as $dateStr) {
                $date = new DateTime($dateStr);
                $diff = $today->diff($date)->days;
                if ($diff === $streak) {
                    $streak++;
                } else {
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_read' => (int)$stats['total_read'],
                    'current_streak' => $streak
                ]
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
