---
phase: 01-git-cleanup
plan: 01D
type: execute
wave: 3
depends_on:
  - 01B
  - 01C
files_modified:
  - .env.production
  - .env.backup
  - includes/vapid_config.php
  - deploy.php
  - test_all_notifications.php
  - test_db_query.php
  - test_members_count.php
  - restore_db.php
  - run_migrations.php
  - reset_password.php
  - setup_database.php
  - setup_avisos.php
  - setup_devotionals.php
  - setup_prayers.php
  - setup_reading_db.php
  - update_avatar_db.php
  - create_table_prefs.php
  - upgrade_avisos.php
  - importar_musicas_excel.php
  - importar_musicas_manual.php
  - importar_musicas_simples.php
autonomous: true
requirements:
  - GIT-01
  - GIT-03

must_haves:
  truths:
    - "git ls-files nao mostra .env.production, .env.backup ou includes/vapid_config.php"
    - "deploy.php nao existe mais na raiz"
    - "test_*.php nao existem na raiz"
    - "Scripts administrativos movidos para maintenance/"
    - "SUMMARY documenta roteiro manual para rotacao de senha do banco e VAPID"
  artifacts:
    - path: ".planning/phases/01-git-cleanup/01D-SUMMARY.md"
      provides: "Roteiro passo-a-passo para rotacao de senha e VAPID (execucao manual pelo Diego)"
  key_links:
    - from: "git ls-files"
      to: ".env.production/.env.backup/vapid_config.php"
      via: "git rm --cached"
      pattern: "nenhum resultado"
---

<objective>
Remover credenciais de producao do tracking git, deletar/mover scripts administrativos perigosos da raiz, e documentar o roteiro manual para rotacao de senha do banco e chaves VAPID.

Purpose: .env.production (DB_PASS=Diego@159753), .env.backup e includes/vapid_config.php (chave privada VAPID) estao versionados no git. Qualquer pessoa com acesso ao repo tem as credenciais de producao. Scripts como deploy.php, test_all_notifications.php e restore_db.php estao na raiz sem auth, acessiveis publicamente. Diego viaja em breve — deixar esse estado e um risco real para os dados do ministerio (fotos, aniversarios, indisponibilidades dos membros).

Output: Secrets removidos do tracking git + .gitignore protegendo essas rotas para o futuro + scripts perigosos removidos da raiz + SUMMARY com roteiro manual para o Diego fechar o ciclo de seguranca.
</objective>

<execution_context>
@$HOME/.claude/get-shit-done/workflows/execute-plan.md
@$HOME/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/phases/01-git-cleanup/01-CONTEXT.md
@.planning/phases/01-git-cleanup/01B-SUMMARY.md
@.planning/phases/01-git-cleanup/01C-SUMMARY.md
</context>

<tasks>

<task type="auto">
  <name>Task D1: Remover secrets do tracking git</name>
  <files>
    .env.production
    .env.backup
    includes/vapid_config.php
  </files>
  <read_first>
    Confirmar quais desses arquivos estao atualmente rastreados:
      git ls-files .env.production .env.backup includes/vapid_config.php

    Resultado esperado: os 3 arquivos listados (estao no index git).

    Verificar que o .gitignore JA contem as entradas de protecao (adicionadas no 01B):
      grep -E "\.env\.\*|vapid_config" .gitignore

    Deve retornar entradas como ".env.*" e "includes/vapid_config.php".
    Se NAO retornar: o 01B nao foi executado ainda — verificar dependencias.
  </read_first>
  <action>
    PASSO 1 — Remover os 3 arquivos do git index (mantendo no disco):

    git rm --cached .env.production
    git rm --cached .env.backup
    git rm --cached includes/vapid_config.php

    Se algum retornar "did not match any files": esse arquivo especifico nao estava rastreado — ignorar e continuar.

    PASSO 2 — Verificar o staging:
      git status

    Deve mostrar as 3 delecoes como staged (prefixo "D" no index).

    PASSO 3 — Commitar:
      git commit -m "chore(security): remove credenciais de producao do tracking git"

    NENHUM outro arquivo deve entrar neste commit.
  </action>
  <verify>
    <automated>git ls-files .env.production .env.backup includes/vapid_config.php</automated>
  </verify>
  <acceptance_criteria>
    - `git ls-files .env.production .env.backup includes/vapid_config.php` retorna VAZIO
    - `git log --oneline -1` mostra: "chore(security): remove credenciais de producao do tracking git"
    - `git show --stat HEAD` mostra os 3 arquivos com "D" (deleted from index)
    - Os arquivos AINDA EXISTEM no disco (ls .env.production deve retornar o arquivo)
    - O .gitignore protege esses caminhos (grep ".env.*" .gitignore retorna resultado)
    - ATENCAO: A senha Diego@159753 continua no historico git — sera revogada pela rotacao manual (Task D4 no SUMMARY)
  </acceptance_criteria>
  <done>Secrets removidos do tracking; .gitignore protege o futuro; historico antigo sera revogado pela rotacao de senha.</done>
</task>

<task type="auto">
  <name>Task D2: Deletar deploy.php e scripts de test da raiz</name>
  <files>
    deploy.php
    test_all_notifications.php
    test_db_query.php
    test_members_count.php
  </files>
  <read_first>
    Verificar quais desses arquivos existem e estao rastreados:
      git ls-files deploy.php test_all_notifications.php test_db_query.php test_members_count.php
      ls deploy.php test_all_notifications.php test_db_query.php test_members_count.php 2>/dev/null

    deploy.php: endpoint publico com secret hardcoded 'louvor2026' que executa git pull.
    test_all_notifications.php: dispara push para TODOS os usuarios sem auth.
    test_db_query.php: executa queries SQL sem auth.
    test_members_count.php: expoe dados sem auth.

    Nenhum tem valor para producao. Todos devem ser deletados.
  </read_first>
  <action>
    PASSO 1 — Verificar se estao rastreados (alguns podem ser untracked se nunca commitados):
      git ls-files deploy.php

    Se rastreados: usar git rm (remove do disco E do index)
      git rm deploy.php
      git rm test_all_notifications.php 2>/dev/null || true
      git rm test_db_query.php 2>/dev/null || true
      git rm test_members_count.php 2>/dev/null || true

    Se nao rastreados (untracked): deletar do disco manualmente:
      Remove-Item deploy.php -ErrorAction SilentlyContinue
      Remove-Item test_all_notifications.php -ErrorAction SilentlyContinue
      Remove-Item test_db_query.php -ErrorAction SilentlyContinue
      Remove-Item test_members_count.php -ErrorAction SilentlyContinue

    PASSO 2 — Verificar status:
      git status -- deploy.php test_all_notifications.php test_db_query.php test_members_count.php

    PASSO 3 — Commitar (apenas se algum estava rastreado — se todos eram untracked, a delecao do disco nao precisa de commit):
      git add deploy.php test_all_notifications.php test_db_query.php test_members_count.php
      git commit -m "chore(security): remove deploy.php e scripts de test da raiz (endpoints publicos sem auth)"

    Se NENHUM estava rastreado: nao ha commit necessario para este passo — apenas confirmar que foram deletados do disco.
  </action>
  <verify>
    <automated>ls deploy.php 2>/dev/null || echo "CORRETO: deploy.php nao existe"</automated>
  </verify>
  <acceptance_criteria>
    - `ls deploy.php 2>/dev/null` retorna vazio / erro (arquivo nao existe no disco)
    - `ls test_all_notifications.php 2>/dev/null` retorna vazio
    - `ls test_db_query.php 2>/dev/null` retorna vazio
    - `git ls-files deploy.php test_*.php` retorna VAZIO (nao rastreados)
  </acceptance_criteria>
  <done>deploy.php e scripts de test deletados da raiz e do tracking.</done>
</task>

<task type="auto">
  <name>Task D3: Mover scripts administrativos da raiz para maintenance/</name>
  <files>
    run_migrations.php
    reset_password.php
    setup_database.php
    setup_avisos.php
    setup_devotionals.php
    setup_prayers.php
    setup_reading_db.php
    update_avatar_db.php
    create_table_prefs.php
    upgrade_avisos.php
    importar_musicas_excel.php
    importar_musicas_manual.php
    importar_musicas_simples.php
  </files>
  <read_first>
    Verificar quais desses arquivos existem na raiz:
      git ls-files run_migrations.php reset_password.php setup_database.php setup_avisos.php setup_devotionals.php setup_prayers.php setup_reading_db.php update_avatar_db.php create_table_prefs.php upgrade_avisos.php importar_musicas_excel.php importar_musicas_manual.php importar_musicas_simples.php

    Verificar quais sao untracked (??):
      git status -- run_migrations.php reset_password.php setup_database.php

    Os que ja estao em maintenance/ (podem ter sido movidos anteriormente) — pular.
  </read_first>
  <action>
    PASSO 1 — Mover cada arquivo da raiz para maintenance/ (se existir na raiz):

    $files = @("run_migrations.php", "reset_password.php", "setup_database.php", "setup_avisos.php", "setup_devotionals.php", "setup_prayers.php", "setup_reading_db.php", "update_avatar_db.php", "create_table_prefs.php", "upgrade_avisos.php", "importar_musicas_excel.php", "importar_musicas_manual.php", "importar_musicas_simples.php")

    foreach ($f in $files) {
      if (Test-Path $f) { Move-Item $f maintenance/$f }
    }

    (Alternativa Bash: for f in run_migrations.php reset_password.php setup_database.php setup_avisos.php setup_devotionals.php setup_prayers.php setup_reading_db.php update_avatar_db.php create_table_prefs.php upgrade_avisos.php importar_musicas_excel.php importar_musicas_manual.php importar_musicas_simples.php; do [ -f "$f" ] && mv "$f" maintenance/; done)

    PASSO 2 — Stagear todos os novos arquivos em maintenance/:
      git add maintenance/run_migrations.php maintenance/reset_password.php maintenance/setup_database.php maintenance/setup_avisos.php maintenance/setup_devotionals.php maintenance/setup_prayers.php maintenance/setup_reading_db.php maintenance/update_avatar_db.php maintenance/create_table_prefs.php maintenance/upgrade_avisos.php maintenance/importar_musicas_excel.php maintenance/importar_musicas_manual.php maintenance/importar_musicas_simples.php 2>/dev/null || true

    PASSO 3 — Stagear as delecoes/movimentos dos arquivos que estavam rastreados na raiz:
      git add -u

    PASSO 4 — Verificar staging:
      git status

    PASSO 5 — Commitar (se houver changes staged):
      git commit -m "chore(security): move scripts administrativos para maintenance/ (nao acessiveis publicamente)"
  </action>
  <verify>
    <automated>ls run_migrations.php 2>/dev/null || echo "CORRETO: nao existe na raiz"</automated>
  </verify>
  <acceptance_criteria>
    - `ls run_migrations.php 2>/dev/null` retorna vazio (nao existe mais na raiz)
    - `ls reset_password.php 2>/dev/null` retorna vazio
    - `ls maintenance/run_migrations.php` retorna o arquivo (existe em maintenance/)
    - `git ls-files run_migrations.php` retorna VAZIO (nao rastreado na raiz)
    - `git status` nao mostra nenhum desses arquivos na raiz como modified/untracked
  </acceptance_criteria>
  <done>Scripts administrativos movidos para maintenance/; raiz limpa de endpoints perigosos.</done>
</task>

<task type="auto">
  <name>Task D4: Limpar .git/refs/desktop.ini (lixo do Google Drive)</name>
  <files>
    (somente .git/ — nao e um arquivo versionado)
  </files>
  <read_first>
    Verificar se o lixo existe:
      ls .git/refs/desktop.ini 2>/dev/null && echo "EXISTE (remover)" || echo "NAO EXISTE"
      ls .git/refs/heads/desktop.ini 2>/dev/null && echo "EXISTE (remover)" || echo "NAO EXISTE"
  </read_first>
  <action>
    Remover os arquivos desktop.ini dentro do .git/:

    Get-ChildItem .git -Recurse -Force -Filter "desktop.ini" | Remove-Item -Force

    (Alternativa Bash: find .git -name "desktop.ini" -delete)

    Este comando NAO cria commit — .git/ e interno ao git e nao versionado.

    Verificar resultado:
      git fsck --no-dangling 2>&1 | head -5
      ls .git/refs/ | cat
  </action>
  <verify>
    <automated>Get-ChildItem .git -Recurse -Force -Filter "desktop.ini" | Measure-Object | Select-Object -ExpandProperty Count</automated>
  </verify>
  <acceptance_criteria>
    - Nenhum arquivo desktop.ini existe dentro de .git/ (Get-ChildItem retorna 0)
    - `git log --oneline -1` ainda funciona normalmente (git nao foi corrompido)
    - `git status` funciona sem erros
  </acceptance_criteria>
  <done>Lixo do Google Drive Drive File Stream removido do .git/; repositorio git mais limpo.</done>
</task>

<task type="auto">
  <name>Task D5: Criar SUMMARY com roteiros manuais de seguranca para o Diego</name>
  <files>
    .planning/phases/01-git-cleanup/01D-SUMMARY.md
  </files>
  <read_first>
    Verificar estado final apos as tasks D1-D4:
      git ls-files .env.production .env.backup includes/vapid_config.php
      git log --oneline -5
      git status
      ls deploy.php test_all_notifications.php run_migrations.php 2>/dev/null | wc -l
  </read_first>
  <action>
    Criar .planning/phases/01-git-cleanup/01D-SUMMARY.md com:

    1. Commits criados (hash + mensagem) de cada task
    2. Confirmacoes de estado final (git ls-files, ls na raiz)
    3. ROTEIRO MANUAL para o Diego executar (sao acoes fora do controle do agente):

    --- ROTEIRO MANUAL: ROTACAO DE SENHA DO BANCO DE DADOS ---
    Contexto: DB_PASS=Diego@159753 estava em .env.production versionado no git.
    A senha foi exposta no historico do git. Mesmo apos o git rm --cached, o historico
    contem a senha — rotacionar invalida o vazamento.

    Passos:
    1. Logar no painel Hostinger (hpanel.hostinger.com)
    2. Menu: Hospedagem > MySQL Databases
    3. Localizar banco 'u884436813_applouvor', usuario 'u884436813_admin'
    4. Clicar em "Change Password" — gerar senha forte (min 24 chars, mistura letras+numeros+simbolos)
    5. Anotar a nova senha em local seguro (bitwarden, 1password, etc.)
    6. Acessar Hostinger File Manager > public_html/applouvor/.htaccess
    7. Trocar a linha: SetEnv DB_PASS Diego@159753
       Pela nova senha: SetEnv DB_PASS <NOVA_SENHA>
    8. Salvar o .htaccess
    9. Testar: abrir vilela.eng.br/applouvor — fazer login — dashboard deve carregar
    10. Atualizar .env.production LOCAL com a nova senha (para referencia offline) — NAO commitar

    --- ROTEIRO MANUAL: REGENERAR CHAVES VAPID ---
    Contexto: Chave privada VAPID exposta em includes/vapid_config.php (agora gitignored).
    Regenerar invalida a chave antiga — notificacoes push precisarao de re-subscribe.

    Passos:
    1. Gerar novas chaves (escolher um metodo):
       Opcao A - Node.js local: npx web-push generate-vapid-keys
       Opcao B - Browser: https://vapidkeys.com/ (pagina simples, nao envia dados)
    2. Salvar as novas chaves em local seguro
    3. Atualizar includes/vapid_config.php local com as novas chaves
    4. Upload via FTP/File Manager para public_html/applouvor/includes/vapid_config.php
    5. (Opcional mas recomendado) Limpar tabela push_subscriptions no banco — usuarios
       vao re-subscribe automaticamente quando abrirem o app
    6. Testar: acessar app no celular, permitir notificacoes, verificar nova entrada
       em push_subscriptions no banco

    --- ROTEIRO MANUAL: PROTEGER maintenance/ EM PRODUCAO ---
    Os scripts em maintenance/ foram movidos para la, mas ainda podem ser acessiveis
    em vilela.eng.br/applouvor/maintenance/<arquivo>.php

    Passos:
    1. Via Hostinger File Manager, criar/editar public_html/applouvor/maintenance/.htaccess
    2. Conteudo do arquivo:
       Require all denied
    3. Salvar
    4. Testar: acessar vilela.eng.br/applouvor/maintenance/restore_db.php
       Deve retornar 403 Forbidden

    4. Verificar estado final do git:
       git log --oneline -8
       git status
       git ls-files | grep -E "\.env|vapid_config"
  </action>
  <verify>
    <automated>ls .planning/phases/01-git-cleanup/01D-SUMMARY.md</automated>
  </verify>
  <acceptance_criteria>
    - Arquivo 01D-SUMMARY.md existe com conteudo relevante
    - SUMMARY lista os commits criados em D1-D3
    - SUMMARY contem os 3 roteiros manuais completos (senha do banco, VAPID, maintenance/.htaccess)
    - SUMMARY confirma: git ls-files nao retorna .env.production, .env.backup, vapid_config.php
  </acceptance_criteria>
  <done>SUMMARY criado com roteiros manuais; Phase 1 com hardening completo.</done>
</task>

</tasks>

<verification>
Verificacao final deste plano (01D):

  git ls-files .env.production .env.backup includes/vapid_config.php
  # Deve retornar VAZIO

  git ls-files deploy.php test_all_notifications.php test_db_query.php test_members_count.php
  # Deve retornar VAZIO

  ls deploy.php test_all_notifications.php 2>/dev/null | wc -l
  # Deve retornar 0

  ls maintenance/run_migrations.php maintenance/reset_password.php
  # Deve retornar os arquivos (existem em maintenance/)

  grep -E "\.env\.\*|vapid_config" .gitignore
  # Deve retornar as linhas de protecao

  git log --oneline -5
  # Deve mostrar os commits de seguranca criados neste plano

  cat .planning/phases/01-git-cleanup/01D-SUMMARY.md | head -5
  # Deve existir com conteudo
</verification>

<success_criteria>
- git ls-files nao mostra .env.production, .env.backup ou includes/vapid_config.php
- deploy.php, test_all_notifications.php, test_db_query.php, test_members_count.php nao existem na raiz
- Scripts administrativos movidos para maintenance/
- SUMMARY documenta roteiros manuais completos para o Diego fechar o ciclo de seguranca
- .git/refs sem arquivos desktop.ini
- GIT-01 e GIT-03 reconfirmados; base segura para desenvolvimento e viagem
</success_criteria>

<output>
Criar `.planning/phases/01-git-cleanup/01D-SUMMARY.md` com:
- Commits criados (hash + mensagem) de cada task D1-D3
- Estado final verificado: git ls-files, ls na raiz, git status
- Roteiros manuais completos para rotacao de senha do banco, VAPID e maintenance/.htaccess
- Checklist de "pronto para a viagem" — Diego confere antes de sair
</output>
