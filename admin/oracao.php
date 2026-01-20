<?php
// admin/oracao.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Pedidos de Oração');

// Render optimized hero header
renderHeroHeader('Pedidos de Oração', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 60px 20px;">
        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i data-lucide="heart-handshake" style="color: #D97706; width: 48px; height: 48px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 12px; font-size: 1.25rem; font-weight: 700;">Mural de Oração</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Compartilhe e interceda pelos pedidos de oração do ministério.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 16px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Prayer Request Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="background: var(--warning-light); padding: 12px; border-radius: 12px;">
                        <i data-lucide="heart" style="color: var(--warning-dark); width: 24px; height: 24px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-primary);">Título do Pedido</h4>
                            <span style="background: var(--warning-light); color: var(--warning-dark); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">Urgente</span>
                        </div>
                        <p style="margin: 0 0 12px; font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">
                            Descrição do pedido de oração...
                        </p>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div style="display: flex; gap: 4px; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                <i data-lucide="user" style="width: 14px; height: 14px;"></i>
                                <span>Nome do Membro</span>
                            </div>
                            <div style="display: flex; gap: 4px; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                                <span>Há 2 horas</span>
                            </div>
                        </div>
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-subtle); display: flex; gap: 8px; align-items: center;">
                            <button style="background: var(--primary-50); color: var(--primary-600); border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="heart" style="width: 14px; height: 14px;"></i>
                                Orar (12)
                            </button>
                            <button style="background: transparent; color: var(--text-secondary); border: none; padding: 6px 12px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="message-circle" style="width: 14px; height: 14px;"></i>
                                Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <button style="background: var(--primary-500); color: white; border: none; border-radius: 12px; padding: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
                Novo Pedido de Oração
            </button>

            <!-- Coming Soon Note -->
            <div style="background: var(--info-light); border: 1px solid var(--info); border-radius: 12px; padding: 16px; text-align: center;">
                <p style="margin: 0; color: var(--info-dark); font-size: 0.875rem; font-weight: 500;">
                    <i data-lucide="info" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    Funcionalidade em desenvolvimento
                </p>
            </div>
        </div>
    </div>
</div>

<?php renderAppFooter(); ?>