import os
import sys
import subprocess
import shutil

# Adiciona o diretório raiz ao path para permitir importações
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), '../..')))

from scripts.deploy.deploy_react_ftp import deploy as run_ftp_deploy

def run_command(command, cwd=None, description=""):
    """
    Executa um comando de terminal de forma robusta e exibe a saída.
    Retorna True se sucesso, False caso contrário.
    """
    print(f"\n============================================================")
    print(f"[EXEC] Executando: {description}...")
    print(f"Comando: {command}")
    print(f"============================================================")
    
    try:
        # No Windows, shell=True é crucial para comandos built-in e arquivos batch/scripts do npm
        result = subprocess.run(command, cwd=cwd, shell=True, text=True)
        if result.returncode == 0:
            print(f"[OK] Sucesso: {description}")
            return True
        else:
            print(f"[FALHA] Erro: {description} falhou com codigo de saida {result.returncode}")
            return False
    except Exception as e:
        print(f"[FALHA] Erro excepcional em {description}: {e}")
        return False

def get_git_changes():
    """
    Verifica se há alterações no Git e retorna uma lista de arquivos alterados (staged).
    """
    try:
        # Pega arquivos modificados (staged)
        result = subprocess.run(
            "git diff --name-only --cached", 
            shell=True, 
            capture_output=True, 
            text=True
        )
        files = [f.strip() for f in result.stdout.split('\n') if f.strip()]
        return files
    except Exception:
        return []

def run_push_deploy():
    root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '../..'))
    dashboard_dir = os.path.join(root_dir, 'dashboard')
    
    print("\n============================================================")
    print("INICIANDO PIPELINE DE AUTOMATIZACAO: PUSH & DEPLOY")
    print("============================================================\n")

    # 1. Compilação do Vite
    build_success = run_command(
        "npm run build", 
        cwd=dashboard_dir, 
        description="Compilacao Estatica do React (Vite Build)"
    )
    if not build_success:
        print("\n[BLOQUEADO] Falha na compilacao do Vite! Abortando deploy para proteger a producao.")
        sys.exit(1)

    # 2. Auditoria do Master Checklist de Qualidade do Antigravity
    # Nota: Executa o checklist.py na raiz do projeto
    quality_success = run_command(
        "python .agent/scripts/checklist.py .", 
        cwd=root_dir, 
        description="Auditoria do Master Checklist de Qualidade"
    )
    if not quality_success:
        print("\n[BLOQUEADO] O projeto nao passou nas auditorias de qualidade! Abortando deploy para proteger a producao.")
        sys.exit(1)

    # 3. Upload FTP para a Hostinger
    print("\n============================================================")
    print("Iniciando Upload FTP dos arquivos compilados para Hostinger...")
    print("============================================================")
    try:
        run_ftp_deploy()
        print("[OK] Upload FTP concluido com absoluto sucesso!")
    except Exception as e:
        print(f"\n[ERRO] Falha critica no upload FTP para Hostinger: {e}")
        sys.exit(1)

    # 4. Sincronização Autônoma com o GitHub
    print("\n============================================================")
    print("Iniciando Sincronizacao Git & GitHub...")
    print("============================================================")
    
    # Adicionar todos os arquivos alterados
    if not run_command("git add .", cwd=root_dir, description="Staging de arquivos no Git (git add .)"):
        print("[FALHA] Falha ao agendar arquivos no Git. Pulando commit/push.")
        sys.exit(1)

    # Identificar alterações para gerar mensagem automática inteligente
    changed_files = get_git_changes()
    
    if not changed_files:
        print("[INFO] Nenhuma alteracao de codigo detectada para commit no repositorio. Sincronizacao concluida!")
        print("\nPIPELINE CONCLUIDO COM SUCESSO ABSOLUTO! O site esta atualizado e seguro em producao.")
        return

    # Gerar mensagem inteligente limitando a exibição de arquivos se forem muitos
    if len(changed_files) <= 4:
        files_str = ", ".join([os.path.basename(f) for f in changed_files])
        commit_msg = f"feat(dashboard): auto-deploy - atualiza {files_str}"
    else:
        commit_msg = f"feat(dashboard): auto-deploy - atualiza {len(changed_files)} arquivos do sistema"

    print(f"Mensagem do Commit gerada: '{commit_msg}'")

    # Fazer o commit
    commit_success = run_command(
        f'git commit -m "{commit_msg}"', 
        cwd=root_dir, 
        description="Criando commit local do Git"
    )
    if not commit_success:
        print("[FALHA] Falha ao criar commit. Pode ser que nao existam mudancas reais.")
        sys.exit(1)

    # Executar o push
    push_success = run_command(
        "git push origin main", 
        cwd=root_dir, 
        description="Publicando alteracoes no GitHub (git push)"
    )
    if not push_success:
        print("[FALHA] Falha ao enviar commit para o GitHub. Verifique sua conexao ou conflitos remotos.")
        sys.exit(1)

    print("\n============================================================")
    print("PIPELINE DE PUSH & DEPLOY EXECUTADO COM SUCESSO ABSOLUTO!")
    print("Site no ar em: https://vilela.eng.br/applouvor/dashboard/")
    print("============================================================\n")

if __name__ == '__main__':
    run_push_deploy()
