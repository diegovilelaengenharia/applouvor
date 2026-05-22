<?php
// api/admin/dashboard_data_api.php
// Wrapper API seguro para fornecer os dados do dashboard em formato JSON.

require_once '../../src/helpers/auth.php';
require_once '../../src/config/db.php';

// Se o usuário não estiver logado, retornamos 401 para o React tratar o redirecionamento
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$data = require_once 'dashboard_data.php';

// Retornar o payload limpo
echo json_encode(['success' => true, 'data' => $data]);
