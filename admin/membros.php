<?php
// admin/membros.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkAdmin();

// --- LÓGICA DE POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die('Ação não autorizada. Por favor, recarregue a página e tente novamente.');
    }

    if (isset($_POST['action'])) {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header("HTTP/1.1 403 Forbidden");
            exit("Acesso negado. Apenas administradores podem realizar esta ação.");
        }

        if ($_POST['action'] === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: membros.php");
            exit;
        }
    }
}

$stmtAllRoles = $pdo->query("SELECT * FROM roles ORDER BY category, name");
$allRoles = $stmtAllRoles->fetchAll(PDO::FETCH_ASSOC);

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$sort = $_GET['sort'] ?? 'name';
$orderBy = match($sort) {
    'taxa'    => 'taxa DESC, u.name ASC',
    'escalas' => 'total_escalas DESC, u.name ASC',
    default   => 'u.name ASC',
};

$stmt = $pdo->query("
    SELECT u.*,
           GROUP_CONCAT(
               CONCAT(r.id, ':', r.name, ':', r.icon, ':', r.color, ':', IFNULL(ur.is_primary, 0))
               ORDER BY ur.is_primary DESC, r.name
               SEPARATOR '||'
           ) as roles_data,
           (
               SELECT COUNT(*)
               FROM schedule_users su
               JOIN schedules sch ON sch.id = su.schedule_id
               WHERE su.user_id = u.id AND sch.event_date < CURDATE()
           ) as total_escalas,
           (
               SELECT ROUND(
                   SUM(CASE WHEN su.status IN ('confirmed','pending') THEN 1 ELSE 0 END) * 100.0
                   / NULLIF(COUNT(su.schedule_id), 0)
               )
               FROM schedule_users su
               JOIN schedules sch ON sch.id = su.schedule_id
               WHERE su.user_id = u.id AND sch.event_date < CURDATE()
           ) as taxa
     FROM users u
     LEFT JOIN user_roles ur ON u.id = ur.user_id
     LEFT JOIN roles r ON ur.role_id = r.id
     GROUP BY u.id
     ORDER BY $orderBy
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as &$user) {
    $user['roles'] = [];
    if (!empty($user['roles_data'])) {
        $rolesArray = explode('||', $user['roles_data']);
        foreach ($rolesArray as $roleStr) {
            list($id, $name, $icon, $color, $isPrimary) = explode(':', $roleStr);
            $user['roles'][] = [
                'id' => $id,
                'name' => $name,
                'icon' => $icon,
                'color' => $color,
                'is_primary' => (bool)$isPrimary
            ];
        }
    }
}
unset($user);

renderAppHeader('Equipe');
?>

<style>
    .bento-member-card {
        background: var(--surface-bright, #ffffff);
        border: 1px solid var(--outline-variant, rgba(224, 226, 231, 0.4));
        box-shadow: 0 1px 3px rgba(0,0,0,0.01);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .dark .bento-member-card {
        background: var(--bg-surface, #1A1B1F);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .bento-member-card:hover {
        border-color: var(--worship-blue, #2E7EED);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04);
        transform: translateY(-2px);
    }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<main class="max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop py-8 mb-24 font-hanken">
    
    <!-- Hero / Status Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1A1B1F] to-[#2C2E35] text-white p-8 md:p-10 shadow-xl border border-white/10 mb-8">
        <!-- Elemento de Fundo Decorativo -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#2E7EED]/20 border border-[#2E7EED]/30 text-[#2E7EED] text-[10px] font-extrabold uppercase tracking-wider mb-4">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7EED] animate-pulse"></span>
                    Gestão Ativa
                </span>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white mb-2 leading-tight">
                    Nossa <span class="bg-gradient-to-r from-[#2E7EED] to-[#60A5FA] bg-clip-text text-transparent">Equipe</span>
                </h1>
                <p class="text-gray-400 font-body text-xs md:text-sm max-w-xl leading-relaxed">
                    Administre os voluntários, instrumentistas e vocalistas. Mapeie escalas, controle presenças e organize os ministérios do Worship.
                </p>
            </div>
            
            <div class="flex items-center gap-4 bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 self-start md:self-auto shadow-sm">
                <div class="w-12 h-12 rounded-xl bg-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED]">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-white leading-none"><?= count($users) ?></div>
                    <div class="text-[9px] font-extrabold uppercase tracking-wider text-gray-400 mt-1">Membros Ativos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros e Barra de Busca Bento Box -->
    <div class="bg-white dark:bg-deep-navy border border-outline-variant/30 rounded-2xl p-5 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 transition-all duration-300">
        <!-- Campo de Busca -->
        <div class="relative flex-1 w-full">
            <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none text-secondary">
                <i data-lucide="search" class="w-5 h-5"></i>
            </div>
            <input type="text" id="memberSearch" placeholder="Buscar membros por nome ou função..." 
                   class="w-full bg-ghost-gray/40 border border-outline-variant/20 dark:bg-surface-variant/5 rounded-xl pl-12 pr-4 py-3.5 text-sm focus:outline-none focus:border-worship-blue focus:ring-2 focus:ring-worship-blue/10 dark:text-on-background transition-all placeholder:text-secondary/50" 
                   onkeyup="filterMembers()">
        </div>

        <!-- Botões de Ordenação -->
        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0 hide-scrollbar w-full lg:w-auto">
            <span class="text-[10px] font-extrabold text-secondary uppercase tracking-wider mr-2 whitespace-nowrap">Ordenar:</span>
            
            <a href="?sort=name" class="px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200 active:scale-95 border <?= $sort === 'name' ? 'bg-[#2E7EED] text-white border-transparent shadow-sm shadow-[#2E7EED]/20' : 'bg-ghost-gray/40 text-secondary border-outline-variant/20 hover:bg-ghost-gray' ?>">
                Nome
            </a>
            <a href="?sort=taxa" class="px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200 active:scale-95 border <?= $sort === 'taxa' ? 'bg-[#2E7EED] text-white border-transparent shadow-sm shadow-[#2E7EED]/20' : 'bg-ghost-gray/40 text-secondary border-outline-variant/20 hover:bg-ghost-gray' ?>">
                Presença
            </a>
            <a href="?sort=escalas" class="px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200 active:scale-95 border <?= $sort === 'escalas' ? 'bg-[#2E7EED] text-white border-transparent shadow-sm shadow-[#2E7EED]/20' : 'bg-ghost-gray/40 text-secondary border-outline-variant/20 hover:bg-ghost-gray' ?>">
                Escalas
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Lista de Membros em Grid Bento -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="membersList">
        <?php 
        foreach ($users as $user): 
            $initial = strtoupper(substr($user['name'], 0, 1));
            $avatarPath = !empty($user['avatar']) ? $user['avatar'] : '';
            if (!empty($avatarPath)) {
                if (strpos($avatarPath, 'http') === false && strpos($avatarPath, 'assets') === false && strpos($avatarPath, 'uploads') === false) {
                    $avatarPath = '../uploads/' . $avatarPath;
                } elseif (strpos($avatarPath, 'assets/') === 0) {
                     $avatarPath = '../' . $avatarPath;
                }
            }
            
            // Gerar gradiente estético baseado no nome para os avatares sem foto
            $hash = md5($user['name']);
            $gradients = [
                ['from-worship-blue', 'to-blue-400'],
                ['from-emerald-500', 'to-teal-400'],
                ['from-altar-gold', 'to-amber-400'],
                ['from-[#3B82F6]', 'to-emerald-400'],
                ['from-slate-700', 'to-slate-500'],
            ];
            $gradIndex = hexdec(substr($hash, 0, 1)) % count($gradients);
            $grad = $gradients[$gradIndex];
        ?>
            <div class="member-card bento-member-card rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group active:scale-[0.98] transition-all duration-200" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>">
                 
                 <!-- Efeito decorativo de fundo ao passar o mouse -->
                 <div class="absolute -right-8 -top-8 w-24 h-24 bg-ghost-gray/20 dark:bg-surface-variant/5 rounded-full group-hover:bg-worship-blue/5 transition-all duration-300 pointer-events-none"></div>
                 
                 <div>
                     <!-- Cabeçalho do Card (Avatar e Ações) -->
                     <div class="flex items-start justify-between mb-4 relative z-10">
                         <!-- Avatar -->
                         <div class="relative">
                             <div class="w-14 h-14 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-sm border border-outline-variant/20 relative">
                                 <?php if ($avatarPath): ?>
                                     <img src="<?= htmlspecialchars($avatarPath) ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                     <div class="hidden absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> items-center justify-center text-white font-bold text-xl font-display"><?= $initial ?></div>
                                 <?php else: ?>
                                     <div class="absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> flex items-center justify-center text-white font-bold text-xl font-display"><?= $initial ?></div>
                                 <?php endif; ?>
                             </div>
                             
                             <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                                 <span class="absolute -bottom-1.5 -right-1.5 bg-altar-gold text-[#1A1B1F] text-[9px] font-extrabold px-2.5 py-0.5 rounded-full border border-white dark:border-deep-navy shadow-sm" title="Administrador">ADM</span>
                             <?php endif; ?>
                         </div>

                         <!-- Ações Rápidas (WhatsApp e Menu de Opções) -->
                         <div class="flex items-center gap-1.5">
                             <?php if (!empty($user['phone'])): ?>
                             <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" 
                                class="w-9 h-9 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 rounded-xl flex items-center justify-center transition-all duration-200 hover:bg-emerald-500/20 active:scale-90 shadow-sm" 
                                title="Falar no WhatsApp">
                                 <i data-lucide="message-circle" class="w-4 h-4"></i>
                             </a>
                             <?php endif; ?>
                             
                             <?php if ($isAdmin): ?>
                                 <div class="relative dropdown-container">
                                     <button onclick="toggleDropdown(event, this)" class="w-9 h-9 bg-ghost-gray dark:bg-surface-variant/10 border border-outline-variant/20 text-secondary rounded-xl flex items-center justify-center hover:bg-outline-variant/20 dark:hover:bg-surface-variant/20 transition-all duration-200 active:scale-90" title="Mais Opções">
                                         <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                     </button>
                                     
                                     <!-- Menu Dropdown -->
                                     <div class="dropdown-menu absolute right-0 top-full mt-2 w-40 bg-white dark:bg-deep-navy border border-outline-variant/30 rounded-2xl shadow-xl opacity-0 invisible scale-95 origin-top-right transition-all duration-200 z-30 overflow-hidden">
                                         <a href="perfil.php?id=<?= $user['id'] ?>" class="flex items-center gap-2.5 px-4 py-3.5 text-secondary hover:bg-ghost-gray dark:hover:bg-surface-variant/10 font-bold text-xs transition-colors">
                                             <i data-lucide="edit-2" class="w-3.5 h-3.5 text-worship-blue"></i> <span>Editar Perfil</span>
                                         </a>
                                         <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" 
                                                 class="w-full flex items-center gap-2.5 px-4 py-3.5 text-rose-500 hover:bg-rose-500/10 font-bold text-xs text-left border-t border-outline-variant/10 transition-colors">
                                             <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> <span>Excluir</span>
                                         </button>
                                     </div>
                                 </div>
                             <?php else: ?>
                                 <a href="perfil.php?id=<?= $user['id'] ?>" class="w-9 h-9 bg-ghost-gray dark:bg-surface-variant/10 border border-outline-variant/20 text-secondary rounded-xl flex items-center justify-center hover:bg-outline-variant/20 dark:hover:bg-surface-variant/20 transition-all duration-200 active:scale-90" title="Ver Perfil">
                                     <i data-lucide="user" class="w-4 h-4"></i>
                                 </a>
                             <?php endif; ?>
                         </div>
                     </div>

                     <!-- Informações do Membro -->
                     <div class="relative z-10">
                         <h3 class="text-sm font-extrabold text-on-background truncate group-hover:text-worship-blue transition-colors duration-200" title="<?= htmlspecialchars($user['name']) ?>">
                             <?= htmlspecialchars($user['name']) ?>
                         </h3>
                         
                         <!-- Contatos/Infos rápidos -->
                         <div class="flex items-center gap-1.5 text-[10px] text-secondary mt-1 font-semibold">
                             <i data-lucide="phone" class="w-3 h-3 text-secondary/70"></i>
                             <span class="truncate"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Sem telefone' ?></span>
                         </div>
                     </div>
                 </div>

                 <!-- Footer do Card (Badges de Funções e Estatísticas de Presença) -->
                 <div class="mt-5 pt-4 border-t border-outline-variant/10 flex items-center justify-between gap-4 relative z-10">
                     <!-- Instrumentos / Funções -->
                     <div class="flex flex-wrap gap-1.5 max-w-[70%]">
                         <?php
                         if (!empty($user['roles'])):
                             $displayRoles = array_slice($user['roles'], 0, 2);
                             foreach ($displayRoles as $role):
                         ?>
                             <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-ghost-gray dark:bg-surface-variant/10 border border-outline-variant/20 text-secondary text-[9px] font-bold uppercase tracking-wider">
                                 <i data-lucide="<?= $role['icon'] ?: 'music' ?>" class="w-3 h-3 text-worship-blue shrink-0"></i>
                                 <span><?= htmlspecialchars($role['name']) ?></span>
                             </span>
                         <?php endforeach; 
                             if(count($user['roles']) > 2):
                         ?>
                             <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-ghost-gray dark:bg-surface-variant/10 border border-outline-variant/20 text-secondary text-[9px] font-bold" title="Mais instrumentos">
                                 +<?= count($user['roles']) - 2 ?>
                             </span>
                         <?php 
                             endif;
                         else: ?>
                             <span class="text-[10px] font-semibold text-secondary/60 italic">Sem função</span>
                         <?php endif; ?>
                     </div>

                     <!-- Taxa de Presença (Bento Status Ring) -->
                     <?php if ($isAdmin && $user['taxa'] !== null): 
                         $taxa = (int)$user['taxa'];
                         if ($taxa >= 80) { 
                             $ringColor = 'text-emerald-500'; 
                         } elseif ($taxa >= 60) { 
                             $ringColor = 'text-worship-blue'; 
                         } elseif ($taxa >= 40) { 
                             $ringColor = 'text-altar-gold'; 
                         } else { 
                             $ringColor = 'text-rose-500'; 
                         }
                     ?>
                         <div class="flex items-center gap-2 shrink-0">
                             <!-- Círculo de Progresso SVG -->
                             <div class="relative w-8 h-8 flex items-center justify-center">
                                 <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                     <path class="text-ghost-gray dark:text-surface-variant/10" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                     <path class="<?= $ringColor ?> transition-all duration-500" stroke-dasharray="<?= $taxa ?>, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                 </svg>
                                 <span class="absolute text-[8px] font-extrabold text-on-background"><?= $taxa ?>%</span>
                             </div>
                         </div>
                     <?php endif; ?>
                 </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- FAB (Admin Only) -->
<?php if ($isAdmin): ?>
    <a href="perfil.php?new=1" class="fixed bottom-8 right-8 w-14 h-14 bg-worship-blue text-white rounded-2xl flex items-center justify-center shadow-lg shadow-worship-blue/20 hover:bg-worship-blue/90 hover:scale-105 transition-all duration-200 z-40" title="Adicionar Novo Membro">
        <i data-lucide="user-plus" class="w-6 h-6"></i>
    </a>
<?php endif; ?>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 transition-opacity duration-300">
    <!-- Backdrop com Blur -->
    <div class="absolute inset-0 bg-[#1A1B1F]/60 backdrop-blur-md opacity-0 transition-opacity duration-300" id="deleteModalBackdrop" onclick="closeDeleteModal()"></div>
    
    <!-- Conteúdo do Modal -->
    <div class="bg-white dark:bg-deep-navy w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-outline-variant/20 scale-90 opacity-0 transition-all duration-300 relative z-10" id="deleteModalContent">
        <div class="px-6 py-5 border-b border-outline-variant/10 flex justify-between items-center bg-rose-500/5">
            <h3 class="font-headline text-base font-bold text-rose-500 flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-rose-500 animate-pulse"></i>
                <span>Excluir Membro</span>
            </h3>
            <button type="button" class="text-secondary hover:bg-ghost-gray dark:hover:bg-surface-variant/10 p-1.5 rounded-lg transition-all" onclick="closeDeleteModal()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        
        <div class="p-6">
            <p class="text-xs text-secondary leading-relaxed mb-4">Tem certeza absoluta que deseja excluir o voluntário <strong><span id="deleteMemberName" class="text-on-background"></span></strong>?</p>
            <div class="text-[11px] font-bold text-rose-500 bg-rose-500/5 border border-rose-500/20 p-4 rounded-xl flex items-start gap-2.5 leading-relaxed">
                <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                <span>Esta ação é irreversível. Todas as escalas históricas, estatísticas de presença e permissões associadas a este membro serão permanentemente removidas do sistema.</span>
            </div>
            
            <div class="mt-8 flex gap-3">
                <button type="button" class="flex-1 py-3 px-5 border border-outline-variant/30 rounded-xl text-xs font-bold text-secondary hover:bg-ghost-gray dark:hover:bg-surface-variant/10 transition-all duration-200 active:scale-95" onclick="closeDeleteModal()">Cancelar</button>
                <form method="POST" id="deleteForm" class="m-0 flex-1">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteMemberId">
                    <button type="submit" class="w-full py-3 px-5 bg-rose-500 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-200 dark:shadow-none hover:bg-rose-600 transition-all duration-200 active:scale-95">Sim, Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Filtragem em tempo real
    function filterMembers() {
        const term = document.getElementById('memberSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.member-card');

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const role = card.getAttribute('data-role');
            if (name.includes(term) || role.includes(term)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Dropdowns
    function toggleDropdown(event, button) {
        event.stopPropagation();
        
        // Fechar outros dropdowns abertos
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            if (menu !== button.nextElementSibling) {
                menu.classList.add('opacity-0', 'invisible', 'scale-95');
            }
        });
        
        const menu = button.nextElementSibling;
        const isClosed = menu.classList.contains('invisible');
        
        if (isClosed) {
            menu.classList.remove('opacity-0', 'invisible', 'scale-95');
        } else {
            menu.classList.add('opacity-0', 'invisible', 'scale-95');
        }
    }

    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.add('opacity-0', 'invisible', 'scale-95');
            });
        }
    });

    // Delete Modal
    function confirmDelete(id, name) {
        document.getElementById('deleteMemberId').value = id;
        document.getElementById('deleteMemberName').textContent = name;
        
        const modal = document.getElementById('deleteModal');
        const backdrop = document.getElementById('deleteModalBackdrop');
        const content = document.getElementById('deleteModalContent');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100');
            content.classList.remove('scale-90', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 20);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const backdrop = document.getElementById('deleteModalBackdrop');
        const content = document.getElementById('deleteModalContent');
        
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-90', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
</script>

<?php
renderAppFooter();
?>