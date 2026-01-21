# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. REDESIGN MODAL MEMBROS ---
old_modal_members = r'(<div id="modalMembers".*?<div class="checkbox-list">.*?</div>\s*<div style="display: flex; gap: 12px; margin-top: 24px;">)'
# Vamos capturar todo o bloco do modal e substituir
# Precisamos mapear o bloco inteiro, isso pode ser difícil com regex simples devido ao aninhamento.
# Vamos tentar substituir o CONTEÚDO do form.

new_modal_members_content = '''
        <div class="sheet-header" style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px; margin-bottom: 16px;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary);">Adicionar à Equipe</div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">Selecione os membros para convocar.</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_members">
            <input type="hidden" name="current_tab" value="equipe">
            
            <!-- Busca Rápida -->
            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-secondary);"></i>
                <input type="text" id="searchMembers" placeholder="Buscar membro..." onkeyup="filterList('list-members', this.value)" style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-secondary); outline: none; font-size: 0.95rem;">
            </div>

            <div class="selection-list" id="list-members" style="max-height: 50vh; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($allUsers as $user):
                    $isAlreadyIn = false;
                    foreach ($currentMembers as $cm) { if ($cm['id'] == $user['id']) $isAlreadyIn = true; }
                    if ($isAlreadyIn) continue;
                    
                    // Cores para avatar no modal (simulado)
                    $initial = strtoupper(substr($user['name'], 0, 1));
                    $colorBg = '#f3f4f6'; $colorTxt = '#6b7280';
                ?>
                    <label class="selection-card" style="cursor: pointer;">
                        <input type="checkbox" name="users[]" value="<?= $user['id'] ?>" style="display: none;" onchange="toggleSelection(this)">
                        <div class="card-content" style="
                            display: flex; align-items: center; gap: 12px; 
                            padding: 12px; 
                            border: 1px solid var(--border-subtle); 
                            border-radius: 12px; 
                            transition: all 0.2s;
                            background: white;
                        ">
                            <!-- Avatar Pequeno -->
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--text-secondary); font-size: 0.9rem;">
                                <?= $initial ?>
                            </div>
                            
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($user['name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($user['instrument'] ?: 'Sem instrumento') ?></div>
                            </div>
                            
                            <div class="check-icon" style="
                                width: 24px; height: 24px; 
                                border-radius: 50%; 
                                border: 2px solid var(--border-subtle); 
                                display: flex; align-items: center; justify-content: center;
                                transition: all 0.2s;
                            ">
                                <i data-lucide="check" style="width: 14px; color: white; opacity: 0;"></i>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
                <button type="button" onclick="closeSheet('modalMembers')" class="ripple" style="flex: 1; justify-content: center; background: transparent; border: 1px solid var(--border-subtle); padding: 14px; border-radius: 12px; color: var(--text-primary); font-weight: 600;">Cancelar</button>
                <button type="submit" class="ripple" style="flex: 1; justify-content: center; background: #047857; border: none; padding: 14px; border-radius: 12px; color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.2);">Adicionar</button>
            </div>
        </form>
'''

# Substituir conteúdo do modalMembers
# Padrão: div id="modalMembers" ... <div class="bottom-sheet-content"> ... form ... </div>
# Vamos usar regex para pegar o conteúdo de dentro da .bottom-sheet-content do modalMembers
content = re.sub(
    r'(<div id="modalMembers".*?<div class="bottom-sheet-content">).*?(</div>\s*</div>\s*<!-- MODAL DE MÚSICAS -->)', 
    r'\1' + new_modal_members_content + r'\2', 
    content, 
    flags=re.DOTALL
)


# --- 2. REDESIGN MODAL MÚSICAS ---
new_modal_songs_content = '''
        <div class="sheet-header" style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 16px; margin-bottom: 16px;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary);">Adicionar Músicas</div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 4px;">Escolha o repertório do culto.</p>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="add_songs">
            <input type="hidden" name="current_tab" value="repertorio">
            
            <!-- Busca Rápida -->
            <div style="position: relative; margin-bottom: 16px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: var(--text-secondary);"></i>
                <input type="text" id="searchSongs" placeholder="Buscar música..." onkeyup="filterList('list-songs', this.value)" style="width: 100%; padding: 12px 12px 12px 40px; border-radius: 12px; border: 1px solid var(--border-subtle); background: var(--bg-secondary); outline: none; font-size: 0.95rem;">
            </div>

            <div class="selection-list" id="list-songs" style="max-height: 50vh; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                <?php foreach ($allSongs as $song):
                    $isAlreadyIn = false;
                    foreach ($currentSongs as $cs) { if ($cs['id'] == $song['id']) $isAlreadyIn = true; }
                    if ($isAlreadyIn) continue;
                ?>
                    <label class="selection-card" style="cursor: pointer;">
                        <input type="checkbox" name="songs[]" value="<?= $song['id'] ?>" style="display: none;" onchange="toggleSelection(this)">
                        <div class="card-content" style="
                            display: flex; align-items: center; gap: 12px; 
                            padding: 12px; 
                            border: 1px solid var(--border-subtle); 
                            border-radius: 12px; 
                            transition: all 0.2s;
                            background: white;
                        ">
                            <!-- Ícone Música -->
                            <div style="width: 40px; height: 40px; border-radius: 8px; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: #0284c7;">
                                <i data-lucide="music" style="width: 20px;"></i>
                            </div>
                            
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($song['title']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($song['artist']) ?></div>
                            </div>
                            
                            <div class="check-icon" style="
                                width: 24px; height: 24px; 
                                border-radius: 50%; 
                                border: 2px solid var(--border-subtle); 
                                display: flex; align-items: center; justify-content: center;
                                transition: all 0.2s;
                            ">
                                <i data-lucide="check" style="width: 14px; color: white; opacity: 0;"></i>
                            </div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-subtle);">
                <button type="button" onclick="closeSheet('modalSongs')" class="ripple" style="flex: 1; justify-content: center; background: transparent; border: 1px solid var(--border-subtle); padding: 14px; border-radius: 12px; color: var(--text-primary); font-weight: 600;">Cancelar</button>
                <button type="submit" class="ripple" style="flex: 1; justify-content: center; background: #0284c7; border: none; padding: 14px; border-radius: 12px; color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);">Adicionar</button>
            </div>
        </form>
'''

content = re.sub(
    r'(<div id="modalSongs".*?<div class="bottom-sheet-content">).*?(</div>\s*</div>\s*<!-- Modal de Edição -->)', 
    r'\1' + new_modal_songs_content + r'\2', 
    content, 
    flags=re.DOTALL
)

# --- 3. ADICIONAR SCRIPTS DE INTERATIVIDADE E BUSCA ---
script_selection = '''
<script>
// --- FUNÇÕES DE SELEÇÃO E BUSCA NOS MODAIS ---

function toggleSelection(input) {
    const cardContent = input.nextElementSibling;
    const checkIcon = cardContent.querySelector('.check-icon');
    const lucideIcon = checkIcon.querySelector('i');
    
    if (input.checked) {
        cardContent.style.borderColor = '#047857'; // Verde PIB
        cardContent.style.background = '#ecfdf5';
        checkIcon.style.background = '#047857';
        checkIcon.style.borderColor = '#047857';
        lucideIcon.style.opacity = '1';
    } else {
        cardContent.style.borderColor = 'var(--border-subtle)';
        cardContent.style.background = 'white';
        checkIcon.style.background = 'transparent';
        checkIcon.style.borderColor = 'var(--border-subtle)';
        lucideIcon.style.opacity = '0';
    }
}

function filterList(listId, query) {
    const list = document.getElementById(listId);
    const items = list.getElementsByTagName('label');
    const filter = query.toLowerCase();
    
    for (let i = 0; i < items.length; i++) {
        const text = items[i].innerText.toLowerCase();
        if (text.indexOf(filter) > -1) {
            items[i].style.display = "";
        } else {
            items[i].style.display = "none";
        }
    }
}
</script>
'''

# Inserir scripts antes do fechamento do footer
content = content.replace('<?php renderAppFooter(); ?>', script_selection + '\n<?php renderAppFooter(); ?>')


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Modais de adição modernizados com design de cards, busca e feedback visual")
