<?php
/**
 * Definições de todos os cards disponíveis para personalização do dashboard
 */

function getAllAvailableCards() {
    return [
        // GESTÃO → AZUL
        'escalas' => [
            'id' => 'escalas',
            'title' => 'Escalas',
            'icon' => 'calendar',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#2563eb', // Blue 600
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
            'color' => '#2563eb', // Blue 600
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
            'color' => '#2563eb', // Blue 600
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
            'color' => '#2563eb', // Blue 600
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
            'color' => '#2563eb', // Blue 600
            'bg' => '#eff6ff', // Blue 50
            'url' => 'indisponibilidade.php',
            'admin_only' => false
        ],
        
        // ESPÍRITO → VERDE
        'leitura' => [
            'id' => 'leitura',
            'title' => 'Leitura Bíblica',
            'icon' => 'book-open',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#059669', // Emerald 600
            'bg' => '#ecfdf5', // Emerald 50
            'url' => 'leitura.php',
            'admin_only' => false
        ],
        'devocional' => [
            'id' => 'devocional',
            'title' => 'Devocional',
            'icon' => 'sunrise',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#059669', // Emerald 600
            'bg' => '#ecfdf5', // Emerald 50
            'url' => 'devocionais.php',
            'admin_only' => false
        ],
        'oracao' => [
            'id' => 'oracao',
            'title' => 'Oração',
            'icon' => 'heart',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#059669', // Emerald 600
            'bg' => '#ecfdf5', // Emerald 50
            'url' => 'oracao.php',
            'admin_only' => false
        ],

        
        // COMUNICAÇÃO → ROXO
        'avisos' => [
            'id' => 'avisos',
            'title' => 'Avisos',
            'icon' => 'bell',
            'category' => 'comunica',
            'category_name' => 'Comunica',
            'color' => '#7c3aed', // Violet 600
            'bg' => '#f5f3ff', // Violet 50
            'url' => 'avisos.php',
            'admin_only' => false
        ],
        'aniversarios' => [
            'id' => 'aniversarios',
            'title' => 'Aniversários',
            'icon' => 'cake',
            'category' => 'comunica',
            'category_name' => 'Comunica',
            'color' => '#7c3aed', // Violet 600
            'bg' => '#f5f3ff', // Violet 50
            'url' => 'aniversarios.php',
            'admin_only' => false
        ],

        
        // ADMIN
        'lider' => [
            'id' => 'lider',
            'title' => 'Painel do Líder',
            'icon' => 'crown',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626', // Red 600
            'bg' => '#fee2e2', // Red 50
            'url' => 'lider.php',
            'admin_only' => true
        ],
        'perfil' => [
            'id' => 'perfil',
            'title' => 'Perfil',
            'icon' => 'user',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'url' => 'perfil.php',
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
