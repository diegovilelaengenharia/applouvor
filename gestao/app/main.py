# Copyright (c) 2026 Diego Vilela — applouvor (gestão do Ministério de Louvor).
"""main.py — app FastAPI da GESTÃO do Louvor (porta 8020, lado LÍDER).

Servido ISOLADO do PWA público (`site/`): aqui é o centro de comando do ministério
(escala, jejum, setlist, repertório, imagem do WhatsApp) que lê e ESCREVE o SSOT
`louvor.db`. Fronteira LGPD: dado de membros da igreja — nunca sai daqui.

Migrado da Central (cockpit) na F4 da cirurgia. O `caminhos.py` resolve o banco por
env `LOUVOR_DB` (default `Vilela Igreja/0. Máquina`, fallback legado `3. Igreja`).

Rodar:  py -m uvicorn app.main:app --port 8020    (de dentro de gestao/)
   ou:  py app/main.py
"""
import sys
from pathlib import Path

# Suporta rodar como script (py app/main.py) E como pacote (uvicorn app.main:app).
_GESTAO = Path(__file__).resolve().parent.parent
if str(_GESTAO) not in sys.path:
    sys.path.insert(0, str(_GESTAO))

from fastapi import FastAPI
from fastapi.responses import HTMLResponse
from fastapi.staticfiles import StaticFiles

from app.routers import louvor

FRONTEND = Path(__file__).resolve().parent / "frontend"

app = FastAPI(title="App Louvor — Gestão", version="0.1.0")
if (FRONTEND / "assets").is_dir():
    app.mount("/assets", StaticFiles(directory=str(FRONTEND / "assets")), name="assets")
app.include_router(louvor.router)


@app.get("/", response_class=HTMLResponse)
def home():
    """Serve o app da gestão (escala/jejum/imagem). Fallback: página mínima."""
    idx = FRONTEND / "index.html"
    if idx.exists():
        return idx.read_text(encoding="utf-8")
    return ("<h1>🎵 App Louvor — Gestão</h1>"
            "<p>Frontend em construção (F4). API viva em "
            "<a href='/api/louvor'>/api/louvor</a>.</p>")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=8020)
