# -*- coding: utf-8 -*-
import re

# Ler arquivo
file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remover navegação de abas
pattern_tabs_nav = r'<!-- Navegação por Abas -->.*?</div>\s*\n'
content = re.sub(pattern_tabs_nav, '', content, flags=re.DOTALL)

# 2. Remover divs de tab-content e classes active
# Remover abertura das divs de tab
content = re.sub(r'<div id="detalhes" class="tab-content[^"]*">', '<div id="detalhes">', content)
content = re.sub(r'<div id="equipe" class="tab-content[^"]*">', '<div id="equipe" style="margin-top: 24px;">', content)
content = re.sub(r'<div id="repertorio" class="tab-content[^"]*">', '<div id="repertorio" style="margin-top: 24px;">', content)

# 3. Remover função openTab do JavaScript (não é mais necessária)
content = re.sub(r'// Funções de tab.*?window\.history\.pushState\(\{\}, \'\', url\);\s*\}', '', content, flags=re.DOTALL)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Abas unidas em pagina unica")
print("OK - Navegacao de abas removida")
print("OK - Tudo visivel de uma vez")
