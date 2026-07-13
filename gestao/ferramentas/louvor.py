# Copyright (c) 2026 Diego Vilela. Uso pessoal/ministério — ecossistema Vilela (lado Deiso: igreja).
"""Planejador de Louvor (deiso #8) — escala dos próximos cultos + lacunas.

Lê o SSOT `3. Igreja\\00. _Gestão\\louvor.db` (construído/editado por louvor_db.py;
também escrito pela Central). Lista os próximos cultos com a equipe escalada,
APONTA AS LACUNAS (posição core sem ninguém) e mostra o jejum da semana. Sem API
paga, 100% local.

Uso:
    py louvor.py            # próximos 30 dias
    py louvor.py 60         # próximos 60 dias
    py louvor.py --json     # saída JSON
"""
import json
import sys
from datetime import date, timedelta
from pathlib import Path

try:
    sys.stdout.reconfigure(encoding="utf-8")
except (AttributeError, ValueError):
    pass

sys.path.insert(0, str(Path(__file__).resolve().parent))
import louvor_db as ldb

# rótulos amigáveis e posições conferidas para lacuna (espelham o louvor_db).
LABELS = ldb.POSICOES_LABEL
CORE = ldb.POSICOES_CORE


def _config(con, chave, padrao):
    row = con.execute("SELECT valor FROM config WHERE chave=?", (chave,)).fetchone()
    if not row:
        return padrao
    try:
        return json.loads(row["valor"])
    except (ValueError, TypeError):
        return row["valor"]


def coletar(janela: int, hoje: date | None = None) -> list[dict]:
    hoje = hoje or date.today()
    con = ldb.conectar(ro=True)
    if not con:
        return []
    try:
        labels = _config(con, "posicoes_label", LABELS)
        core = _config(con, "posicoes_core", CORE)
        limite = (hoje + timedelta(days=janela)).isoformat()
        rows = con.execute(
            "SELECT * FROM escala WHERE data >= ? AND data <= ? ORDER BY data",
            (hoje.isoformat(), limite)).fetchall()
    finally:
        con.close()

    saida = []
    for r in rows:
        ev = dict(r)
        equipe = {labels.get(p, p): ev.get(p, "") for p in labels
                  if str(ev.get(p, "") or "").strip()}
        faltando = [labels.get(p, p) for p in core if not str(ev.get(p, "") or "").strip()]
        saida.append({
            "data": ev["data"],
            "evento": ev.get("evento") or "Culto",
            "equipe": equipe,
            "faltando": faltando,
            "obs": ev.get("obs") or "",
            "dias": (date.fromisoformat(ev["data"]) - hoje).days,
        })
    saida.sort(key=lambda x: x["dias"])
    return saida


def _quando(dias: int) -> str:
    return "HOJE" if dias == 0 else ("amanhã" if dias == 1 else f"em {dias} dias")


def main(argv):
    janela = 30
    como_json = False
    for a in argv:
        if a == "--json":
            como_json = True
        elif a.isdigit():
            janela = int(a)

    itens = coletar(janela)
    if como_json:
        print(json.dumps(itens, ensure_ascii=False, indent=2))
        return 0

    con = ldb.conectar(ro=True)
    if not con:
        print(f"(sem banco: {ldb.DB_REL} — rode: py louvor_db.py)")
        return 0
    try:
        n_equipe = con.execute("SELECT COUNT(*) FROM equipe WHERE disponivel=1").fetchone()[0]
        n_rep = con.execute("SELECT COUNT(*) FROM repertorio").fetchone()[0]
    finally:
        con.close()

    print(f"🎶 PLANEJADOR DE LOUVOR — próximos {janela} dias  "
          f"(equipe ativa: {n_equipe} · repertório: {n_rep})")
    if not itens:
        print("  (nenhum culto no período)")
        return 0
    for it in itens:
        print(f"\n  ⛪ {_quando(it['dias']):>9}  {it['data']}  {it['evento']}")
        for pos, quem in it["equipe"].items():
            print(f"       • {pos}: {quem}")
        if it["faltando"]:
            print(f"       ⚠️ escalar: {', '.join(it['faltando'])}")
        if it["obs"]:
            print(f"       ({it['obs']})")
    print("\n  Dica: edite a escala na Central (aba Louvor) — grava direto no louvor.db.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
