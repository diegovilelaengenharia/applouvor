# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. LÓGICA PHP PARA GERAR TEXTO DO WHATSAPP ---
# Vamos inserir isso logo antes do renderAppHeader ou dentro do body antes de usar.
# Melhor lugar: Antes do Card Principal, dentro da div #detalhes, para ter acesso às variáveis já carregadas.

php_logic = """
    <?php
    // --- GERAR LINK DO WHATSAPP ---
    $waText = "*ESCALA LOUVOR PIB* 🎸🎤\\n";
    $waText .= "🗓 " . $dayName . ", " . $formattedDate . "\\n";
    $waText .= "⏰ " . $timeStr . " • " . $schedule['event_type'] . "\\n\\n";

    $waText .= "*👥 EQUIPE:*\\n";
    if (empty($currentMembers)) {
        $waText .= "(Ninguém escalado ainda)\\n";
    } else {
        foreach ($currentMembers as $m) {
            $waText .= "▪ " . $m['name'] . " (" . ($m['instrument'] ?: 'Voz') . ")\\n";
        }
    }

    $waText .= "\\n*🎵 REPERTÓRIO:*\\n";
    if (empty($currentSongs)) {
        $waText .= "(Nenhuma música selecionada)\\n";
    } else {
        foreach ($currentSongs as $i => $s) {
            $waText .= ($i+1) . ". " . $s['title'] . " - " . $s['artist'] . " (" . $s['tone'] . ")\\n";
        }
    }

    if (!empty($schedule['notes'])) {
        $waText .= "\\n⚠ *Obs:* " . $schedule['notes'] . "\\n";
    }

    $waLink = "https://wa.me/?text=" . urlencode($waText);
    ?>
"""

# Inserir a lógica logo após a abertura da div #detalhes
content = content.replace('<div id="detalhes">', '<div id="detalhes">\n' + php_logic)


# --- 2. INSERIR O BOTÃO NO HEADER ---
# Local: Antes de <?php renderGlobalNavButtons(); ?>
# Estilo: Botão verde do WhatsApp, com ícone share-2 ou message-circle

button_html = """
            <a href="<?= $waLink ?>" target="_blank" class="ripple" style="
                background: #25D366; 
                color: white; 
                width: 40px; 
                height: 40px; 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                margin-right: 12px;
                box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
                transition: transform 0.2s;
            " onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <i data-lucide="share-2" style="width: 20px;"></i>
            </a>
"""

# Procurar onde injetar.
# Padrão: <div style="display: flex; align-items: center;">\s*<?php renderGlobalNavButtons(); ?>
content = content.replace(
    '<?php renderGlobalNavButtons(); ?>', 
    button_html + '\n            <?php renderGlobalNavButtons(); ?>'
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Botão de Compartilhar no WhatsApp adicionado com sucesso!")
