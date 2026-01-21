import re

# Ler arquivo principal
with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Ler novos designs
with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_equipe.php', 'r', encoding='utf-8') as f:
    new_equipe = f.read()

with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\_new_tab_musicas.php', 'r', encoding='utf-8') as f:
    new_musicas = f.read()

# Substituir aba Equipe
pattern_equipe = r'<!-- CONTEÚDO: EQUIPE -->.*?(?=<!-- CONTEÚDO: REPERTÓRIO -->)'
content = re.sub(pattern_equipe, new_equipe + '\n\n', content, flags=re.DOTALL)

# Substituir aba Músicas/Repertório
pattern_musicas = r'<!-- CONTEÚDO: REPERTÓRIO -->.*?(?=<!-- MODAIS -->|$)'
content = re.sub(pattern_musicas, new_musicas + '\n\n', content, flags=re.DOTALL)

# Salvar arquivo
with open(r'c:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\admin\escala_detalhe.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Abas Equipe e Músicas substituídas com sucesso!")
