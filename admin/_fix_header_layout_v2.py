# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. LOCALIZAR ELEMENTOS ---
# Procurar o container flex do topo (onde tem o botão Voltar e GlobalNav)
# <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; position: relative; z-index: 10;">
pattern_nav_row = r'(<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;.*?>)'

# Procurar o botão de 3 Pontinhos isolado (que está absolute e causando problema)
pattern_3dots_absolute = r'<div style="position: absolute; top: .*?; right: .*?; z-index: 20;">\s*<button onclick="toggleMenu\(\)".*?</div>\s*</div>'
match_3dots = re.search(r'(<button onclick="toggleMenu\(\)".*?id="actionMenu".*?</div>)\s*</div>', content, flags=re.DOTALL)

# --- 2. PREPARAR NOVO LAYOUT ---
if match_3dots:
    btn_3dots_html = match_3dots.group(1)
    
    # Limpar estilos antigos do botão (remover absolute, backdrop, background fixo)
    # Vamos criar um botão novo limpo, aproveitando a lógica onclick e o ícone
    new_btn_3dots = '''
    <div style="position: relative; margin-left: 8px;">
        <button onclick="toggleMenu()" class="ripple" style="
            background: rgba(255,255,255,0.2); 
            border: none; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: all 0.2s;
            color: white;
        ">
            <i data-lucide="more-vertical" style="width: 20px;"></i>
        </button>
        <!-- Dropdown Menu (Ajustado) -->
        <div id="actionMenu" style="display: none; position: absolute; top: 50px; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); min-width: 200px; overflow: hidden; z-index: 1000; border: 1px solid var(--border-subtle); transform-origin: top right; animation: menuIn 0.2s ease;">
            <button onclick="openEditModal(); toggleMenu();" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 20px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: var(--text-primary); font-weight: 600; font-size: 0.95rem; transition: background 0.2s;" onmouseover="this.style.background=\'var(--bg-tertiary)\';" onmouseout="this.style.background=\'transparent\';">
                <i data-lucide="edit-3" style="width: 18px; color: #f59e0b;"></i>
                Editar Escala
            </button>
            <div style="height: 1px; background: var(--border-subtle); margin: 0 16px;"></div>
            <form method="POST" onsubmit="return confirm(\'Excluir esta escala?\')" style="margin: 0;">
                <input type="hidden" name="action" value="delete_schedule">
                <button type="submit" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 20px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: #ef4444; font-weight: 600; font-size: 0.95rem; transition: background 0.2s;" onmouseover="this.style.background=\'#fee2e2\';" onmouseout="this.style.background=\'transparent\';">
                    <i data-lucide="trash-2" style="width: 18px;"></i>
                    Excluir Escala
                </button>
            </form>
        </div>
    </div>
    '''

    # --- 3. INSERIR NO CONTAINER FLEX ---
    # Remover o botão absolute antigo
    content = re.sub(pattern_3dots_absolute, '', content, flags=re.DOTALL)
    
    # Inserir o novo botão dentro do bloco de navegação global
    # O bloco é: <div style="display: flex; align-items: center;"> <?php renderGlobalNavButtons(); ?> </div>
    # Vamos substituir o fechamento dessa div pela injeção do botão
    content = content.replace(
        '<?php renderGlobalNavButtons(); ?>', 
        '<?php renderGlobalNavButtons(); ?>' + '\n' + new_btn_3dots
    )

    # --- 4. MELHORIAS VISUAIS EXTRAS (Detalhe Profissional) ---
    # Adicionar animação simples para o menu
    css_animation = '''
    <style>
    @keyframes menuIn {
        from { opacity: 0; transform: scale(0.95) translateY(-10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    </style>
    '''
    if 'menuIn' not in content:
        content = content.replace('</style>', css_animation + '\n</style>')

    # Salvar
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

    print("✅ Botão de 3 pontinhos integrado perfeitamente ao flexbox (sem colisão)")
    print("✅ Menu dropdown com animação suave")
    print("✅ Header limpo e organizado")
else:
    print("⚠️ Não encontrei o botão de 3 pontinhos para mover. Verifique se o arquivo está correto.")

