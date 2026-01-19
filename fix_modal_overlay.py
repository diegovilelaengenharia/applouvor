# Script para corrigir problema de modais sobrepostos

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Melhorar função closeSheet para fechar todos os modais
old_close = '''    function closeSheet(id) {
        document.getElementById(id).classList.remove('active');
    }'''

new_close = '''    function closeSheet(id) {
        const sheet = document.getElementById(id);
        if (sheet) {
            sheet.classList.remove('active');
        }
    }
    
    function closeAllSheets() {
        const sheets = document.querySelectorAll('.bottom-sheet-overlay');
        sheets.forEach(sheet => sheet.classList.remove('active'));
    }'''

content = content.replace(old_close, new_close)

# Adicionar evento de clique no overlay para fechar
overlay_click = '''    // Fechar modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('bottom-sheet-overlay')) {
            closeAllSheets();
        }
    });
    
    // Fechar com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllSheets();
        }
    });'''

# Adicionar antes do </script> final
content = content.replace('</script>\n\n<?php renderAppFooter(); ?>', overlay_click + '\n</script>\n\n<?php renderAppFooter(); ?>')

# Garantir que openFilters fecha o menu primeiro
content = content.replace(
    '''    function openFilters() {
        closeSheet('optionsMenu');
        document.getElementById('filtersModal').classList.add('active');
    }''',
    '''    function openFilters() {
        closeAllSheets();
        setTimeout(() => {
            document.getElementById('filtersModal').classList.add('active');
        }, 100);
    }'''
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Problema de modais sobrepostos corrigido!")
print("- Botao Fechar agora funciona")
print("- Clicar fora do modal fecha")
print("- Tecla ESC fecha modais")
print("- Apenas um modal aberto por vez")
