<?php
// admin/escala_setlist.php — Setlist para impressão e compartilhamento
require_once '../includes/db.php';
require_once '../includes/layout.php';
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
<style>
.setlist-page { max-width: 600px; margin: 0 auto; padding: 16px 16px 80px; }

.setlist-header { text-align: center; margin-bottom: 24px; padding: 20px; background: var(--color-surface, #fff); border-radius: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.setlist-church { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--color-text-muted, #6b7280); margin-bottom: 6px; }
.setlist-title { font-size: 1.4rem; font-weight: 800; color: var(--color-text, #111827); margin: 0 0 4px; }
.setlist-date { font-size: .9rem; color: var(--color-text-secondary, #374151); }

.setlist-songs { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.setlist-song { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--color-surface, #fff); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.song-num { font-size: .8rem; font-weight: 800; color: var(--color-text-muted, #9ca3af); min-width: 24px; text-align: center; }
.song-info { flex: 1; }
.song-title { font-size: .95rem; font-weight: 700; color: var(--color-text, #111827); }
.song-artist { font-size: .8rem; color: var(--color-text-muted, #6b7280); margin-top: 2px; }
.song-meta { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.song-tone { background: #eff6ff; color: #1d4ed8; font-size: .75rem; font-weight: 700; padding: 3px 8px; border-radius: 8px; }
.song-bpm { font-size: .72rem; color: #6b7280; }

.setlist-empty { text-align: center; padding: 40px 20px; color: #6b7280; }

.action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #e5e7eb; padding: 12px 16px; padding-bottom: calc(12px + env(safe-area-inset-bottom, 0px)); display: flex; gap: 10px; z-index: 100; }
.btn-print { flex: 1; padding: 14px; background: var(--color-primary, #3b82f6); color: #fff; border: none; border-radius: 10px; font-size: .95rem; font-weight: 700; cursor: pointer; min-height: 48px; }
.btn-share { padding: 14px 20px; border: 1.5px solid #d1d5db; border-radius: 10px; background: #fff; font-size: .875rem; font-weight: 600; color: #374151; cursor: pointer; min-height: 48px; }

@media print {
    .action-bar, header, nav, .app-header, .page-header { display: none !important; }
    .setlist-page { padding: 0; max-width: 100%; }
    .setlist-song { box-shadow: none; border: 1px solid #e5e7eb; break-inside: avoid; }
    body { background: #fff !important; }
}
</style>

<div class="setlist-page">
    <div class="setlist-header">
        <div class="setlist-church">PIB Oliveira — Ministério de Louvor</div>
        <h1 class="setlist-title"><?= htmlspecialchars($schedule['event_type']) ?></h1>
        <div class="setlist-date"><?= $dayName ?>, <?= $dateFormatted ?><?= $eventTime ? ' às ' . $eventTime : '' ?></div>
    </div>

    <?php if (!empty($songs)): ?>
    <div class="setlist-songs">
        <?php foreach ($songs as $i => $s): ?>
        <div class="setlist-song">
            <div class="song-num"><?= $i + 1 ?></div>
            <div class="song-info">
                <div class="song-title"><?= htmlspecialchars($s['title']) ?></div>
                <div class="song-artist"><?= htmlspecialchars($s['artist']) ?></div>
            </div>
            <div class="song-meta">
                <?php if ($s['display_tone']): ?>
                <span class="song-tone"><?= htmlspecialchars($s['display_tone']) ?></span>
                <?php endif; ?>
                <?php if ($s['bpm']): ?>
                <span class="song-bpm"><?= (int)$s['bpm'] ?> BPM</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="setlist-empty">
        <p>Nenhuma música adicionada a esta escala.</p>
    </div>
    <?php endif; ?>
</div>

<div class="action-bar">
    <button class="btn-print" onclick="window.print()">
        Imprimir
    </button>
    <button class="btn-share" onclick="shareSetlist()">
        Compartilhar
    </button>
</div>

<script>
function shareSetlist() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: '<?= htmlspecialchars(addslashes($schedule['event_type'])) ?> — <?= $dateFormatted ?>',
            text: 'Setlist do culto — PIB Oliveira',
            url: url
        }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.querySelector('.btn-share');
            btn.textContent = 'Link copiado!';
            setTimeout(() => { btn.textContent = 'Compartilhar'; }, 2000);
        });
    }
}
</script>
