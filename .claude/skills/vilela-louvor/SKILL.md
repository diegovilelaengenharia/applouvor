---
name: vilela-louvor
description: >
  Ministério da Igreja (PIB Oliveira/Ilicínea): escala de louvor, jejum, repertório,
  imagem semanal do WhatsApp e agenda — tudo no SSOT louvor.db, pelo app de Gestão
  (porta 8020). Use quando o Diego disser "escala", "quem toca/canta domingo", "gera o mês",
  "monta a escala", "jejum da semana", "imagem do louvor", "repertório", "setlist", ou
  "sincroniza a agenda da igreja". Lado VIDA (modo 🟢).
---

# vilela-louvor — Ministério (Louvor + Cultos)

Skill **local do `applouvor`**, no lado **`gestao/`** (o lado do líder). Modo-vida 🟢. O Diego é
**líder de louvor** na PIB (ver memória `perfil-igreja-diego`).

⚠️ **Fronteira do repo (não errar):** `applouvor\site\` é o **PWA público**, servido em
`louvor.vilela.eng.br`. **Todo push em `main` deploya** (webhook nativo Hostinger, sem filtro de
path — descoberto na FASE 00, 2026-07-16: mesmo commit só em `gestao/` republica o repo
inteiro, protegido por `.htaccess` na raiz). `applouvor\gestao\` não é servido na web, mas
**vai junto** no clone a cada push. Nunca misture os dois num commit mesmo assim (legibilidade
do histórico + o fallback manual `deploy.yml` ainda filtra por `site/**`).

## SSOT do louvor
- **Banco único:** `C:\vilela\Vilela Igreja\0. Máquina\louvor.db` — centro de comando do ministério.
  Resolvido por `gestao\caminhos.py::louvor_db_path()` (env `LOUVOR_DB` → home local → legado).
  Guarda equipe+disponibilidade, escala de cultos, escala de JEJUM (revezamento), repertório,
  setlist, histórico, indisponibilidades, avisos, config.
- A **Central Vilela não gerencia mais o louvor** (F5 da cirurgia fechou esse split-brain) — ela só
  exibe o resumo (`gestao\ferramentas\resumo_diario.py` → `Vilela Sistema\_central\resumos\igreja.json`).
  **Trabalho ao vivo é aqui, no 8020.**

## Pela tela (o caminho normal)
```powershell
cd gestao ; .\iniciar.ps1     # http://127.0.0.1:8020
```
Equipe, geração do mês (rodízio justo), escala, jejum, repertório — tudo na tela, gravando no `louvor.db`.

## Ferramentas (`gestao\ferramentas\`)
| Quero… | Ferramenta |
|---|---|
| ver próximos cultos + **lacunas** (posição sem ninguém) + jejum | `louvor.py` |
| editar equipe / gerar o mês / relatório de justiça (sem a tela) | `louvor_db.py` |
| **imagem semanal** p/ o grupo do WhatsApp (PNG escala+jejum) | `gerar_imagem_louvor.py` (+ `_louvor-imagem-semanal.ps1`, agendada) |
| jogar escalas na **Google Agenda** ([ESCALADO] roxo) | `sincronizar_igreja_agenda.py` |
| resumo diário → caixa de entrada da Central | `resumo_diario.py` |

> A escala do mês (rodízio justo + PIB Criança via indisponibilidade) é gerada pela tela; jul–set/2026
> já foram gerados (ver memória `central-igreja-louvor`). A imagem reflete sempre a escala ATUAL.

## EBD e Leituras — NÃO moram aqui
**EBD** (`ebd_indexar.py`, `ebd_materiais.py`, `ebd_unificar.py`; série atual: Filipenses) e
**Leituras/fichas** (`leituras_enriquecer.py`) ficaram na **Central**, como módulo *Teologia* —
ferramentas em `C:\vilela\Vilela Sistema\ferramentas\`, dados em `Vilela Igreja\00. _Gestão\ebd.json`
e `6. Acervo\01. Biblioteca`. Nunca leram o `louvor.db`. Para mexer nelas, é sessão na **raiz**
(`c:\vilela`), não aqui.

## Verificar por execução
- Gerou escala/imagem? **Abra e confira** (nome certo na posição certa, jejum da semana correto).
- Sincronizou agenda? Confira que não duplicou (o script checa o cache `_agenda.json`) e que o
  [ESCALADO] caiu nos dias certos.
- Testes: `cd gestao ; py -m pytest tests`.
- Antes de pushar: confirme que o diff **não toca `site/`** (senão você deploya o PWA sem querer).

*Liga a [[central-igreja-louvor]], [[perfil-igreja-diego]].*

---
_Herda o **CONTRATO-SKILLS** (`C:\vilela\Vilela Sistema\governanca\skills\CONTRATO-SKILLS.md`): português · fronteira LGPD · verificação por execução · começar pelo sinal · registrar no livro-razão (`achados.py`) · enxugar é progresso._
