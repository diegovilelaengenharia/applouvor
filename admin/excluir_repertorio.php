<?php
// admin/excluir_repertorio.php
// Excluir todas as músicas do repertório

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Excluir todas as músicas
    $pdo->exec("DELETE FROM songs");

    // Redirecionar
    header('Location: repertorio.php?deleted=all');
    exit;
}

require_once '../includes/layout.php';
renderAppHeader('Excluir Repertório');
?>

<style>
    .warning-box {
        background: rgba(239, 68, 68, 0.1);
        border: 2px solid var(--status-error);
        border-radius: 12px;
        padding: 24px;
        margin: 24px 0;
        text-align: center;
    }

    .warning-icon {
        font-size: 4rem;
        margin-bottom: 16px;
    }
</style>

<div style="max-width: 500px; margin: 0 auto; padding: 20px;">
    <div class="warning-box">
        <div class="warning-icon">⚠️</div>
        <h2 style="color: var(--status-error); margin-bottom: 16px;">ATENÇÃO!</h2>
        <p style="font-size: 1.1rem; margin-bottom: 24px;">
            Você está prestes a excluir <strong>TODAS as músicas</strong> do repertório.
        </p>
        <p style="color: var(--text-secondary); margin-bottom: 24px;">
            Esta ação é <strong>irreversível</strong> e não pode ser desfeita!
        </p>
    </div>

    <form method="POST">
        <input type="hidden" name="confirm" value="1">

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <a href="repertorio.php" class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;">
                Cancelar
            </a>
            <button type="submit" class="btn-action-delete ripple" style="flex: 1; justify-content: center;">
                Confirmar Exclusão
            </button>
        </div>
    </form>
</div>

<?php renderAppFooter(); ?>