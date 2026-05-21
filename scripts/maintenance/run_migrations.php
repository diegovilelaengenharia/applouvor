<?php
/**
 * Script para executar migrations das melhorias devocionais
 * Execute este arquivo uma vez via navegador: localhost:8000/run_migrations.php
 */

require_once __DIR__ . '/../../src/config/db.php';

echo "<h1>Executando Migrations - Melhorias Devocionais</h1>";
echo "<pre>";

try {
    // Migrations a executar
    $migrations = [
        'devotional_improvements.sql',
        'devotional_reads.sql',
        'prayer_improvements.sql'  // Nova: garante unicidade de intercessões
    ];
    
    $totalSuccess = 0;
    $totalErrors = 0;
    
    foreach ($migrations as $migrationFile) {
        $sqlFile = __DIR__ . '/../../database/migrations/' . $migrationFile;
        
        echo "\n📄 Executando: $migrationFile\n";
        echo "=====================================\n";
        
        if (!file_exists($sqlFile)) {
            echo "⚠️ Arquivo não encontrado (ignorado): $sqlFile\n";
            continue;
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
                echo "✓ Statement executado\n";
            } catch (PDOException $e) {
                // Ignorar erros de "já existe" 
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠ Já existe (ignorado)\n";
                    $success++;
                } else {
                    $errors++;
                    echo "✗ Erro: " . $e->getMessage() . "\n";
                }
            }
        }
        
        echo "Sucesso: $success | Erros: $errors\n\n";
        $totalSuccess += $success;
        $totalErrors += $errors;
    }
    
    echo "\n=====================================\n";
    echo "RESUMO GERAL:\n";
    echo "✓ Sucesso: $totalSuccess\n";
    echo "✗ Erros: $totalErrors\n";
    echo "=====================================\n";
    
    if ($totalErrors === 0) {
        echo "\n🎉 Todas as migrations executadas com SUCESSO!\n";
        echo "\nPróximos passos:\n";
        echo "1. Recarregue a página de devocionais\n";
        echo "2. Opcional: Delete este arquivo (run_migrations.php)\n";
    } else {
        echo "\n⚠️ Houveram alguns erros. Verifique acima.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
