import sys
import pandas as pd
import sqlalchemy
from datetime import datetime
import os

print("Argumentos recebidos:", sys.argv)
if len(sys.argv) > 1:
    loja_id = int(sys.argv[1])
    print("Loja ID detectado:", loja_id)
else:
    loja_id = 1
    print("Usando fallback:", loja_id)

# Pega o ID da loja passado pelo PHP
if len(sys.argv) > 1:
    loja_id = int(sys.argv[1])
else:
    loja_id = 1  # fallback para testes

# Conexão MySQL (senha com caractere especial escapado)
DB_URI = "mysql+pymysql://root:Home%40spSENAI2025!@localhost:3306/decklog_db"
engine = sqlalchemy.create_engine(DB_URI)

# --- Funções ---
def carregar_vendas(loja_id):
    query = f"""
        SELECT data_venda, SUM(valor_total) AS valor_dia
        FROM vendas
        WHERE loja_id = {loja_id}
        GROUP BY data_venda
        ORDER BY data_venda
    """
    df = pd.read_sql(query, engine)
    df['data_venda'] = pd.to_datetime(df['data_venda'])
    return df

def detectar_anomalias(df, janela=7, z_thresh=1):
    df = df.copy()
    df['media'] = df['valor_dia'].rolling(window=janela, min_periods=3).mean()
    df['desvio'] = df['valor_dia'].rolling(window=janela, min_periods=1).std().fillna(0)
    df['zscore'] = (df['valor_dia'] - df['media']) / (df['desvio'] + 1e-9)
    df['anomalia'] = df['zscore'].abs() > z_thresh
    return df

def salvar_anomalias(df, loja_id):
    insert_sql = """
        INSERT INTO anomalias (loja_id, produto_id, tipo_anomalia, data_ocorrencia, detalhe, score, metodo)
        VALUES (:loja_id, NULL, :tipo_anomalia, :data_ocorrencia, :detalhe, :score, :metodo)
    """
    anomalias = df[df['anomalia']]
    if anomalias.empty:
        print("Nenhuma anomalia encontrada.")
        return

    params = []
    for _, r in anomalias.iterrows():
        params.append({
            'loja_id': loja_id,
            'tipo_anomalia': 'vendas_acima_abaixo',
            'data_ocorrencia': r['data_venda'].date(),
            'detalhe': f"Venda {r['valor_dia']}, média {r['media']:.2f}",
            'score': float(r['zscore']),
            'metodo': 'zscore'
        })

    with engine.begin() as conn:
        conn.execute(sqlalchemy.text(insert_sql), params)
    print(f"{len(params)} anomalias salvas.")

# --- Execução ---
vendas = carregar_vendas(loja_id)
analisado = detectar_anomalias(vendas, janela=7, z_thresh=1)
salvar_anomalias(analisado, loja_id)

print(analisado.tail(10))
