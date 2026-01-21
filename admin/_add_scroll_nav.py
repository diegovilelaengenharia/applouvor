# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- TORNAR OS CARDS DE RESUMO CLICÁVEIS (SCROLL TO) ---

# 1. Card Equipe (Azul)
# Localizar a div do card e adicionar onclick e cursor pointer
# Procurar pelo texto "EQUIPE" dentro do card azul
pattern_card_equipe = r'(<div style="background: #dbeafe;.*?border: 1px solid #bfdbfe;">)'
replacement_equipe = r'<div onclick="document.getElementById(\'equipe\').scrollIntoView({behavior: \'smooth\'})" style="background: #dbeafe; color: #1e40af; padding: 16px; border-radius: 16px; text-align: center; border: 1px solid #bfdbfe; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">'

# Tentar substituir se o padrão for encontrado (simplificado)
# O regex original pode falhar pelos espaços/quebras de linha. Vamos tentar algo mais genérico ou match por cor.
# Card Equipe tem background: #dbeafe
content = content.replace('background: #dbeafe;', 'cursor: pointer; background: #dbeafe;')
content = re.sub(
    r'(<div[^>]*onclick="[^"]*"[^>]*style="[^"]*background: #dbeafe;[^"]*">)', 
    r'\1', # Se já tem onclick, não faz nada (prevenção)
    content
)
# Se não tem onclick ainda:
if 'onclick="document.getElementById(\'equipe\')' not in content:
    content = re.sub(
        r'(<div style="background: #dbeafe;[^"]*">)',
        r'<div onclick="document.getElementById(\'equipe\').scrollIntoView({behavior: \'smooth\'})" style="background: #dbeafe; cursor: pointer; \1'.replace('style="background: #dbeafe;', ''),
        content
    )


# 2. Card Músicas (Rosa)
# Background: #fce7f3
if 'onclick="document.getElementById(\'repertorio\')' not in content:
    content = re.sub(
        r'(<div style="background: #fce7f3;[^"]*">)',
        r'<div onclick="document.getElementById(\'repertorio\').scrollIntoView({behavior: \'smooth\'})" style="background: #fce7f3; cursor: pointer; \1'.replace('style="background: #fce7f3;', ''),
        content
    )

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Cards de resumo agora rolam até a seção correspondente")
