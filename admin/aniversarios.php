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
?>

<div style="text-align: center; margin-top: 40px;">
    <div style="background: #fefce8; width: 80px; height: 80px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px; border: 4px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <i data-lucide="cake" style="width: 40px; height: 40px; color: #ca8a04;"></i>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 800; color: #334155; margin-bottom: 8px;">Parabéns para Você! 🎂</h2>
    <p style="color: #64748b; max-width: 300px; margin: 0 auto 32px;">Aqui está a lista de quem celebra mais um ano de vida em nossa equipe.</p>
</div>

<?php if (empty($aniversariantes)): ?>
    <div style="background: white; padding: 24px; border-radius: 16px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
        <p style="color: #94a3b8;">Nenhuma data cadastrada ainda.</p>
        <a href="membros.php" style="color: #047857; font-weight: 600; text-decoration: none; display: block; margin-top: 12px;">Gerenciar Membros</a>
    </div>
<?php else: ?>
    <div style="display: grid; gap: 12px;">
        <?php foreach ($aniversariantes as $niver): ?>
            <div style="background: white; padding: 16px; border-radius: 16px; display: flex; align-items: center; gap: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="background: #f1f5f9; width: 50px; height: 50px; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <span style="font-weight: 800; color: #334155; font-size: 1.1rem; line-height: 1;"><?= $niver['dia'] ?></span>
                    <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b;"><?= date('M', mktime(0, 0, 0, $niver['mes'], 10)) ?></span>
                </div>
                <div>
                    <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($niver['name']) ?></div>
                    <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($niver['instrument']) ?></div>
                </div>
                <i data-lucide="party-popper" style="margin-left: auto; color: #ca8a04; width: 20px;"></i>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php renderAppFooter(); ?>