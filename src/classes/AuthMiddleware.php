<?php
/**
 * AuthMiddleware - Middleware de Autenticação
 * Centraliza verificação de login e permissões
 */

namespace App;

class AuthMiddleware
{
    /**
     * Exige que o usuário esteja logado
     */
    public static function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../index.php');
            exit;
        }
    }
    
    /**
     * Exige que o usuário seja admin
     */
    public static function requireAdmin()
    {
        self::requireLogin();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ../app/index.php');
            exit;
        }
    }
    
    /**
     * Exige role específica
     */
    public static function requireRole($role)
    {
        self::requireLogin();
        
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $role) {
            http_response_code(403);
            die('Acesso negado.');
        }
    }
    
    /**
     * Verifica se usuário está logado (sem redirecionar)
     */
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Verifica se usuário é admin (sem redirecionar)
     */
    public static function isAdmin()
    {
        return self::check() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Pega ID do usuário logado
     */
    public static function userId()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Pega role do usuário logado
     */
    public static function userRole()
    {
        return $_SESSION['user_role'] ?? null;
    }
    
    /**
     * Gera token CSRF
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida token CSRF
     */
    public static function validateCsrfToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Campo hidden com token CSRF para formulários
     */
    public static function csrfField()
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
