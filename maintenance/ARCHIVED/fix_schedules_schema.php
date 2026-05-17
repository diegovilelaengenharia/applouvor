<?php
require_once '../includes/db.php';

try {
    echo "Verificando schema da tabela schedules...\n";

    // Adicionar created_at se não existir
    try {
        $pdo->exec("ALTER TABLE schedules ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "Coluna 'created_at' adicionada.\n";
        
        // Preencher datas vazias com NOW() (ou uma data aproximada se quiséssemos ser mais precisos, mas NOW serve para ordenar)
        // Na verdade, para não ficar tudo igual, vamos tentar usar event_date - 7 dias como "data de criação" aproximada para os antigos
        $pdo->exec("UPDATE schedules SET created_at = DATE_SUB(event_date, INTERVAL 7 DAY) WHERE created_at IS NULL OR created_at = '0000-00-00 00:00:00'");
        echo "Datas antigas preenchidas com aproximação.\n";

    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna 'created_at' já existe.\n";
        } else {
            echo "Erro ao adicionar created_at: " . $e->getMessage() . "\n";
        }
    }

    echo "Schema verificado com sucesso.\n";
} catch (Exception $e) {
    echo "Erro fatal: " . $e->getMessage() . "\n";
}
