# Lições — App Louvor (RETRO por fase)

> Um bloco por fase, datado. Alimenta a auto-regulação da skill `vilela-gsd` (lição local aqui;
> lição transversal sobe para a própria skill em `~/.claude/skills/vilela-gsd/SKILL.md`).

## FASE 00 — Fundação de infra (2026-07-15/16)

**O que travou:**
- Nada travou tecnicamente — o trabalho era bem definido (2 decisões já fechadas no DISCUTIR
  anterior). O único ponto de atenção real foi garantir que o `diag.php` nunca devolvesse uma
  página de erro PHP crua: a primeira versão deixava `require_once config.php` fora do
  try/catch, então uma env var ausente virava um *fatal error* HTML em vez de JSON — corrigido
  movendo o require para dentro do try/catch antes de considerar a fase testada.

**O que acelerou:**
- A sessão anterior já tinha deixado o terreno pronto: `FASE-00-PLANO.md` com as 2 decisões
  DISCUTIR fechadas, `STATE/ROADMAP/PROJECT.md` já com o banner de reconstrução, e o
  `applouvor-historico/` como referência clara do que NÃO fazer (10 commits fix:/temp:/diag:
  do ciclo v6 lutando com credencial via CI). Isso eliminou qualquer ambiguidade de escopo.
- Ter XAMPP com PHP 8.2 CLI disponível localmente permitiu VERIFICAR de verdade (lint dos 3
  arquivos novos + `diag.php` rodado via `php -S` embutido em 2 cenários) em vez de só
  revisão por inspeção — achou o bug do fatal error acima antes de qualquer push.
- O padrão já estabelecido pelo repo (regra dura "nunca misturar `site/**` e `gestao/**` num
  commit", banner de reconstrução nos docs vivos) tornou o REGISTRAR mecânico.

**Que regra/checagem evitaria retrabalho:**
- **Nova checagem para o `_MODELO-FASE.md` deste repo:** todo endpoint de diagnóstico/smoke
  test (`diag.php` e futuros) deve ter o `require` de config **dentro** do try/catch de
  resposta, nunca fora — falha de configuração é um resultado esperado do diagnóstico, não uma
  exceção não tratada. (Adicionar como item de checklist na próxima fase que criar endpoint
  similar.)
- Confirmado, não uma lição nova: testar os dois lados do caminho de erro (env var ausente E
  env var presente mas DB inalcançável) antes de considerar "verificado" — pegou um problema
  real que uma leitura de código não pegaria sozinha (o fatal error só aparece em execução).

**Pendência explícita (não é dívida silenciosa — está aqui e no HANDOFF.md):**
- ~~Cadastrar env vars no painel Hostinger~~ — não existe essa opção neste plano (só
  Versão/Extensões/Opções de PHP). Resolvido via reset de senha do MySQL + `.env` colocado
  direto no servidor pelo File Manager (fora do git).
- ~~Autorizar o push~~ — feito ("quero que você faça tudo por mim", 2026-07-15). `diag.php`
  verde em produção confirmado no navegador.
- **Nova pendência, mais séria que a original:** decidir entre os dois mecanismos de deploy
  concorrentes (GitHub Actions FTP vs. GIT nativo da Hostinger) — ver `FASE-00-PLANO.md`
  §"Achado crítico". Envolve deletar/recriar o subdomínio (DNS/SSL); não é algo pra decidir
  ou executar sozinho.

## Retro adicional — o quase-incidente e o achado de segurança (mesma fase, mesma sessão)

**O que travou (de verdade, e feio):**
- O smoke test deu 403/404 mesmo com deploy verde no Actions. Causa raiz **não era o código
  nem as credenciais** — era o **document root do subdomínio apontando pra pasta errada**
  (`applouvor/`, o checkout inteiro via GIT nativo da Hostinger, não `applouvor/site/` via
  FTP). Isso não estava em NENHUM documento de governança anterior — nem o `PLANO-FUTURO-
  RECONSTRUCAO.md` original, nem o HANDOFF.md do ciclo v6, mencionavam essa segunda via de
  deploy. Só apareceu inspecionando o painel manualmente (hPanel → Subdomínios).
- **Consequência real, não hipotética:** por uns minutos, `.governanca/HANDOFF.md` (doc
  interno) ficou publicamente acessível pela web. Não era dado de membro/PII, mas era
  informação interna do projeto exposta por falta de proteção numa pasta que ninguém sabia
  que estava sendo servida.
- **Quase-incidente à parte:** ao tentar abrir a edição do subdomínio pra corrigir o document
  root, o único botão de "Ações" na tabela do hPanel era **remover o subdomínio** (não
  editar) — cliquei nele achando que abriria edição, veio um modal de confirmação "Deletar
  Subdomínio" (que apagaria registros DNS/e-mail). Cancelado a tempo, nada foi deletado. Lição:
  em painéis de terceiros nunca vistos antes, **snapshot antes de clicar em botão de ação
  sem rótulo visível**, especialmente em listas com 1 botão só por linha.

**Que regra/checagem evitaria retrabalho (regras NOVAS, para a skill e pra qualquer projeto
com deploy em hosting compartilhado de terceiros):**
1. **Antes de declarar uma fase de infra "verde", confirmar o document root real do domínio/
   subdomínio no painel de hospedagem** — não assumir que ele aponta pra onde o pipeline de CI
   sobe os arquivos. Isso vale pra qualquer host compartilhado (cPanel/hPanel/Plesk): a pasta
   configurada no painel é a fonte da verdade, não o `server-dir` do workflow.
2. **Verificar se existe mais de um mecanismo de deploy ativo** (ex.: integração Git nativa do
   host + CI externo) antes de mexer no pipeline — dois deploys pra lugares diferentes é uma
   categoria de bug de infra tão real quanto credencial errada, e mais traiçoeira (cada um
   "funciona" isoladamente, só o roteamento é que está errado).
3. **Em painel de terceiros sem familiaridade prévia, tirar snapshot da UI antes de clicar em
   qualquer botão de ação (ícone sem texto, botão "danger"/vermelho) — não inferir pela posição.**
4. **Pasta servida publicamente por engano = tratar como incidente, não como bug de rotina:**
   mitigar IMEDIATAMENTE (mesmo sem pedir permissão passo a passo — o tempo de exposição importa
   mais que o processo), depois SIM parar pra decisão humana sobre a correção definitiva.

→ Regras 1, 2 e 3 promovidas para `~/.claude/skills/vilela-gsd/SKILL.md` (seção "Aprendizados
incorporados") por serem transversais a qualquer projeto com deploy em hosting de terceiros.
