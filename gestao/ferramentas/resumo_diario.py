# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
"""Resumo diário da Igreja (Louvor) → a caixa de entrada da Central.

A Central deixou de acompanhar os projetos AO VIVO (decisão 5 da cirurgia): passa a
ser informativa, lendo um JSON por projeto. Este é o lado Igreja desse contrato —
roda no fim do dia (ou pelo Agendador) e deposita `igreja.json` em
`Vilela Sistema\\_central\\resumos\\`. **A Central nunca importa código daqui — só lê
este JSON.** Ponte de mão única, contrato v1 (mesmo schema da prefeitura).

Uso:  py ferramentas/resumo_diario.py            (grava e imprime o caminho)
      py ferramentas/resumo_diario.py --stdout   (só imprime o JSON, não grava)
"""
from __future__ import annotations

import datetime as _dt
import json
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))       # ferramentas/ (louvor_db)
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))   # gestao/ (caminhos)

import caminhos          # noqa: E402
import louvor_db as ldb  # noqa: E402

SCHEMA = 1  # contrato v1 (congelado — a Central valida contra ele)

_CORE = ldb.POSICOES_CORE          # ["voz1","voz2","violao","bateria"]
_LABEL = ldb.POSICOES_LABEL


def _br(iso: str) -> str:
    p = (iso or "").split("-")
    return f"{p[2]}/{p[1]}" if len(p) == 3 else iso


def _git(*args: str) -> str:
    try:
        r = subprocess.run(["git", *args], cwd=str(caminhos.repo_raiz()),
                           capture_output=True, encoding="utf-8", errors="replace", timeout=15)
        return (r.stdout or "").strip() if r.returncode == 0 else ""
    except (OSError, subprocess.SubprocessError):
        return ""


def _governanca() -> dict:
    """Hash + data (nunca o assunto do commit). A Central mostra no card de governança."""
    return {
        "ultimo_commit": _git("log", "-1", "--format=%h %ad", "--date=short") or None,
        "branch": _git("rev-parse", "--abbrev-ref", "HEAD") or None,
        "sujo": bool(_git("status", "--porcelain")),
        "testes": None,        # F8 (pronto.ps1 do applouvor)
        "ultimo_backup": None,
    }


def montar() -> dict:
    """O resumo. Dado do ministério do Diego (equipe própria) — sem PII de cidadão."""
    hoje = _dt.date.today()
    iso_hoje = hoje.isoformat()
    con = ldb.conectar(ro=True)
    if not con:
        return {
            "schema": SCHEMA, "projeto": "igreja", "rotulo": "Vilela Igreja",
            "gerado_em": _dt.datetime.now().isoformat(timespec="seconds"),
            "kpis": [], "destaques": ["banco do louvor não encontrado"],
            "pendencias": [], "prazos": [],
            "proximo_passo": "rodar py ferramentas/louvor_db.py", "governanca": _governanca(),
        }
    try:
        escala = [dict(r) for r in con.execute(
            "SELECT data, voz1, voz2, violao, teclado, baixo, guitarra, bateria, obs "
            "FROM escala WHERE data >= ? ORDER BY data", (iso_hoje,))]
        n_ativos = con.execute("SELECT COUNT(*) FROM equipe WHERE disponivel=1").fetchone()[0]
        n_rep = con.execute("SELECT COUNT(*) FROM repertorio").fetchone()[0]
        jejum = [dict(r) for r in con.execute(
            "SELECT inicio, fim, pessoas FROM jejum WHERE fim >= ? ORDER BY inicio LIMIT 4",
            (iso_hoje,))]
    finally:
        con.close()

    def preenchido(ev):
        return any((ev.get(k) or "").strip() for k in _CORE)

    def lacunas(ev):
        return [_LABEL.get(k, k) for k in _CORE if not (ev.get(k) or "").strip()]

    futuros = [e for e in escala if preenchido(e)]
    com_lacuna = [e for e in escala if lacunas(e)][:8]
    prox = futuros[0] if futuros else (escala[0] if escala else None)

    destaques = []
    if prox:
        escalados = [f"{prox[k]}" for k in ldb.POSICOES if (prox.get(k) or "").strip()]
        destaques.append(f"Próximo culto {_br(prox['data'])}: " +
                         (", ".join(escalados) if escalados else "a escalar"))
    if jejum:
        destaques.append(f"Jejum da semana: {jejum[0]['pessoas']}")
    if not destaques:
        destaques = ["sem cultos futuros escalados"]

    prazos = []
    if prox:
        prazos.append({"rotulo": "Próximo culto", "data": prox["data"]})
    if jejum:
        prazos.append({"rotulo": "Jejum termina", "data": jejum[0]["fim"]})

    if com_lacuna:
        prox_lac = com_lacuna[0]
        proximo_passo = (f"escalar {', '.join(lacunas(prox_lac))} do culto de "
                        f"{_br(prox_lac['data'])} (pela tela 8020 ou gerar o mês)")
    else:
        proximo_passo = "escala em dia — gerar a imagem da semana p/ o grupo"

    return {
        "schema": SCHEMA,
        "projeto": "igreja",
        "rotulo": "Vilela Igreja",
        "gerado_em": _dt.datetime.now().isoformat(timespec="seconds"),
        "kpis": [
            {"rotulo": "Cultos à frente", "valor": len(futuros)},
            {"rotulo": "Equipe ativa", "valor": n_ativos},
            {"rotulo": "Músicas no repertório", "valor": n_rep},
            {"rotulo": "Cultos com lacuna", "valor": len(com_lacuna)},
        ],
        "destaques": destaques,
        "pendencias": ([f"{len(com_lacuna)} culto(s) com posição a escalar"]
                       if com_lacuna else []),
        "prazos": prazos,
        "proximo_passo": proximo_passo,
        "governanca": _governanca(),
    }


def main() -> int:
    resumo = montar()
    if "--stdout" in sys.argv:
        print(json.dumps(resumo, ensure_ascii=False, indent=2))
        return 0
    destino = caminhos.resumos()
    destino.mkdir(parents=True, exist_ok=True)
    alvo = destino / "igreja.json"
    alvo.write_text(json.dumps(resumo, ensure_ascii=False, indent=2), encoding="utf-8")
    print(alvo)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
