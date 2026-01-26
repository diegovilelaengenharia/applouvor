#!/usr/bin/env python3
"""
Script para padronizar tipografia substituindo font-size hardcoded por variáveis CSS
"""
import re
import os

# Mapeamento de valores para variáveis CSS
FONT_SIZE_MAP = {
    # Valores exatos
    '16px': 'var(--font-h3)',  # 1rem = 16px
    '1rem': 'var(--font-h3)',
    
    # H1 - 20px / 1.25rem
    '20px': 'var(--font-h1)',
    '1.25rem': 'var(--font-h1)',
    
    # H2 - 18px / 1.125rem
    '18px': 'var(--font-h2)',
    '1.125rem': 'var(--font-h2)',
    '1.1rem': 'var(--font-h2)',  # Aproximado
    '1.15rem': 'var(--font-h2)',  # Aproximado
    
    # Body - 15px / 0.9375rem
    '15px': 'var(--font-body)',
    '0.9375rem': 'var(--font-body)',
    '0.9rem': 'var(--font-body)',  # Aproximado
    '0.95rem': 'var(--font-body)',  # Aproximado
    
    # Body Small - 13px / 0.8125rem
    '13px': 'var(--font-body-sm)',
    '0.8125rem': 'var(--font-body-sm)',
    '0.8rem': 'var(--font-body-sm)',  # Aproximado
    '0.85rem': 'var(--font-body-sm)',  # Aproximado
    
    # Small/Caption - 12px / 0.75rem
    '12px': 'var(--font-small)',
    '0.75rem': 'var(--font-small)',
    '0.7rem': 'var(--font-small)',  # Aproximado
    '0.65rem': 'var(--font-small)',  # Muito pequeno, usar small
    
    # Display/Large - 24px / 1.5rem
    '24px': 'var(--font-display)',
    '1.5rem': 'var(--font-display)',
    '1.4rem': 'var(--font-h1)',  # Aproximado para H1
    
    # Muito grandes (usar display)
    '2rem': 'var(--font-display)',
    '2.5rem': 'var(--font-display)',
    
    # Outros aproximados
    '1.05rem': 'var(--font-h2)',
    '1.2rem': 'var(--font-h1)',
}

def replace_font_sizes(content):
    """Substitui font-size hardcoded por variáveis CSS"""
    
    def replacer(match):
        value = match.group(1).strip()
        if value in FONT_SIZE_MAP:
            return f"font-size: {FONT_SIZE_MAP[value]}"
        return match.group(0)  # Retorna original se não encontrar mapeamento
    
    # Padrão para encontrar font-size: <valor>
    pattern = r'font-size:\s*([^;]+)'
    return re.sub(pattern, replacer, content)

def process_file(filepath):
    """Processa um arquivo CSS"""
    print(f"Processando: {filepath}")
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    original_content = content
    content = replace_font_sizes(content)
    
    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"[OK] Arquivo atualizado: {filepath}")
        return True
    else:
        print(f"[-] Nenhuma alteracao necessaria: {filepath}")
        return False

def main():
    base_path = r"C:\Users\diego\Meu Drive\03. Igreja  e Espiritualidade\01. PIB Oliveira\Ministério de Louvor\01. Louvor PIB 2026 (Compartilhada)\05. App Louvor\assets\css"
    
    css_files = [
        'style.css',
        'roles.css',
        'components.css',
        'dashboard.css',
        'modern-enhancements.css',
    ]
    
    updated_count = 0
    for filename in css_files:
        filepath = os.path.join(base_path, filename)
        if os.path.exists(filepath):
            if process_file(filepath):
                updated_count += 1
        else:
            print(f"[!] Arquivo nao encontrado: {filepath}")
    
    print(f"\n[OK] Concluido! {updated_count} arquivo(s) atualizado(s).")

if __name__ == '__main__':
    main()
