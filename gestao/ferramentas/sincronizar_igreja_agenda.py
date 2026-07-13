# Copyright (c) 2026 Diego Vilela.
"""sincronizar_igreja_agenda.py — Lê as escalas da Igreja (Louvor e EBD) e agenda no Google.

Puxa os próximos cultos e aulas e joga no calendário, marcando "[ESCALADO]" se o nome
"Diego" (ou outro configurável) estiver na escala. Utiliza a cor ROXA (colorId 3).

Evita duplicidade checando o cache local atual da agenda (_Saida/agenda/_agenda.json).
"""
import json
import sqlite3
import sys
from datetime import date, datetime, timedelta
from pathlib import Path

DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(DIR))
sys.path.insert(0, str(DIR.parent))  # gestao/ (caminhos)
import caminhos

# Ponte SANCIONADA (cross-ecossistema): o cliente da Google Agenda (calendario_painel)
# e o seu token vivem em '0. Sistema/ferramentas' (transversal). A sync de agenda é
# inerentemente transversal — junta Louvor (migrou p/ cá) + EBD (fica na Central) + Google.
_sf = caminhos.sistema_ferramentas_dir()
if _sf:
    sys.path.insert(0, str(_sf))
try:
    import calendario_painel
except ImportError:
    calendario_painel = None

NOME_ALVO = "Diego"
COR_IGREJA = "3" # Roxo no Google Calendar

def _ler_cache_agenda() -> list[dict]:
    cam = calendario_painel.OUT_DIR / "_agenda.json"
    if not cam.exists():
        return []
    try:
        dados = json.loads(cam.read_text(encoding="utf-8"))
        return dados.get("eventos", [])
    except Exception:
        return []

def evento_existe(eventos_agenda: list[dict], data_iso: str, titulo: str) -> bool:
    for e in eventos_agenda:
        # A data pode estar em e["data"] ou no e["inicio"]
        e_data = e.get("data") or (e.get("inicio", "").split("T")[0])
        if e_data == data_iso and e.get("titulo") == titulo:
            return True
    return False

def sincronizar_louvor(agenda_cache: list[dict]):
    db_cam = caminhos.achar_louvor_db()
    if not db_cam:
        print("louvor.db não encontrado.")
        return 0

    con = sqlite3.connect(f"file:{db_cam}?mode=ro", uri=True)
    con.row_factory = sqlite3.Row
    hoje = date.today().isoformat()
    
    criados = 0
    try:
        escala = con.execute("SELECT * FROM escala WHERE data >= ? ORDER BY data", (hoje,)).fetchall()
        for cult in escala:
            data_culto = cult["data"]
            evento_nome = cult["evento"] or "Culto"
            
            # Checa se o alvo está escalado
            escalados = [cult[pos] for pos in ["voz1", "voz2", "violao", "teclado", "baixo", "guitarra", "bateria"] if cult[pos]]
            ta_escalado = NOME_ALVO in escalados
            
            titulo = f"{evento_nome} - PIB Oliveira"
            if ta_escalado:
                titulo = f"[ESCALADO] {titulo}"
                
            if evento_existe(agenda_cache, data_culto, titulo):
                continue
                
            print(f"Agendando: {titulo} para {data_culto}")
            # Assume 19:00 como default para culto
            inicio_iso = f"{data_culto}T19:00"
            fim_iso = f"{data_culto}T21:00"
            
            descricao = f"Equipe: {', '.join(escalados)}" if escalados else "Equipe a definir"
            if cult["obs"]:
                descricao += f"\nObs: {cult['obs']}"
                
            calendario_painel.criar_evento(
                titulo=titulo,
                inicio_iso=inicio_iso,
                fim_iso=fim_iso,
                local="PIB Oliveira",
                descricao=descricao,
                color_id=COR_IGREJA
            )
            criados += 1
            # Atualiza o cache falso para não duplicar no mesmo run
            agenda_cache.append({"data": data_culto, "titulo": titulo})
    except Exception as e:
        if "sem token" in str(e).lower() or "invalid_grant" in str(e).lower() or "insufficient permission" in str(e).lower() or "forbidden" in str(e).lower():
            print("AVISO: Permissão de escrita na Agenda revogada. Rode 'py calendario_painel.py --login'")
        else:
            print(f"Erro ao sincronizar louvor: {e}")
    finally:
        con.close()
        
    return criados

def sincronizar_ebd(agenda_cache: list[dict]):
    # EBD fica na Central (não migra na F4); resolvido relativo à raiz do Drive.
    json_cam = caminhos.raiz_drive() / "3. Igreja" / "00. _Gestão" / "ebd.json"
    if not json_cam.exists():
        print("ebd.json não encontrado.")
        return 0

    try:
        dados = json.loads(json_cam.read_text(encoding="utf-8"))
        aula = dados.get("aula", {})
        hora = aula.get("hora", "09:00")
        
        # Encontra o próximo domingo
        hoje = date.today()
        delta = (6 - hoje.weekday()) % 7
        prox_domingo = hoje + timedelta(days=delta)
        
        titulo = "EBD - PIB Oliveira"
        if evento_existe(agenda_cache, prox_domingo.isoformat(), titulo):
            return 0
            
        print(f"Agendando: {titulo} para {prox_domingo.isoformat()}")
        inicio_iso = f"{prox_domingo.isoformat()}T{hora}"
        # Fim 1h30 depois
        try:
            hh, mm = map(int, hora.split(":"))
            dt_inicio = datetime.combine(prox_domingo, datetime.min.time().replace(hour=hh, minute=mm))
            dt_fim = dt_inicio + timedelta(hours=1, minutes=30)
            fim_iso = dt_fim.strftime("%Y-%m-%dT%H:%M")
        except:
            fim_iso = f"{prox_domingo.isoformat()}T10:30"
            
        calendario_painel.criar_evento(
            titulo=titulo,
            inicio_iso=inicio_iso,
            fim_iso=fim_iso,
            local="PIB Oliveira",
            descricao=f"Série: {dados.get('serie_atual', '')}",
            color_id=COR_IGREJA
        )
        return 1
    except Exception as e:
        if "sem token" in str(e).lower() or "invalid_grant" in str(e).lower() or "insufficient permission" in str(e).lower() or "forbidden" in str(e).lower():
            print("AVISO: Permissão de escrita na Agenda revogada. Rode 'py calendario_painel.py --login'")
        else:
            print(f"Erro ao sincronizar EBD: {e}")
        return 0

def main():
    if calendario_painel is None:
        print("AVISO: calendario_painel não encontrado (0. Sistema/ferramentas). "
              "A sync de agenda depende do cliente Google compartilhado — pulei.")
        return
    print("Sincronizando Igreja -> Google Agenda...")
    cache = _ler_cache_agenda()
    
    n_louvor = sincronizar_louvor(cache)
    n_ebd = sincronizar_ebd(cache)
    
    print(f"Sincronização concluída! {n_louvor} cultos e {n_ebd} EBD agendados.")
    
    if n_louvor > 0 or n_ebd > 0:
        print("Atualizando cache da agenda local...")
        try:
            dados = calendario_painel.espelhar(30, interactive=False)
            out_dir = calendario_painel.OUT_DIR
            out_dir.mkdir(parents=True, exist_ok=True)
            (out_dir / "_agenda.json").write_text(json.dumps(dados, ensure_ascii=False, indent=2), encoding="utf-8")
        except Exception as e:
            pass

if __name__ == "__main__":
    main()
