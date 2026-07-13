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
            header('Location: /');
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
            http_response_code(403);
            die('Acesso negado. Apenas administradores podem ver esta página.');
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
}
