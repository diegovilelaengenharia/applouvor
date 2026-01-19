# Script Python para adicionar menu de opções no repertorio.php

file_path = r"admin\repertorio.php"

# Ler o arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Menu HTML a ser inserido
menu_html = '''
<!-- Menu de Opções -->
<div id="optionsMenu" class="bottom-sheet-overlay">
    <div class="bottom-sheet-content">
        <div class="sheet-header">Opções do Repertório</div>
        
        <button onclick="alert('Filtros em desenvolvimento')" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="filter"></i> Filtros avançados
        </button>
        
        <button onclick="alert('YouTube Playlist em desenvolvimento')" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="play"></i> YouTube
        </button>
        
        <button onclick="alert('Seleção múltipla em desenvolvimento')" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="check-square"></i> Selecionar músicas
        </button>
        
        <a href="importar_excel_page.php" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px; text-decoration: none;">
            <i data-lucide="upload"></i> Importar músicas
        </a>
        
        <a href="exportar_musicas.php" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px; text-decoration: none;">
            <i data-lucide="download"></i> Exportar músicas
        </a>
        
        <button onclick="confirmDeleteAll()" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; color: var(--status-error); border-color: var(--status-error);">
            <i data-lucide="trash-2"></i> Excluir repertório
        </button>
        
        <button onclick="closeSheet('optionsMenu')" class="btn-primary ripple" style="width: 100%; justify-content: center; margin-top: 16px;">
            Fechar
        </button>
    </div>
</div>

<script>
    function openOptionsMenu() {
        document.getElementById('optionsMenu').classList.add('active');
    }
    
    function confirmDeleteAll() {
        if (confirm('⚠️ ATENÇÃO!\\n\\nDeseja realmente excluir TODAS as músicas do repertório?\\n\\nEsta ação não pode ser desfeita!')) {
            if (confirm('Confirme novamente: Excluir TODO o repertório?')) {
                window.location.href = 'excluir_repertorio.php';
            }
        }
        closeSheet('optionsMenu');
    }
</script>

'''

# Inserir antes do renderAppFooter
content = content.replace('<?php renderAppFooter(); ?>', menu_html + '<?php renderAppFooter(); ?>')

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Menu de opcoes adicionado!")
print("Acesse: http://localhost:8000/admin/repertorio.php")
