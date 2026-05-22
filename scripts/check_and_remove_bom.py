import os

def check_and_remove_bom(directory):
    php_files_with_bom = []
    
    # Pastas para ignorar
    ignore_dirs = {'.git', 'node_modules', 'dashboard', 'dist', 'vendor'}
    
    for root, dirs, files in os.walk(directory):
        # Ignorar pastas indesejadas
        dirs[:] = [d for d in dirs if d not in ignore_dirs]
        
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                try:
                    with open(filepath, 'rb') as f:
                        content = f.read(3)
                        if content == b'\xef\xbb\xbf':
                            php_files_with_bom.append(filepath)
                except Exception as e:
                    print(f"Erro ao ler {filepath}: {e}")
                    
    if not php_files_with_bom:
        print("Nenhum arquivo PHP com UTF-8 BOM encontrado!")
        return
        
    print(f"Encontrados {len(php_files_with_bom)} arquivo(s) com BOM:")
    for filepath in php_files_with_bom:
        print(f" - {filepath}")
        
    print("\nRemovendo BOM de todos os arquivos encontrados...")
    for filepath in php_files_with_bom:
        try:
            with open(filepath, 'rb') as f:
                full_content = f.read()
            # Remove os 3 primeiros bytes (BOM)
            clean_content = full_content[3:]
            with open(filepath, 'wb') as f:
                f.write(clean_content)
            print(f" [OK] BOM removido de: {filepath}")
        except Exception as e:
            print(f" [ERRO] Falha ao limpar {filepath}: {e}")

if __name__ == '__main__':
    project_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    print(f"Varrendo arquivos PHP em: {project_dir}")
    check_and_remove_bom(project_dir)
