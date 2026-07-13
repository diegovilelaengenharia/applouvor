# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
"""applouvor/gestao — router ESCALA DE LOUVOR (porta 8020, lado líder).

Migrado da Central (igreja_louvor.py) na F4 da cirurgia. Lê E ESCREVE o banco
DEDICADO (1 SSOT) `louvor.db`, resolvido por `caminhos.py` (env `LOUVOR_DB`,
default `Vilela Igreja/0. Máquina/louvor.db`, fallback legado `3. Igreja/00. _Gestão`).
Serve a escala de cultos,
escala de jejum, equipe (com disponibilidade), repertório, indisponibilidades,
avisos e devocional; e o GERADOR DE EQUIPE JUSTO (porto do AutoSchedule): por
função, sugere quem tocou MENOS e há MAIS tempo, **excluindo indisponíveis**
(disponibilidade fixa + janelas de indisponibilidade da data).

Endpoints (GET):
  /api/louvor             → próximo culto + próximos + jejum + equipe + repertório
                            + indisponibilidades + avisos + devocional + config
  /api/louvor/gerar?data= → sugestão de equipe (rodízio justo) para a data
  /api/louvor/relatorio   → relatório de justiça (ranking de vezes/ano, quem está devendo)
  /api/louvor/estatisticas→ dados agregados para os gráficos (participação, função, mês…)
  /api/louvor/setlist?data= → setlist (ordem de músicas) de um culto

Endpoints (POST — escrita pela tela):
  /api/louvor/escala               → grava/atualiza a escala de um culto (upsert por data)
  /api/louvor/gerar-mes            → preenche os domingos do mês (rodízio justo) e grava
  /api/louvor/setlist              → salva a setlist de um culto (e registra histórico de canto)
  /api/louvor/repertorio           → adiciona/atualiza uma música (tom, bpm, links, tags)
  /api/louvor/indisponibilidade    → adiciona janela de indisponibilidade
  /api/louvor/indisponibilidade/remover
  /api/louvor/aviso                → adiciona aviso (mural)
  /api/louvor/aviso/remover
  /api/louvor/devocional           → adiciona devocional/Palavra da semana
  /api/louvor/devocional/remover

Zona LGPD: `vida-privada`. Degradação graciosa: sem o banco, ok=false.
"""
from __future__ import annotations

import calendar
import json
import sqlite3
import html
import re
import urllib.parse
import urllib.request
from datetime import date, datetime, timedelta
import sys
from pathlib import Path

from fastapi import APIRouter, Body

# SSOT de caminhos do subsistema gestão (raiz gestao/ = parents[2] deste arquivo).
sys.path.insert(0, str(Path(__file__).resolve().parents[2]))
import caminhos

router = APIRouter(tags=["louvor"])

# posições estruturais da escala (= colunas) e core conferido para lacunas.
_POSICOES = ["voz1", "voz2", "violao", "teclado", "baixo", "guitarra", "bateria"]


def _caminho_db() -> Path | None:
    return caminhos.achar_louvor_db()


def _db(rw: bool = False) -> sqlite3.Connection | None:
    cam = _caminho_db()
    if not cam:
        return None
    con = sqlite3.connect(cam) if rw else sqlite3.connect(f"file:{cam}?mode=ro", uri=True)
    con.row_factory = sqlite3.Row
    # banco mora no Drive (sync): espera curta em vez de "database is locked".
    # NUNCA ligar WAL aqui (par -wal/-shm + sync = corrupção) — PLANO-CENTRAL-3.
    con.execute("PRAGMA busy_timeout = 5000")
    return con


def _config(con) -> dict:
    cfg = {}
    try:
        for r in con.execute("SELECT chave, valor FROM config"):
            try:
                cfg[r["chave"]] = json.loads(r["valor"])
            except (ValueError, TypeError):
                cfg[r["chave"]] = r["valor"]
    except sqlite3.OperationalError:
        pass
    return cfg


def _equipe(con) -> list[dict]:
    return [dict(r) for r in con.execute(
        "SELECT nome, genero, funcao, instrumento, disponibilidade, disponivel, "
        "aniversario, naipe FROM equipe")]


def _escala(con) -> list[dict]:
    return [dict(r) for r in con.execute(
        "SELECT data, voz1, voz2, violao, teclado, baixo, guitarra, bateria, evento, obs "
        "FROM escala ORDER BY data")]


def _indisponibilidade(con) -> list[dict]:
    return [dict(r) for r in con.execute(
        "SELECT id, nome, inicio, fim, motivo FROM indisponibilidade ORDER BY inicio")]


def _preenchido(ev: dict) -> bool:
    return any((ev.get(k) or "").strip() for k in _POSICOES)


def _indisponiveis_na_data(con, alvo: date) -> set[str]:
    """Nomes com janela de indisponibilidade cobrindo a data-alvo."""
    fora = set()
    for r in _indisponibilidade(con):
        ini, fim = (r.get("inicio") or ""), (r.get("fim") or "")
        if ini and ini > alvo.isoformat():
            continue
        if fim and fim < alvo.isoformat():
            continue
        if r.get("nome"):
            fora.add(r["nome"].strip())
    return fora


def _historico(escala: list[dict], alvo: date) -> tuple[dict, dict]:
    vezes: dict[str, int] = {}
    ultima: dict[str, date] = {}
    for ev in escala:
        try:
            d = datetime.strptime(ev["data"], "%Y-%m-%d").date()
        except (ValueError, TypeError):
            continue
        if d >= alvo:
            continue
        for nome in (ev.get(k) for k in _POSICOES):
            nome = (nome or "").strip()
            if not nome:
                continue
            vezes[nome] = vezes.get(nome, 0) + 1
            if nome not in ultima or d > ultima[nome]:
                ultima[nome] = d
    return vezes, ultima


def _cands(equipe, vezes, ultima, filtro, fora: set[str]) -> list[dict]:
    out = []
    for m in equipe:
        if not m["disponivel"] or m["nome"] in fora:
            continue
        if not filtro(m):
            continue
        out.append({"nome": m["nome"], "vezes": vezes.get(m["nome"], 0),
                    "ultima": ultima[m["nome"]].strftime("%d/%m/%Y") if m["nome"] in ultima else None})
    out.sort(key=lambda c: (c["vezes"], ultima.get(c["nome"], date.min)))
    return out


def _gerar(con, escala, equipe, alvo: date) -> list[dict]:
    """Rodízio justo, respeitando disponibilidade fixa + indisponibilidade da data."""
    vezes, ultima = _historico(escala, alvo)
    fora = _indisponiveis_na_data(con, alvo)
    def is_voz(m): return "voz" in (m["funcao"] + " " + m["instrumento"]).lower()
    def inst(p): return lambda m: p in (m["instrumento"] + " " + m["funcao"]).lower()
    return [
        {"funcao": "Voz (mulher)", "candidatos": _cands(equipe, vezes, ultima,
            lambda m: is_voz(m) and m["genero"] == "F", fora)},
        {"funcao": "Voz (homem)", "candidatos": _cands(equipe, vezes, ultima,
            lambda m: is_voz(m) and m["genero"] == "M", fora)},
        {"funcao": "Violão", "candidatos": _cands(equipe, vezes, ultima, inst("viol"), fora)},
        {"funcao": "Bateria", "candidatos": _cands(equipe, vezes, ultima, inst("bater"), fora)},
    ]


@router.get("/api/louvor")
def louvor():
    con = _db()
    if not con:
        return {"ok": False, "aviso": "Banco do louvor não encontrado. Rode: py louvor_db.py.",
                "proximo": None, "proximos": [], "jejum": [], "equipe": [], "repertorio": [],
                "indisponibilidade": [], "avisos": [], "devocional": [], "treinamento": [], "config": {}}
    try:
        hoje = date.today()
        escala = _escala(con)
        futuros = [e for e in escala if e["data"] >= hoje.isoformat() and _preenchido(e)]
        jejum = [dict(r) for r in con.execute(
            "SELECT inicio, fim, pessoas FROM jejum WHERE fim >= ? ORDER BY inicio", (hoje.isoformat(),))]
        repertorio = [dict(r) for r in con.execute(
            "SELECT musica, artista, versao, tom, bpm, momento, letra, cifra, audio, video, "
            "tags, categoria, obs FROM repertorio ORDER BY musica")]
        canto = _ultima_canto(con)
        for s in repertorio:
            iso = canto.get(s["musica"])
            s["ultima_vez"] = iso
            s["semanas"] = _semanas_desde(iso, hoje)
        equipe = _equipe(con)
        indisp = [r for r in _indisponibilidade(con) if not r.get("fim") or r["fim"] >= hoje.isoformat()]
        avisos = [dict(r) for r in con.execute(
            "SELECT id, data, titulo, texto, fixado FROM avisos ORDER BY fixado DESC, data DESC")]
        devocional = [dict(r) for r in con.execute(
            "SELECT id, data, titulo, texto, referencia FROM devocional ORDER BY data DESC LIMIT 8")]
        treinamento = [dict(r) for r in con.execute(
            "SELECT id, titulo, caminho, tag FROM treinamento "
            "ORDER BY tag, COALESCE(ordem, 999), titulo")]
        return {
            "ok": True, "hoje": hoje.isoformat(),
            "proximo": futuros[0] if futuros else None,
            "proximos": futuros[:6],
            "jejum": jejum[:6],
            "equipe": equipe,
            "repertorio": repertorio,
            "indisponibilidade": indisp,
            "avisos": avisos,
            "devocional": devocional,
            "treinamento": treinamento,
            "config": _config(con),
            "meta": {"futuros": len(futuros), "equipe_ativa": sum(1 for m in equipe if m["disponivel"])},
        }
    finally:
        con.close()


def _clean_html(text: str) -> str:
    clean = re.sub(r'<p[^>]*>', '', text)
    clean = re.sub(r'</p>', '\n\n', clean)
    clean = re.sub(r'<br\s*/?>', '\n', clean)
    clean = re.sub(r'<[^>]+>', '', clean)
    return html.unescape(clean).strip()


def _buscar_letra_web(artista: str, musica: str) -> str | None:
    query = f"{artista} {musica} letra" if artista else f"{musica} letra"
    url = f"https://html.duckduckgo.com/html/?q={urllib.parse.quote(query)}"
    req = urllib.request.Request(
        url,
        headers={
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
        }
    )
    try:
        with urllib.request.urlopen(req) as response:
            html_content = response.read().decode('utf-8')
            
            # Find letras.mus.br links
            letras_links = re.findall(r'href="([^"]*letras\.mus\.br[^"]*)"', html_content)
            vagalume_links = re.findall(r'href="([^"]*vagalume\.com\.br[^"]*)"', html_content)
            
            # 1. Try Letras.mus.br first
            for link in letras_links:
                target_url = urllib.parse.unquote(link)
                if 'uddg=' in target_url:
                    target_url = target_url.split('uddg=')[1].split('&')[0]
                try:
                    req2 = urllib.request.Request(target_url, headers={'User-Agent': 'Mozilla/5.0'})
                    with urllib.request.urlopen(req2) as resp2:
                        content = resp2.read().decode('utf-8')
                        match = re.search(r'<div class="lyric-original"[^>]*>(.*?)</div>', content, re.DOTALL)
                        if not match:
                            match = re.search(r'<div class="cnt-letra[^>]*>(.*?)</div>', content, re.DOTALL)
                        if match:
                            return _clean_html(match.group(1))
                except Exception:
                    pass
            
            # 2. Try Vagalume next
            for link in vagalume_links:
                target_url = urllib.parse.unquote(link)
                if 'uddg=' in target_url:
                    target_url = target_url.split('uddg=')[1].split('&')[0]
                if "/browse/" in target_url or "/tags/" in target_url:
                    continue
                try:
                    req2 = urllib.request.Request(target_url, headers={'User-Agent': 'Mozilla/5.0'})
                    with urllib.request.urlopen(req2) as resp2:
                        content = resp2.read().decode('utf-8')
                        match = re.search(r'<div id="lyrics"[^>]*>(.*?)</div>', content, re.DOTALL)
                        if not match:
                            match = re.search(r'<div class="lyricArea"[^>]*>(.*?)</div>', content, re.DOTALL)
                        if match:
                            return _clean_html(match.group(1))
                except Exception:
                    pass
                    
    except Exception:
        pass
    return None


@router.get("/api/louvor/buscar-letra")
def api_buscar_letra(musica: str, artista: str = ""):
    if not musica:
        return {"ok": False, "erro": "Informe o título da música."}
    letra = _buscar_letra_web(artista, musica)
    if letra:
        return {"ok": True, "letra": letra}
    return {"ok": False, "erro": "Letra não encontrada na internet."}


@router.get("/api/louvor/gerar")
def gerar(data: str = ""):
    con = _db()
    if not con:
        return {"ok": False, "aviso": "Banco do louvor não encontrado."}
    try:
        escala = _escala(con)
        alvo = None
        if data:
            for fmt in ("%Y-%m-%d", "%d/%m/%Y"):
                try:
                    alvo = datetime.strptime(data, fmt).date()
                    break
                except ValueError:
                    continue
        if not alvo:
            hoje = date.today()
            fut = [e for e in escala if e["data"] >= hoje.isoformat()]
            alvo = datetime.strptime(fut[0]["data"], "%Y-%m-%d").date() if fut else hoje
        return {"ok": True, "data": alvo.strftime("%d/%m/%Y"),
                "sugestao": _gerar(con, escala, _equipe(con), alvo)}
    finally:
        con.close()


# ── histórico de canto (para "não toca há X semanas") ───────────────────────────
def _ultima_canto(con) -> dict:
    out = {}
    try:
        for r in con.execute("SELECT musica, MAX(data) d FROM historico_canto GROUP BY musica"):
            out[r["musica"]] = r["d"]
    except sqlite3.OperationalError:
        pass
    return out


def _semanas_desde(iso: str | None, hoje: date) -> int | None:
    if not iso:
        return None
    try:
        return max(0, (hoje - datetime.strptime(iso, "%Y-%m-%d").date()).days // 7)
    except (ValueError, TypeError):
        return None


# ── RELATÓRIO DE JUSTIÇA (ranking de participação no ano) ───────────────────────
def _is_voz(m) -> bool:
    return "voz" in (m["funcao"] + " " + m["instrumento"]).lower()


@router.get("/api/louvor/relatorio")
def relatorio():
    con = _db()
    if not con:
        return {"ok": False, "aviso": "Banco do louvor não encontrado.", "ranking": []}
    try:
        escala = _escala(con)
        equipe = _equipe(con)
        vezes: dict[str, int] = {}
        ultima: dict[str, str] = {}
        cultos = 0
        for ev in escala:
            if not _preenchido(ev):
                continue
            cultos += 1
            for col in _POSICOES:
                nome = (ev.get(col) or "").strip()
                if not nome:
                    continue
                vezes[nome] = vezes.get(nome, 0) + 1
                if nome not in ultima or ev["data"] > ultima[nome]:
                    ultima[nome] = ev["data"]
        ativos = [m for m in equipe if m["disponivel"]]
        # média entre os ativos que de fato escalam (voz/violão/bateria)
        escalaveis = [m for m in ativos
                      if _is_voz(m) or "viol" in (m["instrumento"] + m["funcao"]).lower()
                      or "bater" in (m["instrumento"] + m["funcao"]).lower()]
        soma = sum(vezes.get(m["nome"], 0) for m in escalaveis)
        media = round(soma / len(escalaveis), 1) if escalaveis else 0
        ranking = []
        for m in equipe:
            n = m["nome"]
            v = vezes.get(n, 0)
            ranking.append({
                "nome": n, "funcao": m["funcao"], "vezes": v,
                "ultima": ultima.get(n), "disponivel": bool(m["disponivel"]),
                "devendo": bool(m["disponivel"] and m in escalaveis and v < media),
            })
        ranking.sort(key=lambda x: (-x["vezes"], x["nome"]))
        return {"ok": True, "cultos": cultos, "media": media,
                "ranking": ranking, "ativos": len(ativos)}
    finally:
        con.close()


# ── ESTATÍSTICAS (dados agregados para os gráficos) ─────────────────────────────
_MESES_BR = ["", "jan", "fev", "mar", "abr", "mai", "jun", "jul", "ago", "set", "out", "nov", "dez"]


def _equilibrio(valores: list[int]) -> int:
    """Índice de equilíbrio 0–100 (100 = rodízio perfeitamente justo). 100·(1 − CV)."""
    vals = [v for v in valores]
    if len(vals) < 2:
        return 100
    media = sum(vals) / len(vals)
    if media == 0:
        return 100
    var = sum((v - media) ** 2 for v in vals) / len(vals)
    cv = (var ** 0.5) / media
    return max(0, min(100, round((1 - cv) * 100)))


@router.get("/api/louvor/estatisticas")
def estatisticas():
    con = _db()
    if not con:
        return {"ok": False, "aviso": "Banco do louvor não encontrado."}
    try:
        escala = _escala(con)
        equipe = _equipe(con)
        ativos_nomes = {m["nome"] for m in equipe if m["disponivel"]}

        vezes: dict[str, int] = {}
        por_funcao = {"Voz feminina": 0, "Voz masculina": 0, "Violão": 0,
                      "Teclado": 0, "Baixo": 0, "Guitarra": 0, "Bateria": 0}
        funcao_col = {"voz1": "Voz feminina", "voz2": "Voz masculina", "violao": "Violão",
                      "teclado": "Teclado", "baixo": "Baixo", "guitarra": "Guitarra",
                      "bateria": "Bateria"}
        por_mes: dict[str, int] = {}
        for ev in escala:
            if not _preenchido(ev):
                continue
            mes = ev["data"][:7]
            por_mes[mes] = por_mes.get(mes, 0) + 1
            for col, lab in funcao_col.items():
                nome = (ev.get(col) or "").strip()
                if nome:
                    por_funcao[lab] += 1
                    vezes[nome] = vezes.get(nome, 0) + 1

        participacao = sorted(
            [{"nome": n, "vezes": v, "ativo": n in ativos_nomes} for n, v in vezes.items()],
            key=lambda x: -x["vezes"])
        meses = sorted(por_mes)
        carga_mensal = [{"mes": _MESES_BR[int(m[5:7])], "cultos": por_mes[m]} for m in meses]

        # equilíbrio do rodízio de vozes (entre os ativos de cada naipe)
        vf = [vezes.get(m["nome"], 0) for m in equipe if m["disponivel"] and _is_voz(m) and m["genero"] == "F"]
        vm = [vezes.get(m["nome"], 0) for m in equipe if m["disponivel"] and _is_voz(m) and m["genero"] == "M"]

        # disponibilidade
        motivos: dict[str, int] = {}
        for m in equipe:
            if not m["disponivel"]:
                mot = (m["disponibilidade"] or "Indisponível").strip()
                motivos[mot] = motivos.get(mot, 0) + 1

        # repertório
        rep = [dict(r) for r in con.execute(
            "SELECT musica, artista, categoria, video, tom FROM repertorio")]
        por_categoria: dict[str, int] = {}
        por_artista: dict[str, int] = {}
        com_link = 0
        for s in rep:
            cat = (s.get("categoria") or "Sem categoria").split(" · ")[0].strip() or "Sem categoria"
            por_categoria[cat] = por_categoria.get(cat, 0) + 1
            art = (s.get("artista") or "").strip()
            if art:
                por_artista[art] = por_artista.get(art, 0) + 1
            if (s.get("video") or "").startswith("http"):
                com_link += 1
        top_artistas = sorted(por_artista.items(), key=lambda x: -x[1])[:10]

        return {
            "ok": True,
            "participacao": participacao,
            "por_funcao": [{"funcao": k, "n": v} for k, v in por_funcao.items() if v],
            "carga_mensal": carga_mensal,
            "equilibrio": {"feminino": _equilibrio(vf), "masculino": _equilibrio(vm)},
            "disponibilidade": {
                "ativos": len(ativos_nomes), "indisponiveis": len(equipe) - len(ativos_nomes),
                "motivos": [{"motivo": k, "n": v} for k, v in sorted(motivos.items(), key=lambda x: -x[1])]},
            "repertorio": {
                "total": len(rep), "com_link": com_link,
                "por_categoria": [{"categoria": k, "n": v} for k, v in sorted(por_categoria.items(), key=lambda x: -x[1])],
                "top_artistas": [{"artista": k, "n": v} for k, v in top_artistas]},
        }
    finally:
        con.close()


# ── SETLIST (ordem de músicas de um culto) ──────────────────────────────────────
@router.get("/api/louvor/setlist")
def get_setlist(data: str = ""):
    con = _db()
    if not con:
        return {"ok": False, "aviso": "Banco do louvor não encontrado.", "itens": []}
    try:
        itens = [dict(r) for r in con.execute(
            "SELECT id, data, ordem, musica, momento, obs FROM setlist WHERE data=? ORDER BY ordem",
            (data,))]
        return {"ok": True, "data": data, "itens": itens}
    finally:
        con.close()


# ── GERADOR DE MÊS (rodízio justo, preenche as lacunas dos domingos) ────────────
def _domingos_do_mes(ano: int, mes: int) -> list[date]:
    n = calendar.monthrange(ano, mes)[1]
    return [date(ano, mes, dia) for dia in range(1, n + 1) if date(ano, mes, dia).weekday() == 6]


def _melhor(pool, vezes, last_assigned, d: date) -> str:
    """Escolhe o mais 'devido': menos vezes, evitando 2 semanas seguidas, depois há mais tempo."""
    prev = d - timedelta(days=7)
    def chave(m):
        nm = m["nome"]
        back = 1 if last_assigned.get(nm) == prev else 0
        ult = last_assigned.get(nm) or date.min
        return (vezes.get(nm, 0), back, ult)
    return min(pool, key=chave)["nome"]


def _gerar_mes(con, ano: int, mes: int, sobrescrever: bool) -> list[dict]:
    equipe = _equipe(con)
    escala = _escala(con)
    cfg = _config(con)
    fixos = cfg.get("equipe_fixa") or {"bateria": "Diego", "violao": "Thalyta"}
    evento_padrao = cfg.get("evento_padrao") or "Culto de domingo"

    domingos = _domingos_do_mes(ano, mes)
    if not domingos:
        return []
    vezes, ultima_d = _historico(escala, domingos[0])
    last_assigned: dict[str, date] = dict(ultima_d)
    by_data = {e["data"]: e for e in escala}

    def _viol(m): return "viol" in (m["instrumento"] + m["funcao"]).lower()
    def _bat(m): return "bater" in (m["instrumento"] + m["funcao"]).lower()
    pools = {
        "vozF": [m for m in equipe if _is_voz(m) and m["genero"] == "F"],
        "vozM": [m for m in equipe if _is_voz(m) and m["genero"] == "M"],
        "violao": [m for m in equipe if _viol(m)],
        "bateria": [m for m in equipe if _bat(m)],
    }
    plano = [("bateria", "bateria"), ("violao", "violao"), ("voz1", "vozF"), ("voz2", "vozM")]

    resultados = []
    for d in domingos:
        diso = d.isoformat()
        fora = _indisponiveis_na_data(con, d)
        ev = dict(by_data.get(diso, {}))
        assigned = set()
        row = {"data": diso}
        for col, pk in plano:
            atual = (ev.get(col) or "").strip()
            if atual and not sobrescrever:
                row[col] = atual
                assigned.add(atual)
                continue
            disp = [m for m in pools[pk] if m["disponivel"]
                    and m["nome"] not in fora and m["nome"] not in assigned]
            fixo = fixos.get(col)
            if fixo and any(m["nome"] == fixo for m in disp):
                escolhido = fixo
            elif disp:
                escolhido = _melhor(disp, vezes, last_assigned, d)
            else:
                escolhido = ""
            row[col] = escolhido
            if escolhido:
                assigned.add(escolhido)
        for nm in assigned:
            vezes[nm] = vezes.get(nm, 0) + 1
            last_assigned[nm] = d
        for col in ("teclado", "baixo", "guitarra"):
            row[col] = ev.get(col) or ""
        row["evento"] = ev.get("evento") or evento_padrao
        row["obs"] = ev.get("obs") or ""
        resultados.append(row)
    return resultados


# ── ESCRITA (POST) — grava direto no SSOT louvor.db ─────────────────────────────
def _erro_sem_banco():
    return {"ok": False, "erro": "Banco do louvor não encontrado. Rode: py louvor_db.py."}


def _upsert_escala(con, row: dict) -> None:
    cols = ["voz1", "voz2", "violao", "teclado", "baixo", "guitarra", "bateria", "evento", "obs"]
    vals = {c: row.get(c, "") for c in cols}
    if con.execute("SELECT 1 FROM escala WHERE data=?", (row["data"],)).fetchone():
        sets = ", ".join(f"{c}=?" for c in cols)
        con.execute(f"UPDATE escala SET {sets} WHERE data=?", (*vals.values(), row["data"]))
    else:
        nomes = ", ".join(["data", *cols])
        ph = ", ".join(["?"] * (len(cols) + 1))
        con.execute(f"INSERT INTO escala ({nomes}) VALUES ({ph})", (row["data"], *vals.values()))


@router.post("/api/louvor/gerar-mes")
def post_gerar_mes(body: dict = Body(default={})):
    """Preenche os domingos de um mês (rodízio justo) e grava. Por padrão só completa
    as LACUNAS (preserva o que já foi escalado); sobrescrever=true regenera tudo."""
    mes = str(body.get("mes") or "").strip()
    try:
        ano, m = int(mes[:4]), int(mes[5:7])
        date(ano, m, 1)
    except (ValueError, IndexError):
        return {"ok": False, "erro": "Informe o mês (AAAA-MM)."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        linhas = _gerar_mes(con, ano, m, bool(body.get("sobrescrever")))
        for row in linhas:
            _upsert_escala(con, row)
        con.commit()
        return {"ok": True, "mes": mes, "domingos": len(linhas), "escala": linhas}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/escala")
def post_escala(body: dict = Body(default={})):
    """Upsert da escala de um culto (chave = data ISO). Campos ausentes viram ''."""
    data = str(body.get("data") or "").strip()
    if not data:
        return {"ok": False, "erro": "Informe a data (AAAA-MM-DD)."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        row = {"data": data}
        row.update({p: str(body.get(p) or "").strip() for p in _POSICOES})
        row["evento"] = str(body.get("evento") or "Culto de domingo").strip()
        row["obs"] = str(body.get("obs") or "").strip()
        _upsert_escala(con, row)
        con.commit()
        return {"ok": True, "data": data}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/indisponibilidade")
def post_indisp(body: dict = Body(default={})):
    nome = str(body.get("nome") or "").strip()
    if not nome:
        return {"ok": False, "erro": "Informe o nome."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        cur = con.execute(
            "INSERT INTO indisponibilidade (nome, inicio, fim, motivo) VALUES (?,?,?,?)",
            (nome, str(body.get("inicio") or "").strip(), str(body.get("fim") or "").strip(),
             str(body.get("motivo") or "").strip()))
        con.commit()
        return {"ok": True, "id": cur.lastrowid}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/indisponibilidade/remover")
def del_indisp(body: dict = Body(default={})):
    return _remover(con_tabela="indisponibilidade", id_=body.get("id"))


@router.post("/api/louvor/aviso")
def post_aviso(body: dict = Body(default={})):
    titulo = str(body.get("titulo") or "").strip()
    if not titulo:
        return {"ok": False, "erro": "Informe o título."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        cur = con.execute(
            "INSERT INTO avisos (data, titulo, texto, fixado) VALUES (?,?,?,?)",
            (str(body.get("data") or date.today().isoformat()).strip(), titulo,
             str(body.get("texto") or "").strip(), 1 if body.get("fixado") else 0))
        con.commit()
        return {"ok": True, "id": cur.lastrowid}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/aviso/remover")
def del_aviso(body: dict = Body(default={})):
    return _remover(con_tabela="avisos", id_=body.get("id"))


@router.post("/api/louvor/devocional")
def post_devocional(body: dict = Body(default={})):
    titulo = str(body.get("titulo") or "").strip()
    if not titulo:
        return {"ok": False, "erro": "Informe o título."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        cur = con.execute(
            "INSERT INTO devocional (data, titulo, texto, referencia) VALUES (?,?,?,?)",
            (str(body.get("data") or date.today().isoformat()).strip(), titulo,
             str(body.get("texto") or "").strip(), str(body.get("referencia") or "").strip()))
        con.commit()
        return {"ok": True, "id": cur.lastrowid}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/devocional/remover")
def del_devocional(body: dict = Body(default={})):
    return _remover(con_tabela="devocional", id_=body.get("id"))


def _remover(con_tabela: str, id_) -> dict:
    try:
        ident = int(id_)
    except (TypeError, ValueError):
        return {"ok": False, "erro": "Informe o id."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        con.execute(f"DELETE FROM {con_tabela} WHERE id=?", (ident,))
        con.commit()
        return {"ok": True}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/setlist")
def post_setlist(body: dict = Body(default={})):
    """Salva a setlist de um culto (substitui a anterior) e registra o histórico de
    canto (musica+data) — alimenta o "não toca há X semanas"."""
    data = str(body.get("data") or "").strip()
    if not data:
        return {"ok": False, "erro": "Informe a data do culto."}
    itens = body.get("itens") or []
    if not isinstance(itens, list):
        return {"ok": False, "erro": "itens deve ser uma lista."}
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        con.execute("DELETE FROM setlist WHERE data=?", (data,))
        con.execute("DELETE FROM historico_canto WHERE data=?", (data,))
        for i, it in enumerate(itens):
            musica = str(it.get("musica") or "").strip()
            if not musica:
                continue
            con.execute(
                "INSERT INTO setlist (data, ordem, musica, momento, obs) VALUES (?,?,?,?,?)",
                (data, it.get("ordem", i), musica,
                 str(it.get("momento") or "").strip(), str(it.get("obs") or "").strip()))
            con.execute("INSERT INTO historico_canto (musica, data) VALUES (?,?)", (musica, data))
        con.commit()
        return {"ok": True, "data": data, "itens": len(itens)}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


@router.post("/api/louvor/repertorio")
def post_repertorio(body: dict = Body(default={})):
    """Adiciona/atualiza uma música (chave = título). Preenche tom, bpm, momento, links, tags."""
    musica = str(body.get("musica") or "").strip()
    if not musica:
        return {"ok": False, "erro": "Informe o título da música."}
    campos = ["artista", "versao", "tom", "bpm", "duracao", "momento",
              "letra", "cifra", "audio", "video", "tags", "categoria", "obs"]
    con = _db(rw=True)
    if not con:
        return _erro_sem_banco()
    try:
        vals = {c: str(body.get(c) or "").strip() for c in campos}
        existe = con.execute("SELECT 1 FROM repertorio WHERE musica=?", (musica,)).fetchone()
        if existe:
            # só atualiza os campos enviados (não apaga o que não veio no body)
            envs = {c: vals[c] for c in campos if c in body}
            if envs:
                sets = ", ".join(f"{c}=?" for c in envs)
                con.execute(f"UPDATE repertorio SET {sets} WHERE musica=?", (*envs.values(), musica))
        else:
            cols = ", ".join(["musica", *campos])
            ph = ", ".join(["?"] * (len(campos) + 1))
            con.execute(f"INSERT INTO repertorio ({cols}) VALUES ({ph})", (musica, *vals.values()))
        con.commit()
        return {"ok": True, "musica": musica}
    except sqlite3.Error as e:
        return {"ok": False, "erro": str(e)}
    finally:
        con.close()


# ───────────────────────── imagem semanal do WhatsApp ─────────────────────────
# Reusa a ferramenta já existente do ecossistema (`0. Sistema/ferramentas/gerar_imagem_louvor.py`,
# Pillow local, sem API paga) que lê o MESMO louvor.db. A Central só dispara e serve o PNG.

def _ferramentas_dir() -> Path | None:
    """As ferramentas agora moram DENTRO do repo (gestao/ferramentas/)."""
    d = caminhos.ferramentas_dir()
    return d if (d / "gerar_imagem_louvor.py").exists() else None


def _png_escala(data_iso: str | None) -> Path | None:
    """Caminho esperado do PNG de uma data (sem gerar)."""
    saida = caminhos.saida_imagens()
    if data_iso:
        return saida / f"escala-louvor-{data_iso}.png"
    return saida


@router.post("/api/louvor/gerar-imagem")
def post_gerar_imagem(body: dict = Body(default={})):
    """Gera (Pillow) a imagem da escala da semana p/ o WhatsApp e devolve a URL p/ preview/baixar."""
    import subprocess
    import sys as _sys
    fdir = _ferramentas_dir()
    if not fdir:
        return {"ok": False, "erro": "Ferramenta gerar_imagem_louvor.py não encontrada."}
    data_iso = str(body.get("data") or "").strip()
    args = [_sys.executable, "gerar_imagem_louvor.py"] + ([data_iso] if data_iso else [])
    try:
        r = subprocess.run(args, cwd=str(fdir), capture_output=True, text=True, timeout=90)
    except subprocess.TimeoutExpired:
        return {"ok": False, "erro": "A geração da imagem demorou demais."}
    if r.returncode != 0:
        return {"ok": False, "erro": (r.stderr or r.stdout or "Falha ao gerar a imagem.").strip()[:300]}
    # a saída do script informa "Imagem gerada: <caminho>"
    destino = ""
    for ln in (r.stdout or "").splitlines():
        if "Imagem gerada:" in ln:
            destino = ln.split("Imagem gerada:", 1)[1].strip()
    nome = Path(destino).name if destino else ""
    return {"ok": True, "arquivo": nome, "url": f"/api/louvor/imagem?nome={urllib.parse.quote(nome)}"}


@router.get("/api/louvor/imagem")
def get_imagem(nome: str = "", data: str = ""):
    """Serve o PNG gerado (preview/download na Central)."""
    from fastapi.responses import FileResponse, JSONResponse
    base = _png_escala(None)
    if not base:
        return JSONResponse({"ok": False, "erro": "Pasta de escalas não encontrada."}, status_code=404)
    alvo = base / (nome if nome else f"escala-louvor-{data}.png")
    # trava de segurança: só serve PNG de dentro da pasta de escalas
    try:
        alvo = alvo.resolve()
        if base.resolve() not in alvo.parents or alvo.suffix.lower() != ".png" or not alvo.exists():
            return JSONResponse({"ok": False, "erro": "Imagem não encontrada."}, status_code=404)
    except OSError:
        return JSONResponse({"ok": False, "erro": "Caminho inválido."}, status_code=400)
    return FileResponse(str(alvo), media_type="image/png", filename=alvo.name)
