import os
import sys
from ftplib import FTP

FTP_HOST = "147.93.64.217"
FTP_USER = "u884436813"
FTP_PASS = "Diego@159753"
REMOTE_BASE_PATH = "/applouvor/dashboard"

def ensure_remote_dir(ftp, remote_dir):
    """
    Garante a existência de um diretório no servidor FTP, criando-o recursivamente se necessário.
    """
    parts = [p for p in remote_dir.split('/') if p]
    current = ""
    for part in parts:
        current = f"{current}/{part}"
        try:
            ftp.mkd(current)
            print(f"Diretório criado: {current}")
        except Exception:
            # Diretório provavelmente já existe
            pass

def deploy():
    # Caminho local da pasta dist
    local_dist = os.path.abspath(os.path.join(os.path.dirname(__file__), '../../dashboard/dist'))
    if not os.path.exists(local_dist):
        print(f"[ERRO] Pasta local de build '{local_dist}' não existe. Execute o build primeiro.")
        sys.exit(1)

    print("=== Conectando ao Servidor FTP Hostinger (Credenciais Atualizadas) ===")
    print(f"Host: {FTP_HOST}")
    print(f"Usuário: {FTP_USER}")
    
    try:
        ftp = FTP(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        # Habilita o modo passivo que é crucial para passar por firewalls e NATs
        ftp.set_pasv(True)
        print("Conectado com sucesso por FTP!")
    except Exception as e:
        print(f"[ERRO] Falha ao se conectar/autenticar no FTP: {e}")
        sys.exit(1)

    # Garante a pasta de destino
    print(f"\nGarantindo diretório remoto: {REMOTE_BASE_PATH}")
    ensure_remote_dir(ftp, REMOTE_BASE_PATH)
    ensure_remote_dir(ftp, f"{REMOTE_BASE_PATH}/assets")

    success_count = 0
    error_count = 0

    # Varre a pasta local e faz upload de todos os arquivos
    for root, dirs, files in os.walk(local_dist):
        for file in files:
            local_file_path = os.path.join(root, file)
            # Determina o caminho relativo em relação à pasta dist
            rel_path = os.path.relpath(local_file_path, local_dist).replace('\\', '/')
            
            # Se for o index.html gerado pelo build do Vite, salvamos no servidor como index.prod.html
            # Isso impede que o git pull do webhook da Hostinger o sobrescreva com a versão de desenvolvimento.
            if rel_path == "index.html":
                remote_file_path = f"{REMOTE_BASE_PATH}/index.prod.html"
            else:
                remote_file_path = f"{REMOTE_BASE_PATH}/{rel_path}"

            # Garante o diretório do arquivo remoto (caso existam subpastas profundas)
            remote_file_dir = os.path.dirname(remote_file_path)
            ensure_remote_dir(ftp, remote_file_dir)

            print(f"Enviando: {rel_path} -> {remote_file_path} ...", end="", flush=True)

            try:
                with open(local_file_path, 'rb') as f:
                    ftp.storbinary(f"STOR {remote_file_path}", f)
                print(" [OK]")
                success_count += 1
            except Exception as e:
                print(f" [ERRO: {e}]")
                error_count += 1

    try:
        ftp.quit()
    except Exception:
        ftp.close()

    print("\n=== Resumo do Deploy ===")
    print(f"Sucesso: {success_count} arquivos")
    print(f"Erros: {error_count} arquivos")

    if success_count > 0 and error_count == 0:
        print("\nDeploy do React SPA concluído com absoluto sucesso via FTP!")
        print("Acesse em: https://vilela.eng.br/applouvor/dashboard/")
    else:
        print("\nDeploy concluído com alguns avisos/erros. Revise os logs acima.")

if __name__ == '__main__':
    deploy()
