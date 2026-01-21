# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\musica_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. ADICIONAR LÓGICA DE EXCLUSÃO (NO TOPO) ---
php_delete_logic = """
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_song') {
    try {
        $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: repertorio.php");
        exit;
    } catch (PDOException $e) {
        // Tratar erro de chave estrangeira se houver
        echo "<script>alert('Erro ao excluir música. Ela pode estar em uso em alguma escala.');</script>";
    }
}

renderAppHeader('Música');
"""
content = content.replace("renderAppHeader('Música');", php_delete_logic)


# --- 2. ATUALIZAR ESTILO DOS CARDS (.info-section) ---
# Atual: background: var(--bg-secondary); border: 1px solid var(--border-subtle); border-radius: 12px; padding: 16px; margin-bottom: 16px;
# Novo Padrão (Escala): background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-subtle); margin-bottom: 32px;

new_card_css = """
    .info-section {
        background: white;
        border: 1px solid var(--border-subtle);
        border-radius: 20px;
        padding: 24px;
        margin-bottom: 32px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
"""
# Substituir CSS antigo
content = re.sub(r'\.info-section\s*\{[^}]*\}', new_card_css, content)


# --- 3. REMOVER BOTÃO EDITAR DO HEADER ---
# Procurar o bloco do botão edit e remover
# <a href="musica_editar.php?id=... edit-2 ... </a>
pattern_edit_btn = r'<a href="musica_editar\.php\?id=<\?= \$id \?>" class="ripple" style="[^"]*background: rgba\(255,255,255,0\.2\);[^"]*">.*?</a>'
content = re.sub(pattern_edit_btn, '', content, flags=re.DOTALL)


# --- 4. ADICIONAR SEÇÃO DE GERENCIAMENTO NO FINAL ---
# Inserir antes do `<?php if ($song['tags']): ?>` ou antes do footer se tags for o ultimo.
# O arquivo termina com tags e depois footer.
# Vou inserir antes de `<?php renderAppFooter(); ?>`

management_section = """
<!-- SEÇÃO DE GERENCIAMENTO -->
<div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-subtle); margin-top: 32px; margin-bottom: 40px;">
    <h3 style="font-size: 0.85rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
        <i data-lucide="settings" style="width: 14px;"></i> Gerenciamento
    </h3>
    
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <!-- Botão Editar -->
        <a href="musica_editar.php?id=<?= $id ?>" class="ripple" style="
            flex: 1;
            background: #fbbf24;
            color: #78350f;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        " onmouseover="this.style.background='#f59e0b'" onmouseout="this.style.background='#fbbf24'">
            <i data-lucide="edit-3" style="width: 20px;"></i> Editar Música
        </a>

        <!-- Botão Excluir -->
        <form method="POST" onsubmit="return confirm('ATENÇÃO: Tem certeza que deseja excluir esta música?')" style="margin: 0; flex: 1;">
            <input type="hidden" name="action" value="delete_song">
            <button type="submit" class="ripple" style="
                width: 100%;
                background: #ef4444;
                color: white;
                padding: 16px;
                border-radius: 12px;
                font-weight: 700;
                font-size: 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                transition: all 0.2s;
                border: none;
            " onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                <i data-lucide="trash-2" style="width: 20px;"></i> Excluir Música
            </button>
        </form>
    </div>
</div>
"""

content = content.replace('<?php renderAppFooter(); ?>', management_section + '\n<?php renderAppFooter(); ?>')


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Música Detalhe padronizado (Cards Brancos + Ações no Footer)")
