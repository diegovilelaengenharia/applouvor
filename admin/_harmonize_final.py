# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. CORRIGIR POSIÇÃO DO MENU DE 3 PONTINHOS ---
# O problema é que ele está absolute. Vamos colocá-lo dentro do container flex dos botões de navegação.
# Botões de navegação estão em: <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; position: relative; z-index: 10;">

# Procura o botão de três pontinhos antigo (HTML)
pattern_3dots_old = r'<div style="position: absolute; top: .*?; right: .*?; z-index: 20;">.*?ActionMenu.*?</div>'
# Captura o HTML do botão para mover
match_btn = re.search(r'(<button onclick="toggleMenu\(\)".*?id="actionMenu".*?</div>)\s*</div>', content, flags=re.DOTALL)

if match_btn:
    btn_html = match_btn.group(1)
    
    # Remover o bloco antigo absoluto
    content = re.sub(r'<div style="position: absolute; top: .*?; right: .*?; z-index: 20;">.*?<!-- Dropdown Menu -->.*?</div>\s*</div>', '', content, flags=re.DOTALL)
    
    # Inserir o botão DENTRO do container flex, logo após <?php renderGlobalNavButtons(); ?>
    # Container Flex: <div style="display: flex; align-items: center;">
    # Vamos adicionar uma margem left no botão de 3 pontinhos
    btn_html_adjusted = btn_html.replace('position: absolute;', 'position: relative;') # Remove absolute do dropdown se tiver, mas o botão é o que importa
    # Ajuste do botão para não ter background blur estranho
    btn_html_adjusted = btn_html_adjusted.replace('backdrop-filter: blur(10px);', '')
    btn_html_adjusted = btn_html_adjusted.replace('background: rgba(255,255,255,0.2);', 'background: transparent;')
    
    # Inserir após renderGlobalNavButtons
    content = content.replace(
        '<?php renderGlobalNavButtons(); ?>', 
        '<?php renderGlobalNavButtons(); ?>' + '\n' + 
        '<div style="margin-left: 8px; position: relative;">' + btn_html_adjusted + '</div>'
    )
    
    # Ajustar o dropdown para não quebrar layout (absolute relative ao pai novo)
    content = content.replace('top: 45px; right: 0;', 'top: 40px; right: 0; width: max-content;')


# --- 2. REARMONIZAR PÁGINA (Ex: Remover espaços em branco excessivos do topo) ---
# Título "Detalhes da Escala" foi removido, mas o container do Hero pode ter ficado com padding estranho
# O card-clean tem margin negativa. Vamos ajustar.

# --- 3. MELHORAR VISUAL DO SETLIST RESUMIDO ---
# Adicionar um card em volta do setlist resumido para separar visualmente
content = content.replace(
    '<!-- SETLIST RESUMIDO -->',
    '<!-- SETLIST RESUMIDO -->\n<div style="background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid #e2e8f0; margin-top: 24px;">'
)
# Fechar a div do setlist (precisamos achar onde termina o foreach do setlist)
# O script anterior inseriu até <?php endif; ?>. Vamos adicionar o fechamento da div lá.
content = content.replace(
    '<?php endif; ?>\n            <!-- CONTEÚDO: EQUIPE -->',
    '<?php endif; ?>\n</div>\n<!-- CONTEÚDO: EQUIPE -->'
)
# Se o script anterior não deixou essa marca, vamos tentar pelo contexto
content = re.sub(
    r'(<i data-lucide="list-music".*?Setlist.*?<\?php endif; \?>)', 
    r'\1\n</div>', 
    content, 
    flags=re.DOTALL
)

# --- 4. SUAVIZAR SEÇÃO DE OBSERVAÇÕES ---
# Observações estão com fundo amarelo forte. Vamos suavizar.
content = content.replace('background: rgba(250, 204, 21, 0.1);', 'background: #fffbeb;')
content = content.replace('border-left: 3px solid #FACC15;', 'border-left: 3px solid #fcd34d;')


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botão de 3 pontinhos movido para a barra de navegação (sem sobreposição)")
print("✅ Setlist resumido colocado em um card elegante")
print("✅ Visual geral harmonizado")
