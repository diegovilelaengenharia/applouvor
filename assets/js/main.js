// Main JS - Global Scripts
document.addEventListener('DOMContentLoaded', () => {
    // 1. Dark Mode Logic
    const themeToggleBtn = document.querySelector('.nav-item[onclick="toggleThemeMode()"]');
    const savedTheme = localStorage.getItem('theme');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        updateThemeIcon(true);
    } else {
        updateThemeIcon(false);
    }

    window.toggleThemeMode = function() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        
        // Salvar preferência
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        
        // Atualizar Ícone (se possível)
        updateThemeIcon(isDark);
    }

    function updateThemeIcon(isDark) {
        // Tenta achar o ícone dentro do botão de toggle na sidebar
        if (!themeToggleBtn) return;
        
        const iconContainer = themeToggleBtn.querySelector('i');
        const textContainer = themeToggleBtn.querySelector('span');

        if (iconContainer) {
            // Remove o ícone antigo e cria um novo ou altera atributos se fosse SVG inline, 
            // mas como é lucide, a melhor forma é trocar o atributo data-lucide e rodar createIcons novamente
            // OU apenas trocar o SVG se já renderizado.
            // Simplificação: Troca texto
            if (isDark) {
               // Idealmente trocaríamos o ícone para 'sun', mas o lucide renderiza svg.
               // Vamos assumir que a pagina recarrega ou simplificar manipulando o SVG se necessário.
               // Para MVP: Mudar texto
               if(textContainer) textContainer.innerText = 'Modo Claro';
            } else {
               if(textContainer) textContainer.innerText = 'Modo Escuro';
            }
        }
    }
});
