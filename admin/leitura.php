<?php
// admin/leitura.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Leitura Bíblica');

// Render optimized hero header
renderPageHeader('Leitura Bíblica', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, #E5E7EB 0%, #D1D5DB 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i data-lucide="book" style="color: var(--primary-gray); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px; font-size: 1.1rem; font-weight: 700;">Plano de Leitura Bíblica</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 0.9rem;">
            Acompanhe o plano de leitura bíblica do ministério e registre seu progresso.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 12px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Reading Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 12px; align-items: start;">
                    <div style="background: var(--success-light); padding: 10px; border-radius: 10px;">
                        <i data-lucide="check-circle" style="color: var(--success); width: 22px; height: 22px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 2px; font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Salmos 23</h4>
                        <p style="margin: 0 0 6px; font-size: 0.8rem; color: var(--text-secondary);">
                            Leitura do dia 20/01/2026
                        </p>
                        <div style="background: var(--gray-100); height: 5px; border-radius: 3px; overflow: hidden;">
                            <div style="background: var(--success); width: 75%; height: 100%;"></div>
                        </div>
                        <p style="margin: 6px 0 0; font-size: 0.7rem; color: var(--text-muted);">75% concluído</p>
                    </div>
                </div>
            </div>

            <!-- Stats Card -->
            <div style="background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%); border-radius: 12px; padding: 16px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; text-align: center;">
                    <div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-600);">15</div>
                        <div style="font-size: 0.7rem; color: var(--primary-600); font-weight: 600;">Dias</div>
                    </div>
                    <div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-600);">45</div>
                        <div style="font-size: 0.7rem; color: var(--primary-600); font-weight: 600;">Capítulos</div>
                    </div>
                    <div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-600);">85%</div>
                        <div style="font-size: 0.7rem; color: var(--primary-600); font-weight: 600;">Progresso</div>
                    </div>
                </div>
            </div>

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