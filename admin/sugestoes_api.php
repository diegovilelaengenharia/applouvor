<?php
// admin/sugestoes_api.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['user_role'] ?? 'user') === 'admin';

try {
    switch ($action) {
        case 'create':
            // Todos podem criar
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['title']) || empty($data['artist'])) {
                throw new Exception("Título e Artista são obrigatórios.");
            }

            $stmt = $pdo->prepare("
                INSERT INTO song_suggestions (user_id, title, artist, tone, youtube_link, spotify_link, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $data['title'],
                $data['artist'],
                $data['tone'] ?? null,
                $data['youtube_link'] ?? null,
                $data['spotify_link'] ?? null,
                $data['reason'] ?? null
            ]);

            echo json_encode(['success' => true, 'message' => 'Sugestão enviada com sucesso!']);
            break;

        case 'list':
            // Admin vê tudo, user vê apenas as suas
            $filter = $_GET['filter'] ?? 'all'; // pending, approved, rejected, all

            $sql = "SELECT s.*, u.name as user_name, u.photo as user_photo 
                    FROM song_suggestions s
                    JOIN users u ON s.user_id = u.id
                    WHERE 1=1";
            $params = [];

            if (!$isAdmin) {
                // Se não é admin, vê apenas as suas
                $sql .= " AND s.user_id = ?";
                $params[] = $userId;
            }

            if ($filter !== 'all') {
                $sql .= " AND s.status = ?";
                $params[] = $filter;
            }

            $sql .= " ORDER BY s.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'suggestions' => $suggestions]);
            break;

        case 'approve':
            if (!$isAdmin) throw new Exception("Permissão negada.");
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            if (!$id) throw new Exception("ID inválido.");

            // Buscar dados da sugestão
            $stmt = $pdo->prepare("SELECT * FROM song_suggestions WHERE id = ?");
            $stmt->execute([$id]);
            $suggestion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$suggestion) throw new Exception("Sugestão não encontrada.");

            $pdo->beginTransaction();

            // 1. Atualizar status para approved
            $stmt = $pdo->prepare("
                UPDATE song_suggestions 
                SET status = 'approved', reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $id]);

            // 2. Inserir na tabela de músicas (songs)
            // Nota: youtube_link e spotify_link não existem na tabela songs
            $stmtInsert = $pdo->prepare("
                INSERT INTO songs (title, artist, tone, bpm, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            // Nota: BPM não vem da sugestão, default null ou 0
            $stmtInsert->execute([
                $suggestion['title'],
                $suggestion['artist'],
                $suggestion['tone'],
                0 // BPM default
            ]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Música aprovada e adicionada ao repertório!']);
            break;

        case 'reject':
            if (!$isAdmin) throw new Exception("Permissão negada.");
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            
            if (!$id) throw new Exception("ID inválido.");

            $stmt = $pdo->prepare("
                UPDATE song_suggestions 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId, $id]);

            echo json_encode(['success' => true, 'message' => 'Sugestão rejeitada.']);
            break;

        case 'count_pending':
            if (!$isAdmin) {
                echo json_encode(['success' => true, 'count' => 0]);
                exit;
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM song_suggestions WHERE status = 'pending'");
            $count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'count' => $count]);
            break;

        default:
            throw new Exception("Ação inválida.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
