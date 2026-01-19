# Script para remover botao YouTube do menu

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remover botão YouTube do menu
old_youtube = '''        <button onclick="alert('YouTube Playlist em desenvolvimento')" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="play"></i> YouTube
        </button>
        
'''

content = content.replace(old_youtube, '')

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Botao YouTube removido do menu!")
print("\nMenu final com 4 opcoes:")
print("1. Filtros avancados")
print("2. Importar musicas")
print("3. Exportar musicas")
print("4. Excluir repertorio")
