# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- PREENCHER FUNDO DOS BOTÕES ---

# Botão Editar (Amarelo Ouro Sólido)
# Antigo: background: white; border: 1px solid #fbbf24; color: #d97706;
# Novo: background: #fbbf24; border: none; color: #78350f;
content = content.replace(
    'background: white;\n            border: 1px solid #fbbf24;\n            color: #d97706;',
    'background: #fbbf24;\n            border: none;\n            color: #78350f;'
)
# Ajustar hover do editar
content = content.replace("onmouseover=\"this.style.background='#fffbeb'\"", "onmouseover=\"this.style.background='#f59e0b'\"") # Escurece um pouco
content = content.replace("onmouseout=\"this.style.background='white'\"", "onmouseout=\"this.style.background='#fbbf24'\"")


# Botão Excluir (Vermelho Sólido)
# Antigo: background: white; border: 1px solid #fecaca; color: #dc2626;
# Novo: background: #fee2e2; border: none; color: #991b1b; (Vermelho claro sólido)
# OU Vermelho Forte: background: #ef4444; color: white;
# Vamos de Vermelho Claro sólido para não "gritar" demais, ou Vermelho com texto branco. 
# O usuário pediu "cores no fundo". Vermelho com branco é mais clássico para delete.

content = content.replace(
    'background: white;\n                border: 1px solid #fecaca;\n                color: #dc2626;',
    'background: #ef4444;\n                border: none;\n                color: white;'
)
# Ajustar hover do excluir
content = content.replace("onmouseover=\"this.style.background='#fef2f2'\"", "onmouseover=\"this.style.background='#dc2626'\"") # Escurece
content = content.replace("onmouseout=\"this.style.background='white'\"", "onmouseout=\"this.style.background='#ef4444'\"")


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botões agora têm fundo sólido (Amarelo e Vermelho)")
