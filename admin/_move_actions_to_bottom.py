# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. REMOVER BOTÃO DE 3 PONTINHOS DO HEADER ---
# Procurar o botão que inserimos recentemente no _fix_header_layout_v2.py
# Padrão: div wrapper + button com more-vertical + dropdown
pattern_3dots_v2 = r'<div style="position: relative; margin-left: 8px;">\s*<button onclick="toggleMenu\(\)".*?id="actionMenu".*?</div>\s*</div>'

# Tentar remover. Se não achar exatamente, tentar padrão mais solto do botão
content = re.sub(pattern_3dots_v2, '', content, flags=re.DOTALL)
# Fallback: remover qualquer botão com onclick="toggleMenu()"
content = re.sub(r'<button onclick="toggleMenu\(\)".*?</button>', '', content, flags=re.DOTALL)
# Remover o div pai se ficou vazio ou o dropdown se sobrou
content = re.sub(r'<div id="actionMenu".*?</div>', '', content, flags=re.DOTALL)


# --- 2. CRIAR SEÇÃO DE AÇÕES NO FINAL DA PÁGINA ---
# Vamos inserir antes do fechamento principal ou dos modais
# Procurar <!-- MODAIS -->
new_actions_section = '''
<!-- SEÇÃO DE GERENCIAMENTO (FINAL DA PÁGINA) -->
<div style="margin-top: 40px; margin-bottom: 40px; border-top: 1px solid var(--border-subtle); padding-top: 24px;">
    <h3 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="settings" style="width: 14px;"></i> Gerenciamento
    </h3>
    
    <div style="display: flex; flex-direction: column; gap: 12px;">
        <!-- Botão Editar -->
        <button onclick="openEditModal()" class="ripple" style="
            width: 100%;
            background: white;
            border: 1px solid #fbbf24;
            color: #d97706;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(251, 191, 36, 0.1);
        " onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background='white'">
            <i data-lucide="edit-3" style="width: 20px;"></i> Editar Detalhes da Escala
        </button>

        <!-- Botão Excluir -->
        <form method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja excluir esta escala? Esta ação não pode ser desfeita.')" style="margin: 0;">
            <input type="hidden" name="action" value="delete_schedule">
            <button type="submit" class="ripple" style="
                width: 100%;
                background: white;
                border: 1px solid #fecaca;
                color: #dc2626;
                padding: 16px;
                border-radius: 12px;
                font-weight: 700;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: all 0.2s;
            " onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='white'">
                <i data-lucide="trash-2" style="width: 20px;"></i> Excluir Escala
            </button>
        </form>
    </div>
</div>
'''

content = content.replace('<!-- MODAIS -->', new_actions_section + '\n\n<!-- MODAIS -->')

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botão de 3 pontinhos removido do header")
print("✅ Seção de Gerenciamento criada no rodapé")
