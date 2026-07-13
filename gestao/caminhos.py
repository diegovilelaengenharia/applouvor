# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
"""caminhos.py — SSOT de caminhos do subsistema `gestao/` (Louvor operacional).

Ponto ÚNICO de resolução de caminhos do app de gestão e das ferramentas. Resolve,
de forma agnóstica de máquina/mount (o ecossistema roda em 2 PCs, e o Google Drive
pode estar em `Transmissão no Google Drive\\Meu Drive`, `~\\Meu Drive` ou numa letra
de unidade `G:\\Meu Drive`):

  - a RAIZ do Google Drive (por env, junction ou marcador-âncora);
  - o `louvor.db` (SSOT do ministério) — via env `LOUVOR_DB`, com destino canônico
    novo (`Vilela Igreja/0. Máquina`) e fallback legado (`3. Igreja/00. _Gestão`);
  - a pasta de ASSETS do Ministério (logo + saída dos PNGs da escala) — via env
    `LOUVOR_ASSETS`, com destino novo e fallback legado.

Este é o único arquivo a editar quando a F6 da cirurgia renomear as pastas do Drive
(`3. Igreja` → `Vilela Igreja`): a resolução por env + fallback já sobrevive à
transição, e o app não precisa saber onde o Drive está montado.

Env (escape hatches, 1 vence):
    LOUVOR_DB       caminho explícito do louvor.db
    LOUVOR_ASSETS   pasta do "Ministério de Louvor" (logo + escalas geradas)
    VILELA_DRIVE    raiz do Drive (pessoal/igreja/sistema)
    VILELA_ROOT     raiz local do código (default C:\\vilela) — onde mora a junction `sistema`
"""
from __future__ import annotations

import os
import string
from pathlib import Path

# ── marcadores-âncora (qualquer um identifica a raiz do Drive) ───────────────────
# Sobrevivem a renomeações de nível superior e à pasta migrar entre a raiz e
# 'Diego (Notebook ACER)'. "Vilela Igreja" é o marcador PÓS-cirurgia (F6).
_MARCADORES = ("0. Sistema", "00. Sistema", "1. Pessoal", "3. Igreja", "Vilela Igreja")

_BASES = (
    Path.home() / "Transmissão no Google Drive" / "Meu Drive",
    Path.home() / "Meu Drive",
)

# louvor.db — destino canônico (pós-F6) e legado (pré-F6).
_DB_NOVO = Path("Vilela Igreja") / "0. Máquina" / "louvor.db"
_DB_LEGADO = Path("3. Igreja") / "00. _Gestão" / "louvor.db"

# assets do Ministério — destino canônico (pós-F6) e legado (pré-F6).
_ASSETS_NOVO = Path("Vilela Igreja") / "01. PIB Oliveira" / "Ministério de Louvor"
_ASSETS_LEGADO = Path("3. Igreja") / "01. PIB Oliveira" / "Ministério de Louvor"

_SAIDA_IMG = "Escalas Semanais (geradas)"
_LOGO_NOME = "logo-igreja-branca-pib-oliveira-204.png"


# ── raiz do Drive ────────────────────────────────────────────────────────────────
def _por_junction() -> Path | None:
    """Raiz via a junction estável '<VILELA_ROOT|C:\\vilela>\\sistema' -> '<raiz>\\0. Sistema'."""
    try:
        j = Path(os.environ.get("VILELA_ROOT") or r"C:\vilela") / "sistema"
        if j.is_dir():
            pai = j.resolve().parent
            if any((pai / m).is_dir() for m in _MARCADORES):
                return pai
    except OSError:
        pass
    return None


def _bases_extra():
    for letra in string.ascii_uppercase:
        base = Path(f"{letra}:/Meu Drive")
        try:
            if base.is_dir():
                yield base
        except OSError:
            continue


def _candidatos():
    j = _por_junction()
    if j:
        yield j
    for base in (*_BASES, *_bases_extra()):
        yield base / "Diego (Notebook ACER)"
        yield base


def raiz_drive() -> Path:
    """Raiz do lado pessoal/igreja/sistema no Drive. Agnóstica de máquina e de mount."""
    env = os.environ.get("VILELA_DRIVE")
    if env:
        p = Path(env)
        if p.is_dir():
            return p
    for c in _candidatos():
        if any((c / m).is_dir() for m in _MARCADORES):
            return c
    return _BASES[0] / "Diego (Notebook ACER)"   # padrão (Drive vivo)


# ── louvor.db (SSOT) ─────────────────────────────────────────────────────────────
def louvor_db_path(criar_dir: bool = False) -> Path:
    """Caminho canônico do louvor.db. Ordem: env LOUVOR_DB → novo (se existir) →
    legado (se existir) → novo (destino canônico, útil para criar).

    `criar_dir`: garante a pasta-mãe do destino (para escrita/criação)."""
    env = os.environ.get("LOUVOR_DB")
    if env:
        cam = Path(env)
    else:
        raiz = raiz_drive()
        novo, legado = raiz / _DB_NOVO, raiz / _DB_LEGADO
        cam = novo if novo.exists() else (legado if legado.exists() else novo)
    if criar_dir:
        cam.parent.mkdir(parents=True, exist_ok=True)
    return cam


def achar_louvor_db() -> Path | None:
    """Só devolve o caminho se o banco EXISTE (para leitura). Senão None (ok=false)."""
    cam = louvor_db_path()
    return cam if cam.exists() else None


# ── assets do Ministério (logo + saída de PNG) ───────────────────────────────────
def ministerio_dir() -> Path:
    """Pasta 'Ministério de Louvor' (logo, escalas geradas). Env LOUVOR_ASSETS →
    novo (se existir) → legado (se existir) → legado (padrão pré-F6)."""
    env = os.environ.get("LOUVOR_ASSETS")
    if env:
        return Path(env)
    raiz = raiz_drive()
    novo, legado = raiz / _ASSETS_NOVO, raiz / _ASSETS_LEGADO
    if novo.exists():
        return novo
    return legado if legado.exists() else legado


def saida_imagens() -> Path:
    return ministerio_dir() / _SAIDA_IMG


def achar_logo() -> Path | None:
    base = ministerio_dir()
    if not base.exists():
        return None
    for achado in base.rglob(_LOGO_NOME):
        return achado
    return None


# ── ferramentas e ponte transversal ──────────────────────────────────────────────
def ferramentas_dir() -> Path:
    """A pasta local `gestao/ferramentas/` (onde vivem louvor_db.py, gerar_imagem…)."""
    return Path(__file__).resolve().parent / "ferramentas"


def sistema_ferramentas_dir() -> Path | None:
    """Ponte SANCIONADA: `0. Sistema/ferramentas/` no Drive, onde mora o cliente
    compartilhado da Google Agenda (`calendario_painel`). Usado só pela sync de agenda."""
    d = raiz_drive() / "0. Sistema" / "ferramentas"
    return d if d.is_dir() else None
