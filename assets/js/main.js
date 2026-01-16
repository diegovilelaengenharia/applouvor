// Theme Toggle Logic
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    const body = document.body;
    
    // Check saved preference
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        // body.classList.add('dark-mode'); // Se quiser iniciar dark se o sistema for dark
        // Pedido do user: "Projeto em cores claras... mas que tambem tenha o botão"
        // Então vamos respeitar o localStorage, mas o default (sem storage) será light (CSS default).
    }

    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        if(themeToggleBtn) themeToggleBtn.innerHTML = '☀️';
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('theme', 'dark');
                themeToggleBtn.innerHTML = '☀️';
            } else {
                localStorage.setItem('theme', 'light');
                themeToggleBtn.innerHTML = '🌙';
            }
        });
    }
});
