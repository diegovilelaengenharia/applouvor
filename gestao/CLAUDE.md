# gestao/ — App da Gestão do Louvor (lado LÍDER)

Briefing do domínio para a IA aberta neste subprojeto (chave de ouro 2 da cirurgia).

## O que é
Centro de comando do Ministério de Louvor da PIB Oliveira, servido na **porta 8020**,
**isolado** do PWA público (`../site/`, MySQL/Hostinger). Aqui é o lado do LÍDER
(Diego): escala de cultos, escala de jejum, setlist + metrônomo, repertório
inteligente, equipe + disponibilidade, treinamento e a **imagem semanal do WhatsApp**.

Migrado da Central (cockpit `igreja_louvor.py`) na **F4** da cirurgia de separação.

## Onde estão os dados
- **SSOT: `louvor.db`** — resolvido por `caminhos.py` (env `LOUVOR_DB` → default
  `Vilela Igreja/0. Máquina` → fallback legado `3. Igreja/00. _Gestão`). O app lê E
  ESCREVE nele. NUNCA ligar WAL (banco mora no Google Drive, sync + `-wal` = corrupção).
- Assets do ministério (logo, PNGs da escala): `caminhos.ministerio_dir()` — hoje em
  `3. Igreja/…`, migram para `Vilela Igreja/…` na F6 (muda só o `caminhos.py`).

## Estrutura
- `app/main.py` — FastAPI 8020 (serve o frontend + monta o router).
- `app/routers/louvor.py` — 18 endpoints `/api/louvor*` (adaptado, lógica intacta).
- `app/frontend/` — shell enxuto (`index.html` + `app-gestao.js`) que reusa o motor
  modular da Central (`registry.js`/`comporCentral` + módulo `louvor.js`) + vendor.
- `ferramentas/` — `louvor_db.py` (schema/seed), `louvor.py` (CLI), `gerar_imagem_louvor.py`
  (PNG do WhatsApp), `sincronizar_igreja_agenda.py` (→ Google Agenda; transversal —
  usa o `calendario_painel` do Sistema por ponte), `resumo_diario.py` (→ igreja.json).
- `caminhos.py` — SSOT de caminhos. `tests/` — smoke test (por execução).

## Fronteira (LGPD)
Dado de MEMBROS da igreja (equipe do Diego). Fica neste ecossistema. A única ponte
para fora é o `igreja.json` (agregado) que a Central LÊ — nunca importa código daqui.

## Rodar / verificar
```
py -m pytest gestao/tests/           # smoke (não precisa do Drive)
./gestao/iniciar.ps1                  # sobe 8020 + abre o navegador
py gestao/ferramentas/louvor.py      # próximos cultos + lacunas no terminal
```
**Verificação é POR EXECUÇÃO** (request/boot), nunca por inspeção — o Starlette 1.3
usa inclusão preguiçosa (`_IncludedRouter`), então contar `app.routes` engana.

## Pendências herdadas (F4 → F5/F6)
- `louvor.db` ainda NÃO foi movido (o app acha o local atual) — mover é coordenado
  com a F5 (Central para de ler o caminho antigo) e F6 (pastas do Drive), tudo parado.
- `cultos.js` não migra (é lente da agenda → Central). Deploy do applouvor só olha
  `site/**` (F1) — commits/pushes em `gestao/` NÃO deployam.
