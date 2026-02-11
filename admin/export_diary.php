<?php
// admin/export_diary.php
require_once '../includes/auth.php';
checkLogin();

$userId = $_SESSION['user_id'];
$format = $_GET['format'] ?? 'pdf';

// Buscar todas as anotações do usuário
$stmt = $pdo->prepare("
    SELECT rp.day_num, rp.note_title, rp.comment, rp.completed_at,
           us.setting_value as plan_type
    FROM reading_progress rp
    LEFT JOIN user_settings us ON us.user_id = rp.user_id AND us.setting_key = 'reading_plan_type'
    WHERE rp.user_id = ? 
    AND (rp.note_title IS NOT NULL OR rp.comment IS NOT NULL)
    ORDER BY rp.day_num ASC
");
$stmt->execute([$userId]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar nome do usuário
$stmtUser = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$userName = $stmtUser->fetchColumn() ?: 'Usuário';

if ($format === 'pdf') {
    // Exportar como PDF usando TCPDF ou FPDF
    require_once '../vendor/autoload.php'; // Se usar Composer
    
    // Criar PDF simples com HTML
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="meu_diario_biblico.pdf"');
    
    // Usar DomPDF ou similar
    $html = generateDiaryHTML($notes, $userName);
    
    // Se não tiver biblioteca, usar HTML to PDF via navegador
    echo "<html><head><meta charset='UTF-8'></head><body>";
    echo "<h1>Meu Diário Bíblico - {$userName}</h1>";
    echo "<p><em>Gerado em: " . date('d/m/Y H:i') . "</em></p>";
    
    foreach ($notes as $note) {
        echo "<div class='note'>";
        echo "<div class='note-day'>Dia {$note['day_num']}</div>";
        if (!empty($note['note_title'])) {
            echo "<div class='note-title'>" . htmlspecialchars($note['note_title']) . "</div>";
        }
        if (!empty($note['comment'])) {
            echo "<div class='note-content'>" . nl2br(htmlspecialchars($note['comment'])) . "</div>";
        }
        echo "</div>";
    }
    
    echo "</body></html>";
    echo "<script>window.print();</script>";
    
} elseif ($format === 'word') {
    // Exportar como Word (DOCX)
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="meu_diario_biblico.docx"');
    header('Cache-Control: max-age=0');
    
    // Criar documento Word simples usando PHPWord ou formato RTF
    // Por simplicidade, vou usar RTF que é compatível com Word
    echo "{\\rtf1\\ansi\\deff0\n";
    echo "{\\fonttbl{\\f0 Arial;}}\n";
    echo "{\\colortbl;\\red44\\green62\\blue80;\\red52\\green152\\blue219;}\n";
    echo "\\f0\\fs28\\b Meu Diário Bíblico - {$userName}\\b0\\fs20\\par\n";
    echo "\\i Gerado em: " . date('d/m/Y H:i') . "\\i0\\par\\par\n";
    
    foreach ($notes as $note) {
        echo "\\par\\cf2\\b Dia {$note['day_num']}\\b0\\cf0\\par\n";
        if (!empty($note['note_title'])) {
            echo "\\b " . rtfEscape($note['note_title']) . "\\b0\\par\n";
        }
        if (!empty($note['comment'])) {
            echo rtfEscape($note['comment']) . "\\par\\par\n";
        }
    }
    
    echo "}";
}

function rtfEscape($text) {
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace("{", "\\{", $text);
    $text = str_replace("}", "\\}", $text);
    $text = str_replace("\n", "\\par\n", $text);
    return $text;
}

function generateDiaryHTML($notes, $userName) {
    // Função auxiliar para gerar HTML
    return "";
}
?>
