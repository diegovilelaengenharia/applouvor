# Forma de trabalhar — ⛪ applouvor

> Versão local. O método completo (CTV, cartões) é o da casa:
> `Vilela Sistema\governanca\METODO-VILELA.md`.

## O ciclo de toda sessão
1. **Chegar:** `git pull` → `.\governanca\harness.ps1`.
2. **Trabalhar:** uma fatia por vez; a skill local `/vilela-louvor` faz o domínio
   (escala, repertório, imagem, agenda). SSOT = `louvor.db` (lar Vilela Igreja).
3. **Verificar POR EXECUÇÃO:** app 8020 no ar, tela conferida, teste verde.
4. **Sair:** commit → ⚠️ **PUSH SÓ COM DECISÃO CONSCIENTE** → handoff se ficou coisa aberta.

## ⚠️ A regra que salva domingo: push em `main` = DEPLOY EM PRODUÇÃO
GitHub Actions publica no Hostinger (FTPS) a cada push em `main`. Antes de pushar:
1. `pronto.ps1 -Full` verde;
2. conferir O QUE vai subir (`git log origin/main..HEAD --oneline`);
3. nunca pushar sexta à noite/sábado sem necessidade — domingo tem culto.

## Git (regras duras)
- **NUNCA `git add -A`**; Conventional Commits em português.
- `louvor.db` e dados do ministério NUNCA no git (gitignored; backup = Cofre Vivo cifrado).
