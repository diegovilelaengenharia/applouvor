<?php
// admin/aniversarios.php
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Busca aniversariantes (supondo que exista birth_date ou similar, se não tiver, placeholder)
// Verificar se coluna birth_date existe, senão usar placeholder
try {
    $stmt = $pdo->query("SELECT *, MONTH(birth_date) as mes, DAY(birth_date) as dia FROM users WHERE birth_date IS NOT NULL ORDER BY mes ASC, dia ASC");
    $aniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $aniversariantes = [];
}

renderAppHeader('Aniversariantes');
renderPageHeader('Aniversariantes', 'Membros que celebram a vida este mês');
?>

<div style="text-align: center; margin-top: 24px;">
    <div style="background: #fefce8; width: 64px; height: 64px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <i data-lucide="cake" style="width: 32px; height: 32px; color: #ca8a04;"></i>
    </div>

    <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">Parabéns para Você! 🎂</h2>
    <p style="color: var(--text-muted); max-width: 300px; margin: 0 auto 24px; font-size: 0.9rem;">Aqui está a lista de quem celebra mais um ano de vida em nossa equipe.</p>
</div>

<?php if (empty($aniversariantes)): ?>
    <div style="background: var(--bg-surface); padding: 24px; border-radius: 12px; text-align: center; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
        <p style="color: var(--text-muted); font-size: 0.9rem;">Nenhuma data cadastrada ainda.</p>
        <a href="membros.php" style="color: var(--primary); font-weight: 600; text-decoration: none; display: block; margin-top: 12px; font-size: 0.85rem;">Gerenciar Membros</a>
    </div>
<?php else: ?>
    <div style="display: grid; gap: 8px;">
        <?php foreach ($aniversariantes as $niver): ?>
            <div style="background: var(--bg-surface); padding: 12px; border-radius: 12px; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <div style="background: var(--bg-body); width: 42px; height: 42px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-weight: 800; color: var(--text-main); font-size: 1rem; line-height: 1;"><?= $niver['dia'] ?></span>
                    <span style="font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted);"><?= date('M', mktime(0, 0, 0, $niver['mes'], 10)) ?></span>
                </div>
                <div>
                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;"><?= htmlspecialchars($niver['name']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($niver['instrument']) ?></div>
                </div>
                <i data-lucide="party-popper" style="margin-left: auto; color: #ca8a04; width: 18px;"></i>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php renderAppFooter(); ?>