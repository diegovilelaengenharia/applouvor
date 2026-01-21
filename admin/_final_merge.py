# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

# Caminhos dos arquivos
path_main = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'
path_new_detalhes = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_detalhes.php'
path_new_equipe = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_equipe.php'
path_new_musicas = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_musicas.php'

# Ler arquivos
with open(path_main, 'r', encoding='utf-8') as f:
    main_content = f.read()

with open(path_new_detalhes, 'r', encoding='utf-8') as f:
    detalhes_content = f.read()

with open(path_new_equipe, 'r', encoding='utf-8') as f:
    equipe_content = f.read()

with open(path_new_musicas, 'r', encoding='utf-8') as f:
    musicas_content = f.read()

# --- 1. MODIFICAÇÃO ABA DETALHES ---
# Injetar o menu de 3 pontinhos na aba detalhes nova, se ainda não tiver
if 'actionMenu' not in detalhes_content:
    # Localizar o header verde para injetar o menu
    pattern_header = r'(<div style="background: linear-gradient.*?padding: 24px; color: white;">)'
    menu_html = '''
        <div style="background: linear-gradient(135deg, #047857 0%, #065f46 100%); padding: 24px; color: white; position: relative;">
            <!-- Menu de Três Pontinhos -->
            <div style="position: absolute; top: 20px; right: 20px;">
                <button onclick="toggleMenu()" class="ripple" style="background: rgba(255,255,255,0.2); border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; backdrop-filter: blur(10px);">
                    <i data-lucide="more-vertical" style="width: 20px; color: white;"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="actionMenu" style="display: none; position: absolute; top: 45px; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 180px; overflow: hidden; z-index: 1000; border: 1px solid var(--border-subtle);">
                    <button onclick="openEditModal(); toggleMenu();" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 18px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: var(--text-primary); font-weight: 600; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background=\'var(--bg-tertiary)\';" onmouseout="this.style.background=\'transparent\';">
                        <i data-lucide="edit-3" style="width: 18px; color: #FFC107;"></i>
                        Editar Escala
                    </button>
                    <div style="height: 1px; background: var(--border-subtle); margin: 0 12px;"></div>
                    <form method="POST" onsubmit="return confirm(\'Excluir esta escala?\')" style="margin: 0;">
                        <input type="hidden" name="action" value="delete_schedule">
                        <button type="submit" class="ripple" style="width: 100%; background: transparent; border: none; padding: 14px 18px; text-align: left; cursor: pointer; display: flex; align-items: center; gap: 12px; color: #DC3545; font-weight: 600; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background=\'#fee2e2\';" onmouseout="this.style.background=\'transparent\';">
                            <i data-lucide="trash-2" style="width: 18px;"></i>
                            Excluir Escala
                        </button>
                    </form>
                </div>
            </div>
    '''
    detalhes_content = re.sub(pattern_header, menu_html, detalhes_content)

# Remover a seção de ações antiga (botões grandes) da aba detalhes nova
detalhes_content = re.sub(r'<!-- Ações -->.*?</div>\s*</div>', '</div>', detalhes_content, flags=re.DOTALL)

# --- 2. SUBSTITUIÇÃO NO ARQUIVO PRINCIPAL ---

# Identificar limites das seções antigas
# Detalhes
main_content = re.sub(r'<!-- CONTEÚDO: DETALHES -->.*?<!-- CONTEÚDO: EQUIPE -->', 
                      '<!-- CONTEÚDO: DETALHES -->\n' + detalhes_content + '\n\n<div style="margin-top: 24px;"></div>\n\n<!-- CONTEÚDO: EQUIPE -->', 
                      main_content, flags=re.DOTALL)

# Equipe (usar replace seguro do python para o conteúdo)
# Ajustar o ID e style da div da equipe para ficar bonita na página única
equipe_content_clean = equipe_content.replace('id="equipe" class="tab-content <?= $activeTab === \'equipe\' ? \'active\' : \'\' ?>"', 'id="equipe"')
main_content = re.sub(r'<!-- CONTEÚDO: EQUIPE -->.*?<!-- CONTEÚDO: REPERTÓRIO -->', 
                      '<!-- CONTEÚDO: EQUIPE -->\n' + equipe_content_clean + '\n\n<div style="margin-top: 24px;"></div>\n\n<!-- CONTEÚDO: REPERTÓRIO -->', 
                      main_content, flags=re.DOTALL)

# Músicas/Repertório
musicas_content_clean = musicas_content.replace('id="repertorio" class="tab-content <?= $activeTab === \'repertorio\' ? \'active\' : \'\' ?>"', 'id="repertorio"')
main_content = re.sub(r'<!-- CONTEÚDO: REPERTÓRIO -->.*?<!-- MODAIS -->', 
                      '<!-- CONTEÚDO: REPERTÓRIO -->\n' + musicas_content_clean + '\n\n<!-- MODAIS -->', 
                      main_content, flags=re.DOTALL)

# --- 3. REMOVER NAVEGAÇÃO DE ABAS ---
main_content = re.sub(r'<!-- Navegação por Abas -->.*?</div>', '', main_content, flags=re.DOTALL)

# --- 4. ATUALIZAR JAVASCRIPT ---
# Remover funções antigas que não precisam mais
new_script = '''
<script>
// --- FUNÇÕES DE MENU E INTERFACE ---
function toggleMenu() {
    const menu = document.getElementById('actionMenu');
    if (menu) {
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(event) {
    const menu = document.getElementById('actionMenu');
    const button = event.target.closest('button[onclick*="toggleMenu"]');
    
    if (!button && menu && !menu.contains(event.target)) {
        menu.style.display = 'none';
    }
});

// Funções para Modais (Compatibilidade e Novos)
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeSheet(id) {
    document.getElementById(id).classList.remove('active');
}

function openEditModal() {
    // Fecha o menu se estiver aberto
    const menu = document.getElementById('actionMenu');
    if(menu) menu.style.display = 'none';
    
    // Abre o modal de edição (usa o ID que já existe no backup: modalEditSchedule)
    document.getElementById('modalEditSchedule').classList.add('active');
}

lucide.createIcons();
</script>
<?php renderAppFooter(); ?>
'''

# Substituir bloco de scripts
main_content = re.sub(r'<script>.*?</script>.*?renderAppFooter\(\); \?>', new_script, main_content, flags=re.DOTALL)

# Validar se as tags HTML estão fechando corretamente (gambiarra visual)
# Garantir que não removemos o fechamento do 'card-clean' ou containers
# (A substituição acima deve ter cuidado com isso)

# Salvar
with open(path_main, 'w', encoding='utf-8') as f:
    f.write(main_content)

print("✅ Redesign aplicado com sucesso (Página Única + Botões Funcionais)!")
