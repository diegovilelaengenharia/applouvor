<?php
// admin/indisponibilidade.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Indisponibilidades');

// Render optimized hero header
renderHeroHeader('Indisponibilidades', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 60px 20px;">
        <div style="background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i data-lucide="calendar-x" style="color: #DC2626; width: 48px; height: 48px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 12px; font-size: 1.25rem; font-weight: 700;">Gerenciar Indisponibilidades</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Informe suas datas de indisponibilidade para facilitar o planejamento das escalas.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 16px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Unavailability Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="background: var(--error-light); padding: 12px; border-radius: 12px;">
                        <i data-lucide="calendar-x" style="color: var(--error); width: 24px; height: 24px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-size: 1rem; font-weight: 600; color: var(--text-primary);">Viagem</h4>
                        <p style="margin: 0 0 8px; font-size: 0.875rem; color: var(--text-secondary);">
                            <i data-lucide="calendar" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></i>
                            15/02 a 20/02
                        </p>
                        <p style="margin: 0; font-size: 0.75rem; color: var(--text-muted);">Motivo: Viagem em família</p>
                    </div>
                    <button style="background: transparent; border: none; color: var(--text-secondary); cursor: pointer; padding: 8px;">
                        <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>
            </div>

            <!-- Add Button -->
            <button style="background: var(--primary-500); color: white; border: none; border-radius: 12px; padding: 16px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
                Adicionar Indisponibilidade
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