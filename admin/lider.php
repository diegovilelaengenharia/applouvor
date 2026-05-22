<?php
// admin/lider.php
require_once '../src/helpers/auth.php';
require_once '../src/config/db.php';
require_once '../src/layout/layout.php';

checkAdmin();

renderAppHeader('Painel do Líder');
?>

<!-- Container Principal com estilo Sacred Minimalist -->
<div class="min-h-screen bg-[#121316] text-[#E2E8F0] px-4 py-8 md:px-8">
    <div class="max-w-7xl mx-auto space-y-12">
        
        <!-- Header de Boas Vindas Moderno -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#1A1B1F] to-[#16171B] border border-neutral-800 p-6 md:p-8 shadow-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#2E7EED]/10 border border-[#2E7EED]/20 text-[#2E7EED] text-xs font-semibold tracking-wide uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#2E7EED] animate-pulse"></span>
                    Painel Administrativo
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight text-white font-sans">
                    Painel do Líder
                </h1>
                <p class="text-neutral-400 max-w-xl text-sm md:text-base">
                    Gerencie a equipe de louvor, configure ausências, aprove sugestões de músicas e organize as escalas litúrgicas em um só lugar.
                </p>
            </div>
            
            <!-- Quick KPI Stats (Total de membros ativos) -->
            <div class="flex items-center gap-4 bg-[#121316] border border-neutral-800/80 rounded-xl p-4 md:self-stretch relative z-10">
                <?php
                $stmtCount = $pdo->query("SELECT COUNT(*) FROM users");
                $totalUsers = $stmtCount->fetchColumn();
                ?>
                <div class="w-12 h-12 rounded-lg bg-[#FFC107]/10 border border-[#FFC107]/20 flex items-center justify-center text-[#FFC107]">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="block text-2xl font-bold text-white"><?= $totalUsers ?></span>
                    <span class="block text-xs text-neutral-400 uppercase tracking-wider font-semibold">Integrantes da Equipe</span>
                </div>
            </div>
            
            <!-- Detalhe decorativo sutil -->
            <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-[#2E7EED]/5 blur-3xl pointer-events-none"></div>
        </div>

        <!-- Seções em Bento Grid Assimétrico -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- SEÇÃO 1: GESTÃO DA EQUIPE (Col-span 7 para maior destaque) -->
            <div class="lg:col-span-7 space-y-4">
                <div class="flex items-center gap-2.5 px-1">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#2E7EED]"></div>
                    <h2 class="text-lg font-bold uppercase tracking-wider text-neutral-300 font-sans">
                        Gestão Operacional
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Card Equipe -->
                    <a href="membros.php" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-lg bg-[#2E7EED]/10 border border-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="users" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs text-neutral-500 font-medium group-hover:text-neutral-400 transition-colors">Visualizar</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-base font-bold text-white group-hover:text-[#2E7EED] transition-colors">Equipe de Louvor</h3>
                            <p class="text-xs text-neutral-400 mt-1">Gerenciar membros, visualizar presenças e funções litúrgicas.</p>
                        </div>
                    </a>

                    <!-- Card Ausências -->
                    <a href="indisponibilidades_equipe.php" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-lg bg-[#F43F5E]/10 border border-[#F43F5E]/20 flex items-center justify-center text-[#F43F5E] group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="calendar-off" class="w-5 h-5"></i>
                            </div>
                            <?php
                            $stmtUnavCount = $pdo->query("SELECT COUNT(*) FROM user_unavailability WHERE end_date >= CURDATE()");
                            $unavCount = $stmtUnavCount->fetchColumn();
                            if ($unavCount > 0):
                            ?>
                                <span class="px-2 py-0.5 rounded-full bg-[#F43F5E]/20 text-[#F43F5E] text-[10px] font-bold"><?= $unavCount ?> ativa(s)</span>
                            <?php else: ?>
                                <span class="text-xs text-neutral-500 font-medium group-hover:text-neutral-400 transition-colors">Visualizar</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-base font-bold text-white group-hover:text-[#F43F5E] transition-colors">Ausências e Faltas</h3>
                            <p class="text-xs text-neutral-400 mt-1">Acompanhar indisponibilidades comunicadas pelos voluntários.</p>
                        </div>
                    </a>

                    <!-- Card Sugestões -->
                    <a href="sugestoes_musicas.php" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98] sm:col-span-2">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-lg bg-[#10B981]/10 border border-[#10B981]/20 flex items-center justify-center text-[#10B981] group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="inbox" class="w-5 h-5"></i>
                            </div>
                            <?php
                            $stmtSugCount = $pdo->query("SELECT COUNT(*) FROM suggestions WHERE status = 'pending'");
                            $sugCount = $stmtSugCount->fetchColumn();
                            if ($sugCount > 0):
                            ?>
                                <span class="px-2.5 py-0.5 rounded-full bg-[#10B981]/20 text-[#10B981] text-xs font-bold animate-pulse"><?= $sugCount ?> pendente(s)</span>
                            <?php else: ?>
                                <span class="text-xs text-neutral-500 font-medium group-hover:text-neutral-400 transition-colors">Limpo</span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-base font-bold text-white group-hover:text-[#10B981] transition-colors">Moderação de Músicas</h3>
                            <p class="text-xs text-neutral-400 mt-1">Revisar, aprovar ou rejeitar novas canções recomendadas pela equipe.</p>
                        </div>
                    </a>

                    <!-- Card Notificações -->
                    <a href="notificacoes.php" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-lg bg-[#FFC107]/10 border border-[#FFC107]/20 flex items-center justify-center text-[#FFC107] group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="bell" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs text-neutral-500 font-medium group-hover:text-neutral-400 transition-colors">Enviar</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-base font-bold text-white group-hover:text-[#FFC107] transition-colors">Mural & Avisos</h3>
                            <p class="text-xs text-neutral-400 mt-1">Enviar comunicados importantes e alertas em massa para a equipe.</p>
                        </div>
                    </a>

                    <!-- Card Tags -->
                    <a href="classificacoes.php" class="group block relative overflow-hidden rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-lg bg-neutral-700/20 border border-neutral-700/40 flex items-center justify-center text-neutral-300 group-hover:scale-110 transition-transform duration-300">
                                <i data-lucide="tags" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs text-neutral-500 font-medium group-hover:text-neutral-400 transition-colors">Configurar</span>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-base font-bold text-white group-hover:text-[#E2E8F0] transition-colors">Tags & Categorias</h3>
                            <p class="text-xs text-neutral-400 mt-1">Configurar tons, andamento, e marcas temáticas do repertório.</p>
                        </div>
                    </a>

                </div>
            </div>

            <!-- SEÇÃO 2: CRIAÇÕES RÁPIDAS (Col-span 5 para equilíbrio visual) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="flex items-center gap-2.5 px-1">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#FFC107]"></div>
                    <h2 class="text-lg font-bold uppercase tracking-wider text-neutral-300 font-sans">
                        Ações & Criações
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 gap-4">
                    
                    <!-- Nova Escala -->
                    <a href="escala_adicionar.php" class="group flex items-center gap-4 rounded-xl bg-[#1A1B1F] border border-neutral-800 p-4 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="w-12 h-12 rounded-lg bg-[#FFC107]/10 border border-[#FFC107]/20 flex items-center justify-center text-[#FFC107] group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                            <i data-lucide="calendar-plus" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white group-hover:text-[#FFC107] transition-colors">Criar Nova Escala</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">Iniciar o Wizard de Agendamento litúrgico.</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    <!-- Cadastrar Música -->
                    <a href="musica_adicionar.php" class="group flex items-center gap-4 rounded-xl bg-[#1A1B1F] border border-neutral-800 p-4 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="w-12 h-12 rounded-lg bg-[#2E7EED]/10 border border-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                            <i data-lucide="music" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white group-hover:text-[#2E7EED] transition-colors">Cadastrar Música</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">Adicionar música nova diretamente no repertório geral.</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    <!-- Novo Comunicado -->
                    <a href="avisos_admin.php" class="group flex items-center gap-4 rounded-xl bg-[#10B981]/10 border border-[#10B981]/20 p-4 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="w-12 h-12 rounded-lg bg-[#10B981]/20 flex items-center justify-center text-[#10B981] group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                            <i data-lucide="megaphone" class="w-6 h-6"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white group-hover:text-[#10B981] transition-colors">Novo Comunicado</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">Publicar devocional, aviso ou roteiro especial.</p>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-1 transition-transform"></i>
                    </a>

                </div>
            </div>

            <!-- SEÇÃO 3: AUDITORIA & METRICAS (Largura total no grid de 12 cols) -->
            <div class="lg:col-span-12 space-y-4">
                <div class="flex items-center gap-2.5 px-1">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#10B981]"></div>
                    <h2 class="text-lg font-bold uppercase tracking-wider text-neutral-300 font-sans">
                        Estatísticas & Auditoria
                    </h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- Atividade da Equipe -->
                    <a href="stats_equipe.php" class="group flex items-center gap-4 rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="w-11 h-11 rounded-lg bg-[#10B981]/10 border border-[#10B981]/20 flex items-center justify-center text-[#10B981] group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white group-hover:text-[#10B981] transition-colors">Engajamento e Presenças</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">Ver estatísticas de faltas, presenças e assiduidade por músico.</p>
                        </div>
                        <i data-lucide="arrow-up-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                    </a>

                    <!-- Relatórios Gerais -->
                    <a href="relatorios_gerais.php" class="group flex items-center gap-4 rounded-xl bg-[#1A1B1F] border border-neutral-800 p-5 hover:border-neutral-700/80 hover:shadow-xl transition-all duration-300 active:scale-[0.98]">
                        <div class="w-11 h-11 rounded-lg bg-[#2E7EED]/10 border border-[#2E7EED]/20 flex items-center justify-center text-[#2E7EED] group-hover:scale-110 transition-transform duration-300 flex-shrink-0">
                            <i data-lucide="pie-chart" class="w-5 h-5"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-bold text-white group-hover:text-[#2E7EED] transition-colors">Indicadores do Ministério</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">Visualizar relatórios consolidados e gráficos de desempenho.</p>
                        </div>
                        <i data-lucide="arrow-up-right" class="w-5 h-5 text-neutral-500 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Inicializa ícones Lucide garantindo que todos os novos ícones sejam renderizados
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<?php renderAppFooter(); ?>