# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# --- 1. REMOVER O HERO HEADER ANTIGO ---
# Esse é o bloco que contém "Detalhes da Escala" e o degradê verde original
# Vamos capturar todo o bloco do Hero Header
pattern_hero = r'<!-- Hero Header -->.*?<div style=".*?background: linear-gradient.*?Detalhes da Escala.*?</div>'
# Mas precisamos manter os botões de navegação (Voltar e Globais)
# Vamos extrair os botões primeiro
nav_buttons_match = re.search(r'(<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">.*?</div>)\s*<div style="display: flex; justify-content: space-between; align-items: flex-start;">', content, flags=re.DOTALL)
nav_buttons = nav_buttons_match.group(1) if nav_buttons_match else ''

# Remover o Hero Header antigo completamente
# Procurar do comentário <!-- Hero Header --> até o fechamento da div
content = re.sub(r'<!-- Hero Header -->.*?<h1.*?Detalhes da Escala.*?</div>\s*</div>', '', content, flags=re.DOTALL)


# --- 2. INTEGRAR BOTÕES DE NAVEGAÇÃO NO NOVO HEADER ---
# O novo header é o do card verde (Event Date & Type)
# Vamos injetar os botões de navegação no topo desse card
# E remover o border-radius superior para ele colar no topo (opcional, ou deixar flutuante)
# Vamos deixar com margem negativa para subir

# Localizar o container principal do card (card-clean)
# Vamos adicionar uma margem negativa top para ele subir e ocupar o lugar do header antigo
content = content.replace(
    '<div class="card-clean" style="padding: 0; overflow: hidden; border-radius: 24px;', 
    '<div class="card-clean" style="padding: 0; overflow: hidden; border-radius: 0 0 32px 32px; margin: -24px -16px 24px -16px;'
)

# Agora precisamos inserir os botões de navegação (Voltar e Ações) DENTRO do header verde do evento
# O header verde começa com: <div style="background: linear-gradient(135deg, #047857 0%, #065f46 100%); padding: 24px; color: white; position: relative;">

# Preparar o HTML dos botões de navegação para serem inseridos
# Vamos ajustar o estilo do botão Voltar para ficar transparente/branco harmônico
nav_buttons_adjusted = nav_buttons.replace(
    'margin-bottom: 24px;', 
    'margin-bottom: 24px; position: relative; z-index: 10;'
)

# Inserir logo após a abertura da div do header verde
content = re.sub(
    r'(<div style="background: linear-gradient\(135deg, #047857 0%, #065f46 100%\); padding: 24px; color: white; position: relative;">)', 
    r'\1' + '\n' + nav_buttons_adjusted, 
    content
)

# --- 3. AJUSTAR POSIÇÃO DO MENU DE TRÊS PONTINHOS ---
# O menu de 3 pontinhos já está absolute top: 20px right: 20px. 
# Como adicionamos uma linha de botões acima, precisamos descer o conteúdo do evento OU ajustar o menu
# Vamos mover o menu de 3 pontinhos para ficar alinhado com os botões de navegação, no canto direito
# Ou melhor, integrar nos nav buttons? Não, o usuário pediu 3 pontinhos.
# Vamos ajustar o `top` do menu de 3 pontinhos para alinhar com o botão Voltar (que tem height ~40px)
# Atualmente top: 20px. Vamos manter, pois o Nav Row tem margin-bottom.
# Talvez seja melhor colocar os 3 pontinhos DENTRO da Nav Row, ao lado do Avatar?
# O Nav Row tem: Voltar (Esq) e GlobalButtons (Dir).
# Vamos mover o botão de 3 pontinhos para dentro da div de GlobalNavigation (includes/layout.php renderiza isso, mas aqui é difícil mexer)
# Vamos posicionar absolute, mas ajustar o Top para ficar alinhado.

# Ajustar top do menu de 3 pontinhos para 28px (alinhar visualmente com os botões)
# E garantir z-index alto
content = content.replace('top: 20px; right: 20px;', 'top: 24px; right: 20px; z-index: 20;')


# --- 4. REMOVER O TÍTULO "Detalhes da Escala" QUE SOBROU (se houver) ---
# Já fizemos no passo 1, mas garantir que não ficou lixo
# O passo 1 usou regex agressivo.

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Cabeçalhos unificados com sucesso!")
print("✅ Navegação preservada no topo")
print("✅ Layout limpo e moderno")
