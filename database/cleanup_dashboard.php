<?php
/**
 * Script para limpar cards removidos do dashboard dos usuários
 * Execute este arquivo uma vez via navegador: http://localhost:8000/database/cleanup_dashboard.php
 */

require_once '../includes/db.php';

echo "<h2>Limpeza de Cards do Dashboard</h2>";
echo "<pre>";

try {
    // 1. Deletar cards removidos
    $removedCards = [
        'stats_escalas', 'stats_repertorio', 'relatorios', 'config_leitura', 'chat',
        'configuracoes', 'monitoramento', 'pastas', 'playlists', 'artistas',
        'classificacoes', 'lider', 'perfil', 'indisponibilidades', 'aniversariantes'
    ];

    $placeholders = implode(',', array_fill(0, count($removedCards), '?'));
    $stmt = $pdo->prepare("DELETE FROM user_dashboard_settings WHERE card_id IN ($placeholders)");
    $stmt->execute($removedCards);
    echo "✅ Removidos " . $stmt->rowCount() . " cards antigos\n\n";

    // 2. Atualizar cards renomeados
    $stmt = $pdo->prepare("UPDATE user_dashboard_settings SET card_id = 'ausencias' WHERE card_id = 'indisponibilidades'");
    $stmt->execute();
    echo "✅ Atualizados " . $stmt->rowCount() . " cards 'indisponibilidades' → 'ausencias'\n";

    $stmt = $pdo->prepare("UPDATE user_dashboard_settings SET card_id = 'aniversarios' WHERE card_id = 'aniversariantes'");
    $stmt->execute();
    echo "✅ Atualizados " . $stmt->rowCount() . " cards 'aniversariantes' → 'aniversarios'\n\n";

    // 3. Verificar cards restantes
    $stmt = $pdo->query("SELECT DISTINCT card_id FROM user_dashboard_settings ORDER BY card_id");
    $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Cards restantes no banco (" . count($remaining) . " únicos):\n";
    foreach($remaining as $card) {
        echo "  ✓ $card\n";
    }
    
    echo "\n✅ Limpeza concluída com sucesso!\n";
    echo "\n⚠️ IMPORTANTE: Após verificar, você pode deletar este arquivo por segurança.\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
