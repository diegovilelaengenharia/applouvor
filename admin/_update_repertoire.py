# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\repertorio.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. ATUALIZAR QUERY 'PASTAS' ---
# Substituir o bloco } elseif ($tab === 'pastas') { ... }
old_query_block = r"\} elseif \(\$tab === 'pastas'\) \{.*?\$stmt = \$pdo->query\(\"SELECT category, COUNT\(\*\) as total FROM songs GROUP BY category ORDER BY category ASC\"\);.*?\$items = \$stmt->fetchAll\(PDO::FETCH_ASSOC\);.*?\$count = count\(\$items\);.*?\}"

new_query_block = """} elseif ($tab === 'pastas') {
    // Buscar tags criadas e contar músicas
    try {
        $stmt = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM song_tags st WHERE st.tag_id = t.id) as total FROM tags t ORDER BY t.name ASC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $items = [];
    }
    $count = count($items);
}"""

# A regex acima é complexa por causa dos caracteres escapados. Vamos tentar replace direto da string conhecida.
old_query_str = """} elseif ($tab === 'pastas') {
    $stmt = $pdo->query("SELECT category, COUNT(*) as total FROM songs GROUP BY category ORDER BY category ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($items);
}"""

content = content.replace(old_query_str, new_query_block)


# --- 2. ATUALIZAR VISUAL LOOP 'PASTAS' ---
# Trecho original: <?php elseif ($tab === 'pastas'): ?> ... <?php foreach ($items as $pasta): ?> ...
# Vamos substituir todo o bloco do elseif pastas.

new_pastas_html = """<?php elseif ($tab === 'pastas'): ?>
    <!-- Lista de Tags (Pastas) -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
        <?php foreach ($items as $pasta): ?>
            <a href="pasta_detalhe.php?id=<?= $pasta['id'] ?>" class="ripple" style="
                background: white;
                border: 1px solid var(--border-subtle);
                border-radius: 16px;
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 16px;
                text-decoration: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                transition: transform 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                
                <div style="
                    width: 52px; 
                    height: 52px; 
                    background: <?= $pasta['color'] ?: '#047857' ?>; 
                    border-radius: 12px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    color: white;
                    box-shadow: 0 4px 10px <?= $pasta['color'] ? $pasta['color'].'40' : '#04785740' ?>;
                ">
                    <i data-lucide="folder" style="width: 26px;"></i>
                </div>
                
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 1.05rem; margin-bottom: 2px;">
                        <?= htmlspecialchars($pasta['name']) ?>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        <?= $pasta['total'] ?> música<?= $pasta['total'] != 1 ? 's' : '' ?>
                    </div>
                </div>
                
                <i data-lucide="chevron-right" style="width: 20px; color: var(--text-muted);"></i>
            </a>
        <?php endforeach; ?>
        
        <?php if (empty($items)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i data-lucide="folder-plus" style="width: 48px; height: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>Nenhuma pasta criada.</p>
                <a href="classificacoes.php" style="color: var(--primary-green); font-weight: 700; margin-top: 8px; display: inline-block;">Criar Classificação</a>
            </div>
        <?php endif; ?>
    </div>
"""

# Regex para substituir o bloco antigo
pattern_pastas = r"<\?php elseif \(\$tab === 'pastas'\): \?>.*?<\?php endif; \?>"
content = re.sub(pattern_pastas, new_pastas_html + "\n<?php endif; ?>", content, flags=re.DOTALL)


# --- 3. ADICIONAR LINK 'GERENCIAR CLASSIFICAÇÕES' NO MENU ---
# Inserir após <div class="sheet-header">Opções do Repertório</div>
menu_link = """
        <a href="classificacoes.php" class="btn-outline ripple" style="width: 100%; justify-content: flex-start; margin-bottom: 10px; text-decoration: none;">
            <i data-lucide="tag"></i> Gerenciar Classificações
        </a>
"""

content = content.replace('<div class="sheet-header">Opções do Repertório</div>', '<div class="sheet-header">Opções do Repertório</div>\n' + menu_link)


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Repertório atualizado com novas Abas de Pastas (Tags)")
