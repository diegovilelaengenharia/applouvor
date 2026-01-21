<?php
// admin/configuracoes.php
require_once '../includes/auth.php';
require_once '../includes/layout.php';

renderAppHeader('Configurações');

renderPageHeader('Configurações', 'Gerencie sua conta e o aplicativo');
?>

<div style="max-width: 800px; margin: 0 auto; padding: 0 16px;">

    <!-- Seção de Conta -->
    <h3 style="font-size: 0.85rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; margin-top: 8px;">
        Conta
    </h3>

    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 32px;">
        <a href="perfil.php" class="ripple" style="display: flex; align-items: center; gap: 16px; padding: 16px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">
            <div style="width: 40px; height: 40px; background: #dbf4ff; border-radius: 10px; color: #0284c7; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="user" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Meu Perfil</div>
                <div style="font-size: 0.85rem; color: #64748b;">Alterar foto, senha e dados</div>
            </div>
            <i data-lucide="chevron-right" style="width: 20px; color: #cbd5e1;"></i>
        </a>
    </div>

    <!-- Seção de Sistema -->
    <h3 style="font-size: 0.85rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">
        Sistema
    </h3>

    <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 32px;">
        <!-- Gestão de Tags -->
        <a href="classificacoes.php" class="ripple" style="display: flex; align-items: center; gap: 16px; padding: 16px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">
            <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 10px; color: #166534; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="tags" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Gestor de Tags</div>
                <div style="font-size: 0.85rem; color: #64748b;">Classificações de música e categorias</div>
            </div>
            <i data-lucide="chevron-right" style="width: 20px; color: #cbd5e1;"></i>
        </a>

        <!-- Outras Configurações Futuras -->
        <a href="#" class="ripple" style="display: flex; align-items: center; gap: 16px; padding: 16px; text-decoration: none; opacity: 0.6; cursor: not-allowed;">
            <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; color: #64748b; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="bell-ring" style="width: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #1e293b;">Notificações</div>
                <div style="font-size: 0.85rem; color: #64748b;">Em breve</div>
            </div>
        </a>
    </div>

    <!-- Seção de Sobre -->
    <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 0.85rem;">
        <p>App Louvor PIB Oliveira</p>
        <p>Versão 1.0.2</p>
        <p style="margin-top: 8px;">Desenvolvido com ❤️ por Diego Vilela</p>
    </div>

</div>

<?php renderAppFooter(); ?>