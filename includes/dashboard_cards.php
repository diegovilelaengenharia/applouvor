<?php
/**
 * Definições de todos os cards disponíveis para personalização do dashboard
 */

function getAllAvailableCards() {
    return [
        // ===== GESTÃO (AZUL) =====
        'escalas' => [
            'id' => 'escalas',
            'title' => 'Escalas',
            'icon' => 'calendar',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'escalas.php',
            'admin_only' => false
        ],
        'repertorio' => [
            'id' => 'repertorio',
            'title' => 'Repertório',
            'icon' => 'music',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'repertorio.php',
            'admin_only' => false
        ],
        'historico' => [
            'id' => 'historico',
            'title' => 'Histórico',
            'icon' => 'history',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'historico.php',
            'admin_only' => false
        ],
        'membros' => [
            'id' => 'membros',
            'title' => 'Membros',
            'icon' => 'users',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'membros.php',
            'admin_only' => false
        ],
        'ausencias' => [
            'id' => 'ausencias',
            'title' => 'Ausências',
            'icon' => 'calendar-x',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'indisponibilidade.php',
            'admin_only' => false
        ],
        'agenda' => [
            'id' => 'agenda',
            'title' => 'Agenda',
            'icon' => 'calendar-days',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563EB', // Azul primário
            'bg' => '#DBEAFE', 
            'url' => 'agenda.php',
            'admin_only' => false
        ],

        // ===== ESPIRITUALIDADE (VERDE) =====
        'leitura' => [
            'id' => 'leitura',
            'title' => 'Leitura Bíblica',
            'icon' => 'book-open',
            'category' => 'espiritualidade',
            'category_name' => 'Espiritualidade',
            'color' => '#10B981', // Verde
            'bg' => '#D1FAE5', 
            'url' => 'leitura.php',
            'admin_only' => false
        ],
        'devocional' => [
            'id' => 'devocional',
            'title' => 'Devocional',
            'icon' => 'sunrise',
            'category' => 'espiritualidade',
            'category_name' => 'Espiritualidade',
            'color' => '#10B981', // Verde
            'bg' => '#D1FAE5', 
            'url' => 'devocionais.php',
            'admin_only' => false
        ],
        'oracao' => [
            'id' => 'oracao',
            'title' => 'Oração',
            'icon' => 'heart',
            'category' => 'espiritualidade',
            'category_name' => 'Espiritualidade',
            'color' => '#10B981', // Verde
            'bg' => '#D1FAE5', 
            'url' => 'devocionais.php?tab=prayer',
            'admin_only' => false
        ],

        // ===== COMUNICAÇÃO (AMARELO) =====
        'avisos' => [
            'id' => 'avisos',
            'title' => 'Avisos',
            'icon' => 'bell',
            'category' => 'comunicacao',
            'category_name' => 'Comunicação',
            'color' => '#F59E0B', // Amarelo/Laranja
            'bg' => '#FEF3C7', 
            'url' => 'avisos.php',
            'admin_only' => false
        ],
        'aniversarios' => [
            'id' => 'aniversarios',
            'title' => 'Aniversários',
            'icon' => 'cake',
            'category' => 'comunicacao',
            'category_name' => 'Comunicação',
            'color' => '#F59E0B', // Amarelo/Laranja
            'bg' => '#FEF3C7', 
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
