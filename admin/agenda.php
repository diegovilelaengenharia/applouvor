<?php
// admin/agenda.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Agenda');

// Render optimized hero header
renderPageHeader('Agenda', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, #E6F4EA 0%, #D4E9E2 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i data-lucide="calendar" style="color: var(--primary-green); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px; font-size: var(--font-h3); font-weight: 700;">Agenda do Ministério</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; font-size: var(--font-body);">
            Visualize e gerencie todos os eventos e compromissos do ministério de louvor.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 12px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Event Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 12px; align-items: center;">
                    <div style="background: var(--primary-50); padding: 8px; border-radius: 10px; text-align: center; min-width: 44px;">
                        <div style="font-size: var(--font-h3); font-weight: 800; color: var(--primary-600); line-height: 1;">20</div>
                        <div style="font-size: var(--font-caption); color: var(--primary-600); font-weight: 600; text-transform: uppercase;">JAN</div>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 2px; font-size: var(--font-body); font-weight: 700; color: var(--text-primary);">Culto Tema Especial</h4>
                        <p style="margin: 0 0 6px; font-size: var(--font-caption); color: var(--text-secondary);">19:00 - Templo Principal</p>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <span style="background: var(--primary-50); color: var(--primary-600); padding: 2px 6px; border-radius: 6px; font-size: var(--font-caption); font-weight: 600;">Culto</span>
                            <span style="background: var(--warning-light); color: var(--warning-dark); padding: 2px 6px; border-radius: 6px; font-size: var(--font-caption); font-weight: 600;">Importante</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coming Soon Note -->
            <div style="background: var(--info-light); border: 1px solid var(--info); border-radius: 10px; padding: 12px; text-align: center;">
                    Funcionalidade em desenvolvimento
                </p>
            </div>
        </div>
    </div>
</div>

<?php renderAppFooter(); ?>