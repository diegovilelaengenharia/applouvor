<?php
/**
 * Definições de todos os cards disponíveis para personalização do dashboard
 */

function getAllAvailableCards() {
    return [
        // GESTÃO (Verde #047857)
        'escalas' => [
            'id' => 'escalas',
            'title' => 'Escalas',
            'icon' => 'calendar',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'escalas.php',
            'admin_only' => false
        ],
        'repertorio' => [
            'id' => 'repertorio',
            'title' => 'Repertório',
            'icon' => 'music',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'repertorio.php',
            'admin_only' => false
        ],
        'membros' => [
            'id' => 'membros',
            'title' => 'Membros',
            'icon' => 'users',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'membros.php',
            'admin_only' => false
        ],
        'stats_escalas' => [
            'id' => 'stats_escalas',
            'title' => 'Estatísticas Escalas',
            'icon' => 'bar-chart-2',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'escalas_stats.php',
            'admin_only' => false
        ],
        'stats_repertorio' => [
            'id' => 'stats_repertorio',
            'title' => 'Estatísticas Repertório',
            'icon' => 'trending-up',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'repertorio_stats.php',
            'admin_only' => false
        ],
        'relatorios' => [
            'id' => 'relatorios',
            'title' => 'Relatórios',
            'icon' => 'file-text',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'relatorios_gerais.php',
            'admin_only' => false
        ],
        'agenda' => [
            'id' => 'agenda',
            'title' => 'Agenda',
            'icon' => 'calendar-days',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'agenda.php',
            'admin_only' => false
        ],
        'indisponibilidades' => [
            'id' => 'indisponibilidades',
            'title' => 'Indisponibilidades',
            'icon' => 'calendar-x',
            'category' => 'gestao',
            'category_name' => 'Gestão',
            'color' => '#047857',
            'bg' => '#ecfdf5',
            'url' => 'indisponibilidade.php',
            'admin_only' => false
        ],
        
        // ESPÍRITO (Índigo #4338ca)
        'leitura' => [
            'id' => 'leitura',
            'title' => 'Leitura Bíblica',
            'icon' => 'book-open',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#4338ca',
            'bg' => '#eef2ff',
            'url' => 'leitura.php',
            'admin_only' => false
        ],
        'devocional' => [
            'id' => 'devocional',
            'title' => 'Devocional',
            'icon' => 'sunrise',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#4338ca',
            'bg' => '#eef2ff',
            'url' => 'devocionais.php',
            'admin_only' => false
        ],
        'oracao' => [
            'id' => 'oracao',
            'title' => 'Oração',
            'icon' => 'heart',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#4338ca',
            'bg' => '#eef2ff',
            'url' => 'oracao.php',
            'admin_only' => false
        ],
        'config_leitura' => [
            'id' => 'config_leitura',
            'title' => 'Config. Leitura',
            'icon' => 'settings',
            'category' => 'espirito',
            'category_name' => 'Espírito',
            'color' => '#4338ca',
            'bg' => '#eef2ff',
            'url' => 'leitura.php#config',
            'admin_only' => false
        ],
        
        // COMUNICA (Laranja #ea580c)
        'avisos' => [
            'id' => 'avisos',
            'title' => 'Avisos',
            'icon' => 'bell',
            'category' => 'comunica',
            'category_name' => 'Comunica',
            'color' => '#ea580c',
            'bg' => '#fff7ed',
            'url' => 'avisos.php',
            'admin_only' => false
        ],
        'aniversariantes' => [
            'id' => 'aniversariantes',
            'title' => 'Aniversariantes',
            'icon' => 'cake',
            'category' => 'comunica',
            'category_name' => 'Comunica',
            'color' => '#ea580c',
            'bg' => '#fff7ed',
            'url' => 'aniversarios.php',
            'admin_only' => false
        ],
        'chat' => [
            'id' => 'chat',
            'title' => 'Chat',
            'icon' => 'message-circle',
            'category' => 'comunica',
            'category_name' => 'Comunica',
            'color' => '#ea580c',
            'bg' => '#fff7ed',
            'url' => 'chat.php',
            'admin_only' => false
        ],
        
        // ADMIN (Vermelho #dc2626)
        'lider' => [
            'id' => 'lider',
            'title' => 'Painel do Líder',
            'icon' => 'crown',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
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
        'configuracoes' => [
            'id' => 'configuracoes',
            'title' => 'Configurações',
            'icon' => 'sliders',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'url' => 'configuracoes.php',
            'admin_only' => true
        ],
        'monitoramento' => [
            'id' => 'monitoramento',
            'title' => 'Monitoramento',
            'icon' => 'activity',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'url' => 'monitoramento_usuarios.php',
            'admin_only' => true
        ],
        'pastas' => [
            'id' => 'pastas',
            'title' => 'Pastas',
            'icon' => 'folder',
            'category' => 'admin',
            'category_name' => 'Admin',
            'color' => '#dc2626',
            'bg' => '#fee2e2',
            'url' => 'repertorio.php#pastas',
            'admin_only' => false
        ],
        
        // EXTRAS (Cinza #64748b)
        'playlists' => [
            'id' => 'playlists',
            'title' => 'Playlists',
            'icon' => 'list-music',
            'category' => 'extras',
            'category_name' => 'Extras',
            'color' => '#64748b',
            'bg' => '#f1f5f9',
            'url' => 'criar_playlist.php',
            'admin_only' => false
        ],
        'artistas' => [
            'id' => 'artistas',
            'title' => 'Artistas',
            'icon' => 'mic-2',
            'category' => 'extras',
            'category_name' => 'Extras',
            'color' => '#64748b',
            'bg' => '#f1f5f9',
            'url' => 'repertorio.php#artistas',
            'admin_only' => false
        ],
        'classificacoes' => [
            'id' => 'classificacoes',
            'title' => 'Classificações',
            'icon' => 'tags',
            'category' => 'extras',
            'category_name' => 'Extras',
            'color' => '#64748b',
            'bg' => '#f1f5f9',
            'url' => 'classificacoes.php',
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
