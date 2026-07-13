# Copyright (c) 2026 Diego Vilela. Uso pessoal (lado Deiso) — Ministério de Louvor.
"""louvor_db.py — Banco DEDICADO e ÚNICO SSOT do Ministério de Louvor (PIB Oliveira).

Centro de comando do ministério (lado líder). Consolida tudo que antes vivia
espalhado (louvor.json + imagens/planilhas catalogadas): equipe (com
disponibilidade), escala de cultos, escala de JEJUM (revezamento), repertório,
setlist, histórico de canto, indisponibilidades, avisos, devocional, eventos,
treinamento e config (posições/momentos). A Central lê e ESCREVE neste banco
(router igreja_louvor → /api/louvor*). As ferramentas louvor.py e
gerar_imagem_louvor.py também leem daqui (fim da fragmentação com o louvor.json).

Banco: `3. Igreja/00. _Gestão/louvor.db` (dado pessoal da igreja, FORA do git).

Filosofia (1 SSOT + escrita pela tela):
  - Execução normal PRESERVA o que foi editado pela Central: cria as tabelas que
    faltam, faz migração (adiciona colunas novas) e só SEMEIA tabelas VAZIAS.
  - `--reset` reconstrói do zero a partir das sementes deste arquivo (use ao
    recatalogar a fonte; descarta edições feitas na tela).

Uso:  py louvor_db.py            # garante schema + semeia o que estiver vazio
      py louvor_db.py --reset    # recria do zero (sementes deste arquivo)
      py louvor_db.py --resumo   # imprime um resumo (contagens)
      py louvor_db.py --ingerir-repertorio  # mescla o "Repertório PIB 2025.docx"
"""
from __future__ import annotations

import argparse
import json
import re
import sqlite3
import sys
import zipfile
from datetime import date, timedelta
from pathlib import Path
from xml.etree import ElementTree as ET

# SSOT de caminhos do subsistema gestão (gestao/ = parents[1] deste arquivo).
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
import caminhos

# Apenas para MENSAGENS de erro ("sem banco: ..."); a resolução real é por caminhos.
DB_REL = Path("Vilela Igreja") / "0. Máquina" / "louvor.db"

# "Repertório Original" do ministério (título · artista · link), catalogado em 2025.
_LOUVOR_COMP = (Path("3. Igreja") / "01. PIB Oliveira" / "Ministério de Louvor"
                / "01. Louvor 2026 (Compartilhada)")
REPERTORIO_DOCX_REL = _LOUVOR_COMP / "03. Repertório" / "Repertório PIB 2025.docx"
# biblioteca de treinamento (16 aulas do seminário + literatura) — indexada, não movida.
TREINAMENTO_DIRS = [
    (_LOUVOR_COMP / "04. Treinamento" / "Aulas Curso Seminario", "Seminário"),
    (_LOUVOR_COMP / "04. Treinamento" / "Rascunhos", "Treinamento"),
    (_LOUVOR_COMP / "07. Literatura", "Literatura"),
]


def _drive() -> Path:
    """Raiz do Drive via caminhos (SSOT do subsistema gestão; resolve por âncora/env)."""
    return caminhos.raiz_drive()


def caminho_db() -> Path | None:
    """Localiza o louvor.db (env LOUVOR_DB → novo → legado). None se não existe."""
    return caminhos.achar_louvor_db()


def conectar(ro: bool = True) -> sqlite3.Connection | None:
    """Abre o louvor.db (read-only por padrão). Retorna None se o banco não existe."""
    cam = caminho_db()
    if not cam:
        return None
    con = sqlite3.connect(f"file:{cam}?mode=ro", uri=True) if ro else sqlite3.connect(cam)
    con.row_factory = sqlite3.Row
    return con


# ── SCHEMA (1 SSOT — espelha o cardápio do App Louvor, lado líder) ───────────────
# colunas de cada tabela; migração adiciona as que faltarem (sem apagar dados).
SCHEMA: dict[str, str] = {
    "equipe": ("nome TEXT, genero TEXT, funcao TEXT, instrumento TEXT, "
               "disponibilidade TEXT, disponivel INTEGER, contato TEXT, "
               "aniversario TEXT, naipe TEXT"),
    "escala": ("data TEXT, voz1 TEXT, voz2 TEXT, violao TEXT, teclado TEXT, "
               "baixo TEXT, guitarra TEXT, bateria TEXT, evento TEXT, obs TEXT"),
    "jejum": "inicio TEXT, fim TEXT, pessoas TEXT",
    "repertorio": ("musica TEXT, artista TEXT, versao TEXT, tom TEXT, bpm TEXT, "
                   "duracao TEXT, momento TEXT, letra TEXT, cifra TEXT, audio TEXT, "
                   "video TEXT, tags TEXT, categoria TEXT, obs TEXT"),
    "setlist": ("id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, ordem INTEGER, "
                "musica TEXT, momento TEXT, obs TEXT"),
    "historico_canto": "id INTEGER PRIMARY KEY AUTOINCREMENT, musica TEXT, data TEXT",
    "indisponibilidade": ("id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT, "
                          "inicio TEXT, fim TEXT, motivo TEXT"),
    "avisos": ("id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, titulo TEXT, "
               "texto TEXT, fixado INTEGER DEFAULT 0"),
    "devocional": ("id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, titulo TEXT, "
                   "texto TEXT, referencia TEXT"),
    "eventos": ("id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT, tipo TEXT, "
                "hora TEXT, local TEXT, titulo TEXT"),
    "treinamento": "id INTEGER PRIMARY KEY AUTOINCREMENT, titulo TEXT, caminho TEXT, tag TEXT, ordem INTEGER",
    "config": "chave TEXT PRIMARY KEY, valor TEXT",
}


# ── EQUIPE (nome, genero, função, instrumento, disponibilidade) ────────────────
# disponibilidade: "Ativo" / texto de indisponibilidade (licença, mudança, afastado).
EQUIPE = [
    ("Diego",         "M", "Líder / Baterista",   "Bateria",      "Ativo (fixo todo domingo)"),
    ("Thalyta",       "F", "Co-líder / Violonista", "Violão",     "Ativo (fixo todo domingo)"),
    ("Samara",        "F", "Voz principal",        "Voz",          "Ativo"),
    ("Raquel",        "F", "Voz principal",        "Voz",          "Ativo"),
    ("Aline",         "F", "Voz principal",        "Voz",          "Ativo"),
    ("Wemerson",      "M", "Voz",                  "Voz",          "Ativo"),
    ("Ananias",       "M", "Voz",                  "Voz",          "Ativo"),
    ("Mariana",       "F", "Tecladista",           "Teclado",      "Licença maternidade"),
    ("Michelle",      "F", "Voz principal",        "Voz",          "Mudando de igreja"),
    ("Weberth",       "M", "Voz",                  "Voz",          "Mudando de igreja"),
    ("Luís Paulo",    "M", "Violonista",           "Violão",       "Mudou de igreja"),
    ("Rubens",        "M", "Músico",               "",             "Afastado temporário"),
    ("Pastor Marcio", "M", "Pastor (convidado)",   "Violão",       "Convidado"),
]

# membro disponível para escalar? (indisponível: licença, mudança, saída, afastamento)
_INDISP = ("licença", "licenca", "mudan", "mudou", "saiu", "afastad", "convidado")


def _disponivel(disp: str) -> int:
    d = (disp or "").lower()
    return 0 if any(k in d for k in _INDISP) else 1


# ── ESCALA DE CULTOS 2026 (catalogada das imagens) ─────────────────────────────
# data ISO -> (voz1, voz2, violao, bateria, obs).  "" = não informado na fonte.
ESCALA = {
    # Janeiro (imagem com Voz Fem/Masc/Violão/Bateria)
    "2026-01-04": ("Leia", "Ananias", "Pastor Marcio", "Ikaro", ""),
    "2026-01-11": ("Samara", "Weberth", "Thalyta", "Diego", ""),
    "2026-01-18": ("Aline", "Wemerson", "Thalyta", "Diego", ""),
    "2026-01-25": ("Raquel", "Ananias", "Thalyta", "Diego", ""),
    # Fevereiro (só vozes)
    "2026-02-01": ("Michelle", "Weberth", "", "Diego", ""),
    "2026-02-08": ("Samara", "Wemerson", "", "Diego", ""),
    "2026-02-15": ("Aline", "Ananias", "", "Diego", ""),
    "2026-02-22": ("Raquel", "Thalyta", "", "Diego", ""),
    # Março (só vozes)
    "2026-03-01": ("Michelle", "Weberth", "", "Diego", ""),
    "2026-03-08": ("Aline", "Ananias", "", "Diego", ""),
    "2026-03-15": ("Samara", "Wemerson", "", "Diego", ""),
    "2026-03-22": ("Michelle", "Weberth", "", "Diego", ""),
    "2026-03-29": ("Raquel", "Thalyta", "", "Diego", ""),
    # Abril–Junho (imagem "Escala de Vozes", 1 mulher + 1 homem; violão = Thalyta quando não canta)
    "2026-04-05": ("Aline", "Weberth", "Thalyta", "Diego", ""),
    "2026-04-12": ("Samara", "Wemerson", "Thalyta", "Diego", ""),
    "2026-04-19": ("Raquel", "Ananias", "Thalyta", "Diego", "Samara na PIB Criança"),
    "2026-04-26": ("Thalyta", "Weberth", "", "Diego", "Raquel na PIB Criança"),
    "2026-05-03": ("Aline", "Wemerson", "Thalyta", "Diego", "Samara na PIB Criança"),
    "2026-05-10": ("Samara", "Ananias", "Thalyta", "Diego", "Raquel na PIB Criança"),
    "2026-05-17": ("Raquel", "Weberth", "Thalyta", "Diego", ""),
    "2026-05-24": ("Thalyta", "Wemerson", "", "Diego", "Samara e Raquel na PIB Criança"),
    "2026-05-31": ("Aline", "Ananias", "Thalyta", "Diego", ""),
    "2026-06-07": ("Raquel", "Weberth", "Thalyta", "Diego", "Samara na PIB Criança (inversão c/ Raquel)"),
    "2026-06-14": ("Samara", "Wemerson", "Thalyta", "Diego", "Raquel na PIB Criança"),
    "2026-06-21": ("Thalyta", "Ananias", "", "Diego", "Samara na PIB Criança"),
    "2026-06-28": ("Aline", "Weberth", "Thalyta", "Diego", "Raquel na PIB Criança"),
}

# ── ESCALA DE JEJUM ────────────────────────────────────────────────────────────
# Semanas explícitas catalogadas (Março–Junho) + rotação contínua de 4 duplas que
# se estende até o fim de julho (âncora: 28/06 = Samara e Aline = índice 0).
JEJUM_EXPLICITO = [
    ("2026-03-01", "2026-03-07", "Diego e Weberth"),
    ("2026-03-08", "2026-03-14", "Michelle, Samara e Aline"),
    ("2026-03-15", "2026-03-21", "Wemerson e Ananias"),
    ("2026-03-22", "2026-03-28", "Raquel e Thalyta"),
    ("2026-04-05", "2026-04-11", "Samara e Aline"),
    ("2026-04-12", "2026-04-18", "Wemerson e Ananias"),
    ("2026-04-19", "2026-04-25", "Raquel e Thalyta"),
    ("2026-04-26", "2026-05-02", "Diego e Weberth"),
    ("2026-05-03", "2026-05-09", "Samara e Aline"),
    ("2026-05-10", "2026-05-16", "Wemerson e Ananias"),
    ("2026-05-17", "2026-05-23", "Raquel e Thalyta"),
    ("2026-05-24", "2026-05-30", "Diego e Weberth"),
    ("2026-05-31", "2026-06-06", "Samara e Aline"),
    ("2026-06-07", "2026-06-13", "Wemerson e Ananias"),
    ("2026-06-14", "2026-06-20", "Raquel e Thalyta"),
    ("2026-06-21", "2026-06-27", "Diego e Weberth"),
    ("2026-06-28", "2026-07-04", "Samara e Aline"),
]
_JEJUM_CICLO = ["Samara e Aline", "Wemerson e Ananias", "Raquel e Thalyta", "Diego e Weberth"]

# ── REPERTÓRIO (indicações de músicas) ─────────────────────────────────────────
# (musica, artista, categoria) — tom/BPM/links a preencher depois.
REPERTORIO = [
    ("Primeira Essência", "Aline Barros", "Desafio · estava entre as músicas desafio"),
    ("Estamos de Pé", "Marcus Salles", "Desafio"),
    ("Vou te Alegrar", "Aline Barros", "Desafio"),
    ("Tudo é Diferente", "Aline Barros", "Desafio"),
    ("Adorar a Deus", "Quatro Por Um", "Desafio"),
    ("Um Só", "Nívea Soares", "Desafio"),
    ("Comunhão", "Kleber Lucas", "Desafio"),
    ("Eu te Bendirei", "Ronaldo Bezerra", "Desafio"),
    ("Hosana", "Mariana Valadão", "Desafio"),
    ("Faz Chover", "Fernandinho", "Desafio"),
    ("Quando Deus escolhe Alguém", "Diante do Trono", "Desafio"),
    ("Nós Te Adoramos", "Julia Vitoria", "Indicação de integrante · já cantada"),
    ("Imensurável", "Aline Barros", "Indicação de integrante · já cantada"),
    ("Tu És Bom", "Nívea Soares", "Indicação de integrante · já cantada"),
    ("O Espírito do Senhor", "Kleber Lucas / Eli Soares", "Indicação de integrante · já cantada"),
    ("Oferta agradável a ti", "Cassiane", "Indicação de integrante · já cantada"),
    ("Sim e Amém", "Marcelo Markes e Felipe Rodrigues", "Indicação de integrante · ainda não cantada"),
    ("Tudo é Perda", "Felipe Rodrigues", "Indicação de integrante · ainda não cantada"),
]

# ── CONFIG (migrado do louvor.json: posições, momentos, fixos) ──────────────────
# posições ESTRUTURAIS da escala (= colunas) e seus rótulos amigáveis.
POSICOES = ["voz1", "voz2", "violao", "teclado", "baixo", "guitarra", "bateria"]
POSICOES_LABEL = {
    "voz1": "Voz 1", "voz2": "Voz 2", "violao": "Violão", "teclado": "Teclado",
    "baixo": "Baixo", "guitarra": "Guitarra", "bateria": "Bateria",
}
# posições conferidas para apontar LACUNA (as que de fato usamos hoje).
POSICOES_CORE = ["voz1", "voz2", "violao", "bateria"]
MOMENTOS_PADRAO = ["Abertura", "Adoração", "Comunhão/Ceia", "Ofertório", "Final"]

CONFIG = {
    "posicoes": json.dumps(POSICOES, ensure_ascii=False),
    "posicoes_label": json.dumps(POSICOES_LABEL, ensure_ascii=False),
    "posicoes_core": json.dumps(POSICOES_CORE, ensure_ascii=False),
    "momentos_padrao": json.dumps(MOMENTOS_PADRAO, ensure_ascii=False),
    "evento_padrao": "Culto de domingo",
    "dia_semana": "domingo",
    "equipe_fixa": json.dumps({"bateria": "Diego", "violao": "Thalyta"}, ensure_ascii=False),
}


def _gerar_jejum() -> list[tuple]:
    """Explícito + extensão por rotação até 31/07/2026."""
    semanas = list(JEJUM_EXPLICITO)
    ini = date(2026, 7, 5)          # semana seguinte a 28/06–04/07
    idx = 1                          # 28/06 foi índice 0 → próxima é 1
    while ini <= date(2026, 7, 31):
        fim = ini + timedelta(days=6)
        semanas.append((ini.isoformat(), fim.isoformat(), _JEJUM_CICLO[idx % 4] + " (rotação)"))
        ini = fim + timedelta(days=1)
        idx += 1
    return semanas


def _domingos_2026() -> list[str]:
    """Todos os domingos de 2026 (para a escala ter todas as datas, mesmo vazias)."""
    d = date(2026, 1, 1)
    d += timedelta(days=(6 - d.weekday()) % 7)  # primeiro domingo
    out = []
    while d.year == 2026:
        out.append(d.isoformat())
        d += timedelta(days=7)
    return out


# ── schema / migração / sementes ────────────────────────────────────────────────
def _colunas(con, tabela: str) -> set[str]:
    return {r[1] for r in con.execute(f"PRAGMA table_info({tabela})")}


def _nome_coluna(defcol: str) -> str:
    return defcol.strip().split()[0]


def garantir_schema(con: sqlite3.Connection) -> None:
    """Cria tabelas que faltam e adiciona colunas novas (migração não destrutiva)."""
    cur = con.cursor()
    for tabela, cols in SCHEMA.items():
        existe = con.execute(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name=?", (tabela,)).fetchone()
        if not existe:
            cur.execute(f"CREATE TABLE {tabela} ({cols})")
            continue
        atuais = _colunas(con, tabela)
        for defcol in cols.split(","):
            defcol = defcol.strip()
            nome = _nome_coluna(defcol)
            if nome not in atuais and "PRIMARY KEY" not in defcol.upper():
                cur.execute(f"ALTER TABLE {tabela} ADD COLUMN {defcol}")
    con.commit()


def _vazia(con, tabela: str) -> bool:
    return con.execute(f"SELECT COUNT(*) FROM {tabela}").fetchone()[0] == 0


def semear(con: sqlite3.Connection, forcar: bool = False) -> None:
    """Semeia as tabelas catalogadas. Por padrão só preenche as VAZIAS (preserva edições)."""
    cur = con.cursor()

    if forcar or _vazia(con, "equipe"):
        cur.execute("DELETE FROM equipe")
        cur.executemany(
            "INSERT INTO equipe (nome, genero, funcao, instrumento, disponibilidade, disponivel) "
            "VALUES (?,?,?,?,?,?)",
            [(n, g, f, i, d, _disponivel(d)) for n, g, f, i, d in EQUIPE])

    if forcar or _vazia(con, "escala"):
        cur.execute("DELETE FROM escala")
        for dia in _domingos_2026():
            v1, v2, vl, bt, ob = ESCALA.get(dia, ("", "", "", "", ""))
            cur.execute(
                "INSERT INTO escala (data, voz1, voz2, violao, bateria, evento, obs) "
                "VALUES (?,?,?,?,?,?,?)",
                (dia, v1, v2, vl, bt, CONFIG["evento_padrao"], ob))

    if forcar or _vazia(con, "jejum"):
        cur.execute("DELETE FROM jejum")
        cur.executemany("INSERT INTO jejum (inicio, fim, pessoas) VALUES (?,?,?)", _gerar_jejum())

    if forcar or _vazia(con, "repertorio"):
        cur.execute("DELETE FROM repertorio")
        cur.executemany(
            "INSERT INTO repertorio (musica, artista, obs) VALUES (?,?,?)", REPERTORIO)

    # config é idempotente (chave PK): sempre garante os valores padrão sem duplicar.
    for chave, valor in CONFIG.items():
        cur.execute("INSERT OR IGNORE INTO config (chave, valor) VALUES (?,?)", (chave, valor))
    con.commit()


# ── INGESTÃO DO REPERTÓRIO (docx → tabela repertorio) ───────────────────────────
_WNS = "{http://schemas.openxmlformats.org/wordprocessingml/2006/main}"


def _norm(s: str) -> str:
    """Normaliza título para deduplicar (minúsculo, sem acento/pontuação/'(ao vivo)')."""
    s = (s or "").lower()
    s = re.sub(r"\(ao vivo\)|\(acústico\)|\(live\)|\(medley\)", "", s)
    s = re.sub(r"[áàâã]", "a", s); s = re.sub(r"[éê]", "e", s); s = re.sub(r"[í]", "i", s)
    s = re.sub(r"[óôõ]", "o", s); s = re.sub(r"[ú]", "u", s); s = re.sub(r"[ç]", "c", s)
    s = re.sub(r"[^a-z0-9 ]", " ", s)
    return re.sub(r"\s+", " ", s).strip()


def parse_repertorio_docx(path: Path) -> list[dict]:
    """Lê o docx (zip+XML) e devolve [{musica, artista, video, versao}] da 'Lista 1'."""
    z = zipfile.ZipFile(path)
    root = ET.fromstring(z.read("word/document.xml"))
    linhas = []
    for p in root.iter(_WNS + "p"):
        txt = "".join(t.text or "" for t in p.iter(_WNS + "t")).strip()
        if not txt or txt.lower().startswith("lista"):
            continue
        linhas.append(txt)
    musicas = []
    for ln in linhas:
        partes = [x.strip() for x in ln.split(" - ")]
        if len(partes) < 2:
            continue
        ultimo = partes[-1]
        sem_link = "sem link" in ultimo.lower()
        tem_link = ultimo.startswith("http")
        video = ultimo if tem_link else ""
        if tem_link or sem_link:
            titulo, artista = partes[0], " - ".join(partes[1:-1])
        else:
            titulo, artista = partes[0], " - ".join(partes[1:])
        versao = ""
        m = re.search(r"\((ao vivo|acústico|live|medley)\)", titulo, re.I)
        if m:
            versao = m.group(1).title()
        musicas.append({"musica": titulo, "artista": artista.strip(),
                        "video": video, "versao": versao})
    return musicas


def ingerir_repertorio(con: sqlite3.Connection, drive: Path) -> dict:
    """Mescla o 'Repertório PIB 2025.docx' na tabela repertorio (dedupe por título)."""
    path = drive / REPERTORIO_DOCX_REL
    if not path.exists():
        return {"erro": f"docx não encontrado: {REPERTORIO_DOCX_REL}"}
    novas = parse_repertorio_docx(path)
    existentes = {_norm(r[0]): r[0] for r in con.execute("SELECT musica FROM repertorio")}
    add = 0
    for m in novas:
        chave = _norm(m["musica"])
        if not chave or chave in existentes:
            continue
        con.execute(
            "INSERT INTO repertorio (musica, artista, versao, video, categoria) "
            "VALUES (?,?,?,?,?)",
            (m["musica"], m["artista"], m["versao"], m["video"], "Repertório PIB 2025"))
        existentes[chave] = m["musica"]
        add += 1
    con.commit()
    total = con.execute("SELECT COUNT(*) FROM repertorio").fetchone()[0]
    return {"lidas": len(novas), "adicionadas": add, "total": total}


# títulos curados das 16 aulas do seminário (os nomes dos PDFs vieram com acentuação
# corrompida no disco — "REPERTÃ_RIO" etc.; aqui ficam legíveis).
_AULAS_SEMINARIO = {
    1: "Introdução ao curso",
    2: "Paixão, vocação e competência",
    3: "O papel do dirigente de louvor",
    4: "O que é o culto",
    5: "As dimensões do culto cristão",
    6: "Preparação pessoal para o culto",
    7: "Como ministrar louvor",
    8: "O que falar",
    9: "Emoção, razão e espiritualidade",
    10: "Relacionamentos interpessoais",
    11: "Escolha de repertório",
    12: "Autenticidade",
    13: "A importância da sinergia",
    14: "Cultura de feedback",
    15: "Trabalho em equipe",
    16: "Sonhe alto",
}
# correções pontuais de mojibake para os demais arquivos.
_MOJIBAKE = {"Ã©": "é", "Ã£": "ã", "Ã§": "ç", "Ã³": "ó", "Ãª": "ê", "Ã¡": "á",
             "Ã­": "í", "Ãº": "ú", "Ã³": "ó", "REPERTÃ_RIO": "REPERTÓRIO"}


def _titulo_treino(nome: str) -> tuple[str, int]:
    """Nome de arquivo → (título legível, ordem). Cura as 16 aulas e o mojibake comum."""
    t = re.sub(r"\.docx\.pdf$", "", nome, flags=re.I)
    t = re.sub(r"\.(pdf|docx?|pptx?)$", "", t, flags=re.I).strip()
    m = re.match(r"^\s*AULA\s+(\d+)", t, re.I)
    if m:
        n = int(m.group(1))
        sub = _AULAS_SEMINARIO.get(n)
        return (f"Aula {n} — {sub}" if sub else f"Aula {n}", n)
    if re.search(r"compilado", t, re.I):
        return ("Compilado das aulas", 90)
    if re.search(r"pilares", t, re.I):
        return ("Pilares do Ministério de Música", 91)
    for k, v in _MOJIBAKE.items():
        t = t.replace(k, v)
    return (t, 95)


def ingerir_treinamento(con: sqlite3.Connection, drive: Path) -> dict:
    """Indexa (NÃO move) PDFs/docx das pastas de treinamento na tabela treinamento.
    Idempotente: insere o que falta e ATUALIZA o título dos já indexados (cura nomes)."""
    add = upd = 0
    existentes = {r[1]: r[0] for r in con.execute("SELECT id, caminho FROM treinamento")}
    for pasta, tag in TREINAMENTO_DIRS:
        base = drive / pasta
        if not base.is_dir():
            continue
        for f in sorted(base.iterdir()):
            if f.is_file() and f.suffix.lower() in (".pdf", ".docx", ".doc", ".pptx"):
                rel = str((pasta / f.name)).replace("\\", "/")
                titulo, ordem = _titulo_treino(f.name)
                if rel in existentes:
                    con.execute("UPDATE treinamento SET titulo=?, tag=?, ordem=? WHERE id=?",
                                (titulo, tag, ordem, existentes[rel]))
                    upd += 1
                else:
                    con.execute("INSERT INTO treinamento (titulo, caminho, tag, ordem) VALUES (?,?,?,?)",
                                (titulo, rel, tag, ordem))
                    add += 1
    con.commit()
    total = con.execute("SELECT COUNT(*) FROM treinamento").fetchone()[0]
    return {"adicionados": add, "atualizados": upd, "total": total}


def _contagens(con) -> dict:
    out = {}
    for t in SCHEMA:
        try:
            out[t] = con.execute(f"SELECT COUNT(*) FROM {t}").fetchone()[0]
        except sqlite3.OperationalError:
            out[t] = 0
    return out


def main(argv=None) -> int:
    for s in (sys.stdout, sys.stderr):
        try:
            s.reconfigure(encoding="utf-8", errors="replace")
        except Exception:
            pass
    ap = argparse.ArgumentParser()
    ap.add_argument("--reset", action="store_true",
                    help="recria do zero a partir das sementes (descarta edições da tela)")
    ap.add_argument("--resumo", action="store_true", help="só imprime contagens")
    ap.add_argument("--ingerir-repertorio", action="store_true",
                    help="mescla o 'Repertório PIB 2025.docx' na tabela repertorio")
    ap.add_argument("--ingerir-treinamento", action="store_true",
                    help="indexa as aulas do seminário + literatura na tabela treinamento")
    a = ap.parse_args(argv)

    drive = _drive()
    db = caminhos.louvor_db_path(criar_dir=True)
    con = sqlite3.connect(db)

    if a.resumo:
        for t, n in _contagens(con).items():
            print(f"  {t:18} {n}")
        con.close()
        return 0

    if a.ingerir_repertorio:
        garantir_schema(con)
        r = ingerir_repertorio(con, drive)
        con.close()
        if r.get("erro"):
            print(f"[repertório] {r['erro']}")
            return 1
        print(f"[repertório] lidas {r['lidas']} · adicionadas {r['adicionadas']} · total {r['total']}")
        return 0

    if a.ingerir_treinamento:
        garantir_schema(con)
        r = ingerir_treinamento(con, drive)
        con.close()
        print(f"[treinamento] adicionados {r['adicionados']} · atualizados {r['atualizados']} · total {r['total']}")
        return 0

    if a.reset:
        for t in SCHEMA:
            con.execute(f"DROP TABLE IF EXISTS {t}")
        con.commit()

    garantir_schema(con)
    semear(con, forcar=a.reset)
    c = _contagens(con)
    con.close()
    modo = "reconstruído (reset)" if a.reset else "atualizado (preservando edições)"
    print(f"[louvor.db] {modo}: equipe {c['equipe']} · escala {c['escala']} · "
          f"jejum {c['jejum']} · repertório {c['repertorio']} · "
          f"setlist {c['setlist']} · indisponib. {c['indisponibilidade']} · "
          f"avisos {c['avisos']} · devocional {c['devocional']}")
    print(f"  banco: {DB_REL}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
