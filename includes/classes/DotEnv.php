<?php
/**
 * DotEnv - Carregador de Variáveis de Ambiente
 * Versão simplificada sem dependências externas
 */

namespace App;

class DotEnv
{
    protected $path;
    
    public function __construct($path)
    {
        $this->path = $path;
    }
    
    /**
     * Carrega o arquivo .env
     */
    public function load()
    {
        $envFile = $this->path . '/.env';
        
        if (!file_exists($envFile)) {
            // Se não existe .env, tenta usar .env.example como fallback
            $envFile = $this->path . '/.env.example';
            
            if (!file_exists($envFile)) {
                // Nenhum arquivo encontrado, continua sem carregar
                return;
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignora comentários
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse linha no formato KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                
                $name = trim($name);
                $value = trim($value);
                
                // Remove aspas se existirem
                $value = trim($value, '"\'');
                
                // Define variável de ambiente
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
        }
    }
    
    /**
     * Pega valor de variável de ambiente
     */
    public static function get($key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}
