<?php
// setup_database.php
require_once 'includes/db.php';

echo '<body style="font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0fdf4;">';
echo '<div style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; max-width: 500px;">';

try {
    $sqlFile = __DIR__ . '/database/create_events.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado em: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Habilitar exceções PDO se não estiverem
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Executar queries
    $pdo->exec($sql);
    
    echo '<div style="font-size: 48px; margin-bottom: 20px;">✅</div>';
    echo '<h1 style="color: #166534; margin: 0 0 16px;">Sucesso Absoluto!</h1>';
    echo '<p style="color: #374151; font-size: 18px; line-height: 1.5;">O banco de dados foi atualizado com todas as tabelas da agenda.</p>';
    echo '<div style="margin-top: 30px;">';
    echo '<a href="admin/agenda.php" style="background: #166534; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-size: 16px;">Acessar Agenda</a>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div style="font-size: 48px; margin-bottom: 20px;">❌</div>';
    echo '<h1 style="color: #991b1b; margin: 0 0 16px;">Ocorreu um Erro</h1>';
    echo '<p style="color: #374151; margin-bottom: 20px;">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p style="font-size: 14px; color: #6b7280;">Verifique se as credenciais do banco estão corretas em includes/db.php</p>';
}

echo '</div></body>';
