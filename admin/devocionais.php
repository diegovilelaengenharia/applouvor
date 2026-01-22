<?php
// admin/devocionais.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

renderAppHeader('Devocionais');

// Render optimized hero header
renderPageHeader('Devocionais', 'Louvor PIB Oliveira');
?>

<!-- Main Content -->
<div class="container fade-in-up">
    <!-- Empty State - Ready for Implementation -->
    <div style="text-align: center; padding: 40px 20px;">
        <div style="background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i data-lucide="book-open" style="color: var(--primary-blue); width: 40px; height: 40px;"></i>
        </div>
        <h3 style="color: var(--text-primary); margin-bottom: 8px; font-size: 1.1rem; font-weight: 700;">Devocionais Diários</h3>
        <p style="color: var(--text-secondary); margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 0.9rem;">
            Compartilhe e acompanhe devocionais diários com o ministério de louvor.
        </p>

        <!-- Placeholder Cards -->
        <div style="display: grid; gap: 12px; max-width: 600px; margin: 0 auto; text-align: left;">
            <!-- Example Devotional Card -->
            <div style="background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; gap: 12px; align-items: start;">
                    <div style="background: var(--info-light); padding: 10px; border-radius: 10px; text-align: center; min-width: 50px;">
                        <div style="font-size: 1.2rem; font-weight: 800; color: var(--info-dark);">20</div>
                        <div style="font-size: 0.7rem; color: var(--info-dark); font-weight: 600;">JAN</div>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px; font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Título do Devocional</h4>
                        <p style="margin: 0 0 8px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4;">
                            "Porque Deus amou o mundo de tal maneira..." - João 3:16
                        </p>
                        <div style="display: flex; gap: 8px; align-items: center; font-size: 0.75rem; color: var(--text-muted);">
                            <i data-lucide="user" style="width: 14px; height: 14px;"></i>
                            <span>Pastor João</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <button style="background: var(--primary-500); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; width: 100%;">
                <i data-lucide="plus" style="width: 18px; height: 18px;"></i>
                Compartilhar Devocional
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