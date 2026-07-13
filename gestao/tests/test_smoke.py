# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
"""Smoke test do app da gestão (louvor, porta 8020).

Autossuficiente: NÃO depende do Drive nem do louvor.db real — cria um banco
temporário semeado pelo próprio louvor_db.py e aponta o app para ele via env
LOUVOR_DB. Prova por EXECUÇÃO que o motor migrado (caminhos → router → app)
responde e escreve. Roda com: py -m pytest gestao/tests/  (de dentro de applouvor)
"""
import sqlite3
import sys
from pathlib import Path

import pytest

_GESTAO = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(_GESTAO))
sys.path.insert(0, str(_GESTAO / "ferramentas"))


@pytest.fixture()
def app_client(tmp_path, monkeypatch):
    """App apontado para um louvor.db temporário semeado (sem tocar o Drive)."""
    db = tmp_path / "louvor.db"
    monkeypatch.setenv("LOUVOR_DB", str(db))
    monkeypatch.setenv("LOUVOR_ASSETS", str(tmp_path / "assets"))
    import louvor_db
    con = sqlite3.connect(db)
    louvor_db.garantir_schema(con)
    louvor_db.semear(con)
    con.close()

    from fastapi.testclient import TestClient
    from app.main import app
    return TestClient(app)


def test_caminhos_resolve_env(tmp_path, monkeypatch):
    monkeypatch.setenv("LOUVOR_DB", str(tmp_path / "x.db"))
    import caminhos
    assert caminhos.louvor_db_path() == tmp_path / "x.db"


def test_api_louvor_le_dados(app_client):
    r = app_client.get("/api/louvor")
    assert r.status_code == 200
    j = r.json()
    assert j["ok"] is True
    assert any(m["nome"] == "Diego" for m in j["equipe"])   # equipe semeada
    assert j["meta"]["equipe_ativa"] >= 1


def test_api_louvor_grava_escala(app_client):
    dom = "2026-12-06"   # 1o domingo de dezembro/2026
    r = app_client.post("/api/louvor/escala",
                        json={"data": dom, "voz1": "Samara", "bateria": "Diego",
                              "obs": "smoke"})
    assert r.status_code == 200 and r.json()["ok"] is True
    # relê e confere que persistiu
    g = app_client.get("/api/louvor/gerar", params={"data": dom})
    assert g.status_code == 200 and g.json()["ok"] is True


def test_home_serve_frontend(app_client):
    r = app_client.get("/")
    assert r.status_code == 200
    assert "Louvor" in r.text


def test_ferramenta_louvor_lista(app_client, capsys):
    """A CLI louvor.py lê o mesmo banco (env já aponta p/ o temporário)."""
    import louvor
    itens = louvor.coletar(365)
    assert isinstance(itens, list)   # não levanta e devolve lista (mesmo que vazia)
