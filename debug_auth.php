<?php
// debug_auth.php - Diagnóstico de Login (JSON)
header('Content-Type: application/json');
require_once 'includes/db.php';
require_once 'includes/auth.php';

$name = 'Diego'; 
$pass = '9577';

$response = [
    'user_input' => $name,
    'pass_input' => $pass
];

try {
    // 1. Buscar Usuário
    $user = App\DB::table('users')->where('name', '=', $name)->first();
    
    $response['user_found'] = (bool)$user;
    
    if ($user) {
        $response['user_id'] = $user['id'];
        $response['user_role'] = $user['role'];
        $dbPass = $user['password'];
        $response['password_type'] = (strlen($dbPass) > 50) ? 'hash' : 'text';
        
        $match = false;
        if (password_verify($pass, $dbPass)) {
            $match = true;
            $response['match_method'] = 'password_verify';
        } elseif ($pass === $dbPass) {
            $match = true;
            $response['match_method'] = 'exact_match';
        }
        
        $response['password_match'] = $match;
        if (!$match) {
             $response['hash_preview'] = substr($dbPass, 0, 10) . '...';
        }
    } else {
        // Search similar
        $userLike = $pdo->query("SELECT id, name FROM users WHERE name LIKE '%Diego%'")->fetchAll(PDO::FETCH_ASSOC);
        $response['similar_users'] = $userLike;
    }

    // Ambiente
    $response['https'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    $cookieParams = session_get_cookie_params();
    $response['cookie_secure_flag'] = $cookieParams['secure'];
    $response['session_id_length'] = strlen(session_id());

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
