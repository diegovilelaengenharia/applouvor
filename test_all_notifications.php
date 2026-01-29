<?php
// test_all_notifications.php
require_once 'includes/db.php';
require_once 'includes/notification_system.php';

// Config
$userId = 22; // Diego
$ns = new NotificationSystem($pdo);

// List of all types to test
$testCases = [
    [
        'type' => NotificationSystem::TYPE_NEW_ESCALA,
        'title' => 'Nova Escala',
        'message' => 'Nova escala de louvor disponível para 01/02.'
    ],
    [
        'type' => NotificationSystem::TYPE_ESCALA_UPDATE,
        'title' => 'Escala Alterada',
        'message' => 'Houve uma alteração na escala de Domingo.'
    ],
    [
        'type' => NotificationSystem::TYPE_MEMBER_ABSENCE,
        'title' => 'Ausência Informada',
        'message' => 'João informou ausência para o próximo ensaio.'
    ],
    [
        'type' => NotificationSystem::TYPE_NEW_MUSIC,
        'title' => 'Nova Música',
        'message' => 'A música "Bondade de Deus" foi adicionada.'
    ],
    [
        'type' => NotificationSystem::TYPE_NEW_AVISO,
        'title' => 'Novo Aviso',
        'message' => 'Ensaio geral quinta-feira às 19h.'
    ],
    [
        'type' => NotificationSystem::TYPE_AVISO_URGENT,
        'title' => 'Aviso Urgente',
        'message' => 'Mudança de horário de última hora!'
    ],
    [
        'type' => NotificationSystem::TYPE_BIRTHDAY,
        'title' => 'Aniversariante',
        'message' => 'Hoje é aniversário da Maria! Dê os parabéns.'
    ],
    [
        'type' => NotificationSystem::TYPE_READING_REMINDER,
        'title' => 'Leitura Bíblica',
        'message' => 'Lembrete: Leitura de hoje - Salmos 23.'
    ],
    [
        'type' => NotificationSystem::TYPE_WEEKLY_REPORT,
        'title' => 'Relatório Semanal',
        'message' => 'Seu relatório de atividades da semana está pronto.'
    ]
];

echo "<h2>Testing All Notification Types for User ID $userId</h2>";
echo "<p>Sending " . count($testCases) . " notifications...</p>";
echo "<ul>";

foreach ($testCases as $case) {
    try {
        // Create generic notification for this type
        $result = $ns->create(
            $userId, 
            $case['type'], 
            "TESTE: " . $case['title'], 
            $case['message'], 
            ['test' => true], 
            '#'
        );
        
        $status = $result ? "<span style='color:green'>OK (Sent)</span>" : "<span style='color:red'>FAILED (DB Error)</span>";
        echo "<li><strong>{$case['type']}</strong>: $status</li>";
        
        // Small delay to prevent rate limiting or overwhelming the browser
        usleep(500000); // 0.5s
        
    } catch (Exception $e) {
        echo "<li><strong>{$case['type']}</strong>: <span style='color:red'>Exception: " . $e->getMessage() . "</span></li>";
    }
}

echo "</ul>";
echo "<p>Check your device! You should receive exactly " . count($testCases) . " notifications.</p>";
?>
