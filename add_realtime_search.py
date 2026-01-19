# Script para adicionar busca em tempo real (autocomplete)

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Adicionar JavaScript para busca em tempo real
realtime_search_js = '''
<script>
    // Busca em tempo real
    const searchInput = document.querySelector('input[name="search"]');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            // Aguardar 300ms após parar de digitar
            searchTimeout = setTimeout(() => {
                const value = this.value.trim();
                
                if (value.length >= 2) {
                    // Fazer busca automática
                    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'musicas';
                    window.location.href = `?tab=${currentTab}&search=${encodeURIComponent(value)}`;
                } else if (value.length === 0) {
                    // Limpar busca
                    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'musicas';
                    window.location.href = `?tab=${currentTab}`;
                }
            }, 500);
        });
        
        // Remover botão "Buscar" pois agora é automático
        const searchButton = searchInput.parentElement.querySelector('button[type="submit"]');
        if (searchButton) {
            searchButton.style.display = 'none';
        }
    }
</script>
'''

# Inserir antes do </script> final (antes do renderAppFooter)
content = content.replace(
    '</script>\n\n<?php renderAppFooter(); ?>',
    realtime_search_js + '\n</script>\n\n<?php renderAppFooter(); ?>'
)

# Atualizar placeholder para indicar busca automática
content = content.replace(
    'placeholder="🔍 Buscar músicas ou artistas..."',
    'placeholder="🔍 Digite para buscar músicas ou artistas..."'
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Busca em tempo real implementada!")
print("- Digite 2+ caracteres para buscar automaticamente")
print("- Aguarda 500ms apos parar de digitar")
print("- Limpa busca ao apagar tudo")
print("- Botao 'Buscar' removido (nao precisa mais)")
