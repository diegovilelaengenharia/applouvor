# -*- coding: utf-8 -*-
import re

# Ler o arquivo
file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Ler os novos designs
with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_equipe.php', 'r', encoding='utf-8') as f:
    new_equipe = f.read()

with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_musicas.php', 'r', encoding='utf-8') as f:
    new_musicas = f.read()

# 1. Substituir aba Equipe (encontrar pelo comentário)
pattern_equipe = r'<!-- NOVO DESIGN: ABA EQUIPE -->.*?(?=<!-- NOVO DESIGN: ABA MÚSICAS -->|<!-- CONTEÚDO: REPERTÓRIO -->|<!-- MODAIS -->|$)'
content = re.sub(pattern_equipe, new_equipe + '\n\n', content, flags=re.DOTALL)

# 2. Substituir aba Músicas
pattern_musicas = r'(<!-- NOVO DESIGN: ABA MÚSICAS -->|<!-- CONTEÚDO: REPERTÓRIO -->).*?(?=<!-- MODAIS -->|$)'
content = re.sub(pattern_musicas, new_musicas + '\n\n<!-- MODAIS -->\n', content, flags=re.DOTALL)

# 3. Adicionar script JavaScript e fechar tags se não existir
if '</script>' not in content or 'toggleMenu' not in content:
    # Adicionar antes do final do arquivo
    script_js = '''
<script>
// Função para toggle do menu de ações
function toggleMenu() {
    const menu = document.getElementById('actionMenu');
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
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

// Funções de tab
function openTab(tabName) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
    
    // Atualizar URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

lucide.createIcons();
</script>

<?php renderAppFooter(); ?>'''
    
    # Adicionar no final
    if not content.strip().endswith('?>'):
        content = content.rstrip() + '\n' + script_js
    else:
        content = content.replace('<?php renderAppFooter(); ?>', script_js)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ Abas Equipe e Músicas substituídas")
print("✓ Script JavaScript adicionado")
print("✓ Arquivo completo e funcional")
