# -*- coding: utf-8 -*-
import re
import sys

# Configurar encoding
sys.stdout.reconfigure(encoding='utf-8')

file_path = r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Estilo unificado para os Cards (Baseado no card de detalhes, mas forcando branco para contraste)
card_style = 'background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid var(--border-subtle); margin-top: 32px;'

# --- 1. ENCAPSULAR EQUIPE ---
# Encontrar a div #equipe e aplicar o estilo de card diretamente nela
# Ela pode ter estilo ou não dependendo das edits passadas.
# Vamos substituir a tag de abertura.
content = re.sub(
    r'<div id="equipe"[^>]*>', 
    f'<div id="equipe" style="{card_style}">', 
    content
)

# --- 2. ENCAPSULAR REPERTÓRIO ---
# Encontrar a div #repertorio
content = re.sub(
    r'<div id="repertorio"[^>]*>', 
    f'<div id="repertorio" style="{card_style}">', 
    content
)

# --- 3. HARMONIZAR O ESPAÇAMENTO INTERNO ---
# Como agora temos padding no card, precisamos remover margens negativas ou excessivas dos filhos se houver.
# No passo anterior, padronizei o header do repertório.
# O header tinha: <div style="display: flex; ... margin-bottom: 16px;">
# Isso geralmente funciona bem com padding.

# Verificar se 'Equipe Escalada' header tem margins estranhas.
# <div style="display: flex; ... margin: 32px 0 16px 0; padding: 0 4px;"> (Linha 406 do dump anterior)
# Esse margin-top 32px vai somar com o padding 24px do card e ficar enorme (56px).
# Vamos reduzir esse margin-top interno.
content = content.replace('margin: 32px 0 16px 0;', 'margin: 0 0 16px 0;') # Remove margem topo do header da equipe

# Mesmo para o header do Repertório se tiver
# No meu script anterior (_fix_repertoire_style.py), não coloquei margem top interna excessiva, 
# mas coloquei margin-top na div #repertorio em si.
# O novo estilo da div #repertorio já TEM margin-top: 32px no card_style definido acima.
# Então está coerente.


# --- 4. AJUSTE DE FUNDO DA PÁGINA (Opcional, mas ajuda no contraste) ---
# Se a página for toda branca, os cards brancos não aparecem.
# Vamos garantir que o fundo geral seja levemente cinza?
# Geralmente isso fica no layout.php ou css global. Não vou mexer aqui para não quebrar site-wide.
# Mas vou assumir que var(--bg-primary) é off-white.


# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Seções encapsuladas em Cards Brancos distintos (Separação Nítida)")
