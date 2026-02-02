<?php
/**
 * Script para executar migrations das melhorias devocionais
 * Execute este arquivo uma vez via navegador: localhost:8000/run_migrations.php
 */

require_once 'includes/db.php';

echo "<h1>Executando Migrations - Melhorias Devocionais</h1>";
echo "<pre>";

try {
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/migrations/devotional_improvements.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remover comentários e linhas vazias
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Dividir em statements individuais
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $success++;
            echo "✓ Statement executado com sucesso\n";
        } catch (PDOException $e) {
            // Ignorar erros de "já existe" 
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "⚠ Já existe (ignorado)\n";
            } else {
                $errors++;
                echo "✗ Erro: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
            }
        }
    }
    
    echo "\n=====================================\n";
    echo "RESUMO:\n";
    echo "✓ Sucesso: $success\n";
    echo "✗ Erros: $errors\n";
    echo "=====================================\n";
    
    if ($errors === 0) {
        echo "\n🎉 Migrations executadas com SUCESSO!\n";
        echo "\nPróximos passos:\n";
        echo "1. Delete este arquivo (run_migrations.php)\n";
        echo "2. Recarregue a página de devocionais\n";
    } else {
        echo "\n⚠️ Houveram alguns erros. Verifique acima.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
