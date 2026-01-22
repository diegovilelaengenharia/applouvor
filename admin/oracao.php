<?php
// admin/oracao.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Pedidos de Oração');

// Render optimized hero header
renderPageHeader('Pedidos de Oração', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i data-lucide="heart-handshake" style="color: #D97706; width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px; font-size: 1.1rem; font-weight: 700;">Mural de Oração</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 0.9rem;">
            Compartilhe e interceda pelos pedidos de oração do ministério.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 12px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Prayer Request Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 12px; align-items: start;">
                    <div style="background: var(--warning-light); padding: 10px; border-radius: 10px;">
                        <i data-lucide="heart" style="color: var(--warning-dark); width: 20px; height: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 6px;">
                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Título do Pedido</h4>
                            <span style="background: var(--warning-light); color: var(--warning-dark); padding: 2px 6px; border-radius: 6px; font-size: 0.7rem; font-weight: 600;">Urgente</span>
                        </div>
                        <p style="margin: 0 0 10px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                            Descrição do pedido de oração...
                        </p>
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <div style="display: flex; gap: 4px; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                                <span>Nome do Membro</span>
                            </div>
                            <div style="display: flex; gap: 4px; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                                <i data-lucide="calendar" style="width: 12px; height: 12px;"></i>
                                <span>Há 2 horas</span>
                            </div>
                        </div>
                        <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid var(--border-subtle); display: flex; gap: 8px; align-items: center;">
                            <button style="background: var(--primary-50); color: var(--primary-600); border: none; border-radius: 8px; padding: 6px 10px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="heart" style="width: 12px; height: 12px;"></i>
                                Orar (12)
                            </button>
                            <button style="background: transparent; color: var(--text-secondary); border: none; padding: 6px 10px; font-size: 0.75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="message-circle" style="width: 12px; height: 12px;"></i>
                                Comentar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <button style="background: var(--primary-500); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; width: 100%;">
                <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                Novo Pedido de Oração
            </button>

            <!-- Coming Soon Note -->
            <div style="background: var(--info-light); border: 1px solid var(--info); border-radius: 10px; padding: 12px; text-align: center;">
                <p style="margin: 0; color: var(--info-dark); font-size: 0.8rem; font-weight: 500;">
                    <i data-lucide="info" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                    Funcionalidade em desenvolvimento
                </p>
            </div>
        </div>
    </div>
</div>

<?php renderAppFooter(); ?>