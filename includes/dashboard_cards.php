<?php
/**
 * Definições de todos os cards disponíveis para personalização do dashboard
 */

function getAllAvailableCards() {
    return [
        // GESTÃO
        'escalas' => [
            'id' => 'escalas',
            'title' => 'Escalas',
            'icon' => 'calendar',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563eb', // Blue 600
            'bg' => '#eff6ff', 
            'url' => 'escalas.php',
            'admin_only' => false
        ],
        'repertorio' => [
            'id' => 'repertorio',
            'title' => 'Repertório',
            'icon' => 'music',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#7c3aed', // Purple 600
            'bg' => '#f5f3ff', 
            'url' => 'repertorio.php',
            'admin_only' => false
        ],
        'membros' => [
            'id' => 'membros',
            'title' => 'Membros',
            'icon' => 'users',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#4f46e5', // Indigo 600
            'bg' => '#eef2ff', 
            'url' => 'membros.php',
            'admin_only' => false
        ],

        'agenda' => [
            'id' => 'agenda',
            'title' => 'Agenda',
            'icon' => 'calendar-days',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#059669', // Emerald 600
            'bg' => '#ecfdf5', 
            'url' => 'agenda.php',
            'admin_only' => false
        ],
        'ausencias' => [
            'id' => 'ausencias',
            'title' => 'Ausências',
            'icon' => 'calendar-x',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#dc2626', // Red 600
            'bg' => '#fef2f2', 
            'url' => 'indisponibilidade.php',
            'admin_only' => false
        ],
        
        'historico' => [
            'id' => 'historico',
            'title' => 'Histórico',
            'icon' => 'history',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#475569', // Slate 600
            'bg' => '#f1f5f9', 
            'url' => 'historico.php',
            'admin_only' => false
        ],
        
        // ESPIRITUAL
        'leitura' => [
            'id' => 'leitura',
            'title' => 'Leitura Bíblica',
            'icon' => 'book-open',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#0d9488', // Teal 600
            'bg' => '#f0fdfa', 
            'url' => 'leitura.php',
            'admin_only' => false
        ],
        'devocional' => [
            'id' => 'devocional',
            'title' => 'Devocional',
            'icon' => 'sunrise',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#ea580c', // Orange 600
            'bg' => '#ffedd5', 
            'url' => 'devocionais.php',
            'admin_only' => false
        ],
        'oracao' => [
            'id' => 'oracao',
            'title' => 'Oração',
            'icon' => 'heart',
            'category' => 'espirito',
            'category_name' => 'Espiritual',
            'color' => '#db2777', // Pink 600
            'bg' => '#fdf2f8', 
            'url' => 'devocionais.php?tab=prayer',
            'admin_only' => false
        ],

        
        // COMUNICAÇÃO
        'avisos' => [
            'id' => 'avisos',
            'title' => 'Avisos',
            'icon' => 'bell',
            'category' => 'comunica',
            'category_name' => 'Comunicação',
            'color' => '#d97706', // Amber 600
            'bg' => '#fffbeb', 
            'url' => 'avisos.php',
            'admin_only' => false
        ],
        'aniversarios' => [
            'id' => 'aniversarios',
            'title' => 'Aniversários',
            'icon' => 'cake',
            'category' => 'comunica',
            'category_name' => 'Comunicação',
            'color' => '#d97706', // Amber 600 - mesma cor de Avisos
            'bg' => '#fffbeb', 
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
