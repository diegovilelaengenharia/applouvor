<?php
// admin/fix_schema.php
require_once '../includes/db.php';

function addColumn($pdo, $table, $column, $definition) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "✅ Coluna '$column' adicionada com sucesso em '$table'.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), '1060') !== false) {
            echo "ℹ️ Coluna '$column' já existe em '$table'.<br>";
        } else {
            echo "❌ Erro ao adicionar '$column': " . $e->getMessage() . "<br>";
        }
    }
}

echo "<h3>Iniciando verificação do banco de dados...</h3>";

// 1. Adicionar observation
addColumn($pdo, 'user_unavailability', 'observation', 'TEXT DEFAULT NULL');

// 2. Adicionar audio_path
addColumn($pdo, 'user_unavailability', 'audio_path', 'VARCHAR(255) DEFAULT NULL');

// 3. Adicionar replacement_id (casos antigos)
addColumn($pdo, 'user_unavailability', 'replacement_id', 'INT DEFAULT NULL');

// 4. Garantir diretório de uploads
$uploadDir = '../uploads/audio';
if (!file_exists($uploadDir)) {
    if (mkdir($uploadDir, 0777, true)) {
        echo "✅ Diretório 'uploads/audio' criado.<br>";
    } else {
        echo "❌ Falha ao criar diretório 'uploads/audio'. Verifique permissões.<br>";
    }
} else {
    echo "ℹ️ Diretório 'uploads/audio' já existe.<br>";
}

echo "<h3>Concluído! Tente registrar a ausência novamente.</h3>";
?>
