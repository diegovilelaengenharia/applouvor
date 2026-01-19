# -*- coding: utf-8 -*-
import pandas as pd
import mysql.connector
from mysql.connector import Error
import sys

# Forar encoding UTF-8
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# Configuraes do banco Hostinger
config = {
    'host': 'srv1074.hstgr.io',
    'database': 'u884436813_applouvor',
    'user': 'u884436813_admin',
    'password': 'Diego@159753'
}

connection = None

try:
    # Conectar ao banco
    print("[*] Conectando ao banco de dados...")
    connection = mysql.connector.connect(**config)
    cursor = connection.cursor()
    
    # 1. Atualizar estrutura da tabela
    print("\n[*] Atualizando estrutura da tabela 'songs'...")
    
    alter_queries = [
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS bpm INT AFTER tone",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS duration VARCHAR(10) AFTER bpm",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS link_letra VARCHAR(500) AFTER category",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS link_cifra VARCHAR(500) AFTER link_letra",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS link_audio VARCHAR(500) AFTER link_cifra",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS link_video VARCHAR(500) AFTER link_audio",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS tags VARCHAR(255) AFTER link_video",
        "ALTER TABLE songs ADD COLUMN IF NOT EXISTS notes TEXT AFTER tags"
    ]
    
    for query in alter_queries:
        try:
            cursor.execute(query)
            print(f"   {query.split('ADD COLUMN')[1].split('AFTER')[0].strip()}")
        except Error as e:
            if 'Duplicate column' in str(e):
                print(f"    Coluna j existe")
            else:
                print(f"   Erro: {e}")
    
    connection.commit()
    print("\n Estrutura atualizada!")
    
    # 2. Ler Excel
    print("\n Lendo arquivo Excel...")
    df = pd.read_excel('banco de dados/Musicas_Louveapp_1768828036289.xlsx')
    df = df.fillna('')
    print(f" {len(df)} msicas encontradas")
    
    # 3. Importar msicas
    print("\n Importando msicas...")
    
    insert_query = """
        INSERT INTO songs (
            title, artist, tone, bpm, duration, category, 
            link_letra, link_cifra, link_audio, link_video, 
            tags, notes, created_at
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
    """
    
    imported = 0
    errors = 0
    
    for index, row in df.iterrows():
        try:
            title = str(row['nomeMusica']).strip()
            artist = str(row['nomeArtista']).strip()
            tone = str(row['tom']).strip() if row['tom'] else None
            bpm = int(row['bpm']) if row['bpm'] and str(row['bpm']).replace('.','').isdigit() else None
            duration = str(row['duracao']) if row['duracao'] else None
            category = str(row['classificacoes']).strip() if row['classificacoes'] else 'Louvor'
            link_letra = str(row['letra']).strip() if row['letra'] else None
            link_cifra = str(row['cifra']).strip() if row['cifra'] else None
            link_audio = str(row['audio']).strip() if row['audio'] else None
            link_video = str(row['video']).strip() if row['video'] else None
            notes = str(row['observacaoMusica']).strip() if row['observacaoMusica'] else None
            tags = 'Repertrio 2025'
            
            if not title or title == 'nan' or not artist or artist == 'nan':
                print(f"    Linha {index+2}: Msica sem ttulo ou artista - pulando")
                continue
            
            cursor.execute(insert_query, (
                title, artist, tone, bpm, duration, category,
                link_letra, link_cifra, link_audio, link_video,
                tags, notes
            ))
            
            imported += 1
            
            if imported % 20 == 0:
                print(f"   Importadas: {imported} msicas...")
                connection.commit()
                
        except Exception as e:
            errors += 1
            print(f"   Erro na msica '{title}': {e}")
    
    connection.commit()
    
    print("\n" + "="*50)
    print(" IMPORTAO CONCLUDA!")
    print(f" Total importado: {imported} msicas")
    print(f" Erros: {errors}")
    print(f"  Tag aplicada: 'Repertrio 2025'")
    print("="*50)
    
except Error as e:
    print(f"\n Erro de conexo: {e}")
    
finally:
    if connection.is_connected():
        cursor.close()
        connection.close()
        print("\n Conexo fechada")
