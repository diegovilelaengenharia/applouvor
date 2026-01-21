# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- PADRONIZAR CABEÇALHO DO REPERTÓRIO ---
# Atualmente tem um botão solto ou uma div estranha.
# Vamos procurar onde começa a div #repertorio
# E substituir o início para incluir o cabeçalho padrão

# Padrão para encontrar o início da seção repertório e o botão feio
# O botão feio deve ter w-full ou ser grande
pattern_repertorio = r'<div id="repertorio".*?>\s*(.*?)(<div style="display: flex; flex-direction: column; gap: 12px;">|<div class="empty-state">)'

# Novo Cabeçalho Padrão (Igual ao da Equipe)
new_header_repertorio = '''
<div id="repertorio" style="margin-top: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 16px;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 800; color: #1f2937; margin: 0; letter-spacing: -0.02em;">Repertório Musical</h3>
            <p style="font-size: 0.85rem; color: #6b7280; margin-top: 2px;">Músicas selecionadas para o culto</p>
        </div>
        <button onclick="openModal('modalSongs')" class="ripple" style="
            background: #ecfdf5; 
            color: #047857; 
            border: 1px solid #d1fae5; 
            padding: 8px 16px; 
            border-radius: 8px; 
            font-weight: 700; 
            font-size: 0.85rem; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            transition: all 0.2s;
        " onmouseover="this.style.background='#d1fae5'" onmouseout="this.style.background='#ecfdf5'">
            <i data-lucide="plus" style="width: 16px;"></i> Adicionar
        </button>
    </div>
'''

# Vamos substituir a abertura da div repertório e qualquer botão solto que esteja antes da lista
# A regex tenta pegar o início e descartar o que tem entre a div e a lista (que é o botão feio)
content = re.sub(
    r'<div id="repertorio"[^>]*>.*?<?php if \(empty\(\$currentSongs\)\): \?>', # Procura até o if empty
    new_header_repertorio + '    <?php if (empty($currentSongs)): ?>',
    content,
    flags=re.DOTALL
)

# Se cair no ELSE (tem músicas), também precisamos remover o botão antigo que fica dentro do else
# O botão antigo no else é: <button onclick="openModal('modalSongs')" class="btn-action-add ripple w-full" ...
content = re.sub(
    r'<button onclick="openModal\(\'modalSongs\'\)" class="btn-action-add ripple w-full".*?</button>', 
    '', # Remove o botão antigo, pois já colocamos no header novo
    content,
    flags=re.DOTALL
)

# Remover também o botão da empty state para não ficar duplicado (opcional, mas o empty state geralmente tem um call to action central)
# Vou deixar o do empty state pois é bom ter um botão central quando está vazio.
# Mas preciso garantir que o header não duplique se estiver vazio.
# O meu replace acima adiciona o header ANTES do if empty.
# Então se estiver vazio, vai mostrar: Header + Empty State (com botão). Isso é OK e visualmente bom.


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Seção 'Repertório' padronizada com botão '+ Adicionar' discreto e alinhado")
