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
        case 'get_user_settings':
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
                'settings' => $settings
            ]);
            break;

        case 'save_user_settings':
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
                SELECT day_number, passage_index, is_completed, completed_at
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

        case 'mark_passage':
            $planId = $_POST['plan_id'] ?? '';
            $dayNumber = $_POST['day_number'] ?? 0;
            $passageIndex = $_POST['passage_index'] ?? 0;
            $isCompleted = $_POST['is_completed'] ?? false;
            
            if (empty($planId) || !$dayNumber) {
                throw new Exception('Dados incompletos');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO reading_progress 
                    (user_id, plan_id, day_number, passage_index, is_completed, completed_at)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    is_completed = VALUES(is_completed),
                    completed_at = VALUES(completed_at),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $completedAt = $isCompleted ? date('Y-m-d H:i:s') : null;
            $stmt->execute([$userId, $planId, $dayNumber, $passageIndex, $isCompleted, $completedAt]);
            
            echo json_encode([
                'success' => true,
                'message' => $isCompleted ? 'Passagem marcada como lida' : 'Marcação removida'
            ]);
            break;

        case 'save_note':
            $planId = $_POST['plan_id'] ?? '';
            $dayNumber = $_POST['day_number'] ?? 0;
            $passageRef = $_POST['passage_reference'] ?? '';
            $noteText = $_POST['note_text'] ?? '';
            
            if (empty($planId) || !$dayNumber || empty($noteText)) {
                throw new Exception('Dados incompletos');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO reading_notes 
                    (user_id, plan_id, day_number, passage_reference, note_text)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $planId, $dayNumber, $passageRef, $noteText]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Anotação salva com sucesso',
                'note_id' => $pdo->lastInsertId()
            ]);
            break;

        case 'get_notes':
            $planId = $_GET['plan_id'] ?? '';
            $dayNumber = $_GET['day_number'] ?? null;
            
            if (empty($planId)) {
                throw new Exception('Plan ID é obrigatório');
            }
            
            $sql = "
                SELECT id, day_number, passage_reference, note_text, created_at
                FROM reading_notes
                WHERE user_id = ? AND plan_id = ?
            ";
            
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
            
            // Total de passagens completadas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as completed_count
                FROM reading_progress
                WHERE user_id = ? AND plan_id = ? AND is_completed = 1
            ");
            $stmt->execute([$userId, $planId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Streak (dias consecutivos)
            $stmt = $pdo->prepare("
                SELECT DISTINCT DATE(completed_at) as completion_date
                FROM reading_progress
                WHERE user_id = ? AND plan_id = ? AND is_completed = 1
                ORDER BY completion_date DESC
            ");
            $stmt->execute([$userId, $planId]);
            $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $currentStreak = 0;
            $today = new DateTime();
            
            foreach ($dates as $index => $dateStr) {
                $date = new DateTime($dateStr);
                $expectedDate = clone $today;
                $expectedDate->modify("-{$index} days");
                
                if ($date->format('Y-m-d') === $expectedDate->format('Y-m-d')) {
                    $currentStreak++;
                } else {
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'completed_count' => (int)$stats['completed_count'],
                    'current_streak' => $currentStreak,
                    'total_notes' => 0 // TODO: implement if needed
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
