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
            'color' => '#3b82f6', // Blue 500 (mais suave)
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
            'color' => '#8b5cf6', // Purple 500 (mais suave)
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
            'color' => '#6366f1', // Indigo 500 (mais suave)
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
            'color' => '#10b981', // Emerald 500 (mais suave)
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
            'color' => '#ef4444', // Red 500 (mais suave)
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
            'color' => '#64748b', // Slate 500 (mais suave)
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
            'color' => '#14b8a6', // Teal 500 (mais suave)
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
            'color' => '#f97316', // Orange 500 (mais suave)
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
            'color' => '#ec4899', // Pink 500 (mais suave)
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
            'color' => '#f59e0b', // Amber 500 (mais suave)
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
            'color' => '#f59e0b', // Amber 500 (mais suave) - mesma cor de Avisos
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
