# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. TRANSFORMAR GERENCIAMENTO EM CARD ---
# Estilo padronizado dos cards:
card_style = 'background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-subtle); margin-top: 32px;'

# Encontrar a div de gerenciamento. 
# Ela começa com: <div style="margin-top: 48px; margin-bottom: 40px; border-top: 1px solid var(--border-subtle); padding-top: 32px;">
# Vamos substituir essa div container pelo estilo de card.
# Atenção: O border-top e padding-top atuais eram para separar visualmente do fundo. Com card, não precisa.

# Regex para achar a abertura da div de gerenciamento
pattern_mgt = r'<div style="margin-top: 48px; margin-bottom: 40px; border-top: 1px solid var(--border-subtle); padding-top: 32px;">'
replacement_mgt = f'<div style="{card_style} margin-bottom: 40px;">' # Mantendo margin-bottom para não colar no rodapé real

content = re.sub(pattern_mgt, replacement_mgt, content)

# Ajustar o título "GERENCIAMENTO" para ficar mais harmonioso dentro do card
# Atual: <h3 style="font-size: 0.85rem; font-weight: 800; color: #94a3b8; ... margin-bottom: 16px; ...">
# Vou manter, talvez aumentar um pouco a margem bottom ou tirar o lucide se ficar repetitivo, mas acho que ok.
# Vou apenas garantir que o padding do card já resolve o espaçamento.


# --- 2. PADRONIZAR BOTÕES "EMPTY STATE" (Laranja -> Verde) ---
# Botões com classe "btn-action-add" ou estilos hardcoded laranja.
# Em #equipe e #repertorio, os empty states podem estar usando classes antigas ou estilos inline.

# Procurar botão de Adicionar Membros (Empty State)
# Trecho: <button onclick="openModal('modalMembers')" class="btn-action-add ripple">
# A classe btn-action-add pode estar definida globalmente como laranja. 
# Vou forçar estilo inline VERDE para garantir consistência com o tema novo.

style_green_btn = 'background: #047857; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);'

# Substituir classe por estilo inline no botão de Membros
content = content.replace(
    'class="btn-action-add ripple">\n                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membros',
    f'class="ripple" style="{style_green_btn}">\n                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Membros'
)

# Substituir classe por estilo inline no botão de Músicas
content = content.replace(
    'class="btn-action-add ripple">\n                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Música',
    f'class="ripple" style="{style_green_btn}">\n                <i data-lucide="plus" style="width: 18px;"></i> Adicionar Música'
)


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Gerenciamento encapsulado em Card e botões Empty State padronizados (Verde)")
