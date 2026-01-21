# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Vamos inserir o Setlist Resumido logo após a seção de Instrumentos Escalados
# Procurar o fechamento da div de instrumentos
# O bloco de instrumentos termina em </div> (fechamento do flex wrap)
# Vamos procurar pelo título "INSTRUMENTOS ESCALADOS" e o conteúdo seguinte

pattern_instruments = r'(<h3.*?INSTRUMENTOS ESCALADOS.*?</h3>.*?<div.*?style="display: flex; gap: 8px; flex-wrap: wrap;">.*?</div>)'

# Novo bloco de Setlist Rápido
new_setlist_block = '''
\\1

            <!-- SETLIST RESUMIDO -->
            <h3 style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 24px 0 12px 0; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="list-music" style="width: 16px;"></i>
                Setlist
            </h3>
            
            <?php if (empty($currentSongs)): ?>
                <div style="font-size: 0.9rem; color: var(--text-secondary); font-style: italic;">Nenhuma música selecionada.</div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($currentSongs as $index => $song): ?>
                        <div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; color: var(--text-primary);">
                            <span style="font-weight: 800; color: #cbd5e1; width: 20px; text-align: right;"><?= $index + 1 ?>.</span>
                            <span style="font-weight: 600;"><?= htmlspecialchars($song['title']) ?></span>
                            <?php if (!empty($song['tone'])): ?>
                                <span style="font-size: 0.75rem; background: #eff6ff; color: #3b82f6; padding: 2px 6px; border-radius: 4px; font-weight: 700;">
                                    <?= htmlspecialchars($song['tone']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
'''

# Fazer a substituição
content = re.sub(pattern_instruments, new_setlist_block, content, flags=re.DOTALL)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Setlist resumido adicionado aos detalhes da escala")
