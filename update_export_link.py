# Script para atualizar link de exportacao no menu

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Substituir link de exportacao
content = content.replace(
    'href="exportar_musicas.php"',
    'href="exportar_completo.php"'
)

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Link de exportacao atualizado!")
print("Agora exporta Excel profissional com 3 abas: Membros, Repertorio e Escalas")
