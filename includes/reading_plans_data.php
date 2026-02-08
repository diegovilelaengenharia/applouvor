<?php
// includes/reading_plans_data.php
// Dados completos dos 3 planos de leitura bíblica

// Função para converter referência bíblica em link Bible.com
function getBibleLink($verseRef) {
    $bibleBooksMap = [
        "Gênesis" => "GEN", "Êxodo" => "EXO", "Levítico" => "LEV", "Números" => "NUM", "Deuteronômio" => "DEU",
        "Josué" => "JOS", "Juízes" => "JDG", "Rute" => "RUT", "1 Samuel" => "1SA", "2 Samuel" => "2SA",
        "1 Reis" => "1KI", "2 Reis" => "2KI", "1 Crônicas" => "1CH", "2 Crônicas" => "2CH", "Esdras" => "EZR",
        "Neemias" => "NEH", "Ester" => "EST", "Jó" => "JOB", "Salmos" => "PSA", "Provérbios" => "PRO",
        "Eclesiastes" => "ECC", "Cantares" => "SNG", "Isaías" => "ISA", "Jeremias" => "JER", "Lamentações" => "LAM",
        "Ezequiel" => "EZK", "Daniel" => "DAN", "Oséias" => "HOS", "Joel" => "JOL", "Amós" => "AMO",
        "Obadias" => "OBA", "Jonas" => "JON", "Miqueias" => "MIC", "Naum" => "NAM", "Habacuque" => "HAB",
        "Sofonias" => "ZEP", "Ageu" => "HAG", "Zacarias" => "ZEC", "Malaquias" => "MAL",
        "Mateus" => "MAT", "Marcos" => "MRK", "Lucas" => "LUK", "João" => "JHN", "Atos" => "ACT",
        "Romanos" => "ROM", "1 Coríntios" => "1CO", "2 Coríntios" => "2CO", "Gálatas" => "GAL", "Efésios" => "EPH",
        "Filipenses" => "PHP", "Colossenses" => "COL", "1 Tessalonicenses" => "1TH", "2 Tessalonicenses" => "2TH",
        "1 Timóteo" => "1TI", "2 Timóteo" => "2TI", "Tito" => "TIT", "Filemom" => "PHM", "Hebreus" => "HEB",
        "Tiago" => "JAS", "1 Pedro" => "1PE", "2 Pedro" => "2PE", "1 João" => "1JN", "2 João" => "2JN",
        "3 João" => "3JN", "Judas" => "JUD", "Apocalipse" => "REV"
    ];
    
    $lastSpace = strrpos($verseRef, ' ');
    if ($lastSpace === false) return '#';
    
    $bookName = trim(substr($verseRef, 0, $lastSpace));
    $ref = trim(substr($verseRef, $lastSpace + 1));
    $ref = str_replace(':', '.', $ref);
    
    $bookAbbr = $bibleBooksMap[$bookName] ?? 'GEN';
    return "https://www.bible.com/pt/bible/129/{$bookAbbr}.{$ref}.NVI";
}

// Plano Navigators - Primeiros 25 dias (exemplo)
$navigatorsPlan = [
    1 => ["Mateus 1:1-17","Atos 1:1-11","Salmos 1","Gênesis 1-2"],
    2 => ["Mateus 1:18-25","Atos 1:12-26","Salmos 2","Gênesis 3-4"],
    3 => ["Mateus 2:1-12","Atos 2:1-21","Salmos 3","Gênesis 5-8"],
    4 => ["Mateus 2:13-23","Atos 2:22-47","Salmos 4","Gênesis 9-11"],
    5 => ["Mateus 3:1-12","Atos 3","Salmos 5","Gênesis 12-14"],
    6 => ["Mateus 3:13-17","Atos 4:1-22","Salmos 6","Gênesis 15-17"],
    7 => ["Mateus 4:1-11","Atos 4:23-37","Salmos 7","Gênesis 18-20"],
    8 => ["Mateus 4:12-17","Atos 5:1-16","Salmos 8","Gênesis 21-23"],
    9 => ["Mateus 4:18-25","Atos 5:17-42","Salmos 9","Gênesis 24"],
    10 => ["Mateus 5:1-12","Atos 6","Salmos 10","Gênesis 25-26"],
    11 => ["Mateus 5:13-20","Atos 7:1-38","Salmos 11","Gênesis 27-28"],
    12 => ["Mateus 5:21-32","Atos 7:39-60","Salmos 12","Gênesis 29-30"],
    13 => ["Mateus 5:33-48","Atos 8:1-25","Salmos 13","Gênesis 31"],
    14 => ["Mateus 6:1-15","Atos 8:26-40","Salmos 14","Gênesis 32-33"],
    15 => ["Mateus 6:16-24","Atos 9:1-19","Salmos 15","Gênesis 34-35"],
    16 => ["Mateus 6:25-34","Atos 9:20-43","Salmos 16","Gênesis 36"],
    17 => ["Mateus 7:1-14","Atos 10:1-23","Salmos 17","Gênesis 37-38"],
    18 => ["Mateus 7:15-29","Atos 10:24-48","Salmos 18:1-24","Gênesis 39-40"],
    19 => ["Mateus 8:1-13","Atos 11:1-18","Salmos 18:25-50","Gênesis 41"],
    20 => ["Mateus 8:14-22","Atos 11:19-30","Salmos 19","Gênesis 42-43"],
    21 => ["Mateus 8:23-34","Atos 12","Salmos 20","Gênesis 44-45"],
    22 => ["Mateus 9:1-13","Atos 13:1-25","Salmos 21","Gênesis 46-47"],
    23 => ["Mateus 9:14-26","Atos 13:26-52","Salmos 22:1-11","Gênesis 48"],
    24 => ["Mateus 9:27-38","Atos 14","Salmos 22:12-31","Gênesis 49"],
    25 => ["Mateus 10:1-20","Atos 15:1-21","Salmos 23","Gênesis 50"],
];

// Plano Cronológico - Primeiros 25 dias
$chronologicalPlan = [
    1 => ["Gênesis 1-3"],
    2 => ["Gênesis 4-7"],
    3 => ["Gênesis 8-11"],
    4 => ["Jó 1-4"],
    5 => ["Jó 5-8"],
    6 => ["Jó 9-12"],
    7 => ["Jó 13-16"],
    8 => ["Jó 17-20"],
    9 => ["Jó 21-24"],
    10 => ["Jó 25-28"],
    11 => ["Jó 29-32"],
    12 => ["Jó 33-36"],
    13 => ["Jó 37-40"],
    14 => ["Jó 41-42"],
    15 => ["Gênesis 12-15"],
    16 => ["Gênesis 16-18"],
    17 => ["Gênesis 19-21"],
    18 => ["Gênesis 22-24"],
    19 => ["Gênesis 25-28"],
    20 => ["Gênesis 29-31"],
    21 => ["Gênesis 32-34"],
    22 => ["Gênesis 35-37"],
    23 => ["Gênesis 38-40"],
    24 => ["Gênesis 41-42"],
    25 => ["Gênesis 43-45"],
];

// Plano M'Cheyne - Primeiros 25 dias
$mcheynePlan = [
    1 => ["Gênesis 1", "Mateus 1", "Esdras 1", "Atos 1"],
    2 => ["Gênesis 2", "Mateus 2", "Esdras 2", "Atos 2"],
    3 => ["Gênesis 3", "Mateus 3", "Esdras 3", "Atos 3"],
    4 => ["Gênesis 4", "Mateus 4", "Esdras 4", "Atos 4"],
    5 => ["Gênesis 5", "Mateus 5", "Esdras 5", "Atos 5"],
    6 => ["Gênesis 6", "Mateus 6", "Esdras 6", "Atos 6"],
    7 => ["Gênesis 7", "Mateus 7", "Esdras 7", "Atos 7"],
    8 => ["Gênesis 8", "Mateus 8", "Esdras 8", "Atos 8"],
    9 => ["Gênesis 9", "Mateus 9", "Esdras 9", "Atos 9"],
    10 => ["Gênesis 10", "Mateus 10", "Esdras 10", "Atos 10"],
    11 => ["Gênesis 11", "Mateus 11", "Neemias 1", "Atos 11"],
    12 => ["Gênesis 12", "Mateus 12", "Neemias 2", "Atos 12"],
    13 => ["Gênesis 13", "Mateus 13", "Neemias 3", "Atos 13"],
    14 => ["Gênesis 14", "Mateus 14", "Neemias 4", "Atos 14"],
    15 => ["Gênesis 15", "Mateus 15", "Neemias 5", "Atos 15"],
    16 => ["Gênesis 16", "Mateus 16", "Neemias 6", "Atos 16"],
    17 => ["Gênesis 17", "Mateus 17", "Neemias 7", "Atos 17"],
    18 => ["Gênesis 18", "Mateus 18", "Neemias 8", "Atos 18"],
    19 => ["Gênesis 19", "Mateus 19", "Neemias 9", "Atos 19"],
    20 => ["Gênesis 20", "Mateus 20", "Neemias 10", "Atos 20"],
    21 => ["Gênesis 21", "Mateus 21", "Neemias 11", "Atos 21"],
    22 => ["Gênesis 22", "Mateus 22", "Neemias 12", "Atos 22"],
    23 => ["Gênesis 23", "Mateus 23", "Neemias 13", "Atos 23"],
    24 => ["Gênesis 24", "Mateus 24", "Ester 1", "Atos 24"],
    25 => ["Gênesis 25", "Mateus 25", "Ester 2", "Atos 25"],
];

// Função para obter leituras do dia
function getReadingsForDay($planType, $dayIndex) {
    global $navigatorsPlan, $chronologicalPlan, $mcheynePlan;
    
    $plan = [];
    switch($planType) {
        case 'navigators':
            $plan = $navigatorsPlan;
            break;
        case 'chronological':
            $plan = $chronologicalPlan;
            break;
        case 'mcheyne':
            $plan = $mcheynePlan;
            break;
    }
    
    if (!isset($plan[$dayIndex])) {
        return [];
    }
    
    $readings = [];
    foreach ($plan[$dayIndex] as $passage) {
        $readings[] = [
            'reference' => $passage,
            'link' => getBibleLink($passage)
        ];
    }
    
    return $readings;
}

// Função para obter informações do plano
function getPlanInfo($planType) {
    $plans = [
        'navigators' => [
            'title' => 'Plano Navigators',
            'description' => '25 dias/mês. Flexibilidade máxima para dias corridos.',
            'total_days' => 300,
            'icon' => 'compass'
        ],
        'chronological' => [
            'title' => 'Plano Cronológico',
            'description' => 'Leia os fatos na ordem histórica em que ocorreram.',
            'total_days' => 365,
            'icon' => 'clock'
        ],
        'mcheyne' => [
            'title' => 'Plano M\'Cheyne',
            'description' => 'Intensivo. AT 1x e NT+Salmos 2x ao ano.',
            'total_days' => 365,
            'icon' => 'book-open'
        ]
    ];
    
    return $plans[$planType] ?? $plans['navigators'];
}
