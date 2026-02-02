<?php
// Script de teste para verificar dados no banco
require_once 'includes/db.php';

echo "<h2>Teste de Dados no Banco</h2>";

// 1. Verificar Escalas
echo "<h3>1. Escalas (schedules)</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM schedules");
    $result = $stmt->fetch();
    echo "Total de escalas: " . $result['total'] . "<br>";
    
    $stmt = $pdo->query("SELECT * FROM schedules ORDER BY event_date DESC LIMIT 5");
    $escalas = $stmt->fetchAll();
    echo "<pre>";
    print_r($escalas);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 2. Verificar Próxima Escala
echo "<h3>2. Próxima Escala (futuras)</h3>";
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE event_date >= ? ORDER BY event_date ASC, event_time ASC LIMIT 1");
    $stmt->execute([$today]);
    $nextSchedule = $stmt->fetch();
    echo "<pre>";
    print_r($nextSchedule);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 3. Verificar Eventos (agenda)
echo "<h3>3. Eventos (events)</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM events");
    $result = $stmt->fetch();
    echo "Total de eventos: " . $result['total'] . "<br>";
    
    $stmt = $pdo->query("SELECT * FROM events ORDER BY start_datetime DESC LIMIT 5");
    $eventos = $stmt->fetchAll();
    echo "<pre>";
    print_r($eventos);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 4. Verificar Próximo Evento
echo "<h3>4. Próximo Evento (futuros)</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE start_datetime >= NOW() ORDER BY start_datetime ASC LIMIT 1");
    $stmt->execute();
    $nextEvent = $stmt->fetch();
    echo "<pre>";
    print_r($nextEvent);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 5. Verificar estrutura da tabela schedules
echo "<h3>5. Estrutura da tabela schedules</h3>";
try {
    $stmt = $pdo->query("DESCRIBE schedules");
    $structure = $stmt->fetchAll();
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 6. Verificar estrutura da tabela events
echo "<h3>6. Estrutura da tabela events</h3>";
try {
    $stmt = $pdo->query("DESCRIBE events");
    $structure = $stmt->fetchAll();
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}
