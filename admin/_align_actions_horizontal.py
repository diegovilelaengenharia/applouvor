# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- COLOCAR BOTÕES DE AÇÃO LADO A LADO ---
# Procurar o container dos botões na seção de gerenciamento
# Atualmente: <div style="display: flex; flex-direction: column; gap: 12px;">
# Vamos mudar para flex-direction: row (ou remover o column, que é row por padrão)

# Padrão para achar o bloco de botões de gerenciamento
pattern_mgt_buttons = r'(<h3.*?Gerenciamento.*?</h3>\s*)<div style="display: flex; flex-direction: column; gap: 12px;">'
replacement_mgt_buttons = r'\1<div style="display: flex; gap: 12px; flex-wrap: wrap;">' 
# Adicionei flex-wrap para segurança em telas muito pequenas, mas o padrão será row

# Precisamos ajustar a largura dos botões/forms também para preencherem o espaço (flex: 1)
# Botão Editar
content = content.replace(
    '<!-- Botão Editar -->\n        <button onclick="openEditModal()" class="ripple" style="\n            width: 100%;',
    '<!-- Botão Editar -->\n        <button onclick="openEditModal()" class="ripple" style="\n            flex: 1;'
)

# Botão Excluir (Form e Botão Interno)
content = content.replace(
    '<form method="POST" onsubmit="return confirm(\'ATENÇÃO: Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.\')" style="margin: 0;">',
    '<form method="POST" onsubmit="return confirm(\'ATENÇÃO: Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.\')" style="margin: 0; flex: 1;">'
)
content = content.replace(
    '<!-- Botão Excluir -->\n            <input type="hidden" name="action" value="delete_schedule">\n            <button type="submit" class="ripple" style="\n                width: 100%;',
    '<!-- Botão Excluir -->\n            <input type="hidden" name="action" value="delete_schedule">\n            <button type="submit" class="ripple" style="\n                width: 100%;' 
    # Mantenho width 100% no botão DENTRO do form, pois o form já terá flex: 1
)

# Aplicar a mudança no container pai
content = re.sub(pattern_mgt_buttons, replacement_mgt_buttons, content, flags=re.DOTALL)


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botões de Editar e Excluir colocados lado a lado (Flex Row)")
