<?php
$flashError = $_SESSION['flash']['error'] ?? $error ?? null;
$flashSuccess = $_SESSION['flash']['success'] ?? $success ?? null;

// Limpa da sessão para não mostrar novamente (flash)
if (isset($_SESSION['flash'])) {
    unset($_SESSION['flash']);
}
?>

<?php if ($flashError): ?>
<div class="bg-red-50 text-red-700 p-4 rounded-xl flex items-start gap-3 mb-6 border border-red-200/50 text-sm">
    <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">error</span>
    <span class="font-semibold leading-tight"><?= htmlspecialchars($flashError) ?></span>
</div>
<?php endif; ?>

<?php if ($flashSuccess): ?>
<div class="bg-green-50 text-green-700 p-4 rounded-xl flex items-start gap-3 mb-6 border border-green-200/50 text-sm">
    <span class="material-symbols-outlined text-[20px] shrink-0 mt-0.5">check_circle</span>
    <span class="font-semibold leading-tight"><?= htmlspecialchars($flashSuccess) ?></span>
</div>
<?php endif; ?>
