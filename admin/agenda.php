<?php
// admin/agenda.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Agenda');

// Render optimized hero header
renderHeroHeader('Agenda', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 60px 20px;">
        <div style="background: linear-gradient(135deg, #E6F4EA 0%, #D4E9E2 100%); width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
            <i data-lucide="calendar" style="color: var(--primary-green); width: 48px; height: 48px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 12px; font-size: 1.25rem; font-weight: 700;">Agenda do Ministério</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Visualize e gerencie todos os eventos e compromissos do ministério de louvor.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 16px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Event Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 16px; padding: 20px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="background: var(--primary-50); padding: 12px; border-radius: 12px; text-align: center; min-width: 60px;">
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-600);">20</div>
                        <div style="font-size: 0.75rem; color: var(--primary-600); font-weight: 600;">JAN</div>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-size: 1rem; font-weight: 600; color: var(--text-primary);">Culto Tema Especial</h4>
                        <p style="margin: 0 0 8px; font-size: 0.875rem; color: var(--text-secondary);">19:00 - Templo Principal</p>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <span style="background: var(--primary-50); color: var(--primary-600); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">Culto</span>
                            <span style="background: var(--warning-light); color: var(--warning-dark); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600;">Importante</span>
                        </div>
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