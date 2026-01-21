# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. HARMONIZAR CABEÇALHOS DE SEÇÃO ---
# Padronizar estilo dos títulos de seção (Equipe, Instrumentos, etc)
# Transformar headers com background em títulos limpos
header_style = 'font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 32px 0 16px 0; display: flex; align-items: center; gap: 8px;'

# Ajustar Header Equipe Escalada (remover box, deixar limpo)
pattern_equipe_header = r'<div style="background: var\(--bg-secondary\); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var\(--border-subtle\); box-shadow: 0 2px 8px rgba\(0,0,0,0.05\);">.*?</div>\s*</div>'
# Substituir por versão limpa
new_equipe_header = '''
<div style="display: flex; justify-content: space-between; align-items: flex-end; margin: 32px 0 16px 0; padding: 0 4px;">
    <div>
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="users" style="width: 20px; color: #047857;"></i>
            Equipe Escalada
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 30px;"><?= $totalMembros ?> participantes confirmados</p>
    </div>
    <button onclick="openModal(\'modalMembers\')" class="ripple" style="background: rgba(16, 185, 129, 0.1); color: #047857; border: 1px solid rgba(16, 185, 129, 0.2); padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
        <i data-lucide="plus" style="width: 14px;"></i> Adicionar
    </button>
</div>
'''
# O regex acima é complexo, vamos tentar localizar pelo conteúdo específico
content = re.sub(r'<!-- Header com Contador -->.*?<div style="background: var\(--bg-secondary\).*?Adicionar\s*</button>\s*</div>\s*</div>', new_equipe_header, content, flags=re.DOTALL)


# --- 2. HARMONIZAR HEADER REPERTÓRIO ---
# Fazer o mesmo para Músicas
new_musicas_header = '''
<div style="display: flex; justify-content: space-between; align-items: flex-end; margin: 40px 0 16px 0; padding: 0 4px;">
    <div>
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px;">
            <i data-lucide="music" style="width: 20px; color: #be185d;"></i>
            Repertório
        </h3>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 4px 0 0 30px;"><?= $totalMusicas ?> músicas • ~<?= $duracaoEstimada ?>min</p>
    </div>
    <button onclick="openModal(\'modalSongs\')" class="ripple" style="background: rgba(236, 72, 153, 0.1); color: #be185d; border: 1px solid rgba(236, 72, 153, 0.2); padding: 8px 16px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;">
        <i data-lucide="plus" style="width: 14px;"></i> Adicionar
    </button>
</div>
'''
# Substituir header antigo de músicas (precisa ser preciso para nao quebrar)
content = re.sub(r'<div style="background: var\(--bg-secondary\); border-radius: 16px; padding: 20px; margin-bottom: 20px; border: 1px solid var\(--border-subtle\); box-shadow: 0 2px 8px rgba\(0,0,0,0.05\);">\s*<div style="display: flex; justify-content: space-between;.*?</div>\s*</div>', new_musicas_header, content, flags=re.DOTALL)


# --- 3. HARMONIZAR CARDS DE MEMBROS E MÚSICAS ---
# Suavizar bordas e sombras
# Cards de Membros
style_card_member = 'background: white; border: 1px solid rgba(0,0,0,0.05); border-radius: 16px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: 8px;'
content = re.sub(r'background: var\(--bg-secondary\); border: 1.5px solid var\(--border-subtle\); border-radius: 14px; padding: 14px;.*?box-shadow: 0 1px 4px rgba\(0,0,0,0.05\);', style_card_member, content)

# Cards de Músicas (remover header azul pesado)
# Vamos simplificar o card de música para ficar mais clean
# Remover o background linear gradiente do número da música e usar algo mais clean
content = content.replace('background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);', 'background: #f1f5f9; color: #64748b;')
content = content.replace('background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);', 'background: #f1f5f9; color: #64748b;')


# --- 4. AJUSTE DE CORES GERAIS ---
# Ajustar títulos de subseções (Voz, Instrumentos)
# Remover estilo antigo e padronizar
content = content.replace(
    'font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;', 
    'font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 24px 0 12px 4px; display: flex; align-items: center; gap: 8px;'
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Visual refinado e harmonizado com sucesso!")
