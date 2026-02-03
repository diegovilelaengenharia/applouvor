<?php
// admin/relatorios_gerais.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkAdmin();

// --- 1. FILTERS & CONFIGURATION ---
$period = $_GET['period'] ?? 'year'; 
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$semester = $_GET['semester'] ?? (date('m') <= 6 ? 1 : 2);
$isPrintMode = isset($_GET['print']) && $_GET['print'] === 'true';

// Date Range Logic
if ($period === 'month') {
    $startDate = "$year-$month-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $titlePeriod = "Mês: " . date('m/Y', strtotime($startDate));
} elseif ($period === 'semester') {
    if ($semester == 1) {
        $startDate = "$year-01-01";
        $endDate = "$year-06-30";
        $titlePeriod = "1º Sem. $year";
    } else {
        $startDate = "$year-07-01";
        $endDate = "$year-12-31";
        $titlePeriod = "2º Sem. $year";
    }
} else { // year
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
    $titlePeriod = "Ano $year";
}

$dateCondition = "s.event_date BETWEEN :start AND :end";
$params = ['start' => $startDate, 'end' => $endDate];


// --- 2. DATA QUERIES ---

// A. KPIs & GENERAL
// -----------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules s WHERE $dateCondition");
$stmt->execute($params);
$kpi_scales = $stmt->fetchColumn();

// Confirmed Rate
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN su.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed FROM schedule_users su JOIN schedules s ON su.schedule_id = s.id WHERE $dateCondition");
$stmt->execute($params);
$partData = $stmt->fetch(PDO::FETCH_ASSOC);
$rate_confirmed = $partData['total'] > 0 ? round(($partData['confirmed'] / $partData['total']) * 100) : 0;

// Songs Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM schedule_songs ss JOIN schedules s ON ss.schedule_id = s.id WHERE $dateCondition");
$stmt->execute($params);
$kpi_songs = $stmt->fetchColumn();

// Chapters Count
$stmt = $pdo->prepare("SELECT SUM(JSON_LENGTH(verses_read)) FROM reading_progress WHERE completed_at BETWEEN :start AND :end");
$stmt->execute($params);
$kpi_chapters = $stmt->fetchColumn() ?: 0;


// B. SCALES & TEAM (Expanded)
// ---------------------------
// 1. Top Pairs (Duplas)
$stmt = $pdo->prepare("
    SELECT LEAST(u1.name, u2.name) as p1, GREATEST(u1.name, u2.name) as p2, COUNT(*) as qtd
    FROM schedule_users su1
    JOIN schedule_users su2 ON su1.schedule_id = su2.schedule_id AND su1.user_id < su2.user_id
    JOIN schedules s ON su1.schedule_id = s.id
    JOIN users u1 ON su1.user_id = u1.id
    JOIN users u2 ON su2.user_id = u2.id
    WHERE $dateCondition 
    AND su1.status != 'declined' AND su2.status != 'declined'
    AND (u1.instrument LIKE '%Vocal%' OR u1.instrument LIKE '%Voz%' OR u1.instrument LIKE '%Ministro%' OR u1.instrument LIKE '%Cantor%' OR u1.instrument LIKE '%Backing%')
    AND (u2.instrument LIKE '%Vocal%' OR u2.instrument LIKE '%Voz%' OR u2.instrument LIKE '%Ministro%' OR u2.instrument LIKE '%Cantor%' OR u2.instrument LIKE '%Backing%')
    GROUP BY p1, p2
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topPairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Absences Ranking
$stmt = $pdo->prepare("
    SELECT u.name, COUNT(*) as qtd
    FROM schedule_users su
    JOIN schedules s ON su.schedule_id = s.id
    JOIN users u ON su.user_id = u.id
    WHERE $dateCondition AND su.status = 'declined'
    GROUP BY su.user_id
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topDeclines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Taxa de Confirmação por Membro
$stmt = $pdo->prepare("
    SELECT u.name, u.avatar_color,
           COUNT(*) as total_invites,
           SUM(CASE WHEN su.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
           ROUND((SUM(CASE WHEN su.status = 'confirmed' THEN 1 ELSE 0 END) / COUNT(*)) * 100) as rate
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    JOIN schedules s ON su.schedule_id = s.id
    WHERE $dateCondition
    GROUP BY su.user_id
    ORDER BY rate DESC, confirmed DESC
");
$stmt->execute($params);
$memberConfirmRate = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Membros Mais/Menos Escalados
$stmt = $pdo->prepare("
    SELECT u.name, u.instrument, u.avatar_color, COUNT(*) as qtd
    FROM schedule_users su
    JOIN users u ON su.user_id = u.id
    JOIN schedules s ON su.schedule_id = s.id
    WHERE $dateCondition
    GROUP BY su.user_id
    ORDER BY qtd DESC
");
$stmt->execute($params);
$memberScaleCount = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Tendência Temporal (Escalas por Mês)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(s.event_date, '%Y-%m') as month, COUNT(*) as qtd
    FROM schedules s
    WHERE $dateCondition
    GROUP BY month
    ORDER BY month ASC
");
$stmt->execute($params);
$scaleTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Análise de Substituições
$stmt = $pdo->prepare("
    SELECT u.name as substituido, r.name as substituto, COUNT(*) as vezes
    FROM user_unavailability ua
    JOIN users u ON ua.user_id = u.id
    LEFT JOIN users r ON ua.replacement_id = r.id
    WHERE ua.start_date BETWEEN :start AND :end AND ua.replacement_id IS NOT NULL
    GROUP BY ua.user_id, ua.replacement_id
    ORDER BY vezes DESC
    LIMIT 10
");
$stmt->execute($params);
$substitutions = $stmt->fetchAll(PDO::FETCH_ASSOC);


// C. REPERTOIRE (Expanded)
// ------------------------
// 1. Top Songs
$stmt = $pdo->prepare("
    SELECT sg.title, sg.artist, COUNT(*) as qtd
    FROM schedule_songs ss
    JOIN schedules s ON ss.schedule_id = s.id
    JOIN songs sg ON ss.song_id = sg.id
    WHERE $dateCondition
    GROUP BY ss.song_id
    ORDER BY qtd DESC
    LIMIT 10
");
$stmt->execute($params);
$topSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Top Artists
$stmt = $pdo->prepare("
    SELECT sg.artist, COUNT(*) as qtd
    FROM schedule_songs ss
    JOIN schedules s ON ss.schedule_id = s.id
    JOIN songs sg ON ss.song_id = sg.id
    WHERE $dateCondition
    GROUP BY sg.artist
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topArtists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Top Tones (Keys)
$stmt = $pdo->prepare("
    SELECT sg.tone, COUNT(*) as qtd
    FROM schedule_songs ss
    JOIN schedules s ON ss.schedule_id = s.id
    JOIN songs sg ON ss.song_id = sg.id
    WHERE $dateCondition AND sg.tone IS NOT NULL AND sg.tone != ''
    GROUP BY sg.tone
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topTones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Top Tags
$stmt = $pdo->prepare("
    SELECT t.name, t.color, COUNT(*) as qtd
    FROM schedule_songs ss
    JOIN schedules s ON ss.schedule_id = s.id
    JOIN song_tags st ON ss.song_id = st.song_id
    JOIN tags t ON st.tag_id = t.id
    WHERE $dateCondition
    GROUP BY t.id
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topTags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Rotação de Músicas (Distribuição)
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN qtd = 1 THEN '1x'
            WHEN qtd BETWEEN 2 AND 3 THEN '2-3x'
            WHEN qtd BETWEEN 4 AND 6 THEN '4-6x'
            ELSE '7+x'
        END as faixa,
        COUNT(*) as musicas
    FROM (
        SELECT song_id, COUNT(*) as qtd
        FROM schedule_songs ss
        JOIN schedules s ON ss.schedule_id = s.id
        WHERE $dateCondition
        GROUP BY song_id
    ) sub
    GROUP BY faixa
    ORDER BY FIELD(faixa, '1x', '2-3x', '4-6x', '7+x')
");
$stmt->execute($params);
$songRotation = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Músicas Esquecidas (Não tocadas há muito tempo)
$stmt = $pdo->prepare("
    SELECT sg.title, sg.artist, MAX(s.event_date) as ultima_vez,
           DATEDIFF(CURDATE(), MAX(s.event_date)) as dias_atras
    FROM songs sg
    LEFT JOIN schedule_songs ss ON sg.id = ss.song_id
    LEFT JOIN schedules s ON ss.schedule_id = s.id
    WHERE sg.id IN (SELECT DISTINCT song_id FROM schedule_songs)
    GROUP BY sg.id
    HAVING ultima_vez < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    ORDER BY dias_atras DESC
    LIMIT 10
");
$stmt->execute();
$forgottenSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7. BPM Médio
$stmt = $pdo->prepare("
    SELECT AVG(sg.bpm) as bpm_medio, MIN(sg.bpm) as bpm_min, MAX(sg.bpm) as bpm_max
    FROM schedule_songs ss
    JOIN schedules s ON ss.schedule_id = s.id
    JOIN songs sg ON ss.song_id = sg.id
    WHERE $dateCondition AND sg.bpm IS NOT NULL AND sg.bpm > 0
");
$stmt->execute($params);
$bpmStats = $stmt->fetch(PDO::FETCH_ASSOC);

// 8. Completude do Repertório (Links)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN link_cifra IS NOT NULL AND link_cifra != '' THEN 1 ELSE 0 END) as com_cifra,
        SUM(CASE WHEN link_letra IS NOT NULL AND link_letra != '' THEN 1 ELSE 0 END) as com_letra,
        SUM(CASE WHEN link_audio IS NOT NULL AND link_audio != '' THEN 1 ELSE 0 END) as com_audio,
        SUM(CASE WHEN link_video IS NOT NULL AND link_video != '' THEN 1 ELSE 0 END) as com_video
    FROM songs
    WHERE id IN (SELECT DISTINCT song_id FROM schedule_songs ss JOIN schedules s ON ss.schedule_id = s.id WHERE $dateCondition)
");
$stmt->execute($params);
$repertoireCompleteness = $stmt->fetch(PDO::FETCH_ASSOC);


// D. SPIRITUAL DEEP DIVE
// ----------------------
// 1. All Users Plan Status
$stmt = $pdo->query("
    SELECT 
        u.name, 
        u.avatar_color,
        (SELECT setting_value FROM user_settings WHERE user_id = u.id AND setting_key = 'reading_plan_type' LIMIT 1) as plan,
        IFNULL((SELECT SUM(JSON_LENGTH(verses_read)) FROM reading_progress rp WHERE rp.user_id = u.id AND rp.completed_at BETWEEN '$startDate' AND '$endDate'), 0) as chapters_period
    FROM users u
    ORDER BY chapters_period DESC, u.name ASC
");
$spiritualData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Reading Hour Heatmap
$stmt = $pdo->prepare("
    SELECT HOUR(completed_at) as hour_of_day, COUNT(*) as qtd
    FROM reading_progress
    WHERE completed_at BETWEEN :start AND :end
    GROUP BY hour_of_day
    ORDER BY qtd DESC, hour_of_day ASC
");
$stmt->execute($params);
$readingHoursRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$readingHours = [];
foreach($readingHoursRaw as $r) $readingHours[$r['hour_of_day']] = $r['qtd'];

// 3. Ranking de Leitores (Top 10)
$stmt = $pdo->prepare("
    SELECT u.name, u.avatar_color,
           COUNT(DISTINCT rp.id) as total_leituras,
           IFNULL(SUM(JSON_LENGTH(rp.verses_read)), 0) as total_capitulos
    FROM users u
    LEFT JOIN reading_progress rp ON u.id = rp.user_id AND rp.completed_at BETWEEN :start AND :end
    GROUP BY u.id
    HAVING total_capitulos > 0
    ORDER BY total_capitulos DESC
    LIMIT 10
");
$stmt->execute($params);
$topReaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Taxa de Adesão ao Plano
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(DISTINCT user_id) FROM reading_progress WHERE completed_at BETWEEN :start AND :end) as leitores_ativos,
        (SELECT COUNT(*) FROM users) as total_usuarios
");
$stmt->execute($params);
$adherenceData = $stmt->fetch(PDO::FETCH_ASSOC);
$adherenceRate = $adherenceData['total_usuarios'] > 0 ? round(($adherenceData['leitores_ativos'] / $adherenceData['total_usuarios']) * 100) : 0;

// 5. Comparação entre Planos
$stmt = $pdo->prepare("
    SELECT 
        IFNULL((SELECT setting_value FROM user_settings WHERE user_id = u.id AND setting_key = 'reading_plan_type' LIMIT 1), 'nenhum') as plano,
        COUNT(DISTINCT u.id) as usuarios,
        IFNULL(SUM(JSON_LENGTH(rp.verses_read)), 0) as capitulos
    FROM users u
    LEFT JOIN reading_progress rp ON u.id = rp.user_id AND rp.completed_at BETWEEN :start AND :end
    GROUP BY plano
    ORDER BY capitulos DESC
");
$stmt->execute($params);
$planComparison = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Dias da Semana com Mais Leituras
$stmt = $pdo->prepare("
    SELECT 
        DAYOFWEEK(completed_at) as dia_num,
        CASE DAYOFWEEK(completed_at)
            WHEN 1 THEN 'Domingo'
            WHEN 2 THEN 'Segunda'
            WHEN 3 THEN 'Terça'
            WHEN 4 THEN 'Quarta'
            WHEN 5 THEN 'Quinta'
            WHEN 6 THEN 'Sexta'
            WHEN 7 THEN 'Sábado'
        END as dia_semana,
        COUNT(*) as qtd
    FROM reading_progress
    WHERE completed_at BETWEEN :start AND :end
    GROUP BY dia_num, dia_semana
    ORDER BY dia_num ASC
");
$stmt->execute($params);
$weekdayReading = $stmt->fetchAll(PDO::FETCH_ASSOC);


// E. ABSENCE ANALYSIS (NEW SECTION)
// ----------------------------------
// 1. Total de Ausências
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_ausencias
    FROM user_unavailability
    WHERE start_date BETWEEN :start AND :end
");
$stmt->execute($params);
$totalAbsences = $stmt->fetchColumn();

// 2. Membros com Mais Ausências
$stmt = $pdo->prepare("
    SELECT u.name, u.avatar_color, COUNT(*) as qtd
    FROM user_unavailability ua
    JOIN users u ON ua.user_id = u.id
    WHERE ua.start_date BETWEEN :start AND :end
    GROUP BY ua.user_id
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topAbsentMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Motivos Mais Comuns
$stmt = $pdo->prepare("
    SELECT reason, COUNT(*) as qtd
    FROM user_unavailability
    WHERE start_date BETWEEN :start AND :end AND reason IS NOT NULL AND reason != ''
    GROUP BY reason
    ORDER BY qtd DESC
    LIMIT 5
");
$stmt->execute($params);
$topAbsenceReasons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Taxa de Substituição
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN replacement_id IS NOT NULL THEN 1 ELSE 0 END) as com_substituto
    FROM user_unavailability
    WHERE start_date BETWEEN :start AND :end
");
$stmt->execute($params);
$substitutionData = $stmt->fetch(PDO::FETCH_ASSOC);
$substitutionRate = $substitutionData['total'] > 0 ? round(($substitutionData['com_substituto'] / $substitutionData['total']) * 100) : 0;

// 5. Membros que Mais Substituem
$stmt = $pdo->prepare("
    SELECT u.name, u.avatar_color, COUNT(*) as vezes
    FROM user_unavailability ua
    JOIN users u ON ua.replacement_id = u.id
    WHERE ua.start_date BETWEEN :start AND :end
    GROUP BY ua.replacement_id
    ORDER BY vezes DESC
    LIMIT 5
");
$stmt->execute($params);
$topSubstitutes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Ausências com Áudio
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN audio_path IS NOT NULL AND audio_path != '' THEN 1 ELSE 0 END) as com_audio
    FROM user_unavailability
    WHERE start_date BETWEEN :start AND :end
");
$stmt->execute($params);
$audioData = $stmt->fetch(PDO::FETCH_ASSOC);
$audioRate = $audioData['total'] > 0 ? round(($audioData['com_audio'] / $audioData['total']) * 100) : 0;


// F. CROSS ANALYSIS (NEW SECTION)
// --------------------------------
// 1. Correlação Participação x Leitura (Engagement Score)
$stmt = $pdo->prepare("
    SELECT 
        u.name,
        u.avatar_color,
        u.instrument,
        COUNT(DISTINCT su.schedule_id) as escalas_confirmadas,
        IFNULL(SUM(JSON_LENGTH(rp.verses_read)), 0) as capitulos_lidos,
        COUNT(DISTINCT ua.id) as ausencias
    FROM users u
    LEFT JOIN schedule_users su ON u.id = su.user_id AND su.status = 'confirmed'
    LEFT JOIN schedules s ON su.schedule_id = s.id AND $dateCondition
    LEFT JOIN reading_progress rp ON u.id = rp.user_id AND rp.completed_at BETWEEN :start AND :end
    LEFT JOIN user_unavailability ua ON u.id = ua.user_id AND ua.start_date BETWEEN :start AND :end
    GROUP BY u.id
    ORDER BY escalas_confirmadas DESC, capitulos_lidos DESC
");
$stmt->execute($params);
$engagementData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular Score de Engajamento (0-100)
$maxScales = max(array_column($engagementData, 'escalas_confirmadas')) ?: 1;
$maxChapters = max(array_column($engagementData, 'capitulos_lidos')) ?: 1;
foreach($engagementData as &$member) {
    $scaleScore = ($member['escalas_confirmadas'] / $maxScales) * 40;
    $readingScore = ($member['capitulos_lidos'] / $maxChapters) * 40;
    $absencePenalty = min($member['ausencias'] * 5, 20); // Máximo -20 pontos
    $member['engagement_score'] = max(0, round($scaleScore + $readingScore - $absencePenalty));
}
unset($member);

// Ordenar por score
usort($engagementData, function($a, $b) {
    return $b['engagement_score'] - $a['engagement_score'];
});

// Top 5 MVPs
$mvpMembers = array_slice($engagementData, 0, 5);


// --- 3. RENDER LOGIC ---
if ($isPrintMode) {
    // PRINT VIEW (Ultra Complete)
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório Analítico Ultra Completo</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        <style>
             body { font-family: 'Inter', sans-serif; padding: 30px; max-width: 210mm; margin: 0 auto; color: #1e293b; background: white; font-size: 10px; line-height: 1.4;}
             @media print { body { padding: 0; margin: 8mm; font-size: 9px; } .no-print { display: none; } @page { margin: 10mm; } }
             h1 { font-size: 22px; margin: 0 0 4px 0; color: #0f172a; }
             h2 { font-size: 14px; margin: 25px 0 12px 0; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0; color: #0f172a; text-transform: uppercase; font-weight: 800; page-break-after: avoid; }
             h3 { font-size: 11px; margin: 15px 0 8px 0; font-weight: 700; color: #64748b; page-break-after: avoid; }
             table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 15px; page-break-inside: avoid; }
             th { text-align: left; background: #f8fafc; padding: 5px 6px; font-weight: 700; color: #64748b; border-bottom: 1px solid #cbd5e1; }
             td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; }
             .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
             .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px; }
             .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
             .stat-box { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
             .stat-box:last-child { border-bottom: none; }
             .kpi-card { border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; text-align: center; page-break-inside: avoid; }
             .kpi-value { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
             .kpi-label { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; }
             .badge { padding: 2px 6px; border-radius: 3px; font-weight: 700; font-size: 8px; display: inline-block; }
             .section { margin-bottom: 30px; page-break-inside: avoid; }
             .alert-box { padding: 10px; border-radius: 6px; margin-bottom: 15px; page-break-inside: avoid; }
             .podium { display: flex; justify-content: center; align-items: flex-end; gap: 10px; margin: 20px 0; page-break-inside: avoid; }
             .podium-item { text-align: center; }
             .podium-bar { border-radius: 6px 6px 0 0; padding: 10px 8px; color: white; font-weight: 800; }
        </style>
    </head>
    <body>
        <div class="no-print" style="margin-bottom: 20px; text-align: right;">
            <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #0f172a; color: white; border: none; border-radius: 6px; font-weight: 600;">
                <i data-lucide="printer" style="width: 16px; margin-right: 5px; vertical-align: middle;"></i> Salvar como PDF / Imprimir
            </button>
            <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; margin-left: 10px;">Fechar</button>
        </div>

        <!-- HEADER -->
        <div style="margin-bottom: 30px;">
            <h1>Relatório Analítico Ultra Completo</h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 11px; font-weight: 600;">PIB Oliveira • Ministério de Louvor • <?= $titlePeriod ?></p>
        </div>

        <!-- KPIs PRINCIPAIS -->
        <div class="grid-4">
            <?php 
                $kpis = [
                    ['Escalas', $kpi_scales, '#3b82f6'],
                    ['Adesão', $rate_confirmed . '%', '#10b981'],
                    ['Músicas', $kpi_songs, '#ec4899'],
                    ['Capítulos', number_format($kpi_chapters), '#8b5cf6']
                ];
                foreach($kpis as $k): ?>
                <div class="kpi-card" style="border-left: 3px solid <?= $k[2] ?>;">
                    <div class="kpi-value" style="color: <?= $k[2] ?>;"><?= $k[1] ?></div>
                    <div class="kpi-label"><?= $k[0] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SEÇÃO 1: ANÁLISE DE ESCALAS -->
        <div class="section">
            <h2>📈 Análise de Escalas</h2>
            
            <div class="grid-2">
                <!-- Taxa de Confirmação -->
                <div>
                    <h3>Taxa de Confirmação por Membro</h3>
                    <table>
                        <thead><tr><th>Membro</th><th style="text-align: center;">Confirmadas</th><th style="text-align: center;">Taxa</th></tr></thead>
                        <tbody>
                        <?php foreach(array_slice($memberConfirmRate, 0, 10) as $m): ?>
                            <tr>
                                <td><b><?= $m['name'] ?></b></td>
                                <td style="text-align: center;"><?= $m['confirmed'] ?>/<?= $m['total_invites'] ?></td>
                                <td style="text-align: center;">
                                    <span class="badge" style="background: <?= $m['rate'] >= 80 ? '#10b981' : ($m['rate'] >= 60 ? '#f59e0b' : '#ef4444') ?>; color: white;">
                                        <?= $m['rate'] ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Membros Mais Escalados -->
                <div>
                    <h3>Membros Mais Escalados</h3>
                    <table>
                        <thead><tr><th>Membro</th><th style="text-align: center;">Escalas</th></tr></thead>
                        <tbody>
                        <?php foreach(array_slice($memberScaleCount, 0, 10) as $m): ?>
                            <tr>
                                <td><b><?= $m['name'] ?></b></td>
                                <td style="text-align: center; font-weight: 700; color: #3b82f6;"><?= $m['qtd'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid-2">
                <!-- Duplas Mais Frequentes -->
                <div>
                    <h3>Duplas Mais Frequentes</h3>
                    <?php foreach(array_slice($topPairs, 0, 8) as $p): ?>
                    <div class="stat-box">
                        <span><?= $p['p1'] ?> & <?= $p['p2'] ?></span>
                        <b style="color: #3b82f6;"><?= $p['qtd'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Substituições Frequentes -->
                <?php if(!empty($substitutions)): ?>
                <div>
                    <h3>Substituições Mais Frequentes</h3>
                    <?php foreach(array_slice($substitutions, 0, 8) as $s): ?>
                    <div class="stat-box">
                        <span style="font-size: 9px;"><?= $s['substituido'] ?> → <?= $s['substituto'] ?></span>
                        <b style="color: #3b82f6;"><?= $s['vezes'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tendência Temporal -->
            <?php if(!empty($scaleTrend)): ?>
            <div>
                <h3>Tendência Temporal (Escalas por Mês)</h3>
                <div style="display: flex; align-items: flex-end; gap: 4px; height: 60px; border-bottom: 1px solid #cbd5e1;">
                    <?php 
                    $maxTrend = max(array_column($scaleTrend, 'qtd')) ?: 1;
                    foreach($scaleTrend as $t): 
                        $height = ($t['qtd'] / $maxTrend) * 100;
                    ?>
                    <div style="flex: 1; background: #3b82f6; border-radius: 3px 3px 0 0; height: <?= max($height, 5) ?>%; position: relative;">
                        <div style="position: absolute; top: -15px; left: 50%; transform: translateX(-50%); font-size: 8px; font-weight: 700;"><?= $t['qtd'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 7px; color: #94a3b8; margin-top: 3px;">
                    <?php foreach($scaleTrend as $t): ?>
                    <span><?= $t['month'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SEÇÃO 2: ANÁLISE DE REPERTÓRIO -->
        <div class="section" style="page-break-before: always;">
            <h2>🎵 Análise de Repertório</h2>
            
            <div class="grid-2">
                <!-- Top 10 Músicas -->
                <div>
                    <h3>Top 10 Músicas Mais Tocadas</h3>
                    <table>
                        <thead><tr><th>#</th><th>Música</th><th style="text-align: center;">Vezes</th></tr></thead>
                        <tbody>
                        <?php foreach($topSongs as $idx => $s): ?>
                            <tr>
                                <td style="color: #94a3b8; font-weight: 700;"><?= $idx+1 ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= $s['title'] ?></div>
                                    <div style="font-size: 8px; color: #94a3b8;"><?= $s['artist'] ?></div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #ec4899;"><?= $s['qtd'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rotação & BPM -->
                <div>
                    <h3>Rotação de Músicas</h3>
                    <?php if(!empty($songRotation)): ?>
                    <table>
                        <thead><tr><th>Frequência</th><th style="text-align: center;">Músicas</th></tr></thead>
                        <tbody>
                        <?php foreach($songRotation as $sr): ?>
                            <tr>
                                <td><b><?= $sr['faixa'] ?></b></td>
                                <td style="text-align: center; font-weight: 700;"><?= $sr['musicas'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if($bpmStats && $bpmStats['bpm_medio']): ?>
                    <div style="margin-top: 15px; padding: 10px; background: #f0f9ff; border-radius: 6px; border: 1px solid #bae6fd; text-align: center;">
                        <div style="font-size: 8px; color: #0369a1; font-weight: 600; margin-bottom: 3px;">BPM MÉDIO</div>
                        <div style="font-size: 18px; font-weight: 800; color: #0c4a6e;"><?= round($bpmStats['bpm_medio']) ?></div>
                        <div style="font-size: 8px; color: #0369a1;">Min: <?= $bpmStats['bpm_min'] ?> | Max: <?= $bpmStats['bpm_max'] ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid-3">
                <!-- Top Artistas -->
                <div>
                    <h3>Top Artistas</h3>
                    <?php foreach(array_slice($topArtists, 0, 8) as $a): ?>
                    <div class="stat-box">
                        <span><?= $a['artist'] ?></span>
                        <b><?= $a['qtd'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tons Preferidos -->
                <div>
                    <h3>Tons Mais Usados</h3>
                    <?php foreach(array_slice($topTones, 0, 8) as $t): ?>
                    <div class="stat-box">
                        <span><b><?= $t['tone'] ?></b></span>
                        <span><?= $t['qtd'] ?>x</span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tags -->
                <div>
                    <h3>Tags Mais Usadas</h3>
                    <?php foreach(array_slice($topTags, 0, 8) as $t): ?>
                    <div class="stat-box">
                        <span><?= $t['name'] ?></span>
                        <b><?= $t['qtd'] ?></b>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Completude do Repertório -->
            <?php if($repertoireCompleteness && $repertoireCompleteness['total'] > 0): ?>
            <div>
                <h3>Completude do Repertório (Links Disponíveis)</h3>
                <div class="grid-4">
                    <?php 
                    $links = [
                        ['label' => 'Cifra', 'count' => $repertoireCompleteness['com_cifra'], 'color' => '#3b82f6'],
                        ['label' => 'Letra', 'count' => $repertoireCompleteness['com_letra'], 'color' => '#10b981'],
                        ['label' => 'Áudio', 'count' => $repertoireCompleteness['com_audio'], 'color' => '#f59e0b'],
                        ['label' => 'Vídeo', 'count' => $repertoireCompleteness['com_video'], 'color' => '#ec4899']
                    ];
                    foreach($links as $link):
                        $pct = round(($link['count'] / $repertoireCompleteness['total']) * 100);
                    ?>
                    <div class="kpi-card" style="border-left: 3px solid <?= $link['color'] ?>;">
                        <div class="kpi-value" style="color: <?= $link['color'] ?>;"><?= $pct ?>%</div>
                        <div class="kpi-label"><?= $link['label'] ?></div>
                        <div style="font-size: 8px; color: #94a3b8; margin-top: 2px;"><?= $link['count'] ?>/<?= $repertoireCompleteness['total'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Músicas Esquecidas -->
            <?php if(!empty($forgottenSongs)): ?>
            <div class="alert-box" style="background: #fef2f2; border: 1px solid #fee2e2;">
                <h3 style="color: #dc2626; margin-top: 0;">⚠️ Músicas Esquecidas (Não tocadas há 90+ dias)</h3>
                <div class="grid-2">
                    <?php foreach(array_slice($forgottenSongs, 0, 10) as $fs): ?>
                    <div class="stat-box">
                        <div>
                            <div style="font-weight: 600; font-size: 9px;"><?= $fs['title'] ?></div>
                            <div style="font-size: 8px; color: #94a3b8;"><?= $fs['artist'] ?></div>
                        </div>
                        <span style="color: #dc2626; font-weight: 700; font-size: 9px;"><?= $fs['dias_atras'] ?> dias</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- SEÇÃO 3: ANÁLISE DE LEITURAS BÍBLICAS -->
        <div class="section" style="page-break-before: always;">
            <h2>📖 Análise de Leituras Bíblicas</h2>
            
            <div class="grid-3">
                <!-- KPIs de Leitura -->
                <div class="kpi-card" style="border-left: 3px solid #8b5cf6;">
                    <div class="kpi-value" style="color: #8b5cf6;"><?= $adherenceRate ?>%</div>
                    <div class="kpi-label">Taxa de Adesão</div>
                    <div style="font-size: 8px; color: #94a3b8; margin-top: 2px;"><?= $adherenceData['leitores_ativos'] ?>/<?= $adherenceData['total_usuarios'] ?> membros</div>
                </div>

                <?php if(!empty($planComparison)): ?>
                <?php foreach($planComparison as $pc): ?>
                <div class="kpi-card" style="border-left: 3px solid #8b5cf6;">
                    <div class="kpi-value" style="color: #8b5cf6;"><?= $pc['capitulos'] ?></div>
                    <div class="kpi-label"><?= $pc['plano'] ?></div>
                    <div style="font-size: 8px; color: #94a3b8; margin-top: 2px;">capítulos lidos</div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="grid-2">
                <!-- Ranking de Leitores -->
                <div>
                    <h3>🏆 Top 10 Leitores</h3>
                    <table>
                        <thead><tr><th>#</th><th>Membro</th><th style="text-align: center;">Capítulos</th></tr></thead>
                        <tbody>
                        <?php foreach($topReaders as $idx => $r): ?>
                            <tr>
                                <td style="font-weight: 700; color: <?= $idx < 3 ? '#f59e0b' : '#94a3b8' ?>;"><?= $idx+1 ?></td>
                                <td><b><?= $r['name'] ?></b></td>
                                <td style="text-align: center; font-weight: 700; color: #8b5cf6;"><?= $r['total_capitulos'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Leitura por Membro (Todos) -->
                <div>
                    <h3>Status de Leitura (Todos os Membros)</h3>
                    <table>
                        <thead><tr><th>Membro</th><th>Plano</th><th style="text-align: center;">Caps.</th></tr></thead>
                        <tbody>
                        <?php foreach($spiritualData as $sd): ?>
                            <tr>
                                <td><?= $sd['name'] ?></td>
                                <td style="font-size: 8px; color: #64748b;"><?= ucfirst($sd['plan']) ?></td>
                                <td style="text-align: center; font-weight: 700;"><?= $sd['chapters_period'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Horários e Dias -->
            <div class="grid-2">
                <div>
                    <h3>Horários Mais Comuns de Leitura</h3>
                    <?php 
                    $hoursSorted = $readingHours; 
                    arsort($hoursSorted);
                    $i=0;
                    foreach($hoursSorted as $h => $q): 
                        if($i++ >= 8) break;
                        $periodo = $h >= 6 && $h < 12 ? 'Manhã' : ($h >= 12 && $h < 18 ? 'Tarde' : ($h >= 18 && $h < 23 ? 'Noite' : 'Madrugada'));
                    ?>
                    <div class="stat-box">
                        <span><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00 - <?= str_pad($h+1, 2, '0', STR_PAD_LEFT) ?>:00 (<?= $periodo ?>)</span>
                        <b><?= $q ?> caps</b>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div>
                    <h3>Dias da Semana com Mais Leituras</h3>
                    <?php if(!empty($weekdayReading)): ?>
                    <?php foreach($weekdayReading as $wd): ?>
                    <div class="stat-box">
                        <span><?= $wd['dia_semana'] ?></span>
                        <b><?= $wd['qtd'] ?> caps</b>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: ANÁLISE DE AUSÊNCIAS -->
        <?php if($totalAbsences > 0): ?>
        <div class="section" style="page-break-before: always;">
            <h2>⚠️ Análise de Ausências</h2>
            
            <!-- KPIs de Ausências -->
            <div class="grid-3">
                <div class="kpi-card" style="border-left: 3px solid #f59e0b;">
                    <div class="kpi-value" style="color: #f59e0b;"><?= $totalAbsences ?></div>
                    <div class="kpi-label">Total de Ausências</div>
                </div>

                <div class="kpi-card" style="border-left: 3px solid #10b981;">
                    <div class="kpi-value" style="color: #10b981;"><?= $substitutionRate ?>%</div>
                    <div class="kpi-label">Com Substituto</div>
                </div>

                <div class="kpi-card" style="border-left: 3px solid #3b82f6;">
                    <div class="kpi-value" style="color: #3b82f6;"><?= $audioRate ?>%</div>
                    <div class="kpi-label">Com Áudio Explicativo</div>
                </div>
            </div>

            <div class="grid-3">
                <!-- Membros com Mais Ausências -->
                <div>
                    <h3>Membros com Mais Ausências</h3>
                    <?php foreach(array_slice($topAbsentMembers, 0, 10) as $m): ?>
                    <div class="stat-box">
                        <span><?= $m['name'] ?></span>
                        <b style="color: #f59e0b;"><?= $m['qtd'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Motivos Mais Comuns -->
                <?php if(!empty($topAbsenceReasons)): ?>
                <div>
                    <h3>Motivos Mais Comuns</h3>
                    <?php foreach($topAbsenceReasons as $r): ?>
                    <div class="stat-box">
                        <span style="font-size: 9px;"><?= $r['reason'] ?></span>
                        <b><?= $r['qtd'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Membros que Mais Substituem -->
                <?php if(!empty($topSubstitutes)): ?>
                <div>
                    <h3>🦸 Membros que Mais Substituem</h3>
                    <?php foreach($topSubstitutes as $s): ?>
                    <div class="stat-box">
                        <span><?= $s['name'] ?></span>
                        <b style="color: #10b981;"><?= $s['vezes'] ?>x</b>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- SEÇÃO 5: ANÁLISES CRUZADAS & MVPs -->
        <div class="section" style="page-break-before: always;">
            <h2>🏆 Análises Cruzadas & Membros MVP</h2>
            
            <!-- Pódio MVP -->
            <div>
                <h3 style="text-align: center; margin-bottom: 15px;">Top 5 Membros MVP (Maior Engajamento)</h3>
                <div class="podium">
                    <?php foreach($mvpMembers as $idx => $mvp): 
                        $heights = [90, 110, 80, 70, 60];
                        $colors = ['#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e'];
                        $medals = ['🥇', '🥈', '🥉', '4º', '5º'];
                    ?>
                    <div class="podium-item">
                        <div class="podium-bar" style="width: 50px; height: <?= $heights[$idx] ?>px; background: <?= $colors[$idx] ?>;">
                            <div style="font-size: 14px; margin-bottom: 2px;"><?= $medals[$idx] ?></div>
                            <div style="font-size: 16px; font-weight: 800;"><?= $mvp['engagement_score'] ?></div>
                            <div style="font-size: 7px; opacity: 0.9;">pts</div>
                        </div>
                        <div style="margin-top: 5px; font-weight: 700; font-size: 9px; color: #0f172a;"><?= $mvp['name'] ?></div>
                        <div style="font-size: 7px; color: #64748b;"><?= $mvp['instrument'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tabela Completa de Engajamento -->
            <div>
                <h3>Score de Engajamento (Todos os Membros)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Instrumento</th>
                            <th style="text-align: center;">Escalas</th>
                            <th style="text-align: center;">Capítulos</th>
                            <th style="text-align: center;">Ausências</th>
                            <th style="text-align: center;">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($engagementData as $member): ?>
                        <tr>
                            <td><b><?= $member['name'] ?></b></td>
                            <td style="font-size: 8px; color: #64748b;"><?= $member['instrument'] ?></td>
                            <td style="text-align: center; font-weight: 700; color: #3b82f6;"><?= $member['escalas_confirmadas'] ?></td>
                            <td style="text-align: center; font-weight: 700; color: #8b5cf6;"><?= $member['capitulos_lidos'] ?></td>
                            <td style="text-align: center; font-weight: 700; color: <?= $member['ausencias'] > 0 ? '#f59e0b' : '#10b981' ?>;"><?= $member['ausencias'] ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: <?= $member['engagement_score'] >= 70 ? '#10b981' : ($member['engagement_score'] >= 40 ? '#f59e0b' : '#ef4444') ?>; color: white; font-size: 9px; padding: 3px 8px;">
                                    <?= $member['engagement_score'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Legenda do Score -->
            <div style="margin-top: 15px; padding: 10px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 9px; font-weight: 700; margin-bottom: 5px; color: #64748b;">COMO É CALCULADO O SCORE DE ENGAJAMENTO:</div>
                <div style="font-size: 8px; line-height: 1.6; color: #475569;">
                    • <b>40 pontos</b> baseados em escalas confirmadas (proporcional ao membro mais escalado)<br>
                    • <b>40 pontos</b> baseados em capítulos lidos (proporcional ao maior leitor)<br>
                    • <b>-5 pontos</b> por ausência (máximo -20 pontos)<br>
                    • <span style="color: #10b981; font-weight: 700;">70-100 pts</span> = Excelente | 
                    <span style="color: #f59e0b; font-weight: 700;">40-69 pts</span> = Moderado | 
                    <span style="color: #ef4444; font-weight: 700;">0-39 pts</span> = Precisa melhorar
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div style="margin-top: 40px; padding-top: 15px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 8px; color: #94a3b8;">
            <p style="margin: 0;">Relatório gerado em <?= date('d/m/Y \à\s H:i') ?> • PIB Oliveira - Ministério de Louvor</p>
            <p style="margin: 4px 0 0;">Sistema de Gestão de Escalas e Repertório v2.0</p>
        </div>

        <script>lucide.createIcons();</script>
    </body>
    </html>
    <?php
    exit;
}


// --- STANDARD VIEW ---
renderAppHeader('Indicadores Avançados');
?>

<style>
    .stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
    .stat-title { font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; }
    .list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .list-item:last-child { border: none; }
    .metric-big { font-size: 18px; font-weight: 800; color: #0f172a; }
    .metric-sub { font-size: 12px; color: #64748b; }
    
    .heatmap { display: flex; align-items: flex-end; gap: 4px; height: 100px; margin-top: 20px; border-bottom: 1px solid #cbd5e1; }
    .heat-bar { flex: 1; background: #3b82f6; border-radius: 4px 4px 0 0; position: relative; transition: all 0.2s; }
    .heat-bar:hover { background: #2563eb; }
    .heat-bar:hover::after { content: attr(data-val); position: absolute; top: -25px; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; }
</style>

<div class="container-fluid" style="padding: 20px; max-width: 1200px; margin: 0 auto;">

     <!-- HEADER & FILTERS -->
    <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div>
            <h2 style="margin: 0; font-size: 20px; color: #0f172a;">Painel de Indicadores</h2>
            <p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">Análise Profunda: <strong><?= $titlePeriod ?></strong></p>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <form method="GET" style="display: flex; gap: 10px;">
                <select name="period" onchange="this.form.submit()" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <option value="month" <?= $period=='month'?'selected':'' ?>>Mensal</option>
                    <option value="year" <?= $period=='year'?'selected':'' ?>>Anual</option>
                </select>
                <select name="year" onchange="this.form.submit()" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <?php for($y=2024; $y<=date('Y')+1; $y++): ?>
                    <option value="<?= $y ?>" <?= $year==$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <?php if($period=='month'): ?>
                <select name="month" onchange="this.form.submit()" style="padding: 8px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <?php for($m=1; $m<=12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month==$m?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                    <?php endfor; ?>
                </select>
                <?php endif; ?>
            </form>
             <button onclick="window.open('relatorios_gerais.php?print=true&period=<?= $period ?>&year=<?= $year ?>&month=<?= $month ?>', '_blank')" style="background: #0f172a; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i data-lucide="printer" style="width: 16px; display:inline-block; vertical-align:middle; margin-right:5px"></i> Imprimir
            </button>
    </div>
</div>


    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
        
        <!-- SEÇÃO 1: ANÁLISE DE ESCALAS (EXPANDIDA) -->
        <div class="stat-card" style="grid-column: 1 / -1;">
            <div class="stat-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSection('scales')">
                <span>📈 ANÁLISE DE ESCALAS</span>
                <i data-lucide="chevron-down" id="icon-scales" style="width: 20px;"></i>
            </div>
            <div id="section-scales">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    


                    <!-- Membros Mais Escalados -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Membros Mais Escalados</h5>
                        <canvas id="chartMemberScales" style="max-height: 200px;"></canvas>
                    </div>

                    <!-- Duplas & Substituições -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Duplas Mais Frequentes</h5>
                        <?php foreach($topPairs as $p): ?>
                        <div class="list-item">
                            <div style="font-weight: 600; font-size: 13px;"><?= $p['p1'] ?> <span style="color: #cbd5e1;">+</span> <?= $p['p2'] ?></div>
                            <div class="metric-big" style="font-size: 14px;"><?= $p['qtd'] ?> <span class="metric-sub">vezes</span></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(!empty($substitutions)): ?>
                        <h5 style="font-size: 13px; margin: 20px 0 10px 0; color: #64748b;">Substituições Frequentes</h5>
                        <?php foreach(array_slice($substitutions, 0, 3) as $s): ?>
                        <div class="list-item">
                            <div style="font-size: 12px;"><?= $s['substituido'] ?> → <?= $s['substituto'] ?></div>
                            <div style="font-weight: 700; color: #3b82f6;"><?= $s['vezes'] ?>x</div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>


            </div>
        </div>

        <!-- SEÇÃO 2: ANÁLISE DE REPERTÓRIO (EXPANDIDA) -->
        <div class="stat-card" style="grid-column: 1 / -1;">
            <div class="stat-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSection('repertoire')">
                <span>🎵 ANÁLISE DE REPERTÓRIO</span>
                <i data-lucide="chevron-down" id="icon-repertoire" style="width: 20px;"></i>
            </div>
            <div id="section-repertoire">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    
                    <!-- Top Músicas -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Top 10 Músicas</h5>
                        <table style="width: 100%; font-size: 12px;">
                            <tbody>
                            <?php foreach($topSongs as $idx => $s): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 6px 0; color: #94a3b8; font-weight: 700;">#<?= $idx+1 ?></td>
                                    <td style="padding: 6px 0;">
                                        <div style="font-weight: 600;"><?= $s['title'] ?></div>
                                        <div style="font-size: 10px; color: #94a3b8;"><?= $s['artist'] ?></div>
                                    </td>
                                    <td style="text-align: right; padding: 6px 0; font-weight: 700;"><?= $s['qtd'] ?>x</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Rotação & BPM -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Rotação de Músicas</h5>
                        <canvas id="chartSongRotation" style="max-height: 200px;"></canvas>
                        
                        <?php if($bpmStats && $bpmStats['bpm_medio']): ?>
                        <div style="margin-top: 20px; padding: 12px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
                            <div style="font-size: 11px; color: #0369a1; font-weight: 600; margin-bottom: 4px;">BPM Médio</div>
                            <div style="font-size: 24px; font-weight: 800; color: #0c4a6e;"><?= round($bpmStats['bpm_medio']) ?></div>
                            <div style="font-size: 10px; color: #0369a1;">Min: <?= $bpmStats['bpm_min'] ?> | Max: <?= $bpmStats['bpm_max'] ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Artistas, Tons & Tags -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Top Artistas</h5>
                        <?php foreach(array_slice($topArtists, 0, 5) as $a): ?>
                        <div class="list-item">
                            <span style="font-weight: 600; font-size: 13px;"><?= $a['artist'] ?></span>
                            <span style="font-weight: 700;"><?= $a['qtd'] ?>x</span>
                        </div>
                        <?php endforeach; ?>

                        <h5 style="font-size: 13px; margin: 20px 0 10px 0; color: #64748b;">Tons Preferidos</h5>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php foreach($topTones as $t): ?>
                            <div style="background: #f1f5f9; border-radius: 6px; padding: 8px 12px; text-align: center;">
                                <div style="font-weight: 800; color: #334155;"><?= $t['tone'] ?></div>
                                <div style="font-size: 10px; color: #94a3b8;"><?= $t['qtd'] ?>x</div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <h5 style="font-size: 13px; margin: 20px 0 10px 0; color: #64748b;">Tags Mais Usadas</h5>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach($topTags as $t): ?>
                            <span style="background: <?= $t['color'] ?>20; color: <?= $t['color'] ?>; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                                <?= $t['name'] ?> (<?= $t['qtd'] ?>)
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Completude do Repertório -->
                <?php if($repertoireCompleteness && $repertoireCompleteness['total'] > 0): ?>
                <div style="margin-top: 30px;">
                    <h5 style="font-size: 13px; margin: 0 0 15px 0; color: #64748b;">Completude do Repertório (Links Disponíveis)</h5>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                        <?php 
                        $links = [
                            ['label' => 'Cifra', 'count' => $repertoireCompleteness['com_cifra'], 'color' => '#3b82f6'],
                            ['label' => 'Letra', 'count' => $repertoireCompleteness['com_letra'], 'color' => '#10b981'],
                            ['label' => 'Áudio', 'count' => $repertoireCompleteness['com_audio'], 'color' => '#f59e0b'],
                            ['label' => 'Vídeo', 'count' => $repertoireCompleteness['com_video'], 'color' => '#ec4899']
                        ];
                        foreach($links as $link):
                            $pct = round(($link['count'] / $repertoireCompleteness['total']) * 100);
                        ?>
                        <div onclick="showMissingSongs('<?= $link['label'] ?>', '<?= strtolower($link['label']) ?>')" style="padding: 12px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: transform 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            <div style="font-size: 11px; color: #64748b; margin-bottom: 4px;"><?= $link['label'] ?></div>
                            <div style="font-size: 24px; font-weight: 800; color: <?= $link['color'] ?>;"><?= $pct ?>%</div>
                            <div style="font-size: 10px; color: #94a3b8;"><?= $link['count'] ?>/<?= $repertoireCompleteness['total'] ?></div>
                            <div style="font-size: 9px; color: <?= $link['color'] ?>; margin-top: 4px; font-weight: 600;">Ver pendências →</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Músicas Esquecidas -->
                <?php if(!empty($forgottenSongs)): ?>
                <div style="margin-top: 30px; padding: 16px; background: #fef2f2; border-radius: 8px; border: 1px solid #fee2e2;">
                    <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #dc2626;">⚠️ Músicas Esquecidas (Não tocadas há 90+ dias)</h5>
                    <?php foreach(array_slice($forgottenSongs, 0, 5) as $fs): ?>
                    <div class="list-item">
                        <div>
                            <div style="font-weight: 600; font-size: 13px;"><?= $fs['title'] ?></div>
                            <div style="font-size: 11px; color: #94a3b8;"><?= $fs['artist'] ?></div>
                        </div>
                        <div style="text-align: right; color: #dc2626; font-weight: 700; font-size: 12px;">
                            <?= $fs['dias_atras'] ?> dias
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEÇÃO 3: ANÁLISE DE LEITURAS BÍBLICAS (EXPANDIDA) -->
        <div class="stat-card" style="grid-column: 1 / -1;">
            <div class="stat-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSection('reading')">
                <span>📖 ANÁLISE DE LEITURAS BÍBLICAS</span>
                <i data-lucide="chevron-down" id="icon-reading" style="width: 20px;"></i>
            </div>
            <div id="section-reading">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    
                    <!-- Ranking de Leitores -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">🏆 Top 10 Leitores</h5>
                        <?php foreach($topReaders as $idx => $r): ?>
                        <div class="list-item">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $r['avatar_color'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px;">
                                    <?= $idx+1 ?>
                                </div>
                                <span style="font-weight: 600; font-size: 13px;"><?= $r['name'] ?></span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 800; color: #8b5cf6;"><?= $r['total_capitulos'] ?></div>
                                <div style="font-size: 10px; color: #94a3b8;">caps</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Taxa de Adesão & Comparação de Planos -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Taxa de Adesão ao Plano</h5>
                        <div style="padding: 20px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); border-radius: 12px; text-align: center; color: white;">
                            <div style="font-size: 48px; font-weight: 800; margin-bottom: 8px;"><?= $adherenceRate ?>%</div>
                            <div style="font-size: 13px; opacity: 0.9;"><?= $adherenceData['leitores_ativos'] ?> de <?= $adherenceData['total_usuarios'] ?> membros</div>
                        </div>


                    </div>

                    <!-- Horários de Leitura -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Horários Mais Comuns</h5>
                        <?php 
                        $hoursSorted = $readingHours; 
                        arsort($hoursSorted);
                        $i=0;
                        foreach($hoursSorted as $h => $q): 
                            if($i++ >= 5) break;
                            $periodo = $h >= 6 && $h < 12 ? 'Manhã' : ($h >= 12 && $h < 18 ? 'Tarde' : ($h >= 18 && $h < 23 ? 'Noite' : 'Madrugada'));
                        ?>
                        <div class="list-item">
                            <span><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00 - <?= str_pad($h+1, 2, '0', STR_PAD_LEFT) ?>:00 (<?= $periodo ?>)</span>
                            <b><?= $q ?> caps</b>
                        </div>
                        <?php endforeach; ?>

                        <h5 style="font-size: 13px; margin: 20px 0 10px 0; color: #64748b;">Dias da Semana</h5>
                        <canvas id="chartWeekdayReading" style="max-height: 150px;"></canvas>
                    </div>
                </div>

                <!-- Heatmap 24h -->
                <div style="margin-top: 30px;">
                    <h5 style="font-size: 13px; margin: 0 0 15px 0; color: #64748b;">Heatmap de Leituras (24 horas)</h5>
                    <div class="heatmap">
                        <?php 
                        $maxRead = max($readingHours) ?: 1;
                        for($h=0; $h<24; $h++): 
                           $val = $readingHours[$h] ?? 0;
                           $height = ($val / $maxRead) * 100;
                           $color = $height > 0 ? '#8b5cf6' : '#e2e8f0';
                        ?>
                        <div class="heat-bar" style="height: <?= max($height, 5) ?>%; background: <?= $color ?>;" data-val="<?= $val > 0 ? $val : '' ?>" title="<?= $h ?>h: <?= $val ?> leituras"></div>
                        <?php endfor; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; margin-top: 4px;">
                        <span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>23h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: ANÁLISE DE AUSÊNCIAS (NOVA) -->
        <?php if($totalAbsences > 0): ?>
        <div class="stat-card" style="grid-column: 1 / -1;">
            <div class="stat-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSection('absences')">
                <span>⚠️ ANÁLISE DE AUSÊNCIAS</span>
                <i data-lucide="chevron-down" id="icon-absences" style="width: 20px;"></i>
            </div>
            <div id="section-absences">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    
                    <!-- KPIs de Ausências -->
                    <div style="padding: 20px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 48px; font-weight: 800; margin-bottom: 8px;"><?= $totalAbsences ?></div>
                        <div style="font-size: 13px; opacity: 0.9;">Total de Ausências</div>
                    </div>

                    <div style="padding: 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 48px; font-weight: 800; margin-bottom: 8px;"><?= $substitutionRate ?>%</div>
                        <div style="font-size: 13px; opacity: 0.9;">Com Substituto</div>
                    </div>

                    <div style="padding: 20px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px; text-align: center; color: white;">
                        <div style="font-size: 48px; font-weight: 800; margin-bottom: 8px;"><?= $audioRate ?>%</div>
                        <div style="font-size: 13px; opacity: 0.9;">Com Áudio Explicativo</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    
                    <!-- Membros com Mais Ausências -->
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Membros com Mais Ausências</h5>
                        <?php foreach($topAbsentMembers as $m): ?>
                        <div class="list-item">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $m['avatar_color'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px;">
                                    <?= strtoupper(substr($m['name'], 0, 2)) ?>
                                </div>
                                <span style="font-weight: 600; font-size: 13px;"><?= $m['name'] ?></span>
                            </div>
                            <div style="font-weight: 700; color: #f59e0b;"><?= $m['qtd'] ?>x</div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Motivos Mais Comuns -->
                    <?php if(!empty($topAbsenceReasons)): ?>
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Motivos Mais Comuns</h5>
                        <canvas id="chartAbsenceReasons" style="max-height: 200px;"></canvas>
                    </div>
                    <?php endif; ?>

                    <!-- Membros que Mais Substituem -->
                    <?php if(!empty($topSubstitutes)): ?>
                    <div>
                        <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">🦸 Membros que Mais Substituem</h5>
                        <?php foreach($topSubstitutes as $s): ?>
                        <div class="list-item">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $s['avatar_color'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px;">
                                    <?= strtoupper(substr($s['name'], 0, 2)) ?>
                                </div>
                                <span style="font-weight: 600; font-size: 13px;"><?= $s['name'] ?></span>
                            </div>
                            <div style="font-weight: 700; color: #10b981;"><?= $s['vezes'] ?>x</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- SEÇÃO 5: ANÁLISES CRUZADAS & MVPs (NOVA) -->
        <div class="stat-card" style="grid-column: 1 / -1;">
            <div class="stat-title" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleSection('mvp')">
                <span>🏆 ANÁLISES CRUZADAS & MEMBROS MVP</span>
                <i data-lucide="chevron-down" id="icon-mvp" style="width: 20px;"></i>
            </div>
            <div id="section-mvp">
                <!-- Pódio MVP -->
                <div style="margin-top: 20px;">
                    <h5 style="font-size: 13px; margin: 0 0 20px 0; color: #64748b; text-align: center;">🥇 Top 5 Membros MVP (Maior Engajamento)</h5>
                    <div style="display: flex; justify-content: center; align-items: flex-end; gap: 12px; margin-bottom: 30px;">
                        <?php foreach($mvpMembers as $idx => $mvp): 
                            $heights = [180, 220, 160, 140, 120];
                            $colors = ['#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e'];
                            $medals = ['🥇', '🥈', '🥉', '4º', '5º'];
                        ?>
                        <div style="text-align: center;">
                            <div style="width: 100px; height: <?= $heights[$idx] ?>px; background: linear-gradient(180deg, <?= $colors[$idx] ?> 0%, <?= $colors[$idx] ?>99 100%); border-radius: 8px 8px 0 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 12px; color: white; position: relative;">
                                <div style="font-size: 28px; margin-bottom: 4px;"><?= $medals[$idx] ?></div>
                                <div style="font-size: 24px; font-weight: 800; margin-bottom: 4px;"><?= $mvp['engagement_score'] ?></div>
                                <div style="font-size: 10px; opacity: 0.9;">pontos</div>
                            </div>
                            <div style="margin-top: 8px; font-weight: 700; font-size: 12px; color: #0f172a;"><?= $mvp['name'] ?></div>
                            <div style="font-size: 10px; color: #64748b;"><?= $mvp['instrument'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabela de Engajamento Completa -->
                <div style="margin-top: 30px;">
                    <h5 style="font-size: 13px; margin: 0 0 10px 0; color: #64748b;">Score de Engajamento (Todos os Membros)</h5>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 10px; text-align: left; font-weight: 700; color: #64748b;">Membro</th>
                                    <th style="padding: 10px; text-align: center; font-weight: 700; color: #64748b;">Escalas</th>
                                    <th style="padding: 10px; text-align: center; font-weight: 700; color: #64748b;">Capítulos</th>
                                    <th style="padding: 10px; text-align: center; font-weight: 700; color: #64748b;">Ausências</th>
                                    <th style="padding: 10px; text-align: center; font-weight: 700; color: #64748b;">Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($engagementData as $member): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $member['avatar_color'] ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 11px;">
                                                <?= strtoupper(substr($member['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?= $member['name'] ?></div>
                                                <div style="font-size: 10px; color: #94a3b8;"><?= $member['instrument'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 10px; text-align: center; font-weight: 700; color: #3b82f6;"><?= $member['escalas_confirmadas'] ?></td>
                                    <td style="padding: 10px; text-align: center; font-weight: 700; color: #8b5cf6;"><?= $member['capitulos_lidos'] ?></td>
                                    <td style="padding: 10px; text-align: center; font-weight: 700; color: <?= $member['ausencias'] > 0 ? '#f59e0b' : '#10b981' ?>;"><?= $member['ausencias'] ?></td>
                                    <td style="padding: 10px; text-align: center;">
                                        <div style="font-size: 18px; font-weight: 800; color: <?= $member['engagement_score'] >= 70 ? '#10b981' : ($member['engagement_score'] >= 40 ? '#f59e0b' : '#ef4444') ?>;">
                                            <?= $member['engagement_score'] ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Músicas Pendentes -->
<div id="modalMissingSongs" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 12px; padding: 20px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
            <h3 id="modalTitle" style="margin: 0; font-size: 16px; color: #0f172a;">Músicas Pendentes</h3>
            <button onclick="document.getElementById('modalMissingSongs').style.display='none'" style="background: none; border: none; cursor: pointer; color: #64748b;">
                <i data-lucide="x" style="width: 20px;"></i>
            </button>
        </div>
        <div id="modalContent" style="overflow-y: auto; flex: 1;">
            <!-- Lista será injetada aqui -->
        </div>
        <div style="margin-top: 15px; text-align: right;">
            <button onclick="document.getElementById('modalMissingSongs').style.display='none'" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; color: #475569;">Fechar</button>
        </div>
    </div>
</div>

<?php
// Pre-fetch missing songs data for the modal
$missingData = [];
$types = ['cifra' => 'link_cifra', 'letra' => 'link_letra', 'áudio' => 'link_audio', 'vídeo' => 'link_video'];

foreach ($types as $label => $col) {
    $stmt = $pdo->prepare("SELECT title, artist FROM songs WHERE ($col IS NULL OR $col = '') ORDER BY title ASC");
    $stmt->execute();
    $missingData[$label] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<script>
    const missingSongsData = <?= json_encode($missingData) ?>;

    function showMissingSongs(label, type) {
        const modal = document.getElementById('modalMissingSongs');
        const title = document.getElementById('modalTitle');
        const content = document.getElementById('modalContent');
        
        // Normalize type key
        const key = label.toLowerCase();
        
        title.innerText = `Músicas sem ${label}`;
        content.innerHTML = '';

        if (missingSongsData[key] && missingSongsData[key].length > 0) {
            const list = document.createElement('div');
            list.style.display = 'grid';
            list.style.gap = '8px';
            
            missingSongsData[key].forEach(song => {
                const item = document.createElement('div');
                item.style.padding = '8px';
                item.style.borderBottom = '1px solid #f1f5f9';
                item.innerHTML = `
                    <div style="font-weight: 600; font-size: 13px; color: #334155;">${song.title}</div>
                    <div style="font-size: 11px; color: #94a3b8;">${song.artist}</div>
                `;
                list.appendChild(item);
            });
            content.appendChild(list);
        } else {
            content.innerHTML = '<div style="text-align: center; color: #64748b; padding: 20px;">Nenhuma pendência encontrada! 🎉</div>';
        }

        modal.style.display = 'flex';
        lucide.createIcons();
    }
</script>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    lucide.createIcons();

    // Toggle Sections
    function toggleSection(id) {
        const section = document.getElementById('section-' + id);
        const icon = document.getElementById('icon-' + id);
        if(section.style.display === 'none') {
            section.style.display = 'block';
            icon.setAttribute('data-lucide', 'chevron-down');
        } else {
            section.style.display = 'none';
            icon.setAttribute('data-lucide', 'chevron-right');
        }
        lucide.createIcons();
    }

    // Chart: Membros Mais Escalados
    <?php if(!empty($memberScaleCount)): ?>
    new Chart(document.getElementById('chartMemberScales'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column(array_slice($memberScaleCount, 0, 8), 'name')) ?>,
            datasets: [{
                label: 'Escalas',
                data: <?= json_encode(array_column(array_slice($memberScaleCount, 0, 8), 'qtd')) ?>,
                backgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>

    // Chart: Tendência Temporal
    <?php if(!empty($scaleTrend)): ?>
    new Chart(document.getElementById('chartScaleTrend'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($scaleTrend, 'month')) ?>,
            datasets: [{
                label: 'Escalas por Mês',
                data: <?= json_encode(array_column($scaleTrend, 'qtd')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: '#3b82f620',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>

    // Chart: Rotação de Músicas
    <?php if(!empty($songRotation)): ?>
    new Chart(document.getElementById('chartSongRotation'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($songRotation, 'faixa')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($songRotation, 'musicas')) ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ec4899']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    <?php endif; ?>

    // Chart: Comparação de Planos
    <?php if(!empty($planComparison)): ?>
    new Chart(document.getElementById('chartPlanComparison'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($planComparison, 'plano')) ?>,
            datasets: [{
                label: 'Capítulos Lidos',
                data: <?= json_encode(array_column($planComparison, 'capitulos')) ?>,
                backgroundColor: '#8b5cf6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>

    // Chart: Dias da Semana
    <?php if(!empty($weekdayReading)): ?>
    new Chart(document.getElementById('chartWeekdayReading'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($weekdayReading, 'dia_semana')) ?>,
            datasets: [{
                label: 'Leituras',
                data: <?= json_encode(array_column($weekdayReading, 'qtd')) ?>,
                backgroundColor: '#8b5cf6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
    <?php endif; ?>

    // Chart: Motivos de Ausência
    <?php if(!empty($topAbsenceReasons)): ?>
    new Chart(document.getElementById('chartAbsenceReasons'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($topAbsenceReasons, 'reason')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($topAbsenceReasons, 'qtd')) ?>,
                backgroundColor: ['#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    <?php endif; ?>
</script>

<?php renderAppFooter(); ?>

