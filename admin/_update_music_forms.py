# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

files = [
    r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\musica_adicionar.php',
    r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\musica_editar.php'
]

# --- CSS COMUM (Incluindo Seletor de Tags) ---
common_style = """<style>
    /* Modern Form Styles & Tag Selector */
    body { background-color: #f8fafc !important; }
    .form-section { background: white; border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 20px; padding: 32px; margin-bottom: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: all 0.3s ease; }
    .form-section:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .form-section-title { font-size: 0.85rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 24px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; }
    .form-label { font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; display: block; }
    .form-input { width: 100%; padding: 14px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #1e293b; font-size: 0.95rem; font-weight: 500; transition: all 0.2s; }
    .form-input:focus { background: white; border-color: #047857; box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1); outline: none; }
    
    /* Tag Selector */
    .tag-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
    .tag-option { position: relative; cursor: pointer; }
    .tag-option input { display: none; }
    .tag-pill { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 12px; background: #f1f5f9; border: 2px solid transparent; font-weight: 600; color: #64748b; transition: all 0.2s; text-align: center; font-size: 0.9rem; }
    .tag-option input:checked + .tag-pill { background: #ecfdf5; border-color: var(--tag-color, #047857); color: var(--tag-color, #047857); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .tag-pill::before { content: ''; width: 10px; height: 10px; border-radius: 50%; background: var(--tag-color, #ccc); opacity: 0.5; }
    .tag-option input:checked + .tag-pill::before { opacity: 1; box-shadow: 0 0 0 2px white; }
    
    /* Input Icon */
    .input-icon-wrapper { position: relative; }
    .input-icon-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 18px; pointer-events: none; }
    .input-icon-wrapper input { padding-left: 48px; }
    
    /* Autocomplete */
    .autocomplete-suggestions { position: absolute; background: white; border: 1px solid #e2e8f0; border-radius: 12px; max-height: 250px; overflow-y: auto; z-index: 1000; width: 100%; display: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); margin-top: 4px; }
    .autocomplete-suggestion { padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f1f5f9; color: #475569; }
    .autocomplete-suggestion:hover { background: #f0fdf4; color: #047857; }
</style>"""

# --- HTML SELETOR DE TAGS ---
tags_html = """
        <div class="form-group">
            <label class="form-label">Classificações (Selecione uma ou mais)</label>
            <div class="tag-grid">
                <?php foreach ($allTags as $tag): 
                    $isChecked = in_array($tag['id'], $selectedTagIds); 
                ?>
                    <label class="tag-option">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
                        <span class="tag-pill" style="--tag-color: <?= $tag['color'] ?: '#047857' ?>">
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 10px; text-align: right;">
                <a href="classificacoes.php" target="_blank" style="font-size: 0.85rem; color: #047857; font-weight: 600; text-decoration: none;">+ Gerenciar Classificações</a>
            </div>
        </div>
"""

for file_path in files:
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. INJETAR BUSCA DE TAGS NO PHP (Topo)
    php_tags_query = """
// Buscar todas as tags
$allTags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Se for editar, buscar tags já selecionadas
$selectedTagIds = [];
if (isset($id)) {
    $stmtTags = $pdo->prepare("SELECT tag_id FROM song_tags WHERE song_id = ?");
    $stmtTags->execute([$id]);
    $selectedTagIds = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
}
"""
    # Inserir após require_once layout
    if '$allTags' not in content:
        content = content.replace("require_once '../includes/layout.php';", "require_once '../includes/layout.php';\n" + php_tags_query)


    # 2. ATUALIZAR LÓGICA POST (Salvar Tags)
    # Lógica para salvar song_tags e atualizar category (legacy)
    # Procurar onde executa o INSERT ou UPDATE e modificar.
    
    post_logic_insert = """
    // Pegar nome da primeira tag para preencher category (legacy)
    $categoryLegacy = 'Outros';
    if (!empty($_POST['selected_tags'])) {
        // Buscar nome da primeira tag selecionada
        $firstTagId = $_POST['selected_tags'][0];
        foreach ($allTags as $t) {
            if ($t['id'] == $firstTagId) {
                $categoryLegacy = $t['name'];
                break;
            }
        }
    } else {
        $categoryLegacy = $_POST['category'] ?? 'Louvor'; // Fallback se o usuário não selecionar nada
    }

    $stmt = $pdo->prepare("
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, custom_fields, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['artist'],
        $_POST['tone'] ?: null,
        $_POST['bpm'] ?: null,
        $_POST['duration'] ?: null,
        $categoryLegacy,
        $_POST['link_letra'] ?: null,
        $_POST['link_cifra'] ?: null,
        $_POST['link_audio'] ?: null,
        $_POST['link_video'] ?: null,
        $_POST['tags'] ?: null,
        $_POST['notes'] ?: null,
        $customFieldsJson
    ]);

    $newId = $pdo->lastInsertId();

    // Salvar Relacionamento song_tags
    if (!empty($_POST['selected_tags'])) {
        $stmtTag = $pdo->prepare("INSERT INTO song_tags (song_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['selected_tags'] as $tagId) {
            $stmtTag->execute([$newId, $tagId]);
        }
    }
    """
    
    post_logic_update = """
    // Pegar nome da primeira tag para preencher category (legacy)
    $categoryLegacy = 'Outros';
    if (!empty($_POST['selected_tags'])) {
        $firstTagId = $_POST['selected_tags'][0];
        foreach ($allTags as $t) {
            if ($t['id'] == $firstTagId) { $categoryLegacy = $t['name']; break; }
        }
    } else {
        $categoryLegacy = $song['category']; // Manter anterior se não mudar
    }

    $stmt = $pdo->prepare("
        UPDATE songs SET 
            title = ?, artist = ?, tone = ?, bpm = ?, duration = ?, category = ?,
            link_letra = ?, link_cifra = ?, link_audio = ?, link_video = ?, 
            tags = ?, notes = ?, custom_fields = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['artist'],
        $_POST['tone'] ?: null,
        $_POST['bpm'] ?: null,
        $_POST['duration'] ?: null,
        $categoryLegacy,
        $_POST['link_letra'] ?: null,
        $_POST['link_cifra'] ?: null,
        $_POST['link_audio'] ?: null,
        $_POST['link_video'] ?: null,
        $_POST['tags'] ?: null,
        $_POST['notes'] ?: null,
        $customFieldsJson,
        $id
    ]);

    // Atualizar Tags Relacionadas
    $pdo->prepare("DELETE FROM song_tags WHERE song_id = ?")->execute([$id]);
    
    if (!empty($_POST['selected_tags'])) {
        $stmtTag = $pdo->prepare("INSERT INTO song_tags (song_id, tag_id) VALUES (?, ?)");
        foreach ($_POST['selected_tags'] as $tagId) {
            $stmtTag->execute([$id, $tagId]);
        }
    }
    """

    # Substituir bloco do INSERT (adicionar)
    if 'INSERT INTO songs' in content:
        # Regex simplificado para pegar o prepare e execute do insert
        pattern_insert = r'\$stmt = \$pdo->prepare\("\s*INSERT INTO songs.*?\);.*?\], \$_POST\[\'tags\'\] \?: null,.*?\$_POST\[\'notes\'\] \?: null,.*?\$customFieldsJson.*?\]\);.*?\$newId = \$pdo->lastInsertId\(\);'
        # Difícil casar regex multi-linha preciso.
        # Vou procurar trecho chave.
        start_insert = '$stmt = $pdo->prepare("\n        INSERT INTO songs ('
        end_insert = '$newId = $pdo->lastInsertId();'
        
        # Encontrar índices
        s_idx = content.find(start_insert)
        e_idx = content.find(end_insert)
        
        if s_idx != -1 and e_idx != -1:
            # Substituir todo o bloco até o newId
            content = content[:s_idx] + post_logic_insert + content[e_idx + len(end_insert):]

    # Substituir bloco do UPDATE (editar)
    if 'UPDATE songs SET' in content:
        start_update = '$stmt = $pdo->prepare("\n        UPDATE songs SET'
        end_update = '$id\n    ]);' # Final do array do execute
        
        # O execute termina com ']);'. Vou procurar 'header("Location: musica_detalhe' que vem logo depois.
        marker_after = 'header("Location: musica_detalhe'
        s_idx = content.find(start_update)
        e_idx = content.find(marker_after)
        
        if s_idx != -1 and e_idx != -1:
             content = content[:s_idx] + post_logic_update + "\n\n    " + content[e_idx:]


    # 3. SUBSTITUIR STYLE (Unificar CSS)
    # Remover styles antigos e colocar o common_style
    content = re.sub(r'<style>.*?</style>', common_style, content, flags=re.DOTALL)


    # 4. SUBSTITUIR CAMPO CATEGORIA POR TAG SELECTOR HTML
    # O campo antigo: <select name="category" ... </select> envolvido em form-group
    # Regex para pegar o form-group da categoria
    pattern_cat = r'<div class="form-group">\s*<label class="form-label">Categoria</label>\s*<select name="category".*?</select>\s*</div>'
    content = re.sub(pattern_cat, tags_html, content, flags=re.DOTALL)


    # 5. AJUSTAR INPUTS NO EDITAR (Para ter ícones igual Adicionar)
    # Se for edited_file e não teve ícones aplicados como no add_song.
    if 'musica_editar.php' in file_path:
        def create_icon_input(label, name, val_var, placeholder, icon):
            return f"""
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">{label}</label>
            <div class="input-icon-wrapper">
                <i data-lucide="{icon}"></i>
                <input type="url" name="{name}" class="form-input" value="<?= htmlspecialchars({val_var}) ?>" placeholder="{placeholder}">
            </div>
        </div>"""
        
        # Refazer seção referencias do editar
        start_marker = '<!-- Links/Referências -->'
        pattern_refs = re.escape(start_marker) + r'.*?<div class="form-section">.*?<div class="form-section-title">Referências</div>.*?</div>\s*</div>'
        # Usar replace manual pq regex falha em blocos grandes sem limites claros
        
        new_refs_html = f"""
    <!-- Links/Referências -->
    <div class="form-section">
        <div class="form-section-title">Referências e Mídia</div>
        {create_icon_input('Link da Letra', 'link_letra', "$song['link_letra']", 'https://...', 'file-text')}
        {create_icon_input('Link da Cifra', 'link_cifra', "$song['link_cifra']", 'https://...', 'music-2')}
        {create_icon_input('Link do Áudio', 'link_audio', "$song['link_audio']", 'https://...', 'headphones')}
        {create_icon_input('Link do Vídeo', 'link_video', "$song['link_video']", 'https://...', 'video')}
    </div>
"""
        # Substituir bloco referencias
        # Localizar via string simples
        block_start = '<!-- Links/Referências -->\n    <div class="form-section">\n        <div class="form-section-title">Referências</div>'
        # Até o próximo comentário
        block_end_marker = '<!-- Campos Customizados -->'
        
        s_idx = content.find(block_start)
        e_idx = content.find(block_end_marker)
        if s_idx != -1 and e_idx != -1:
             content = content[:s_idx] + new_refs_html + "\n\n    " + content[e_idx:]
        
        # Modernizar Botão Salvar (no editar)
        submit_btn_style = "background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); flex: 2; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;"
        content = content.replace('class="btn-action-save ripple" style="flex: 2; justify-content: center;"', f'class="ripple" style="{submit_btn_style}"')
        content = content.replace('class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;"', 'class="ripple" style="background: white; color: #64748b; border: 1px solid #cbd5e1; padding: 16px; border-radius: 12px; font-weight: 600; flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none;"')


    # Salvar
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("✅ Formulários de Música atualizados com Tags Selecionáveis!")
