<?php
// admin/aniversarios.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Aniversários');

// Render optimized hero header
renderHeroHeader('Aniversários', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 60px 20px;">
        <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i data-lucide="cake" style="color: #D97706; width: 48px; height: 48px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 12px; font-size: 1.25rem; font-weight: 700;">Aniversariantes do Mês</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Celebre os aniversários dos membros do ministério de louvor.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 16px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Birthday Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 16px; align-items: center;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        🎂
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-size: 1rem; font-weight: 600; color: var(--text-primary);">Nome do Membro</h4>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">
                            <i data-lucide="calendar" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                            25 de Janeiro
                        </p>
                    </div>
                    <div style="background: var(--warning-light); color: var(--warning-dark); padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700;">
                        HOJE
                    </div>
                </div>
            </div>

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