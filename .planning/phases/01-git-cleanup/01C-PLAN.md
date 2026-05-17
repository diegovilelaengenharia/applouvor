---
phase: 01-git-cleanup
plan: 01C
type: execute
wave: 2
depends_on:
  - 01A
files_modified:
  - maintenance/debug_auth.php
  - maintenance/debug_roles.php
  - maintenance/debug_vocals.php
  - maintenance/exemplos_uso.php
  - maintenance/fix_mariana.php
  - maintenance/migrate_passwords.php
  - maintenance/temp_backup.txt
  - maintenance/temp_gen_keys.php
  - maintenance/restore_db.php
  - restore_db.php
  - ACESSAR_SISTEMA.bat
  - adicionar_hosts.bat
  - setup_local.ps1
autonomous: true
requirements:
  - GIT-03

must_haves:
  truths:
    - "maintenance/ contem todos os scripts de manutencao e esta commitado"
    - "restore_db.php existe em maintenance/ e NAO existe mais na raiz"
    - "ACESSAR_SISTEMA.bat, adicionar_hosts.bat e setup_local.ps1 estao commitados na raiz"
    - "git status mostra working tree clean apos os commits deste plano (combinado com 01A e 01B)"
  artifacts:
    - path: "maintenance/restore_db.php"
      provides: "Script de restauracao de banco no local correto"
    - path: "ACESSAR_SISTEMA.bat"
      provides: "Script de setup versionado na raiz"
    - path: "adicionar_hosts.bat"
      provides: "Script de setup versionado na raiz"
    - path: "setup_local.ps1"
      provides: "Script de setup versionado na raiz"
  key_links:
    - from: "restore_db.php (raiz)"
      to: "maintenance/restore_db.php"
      via: "git mv ou add+delete"
      pattern: "maintenance/restore_db.php"
---

<objective>
Commitar os scripts de manutencao organizados em maintenance/ e versionar os scripts de setup de ambiente na raiz do projeto.

Purpose: GIT-03 requer que scripts de manutencao estejam organizados em maintenance/ e commitados. Este plano finaliza a limpeza do repositorio: depois dele, combinado com 01A e 01B, o git status deve mostrar working tree clean.

Output: Dois commits — um para maintenance/ (incluindo restore_db.php movido da raiz) e um para os scripts de setup na raiz.
</objective>

<execution_context>
@$HOME/.claude/get-shit-done/workflows/execute-plan.md
@$HOME/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/phases/01-git-cleanup/01-CONTEXT.md
@.planning/phases/01-git-cleanup/01A-SUMMARY.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Commitar scripts de manutencao em maintenance/ (incluindo restore_db.php)</name>
  <files>
    maintenance/migrate_passwords.php
    maintenance/exemplos_uso.php
    maintenance/restore_db.php
    restore_db.php
  </files>
  <read_first>
    Verificar o estado atual dos arquivos de maintenance/:
      git status -- maintenance/
      ls maintenance/

    Arquivos a DELETAR (nao versionar — sao descartaveis):
      maintenance/debug_auth.php    (debug script pontual)
      maintenance/debug_roles.php   (debug script pontual)
      maintenance/debug_vocals.php  (debug script pontual)
      maintenance/temp_backup.txt   (backup textual sem proposito)
      maintenance/temp_gen_keys.php (gerador de chaves ja consumido)
      maintenance/fix_mariana.php   (fix pontual de usuario ja aplicado)

    Arquivos a VERSIONAR em maintenance/:
      maintenance/migrate_passwords.php  (auditoria historica de migração de senhas)
      maintenance/exemplos_uso.php       (documentacao por exemplo)
      maintenance/restore_db.php         (utilitario valido)

    Verificar se restore_db.php esta na raiz como untracked:
      git status -- restore_db.php

    Verificar se maintenance/restore_db.php JA existe:
      ls maintenance/restore_db.php 2>/dev/null || echo "NAO EXISTE"
  </read_first>
  <action>
    PASSO 1 — DELETAR os arquivos descartaveis de maintenance/ (NAO versionar):

    Remove-Item maintenance/debug_auth.php
    Remove-Item maintenance/debug_roles.php
    Remove-Item maintenance/debug_vocals.php
    Remove-Item maintenance/temp_backup.txt
    Remove-Item maintenance/temp_gen_keys.php
    Remove-Item maintenance/fix_mariana.php

    (Alternativa Bash: rm maintenance/debug_auth.php maintenance/debug_roles.php maintenance/debug_vocals.php maintenance/temp_backup.txt maintenance/temp_gen_keys.php maintenance/fix_mariana.php)

    PASSO 2 — Mover restore_db.php da raiz para maintenance/ (se ainda nao foi movido):

    Se maintenance/restore_db.php NAO existir:
      Move-Item restore_db.php maintenance/restore_db.php
    Se JA existir em maintenance/:
      Remove-Item restore_db.php

    PASSO 3 — Fazer staging dos arquivos validos de maintenance/:

    git add maintenance/migrate_passwords.php maintenance/exemplos_uso.php maintenance/restore_db.php

    PASSO 4 — CRITICO: stagear TAMBEM as delecoes dos arquivos antigos da raiz (tracked com status "D"):

    Os seguintes arquivos estavam na raiz como tracked e foram deletados do disco — o git ainda nao sabe:
      debug_auth.php, debug_roles.php, debug_vocals.php, exemplos_uso.php,
      fix_mariana.php, migrate_passwords.php, temp_backup.txt, temp_gen_keys.php

    git add -u
    # Este comando stageia TODAS as mudancas de arquivos ja rastreados (incluindo delecoes)
    # NAO adiciona arquivos novos (so os tracked deletados/modificados)

    PASSO 5 — Verificar staging antes do commit:
      git status

    Deve mostrar:
    - new file: maintenance/migrate_passwords.php
    - new file: maintenance/exemplos_uso.php
    - new file: maintenance/restore_db.php
    - deleted: debug_auth.php (e outros da lista acima)

    PASSO 6 — Commitar:
      git commit -m "chore(maintenance): organiza scripts de manutencao e remove descartaveis"

    IMPORTANTE: Scripts de SETUP (ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1) NAO entram neste commit — eles ficam na raiz e serao commitados na Task 2.
  </action>
  <verify>
    <automated>git log --oneline -1 | grep "chore(maintenance)"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -1` mostra: "chore(maintenance): organiza scripts de manutencao e remove descartaveis"
    - `git show --stat HEAD` lista: maintenance/migrate_passwords.php, maintenance/exemplos_uso.php, maintenance/restore_db.php (new files) + as delecoes da raiz (D debug_auth.php, D debug_roles.php, etc.)
    - `ls maintenance/restore_db.php` retorna o arquivo (existe no disco)
    - `ls maintenance/debug_auth.php 2>/dev/null` retorna vazio (foi deletado, nao versionado)
    - `ls restore_db.php 2>/dev/null` retorna vazio (NAO existe mais na raiz)
    - `git status -- maintenance/` nao mostra mais arquivos como untracked relevantes
  </acceptance_criteria>
  <done>Scripts de manutencao valiosos commitados; descartaveis deletados; delecoes da raiz stageiadas com git add -u; restore_db.php movido.</done>
</task>

<task type="auto">
  <name>Task 2: Versionar scripts de setup de ambiente na raiz</name>
  <files>
    ACESSAR_SISTEMA.bat
    adicionar_hosts.bat
    setup_local.ps1
  </files>
  <read_first>
    Verificar os 3 arquivos de setup:
      git status -- ACESSAR_SISTEMA.bat adicionar_hosts.bat setup_local.ps1

    Devem aparecer como "?? ACESSAR_SISTEMA.bat", "?? adicionar_hosts.bat", "?? setup_local.ps1".

    Ler brevemente cada arquivo para confirmar que:
    - Sao scripts de configuracao de ambiente local (nao scripts de producao)
    - Nao contem credenciais hardcoded (senhas, tokens de banco em texto claro)
    - ACESSAR_SISTEMA.bat: provavelmente abre o navegador ou o servidor local
    - adicionar_hosts.bat: provavelmente edita o arquivo hosts do Windows
    - setup_local.ps1: script PowerShell de configuracao do ambiente de dev

    Executar uma verificacao rapida:
      cat ACESSAR_SISTEMA.bat
      cat adicionar_hosts.bat
      cat setup_local.ps1
  </read_first>
  <action>
    PASSO 1 — Fazer staging dos 3 scripts de setup:
      git add ACESSAR_SISTEMA.bat adicionar_hosts.bat setup_local.ps1

    PASSO 2 — Verificar staging:
      git status

    Deve mostrar os 3 arquivos como "new file" no staged area.

    PASSO 3 — Commitar:
      git commit -m "chore(dev): versiona scripts de setup de ambiente local na raiz"

    PASSO 4 — Verificar que o working tree esta limpo:
      git status

    Apos este commit (combinado com 01A e 01B), o output de git status deve ser:
      "nothing to commit, working tree clean"

    Se ainda houver arquivos nao commitados, anotar no SUMMARY (podem ser arquivos do .gitignore ou arquivos inesperados).
  </action>
  <verify>
    <automated>git log --oneline -1 | grep "chore(dev): versiona scripts"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -1` mostra: "chore(dev): versiona scripts de setup de ambiente local na raiz"
    - `git show --stat HEAD` lista: ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1
    - `git status` mostra "nothing to commit, working tree clean" (ou apenas arquivos ignorados pelo .gitignore como App louvor 23.01.2026/ e desktop.ini que estao fisicamente no disco)
    - `git ls-files ACESSAR_SISTEMA.bat adicionar_hosts.bat setup_local.ps1` retorna os 3 arquivos (estao rastreados)
  </acceptance_criteria>
  <done>Scripts de setup versionados na raiz; working tree clean (Phase 1 completa).</done>
</task>

</tasks>

<verification>
Verificacao final de toda a Phase 1 (apos 01A + 01B + 01C):

  git status
  # Esperado: "nothing to commit, working tree clean"
  # (ou somente arquivos ignorados listados com --ignored)

  git log --oneline -9
  # Deve mostrar os commits da Phase 1 (7 commits no total):
  # chore(dev): versiona scripts de setup de ambiente local na raiz
  # chore(maintenance): organiza scripts de manutencao em maintenance/
  # chore(git): remove desktop.ini tracking e ignora pasta de backup antiga  [de 01B]
  # chore(dev): atualiza run_server.bat e design system MASTER.md            [de 01A]
  # chore(assets): atualiza design system CSS (variables, pib-cards, leitura) [de 01A]
  # feat(api): adiciona confirm_scale e atualiza reading_progress              [de 01A]
  # feat(includes): atualiza modulos compartilhados (auth, config, layout, dashboard) [de 01A]
  # feat(admin): atualiza paginas de admin com melhorias do Gemini CLI         [de 01A]

  ls maintenance/
  # Deve listar todos os 9 scripts: debug_auth.php, debug_roles.php,
  # debug_vocals.php, exemplos_uso.php, fix_mariana.php, migrate_passwords.php,
  # temp_backup.txt, temp_gen_keys.php, restore_db.php

  ls restore_db.php 2>/dev/null || echo "CORRETO: nao existe mais na raiz"
  # Deve printar "CORRETO: nao existe mais na raiz"

  git ls-files "*.ini"
  # Deve retornar VAZIO
</verification>

<success_criteria>
- `git status` mostra working tree clean
- maintenance/ contem 9 scripts de manutencao commitados
- restore_db.php NAO existe na raiz do projeto
- ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1 rastreados na raiz
- GIT-03 cumprido: scripts de manutencao organizados em maintenance/ e commitados
- Phase 1 completa: todos GIT-01, GIT-02, GIT-03 satisfeitos
</success_criteria>

<output>
Apos conclusao, criar `.planning/phases/01-git-cleanup/01C-SUMMARY.md` com:
- Commits criados (hash + mensagem)
- Confirmacao: ls maintenance/ listando os 9 arquivos
- Confirmacao: git status = working tree clean
- Quaisquer arquivos inesperados encontrados durante a execucao
</output>
