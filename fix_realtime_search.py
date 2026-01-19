# Script para corrigir busca em tempo real no repertorio.php

file_path = r"admin\repertorio.php"

# Ler arquivo
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remover script antigo se existir
if '<script>\n    // Busca em tempo real' in content:
    # Já existe, não fazer nada
    print("Script de busca em tempo real ja existe")
else:
    # Adicionar script de busca em tempo real
    realtime_search = '''
    // Busca em tempo real
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            
            // Aguardar 500ms após parar de digitar
            searchTimeout = setTimeout(() => {
                if (value.length >= 2 || value.length === 0) {
                    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'musicas';
                    if (value.length === 0) {
                        window.location.href = `?tab=${currentTab}`;
                    } else {
                        window.location.href = `?tab=${currentTab}&search=${encodeURIComponent(value)}`;
                    }
                }
            }, 500);
        });
    }
'''
    
    # Inserir antes do último </script>
    last_script_pos = content.rfind('</script>')
    if last_script_pos != -1:
        content = content[:last_script_pos] + realtime_search + '\n' + content[last_script_pos:]

# Salvar
with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("OK - Busca em tempo real corrigida!")
print("Agora funciona:")
print("- Digite 2+ caracteres")
print("- Aguarda 500ms")
print("- Busca automatica")
