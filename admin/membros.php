<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

// Processar Adição Rápida (se necessário futuramente)
// ...

// Filtro de Busca
$search = $_GET['q'] ?? '';
$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR category LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY name ASC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Helper de Categorias
function formatCategory($slug)
{
    $map = [
        'voz_feminina' => 'Voz Feminina',
        'voz_masculina' => 'Voz Masculina',
        'violao' => 'Violão',
        'teclado' => 'Teclado',
        'bateria' => 'Bateria',
        'baixo' => 'Baixo',
        'guitarra' => 'Guitarra',
        'outros' => 'Outros'
    ];
    return $map[$slug] ?? ucfirst($slug);
}

renderAppHeader('Membros');
?>

<div class="container" style="padding-top: 20px; padding-bottom: 80px;">

    <!-- Cabeçalho com Busca e Ação -->
    <div style="display:flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="font-size:1.2rem;">Equipe (<?= count($users) ?>)</h2>
            <a href="editar_membro.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">+ Novo</a>
        </div>

        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="q" class="form-input" placeholder="Buscar membro..." value="<?= htmlspecialchars($search) ?>" style="background-color: var(--bg-tertiary); border:none;">
        </form>
    </div>

    <!-- Lista de Membros -->
    <div class="list-group">
        <?php if (empty($users)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                <i data-lucide="users" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                <p>Nenhum membro encontrado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <div class="list-item" style="border:none; border-bottom: 1px solid var(--border-subtle); border-radius: 0; padding: 16px 0; background: transparent;">
                    <a href="editar_membro.php?id=<?= $u['id'] ?>" style="display:flex; align-items:center; gap: 15px; width: 100%; text-decoration:none;">

                        <!-- Avatar -->
                        <div class="user-avatar" style="width: 48px; height: 48px; font-size: 1.1rem; border-radius: 50%; background: var(--bg-tertiary); color: var(--text-primary); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid var(--bg-secondary);">
                            <?php if (!empty($u['avatar'])): ?>
                                <img src="../assets/uploads/<?= htmlspecialchars($u['avatar']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?= strtoupper(substr($u['name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div style="flex: 1;">
                            <div style="display:flex; align-items:center; gap: 8px;">
                                <span style="font-weight: 600; font-size: 1.05rem; color: var(--text-primary);"><?= htmlspecialchars($u['name']) ?></span>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <i data-lucide="shield-check" style="width: 14px; height: 14px; color: var(--accent-blue);"></i>
                                <?php endif; ?>
                            </div>

                            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 2px;">
                                <?= formatCategory($u['category']) ?>
                            </div>
                        </div>

                        <!-- Detalhes do Contato -->
                        <div style="text-align:right;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); opacity: 0.7;">
                                <?= htmlspecialchars($u['phone']) ?>
                            </div>
                            <i data-lucide="chevron-right" style="color: var(--text-muted); width: 18px; margin-top: 5px;"></i>
                        </div>

                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php
renderAppFooter();
?>