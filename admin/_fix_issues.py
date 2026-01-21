# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

# Ler arquivo principal
file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remover seção de botões grandes (Ações)
pattern_acoes = r'\s*<!-- Ações -->.*?</div>\s*</div>\s*</div>'
content = re.sub(pattern_acoes, '\n\n    </div>\n</div>', content, flags=re.DOTALL)

# 2. Adicionar script JavaScript antes do </body> ou <?php renderAppFooter()
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

# Substituir o renderAppFooter existente
content = re.sub(r'<\?php renderAppFooter\(\); \?>', script_js, content)

# Salvar arquivo
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Correcoes aplicadas com sucesso!")
print("1. Botoes grandes removidos")
print("2. Script JavaScript adicionado")
