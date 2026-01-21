# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\musica_adicionar.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. CSS MODERNO ---
new_css = """
<style>
    /* Modern Form Styles */
    body {
        background-color: #f8fafc !important; 
    }

    .form-section {
        background: white;
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 32px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: all 0.3s ease;
    }

    .form-section:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
    }

    .form-section-title {
        font-size: 0.85rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 24px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .form-group {
        position: relative;
    }

    .form-label {
        font-size: 0.9rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 8px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #1e293b;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .form-input:focus {
        background: white;
        border-color: #047857;
        box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1);
        outline: none;
    }
    
    .form-input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }

    /* Input com Ícone Interno */
    .input-icon-wrapper {
        position: relative;
    }
    
    .input-icon-wrapper i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        width: 18px;
        transition: color 0.2s;
        pointer-events: none;
    }

    .input-icon-wrapper input {
        padding-left: 48px;
    }

    .input-icon-wrapper input:focus + i,
    .input-icon-wrapper input:focus ~ i { /* Fallback */
        color: #047857;
    }
    
    /* Autocomplete */
    .autocomplete-suggestions {
        position: absolute;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        width: 100%;
        display: none;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        margin-top: 4px;
    }

    .autocomplete-suggestion {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
        font-weight: 500;
        transition: background 0.1s;
    }

    .autocomplete-suggestion:hover {
        background: #f0fdf4;
        color: #047857;
    }
</style>
"""

# Substituir todo o bloco <style>... </style> antigo
content = re.sub(r'<style>.*?</style>', new_css, content, flags=re.DOTALL)


# --- 2. TRANSFORMAR LINKS EM INPUTS COM ÍCONE INTERNO ---
# Substituir toda a div de "Referências"
# Trecho original começa em line 195 e vai até 225
refs_section_pattern = r'<!-- Links/Referências -->.*?<div class="form-section">.*?<div class="form-section-title">Referências</div>.*?</div>\s*</div>'
# O regex acima é arriscado com .*?. Vamos substituir bloco por bloco.

# Função helper
def create_icon_input(label, name, placeholder, icon):
    return f"""
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label">{label}</label>
            <div class="input-icon-wrapper">
                <i data-lucide="{icon}"></i>
                <input type="url" name="{name}" class="form-input" placeholder="{placeholder}">
            </div>
        </div>
    """

new_refs_html = f"""
    <!-- Links/Referências -->
    <div class="form-section">
        <div class="form-section-title">Referências e Mídia</div>

        {create_icon_input('Link da Letra', 'link_letra', 'https://www.letras.mus.br/...', 'file-text')}
        {create_icon_input('Link da Cifra', 'link_cifra', 'https://www.cifraclub.com.br/...', 'music-2')}
        {create_icon_input('Link do Áudio (Spotify/Deezer)', 'link_audio', 'https://open.spotify.com/...', 'headphones')}
        {create_icon_input('Link do Vídeo (YouTube)', 'link_video', 'https://youtu.be/...', 'video')}
    </div>
"""

# Identificar o bloco atual de referências para substituir
# Usar marcadores claros do código
start_marker = '<!-- Links/Referências -->'
end_marker = '<!-- Campos Customizados -->'

# Pegar tudo entre esses marcadores
pattern = re.escape(start_marker) + r'.*?' + re.escape(end_marker)
# Substituir
# Usando re.DOTALL para pegar quebras de linha
content = re.sub(pattern, new_refs_html + '\n\n    ' + end_marker, content, flags=re.DOTALL)


# --- 3. MELHORAR BOTÃO DE SUBMIT ---
# Procurar o botão: <button type="submit" class="btn-action-save ripple"
# Substituir por estilo inline poderoso
submit_btn_style = "background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(4, 120, 87, 0.25); flex: 2; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;"
cancel_btn_style = "background: white; color: #64748b; border: 1px solid #cbd5e1; padding: 16px; border-radius: 12px; font-weight: 600; font-size: 1rem; flex: 1; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s;"

content = content.replace('class="btn-action-save ripple" style="flex: 2; justify-content: center;"', f'class="ripple" style="{submit_btn_style}"')
content = content.replace('class="btn-outline ripple" style="flex: 1; justify-content: center; text-decoration: none;"', f'class="ripple" style="{cancel_btn_style}" onmouseover="this.style.background=\'#f1f5f9\'" onmouseout="this.style.background=\'white\'"')


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Página 'Adicionar Música' modernizada com sucesso!")
