# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\classificacoes.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- ADICIONAR BOTÃO NO TOPO ---
# Procurar o título H1
# <h1 style="font-size: 1.8rem; ...">Gestão de Tags</h1>
# <p ...>Crie pastas ...</p>
# </div>

# Vou injetar o botão dentro da div hero, alinhado ou abaixo.

# Novo Header com Botão
new_header = """
<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 800; color: white;">Gestão de Tags</h1>
        <p style="color: rgba(255,255,255,0.8); margin-top: 4px;">Crie pastas para organizar o repertório.</p>
    </div>
    <button onclick="openModal()" class="ripple" style="
        background: white; 
        color: #047857; 
        border: none; 
        padding: 10px 20px; 
        border-radius: 12px; 
        font-weight: 700; 
        font-size: 0.9rem; 
        display: flex; 
        align-items: center; 
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        cursor: pointer;
    ">
        <i data-lucide="plus" style="width: 18px;"></i> Nova
    </button>
</div>
"""

# Regex para substituir o bloco antigo
pattern_header = r'<div style="margin-bottom: 24px;">\s*<h1.*?</h1>\s*<p.*?</p>\s*</div>'

if re.search(pattern_header, content, re.DOTALL):
    content = re.sub(pattern_header, new_header, content, flags=re.DOTALL)
else:
    # Se falhar o regex, tenta string exata (mais arriscado se tiver mudado espaço)
    # Tentar injetar após renderAppHeader se não achar
    pass 

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botão 'Nova' adicionado ao topo de Classificações")
