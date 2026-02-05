<?php
/**
 * Definições de todos os cards disponíveis para personalização do dashboard
 */

function getAllAvailableCards() {
    return [
        // GESTÃO → AZUL (Vibrant Blue)
        'escalas' => [
            'id' => 'escalas',
            'title' => 'Escalas',
            'icon' => 'calendar',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'escalas.php',
            'admin_only' => false
        ],
        'repertorio' => [
            'id' => 'repertorio',
            'title' => 'Repertório',
            'icon' => 'music',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'repertorio.php',
            'admin_only' => false
        ],
        'membros' => [
            'id' => 'membros',
            'title' => 'Membros',
            'icon' => 'users',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'membros.php',
            'admin_only' => false
        ],

        'agenda' => [
            'id' => 'agenda',
            'title' => 'Agenda',
            'icon' => 'calendar-days',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'agenda.php',
            'admin_only' => false
        ],
        'ausencias' => [
            'id' => 'ausencias',
            'title' => 'Ausências',
            'icon' => 'calendar-x',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'indisponibilidade.php',
            'admin_only' => false
        ],
        
        'historico' => [
            'id' => 'historico',
            'title' => 'Histórico',
            'icon' => 'history',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#3b82f6', // Vibrant Blue
            'bg' => '#eff6ff', // Blue 50
            'url' => 'historico.php',
            'admin_only' => false
        ],
        
        // ESPIRITUAL → VERDE (Vibrant Green)
        'leitura' => [
            'id' => 'leitura',
            'title' => 'Leitura Bíblica',
            'icon' => 'book-open',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#24CE6B', // Vibrant Green
            'bg' => '#e8f9f0', // Green 50
            'url' => 'leitura.php',
            'admin_only' => false
        ],
        'devocional' => [
            'id' => 'devocional',
            'title' => 'Devocional',
            'icon' => 'sunrise',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#24CE6B', // Vibrant Green
            'bg' => '#e8f9f0', // Green 50
            'url' => 'devocionais.php',
            'admin_only' => false
        ],
        'oracao' => [
            'id' => 'oracao',
            'title' => 'Oração',
            'icon' => 'heart',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#24CE6B', // Vibrant Green
            'bg' => '#e8f9f0', // Green 50
            'url' => 'oracao.php',
            'admin_only' => false
        ],

        
        // COMUNICAÇÃO → AMARELO (Vibrant Yellow)
        'avisos' => [
            'id' => 'avisos',
            'title' => 'Avisos',
            'icon' => 'bell',
            'category' => 'comunica',
            'category_name' => 'Comunicação',
            'color' => '#FFC501', // Vibrant Yellow
            'bg' => '#fffbeb', // Yellow 50
            'url' => 'avisos.php',
            'admin_only' => false
        ],
        'aniversarios' => [
            'id' => 'aniversarios',
            'title' => 'Aniversários',
            'icon' => 'cake',
            'category' => 'comunica',
            'category_name' => 'Comunicação',
            'color' => '#FFC501', // Vibrant Yellow
            'bg' => '#fffbeb', // Yellow 50
            'url' => 'aniversarios.php',
            'admin_only' => false
        ],

    ];
}

function getCardsByCategory() {
    $cards = getAllAvailableCards();
    $byCategory = [];
    
    foreach ($cards as $card) {
        $category = $card['category'];
        if (!isset($byCategory[$category])) {
            $byCategory[$category] = [
                'name' => $card['category_name'],
                'cards' => []
            ];
        }
        $byCategory[$category]['cards'][] = $card;
    }
    
    return $byCategory;
}
