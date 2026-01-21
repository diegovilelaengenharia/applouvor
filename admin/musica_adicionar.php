<?php
// admin/musica_adicionar.php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/layout.php';

checkLogin();

// Buscar todas as tags
$allTags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Buscar artistas únicos para autocomplete
$artists = $pdo->query("SELECT DISTINCT artist FROM songs ORDER BY artist ASC")->fetchAll(PDO::FETCH_COLUMN);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Processar campos customizados
    $customFields = [];
    if (!empty($_POST['custom_field_name']) && !empty($_POST['custom_field_link'])) {
        foreach ($_POST['custom_field_name'] as $index => $name) {
            if (!empty($name) && !empty($_POST['custom_field_link'][$index])) {
                $customFields[] = [
                    'name' => trim($name),
                    'link' => trim($_POST['custom_field_link'][$index])
                ];
            }
        }
    }
    $customFieldsJson = !empty($customFields) ? json_encode($customFields) : null;


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

    header("Location: musica_detalhe.php?id=$newId");
    exit;
}

renderAppHeader('Adicionar Música');
?>

<div class="container" style="padding-top: 24px; max-width: 800px; margin: 0 auto;">

    <!-- Cabeçalho Simples -->
    <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0;">Nova Música</h1>
            <p style="color: #64748b; margin-top: 4px;">Preencha os dados da canção</p>
        </div>
        <a href="repertorio.php" class="ripple" style="
            width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; 
            display: flex; align-items: center; justify-content: center; color: #64748b;
        ">
            <i data-lucide="x" style="width: 20px;"></i>
        </a>
    </div>

    <!-- Formulário -->
    <form method="POST">

        <!-- Cartão Principal -->
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Título</label>
                <input type="text" name="title" required placeholder="Ex: Grande é o Senhor" autofocus
                    style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; outline: none; transition: all 0.2s;"
                    onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
                    onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
            </div>

            <div style="margin-bottom: 20px; position: relative;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Artista</label>
                <div style="position: relative;">
                    <i data-lucide="mic-2" style="position: absolute; left: 12px; top: 12px; color: #94a3b8; width: 18px;"></i>
                    <input type="text" name="artist" id="artistInput" required placeholder="Ex: Adhemar de Campos" autocomplete="off"
                        style="width: 100%; padding: 12px 12px 12px 40px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; outline: none; transition: all 0.2s;"
                        onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
                        onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                </div>
                <div id="artistSuggestions" style="
                    position: absolute; width: 100%; background: white; border: 1px solid #e2e8f0; 
                    border-radius: 12px; margin-top: 4px; z-index: 100; max-height: 200px; overflow-y: auto; display: none;
                    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                "></div>
            </div>

            <!-- Grid de Detalhes -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Tom</label>
                    <input type="text" name="tone" placeholder="Ex: G"
                        style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; text-transform: uppercase;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">BPM</label>
                    <input type="number" name="bpm" placeholder="120"
                        style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Duração</label>
                    <input type="text" name="duration" placeholder="0:00"
                        style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                </div>
            </div>

        </div>

        <!-- Tags / Classificações -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin: 0;">Classificação</h3>
                <a href="classificacoes.php" target="_blank" style="font-size: 0.8rem; color: #2563eb; font-weight: 600; text-decoration: none;">Gerenciar Tags</a>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <?php foreach ($allTags as $tag): ?>
                    <label style="cursor: pointer;">
                        <input type="checkbox" name="selected_tags[]" value="<?= $tag['id'] ?>" style="display: none;" onchange="toggleTag(this)">
                        <span class="tag-pill" style="
                            display: inline-block; padding: 8px 16px; border-radius: 20px; 
                            background: #f1f5f9; color: #64748b; font-size: 0.9rem; font-weight: 600;
                            border: 1px solid transparent; transition: all 0.2s;
                        ">
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Links Úteis (Collapsible ou Grid) -->
        <h3 style="font-size: 1rem; font-weight: 700; color: #334155; margin-bottom: 12px;">Links & Referências</h3>
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 24px;">

            <div style="padding: 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="music-2" style="color: #f97316; width: 20px;"></i>
                <input type="url" name="link_cifra" placeholder="Link da Cifra (CifraClub...)" style="flex: 1; border: none; outline: none; font-size: 0.95rem;">
            </div>
            <div style="padding: 16px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="youtube" style="color: #ef4444; width: 20px;"></i>
                <input type="url" name="link_video" placeholder="Link do Vídeo (YouTube...)" style="flex: 1; border: none; outline: none; font-size: 0.95rem;">
            </div>
            <div style="padding: 16px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="headphones" style="color: #10b981; width: 20px;"></i>
                <input type="url" name="link_audio" placeholder="Link do Áudio (Spotify...)" style="flex: 1; border: none; outline: none; font-size: 0.95rem;">
            </div>

        </div>

        <!-- Observações -->
        <div style="margin-bottom: 32px;">
            <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px;">Observações</label>
            <textarea name="notes" rows="3" placeholder="Detalhes sobre arranjo, versão, etc..."
                style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; resize: vertical;"></textarea>
        </div>

        <!-- Botão Salvar -->
        <button type="submit" class="ripple" style="
            width: 100%; border: none; 
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); 
            color: white; padding: 16px; border-radius: 16px; 
            font-size: 1.1rem; font-weight: 700; 
            display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
            cursor: pointer;
        ">
            <i data-lucide="check-circle" style="width: 20px;"></i>
            Salvar Música
        </button>

    </form>

    <div style="height: 60px;"></div>
</div>

<script>
    // Toggle Visual Tag
    function toggleTag(input) {
        const pill = input.nextElementSibling;
        if (input.checked) {
            pill.style.background = '#dcfce7';
            pill.style.color = '#166534';
            pill.style.borderColor = '#bbf7d0';
        } else {
            pill.style.background = '#f1f5f9';
            pill.style.color = '#64748b';
            pill.style.borderColor = 'transparent';
        }
    }

    // Autocomplete Logic
    const artists = <?= json_encode($artists) ?>;
    const input = document.getElementById('artistInput');
    const suggestions = document.getElementById('artistSuggestions');

    input.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        suggestions.innerHTML = '';
        if (val.length < 2) {
            suggestions.style.display = 'none';
            return;
        }

        const filtered = artists.filter(a => a.toLowerCase().includes(val));
        if (filtered.length > 0) {
            suggestions.style.display = 'block';
            filtered.forEach(artist => {
                const div = document.createElement('div');
                div.textContent = artist;
                div.style.padding = '12px';
                div.style.cursor = 'pointer';
                div.style.borderBottom = '1px solid #f1f5f9';
                div.onmouseover = () => div.style.background = '#f8fafc';
                div.onmouseout = () => div.style.background = 'white';
                div.onclick = () => {
                    input.value = artist;
                    suggestions.style.display = 'none';
                }
                suggestions.appendChild(div);
            });
        } else {
            suggestions.style.display = 'none';
        }
    });

    document.addEventListener('click', e => {
        if (!input.contains(e.target)) suggestions.style.display = 'none';
    });
</script>

<?php renderAppFooter(); ?>