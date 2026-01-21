# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. ATUALIZAR QUERY SQL PARA BUSCAR MAIS CAMPOS ---
# Procurar query antiga
old_query = r'SELECT s.id, s.title, s.artist, s.tone, ss.order_index'
new_query = r'SELECT s.id, s.title, s.artist, s.tone, s.bpm, s.link, s.category, ss.order_index'
content = content.replace(old_query, new_query)

# --- 2. ATUALIZAR CARD DA MÚSICA PARA EXIBIR DETALHES ---
# O card está dentro de um foreach
# Vamos procurar o bloco onde exibe artista e tom e adicionar mais coisas
# Padrão para encontrar o bloco de info
pattern_song_info = r'<div style="font-size: 0.85rem; color: var\(--text-secondary\); font-weight: 500;">.*?<?= htmlspecialchars\(\$song\[\'artist\'\]\) \?> • <span style="font-weight: 700; color: #3B82F6;"><?= htmlspecialchars\(\$song\[\'tone\'\]\) \?></span>.*?</div>'

# Novo bloco de conteúdo com BPM, Categoria e Link
new_song_info = '''
<div style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; display: flex; flex-direction: column; gap: 4px;">
    <span><?= htmlspecialchars($song['artist']) ?></span>
    
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 2px;">
        <!-- Tom -->
        <span style="background: rgba(59, 130, 246, 0.1); color: #2563eb; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
            Tom: <?= htmlspecialchars($song['tone'] ?: '-') ?>
        </span>

        <!-- BPM -->
        <?php if (!empty($song['bpm'])): ?>
        <span style="background: rgba(245, 158, 11, 0.1); color: #d97706; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
            <?= $song['bpm'] ?> BPM
        </span>
        <?php endif; ?>

        <!-- Link -->
        <?php if (!empty($song['link'])): ?>
        <a href="<?= htmlspecialchars($song['link']) ?>" target="_blank" class="ripple" style="background: rgba(100, 116, 139, 0.1); color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">
            <i data-lucide="external-link" style="width: 10px;"></i> Link
        </a>
        <?php endif; ?>
    </div>
</div>
'''

# Fazer a substituição (usando regex com flag DOTALL)
content = re.sub(pattern_song_info, new_song_info.strip(), content, flags=re.DOTALL)


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Query SQL atualizada com bpm, link, category")
print("✅ Card de música atualizado com badges de informação")
