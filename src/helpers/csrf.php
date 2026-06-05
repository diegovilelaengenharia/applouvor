<?php
// src/helpers/csrf.php

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!$expected || !hash_equals($expected, $token)) {
        http_response_code(403);
        die('Acesso negado. Token CSRF inválido ou expirado. Recarregue a página.');
    }
    // Rotacionar token após validação bem-sucedida para maior segurança
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
