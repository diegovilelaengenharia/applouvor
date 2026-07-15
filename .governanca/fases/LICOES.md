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
- Cadastrar `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` no painel Hostinger — só o Diego.
- Autorizar o push — só o Diego (regra dura do método).
- Depois do push: conferir `diag.php` no ar + segundo push de controle — fecha os critérios
  3 e 4 da fase, hoje em aberto.
