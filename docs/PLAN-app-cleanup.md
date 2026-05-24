# Plano de Limpeza, Organização e Salvamento de Versão (v5.2.0)

Este plano estabelece os passos necessários para realizar uma limpeza profunda e organização estrutural do sistema do App Louvor, consolidando definitivamente a nova **React SPA** como o único painel administrativo, eliminando redundâncias do PHP antigo, otimizando a segurança em produção e congelando a versão estável com Tagging no Git.

---

## 📋 Visão Geral

O sistema acumulou arquivos herdados do painel administrativo PHP clássico que agora são totalmente supridos pela SPA do Vite em `/dashboard/`. Além disso, o deploy automático via Git webhook na Hostinger expõe arquivos confidenciais e estruturais de desenvolvimento (como arquivos TypeScript e configs de compilação) na pasta pública do servidor. 

Este plano introduz:
1. **Consolidação do Painel**: Remoção dos arquivos administrativos PHP obsoletos sob `/admin` e redirecionamento transparente para o novo `/dashboard/`.
2. **Otimização de Produção**: Inclusão de rotina de limpeza pós-deploy no script FTP para eliminar arquivos e pastas de desenvolvimento (`src/`, `vite.config.ts`, etc.) do servidor público da Hostinger.
3. **Congelamento da Versão**: Atualização do versionamento lógico e de cache do Service Worker da raiz para a versão **v5.2.0** e criação de uma Git Tag oficial no GitHub.

---

## 🏗️ Tipo de Projeto & Escopo
* **Tipo de Projeto:** WEB (React Frontend SPA + Vanilla PHP Backend)
* **Arquivos Afetados:** `src/config/config.php`, `sw.js`, `VERSION.txt`, arquivos dentro de `admin/` e `scripts/deploy/deploy_react_ftp.py`.

---

## 🎯 Critérios de Sucesso
1. **Painel Unificado**: Acessar `https://vilela.eng.br/applouvor/admin/` deve redirecionar instantaneamente e com segurança o usuário para o `/dashboard/`.
2. **Redução de Arquivos no Servidor**: O diretório `/domains/vilela.eng.br/public_html/applouvor/dashboard/` em produção deve conter estritamente os arquivos da build compilada e arquivos de controle (`assets/`, `index.prod.html`, `manifest.json`, `sw.js`, `index.php` e `.htaccess`), sem expor código-fonte (`src/`, etc.).
3. **Versionamento v5.2.0 Ativo**: O PWA do músico na raiz deve forçar a atualização imediata dos navegadores apontando para a versão cacheada `v5.2.0`.
4. **Histórico Seguro**: Tag de release `v5.2.0` criada com sucesso e empurrada para o repositório GitHub.

---

## 🛠️ Cronograma de Tarefas (Task Breakdown)

### 📌 Fase 1: Versionamento e Congelamento (Version Bump v5.2.0)

#### 📝 Tarefa 1: Atualizar a Versão Lógica do App
* **Agente Responsável:** `documentation-writer`
* **Skills Recomendadas:** `plan-writing`, `clean-code`
* **Prioridade:** Alta (P0)
* **Dependências:** Nenhuma
* **INPUT:** Arquivo [src/config/config.php](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/src/config/config.php) definindo `define('APP_VERSION', '5.1.0')`
* **OUTPUT:** Arquivo `src/config/config.php` atualizado para `define('APP_VERSION', '5.2.0')`
* **VERIFY:** Confirmar que a constante `APP_VERSION` está exatamente configurada como `5.2.0`.

#### 📝 Tarefa 2: Atualizar Cache do Service Worker do Músico (Raiz)
* **Agente Responsável:** `frontend-specialist`
* **Skills Recomendadas:** `clean-code`
* **Prioridade:** Alta (P0)
* **Dependências:** Tarefa 1
* **INPUT:** Arquivo [sw.js](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/sw.js) na raiz contendo `const CACHE_NAME = 'louvor-pib-v5.1.0'`
* **OUTPUT:** Arquivo `sw.js` contendo `const CACHE_NAME = 'louvor-pib-v5.2.0'`
* **VERIFY:** Verificar se o cache de Service Worker reflete exatamente a string `louvor-pib-v5.2.0`.

#### 📝 Tarefa 3: Atualizar Arquivo do Versionador
* **Agente Responsável:** `documentation-writer`
* **Skills Recomendadas:** `plan-writing`
* **Prioridade:** Média (P1)
* **Dependências:** Tarefa 1
* **INPUT:** Arquivo [VERSION.txt](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/VERSION.txt) desatualizado
* **OUTPUT:** Arquivo `VERSION.txt` bumpado para a data atual de 24 de Maio e versão lógica `v5.2.0 - App Cleanup, Admin Consolidado e Remoção de Redundâncias`.
* **VERIFY:** Ler o conteúdo e garantir a exatidão das informações.

---

### 📌 Fase 2: Remoção de Painéis Obsoletos (Admin Cleanup)

#### 📝 Tarefa 4: Remover Scripts PHP Administrativos Antigos
* **Agente Responsável:** `backend-specialist`
* **Skills Recomendadas:** `clean-code`
* **Prioridade:** Alta (P0)
* **Dependências:** Nenhuma
* **INPUT:** Lista de arquivos administrativos PHP obsoletos dentro da pasta `/admin/` (como `escalas.php`, `repertorio.php`, `metronomo.php`, `avisos.php`, `devocionais.php`, `oracao.php`, `leitura.php`)
* **OUTPUT:** Exclusão lógica (via Git) de todos os arquivos redundantes na pasta `/admin/`, deixando a pasta limpa de código morto.
* **VERIFY:** Executar listagem do diretório `/admin/` localmente e garantir que nenhum desses arquivos ainda reside em disco.

#### 📝 Tarefa 5: Configurar Redirecionamento Seguro no Admin
* **Agente Responsável:** `backend-specialist`
* **Skills Recomendadas:** `clean-code`, `api-patterns`
* **Prioridade:** Alta (P0)
* **Dependências:** Tarefa 4
* **INPUT:** Arquivo de entrada [admin/index.php](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/admin/index.php) antigo
* **OUTPUT:** Modificação de `admin/index.php` para realizar um redirecionamento HTTP nativo e limpo `301/302` para o subdiretório de produção do novo painel em `/applouvor/dashboard/`.
* **VERIFY:** Executar requisição HTTP no redirecionador e confirmar o cabeçalho `Location: ../dashboard/` com status correto.

---

### 📌 Fase 3: Limpeza de Código-Fonte em Produção (FTP Deployer Upgrade)

#### 📝 Tarefa 6: Aprimorar o Script FTP para Excluir Arquivos de Dev
* **Agente Responsável:** `devops-engineer`
* **Skills Recomendadas:** `python-patterns`, `clean-code`, `deployment-procedures`
* **Prioridade:** Alta (P0)
* **Dependências:** Nenhuma
* **INPUT:** Script de deploy FTP [deploy_react_ftp.py](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/scripts/deploy/deploy_react_ftp.py)
* **OUTPUT:** Inclusão de uma rotina no final da execução FTP que navega nos subdiretórios remotos do FTP e exclui de forma sistemática e silenciosa pastas e arquivos de desenvolvimento do servidor (como `src/`, `public/`, `tsconfig.json`, `vite.config.ts`, `package.json` e `package-lock.json`), poupando espaço e aumentando exponencialmente a segurança em produção.
* **VERIFY:** Revisar a lógica da rotina de deleção por FTP e atestar que ela deleta estritamente os arquivos corretos.

---

### 📌 Fase 4: Execução, Sincronização & Tagging

#### 📝 Tarefa 7: Executar Pipeline de Produção e Deploy Único
* **Agente Responsável:** `devops-engineer`
* **Skills Recomendadas:** `deployment-procedures`, `powershell-windows`
* **Prioridade:** Alta (P0)
* **Dependências:** Todas as tarefas anteriores das Fases 1, 2 e 3
* **INPUT:** Atalho de console [push-deploy.ps1](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20%28COM%20STICH%29%20com%20REACT/push-deploy.ps1) na raiz
* **OUTPUT:** Compilação do Vite local, verificação e aprovação do Master Checklist de Qualidade, upload seguro via FTP com limpeza atômica pós-deploy rodando de forma 100% autônoma e sincronização de commit/push Git.
* **VERIFY:** Validar que a URL `https://vilela.eng.br/applouvor/dashboard/` carrega instantaneamente, e que as chamadas a `admin/` são redirecionadas com sucesso.

#### 📝 Tarefa 8: Congelar a Versão com Git Tagging no GitHub
* **Agente Responsável:** `devops-engineer`
* **Skills Recomendadas:** `powershell-windows`
* **Prioridade:** Média (P1)
* **Dependências:** Tarefa 7
* **INPUT:** Repositório local limpo e empurrado para a branch `main`
* **OUTPUT:** Execução dos comandos Git locais para marcar a tag `v5.2.0` no histórico e subir de forma definitiva para o GitHub:
  ```bash
  git tag -a v5.2.0 -m "Release v5.2.0 - App Cleanup, Admin Consolidado e Limpeza de Dev"
  git push origin v5.2.0
  ```
* **VERIFY:** Confirmar que a tag foi publicada com sucesso e reside de forma legível no repositório GitHub.

---

## 📊 Plano de Verificação (PHASE X)

### Testes Automatizados & Lints
1. **Auditoria de Qualidade Local:** Rodar o checklist master de qualidade do Antigravity (`python .agent/scripts/checklist.py .`).
2. **Build de Produção local:** Rodar `npm run build` na pasta `dashboard/` e certificar que passa sem erros.

### Verificação Manual Remota
1. **Redirecionamento:** Acessar `https://vilela.eng.br/applouvor/admin/` e constatar se o navegador é levado para o painel reativo.
2. **Inspeção de Pasta FTP Remota:** Conectar-se ao FTP e atestar que a pasta `/domains/vilela.eng.br/public_html/applouvor/dashboard/` não expõe arquivos e pastas como `src/` ou `vite.config.ts`.
3. **Tagging:** Acessar a aba "Releases/Tags" no repositório do GitHub e constatar a existência da tag `v5.2.0`.
