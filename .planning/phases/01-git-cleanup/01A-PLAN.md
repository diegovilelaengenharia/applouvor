---
phase: 01-git-cleanup
plan: 01A
type: execute
wave: 1
depends_on: []
files_modified:
  - admin/dashboard_data.php
  - admin/escalas.php
  - admin/index.php
  - admin/leitura.php
  - admin/membros.php
  - admin/repertorio.php
  - admin/sidebar.php
  - admin/sql/create_reading_tables.sql
  - api/reading_progress.php
  - api/confirm_scale.php
  - assets/css/core/variables.css
  - assets/css/components/pib-cards.css
  - assets/css/pages/leitura.css
  - includes/auth.php
  - includes/config.php
  - includes/dashboard_render.php
  - includes/layout.php
  - design-system/app-louvor/MASTER.md
  - run_server.bat
autonomous: true
requirements:
  - GIT-01

must_haves:
  truths:
    - "Mudancas de admin/ estao em um unico commit semantico no historico"
    - "Mudancas de includes/ estao em um unico commit semantico no historico"
    - "Mudancas de api/ e novos endpoints estao em commits semanticos no historico"
    - "Mudancas de assets/css/ estao em um unico commit semantico no historico"
    - "run_server.bat e design-system/ estao commitados"
    - "Nenhum commit contem desktop.ini (esses ficam para 01B)"
  artifacts:
    - path: ".git/COMMIT_EDITMSG"
      provides: "Ultimo commit da sequencia do plano"
  key_links:
    - from: "git log --oneline"
      to: "5+ commits novos"
      via: "git commit por area"
      pattern: "feat\\(admin\\)|feat\\(includes\\)|feat\\(api\\)|chore\\(assets\\)|chore\\(run_server\\)"
---

<objective>
Commitar as mudancas do estado atual do projeto (feitas pelo Gemini CLI) em commits semanticos agrupados por area/modulo. Cada commit deve representar uma area logica do codebase, facilitando o historico e o code review.

Purpose: Estabelecer um historico git limpo e semantico antes de qualquer desenvolvimento novo. Sem este plano, 33+ arquivos modificados ficam como um bloco amorfo impossivel de revisar.

Output: 5 commits semanticos no historico git cobrindo admin/, includes/, api/, assets/css/, e utilitarios.
</objective>

<execution_context>
@$HOME/.claude/get-shit-done/workflows/execute-plan.md
@$HOME/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@.planning/PROJECT.md
@.planning/ROADMAP.md
@.planning/STATE.md
@.planning/phases/01-git-cleanup/01-CONTEXT.md
</context>

<tasks>

<task type="auto">
  <name>Task 1: Revisar e commitar mudancas de admin/</name>
  <files>
    admin/dashboard_data.php
    admin/escalas.php
    admin/index.php
    admin/leitura.php
    admin/membros.php
    admin/repertorio.php
    admin/sidebar.php
    admin/sql/create_reading_tables.sql
  </files>
  <read_first>
    Antes de qualquer staging, executar:
      git diff HEAD -- admin/dashboard_data.php admin/escalas.php admin/index.php admin/leitura.php admin/membros.php admin/repertorio.php admin/sidebar.php admin/sql/create_reading_tables.sql

    Ler a saida completa do diff. Confirmar que:
    - Nao ha credenciais expostas (senhas, tokens)
    - Nao ha codigo comentado suspeito ou debug code de teste (var_dump, die(), echo "test")
    - As mudancas sao melhorias de feature ou correcoes (nao lixo do Gemini CLI)

    Se encontrar algo suspeito: anotar no SUMMARY mas commitar assim mesmo (a decisao de reverter e do usuario depois).
  </read_first>
  <action>
    Apos revisar o diff e confirmar que nao ha lixo:

    1. Fazer staging dos 8 arquivos de admin/ (excluir desktop.ini que sera tratado no 01B):
       git add admin/dashboard_data.php admin/escalas.php admin/index.php admin/leitura.php admin/membros.php admin/repertorio.php admin/sidebar.php admin/sql/create_reading_tables.sql

    2. Commitar com a mensagem exata:
       git commit -m "feat(admin): atualiza paginas de admin com melhorias do Gemini CLI"

    Nota: admin/desktop.ini NAO deve ser incluido — ele sera tratado no plano 01B.
  </action>
  <verify>
    <automated>git log --oneline -1 | grep "feat(admin)"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -1` mostra commit com mensagem "feat(admin): atualiza paginas de admin com melhorias do Gemini CLI"
    - `git show --stat HEAD` lista exatamente estes arquivos: dashboard_data.php, escalas.php, index.php, leitura.php, membros.php, repertorio.php, sidebar.php, sql/create_reading_tables.sql (todos prefixados com admin/)
    - `git show --stat HEAD` NAO lista desktop.ini
    - `git status -- admin/` mostra apenas "admin/desktop.ini" como modificado (os outros estao limpos)
  </acceptance_criteria>
  <done>8 arquivos de admin/ commitados; admin/desktop.ini permanece fora do staging.</done>
</task>

<task type="auto">
  <name>Task 2: Revisar e commitar mudancas de includes/</name>
  <files>
    includes/auth.php
    includes/config.php
    includes/dashboard_render.php
    includes/layout.php
  </files>
  <read_first>
    Antes de staging, executar:
      git diff HEAD -- includes/auth.php includes/config.php includes/dashboard_render.php includes/layout.php

    Verificar:
    - includes/config.php: confirmar que nao expoe credenciais de banco em texto claro
    - includes/auth.php: confirmar que logica de autenticacao esta intacta (nao ha regressoes)
    - Nao ha debug code (var_dump, print_r, die())

    IMPORTANTE: includes/desktop.ini NAO deve ser incluido no staging.
  </read_first>
  <action>
    Apos revisar o diff:

    1. Fazer staging apenas dos 4 arquivos PHP (excluir includes/desktop.ini):
       git add includes/auth.php includes/config.php includes/dashboard_render.php includes/layout.php

    2. Commitar:
       git commit -m "feat(includes): atualiza modulos compartilhados (auth, config, layout, dashboard)"
  </action>
  <verify>
    <automated>git log --oneline -1 | grep "feat(includes)"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -1` mostra commit com mensagem "feat(includes): atualiza modulos compartilhados (auth, config, layout, dashboard)"
    - `git show --stat HEAD` lista exatamente: includes/auth.php, includes/config.php, includes/dashboard_render.php, includes/layout.php
    - `git show --stat HEAD` NAO lista includes/desktop.ini
    - `git status -- includes/` mostra apenas "includes/desktop.ini" como modificado restante
  </acceptance_criteria>
  <done>4 arquivos de includes/ commitados; includes/desktop.ini permanece fora do staging.</done>
</task>

<task type="auto">
  <name>Task 3: Revisar e commitar mudancas de api/ e assets/css/</name>
  <files>
    api/reading_progress.php
    api/confirm_scale.php
    assets/css/core/variables.css
    assets/css/components/pib-cards.css
    assets/css/pages/leitura.css
  </files>
  <read_first>
    Executar dois diffs separados:

    1. Para api/:
       git diff HEAD -- api/reading_progress.php
       (api/confirm_scale.php e api/confirm_scale.php sao novos — nao tem diff HEAD, apenas existem como untracked)

    2. Para assets/css/:
       git diff HEAD -- assets/css/core/variables.css assets/css/pages/leitura.css
       (assets/css/components/pib-cards.css e novo — untracked)

    Verificar:
    - api/reading_progress.php: nao ha credenciais hardcoded, retorna JSON valido
    - api/confirm_scale.php: novo arquivo, verificar que nao expoe dados sensiveis
    - variables.css: mudancas sao de variaveis CSS (nao codigo PHP misturado)
    - pib-cards.css: novo componente de card

    assets/css/desktop.ini NAO deve entrar no staging.
  </read_first>
  <action>
    Dois commits separados:

    COMMIT 1 — API:
    git add api/reading_progress.php api/confirm_scale.php
    git commit -m "feat(api): adiciona confirm_scale e atualiza reading_progress"

    COMMIT 2 — CSS:
    git add assets/css/core/variables.css assets/css/components/pib-cards.css assets/css/pages/leitura.css
    git commit -m "chore(assets): atualiza design system CSS (variables, pib-cards, leitura)"

    IMPORTANTE: assets/desktop.ini, assets/css/desktop.ini, assets/images/desktop.ini, assets/js/desktop.ini NAO entram em nenhum destes commits.
  </action>
  <verify>
    <automated>git log --oneline -3 | grep -c "feat(api)\|chore(assets)"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -3` mostra dois commits recentes: um com "feat(api)" e outro com "chore(assets)"
    - `git show --stat HEAD~1` lista: api/reading_progress.php e api/confirm_scale.php
    - `git show --stat HEAD` lista: assets/css/core/variables.css, assets/css/components/pib-cards.css, assets/css/pages/leitura.css
    - Nenhum destes dois commits contem qualquer desktop.ini
    - `git status -- assets/css/` mostra apenas "assets/css/desktop.ini" como modificado restante
  </acceptance_criteria>
  <done>2 commits: api/ e assets/css/ commitados sem desktop.ini files.</done>
</task>

<task type="auto">
  <name>Task 4: Commitar utilitarios (run_server.bat e design-system/)</name>
  <files>
    run_server.bat
    design-system/app-louvor/MASTER.md
  </files>
  <read_first>
    Executar:
      git diff HEAD -- run_server.bat "design-system/app-louvor/MASTER.md"

    Verificar:
    - run_server.bat: confirmar que o servidor PHP esta configurado corretamente (porta, diretorio)
    - MASTER.md: e documentacao do design system (nao codigo executavel)
  </read_first>
  <action>
    Um commit para os dois arquivos utilitarios:

    git add run_server.bat "design-system/app-louvor/MASTER.md"
    git commit -m "chore(dev): atualiza run_server.bat e design system MASTER.md"
  </action>
  <verify>
    <automated>git log --oneline -1 | grep "chore(dev)"</automated>
  </verify>
  <acceptance_criteria>
    - `git log --oneline -1` mostra "chore(dev): atualiza run_server.bat e design system MASTER.md"
    - `git show --stat HEAD` lista: run_server.bat e design-system/app-louvor/MASTER.md
    - `git status` nao mostra mais run_server.bat ou design-system/ como modificados
  </acceptance_criteria>
  <done>run_server.bat e MASTER.md commitados.</done>
</task>

</tasks>

<verification>
Apos todos os 5 commits deste plano:

git log --oneline -5

Deve mostrar (em ordem do mais recente para o mais antigo):
1. chore(dev): atualiza run_server.bat e design system MASTER.md
2. chore(assets): atualiza design system CSS (variables, pib-cards, leitura)
3. feat(api): adiciona confirm_scale e atualiza reading_progress
4. feat(includes): atualiza modulos compartilhados (auth, config, layout, dashboard)
5. feat(admin): atualiza paginas de admin com melhorias do Gemini CLI

git status deve ainda mostrar como pendentes (para os proximos planos):
- desktop.ini files (varios diretorios) — cobertos por 01B
- maintenance/ files (untracked) — cobertos por 01C
- ACESSAR_SISTEMA.bat, adicionar_hosts.bat, setup_local.ps1 — cobertos por 01C
- restore_db.php — coberto por 01C
</verification>

<success_criteria>
- `git log --oneline -5` mostra exatamente os 5 commits com os prefixos semanticos corretos
- Nenhum dos 5 commits contem desktop.ini
- Os arquivos de admin/, includes/, api/, assets/css/, run_server.bat e design-system/ estao limpos no git status
- GIT-01 cumprido: estado atual commitado de forma organizada com mensagens semanticas
</success_criteria>

<output>
Apos conclusao, criar `.planning/phases/01-git-cleanup/01A-SUMMARY.md` com:
- Commits criados (hash + mensagem)
- Qualquer arquivo suspeito encontrado no diff review
- Estado do git status ao final deste plano
</output>
