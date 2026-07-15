# FASE XX — <nome da fase>

> Método: skill global **`vilela-gsd`** (uma fase por vez; fase só fecha VERIFICADA).
> Copie este modelo para `FASE-XX-PLANO.md` no passo PLANEJAR.

**Objetivo (1 frase):** …

**Critérios de sucesso (mensuráveis, por execução):**
1. …
2. …

**Fora de escopo desta fase:** …

## Fatias
- [ ] …
- [ ] …

## Verificação (por execução)
| Como provar | Resultado esperado |
|---|---|
| comando / fluxo na tela | … |

⚠️ Checagens fixas deste repo (lições incorporadas):
- [ ] Diff **não mistura** `site/**` e `gestao/**` no mesmo commit.
- [ ] Se a fase toca `site/**`: OK explícito do Diego antes do push (push = deploy produção).
- [ ] Gate local verde: `.\governanca\harness.ps1` (e `pronto.ps1 -Full` antes de push).
- [ ] (FASE 00, 2026-07-16) Todo endpoint de diagnóstico/smoke test: o `require` de config
      fica DENTRO do try/catch que monta a resposta — falha de configuração é um resultado
      esperado do diagnóstico (JSON de erro), nunca um fatal error PHP cru na tela.

## Registro (ao fechar)
- [ ] STATE.md (posição) · ROADMAP.md (fase ✅) · CHANGELOG.md (topo)
- [ ] RETRO em `LICOES.md` (o que travou? o que acelerou? que regra evitaria retrabalho?)
- [ ] Pendências explícitas (nunca dívida silenciosa)
