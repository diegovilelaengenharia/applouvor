<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

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

<div class="container" style="padding-top: 10px; max-width: 1000px;">

    <!-- Header da Seção -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 1.5rem; margin-bottom: 4px;">Equipe de Louvor</h2>
            <p style="color: var(--text-secondary); font-size: 0.9rem;">Gerencie os músicos e vocalistas</p>
        </div>
        <a href="editar_membro.php" class="btn-primary" style="display: flex; align-items: center; gap: 8px;">
            <i data-lucide="plus"></i> Novo Membro
        </a>
    </div>

    <!-- Barra de Ferramentas / Busca -->
    <div style="margin-bottom: 24px;">
        <form method="GET" action="" style="position: relative;">
            <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); width: 18px;"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar por nome ou função..."
                style="width: 100%; padding: 12px 12px 12px 48px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-secondary); color: var(--text-primary); outline: none;">
        </form>
    </div>

    <!-- Tabela Clean -->
    <div class="clean-table-container">
        <table class="clean-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Função Principal</th>
                    <th>Telefone</th>
                    <th>Nível</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <p style="color: var(--text-secondary);">Nenhum membro encontrado.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $initial = strtoupper(substr($u['name'], 0, 1));
                    ?>
                        <tr>
                            <td>
                                <div class="avatar-cell">
                                    <div class="table-avatar"><?= $initial ?></div>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 600;"><?= htmlspecialchars($u['name']) ?></span>
                                        <span style="font-size: 0.8rem; color: var(--text-secondary); display: none;">@<?= strtolower(str_replace(' ', '', $u['name'])) ?></span> <!-- Opcional -->
                                    </div>
                                </div>
                            </td>
                            <td><?= formatCategory($u['category'] ?? 'outros') ?></td>
                            <td style="font-family: monospace; color: var(--text-secondary);"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge-pill admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge-pill user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="editar_membro.php?id=<?= $u['id'] ?>" class="btn-outline">
                                    <i data-lucide="pencil" style="width: 14px;"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php
renderAppFooter();
?>