# Script para adicionar seção de campos customizados no musica_adicionar.php

file_path = r"admin\musica_adicionar.php"

# Ler o arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Texto a ser inserido
custom_fields_section = '''    </div>
    
    <!-- Campos Customizados -->
    <div class="form-section">
        <div class="form-section-title">Campos Adicionais</div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px;">
            Adicione links personalizados como Google Drive, Partitura, Playback, etc.
        </p>
        
        <div id="customFieldsContainer"></div>
        
        <button type="button" onclick="addCustomField()" class="btn-outline ripple" style="width: 100%; justify-content: center; margin-top: 12px;">
            <i data-lucide="plus"></i> Adicionar Campo
        </button>
    </div>
    
    <!-- Tags e Observações -->'''

# Substituir
content = content.replace('    </div>\r\n    \r\n    <!-- Tags e Observações -->', custom_fields_section)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Seção de Campos Adicionais inserida com sucesso!")
print("Acesse: http://localhost:8000/admin/musica_adicionar.php")
