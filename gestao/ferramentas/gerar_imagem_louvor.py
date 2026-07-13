# Copyright (c) 2026 Diego Vilela. Uso pessoal/ministério — ecossistema Vilela (lado Deiso: igreja).
"""Gera a IMAGEM semanal do Louvor (escala do domingo + jejum) para o grupo do WhatsApp.

Lê o SSOT `3. Igreja\\00. _Gestão\\louvor.db` (mesmo banco da Central) e renderiza
um PNG vertical (amigável p/ WhatsApp) com a escala do próximo domingo e o jejum da
semana. Reflete sempre a escala atual (edições feitas na tela aparecem aqui). Sem
API paga (Pillow local).

Uso:
    py gerar_imagem_louvor.py            # gera a imagem da próxima semana
    py gerar_imagem_louvor.py 2026-07-05 # força o domingo-alvo

Ideal rodar no início da semana (tarefa do Agendador) e enviar no grupo.
"""
import sys
from datetime import date, datetime, timedelta
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

sys.path.insert(0, str(Path(__file__).resolve().parent))
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))  # gestao/ (caminhos)
import louvor_db as ldb
import caminhos

# Assets do Ministério (logo + saída dos PNGs) resolvidos pelo SSOT caminhos.py:
# hoje em '3. Igreja/…', migram para 'Vilela Igreja/…' na F6 sem tocar neste arquivo.
# O logo é achado por NOME (anti-drift) dentro da pasta do Ministério.
SAIDA_DIR = caminhos.saida_imagens()


def _achar_logo() -> Path | None:
    return caminhos.achar_logo()

# posições renderizadas (ordem): label exibido + chave na escala.
POSICOES = [(ldb.POSICOES_LABEL[p], p) for p in ldb.POSICOES]
CORE = set(ldb.POSICOES_CORE)

# Paleta
AZUL = (23, 42, 70)
AZUL_CLARO = (37, 99, 135)
DOURADO = (212, 175, 55)
BRANCO = (255, 255, 255)
CINZA = (90, 90, 90)
CINZA_CLARO = (150, 150, 150)
W, H = 1080, 1350

MESES = ["", "janeiro", "fevereiro", "março", "abril", "maio", "junho",
         "julho", "agosto", "setembro", "outubro", "novembro", "dezembro"]


def _fonte(tam, bold=False):
    cam = "C:/Windows/Fonts/" + ("arialbd.ttf" if bold else "arial.ttf")
    try:
        return ImageFont.truetype(cam, tam)
    except OSError:
        return ImageFont.load_default()


def _proximo_domingo(hoje: date) -> date:
    return hoje + timedelta(days=(6 - hoje.weekday()) % 7)


def _escala_do_domingo(con, dom: date) -> list[tuple[str, str]]:
    """Lista (label, quem) para o domingo: posições preenchidas + core vazias ('a escalar')."""
    row = con.execute("SELECT * FROM escala WHERE data=?", (dom.isoformat(),)).fetchone()
    ev = dict(row) if row else {}
    linhas = []
    for label, chave in POSICOES:
        quem = str(ev.get(chave, "") or "").strip()
        if quem or chave in CORE:
            linhas.append((label, quem))
    return linhas


def _jejum_da_semana(con, hoje: date) -> str:
    seg = hoje - timedelta(days=hoje.weekday())  # segunda desta semana
    dom = seg + timedelta(days=6)
    row = con.execute(
        "SELECT pessoas FROM jejum WHERE inicio <= ? AND fim >= ? ORDER BY inicio LIMIT 1",
        (dom.isoformat(), seg.isoformat())).fetchone()
    return row["pessoas"] if row else ""


def _centro(draw, txt, fonte, y, cor, x0=0, x1=W):
    b = draw.textbbox((0, 0), txt, font=fonte)
    draw.text(((x0 + x1 - (b[2] - b[0])) / 2, y), txt, font=fonte, fill=cor)


def gerar(con, dom: date) -> Path:
    linhas = _escala_do_domingo(con, dom)
    jejum = _jejum_da_semana(con, date.today())

    img = Image.new("RGB", (W, H), BRANCO)
    d = ImageDraw.Draw(img)

    # cabeçalho
    d.rectangle([0, 0, W, 300], fill=AZUL)
    d.rectangle([0, 300, W, 308], fill=DOURADO)
    logo_cam = _achar_logo()
    if logo_cam:
        try:
            logo = Image.open(logo_cam).convert("RGBA")
            logo.thumbnail((150, 150))
            img.paste(logo, (int((W - logo.width) / 2), 28), logo)
        except (OSError, FileNotFoundError):
            pass
    _centro(d, "MINISTÉRIO DE LOUVOR", _fonte(46, True), 190, BRANCO)
    _centro(d, "PIB Oliveira", _fonte(30), 246, DOURADO)

    # título da escala
    _centro(d, "ESCALA DA SEMANA", _fonte(40, True), 350, AZUL)
    dia_txt = f"Domingo, {dom.day:02d} de {MESES[dom.month]}"
    _centro(d, dia_txt, _fonte(30), 408, AZUL_CLARO)

    # linhas da escala
    y = 480
    fpos, fnome = _fonte(30, True), _fonte(30)
    for label, quem in linhas:
        d.text((110, y), f"{label}:", font=fpos, fill=AZUL)
        if quem:
            d.text((500, y), quem, font=fnome, fill=(20, 20, 20))
        else:
            d.text((500, y), "a escalar", font=fnome, fill=CINZA_CLARO)
        y += 58
    d.line([90, y + 8, W - 90, y + 8], fill=(225, 225, 225), width=2)

    # jejum
    y += 36
    d.rectangle([90, y, W - 90, y + 120], fill=(245, 247, 250))
    d.text((110, y + 18), "Jejum desta semana", font=_fonte(28, True), fill=AZUL)
    txt_j = jejum if jejum else "a definir"
    d.text((110, y + 64), txt_j, font=_fonte(28), fill=(40, 40, 40))

    # rodapé
    _centro(d, f"Gerado em {date.today().day:02d}/{date.today().month:02d}  •  que Deus abençoe o nosso louvor",
            _fonte(22), H - 70, CINZA)

    SAIDA_DIR.mkdir(parents=True, exist_ok=True)
    destino = SAIDA_DIR / f"escala-louvor-{dom.isoformat()}.png"
    img.save(destino, "PNG")
    return destino


def main(argv):
    if argv and not argv[0].startswith("-"):
        dom = datetime.strptime(argv[0], "%Y-%m-%d").date()
    else:
        dom = _proximo_domingo(date.today())
    con = ldb.conectar(ro=True)
    if not con:
        print(f"(sem banco: {ldb.DB_REL} — rode: py louvor_db.py)")
        return 1
    try:
        destino = gerar(con, dom)
    finally:
        con.close()
    print(f"Imagem gerada: {destino}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
