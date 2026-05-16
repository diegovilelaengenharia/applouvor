---
phase: 01-git-cleanup
plan: 01B
type: execute
wave: 2
depends_on:
  - 01A
files_modified:
  - .gitignore
  - desktop.ini
  - admin/desktop.ini
  - assets/desktop.ini
  - assets/css/desktop.ini
  - assets/images/desktop.ini
  - assets/js/desktop.ini
  - includes/desktop.ini
  - "banco de dados/desktop.ini"
autonomous: true
requirements:
  - GIT-02

must_haves:
  truths:
    - "Nenhum desktop.ini aparece em 'git status' apos o commit"
    - ".gitignore contem entrada para 'App louvor 23.01.2026/' (pasta de backup ignorada)"
    - "git ls-files nao retorna nenhum arquivo desktop.ini"
    - "Existe um commit dedicado exclusivo para remocao do desktop.ini tracking"
  artifacts:
    - path: ".gitignore"
      provides: "Protecao futura contra retracking de desktop.ini e pasta de backup"
      contains: "App louvor 23.01.2026/"
  key_links:
    - from: "git rm --cached"
      to: "git ls-files *.ini"
      via: "remocao do index git"
      pattern: "nenhum resultado"
---

<objective>
Remover todos os arquivos desktop.ini do rastreamento git (git index) e garantir que nunca sejam rastreados novamente. Adicionar a pasta de backup antiga ao .gitignore.

Purpose: desktop.ini e um arquivo de metadados do Windows Explorer — nao tem lugar num repositorio de codigo. Uma vez que o entry e removido do index git, o .gitignore (que ja contem "desktop.ini") previne futuros re-trackings automaticamente.

Output: Um commit dedicado removendo todos os 8 desktop.ini do tracking + .gitignore atualizado com "App louvor 23.01.2026/".
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
  <name>Task 1: Verificar estado atual do tracking de desktop.ini</name>
  <files>
    (somente leitura — nenhuma modificacao nesta task)
  </files>
  <read_first>
    Executar para ver o estado atual antes de qualquer mudanca:
      git ls-files "*.ini"

    Resultado esperado (8 arquivos):
      admin/desktop.ini
      assets/css/desktop.ini
      assets/desktop.ini
      assets/images/desktop.ini
      assets/js/desktop.ini
      banco de dados/desktop.ini
      desktop.ini
      includes/desktop.ini

    Tambem verificar o .gitignore atual:
      cat .gitignore

    Confirmar que "desktop.ini" ja esta no .gitignore (linha simples, sem barra).
    Se NAO estiver, sera necessario adicionar na proxima task.
  </read_first>
  <action>
    Esta task e somente de verificacao/diagnostico. Nao fazer nenhum staging ou commit.

    Anotar mentalmente:
    - Quantos desktop.ini aparecem no git ls-files (devem ser 8)
    - Se "desktop.ini" ja esta no .gitignore (provavelmente sim, baseado no estado atual)
    - Se "App louvor 23.01.2026/" JA esta no .gitignore (provavelmente NAO — sera adicionado na Task 2)
  </action>
  <verify>
    <automated>git ls-files "*.ini" | wc -l</automated>
  </verify>
  <acceptance_criteria>
    - `git ls-files "*.ini"` retorna exatamente 8 linhas (os 8 desktop.ini ainda rastreados)
    - `grep -c "desktop.ini" .gitignore` retorna 1 ou mais (entry ja existe)
  </acceptance_criteria>
  <done>Estado de baseline documentado: 8 desktop.ini rastreados, .gitignore revisado.</done>
</task>

<task type="auto">
  <name>Task 2: Atualizar .gitignore e remover desktop.ini do tracking</name>
  <files>
    .gitignore
    desktop.ini
    admin/desktop.ini
    assets/desktop.ini
    assets/css/desktop.ini
    assets/images/desktop.ini
    assets/js/desktop.ini
    includes/desktop.ini
    "banco de dados/desktop.ini"
  </files>
  <read_first>
    Ler o .gitignore completo para identificar onde inserir as novas entradas:
      cat .gitignore

    Localizar a secao "# Arquivos de sistema" — e onde "desktop.ini" ja deve estar.
    A nova entrada "App louvor 23.01.2026/" deve ser adicionada em uma secao adequada.
  </read_first>
  <action>
    PASSO 1 — Adicionar "App louvor 23.01.2026/" ao .gitignore:

    Abrir .gitignore e adicionar ao final do arquivo (ou apos a secao de arquivos de sistema):

    # Versao antiga (backup local — nao versionar)
    App louvor 23.01.2026/

    Salvar o arquivo.

    PASSO 2 — Remover todos os desktop.ini do git index:

    Executar em sequencia (cada comando separado):

      git rm --cached desktop.ini
      git rm --cached admin/desktop.ini
      git rm --cached assets/desktop.ini
      git rm --cached "assets/css/desktop.ini"
      git rm --cached "assets/images/desktop.ini"
      git rm --cached "assets/js/desktop.ini"
      git rm --cached includes/desktop.ini
      git rm --cached "banco de dados/desktop.ini"

    Se algum caminho tiver erro "did not match any files", ignorar e continuar — significa que esse arquivo especifico nao estava rastreado.

    PASSO 3 — Fazer staging do .gitignore atualizado:
      git add .gitignore

    PASSO 4 — Verificar o staging antes do commit:
      git status

    O staging deve mostrar:
    - deleted: [todos os 8 desktop.ini]
    - modified: .gitignore

    PASSO 5 — Commitar:
      git commit -m "chore(git): remove desktop.ini tracking e ignora pasta de backup antiga"

    NENHUM outro arquivo deve entrar neste commit alem dos desktop.ini deletados e o .gitignore.
  </action>
  <verify>
    <automated>git ls-files "*.ini" | wc -l</automated>
  </verify>
  <acceptance_criteria>
    - `git ls-files "*.ini"` retorna 0 linhas (nenhum desktop.ini rastreado)
    - `git log --oneline -1` mostra: "chore(git): remove desktop.ini tracking e ignora pasta de backup antiga"
    - `git show --stat HEAD` lista exatamente: .gitignore modificado + 8 desktop.ini deletados (prefixados com "D")
    - `grep "App louvor 23.01.2026/" .gitignore` retorna a linha (entry existe no arquivo)
    - `git status` NAO mostra nenhum desktop.ini em nenhum diretorio
    - Os arquivos desktop.ini AINDA EXISTEM no disco (apenas foram removidos do tracking — nao foram deletados fisicamente). Verificar: ls desktop.ini (deve existir)
  </acceptance_criteria>
  <done>Todos os 8 desktop.ini removidos do git index; .gitignore atualizado com pasta de backup; commit dedicado criado.</done>
</task>

</tasks>

<verification>
Verificacao final deste plano:

  git ls-files "*.ini"
  # Deve retornar VAZIO (nenhuma saida)

  git log --oneline -1
  # Deve mostrar: chore(git): remove desktop.ini tracking e ignora pasta de backup antiga

  git show --stat HEAD
  # Deve mostrar: .gitignore | N ++-- e 8x "delete mode 100644 .../desktop.ini"

  grep "App louvor 23.01.2026/" .gitignore
  # Deve retornar a linha com o entry

  git status | grep desktop.ini
  # Deve retornar VAZIO (nenhum desktop.ini aparece no status)
</verification>

<success_criteria>
- `git ls-files "*.ini"` retorna zero resultados
- `git status` nao menciona desktop.ini em nenhum diretorio
- .gitignore contem "App louvor 23.01.2026/" como entrada ignorada
- GIT-02 cumprido: desktop.ini removido do tracking e protegido pelo .gitignore
</success_criteria>

<output>
Apos conclusao, criar `.planning/phases/01-git-cleanup/01B-SUMMARY.md` com:
- Hash e mensagem do commit de limpeza
- Lista dos 8 caminhos de desktop.ini removidos
- Confirmacao do estado final: git ls-files "*.ini" = 0 resultados
</output>
