<?php
/**
 * Setup do Sistema de Notificações
 * Executa o schema do banco de dados
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup - Sistema de Notificações</title>
    
</head>
<body>
    <h1>🔔 Setup do Sistema de Notificações</h1>";

try {
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/create_notifications.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Erro ao ler arquivo SQL");
    }
    
    echo "<div class='info'>📄 Arquivo SQL carregado com sucesso</div>";
    
    // Remover comentários SQL (-- ...)
    $sql = preg_replace('/^--.*$/m', '', $sql);
    
    // Separar e executar cada statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    echo "<h2>Executando Statements SQL</h2>";
    
    // $pdo->beginTransaction(); // Removido
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Extrair nome da tabela ou ação
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Tabela <code>{$matches[1]}</code> criada</div>";
            } elseif (preg_match('/INSERT INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<div class='success'>✓ Dados inseridos em <code>{$matches[1]}</code></div>";
            } else {
                echo "<div class='success'>✓ Statement " . ($index + 1) . " executado</div>";
            }
        } catch (PDOException $e) {
            $errorCount++;
            // Ignorar erro se tabela já existe
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div class='info'>ℹ Statement " . ($index + 1) . " - Tabela já existe (ignorado)</div>";
            } else {
                echo "<div class='error'>✗ Erro no statement " . ($index + 1) . ": " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // $pdo->commit();
    
    echo "<h2>Resumo</h2>";
    echo "<div class='success'>";
    echo "<strong>Setup concluído!</strong><br>";
    echo "Statements executados com sucesso: $successCount<br>";
    if ($errorCount > 0) {
        echo "Erros (ignorados): $errorCount<br>";
    }
    echo "</div>";
    
    echo "<h2>Próximos Passos</h2>";
    echo "<div class='info'>";
    echo "1. O sistema de notificações está pronto para uso<br>";
    echo "2. Acesse <code>/admin/notificacoes.php</code> para ver suas notificações<br>";
    echo "3. O botão de notificações aparecerá no header<br>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div class='error'><strong>Erro Fatal:</strong> " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
