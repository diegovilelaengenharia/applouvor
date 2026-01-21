# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\musica_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. INJETAR BUSCA DE TAGS NO PHP
# Logo após buscar a música ($song = ...)
php_tags_query = """
// Buscar Tags da Música
$stmtTags = $pdo->prepare("
    SELECT t.* FROM tags t
    JOIN song_tags st ON t.id = st.tag_id
    WHERE st.song_id = ?
");
$stmtTags->execute([$id]);
$songTags = $stmtTags->fetchAll(PDO::FETCH_ASSOC);
"""

if '$songTags' not in content:
    content = content.replace("if (!$song) {", php_tags_query + "\nif (!$song) {")


# 2. SUBSTITUIR EXIBIÇÃO DA CATEGORIA POR TAGS
# Procurar o bloco onde exibe CATEGORIA (geralmente num <div class="info-item"> ou similar)
# No arquivo atual: <div class="detail-label">Categoria</div> ... <div class="detail-value">...</div>

# HTML das Tags
tags_html = """
                <div class="detail-label">Classificações</div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px;">
                    <?php if (!empty($songTags)): ?>
                        <?php foreach ($songTags as $t): ?>
                            <a href="pasta_detalhe.php?id=<?= $t['id'] ?>" style="text-decoration: none;">
                                <span style="
                                    background: <?= $t['color'] ?: '#047857' ?>15; 
                                    color: <?= $t['color'] ?: '#047857' ?>; 
                                    border: 1px solid <?= $t['color'] ?: '#047857' ?>40; 
                                    padding: 6px 12px; 
                                    border-radius: 8px; 
                                    font-weight: 600; 
                                    font-size: 0.9rem;
                                    display: inline-block;
                                ">
                                    <?= htmlspecialchars($t['name']) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Sem classificação</span>
                    <?php endif; ?>
                </div>
"""

# Tentar substituir o bloco da Categoria
# Padrão: Label Categoria e o Valor
pattern_cat = r'<div class="detail-label">\s*Categoria\s*</div>\s*<div class="detail-value">.*?</div>'

if re.search(pattern_cat, content, re.DOTALL):
    content = re.sub(pattern_cat, tags_html, content, flags=re.DOTALL)
else:
    # Fallback: Tentar substituir pelo valor da variavel $song['category'] se estiver solto
    # Mas no visual novo, deve estar estruturado.
    pass

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ 'musica_detalhe.php' atualizado para exibir Tags coloridas!")
