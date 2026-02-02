<?php
/**
 * API para gerenciamento de sugestões de músicas
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $userId = $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
    
    switch($action) {
        case 'create':
            // Qualquer usuário pode criar sugestão
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $title = trim($data['title'] ?? '');
            $artist = trim($data['artist'] ?? '');
            $tone = trim($data['tone'] ?? '');
            $youtubeLink = trim($data['youtube_link'] ?? '');
            $spotifyLink = trim($data['spotify_link'] ?? '');
            $reason = trim($data['reason'] ?? '');
            
            if (empty($title) || empty($artist)) {
                throw new Exception('Título e artista são obrigatórios');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO song_suggestions 
                (user_id, title, artist, tone, youtube_link, spotify_link, reason, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $title, $artist, $tone, $youtubeLink, $spotifyLink, $reason]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sugestão enviada com sucesso!',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'list':
            // Listar sugestões
            $status = $_GET['status'] ?? 'pending';
            $validStatuses = ['pending', 'approved', 'rejected', 'all'];
            
            if (!in_array($status, $validStatuses)) {
                $status = 'pending';
            }
            
            $query = "
                SELECT s.*, 
                       u.name as suggested_by_name,
                       r.name as reviewed_by_name
                FROM song_suggestions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN users r ON s.reviewed_by = r.id
            ";
            
            if ($status !== 'all') {
                $query .= " WHERE s.status = ?";
                $stmt = $pdo->prepare($query . " ORDER BY s.created_at DESC");
                $stmt->execute([$status]);
            } else {
                $stmt = $pdo->prepare($query . " ORDER BY s.created_at DESC");
                $stmt->execute();
            }
            
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'suggestions' => $suggestions
            ]);
            break;
            
        case 'count_pending':
            // Contar sugestões pendentes (para badge)
            $stmt = $pdo->query("SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'");
            $count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'approve':
            // Apenas admin pode aprovar
            if (!$isAdmin) {
                throw new Exception('Acesso negado');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $suggestionId = $data['id'] ?? null;
            
            if (!$suggestionId) {
                throw new Exception('ID da sugestão não fornecido');
            }
            
            // Buscar sugestão
            $stmt = $pdo->prepare("SELECT * FROM song_suggestions WHERE id = ?");
            $stmt->execute([$suggestionId]);
            $suggestion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$suggestion) {
                throw new Exception('Sugestão não encontrada');
            }
            
            // Iniciar transação
            $pdo->beginTransaction();
            
            try {
                // Adicionar música ao repertório
                $stmtSong = $pdo->prepare("
                    INSERT INTO songs (title, artist, tone, youtube_link, spotify_link, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmtSong->execute([
                    $suggestion['title'],
                    $suggestion['artist'],
                    $suggestion['tone'],
                    $suggestion['youtube_link'],
                    $suggestion['spotify_link']
                ]);
                
                // Atualizar status da sugestão
                $stmtUpdate = $pdo->prepare("
                    UPDATE song_suggestions 
                    SET status = 'approved', reviewed_by = ?, reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$userId, $suggestionId]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Sugestão aprovada e música adicionada ao repertório!'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'reject':
            // Apenas admin pode rejeitar
            if (!$isAdmin) {
                throw new Exception('Acesso negado');
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método não permitido');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $suggestionId = $data['id'] ?? null;
            
            if (!$suggestionId) {
                throw new Exception('ID da sugestão não fornecido');
            }
            
            $stmt = $pdo->prepare("
                UPDATE song_suggestions 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $suggestionId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Sugestão rejeitada'
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
