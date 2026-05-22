<?php
// api/admin/repertorio_api.php
// API JSON para busca e listagem de músicas do repertório, tags e filtros.

require_once '../../src/helpers/auth.php';
require_once '../../src/config/db.php';
require_once '../../src/classes/MusicRepository.php';

header('Content-Type: application/json');

// Se o usuário não estiver logado, retornamos 401
$loggedUserId = $_SESSION['user_id'] ?? 0;
if (!$loggedUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$musicRepo = new \App\Repositories\MusicRepository($pdo);

try {
    $action = $_GET['action'] ?? 'songs';

    if ($action === 'filters') {
        // Retorna as tags, artistas e tons para montar os filtros no frontend
        $tags = $musicRepo->getTagsWithCount();
        $artists = $musicRepo->getArtistsWithCount();
        $tones = $musicRepo->getTonesWithCount();

        echo json_encode([
            'success' => true,
            'data' => [
                'tags' => $tags,
                'artists' => $artists,
                'tones' => $tones
            ]
        ]);
        exit;
    } else {
        // LISTAGEM E BUSCA DE MÚSICAS
        $search = $_GET['search'] ?? '';
        $tagId = isset($_GET['tag_id']) && $_GET['tag_id'] !== '' ? (int)$_GET['tag_id'] : null;
        $tone = $_GET['tone'] ?? null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

        $songs = $musicRepo->getSongs($search, $tagId, $tone, $limit);

        // Para cada música, buscar suas tags ativas
        foreach ($songs as &$song) {
            $song['tags'] = $musicRepo->getSongTags((int)$song['id']);
        }
        unset($song);

        echo json_encode([
            'success' => true,
            'data' => $songs
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    exit;
}
