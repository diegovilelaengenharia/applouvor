<?php
require_once '../includes/auth.php';
require_once '../includes/layout.php';

// Check if it's admin or musician to include correct auth check if needed
// For simplicity, just check login
checkLogin();

renderAppHeader('Em Manutenção');
?>

<div class="app-content">
    <div class="container">
        <div class="card-clean fade-in-up" style="text-align: center; padding: 40px 20px; margin-top: 32px;">
            <div style="
                width: 60px; 
                height: 60px; 
                background: var(--bg-secondary); 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                margin: 0 auto 20px;
                color: var(--primary-color);">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </div>

            <h2 style="font-size: 1.25rem; margin-bottom: 8px; color: var(--text-primary);">Página em Construção</h2>

            <p style="color: var(--text-secondary); line-height: 1.5; max-width: 400px; margin: 0 auto 24px; font-size: 0.9rem;">
                Estamos trabalhando com carinho nesta funcionalidade. <br>
                Em breve estará disponível para facilitar ainda mais o seu ministério! 🚀
            </p>

            <button onclick="history.back()" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; font-size: 0.9rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5m7 7-7-7 7-7" />
                </svg>
                Voltar
            </button>
        </div>
    </div>
</div>

<?php
renderAppFooter();
?>