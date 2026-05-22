<?php
// api/admin/devocionais_api.php
// API JSON para listar e interagir com devocionais, séries e pedidos de oração.

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

try {
    $action = $_GET['action'] ?? 'all';

    if ($action === 'prayers') {
        // LISTAGEM DE PEDIDOS DE ORAÇÃO ATIVOS
        $prayers = [];
        try {
            $pSql = "SELECT p.*, u.name as author_name, u.avatar as author_avatar,
                    (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'pray') as pray_count,
                    (SELECT COUNT(*) FROM prayer_interactions WHERE prayer_id = p.id AND type = 'comment') as comment_count,
                    IF(pi_user.id IS NOT NULL, 1, 0) as is_interceded
                    FROM prayer_requests p 
                    LEFT JOIN users u ON p.user_id = u.id
                    LEFT JOIN prayer_interactions pi_user ON p.id = pi_user.prayer_id 
                                                           AND pi_user.user_id = ?
                                                           AND pi_user.type = 'pray'
                    WHERE p.is_answered = 0
                    ORDER BY is_interceded ASC, p.is_urgent DESC, p.created_at DESC";
            $pStmt = $pdo->prepare($pSql);
            $pStmt->execute([$loggedUserId]);
            $prayers = $pStmt->fetchAll(PDO::FETCH_ASSOC);

            // Ajustar fotos de avatares
            foreach ($prayers as &$p) {
                if ($p['is_anonymous']) {
                    $p['author_name'] = 'Anônimo';
                    $p['author_avatar'] = 'https://ui-avatars.com/api/?name=A&background=e11d48&color=fff';
                } else {
                    if (!empty($p['author_avatar'])) {
                        if (strpos($p['author_avatar'], 'http') === false && strpos($p['author_avatar'], 'assets') === false && strpos($p['author_avatar'], 'uploads') === false) {
                            $p['author_avatar'] = '../uploads/' . $p['author_avatar'];
                        } elseif (strpos($p['author_avatar'], 'assets/') === 0) {
                            $p['author_avatar'] = '../' . $p['author_avatar'];
                        }
                    } else {
                        $p['author_avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($p['author_name'] ?? 'M') . '&background=f43f5e&color=fff';
                    }
                }
            }
            unset($p);
        } catch (Exception $e) {
            // Tabela pode não existir
        }

        echo json_encode([
            'success' => true,
            'data' => $prayers
        ]);
        exit;
    } elseif ($action === 'devotional_detail') {
        // DETALHE DE UM DEVOCIONAL (Comentários e Conteúdo)
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT d.*, u.name as author_name, u.avatar as author_avatar
                               FROM devotionals d
                               LEFT JOIN users u ON d.user_id = u.id
                               WHERE d.id = ?");
        $stmt->execute([$id]);
        $devotional = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$devotional) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Devocional não encontrado']);
            exit;
        }

        // Buscar tags
        $tStmt = $pdo->prepare("SELECT t.* FROM tags t 
                                INNER JOIN devotional_tags dt ON t.id = dt.tag_id 
                                WHERE dt.devotional_id = ?");
        $tStmt->execute([$id]);
        $tags = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar comentários
        $cStmt = $pdo->prepare("SELECT c.*, u.name as author_name, u.avatar as author_avatar 
                                FROM devotional_comments c 
                                LEFT JOIN users u ON c.user_id = u.id 
                                WHERE c.devotional_id = ? 
                                ORDER BY c.created_at ASC");
        $cStmt->execute([$id]);
        $comments = $cStmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajustar caminhos de avatar
        foreach ($comments as &$c) {
            if (!empty($c['author_avatar'])) {
                if (strpos($c['author_avatar'], 'http') === false && strpos($c['author_avatar'], 'assets') === false && strpos($c['author_avatar'], 'uploads') === false) {
                    $c['author_avatar'] = '../uploads/' . $c['author_avatar'];
                } elseif (strpos($c['author_avatar'], 'assets/') === 0) {
                    $c['author_avatar'] = '../' . $c['author_avatar'];
                }
            } else {
                $c['author_avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($c['author_name'] ?? 'M') . '&background=2e7eed&color=fff';
            }
        }
        unset($c);

        if (!empty($devotional['author_avatar'])) {
            if (strpos($devotional['author_avatar'], 'http') === false && strpos($devotional['author_avatar'], 'assets') === false && strpos($devotional['author_avatar'], 'uploads') === false) {
                $devotional['author_avatar'] = '../uploads/' . $devotional['author_avatar'];
            } elseif (strpos($devotional['author_avatar'], 'assets/') === 0) {
                $devotional['author_avatar'] = '../' . $devotional['author_avatar'];
            }
        } else {
            $devotional['author_avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($devotional['author_name'] ?? 'M') . '&background=2e7eed&color=fff';
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'devotional' => $devotional,
                'tags' => $tags,
                'comments' => $comments
            ]
        ]);
        exit;
    } else {
        // LISTAGEM DE DEVOCIONAIS
        $filterTag = $_GET['tag'] ?? '';
        $filterType = $_GET['type'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $filterAuthor = $_GET['author'] ?? '';
        $filterRead = $_GET['read_status'] ?? 'all';

        $sql = "SELECT d.id, d.title, d.content, d.media_type, d.media_url, d.created_at, d.user_id,
                u.name as author_name, u.avatar as author_avatar,
                s.title as series_title, s.cover_color as series_color,
                (SELECT COUNT(*) FROM devotional_comments WHERE devotional_id = d.id) as comment_count,
                IF(dr.id IS NOT NULL, 1, 0) as is_read
                FROM devotionals d 
                LEFT JOIN users u ON d.user_id = u.id 
                LEFT JOIN devotional_series s ON d.series_id = s.id
                LEFT JOIN devotional_reads dr ON d.id = dr.devotional_id AND dr.user_id = ?
                WHERE 1=1";
        $params = [$loggedUserId];

        if (!empty($filterTag)) {
            $sql .= " AND d.id IN (SELECT devotional_id FROM devotional_tags WHERE tag_id = ?)";
            $params[] = $filterTag;
        }

        if ($filterType !== 'all') {
            $sql .= " AND d.media_type = ?";
            $params[] = $filterType;
        }

        if (!empty($search)) {
            $sql .= " AND (d.title LIKE ? OR d.content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filterAuthor)) {
            $sql .= " AND d.user_id = ?";
            $params[] = $filterAuthor;
        }

        if ($filterRead === 'read') {
            $sql .= " AND dr.id IS NOT NULL";
        } elseif ($filterRead === 'unread') {
            $sql .= " AND dr.id IS NULL";
        }

        $sql .= " ORDER BY is_read ASC, d.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $devotionals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Processar os avatares e buscar as tags de cada um
        foreach ($devotionals as &$dev) {
            if (!empty($dev['author_avatar'])) {
                if (strpos($dev['author_avatar'], 'http') === false && strpos($dev['author_avatar'], 'assets') === false && strpos($dev['author_avatar'], 'uploads') === false) {
                    $dev['author_avatar'] = '../uploads/' . $dev['author_avatar'];
                } elseif (strpos($dev['author_avatar'], 'assets/') === 0) {
                    $dev['author_avatar'] = '../' . $dev['author_avatar'];
                }
            } else {
                $dev['author_avatar'] = 'https://ui-avatars.com/api/?name=' . urlencode($dev['author_name'] ?? 'M') . '&background=2e7eed&color=fff';
            }

            // Tags
            $tStmt = $pdo->prepare("SELECT t.* FROM tags t 
                                    INNER JOIN devotional_tags dt ON t.id = dt.tag_id 
                                    WHERE dt.devotional_id = ?");
            $tStmt->execute([$dev['id']]);
            $dev['tags'] = $tStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($dev);

        // Buscar dados auxiliares (tags e autores para filtros)
        $tagsStmt = $pdo->query("SELECT * FROM tags ORDER BY name");
        $allTags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);

        $authorsStmt = $pdo->query("
            SELECT DISTINCT u.id, u.name 
            FROM users u
            INNER JOIN devotionals d ON u.id = d.user_id
            ORDER BY u.name ASC
        ");
        $allAuthors = $authorsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'devotionals' => $devotionals,
                'tags' => $allTags,
                'authors' => $allAuthors
            ]
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
    exit;
}
