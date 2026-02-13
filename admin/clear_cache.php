<?php
// admin/clear_cache.php
// Script para limpar cache de OPcache do servidor

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Limpeza de Cache do Servidor</h1>";

// 1. Limpar OPcache (se disponível)
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green;'>✅ OPcache limpo com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha ao limpar OPcache</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ OPcache não está habilitado</p>";
}

// 2. Limpar cache de realpath
if (function_exists('clearstatcache')) {
    clearstatcache(true);
    echo "<p style='color: green;'>✅ Cache de realpath limpo</p>";
}

// 3. Verificar versão dos arquivos críticos
echo "<h2>Verificação de Arquivos</h2>";

$files_to_check = [
    '../includes/dashboard_cards.php',
    '../includes/dashboard_render.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $mtime = filemtime($file);
        $date = date('Y-m-d H:i:s', $mtime);
        echo "<p>📄 <strong>" . basename($file) . "</strong>: Modificado em $date</p>";
        
        // Verificar se contém as chaves corretas
        $content = file_get_contents($file);
        if (strpos($content, 'espiritualidade') !== false) {
            echo "<p style='color: green; margin-left: 20px;'>✅ Contém 'espiritualidade'</p>";
        } else {
            echo "<p style='color: red; margin-left: 20px;'>❌ NÃO contém 'espiritualidade'</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Arquivo não encontrado: $file</p>";
    }
}

echo "<hr>";
echo "<p><a href='../admin/'>← Voltar ao Dashboard</a></p>";
