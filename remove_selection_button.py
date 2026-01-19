# Script para remover botao "Selecionar musicas" do menu

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remover botão de seleção múltipla do menu
old_button = '''        <button onclick="toggleSelectionMode()" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px;">
            <i data-lucide="check-square"></i> Selecionar músicas
        </button>
        
'''

content = content.replace(old_button, '')

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Botao 'Selecionar musicas' removido do menu!")
print("Agora o menu tem apenas:")
print("- Filtros avancados")
print("- YouTube")
print("- Importar musicas")
print("- Exportar musicas")
print("- Excluir repertorio")
