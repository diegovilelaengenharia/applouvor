<?php
// admin/debug_dashboard.php
// Script de debug para verificar o que está sendo renderizado

header('Content-Type: text/html; charset=utf-8');
require_once '../includes/auth.php';
require_once '../includes/db.php';

echo "<h1>🔍 Debug do Dashboard</h1>";
echo "<style>
body { font-family: monospace; padding: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// 1. Verificar se dashboard_cards.php existe e pode ser carregado
echo "<h2>1. Verificando dashboard_cards.php</h2>";
if (file_exists('../includes/dashboard_cards.php')) {
    echo "<p class='success'>✅ Arquivo existe</p>";
    require_once '../includes/dashboard_cards.php';
    
    if (function_exists('getAllAvailableCards')) {
        echo "<p class='success'>✅ Função getAllAvailableCards() existe</p>";
        
        $cards = getAllAvailableCards();
        echo "<p>Total de cards definidos: <strong>" . count($cards) . "</strong></p>";
        
        // Mostrar estrutura de cada card
        echo "<h3>Estrutura dos Cards:</h3>";
        foreach ($cards as $id => $card) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> {$id}<br>";
            echo "<strong>Título:</strong> {$card['title']}<br>";
            echo "<strong>Categoria (chave):</strong> <span style='background: yellow;'>{$card['category']}</span><br>";
            echo "<strong>Categoria (nome):</strong> {$card['category_name']}<br>";
            echo "<strong>Cor:</strong> <span style='background: {$card['color']}; padding: 2px 10px; color: white;'>{$card['color']}</span><br>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ Função getAllAvailableCards() NÃO existe</p>";
    }
} else {
    echo "<p class='error'>❌ Arquivo NÃO existe</p>";
}

// 2. Verificar configurações do usuário no banco
echo "<h2>2. Configurações do Usuário no Banco</h2>";
$userId = $_SESSION['user_id'] ?? 1;
echo "<p>User ID: <strong>{$userId}</strong></p>";

try {
    $stmt = $pdo->prepare("SELECT card_id, is_visible, display_order FROM user_dashboard_settings WHERE user_id = ? ORDER BY display_order");
    $stmt->execute([$userId]);
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($settings)) {
        echo "<p class='warning'>⚠️ Nenhuma configuração no banco (vai usar fallback)</p>";
    } else {
        echo "<p class='success'>✅ {$count} cards configurados</p>";
        echo "<pre>";
        print_r($settings);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erro: {$e->getMessage()}</p>";
}

// 3. Verificar mapeamento de categorias no index.php
echo "<h2>3. Categorias Esperadas no index.php</h2>";
$categoryOrder = ['Gestão', 'Espiritualidade', 'Comunicação'];
echo "<pre>";
print_r($categoryOrder);
echo "</pre>";

// 4. Simular agrupamento
echo "<h2>4. Simulação de Agrupamento</h2>";
if (isset($cards) && !empty($settings)) {
    $groupedCards = [];
    foreach ($categoryOrder as $cat) {
        $groupedCards[$cat] = [];
    }
    
    foreach ($settings as $setting) {
        $cardId = $setting['card_id'];
        if (isset($cards[$cardId])) {
            $cardDef = $cards[$cardId];
            $catName = $cardDef['category_name'];
            
            if (!isset($groupedCards[$catName])) {
                echo "<p class='error'>❌ Categoria desconhecida: '{$catName}' (card: {$cardId})</p>";
                $catName = 'Comunicação'; // Fallback
            }
            
            $groupedCards[$catName][] = $cardId;
        }
    }
    
    echo "<h3>Cards Agrupados:</h3>";
    foreach ($groupedCards as $cat => $cardIds) {
        echo "<p><strong>{$cat}:</strong> " . implode(', ', $cardIds) . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='index.php'>← Voltar ao Dashboard</a></p>";
