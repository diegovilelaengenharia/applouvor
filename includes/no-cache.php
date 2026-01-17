<?php
// includes/no-cache.php
// Configurações para prevenir cache do navegador

// Headers HTTP para desabilitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Versão dinâmica baseada no timestamp para forçar reload de assets
if (!defined('ASSET_VERSION')) {
    define('ASSET_VERSION', time());
}

/**
 * Função helper para adicionar versão aos assets
 * Uso: asset('assets/css/style.css')
 */
function asset($path)
{
    return $path . '?v=' . ASSET_VERSION;
}
