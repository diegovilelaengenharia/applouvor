<?php
// admin/lider.php
// Painel de Liderança (Separado do Index)

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// Apenas admins podem acessar
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

renderAppHeader('Painel Líder');
?>

<div class="container" style="padding-top: 24px; max-width: 800px; margin: 0 auto;">

    <!-- Cabeçalho -->
    <div style="margin-bottom: 32px; display: flex; align-items: center; gap: 16px;">
        <a href="configuracoes.php" class="ripple" style="
            width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; 
            display: flex; align-items: center; justify-content: center; color: #64748b;
        ">
            <i data-lucide="arrow-left" style="width: 20px;"></i>
        </a>
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0;">Painel do Líder</h1>
            <p style="color: #64748b; margin-top: 4px;">Ferramentas de gestão avançada</p>
        </div>
    </div>

    <!-- Lista de Ferramentas -->
    <div style="display: grid; gap: 16px;">

        <!-- Gestão de Membros -->
        <a href="membros.php" class="ripple" style="
            display: flex; align-items: center; gap: 16px; 
            background: white; padding: 20px; border-radius: 16px; 
            border: 1px solid #e2e8f0; text-decoration: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">

            <div style="
                width: 48px; height: 48px; border-radius: 12px; 
                background: #eff6ff; color: #3b82f6; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="users" style="width: 24px;"></i>
            </div>

            <div style="flex: 1;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 700;">Gestão de Membros</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Adicionar, editar e gerenciar equipe</p>
            </div>

            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
        </a>

        <!-- Agenda Geral -->
        <a href="escalas.php" class="ripple" style="
            display: flex; align-items: center; gap: 16px; 
            background: white; padding: 20px; border-radius: 16px; 
            border: 1px solid #e2e8f0; text-decoration: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#8b5cf6'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">

            <div style="
                width: 48px; height: 48px; border-radius: 12px; 
                background: #f5f3ff; color: #8b5cf6; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="calendar-days" style="width: 24px;"></i>
            </div>

            <div style="flex: 1;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 700;">Agenda de Escalas</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Visualizar e organizar escalas do mês</p>
            </div>

            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
        </a>

        <!-- Gestão de Avisos -->
        <a href="avisos.php" class="ripple" style="
            display: flex; align-items: center; gap: 16px; 
            background: white; padding: 20px; border-radius: 16px; 
            border: 1px solid #e2e8f0; text-decoration: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#f59e0b'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">

            <div style="
                width: 48px; height: 48px; border-radius: 12px; 
                background: #fffbeb; color: #f59e0b; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="bell-ring" style="width: 24px;"></i>
            </div>

            <div style="flex: 1;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 700;">Mural de Avisos</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Comunicados importantes para o time</p>
            </div>

            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
        </a>

        <!-- Boletim Estatístico (Repertório) -->
        <a href="#" onclick="alert('Funcionalidade sendo implementada!')" class="ripple" style="
            display: flex; align-items: center; gap: 16px; 
            background: white; padding: 20px; border-radius: 16px; 
            border: 1px solid #e2e8f0; text-decoration: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.2s; opacity: 0.8;
        " onmouseover="this.style.borderColor='#10b981'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">

            <div style="
                width: 48px; height: 48px; border-radius: 12px; 
                background: #ecfdf5; color: #10b981; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="bar-chart-2" style="width: 24px;"></i>
            </div>

            <div style="flex: 1;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 700;">Boletim Estatístico</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Métricas de repertório e escalas</p>
            </div>

            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
        </a>

        <!-- Monitoramento de Usuários (Novo) -->
        <a href="monitoramento_usuarios.php" class="ripple" style="
            display: flex; align-items: center; gap: 16px; 
            background: white; padding: 20px; border-radius: 16px; 
            border: 1px solid #e2e8f0; text-decoration: none; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: all 0.2s;
        " onmouseover="this.style.borderColor='#3b82f6'; this.style.transform='translateY(-2px)'"
            onmouseout="this.style.borderColor='#e2e8f0'; this.style.transform='translateY(0)'">

            <div style="
                width: 48px; height: 48px; border-radius: 12px; 
                background: #eff6ff; color: #3b82f6; 
                display: flex; align-items: center; justify-content: center;
            ">
                <i data-lucide="activity" style="width: 24px;"></i>
            </div>

            <div style="flex: 1;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.05rem; font-weight: 700;">Monitoramento de Acessos</h3>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 0.9rem;">Logins, atividade e usuários online</p>
            </div>

            <i data-lucide="chevron-right" style="color: #cbd5e1;"></i>
        </a>
    </div>

</div>

<?php renderAppFooter(); ?>