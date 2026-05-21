<?php
// admin/escala_setlist.php — Setlist para impressão e compartilhamento
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';
checkLogin();

$scheduleId = (int)($_GET['id'] ?? 0);
if (!$scheduleId) {
    header('Location: escalas.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM schedules WHERE id = ?");
$stmt->execute([$scheduleId]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    header('Location: escalas.php');
    exit;
}

$stmtSongs = $pdo->prepare("
    SELECT s.title, s.artist, s.tone, s.bpm,
           ss.position,
           COALESCE(r.custom_tone, s.tone) as display_tone
    FROM schedule_songs ss
    JOIN songs s ON ss.song_id = s.id
    LEFT JOIN schedule_roteiro r ON r.schedule_id = ss.schedule_id
                                 AND r.song_id = ss.song_id
                                 AND r.item_type = 'musica'
    WHERE ss.schedule_id = ?
    ORDER BY ss.position ASC
");
$stmtSongs->execute([$scheduleId]);
$songs = $stmtSongs->fetchAll(PDO::FETCH_ASSOC);

$eventDate = new DateTime($schedule['event_date']);
$dateFormatted = $eventDate->format('d/m/Y');
$dayName = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][$eventDate->format('w')];
$eventTime = $schedule['event_time'] ? substr($schedule['event_time'], 0, 5) : '';

renderAppHeader(htmlspecialchars($schedule['event_type']) . ' — Setlist', 'escala_detalhe.php?id=' . $scheduleId);
?>

<main class="max-w-2xl mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-28">
    
    <div class="bg-surface-container-lowest border border-surface-container-highest rounded-2xl p-6 shadow-sm mb-6 text-center">
        <div class="font-label-sm text-[10px] font-bold uppercase tracking-widest text-on-surface-variant mb-2">PIB Oliveira — Ministério de Louvor</div>
        <h1 class="font-headline-md text-headline-md text-on-surface font-bold mb-1"><?= htmlspecialchars($schedule['event_type']) ?></h1>
        <div class="font-body-md text-body-md text-on-surface-variant"><?= $dayName ?>, <?= $dateFormatted ?><?= $eventTime ? ' às ' . $eventTime : '' ?></div>
    </div>

    <?php if (!empty($songs)): ?>
    <div class="flex flex-col gap-3">
        <?php foreach ($songs as $i => $s): ?>
        <div class="bg-surface border border-surface-container-highest rounded-xl p-4 shadow-sm flex items-center gap-4 break-inside-avoid print:border print:border-gray-200 print:shadow-none">
            <div class="font-headline-md text-xl font-bold text-outline-variant min-w-[28px] text-center"><?= $i + 1 ?></div>
            <div class="flex-1">
                <div class="font-body-lg text-body-lg text-on-surface font-bold"><?= htmlspecialchars($s['title']) ?></div>
                <div class="font-body-md text-body-md text-on-surface-variant text-sm mt-0.5"><?= htmlspecialchars($s['artist']) ?></div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <?php if ($s['display_tone']): ?>
                <span class="bg-primary/10 text-primary font-label-sm text-xs px-3 py-1 rounded-full font-bold">
                    <?= htmlspecialchars($s['display_tone']) ?>
                </span>
                <?php endif; ?>
                <?php if ($s['bpm']): ?>
                <span class="font-label-sm text-xs text-on-surface-variant"><?= (int)$s['bpm'] ?> BPM</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-surface-container-low border border-dashed border-outline-variant rounded-xl p-8 text-center mt-4">
        <span class="material-symbols-outlined text-4xl text-secondary mb-4">music_off</span>
        <h4 class="font-headline-md text-headline-md text-on-background mb-2">Sem músicas</h4>
        <p class="font-body-md text-body-md text-secondary">Nenhuma música adicionada a esta escala.</p>
    </div>
    <?php endif; ?>

</main>

<div class="fixed bottom-0 left-0 right-0 bg-surface border-t border-surface-container-highest p-4 z-50 flex gap-4 max-w-2xl mx-auto print:hidden">
    <button class="flex-1 py-3 px-4 bg-primary text-on-primary rounded-full font-label-sm font-bold shadow-md hover:bg-primary-container hover:text-on-primary-container transition-colors transform active:scale-95 text-center flex items-center justify-center gap-2" onclick="window.print()">
        <span class="material-symbols-outlined text-[18px]">print</span>
        Imprimir
    </button>
    <button class="flex-1 py-3 px-4 bg-surface-container border border-surface-container-highest rounded-full font-label-sm font-bold text-on-surface transition-colors hover:bg-surface-container-high text-center flex items-center justify-center gap-2" onclick="shareSetlist(this)">
        <span class="material-symbols-outlined text-[18px]">share</span>
        <span>Compartilhar</span>
    </button>
</div>

<script>
function shareSetlist(btnElement) {
    const url = window.location.href;
    const btnText = btnElement.querySelector('span:not(.material-symbols-outlined)');
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars(addslashes($schedule['event_type'])) ?> — <?= $dateFormatted ?>',
            text: 'Setlist do culto — PIB Oliveira',
            url: url
        }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            btnText.textContent = 'Link copiado!';
            setTimeout(() => { btnText.textContent = 'Compartilhar'; }, 2000);
        });
    }
}
</script>

<style>
@media print {
    body { background: white !important; }
    .max-w-2xl { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .bg-surface-container-lowest { background: white !important; border: none !important; box-shadow: none !important; margin-bottom: 2rem !important; }
    .print\:border { border: 1px solid #e5e7eb !important; }
    .print\:shadow-none { box-shadow: none !important; }
    .print\:hidden { display: none !important; }
    header { display: none !important; }
}
</style>

<?php renderAppFooter(); ?>
