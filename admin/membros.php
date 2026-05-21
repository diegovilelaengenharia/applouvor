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

renderAppHeader('Equipe')<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mb-24 space-y-8">
    
    <!-- Hero / Status Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#1A1B1F] to-[#2C2E35] text-white p-8 md:p-10 shadow-xl border border-white/10">
        <!-- Elemento de Fundo Decorativo -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-[#2E7EED]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-[#FFC107]/5 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#2E7EED]/20 border border-[#2E7EED]/30 text-[#2E7EED] text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7EED] animate-pulse"></span>
                    Gestão Ativa
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white mb-2">
                    Nossa <span class="bg-gradient-to-r from-[#2E7EED] to-[#60A5FA] bg-clip-text text-transparent">Equipe</span>
                </h1>
                <p class="text-gray-400 font-body text-sm md:text-base max-w-xl">
                    Administre os voluntários, instrumentistas e vocalistas. Mapeie escalas, controle presenças e organize os ministérios do Worship.
                </p>
            </div>
            
            <div class="flex items-center gap-4 bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-6 self-start md:self-auto">
                <div class="w-12 h-12 rounded-xl bg-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED]">
                    <span class="material-symbols-outlined text-3xl">groups</span>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-white"><?= count($users) ?></div>
                    <div class="text-xs font-bold uppercase tracking-wider text-gray-400">Membros Ativos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros e Barra de Busca Bento Box -->
    <div class="bg-white border border-[#EDEDED] rounded-3xl p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-6 transition-all duration-300">
        <!-- Campo de Busca -->
        <div class="relative flex-1 max-w-xl">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">search</span>
            <input type="text" id="memberSearch" placeholder="Buscar membros por nome ou função..." 
                   class="w-full bg-[#F4F4F5] border border-[#EDEDED] rounded-2xl pl-12 pr-4 py-3.5 font-body text-[#1A1B1F] placeholder-gray-400 focus:outline-none focus:border-[#2E7EED] focus:ring-2 focus:ring-[#2E7EED]/10 transition-all" 
                   onkeyup="filterMembers()">
        </div>

        <!-- Botões de Ordenação -->
        <?php if ($isAdmin): ?>
        <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0 scrollbar-none">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mr-2 whitespace-nowrap">Ordenar por:</span>
            
            <a href="?sort=name" class="px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all <?= $sort === 'name' ? 'bg-[#2E7EED] text-white shadow-md shadow-[#2E7EED]/20' : 'bg-[#F4F4F5] text-gray-600 border border-[#EDEDED] hover:bg-gray-100' ?>">
                Nome
            </a>
            <a href="?sort=taxa" class="px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all <?= $sort === 'taxa' ? 'bg-[#2E7EED] text-white shadow-md shadow-[#2E7EED]/20' : 'bg-[#F4F4F5] text-gray-600 border border-[#EDEDED] hover:bg-gray-100' ?>">
                Taxa de Presença
            </a>
            <a href="?sort=escalas" class="px-5 py-2.5 rounded-full text-xs font-bold whitespace-nowrap transition-all <?= $sort === 'escalas' ? 'bg-[#2E7EED] text-white shadow-md shadow-[#2E7EED]/20' : 'bg-[#F4F4F5] text-gray-600 border border-[#EDEDED] hover:bg-gray-100' ?>">
                Total de Escalas
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Lista de Membros em Grid Bento -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="membersList">
        <?php 
        $delay = 0;
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
            
            // Garantir cores harmônicas sem violar o Purple Ban
            $gradients = [
                ['from-[#2E7EED]', 'to-[#60A5FA]'],
                ['from-[#10B981]', 'to-[#34D399]'],
                ['from-[#F59E0B]', 'to-[#FBBF24]'],
                ['from-[#3B82F6]', 'to-[#10B981]'],
                ['from-[#1F2937]', 'to-[#4B5563]'],
            ];
            $gradIndex = hexdec(substr($hash, 0, 1)) % count($gradients);
            $grad = $gradients[$gradIndex];
        ?>
            <div class="member-card group bg-white border border-[#EDEDED] rounded-3xl p-5 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between relative overflow-hidden animate-fade-in" 
                 style="animation-delay: <?= $delay ?>ms;" 
                 data-name="<?= strtolower($user['name']) ?>" 
                 data-role="<?= strtolower($user['instrument'] ?? '') ?>">
                 
                 <!-- Efeito decorativo de fundo ao passar o mouse -->
                 <div class="absolute -right-8 -top-8 w-24 h-24 bg-gray-50 rounded-full group-hover:bg-[#2E7EED]/5 transition-all duration-300 pointer-events-none"></div>
                 
                 <div>
                     <!-- Cabeçalho do Card (Avatar e Ações) -->
                     <div class="flex items-start justify-between mb-4 relative z-10">
                         <!-- Avatar -->
                         <div class="relative">
                             <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0 overflow-hidden shadow-sm border border-gray-100 relative">
                                 <?php if ($avatarPath): ?>
                                     <img src="<?= htmlspecialchars($avatarPath) ?>" class="w-full h-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                     <div class="hidden absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> items-center justify-center text-white font-bold text-2xl font-display"><?= $initial ?></div>
                                 <?php else: ?>
                                     <div class="absolute inset-0 bg-gradient-to-br <?= $grad[0] ?> <?= $grad[1] ?> flex items-center justify-center text-white font-bold text-2xl font-display"><?= $initial ?></div>
                                 <?php endif; ?>
                             </div>
                             
                             <?php if ($user['role'] === 'admin'): ?>
                                 <span class="absolute -bottom-1.5 -right-1.5 bg-[#FFC107] text-[#1A1B1F] text-[9px] font-extrabold px-2 py-0.5 rounded-full border-2 border-white shadow-sm" title="Administrador">ADM</span>
                             <?php endif; ?>
                         </div>

                         <!-- Ações Rápidas (WhatsApp e Menu de Opções) -->
                         <div class="flex items-center gap-1.5">
                             <?php if (!empty($user['phone'])): ?>
                             <a href="https://wa.me/55<?= preg_replace('/\D/', '', $user['phone']) ?>" target="_blank" 
                                class="w-9 h-9 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center transition-all shadow-sm" 
                                title="Falar no WhatsApp">
                                 <span class="material-symbols-outlined text-[18px]">chat</span>
                             </a>
                             <?php endif; ?>
                             
                             <?php if ($isAdmin): ?>
                                 <div class="relative dropdown-container">
                                     <button onclick="toggleDropdown(event, this)" class="w-9 h-9 bg-gray-50 border border-gray-100 text-gray-500 rounded-xl flex items-center justify-center hover:bg-gray-100 transition-all" title="Mais Opções">
                                         <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                     </button>
                                     
                                     <!-- Menu Dropdown -->
                                     <div class="dropdown-menu absolute right-0 top-full mt-2 w-36 bg-white border border-[#EDEDED] rounded-2xl shadow-xl opacity-0 invisible scale-95 origin-top-right transition-all duration-200 z-30 overflow-hidden">
                                         <a href="perfil.php?id=<?= $user['id'] ?>" class="flex items-center gap-2.5 px-4 py-3 text-gray-700 hover:bg-gray-50 font-medium text-sm transition-colors">
                                             <span class="material-symbols-outlined text-[16px] text-gray-400">edit</span> Editar Perfil
                                         </a>
                                         <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" 
                                                 class="w-full flex items-center gap-2.5 px-4 py-3 text-red-600 hover:bg-red-50 font-medium text-sm text-left border-t border-gray-100 transition-colors">
                                             <span class="material-symbols-outlined text-[16px] text-red-400">delete</span> Excluir
                                         </button>
                                     </div>
                                 </div>
                             <?php else: ?>
                                 <a href="perfil.php?id=<?= $user['id'] ?>" class="w-9 h-9 bg-gray-50 border border-gray-100 text-gray-500 rounded-xl flex items-center justify-center hover:bg-gray-100 transition-all" title="Ver Perfil">
                                     <span class="material-symbols-outlined text-[18px]">person</span>
                                 </a>
                             <?php endif; ?>
                         </div>
                     </div>

                     <!-- Informações do Membro -->
                     <div class="relative z-10">
                         <h3 class="text-base font-bold text-[#1A1B1F] truncate group-hover:text-[#2E7EED] transition-colors duration-200" title="<?= htmlspecialchars($user['name']) ?>">
                             <?= htmlspecialchars($user['name']) ?>
                         </h3>
                         
                         <!-- Contatos/Infos rápidos -->
                         <div class="flex items-center gap-1.5 text-xs text-gray-400 mt-0.5 mb-3">
                             <span class="material-symbols-outlined text-[14px]">smartphone</span>
                             <span class="truncate"><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Sem telefone' ?></span>
                         </div>
                     </div>
                 </div>

                 <!-- Footer do Card (Badges de Funções e Estatísticas de Presença) -->
                 <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between gap-4 relative z-10">
                     <!-- Instrumentos / Funções -->
                     <div class="flex flex-wrap gap-1.5 max-w-[70%]">
                         <?php
                         if (!empty($user['roles'])):
                             $displayRoles = array_slice($user['roles'], 0, 2);
                             foreach ($displayRoles as $role):
                         ?>
                             <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-200 text-gray-600 text-[10px] font-bold">
                                 <i data-lucide="<?= $role['icon'] ?>" class="w-3 h-3 text-[#2E7EED]"></i>
                                 <?= htmlspecialchars($role['name']) ?>
                             </span>
                         <?php endforeach; 
                             if(count($user['roles']) > 2):
                         ?>
                             <span class="inline-flex items-center px-2 py-1 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 text-[10px] font-bold" title="Mais instrumentos">
                                 +<?= count($user['roles']) - 2 ?>
                             </span>
                         <?php 
                             endif;
                         else: ?>
                             <span class="text-[10px] font-semibold text-gray-400 italic">Nenhuma função</span>
                         <?php endif; ?>
                     </div>

                     <!-- Taxa de Presença (Bento Status Ring) -->
                     <?php if ($isAdmin && $user['taxa'] !== null): 
                         $taxa = (int)$user['taxa'];
                         if ($taxa >= 80) { 
                             $ringColor = 'text-emerald-500'; 
                             $bgColor = 'bg-emerald-50'; 
                             $textColor = 'text-emerald-700'; 
                         } elseif ($taxa >= 60) { 
                             $ringColor = 'text-blue-500'; 
                             $bgColor = 'bg-blue-50'; 
                             $textColor = 'text-blue-700'; 
                         } elseif ($taxa >= 40) { 
                             $ringColor = 'text-amber-500'; 
                             $bgColor = 'bg-amber-50'; 
                             $textColor = 'text-amber-700'; 
                         } else { 
                             $ringColor = 'text-rose-500'; 
                             $bgColor = 'bg-rose-50'; 
                             $textColor = 'text-rose-700'; 
                         }
                     ?>
                         <div class="flex items-center gap-2">
                             <!-- Círculo de Progresso SVG -->
                             <div class="relative w-8 h-8 flex items-center justify-center">
                                 <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                     <path class="text-gray-100" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                     <path class="<?= $ringColor ?> transition-all duration-500" stroke-dasharray="<?= $taxa ?>, 100" stroke-width="3.5" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                 </svg>
                                 <span class="absolute text-[9px] font-black text-gray-700"><?= $taxa ?>%</span>
                             </div>
                         </div>
                     <?php endif; ?>
                 </div>
            </div>
        <?php 
            $delay += 40;
        endforeach; ?>
    </div>
</main>

<!-- FAB (Admin Only) -->
<?php if ($isAdmin): ?>
    <a href="perfil.php?new=1" class="fixed bottom-8 right-8 w-14 h-14 bg-[#2E7EED] text-white rounded-2xl flex items-center justify-center shadow-lg shadow-[#2E7EED]/30 hover:bg-[#1a6ad4] hover:scale-105 hover:shadow-xl transition-all duration-200 z-40" title="Adicionar Novo Membro">
        <span class="material-symbols-outlined text-3xl">person_add</span>
    </a>
<?php endif; ?>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 transition-opacity duration-300">
    <!-- Backdrop com Blur -->
    <div class="absolute inset-0 bg-[#1A1B1F]/60 backdrop-blur-md opacity-0 transition-opacity duration-300" id="deleteModalBackdrop" onclick="closeDeleteModal()"></div>
    
    <!-- Conteúdo do Modal -->
    <div class="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-2xl border border-red-50 scale-90 opacity-0 transition-all duration-300 relative z-10" id="deleteModalContent">
        <div class="px-6 py-5 border-b border-red-50 flex justify-between items-center bg-red-50/30">
            <h3 class="font-headline text-lg font-bold text-red-600 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-red-500 animate-pulse">warning</span>
                Excluir Membro
            </h3>
            <button type="button" class="text-gray-400 hover:bg-gray-100 p-1.5 rounded-xl transition-all" onclick="closeDeleteModal()">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="p-6">
            <p class="font-body text-gray-700 mb-3">Tem certeza absoluta que deseja excluir <strong><span id="deleteMemberName" class="text-[#1A1B1F]"></span></strong>?</p>
            <p class="text-xs font-semibold text-red-700 bg-red-50 border border-red-100 p-4 rounded-2xl flex items-start gap-2.5 leading-relaxed">
                <span class="material-symbols-outlined text-[16px] flex-shrink-0 mt-0.5">info</span>
                <span>Esta ação é irreversível. Todas as escalas históricas, estatísticas de presença e permissões associadas a este membro serão permanentemente removidas do sistema.</span>
            </p>
            
            <div class="mt-8 flex gap-3">
                <button type="button" class="flex-1 py-3 px-5 border border-gray-200 rounded-2xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition-all" onclick="closeDeleteModal()">Cancelar</button>
                <form method="POST" id="deleteForm" class="m-0 flex-1">
                    <?= App\AuthMiddleware::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteMemberId">
                    <button type="submit" class="w-full py-3 px-5 bg-red-600 text-white rounded-2xl text-sm font-bold shadow-md shadow-red-200 hover:bg-red-700 transition-all">Sim, Excluir</button>
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

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    opacity: 0;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<?php
renderAppFooter();
?>