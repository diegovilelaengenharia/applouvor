# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. REMOVER CLASSES TAB-CONTENT ---
# Substituir as divs com lógica de tab por divs simples
# Detalhes
content = re.sub(r'<div id="detalhes" class="tab-content.*?">', '<div id="detalhes">', content)
# Equipe
content = re.sub(r'<div id="equipe" class="tab-content.*?">', '<div id="equipe" style="margin-top: 24px;">', content)
# Repertório
content = re.sub(r'<div id="repertorio" class="tab-content.*?">', '<div id="repertorio" style="margin-top: 24px;">', content)


# --- 2. REMOVER CSS DE TABS ---
# O CSS .tab-content { display: none; } está escondendo tudo!
# Vamos remover ou comentar o bloco de CSS de tabs
pattern_css = r'\.tab-content\s*\{\s*display:\s*none;.*?\}'
# Remover todo o bloco de estilo de tabs se possível, ou apenas neutralizar
content = content.replace('.tab-content {', '/* .tab-content {')
content = content.replace('display: none;', '/* display: none; */')
content = content.replace('animation: fadeIn 0.3s ease;', '/* animation: fadeIn 0.3s ease; */ }')


# --- 3. GARANTIR QUE OS CONTEÚDOS ESTEJAM VISÍVEIS ---
# Verificar se não sobrou nenhuma classe tab-content perdida no HTML
content = content.replace('class="tab-content', 'class="section-content') 
# Mudei para section-content só para limpar a classe problemática

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Classes de tabs removidas")
print("✅ CSS hidden removido")
print("✅ Todas as seções devem estar visíveis agora")
